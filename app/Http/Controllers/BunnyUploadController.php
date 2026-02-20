<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Video;
use App\Models\VideoAssignment;
use App\Models\VideoGroup;
use App\Services\BunnyStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BunnyUploadController extends Controller
{
    /**
     * Paso 1: Crea el video en Bunny y devuelve credenciales TUS al browser.
     * POST /api/upload/bunny/init
     */
    public function init(Request $request)
    {
        $org = auth()->user()->currentOrganization();

        $isClub = $org->isClub();

        $request->validate([
            'title' => 'required|string|max:255',
            'filename' => ['required', 'string', 'max:255', 'regex:/\.(mp4|mov|avi|webm|mkv)$/i'],
            'file_size' => 'required|integer|min:1|max:8589934592',
            'mime_type' => ['nullable', 'string', 'in:video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska,video/mpeg,video/x-m4v'],
            'category_id' => $isClub ? [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('organization_id', $org->id)),
            ] : 'nullable',
            'match_date' => 'required|date',
            'visibility_type' => 'nullable|in:public,forwards,backs,specific',
            'description' => 'nullable|string',
            'local_team_name' => 'nullable|string|max:255',
            'rival_team_id' => 'nullable|exists:rival_teams,id',
            'rival_team_name' => 'nullable|string|max:255',
            'tournament_id' => 'nullable|exists:tournaments,id',
            'division' => 'nullable|in:primera,intermedia,unica',
            'assigned_players' => 'nullable|array',
            'assigned_players.*' => [
                'exists:users,id',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $org->id),
            ],
            'assignment_notes' => 'nullable|string|max:1000',
            'is_master' => 'nullable|boolean',
            'master_video_id' => 'nullable|exists:videos,id',
            'camera_angle' => 'nullable|string|max:100',
        ]);

        // C-02: Limitar uploads simultáneos por usuario
        $pendingCount = Video::where('uploaded_by', auth()->id())
            ->where('bunny_status', 'pendingupload')
            ->count();
        if ($pendingCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Tenés demasiados uploads pendientes. Esperá a que terminen antes de subir otro.',
            ], 429);
        }

        try {
            $bunny = BunnyStreamService::forOrganization($org);

            // Crear video en Bunny y obtener credenciales TUS
            $upload = $bunny->createVideo($request->title);

            // Para asociaciones: auto-crear o encontrar el Club por nombre
            // para que los videos aparezcan en carpetas Torneo > Club
            $clubId = null;
            if ($request->filled('local_team_name') && ! $org->isClub()) {
                $club   = Club::firstOrCreate(['name' => trim($request->local_team_name)]);
                $clubId = $club->id;
            }

            // Crear registro en BD
            $video = Video::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => 'bunny:'.$upload['guid'],
                'file_name' => $request->filename,
                'file_size' => $request->file_size,
                'mime_type' => $request->input('mime_type', 'video/mp4'),
                'category_id' => $request->category_id,
                'match_date' => $request->match_date,
                'visibility_type' => $request->input('visibility_type', 'public'),
                'analyzed_team_name' => $request->local_team_name,
                'club_id'           => $clubId,
                'rival_team_id' => $request->rival_team_id,
                'rival_team_name' => $request->rival_team_name,
                'tournament_id' => $request->tournament_id,
                'division' => $request->division,
                'organization_id' => $org->id,
                'uploaded_by' => auth()->id(),
                'status' => 'pending',
                'processing_status' => 'pending',
                'bunny_video_id' => $upload['guid'],
                'bunny_status' => 'pendingupload',
            ]);

            // Asignaciones
            if ($request->filled('assigned_players')) {
                foreach ($request->assigned_players as $playerId) {
                    VideoAssignment::create([
                        'video_id' => $video->id,
                        'assigned_to' => $playerId,
                        'assigned_by' => auth()->id(),
                        'notes' => $request->assignment_notes,
                    ]);
                }
            }

            // Multi-ángulo: vincular master/slave
            $groupId = null;
            $isMaster = (bool) $request->input('is_master', true);

            if ($isMaster) {
                $group = VideoGroup::create([
                    'name' => null,
                    'organization_id' => $org->id,
                ]);
                $group->videos()->attach($video->id, [
                    'is_master' => true,
                    'camera_angle' => 'Master / Tribuna Central',
                    'is_synced' => true,
                    'sync_offset' => 0,
                ]);
                $groupId = $group->id;
            } elseif ($request->filled('master_video_id')) {
                $master = Video::find($request->master_video_id);
                if ($master) {
                    // Get or create group for master
                    $group = $master->videoGroups()->first();
                    if (!$group) {
                        $group = VideoGroup::create([
                            'name' => null,
                            'organization_id' => $org->id,
                        ]);
                        $group->videos()->attach($master->id, [
                            'is_master' => true,
                            'camera_angle' => 'Master / Tribuna Central',
                            'is_synced' => true,
                            'sync_offset' => 0,
                        ]);
                    }
                    $group->videos()->attach($video->id, [
                        'is_master' => false,
                        'camera_angle' => $request->input('camera_angle', 'Ángulo adicional'),
                        'is_synced' => false,
                        'sync_offset' => null,
                    ]);
                    $groupId = $group->id;

                    // Force is_master=false on videos table (bypasses $fillable restriction)
                    \DB::table('videos')->where('id', $video->id)->update(['is_master' => false]);
                }
            }

            Log::info('Bunny Stream upload initiated', [
                'video_id' => $video->id,
                'bunny_guid' => $upload['guid'],
                'user_id' => auth()->id(),
                'org_id' => $org->id,
                'group_id' => $groupId,
            ]);

            return response()->json([
                'success' => true,
                'video_id' => $video->id,
                'bunny_guid' => $upload['guid'],
                'upload_url' => $upload['upload_url'],
                'signature' => $upload['signature'],
                'expire' => $upload['expire'],
                'library_id' => $upload['library_id'],
                'group_id' => $groupId,
            ]);

        } catch (\Exception $e) {
            Log::error('Bunny upload init failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la subida. Por favor intentá de nuevo.',
            ], 500);
        }
    }

    /**
     * Paso 2: Browser avisa que terminó de subir via TUS.
     * POST /api/upload/bunny/complete
     */
    public function complete(Request $request)
    {
        $org = auth()->user()->currentOrganization();

        $request->validate([
            'video_id' => ['required', Rule::exists('videos', 'id')->where('organization_id', $org->id)],
            'bunny_guid' => 'required|string|uuid',
        ]);

        $video = Video::findOrFail($request->video_id);

        // S-01: Verificar ownership — solo el uploader de la misma org puede completar
        $org = auth()->user()->currentOrganization();
        abort_if(
            $video->organization_id !== $org?->id || $video->uploaded_by !== auth()->id(),
            403,
            'No autorizado'
        );

        if ($video->bunny_video_id !== $request->bunny_guid) {
            return response()->json(['success' => false, 'message' => 'GUID no coincide'], 400);
        }

        $bunny = BunnyStreamService::forOrganization($video->organization);
        $mp4Url = $bunny->getOriginalUrl($video->bunny_video_id);

        $video->update([
            'bunny_status' => 'queued',
            'bunny_mp4_url' => $mp4Url,
        ]);

        Log::info('Bunny Stream upload complete', [
            'video_id' => $video->id,
            'bunny_guid' => $video->bunny_video_id,
            'bunny_mp4_url' => $mp4Url,
        ]);

        return response()->json([
            'success' => true,
            'redirect_url' => route('videos.show', $video),
            'bunny_mp4_url' => $mp4Url,
        ]);
    }

    /**
     * Polling del estado de encoding en Bunny.
     * GET /api/upload/bunny/{video}/status
     */
    public function status(Video $video)
    {
        // S-02: Verificar que el video pertenece a la org actual
        abort_if($video->organization_id !== auth()->user()->currentOrganization()?->id, 403, 'No autorizado');

        if (! $video->bunny_video_id) {
            return response()->json(['success' => false, 'message' => 'Not a Bunny video'], 400);
        }

        $bunny = BunnyStreamService::forOrganization($video->organization);

        try {
            $details = $bunny->getVideoDetails($video->bunny_video_id);
        } catch (\Exception $e) {
            Log::error('Bunny getVideoDetails failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Error al obtener el estado del video.'], 500);
        }

        // Persistir cambios en BD (separado del response para no bloquear el frontend)
        if ($details['status'] !== $video->bunny_status) {
            $updates = ['bunny_status' => $details['status']];

            if ($details['ready']) {
                $updates['bunny_hls_url'] = $details['hls_url'];
                $updates['bunny_thumbnail'] = $details['thumbnail_url'];
                $updates['processing_status'] = 'completed';
                $updates['status'] = 'completed'; // ENUM: pending|processing|completed|archived
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
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'status' => $details['status'],
            'ready' => $details['ready'],
            'playback_url' => $details['hls_url'],
            'thumbnail' => $details['thumbnail_url'],
        ]);
    }
}
