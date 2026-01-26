<?php

namespace App\Http\Controllers;

use App\Jobs\CompressVideoJob;
use App\Models\Video;
use App\Models\VideoAssignment;
use App\Services\LongoMatchXmlParser;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DirectUploadController extends Controller
{
    protected LongoMatchXmlParser $xmlParser;

    public function __construct(LongoMatchXmlParser $xmlParser)
    {
        $this->xmlParser = $xmlParser;
    }

    /**
     * Validate LongoMatch XML content
     */
    public function validateXml(Request $request)
    {
        $request->validate([
            'xml_content' => 'required|string',
        ]);

        $result = $this->xmlParser->validate($request->xml_content);

        return response()->json($result);
    }

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

            // URL valid for 12 hours (enough for very large uploads)
            $presignedRequest = $client->createPresignedRequest($cmd, '+12 hours');
            $presignedUrl = (string) $presignedRequest->getUri();

            // Generate a unique upload ID to track this upload
            $uploadId = Str::uuid()->toString();

            // Store upload info in cache for later confirmation (12 hours for large uploads)
            cache()->put("direct_upload_{$uploadId}", [
                'key' => $key,
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'content_type' => $request->content_type,
                'file_size' => $request->file_size,
                'org_slug' => $orgSlug,
                'org_name' => $currentOrg ? $currentOrg->name : 'Mi Equipo',
                'user_id' => auth()->id(),
            ], now()->addHours(12));

            Log::info("Upload ID created: {$uploadId}", [
                'filename' => $filename,
                'size_mb' => round($request->file_size / 1024 / 1024, 2),
                'user_id' => auth()->id(),
            ]);

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
            'xml_content' => 'nullable|string', // LongoMatch XML content
        ]);

        // Retrieve upload info from cache
        $uploadInfo = cache()->get("direct_upload_{$request->upload_id}");

        if (!$uploadInfo) {
            Log::warning("Upload ID not found in cache: {$request->upload_id}", [
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);
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

            // Process LongoMatch XML if provided
            $xmlImportStats = null;
            if ($request->filled('xml_content')) {
                try {
                    $parsedData = $this->xmlParser->parse($request->xml_content);
                    $xmlImportStats = $this->xmlParser->importToVideo($video, $parsedData, true);
                    Log::info("LongoMatch XML imported for video {$video->id}", $xmlImportStats);
                } catch (\Exception $e) {
                    Log::warning("Failed to import LongoMatch XML for video {$video->id}: " . $e->getMessage());
                    // Don't fail the whole upload, just log the error
                }
            }

            // Clear cache
            cache()->forget("direct_upload_{$request->upload_id}");

            $successMessage = $this->getSuccessMessage($request->visibility_type, $request->assigned_players ?? []);

            $response = [
                'success' => true,
                'message' => $successMessage,
                'video_id' => $video->id,
                'redirect' => route('videos.show', $video),
            ];

            if ($xmlImportStats) {
                $response['xml_import'] = $xmlImportStats;
            }

            return response()->json($response);

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

    /**
     * Initiate multipart upload for large files
     */
    public function initiateMultipartUpload(Request $request)
    {
        $request->validate([
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string',
            'file_size' => 'required|integer|max:8589934592', // 8GB max
            'parts_count' => 'required|integer|min:1|max:10000',
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

            // Start multipart upload
            $result = $client->createMultipartUpload([
                'Bucket' => config('filesystems.disks.spaces.bucket'),
                'Key' => $key,
                'ContentType' => $request->content_type,
                'ACL' => 'public-read',
            ]);

            $uploadId = $result['UploadId'];
            $internalUploadId = Str::uuid()->toString();

            // Store upload info in cache
            cache()->put("multipart_upload_{$internalUploadId}", [
                's3_upload_id' => $uploadId,
                'key' => $key,
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'content_type' => $request->content_type,
                'file_size' => $request->file_size,
                'parts_count' => $request->parts_count,
                'org_slug' => $orgSlug,
                'org_name' => $currentOrg ? $currentOrg->name : 'Mi Equipo',
                'user_id' => auth()->id(),
                'completed_parts' => [],
            ], now()->addHours(24));

            Log::info("Multipart Upload initiated: {$internalUploadId}", [
                's3_upload_id' => $uploadId,
                'filename' => $filename,
                'size_mb' => round($request->file_size / 1024 / 1024, 2),
                'parts' => $request->parts_count,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'upload_id' => $internalUploadId,
                's3_upload_id' => $uploadId,
                'key' => $key,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initiate multipart upload: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error iniciando subida multipart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get presigned URLs for uploading parts
     */
    public function getPartUploadUrls(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'part_numbers' => 'required|array',
            'part_numbers.*' => 'integer|min:1|max:10000',
        ]);

        $uploadInfo = cache()->get("multipart_upload_{$request->upload_id}");

        if (!$uploadInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Upload ID no válido o expirado',
            ], 400);
        }

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

            $presignedUrls = [];

            foreach ($request->part_numbers as $partNumber) {
                $cmd = $client->getCommand('UploadPart', [
                    'Bucket' => config('filesystems.disks.spaces.bucket'),
                    'Key' => $uploadInfo['key'],
                    'UploadId' => $uploadInfo['s3_upload_id'],
                    'PartNumber' => $partNumber,
                ]);

                $presignedRequest = $client->createPresignedRequest($cmd, '+12 hours');
                $presignedUrls[$partNumber] = (string) $presignedRequest->getUri();
            }

            return response()->json([
                'success' => true,
                'urls' => $presignedUrls,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate part URLs: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error generando URLs de partes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete multipart upload
     */
    public function completeMultipartUpload(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'parts' => 'nullable|array',
            'parts.*.PartNumber' => 'integer',
            'parts.*.ETag' => 'string',
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
            'xml_content' => 'nullable|string',
        ]);

        $uploadInfo = cache()->get("multipart_upload_{$request->upload_id}");

        if (!$uploadInfo) {
            Log::warning("Multipart Upload ID not found in cache: {$request->upload_id}");
            return response()->json([
                'success' => false,
                'message' => 'Upload ID no válido o expirado',
            ], 400);
        }

        if ($uploadInfo['user_id'] !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

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

            // If parts not provided or incomplete (CORS issue), fetch from S3
            $parts = $request->parts ?? [];

            if (empty($parts) || !isset($parts[0]['ETag'])) {
                Log::info("ETags not provided by client, fetching from Spaces", [
                    'upload_id' => $request->upload_id,
                ]);

                // List all uploaded parts to get their ETags
                $listResult = $client->listParts([
                    'Bucket' => config('filesystems.disks.spaces.bucket'),
                    'Key' => $uploadInfo['key'],
                    'UploadId' => $uploadInfo['s3_upload_id'],
                ]);

                $parts = [];
                foreach ($listResult['Parts'] as $part) {
                    $parts[] = [
                        'PartNumber' => $part['PartNumber'],
                        'ETag' => $part['ETag'],
                    ];
                }

                Log::info("Retrieved {count} parts from Spaces", [
                    'count' => count($parts),
                ]);
            }

            // Complete the multipart upload
            $result = $client->completeMultipartUpload([
                'Bucket' => config('filesystems.disks.spaces.bucket'),
                'Key' => $uploadInfo['key'],
                'UploadId' => $uploadInfo['s3_upload_id'],
                'MultipartUpload' => [
                    'Parts' => $request->parts,
                ],
            ]);

            Log::info("Multipart upload completed on Spaces", [
                'upload_id' => $request->upload_id,
                'key' => $uploadInfo['key'],
                'parts_count' => count($request->parts),
            ]);

            // Ensure ACL is public-read
            $this->ensurePublicAcl($uploadInfo['key']);

            // Create video record
            $video = Video::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $uploadInfo['key'],
                'thumbnail_path' => null,
                'file_name' => $uploadInfo['filename'],
                'file_size' => $uploadInfo['file_size'],
                'mime_type' => $uploadInfo['content_type'],
                'uploaded_by' => auth()->id(),
                'analyzed_team_name' => $uploadInfo['org_name'],
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

            Log::info("Video {$video->id} created via multipart upload, compression job dispatched");

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

            // Process LongoMatch XML if provided
            $xmlImportStats = null;
            if ($request->filled('xml_content')) {
                try {
                    $parsedData = $this->xmlParser->parse($request->xml_content);
                    $xmlImportStats = $this->xmlParser->importToVideo($video, $parsedData, true);
                    Log::info("LongoMatch XML imported for video {$video->id}", $xmlImportStats);
                } catch (\Exception $e) {
                    Log::warning("Failed to import LongoMatch XML for video {$video->id}: " . $e->getMessage());
                }
            }

            // Clear cache
            cache()->forget("multipart_upload_{$request->upload_id}");

            $successMessage = $this->getSuccessMessage($request->visibility_type, $request->assigned_players ?? []);

            $response = [
                'success' => true,
                'message' => $successMessage,
                'video_id' => $video->id,
                'redirect' => route('videos.show', $video),
            ];

            if ($xmlImportStats) {
                $response['xml_import'] = $xmlImportStats;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Failed to complete multipart upload: ' . $e->getMessage());

            // Try to abort the multipart upload
            try {
                $client->abortMultipartUpload([
                    'Bucket' => config('filesystems.disks.spaces.bucket'),
                    'Key' => $uploadInfo['key'],
                    'UploadId' => $uploadInfo['s3_upload_id'],
                ]);
            } catch (\Exception $abortError) {
                Log::error('Failed to abort multipart upload: ' . $abortError->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Error completando subida: ' . $e->getMessage(),
            ], 500);
        }
    }
}
