<?php

namespace App\Http\Controllers;

use App\Jobs\CompressVideoJob;
use App\Models\Video;
use App\Models\VideoAssignment;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DirectUploadController extends Controller
{
    /**
     * Generate a pre-signed URL for direct upload to DigitalOcean Spaces
     */
    public function getPresignedUrl(Request $request)
    {
        $request->validate([
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string',
            'file_size' => 'required|integer|max:8589934592', // 8GB max
        ]);

        $originalFilename = $request->filename;

        // Sanitize filename
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $originalFilename);
        $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);
        $filename = time() . '_' . $sanitizedName;

        // Get organization slug for path
        $currentOrg = auth()->user()->currentOrganization();
        $orgSlug = $currentOrg ? $currentOrg->slug : 'default';

        $key = "videos/{$orgSlug}/{$filename}";

        try {
            $client = new S3Client([
                'version' => 'latest',
                'region' => config('filesystems.disks.spaces.region'),
                'endpoint' => config('filesystems.disks.spaces.endpoint'),
                'credentials' => [
                    'key' => config('filesystems.disks.spaces.key'),
                    'secret' => config('filesystems.disks.spaces.secret'),
                ],
            ]);

            $cmd = $client->getCommand('PutObject', [
                'Bucket' => config('filesystems.disks.spaces.bucket'),
                'Key' => $key,
                'ContentType' => $request->content_type,
                'ACL' => 'public-read',
            ]);

            // URL valid for 2 hours (enough for large uploads)
            $presignedRequest = $client->createPresignedRequest($cmd, '+2 hours');
            $presignedUrl = (string) $presignedRequest->getUri();

            // Generate a unique upload ID to track this upload
            $uploadId = Str::uuid()->toString();

            // Store upload info in cache for later confirmation
            cache()->put("direct_upload_{$uploadId}", [
                'key' => $key,
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'content_type' => $request->content_type,
                'file_size' => $request->file_size,
                'org_slug' => $orgSlug,
                'org_name' => $currentOrg ? $currentOrg->name : 'Mi Equipo',
                'user_id' => auth()->id(),
            ], now()->addHours(3));

            return response()->json([
                'success' => true,
                'upload_url' => $presignedUrl,
                'upload_id' => $uploadId,
                'key' => $key,
                'cdn_url' => config('filesystems.disks.spaces.url') . '/' . $key,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate pre-signed URL: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error generando URL de subida: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm upload completed and create video record
     */
    public function confirmUpload(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rival_team_name' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'division' => 'nullable|in:primera,intermedia,unica',
            'rugby_situation_id' => 'nullable|exists:rugby_situations,id',
            'match_date' => 'required|date',
            'assigned_players' => 'nullable|array',
            'assigned_players.*' => 'exists:users,id',
            'assignment_notes' => 'nullable|string|max:1000',
            'visibility_type' => 'required|in:public,forwards,backs,specific',
        ]);

        // Retrieve upload info from cache
        $uploadInfo = cache()->get("direct_upload_{$request->upload_id}");

        if (!$uploadInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Upload ID no válido o expirado',
            ], 400);
        }

        // Verify user
        if ($uploadInfo['user_id'] !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        try {
            // Ensure ACL is public-read (fallback in case client header didn't work)
            $this->ensurePublicAcl($uploadInfo['key']);

            $video = Video::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $uploadInfo['key'],
                'thumbnail_path' => null,
                'file_name' => $uploadInfo['filename'],
                'file_size' => $uploadInfo['file_size'],
                'mime_type' => $uploadInfo['content_type'],
                'uploaded_by' => auth()->id(),
                'analyzed_team_name' => $uploadInfo['org_name'], // Nombre de la organización
                'rival_team_name' => $request->rival_team_name,
                'category_id' => $request->category_id,
                'division' => $request->division,
                'rugby_situation_id' => $request->rugby_situation_id,
                'match_date' => $request->match_date,
                'status' => 'pending',
                'visibility_type' => $request->visibility_type,
                'processing_status' => 'pending',
            ]);

            // Dispatch compression job
            CompressVideoJob::dispatch($video->id);

            Log::info("Video {$video->id} created via direct upload, compression job dispatched");

            // Create assignments if visibility is 'specific'
            if ($request->visibility_type === 'specific' && $request->filled('assigned_players')) {
                foreach ($request->assigned_players as $playerId) {
                    VideoAssignment::create([
                        'video_id' => $video->id,
                        'assigned_to' => $playerId,
                        'assigned_by' => auth()->id(),
                        'notes' => $request->assignment_notes ?? 'Video asignado desde subida inicial.',
                    ]);
                }
            }

            // Clear cache
            cache()->forget("direct_upload_{$request->upload_id}");

            $successMessage = $this->getSuccessMessage($request->visibility_type, $request->assigned_players ?? []);

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'video_id' => $video->id,
                'redirect' => route('videos.show', $video),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to confirm upload: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creando registro de video: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getSuccessMessage(string $visibilityType, array $assignedPlayers): string
    {
        if ($visibilityType === 'specific' && count($assignedPlayers) > 0) {
            $count = count($assignedPlayers);
            return "Video subido exitosamente y asignado a {$count} jugador(es). Se está comprimiendo en segundo plano.";
        }

        $messages = [
            'public' => 'Video subido exitosamente y visible para todo el equipo. Se está comprimiendo en segundo plano.',
            'forwards' => 'Video subido exitosamente y visible para delanteros. Se está comprimiendo en segundo plano.',
            'backs' => 'Video subido exitosamente y visible para backs. Se está comprimiendo en segundo plano.',
            'specific' => 'Video subido exitosamente. Se está comprimiendo en segundo plano.',
        ];

        return $messages[$visibilityType] ?? 'Video subido exitosamente.';
    }

    /**
     * Ensure the uploaded file has public-read ACL
     */
    private function ensurePublicAcl(string $key): void
    {
        try {
            $client = new S3Client([
                'version' => 'latest',
                'region' => config('filesystems.disks.spaces.region'),
                'endpoint' => config('filesystems.disks.spaces.endpoint'),
                'credentials' => [
                    'key' => config('filesystems.disks.spaces.key'),
                    'secret' => config('filesystems.disks.spaces.secret'),
                ],
            ]);

            $client->putObjectAcl([
                'Bucket' => config('filesystems.disks.spaces.bucket'),
                'Key' => $key,
                'ACL' => 'public-read',
            ]);

            Log::info("ACL set to public-read for: {$key}");
        } catch (\Exception $e) {
            Log::warning("Failed to set ACL for {$key}: " . $e->getMessage());
        }
    }
}
