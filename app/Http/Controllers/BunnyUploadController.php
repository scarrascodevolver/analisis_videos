<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoAssignment;
use App\Models\VideoGroup;
use App\Services\BunnyStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BunnyUploadController extends Controller
{
    public function __construct(private BunnyStreamService $bunny) {}

    /**
     * Paso 1: Crea el video en Bunny y devuelve credenciales TUS al browser.
     * POST /api/upload/bunny/init
     */
    public function init(Request $request)
    {
        $currentOrg = auth()->user()->currentOrganization();

        $request->validate([
            'title'              => 'required|string|max:255',
            'filename'           => 'required|string|max:255',
            'file_size'          => 'required|integer|min:1',
            'mime_type'          => 'nullable|string|max:100',
            'category_id'        => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('organization_id', $currentOrg->id)),
            ],
            'match_date'         => 'required|date',
            'visibility_type'    => 'required|in:public,forwards,backs,specific',
            'description'        => 'nullable|string',
            'local_team_name'    => 'nullable|string|max:255',
            'rival_team_id'      => 'nullable|exists:rival_teams,id',
            'rival_team_name'    => 'nullable|string|max:255',
            'tournament_id'      => 'nullable|exists:tournaments,id',
            'division'           => 'nullable|in:primera,intermedia,unica',
            'assigned_players'   => 'nullable|array',
            'assigned_players.*' => 'exists:users,id',
            'assignment_notes'   => 'nullable|string|max:1000',
            'is_master'          => 'nullable|boolean',
            'master_video_id'    => 'nullable|exists:videos,id',
            'camera_angle'       => 'nullable|string|max:100',
        ]);

        try {
            // Crear video en Bunny y obtener credenciales TUS
            $upload = $this->bunny->createVideo($request->title);

            // Crear registro en BD
            $video = Video::create([
                'title'             => $request->title,
                'description'       => $request->description,
                'file_path'         => 'bunny:' . $upload['guid'],
                'file_name'         => $request->filename,
                'file_size'         => $request->file_size,
                'mime_type'         => $request->input('mime_type', 'video/mp4'),
                'category_id'       => $request->category_id,
                'match_date'        => $request->match_date,
                'visibility_type'   => $request->visibility_type,
                'analyzed_team_name' => $request->local_team_name,
                'rival_team_id'     => $request->rival_team_id,
                'rival_team_name'   => $request->rival_team_name,
                'tournament_id'     => $request->tournament_id,
                'division'          => $request->division,
                'organization_id'   => $currentOrg->id,
                'uploaded_by'       => auth()->id(),
                'status'            => 'pending',
                'processing_status' => 'pending',
                'bunny_video_id'    => $upload['guid'],
                'bunny_status'      => 'pendingupload',
            ]);

            // Asignaciones
            if ($request->filled('assigned_players')) {
                foreach ($request->assigned_players as $playerId) {
                    VideoAssignment::create([
                        'video_id'    => $video->id,
                        'assigned_to' => $playerId,
                        'assigned_by' => auth()->id(),
                        'notes'       => $request->assignment_notes,
                    ]);
                }
            }

            // Multi-ángulo: vincular master/slave
            $groupId  = null;
            $isMaster = (bool) $request->input('is_master', true);

            if ($isMaster) {
                $group = VideoGroup::create([
                    'name'            => null,
                    'organization_id' => $currentOrg->id,
                ]);
                $group->videos()->attach($video->id, [
                    'is_master'    => true,
                    'camera_angle' => 'Master / Tribuna Central',
                    'is_synced'    => true,
                    'sync_offset'  => 0,
                ]);
                $groupId = $group->id;
            } elseif ($request->filled('master_video_id')) {
                $master = Video::find($request->master_video_id);
                if ($master) {
                    $group = $master->videoGroups()->first();
                    if ($group) {
                        $group->videos()->attach($video->id, [
                            'is_master'    => false,
                            'camera_angle' => $request->input('camera_angle', 'Ángulo adicional'),
                            'is_synced'    => false,
                            'sync_offset'  => null,
                        ]);
                        $groupId = $group->id;
                    }
                }
            }

            Log::info('Bunny Stream upload initiated', [
                'video_id'      => $video->id,
                'bunny_guid'    => $upload['guid'],
                'user_id'       => auth()->id(),
                'group_id'      => $groupId,
            ]);

            return response()->json([
                'success'    => true,
                'video_id'   => $video->id,
                'bunny_guid' => $upload['guid'],
                'upload_url' => $upload['upload_url'],
                'signature'  => $upload['signature'],
                'expire'     => $upload['expire'],
                'library_id' => $upload['library_id'],
                'group_id'   => $groupId,
            ]);

        } catch (\Exception $e) {
            Log::error('Bunny upload init failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la subida: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Paso 2: Browser avisa que terminó de subir via TUS.
     * POST /api/upload/bunny/complete
     */
    public function complete(Request $request)
    {
        $request->validate([
            'video_id'   => 'required|exists:videos,id',
            'bunny_guid' => 'required|string',
        ]);

        $video = Video::findOrFail($request->video_id);

        if ($video->bunny_video_id !== $request->bunny_guid) {
            return response()->json(['success' => false, 'message' => 'GUID no coincide'], 400);
        }

        $mp4Url = $this->bunny->getOriginalUrl($video->bunny_video_id);

        $video->update([
            'bunny_status'  => 'queued',
            'bunny_mp4_url' => $mp4Url,
        ]);

        Log::info('Bunny Stream upload complete', [
            'video_id'      => $video->id,
            'bunny_guid'    => $video->bunny_video_id,
            'bunny_mp4_url' => $mp4Url,
        ]);

        return response()->json([
            'success'       => true,
            'redirect_url'  => route('videos.show', $video),
            'bunny_mp4_url' => $mp4Url,
        ]);
    }

    /**
     * Polling del estado de encoding en Bunny.
     * GET /api/upload/bunny/{video}/status
     */
    public function status(Video $video)
    {
        if (! $video->bunny_video_id) {
            return response()->json(['success' => false, 'message' => 'Not a Bunny video'], 400);
        }

        try {
            $details = $this->bunny->getVideoDetails($video->bunny_video_id);
        } catch (\Exception $e) {
            Log::error('Bunny getVideoDetails failed', [
                'video_id' => $video->id,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        // Persistir cambios en BD (separado del response para no bloquear el frontend)
        if ($details['status'] !== $video->bunny_status) {
            $updates = ['bunny_status' => $details['status']];

            if ($details['ready']) {
                $updates['bunny_hls_url']     = $details['hls_url'];
                $updates['bunny_thumbnail']   = $details['thumbnail_url'];
                $updates['processing_status'] = 'completed';
                $updates['status']            = 'completed'; // ENUM: pending|processing|completed|archived
                if ($details['duration']) {
                    $updates['duration'] = (int) $details['duration'];
                }
            }

            if ($details['status'] === 'error') {
                $updates['processing_status'] = 'failed';
            }

            try {
                $video->update($updates);
            } catch (\Exception $e) {
                // Log pero no bloquear: el frontend igual recibe el status correcto
                Log::warning('Bunny status DB update failed (non-critical)', [
                    'video_id' => $video->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success'      => true,
            'status'       => $details['status'],
            'ready'        => $details['ready'],
            'playback_url' => $details['hls_url'],
            'thumbnail'    => $details['thumbnail_url'],
        ]);
    }
}
