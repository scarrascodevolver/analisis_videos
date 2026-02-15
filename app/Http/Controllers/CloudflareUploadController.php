<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoAssignment;
use App\Services\CloudflareStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CloudflareUploadController extends Controller
{
    public function __construct(private CloudflareStreamService $stream) {}

    /**
     * Paso 1: El browser pide un endpoint TUS a Cloudflare.
     * Crea el registro de video en BD y devuelve la URL de upload.
     *
     * POST /api/upload/cloudflare/init
     */
    public function init(Request $request)
    {
        $currentOrg = auth()->user()->currentOrganization();

        $request->validate([
            'title'            => 'required|string|max:255',
            'filename'         => 'required|string|max:255',
            'file_size'        => 'required|integer|min:1',
            'category_id'      => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('organization_id', $currentOrg->id)),
            ],
            'match_date'       => 'required|date',
            'visibility_type'  => 'required|in:public,forwards,backs,specific',
            'description'      => 'nullable|string',
            'rival_team_name'  => 'nullable|string|max:255',
            'division'         => 'nullable|in:primera,intermedia,unica',
            'assigned_players' => 'nullable|array',
            'assigned_players.*' => 'exists:users,id',
            'assignment_notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Pedir endpoint TUS a Cloudflare
            $upload = $this->stream->createDirectUpload(
                maxDurationSeconds: 43200, // 12 horas máximo
                videoTitle: $request->title,
                meta: ['file_size' => $request->file_size]
            );

            // Crear registro en BD
            $video = Video::create([
                'title'            => $request->title,
                'description'      => $request->description,
                'file_path'        => 'cloudflare:' . $upload['uid'],
                'file_name'        => $request->filename,
                'file_size'        => $request->file_size,
                'category_id'      => $request->category_id,
                'match_date'       => $request->match_date,
                'visibility_type'  => $request->visibility_type,
                'rival_team_name'  => $request->rival_team_name,
                'division'         => $request->division,
                'organization_id'  => $currentOrg->id,
                'user_id'          => auth()->id(),
                'status'           => 'pending',
                'processing_status' => 'pending',
                'cloudflare_uid'    => $upload['uid'],
                'cloudflare_status' => 'pendingupload',
            ]);

            // Asignaciones a jugadores
            if ($request->filled('assigned_players')) {
                foreach ($request->assigned_players as $playerId) {
                    VideoAssignment::create([
                        'video_id'          => $video->id,
                        'user_id'           => $playerId,
                        'assigned_by'       => auth()->id(),
                        'assignment_notes'  => $request->assignment_notes,
                        'organization_id'   => $currentOrg->id,
                    ]);
                }
            }

            Log::info("Cloudflare Stream upload initiated", [
                'video_id'       => $video->id,
                'cloudflare_uid' => $upload['uid'],
                'user_id'        => auth()->id(),
            ]);

            return response()->json([
                'success'        => true,
                'video_id'       => $video->id,
                'cloudflare_uid' => $upload['uid'],
                'upload_url'     => $upload['upload_url'],
            ]);

        } catch (\Exception $e) {
            Log::error('Cloudflare upload init failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la subida: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Paso 2: El browser avisa que terminó de subir.
     * Actualiza el estado del video a 'queued' (Cloudflare está procesando).
     *
     * POST /api/upload/cloudflare/complete
     */
    public function complete(Request $request)
    {
        $request->validate([
            'video_id'       => 'required|exists:videos,id',
            'cloudflare_uid' => 'required|string',
        ]);

        $video = Video::findOrFail($request->video_id);

        if ($video->cloudflare_uid !== $request->cloudflare_uid) {
            return response()->json(['success' => false, 'message' => 'UID no coincide'], 400);
        }

        $video->update(['cloudflare_status' => 'queued']);

        Log::info("Cloudflare Stream upload complete", [
            'video_id'       => $video->id,
            'cloudflare_uid' => $video->cloudflare_uid,
        ]);

        return response()->json([
            'success'      => true,
            'redirect_url' => route('videos.show', $video),
        ]);
    }

    /**
     * Consulta el estado actual del video en Cloudflare (para polling desde el frontend).
     *
     * GET /api/upload/cloudflare/{video}/status
     */
    public function status(Video $video)
    {
        if (! $video->cloudflare_uid) {
            return response()->json(['success' => false, 'message' => 'Not a Cloudflare video'], 400);
        }

        try {
            $details = $this->stream->getVideoDetails($video->cloudflare_uid);

            // Actualizar BD si cambió el estado
            if ($details['status'] !== $video->cloudflare_status) {
                $updates = ['cloudflare_status' => $details['status']];

                if ($details['ready']) {
                    $updates['cloudflare_playback_url'] = $details['hls_url'];
                    $updates['cloudflare_thumbnail']    = $details['thumbnail_url'];
                    $updates['processing_status']       = 'completed';
                    $updates['status']                  = 'active';

                    if ($details['duration']) {
                        $updates['duration'] = (int) $details['duration'];
                    }
                }

                if ($details['status'] === 'error') {
                    $updates['processing_status'] = 'failed';
                }

                $video->update($updates);
            }

            return response()->json([
                'success'     => true,
                'status'      => $details['status'],
                'ready'       => $details['ready'],
                'playback_url' => $details['hls_url'],
                'thumbnail'   => $details['thumbnail_url'],
            ]);

        } catch (\Exception $e) {
            Log::error('Cloudflare status check failed', [
                'video_id' => $video->id,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
