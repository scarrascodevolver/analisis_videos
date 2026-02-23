<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RugbySituation;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isStaff = in_array($user->role, ['analista', 'entrenador']);

        // Jugadores: lista plana de sus videos asignados (sin cambios)
        if (! $isStaff) {
            $query = Video::with(['category', 'uploader', 'rugbySituation', 'rivalTeam'])
                ->where('is_master', true)
                ->teamVisible($user);

            if ($request->filled('rugby_situation')) {
                $query->where('rugby_situation_id', $request->rugby_situation);
            }
            if ($request->filled('search')) {
                $query->where('title', 'like', '%'.$request->search.'%');
            }

            $videos = $query->latest()->paginate(9);
            $rugbySituations = RugbySituation::active()->ordered()->get()->groupBy('category');
            $userCategoryId = $user->profile->user_category_id ?? null;
            $categories = $userCategoryId ? Category::where('id', $userCategoryId)->get() : collect();

            return view('videos.index', compact('videos', 'rugbySituations', 'categories'))->with('view', 'list');
        }

        // Analistas/entrenadores: navegación por carpetas (adaptativa según tipo de org)
        $org = $user->currentOrganization();
        $orgType = $org?->type ?? 'club';

        // ── CLUB: Categoría → Videos ────────────────────────────────────────
        if ($orgType === 'club') {
            $categoryParam = $request->get('category'); // id | null

            // Nivel 1: carpetas de categorías
            if (! $categoryParam) {
                $categories = Category::withCount('videos')
                    ->orderBy('name')
                    ->get();

                return view('videos.index', compact('categories'))->with('view', 'club_categories');
            }

            // Nivel 2: videos de la categoría
            $category = Category::findOrFail($categoryParam);

            $videos = Video::with(['category', 'uploader', 'tournament', 'rivalTeam'])
                ->withCount('clips')
                ->where('is_master', true)
                ->teamVisible($user)
                ->where('category_id', $categoryParam)
                ->orderBy('match_date', 'desc')
                ->paginate(24);

            return view('videos.index', compact('category', 'videos'))->with('view', 'matches');
        }

        // ── ASOCIACIÓN: Torneo → Partidos ─────────────────────────────────
        $tournamentParam = $request->get('tournament'); // id | null

        // Nivel 1: carpetas de torneos
        if (! $tournamentParam) {
            $tournaments = Tournament::withCount('videos')
                ->orderByDesc('updated_at')
                ->get();

            return view('videos.index', compact('tournaments'))->with('view', 'asoc_tournaments');
        }

        $tournament = Tournament::findOrFail($tournamentParam);

        // Nivel 2: lista de partidos del torneo (cada video master = un partido)
        $matches = Video::with(['rivalTeam', 'videoGroups.videos'])
            ->withCount('clips')
            ->where('is_master', true)
            ->teamVisible($user)
            ->where('tournament_id', $tournamentParam)
            ->orderBy('match_date', 'desc')
            ->get()
            ->each(function ($video) {
                $group = $video->videoGroups->first();
                if ($group && $group->videos->isNotEmpty()) {
                    $video->total_size   = $group->videos->sum(fn ($v) => $v->compressed_file_size ?? $v->file_size ?? 0);
                    $video->angles_count = $group->videos->count();
                } else {
                    $video->total_size   = $video->compressed_file_size ?? $video->file_size ?? 0;
                    $video->angles_count = 1;
                }
            });

        return view('videos.index', compact('tournament', 'matches'))->with('view', 'asoc_matches');
    }

    public function create()
    {
        // Categories: Analysts and coaches see all, staff see only their category
        if (in_array(auth()->user()->role, ['analista', 'entrenador'])) {
            $categories = Category::all();
        } else {
            // Staff only see their assigned category
            $userCategoryId = auth()->user()->profile->user_category_id ?? null;
            $categories = $userCategoryId ? Category::where('id', $userCategoryId)->get() : collect();
        }

        $currentOrg = auth()->user()->currentOrganization();
        $organizationName = $currentOrg?->name ?? 'Mi Equipo';
        $isClub = $currentOrg?->isClub() ?? true;

        // Default equipo local: último equipo analizado por este usuario (si existe)
        // No se pre-rellena con el nombre de la org — el campo es de escritura libre
        $defaultTeam = Video::where('uploaded_by', auth()->id())
            ->whereNotNull('analyzed_team_name')
            ->where('analyzed_team_name', '!=', '')
            ->orderBy('created_at', 'desc')
            ->value('analyzed_team_name');

        $players = User::whereHas('organizations', fn ($q) => $q->where('organizations.id', $currentOrg->id))
            ->where(function ($query) {
                $query->where('role', 'jugador')
                    ->orWhere('role', 'entrenador')
                    ->orWhereHas('profile', function ($q) {
                        $q->where('can_receive_assignments', true);
                    });
            })
            ->with('profile')
            ->orderBy('name')
            ->get();

        return view('videos.create', compact('categories', 'players', 'organizationName', 'defaultTeam', 'isClub'));
    }

    public function store(Request $request)
    {
        try {
            $currentOrg = auth()->user()->currentOrganization();

            $isYoutube = $request->filled('youtube_url') || $request->input('video_source') === 'youtube';

            $request->validate([
                'title' => $isYoutube ? 'nullable|string|max:255' : 'required|string|max:255',
                'description' => 'nullable|string',
                'video_source' => 'nullable|in:upload,youtube',
                'video_file' => $isYoutube
                    ? 'nullable'
                    : 'required|file|mimes:mp4,mov,avi,webm,mkv|max:8388608',
                'youtube_url' => $isYoutube
                    ? ['required', 'string', 'max:500', function ($attribute, $value, $fail) {
                        if (!\App\Models\Video::extractYoutubeVideoId($value)) {
                            $fail('La URL de YouTube no es válida. Usá el formato: youtube.com/watch?v=ID o youtu.be/ID');
                        }
                    }]
                    : 'nullable',
                'rival_team_name' => 'nullable|string|max:255',
                'category_id' => [
                    'required',
                    Rule::exists('categories', 'id')->where(function ($query) use ($currentOrg) {
                        $query->where('organization_id', $currentOrg->id);
                    }),
                ],
                'tournament_id' => 'nullable|exists:tournaments,id',
                'rival_team_id' => 'nullable|exists:rival_teams,id',
                'division' => 'nullable|in:primera,intermedia,unica',
                'match_date' => 'required|date',
                'assigned_players' => 'nullable|array',
                'assigned_players.*' => 'exists:users,id',
                'assignment_notes' => 'nullable|string|max:1000',
                'visibility_type' => 'required|in:public,forwards,backs,specific',
            ], [
                'video_file.required' => 'Seleccioná un archivo de video o pegá una URL de YouTube.',
                'video_file.max' => 'El archivo de video no puede superar 8GB. Videos grandes serán comprimidos automáticamente.',
                'video_file.mimes' => 'El archivo debe ser un video en formato: MP4, MOV, AVI, WEBM o MKV.',
                'category_id.exists' => 'La categoría seleccionada no es válida para tu organización.',
                'youtube_url.required' => 'Ingresá la URL del video de YouTube.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        $currentOrg = auth()->user()->currentOrganization();
        $orgSlug = $currentOrg ? $currentOrg->slug : 'default';
        $organizationName = $currentOrg ? $currentOrg->name : 'Mi Equipo';

        $isYoutube = $request->filled('youtube_url') || $request->input('video_source') === 'youtube';

        if ($isYoutube) {
            // ── YouTube: no upload de archivo ────────────────────────────────
            $youtubeUrl = $request->input('youtube_url');
            $youtubeVideoId = Video::extractYoutubeVideoId($youtubeUrl);

            $autoTitle = $request->title
                ?: trim(($request->local_team_name ?? '') . ' vs ' . ($request->rival_team_name ?? ''))
                   ?: "Video YouTube {$request->match_date}";

            $video = Video::create([
                'title' => $autoTitle,
                'description' => $request->description,
                'file_path' => null,
                'thumbnail_path' => "https://img.youtube.com/vi/{$youtubeVideoId}/maxresdefault.jpg",
                'file_name' => null,
                'file_size' => 0,
                'mime_type' => null,
                'uploaded_by' => auth()->id(),
                'organization_id' => $currentOrg?->id,
                'analyzed_team_name' => $request->local_team_name ?: $organizationName,
                'rival_team_id' => $request->rival_team_id ?: null,
                'rival_team_name' => $request->rival_team_name,
                'tournament_id' => $request->tournament_id ?: null,
                'category_id' => $request->category_id,
                'division' => $request->division ?: 'unica',
                'match_date' => $request->match_date,
                'status' => 'ready',
                'visibility_type' => $request->visibility_type ?? 'public',
                'processing_status' => 'completed',
                'is_youtube_video' => true,
                'youtube_url' => $youtubeUrl,
                'youtube_video_id' => $youtubeVideoId,
            ]);

            // is_master no está en $fillable — se setea con query directa (igual que el modelo)
            \DB::table('videos')->where('id', $video->id)->update(['is_master' => true]);

            \Log::info("Video {$video->id} registrado desde YouTube: {$youtubeUrl}");
        } else {
            // ── Upload de archivo local/cloud ─────────────────────────────────
            $file = $request->file('video_file');
            $originalName = $file->getClientOriginalName();

            $sanitizedName = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $originalName);
            $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);
            $filename = time().'_'.$sanitizedName;

            if (app()->environment('production')) {
                try {
                    $path = $file->storeAs("videos/{$orgSlug}", $filename, 'spaces');
                    Storage::disk('spaces')->setVisibility($path, 'public');
                } catch (Exception $e) {
                    \Log::warning('DigitalOcean Spaces upload failed, using local storage: '.$e->getMessage());
                    $path = $file->storeAs("videos/{$orgSlug}", $filename, 'public');
                }
            } else {
                $path = $file->storeAs("videos/{$orgSlug}", $filename, 'public');
            }

            $thumbnailPath = $this->generateVideoThumbnail($filename);

            $video = Video::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'file_name' => $filename,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => auth()->id(),
                'analyzed_team_name' => $organizationName,
                'rival_team_id' => $request->rival_team_id,
                'rival_team_name' => $request->rival_team_name,
                'tournament_id' => $request->tournament_id,
                'category_id' => $request->category_id,
                'division' => $request->division,
                'match_date' => $request->match_date,
                'status' => 'pending',
                'visibility_type' => $request->visibility_type,
                'processing_status' => 'pending',
            ]);

            // Dispatch compression job based on organization strategy
            $this->dispatchCompressionJob($video);

            \Log::info("Video {$video->id} uploaded successfully, compression decision applied");
        }

        \Log::info("Video {$video->id} uploaded successfully, compression decision applied");

        // Crear asignaciones si el tipo de visibilidad es 'specific'
        if ($request->visibility_type === 'specific' && $request->filled('assigned_players') && is_array($request->assigned_players)) {
            foreach ($request->assigned_players as $playerId) {
                \App\Models\VideoAssignment::create([
                    'video_id' => $video->id,
                    'assigned_to' => $playerId,
                    'assigned_by' => auth()->id(),
                    'notes' => $request->assignment_notes ?? 'Video asignado desde subida inicial.',
                ]);
            }

            $assignedCount = count($request->assigned_players);
            $youtubeNote = $isYoutube ? '' : ' Se está comprimiendo en segundo plano para optimizar la reproducción.';
            $successMessage = "Video guardado exitosamente y asignado a {$assignedCount} jugador(es).{$youtubeNote}";
        } else {
            if ($isYoutube) {
                $successMessage = 'Video de YouTube agregado exitosamente y listo para reproducir.';
            } else {
                $visibilityMessages = [
                    'public' => 'Video subido exitosamente y visible para todo el equipo. Se está comprimiendo en segundo plano.',
                    'forwards' => 'Video subido exitosamente y visible para delanteros. Se está comprimiendo en segundo plano.',
                    'backs' => 'Video subido exitosamente y visible para backs. Se está comprimiendo en segundo plano.',
                    'specific' => 'Video subido exitosamente. Se está comprimiendo en segundo plano.',
                ];
                $successMessage = $visibilityMessages[$request->visibility_type] ?? 'Video subido exitosamente. Se está comprimiendo en segundo plano.';
            }
        }

        // Check if request is AJAX
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'video_id' => $video->id,
                'redirect' => route('videos.show', $video),
            ]);
        }

        return redirect()->route('videos.show', $video)
            ->with('success', $successMessage);
    }

    public function show(Request $request, Video $video)
    {
        $video->load(['category', 'uploader']);

        // REDIRECT TO MASTER: If viewing a slave video, redirect to its master
        if ($video->isPartOfGroup()) {
            $firstGroup = $video->videoGroups()->first();
            if ($firstGroup && $video->isSlave($firstGroup->id)) {
                $master = $firstGroup->getMasterVideo();
                if ($master && $master->id !== $video->id) {
                    return redirect()->route('videos.show', $master);
                }
            }
        }

        // Cargar comentarios principales con todas las respuestas anidadas recursivamente + menciones
        $comments = $video->comments()
            ->whereNull('parent_id')
            ->with(['user', 'mentionedUsers', 'replies' => function ($query) {
                // Cargar respuestas recursivamente con todos sus niveles + menciones
                $query->with(['user', 'mentionedUsers', 'replies' => function ($q) {
                    $q->with(['user', 'mentionedUsers', 'replies' => function ($q2) {
                        $q2->with(['user', 'mentionedUsers', 'replies.user', 'replies.mentionedUsers']); // Nivel 4+
                    }]);
                }]);
            }])
            ->orderBy('timestamp_seconds')
            ->get();

        // Default: Usar Vue/Inertia (migración a SPA)
        $currentOrgId = auth()->user()->currentOrganization()?->id;
        $orgUsers = User::select('id', 'name', 'role')
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $currentOrgId))
            ->get();

        $bunnyService = \App\Services\BunnyStreamService::forOrganization($video->organization);

        $videoData = array_merge($video->toArray(), [
            'stream_url' => $video->is_youtube_video ? null : route('videos.stream', $video),
            'edit_url' => route('videos.edit', $video),
            'is_part_of_group' => $video->isPartOfGroup(),
            'bunny_library_id' => $video->organization?->bunny_library_id,
            'bunny_hls_url' => $video->bunny_video_id && $video->bunny_status === 'ready'
                                    ? $bunnyService->getHlsUrl($video->bunny_video_id)
                                    : ($video->bunny_hls_url ?? null),
            'bunny_mp4_url' => $video->bunny_mp4_url,
            'is_youtube_video' => (bool) $video->is_youtube_video,
            'youtube_video_id' => $video->youtube_video_id,
            'youtube_url' => $video->youtube_url,
            'slave_videos' => $video->isPartOfGroup()
                ? $video->videoGroups->flatMap(function ($group) use ($video, $bunnyService) {
                    return $group->videos
                        ->filter(fn ($v) => $v->id !== $video->id)
                        ->map(fn ($v) => [
                            'id' => $v->id,
                            'title' => $v->title,
                            'stream_url' => route('videos.stream', $v),
                            'sync_offset' => $v->pivot->sync_offset ?? 0,
                            'bunny_hls_url' => $v->bunny_video_id && $v->bunny_status === 'ready'
                                                ? $bunnyService->getHlsUrl($v->bunny_video_id)
                                                : ($v->bunny_hls_url ?? null),
                            'bunny_status' => $v->bunny_status,
                            'bunny_mp4_url' => $v->bunny_mp4_url,
                        ])
                        ->values();
                })->values()->all()
                : [],
        ]);

        return Inertia::render('Videos/Show', [
            'video' => $videoData,
            'comments' => $comments,
            'allUsers' => $orgUsers,
        ]);
    }

    public function edit(Video $video)
    {
        $this->authorize('update', $video);

        $currentOrg = auth()->user()->currentOrganization();
        $categories = Category::where('organization_id', $currentOrg->id)->get();
        $organizationName = $currentOrg->name ?? 'Mi Equipo';
        $isClub = $currentOrg?->isClub() ?? true;

        return view('videos.edit', compact('video', 'categories', 'organizationName', 'isClub'));
    }

    public function update(Request $request, Video $video)
    {
        $this->authorize('update', $video);

        $currentOrg = auth()->user()->currentOrganization();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rival_team_name' => 'nullable|string|max:255',
            'category_id' => [
                $currentOrg?->isClub() ? 'required' : 'nullable',
                Rule::exists('categories', 'id')->where('organization_id', $currentOrg?->id),
            ],
            'match_date' => 'required|date',
        ]);

        $video->update($request->only([
            'title',
            'description',
            'rival_team_name',
            'category_id',
            'match_date',
        ]));

        return redirect()->route('videos.show', $video)
            ->with('success', 'Video actualizado exitosamente');
    }

    public function destroy(Request $request, Video $video)
    {
        $this->authorize('delete', $video);

        $videoTitle = $video->title;

        // Delete video file from storage - try Spaces first, then local
        try {
            if (Storage::disk('spaces')->exists($video->file_path)) {
                Storage::disk('spaces')->delete($video->file_path);
            }
        } catch (Exception $e) {
            \Log::warning('DigitalOcean Spaces delete failed: '.$e->getMessage());
        }

        // Also try deleting from local storage (for old files or fallback)
        try {
            Storage::disk('public')->delete($video->file_path);
        } catch (Exception $e) {
            \Log::warning('Local storage delete failed: '.$e->getMessage());
        }

        // Delete thumbnail if exists
        if ($video->thumbnail_path) {
            try {
                if (Storage::disk('spaces')->exists($video->thumbnail_path)) {
                    Storage::disk('spaces')->delete($video->thumbnail_path);
                }
            } catch (Exception $e) {
                \Log::warning('Thumbnail delete from Spaces failed: '.$e->getMessage());
            }

            try {
                Storage::disk('public')->delete($video->thumbnail_path);
            } catch (Exception $e) {
                \Log::warning('Thumbnail delete from local storage failed: '.$e->getMessage());
            }
        }

        // Delete original file if exists (uncompressed video)
        if ($video->original_file_path && $video->original_file_path !== $video->file_path) {
            try {
                if (Storage::disk('spaces')->exists($video->original_file_path)) {
                    Storage::disk('spaces')->delete($video->original_file_path);
                }
            } catch (Exception $e) {
                \Log::warning('Original file delete from Spaces failed: '.$e->getMessage());
            }

            try {
                Storage::disk('public')->delete($video->original_file_path);
            } catch (Exception $e) {
                \Log::warning('Original file delete from local storage failed: '.$e->getMessage());
            }
        }

        // Eliminar video de Bunny Stream si existe
        if ($video->bunny_video_id) {
            try {
                \App\Services\BunnyStreamService::forOrganization($video->organization)
                    ->deleteVideo($video->bunny_video_id);
            } catch (\Exception $e) {
                \Log::warning('Bunny Stream delete failed: ' . $e->getMessage(), [
                    'video_id'       => $video->id,
                    'bunny_video_id' => $video->bunny_video_id,
                ]);
            }
        }

        $video->delete();

        // Respuesta JSON para AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Video '{$videoTitle}' eliminado exitosamente",
            ]);
        }

        return redirect()->route('videos.index')
            ->with('success', 'Video eliminado exitosamente');
    }

    /**
     * Import LongoMatch XML clips to an existing video
     */
    public function importXml(Request $request, Video $video)
    {
        $request->validate([
            'xml_file' => 'required|file|mimes:xml|max:10240', // 10MB max
        ]);

        try {
            $xmlContent = file_get_contents($request->file('xml_file')->getRealPath());

            $xmlParser = app(\App\Services\LongoMatchXmlParser::class);
            $parsedData = $xmlParser->parse($xmlContent);
            $stats = $xmlParser->importToVideo($video, $parsedData, true);

            return redirect()->route('videos.edit', $video)
                ->with('success', "XML importado exitosamente: {$stats['clips_created']} clips creados.");

        } catch (\Exception $e) {
            \Log::error('Error importing XML to video '.$video->id.': '.$e->getMessage());

            return redirect()->route('videos.edit', $video)
                ->with('error', 'Error al importar XML: '.$e->getMessage());
        }
    }

    /**
     * Delete all clips from this video
     */
    public function deleteAllClips(Video $video)
    {
        try {
            $clipsCount = $video->clips()->count();

            if ($clipsCount === 0) {
                return redirect()->route('videos.edit', $video)
                    ->with('info', 'No hay clips para eliminar.');
            }

            $video->clips()->delete();

            \Log::info("Deleted all clips from video {$video->id}", [
                'clips_deleted' => $clipsCount,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('videos.edit', $video)
                ->with('success', "Se eliminaron {$clipsCount} clips exitosamente.");

        } catch (\Exception $e) {
            \Log::error('Error deleting clips from video '.$video->id.': '.$e->getMessage());

            return redirect()->route('videos.edit', $video)
                ->with('error', 'Error al eliminar clips: '.$e->getMessage());
        }
    }

    public function playerUpload()
    {
        $categories = Category::all();

        // El equipo analizado es siempre la organización actual
        $currentOrg = auth()->user()->currentOrganization();
        $organizationName = $currentOrg ? $currentOrg->name : 'Mi Equipo';

        return view('videos.player-upload', compact('categories', 'organizationName'));
    }

    public function playerStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video_file' => 'required|file|mimes:mp4,mov,avi,webm,mkv|max:8388608', // 8GB max
            'category_id' => 'required|exists:categories,id',
            'analysis_request' => 'required|string',
        ], [
            'video_file.max' => 'El archivo de video no puede superar 8GB. Videos grandes serán comprimidos automáticamente.',
            'video_file.mimes' => 'El archivo debe ser un video en formato: MP4, MOV, AVI, WEBM o MKV.',
        ]);

        $file = $request->file('video_file');
        $originalName = $file->getClientOriginalName();

        // Sanitizar nombre de archivo: remover espacios y caracteres especiales
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $originalName);
        $sanitizedName = preg_replace('/_+/', '_', $sanitizedName); // Múltiples _ a uno solo

        $filename = time().'_player_'.$sanitizedName;

        // Obtener el slug de la organización actual para el path
        $currentOrg = auth()->user()->currentOrganization();
        $orgSlug = $currentOrg ? $currentOrg->slug : 'default';
        $organizationName = $currentOrg ? $currentOrg->name : 'Mi Equipo';

        // En producción: usar Spaces con fallback a local
        // En desarrollo: usar storage local directamente (más rápido)
        if (app()->environment('production')) {
            try {
                $path = $file->storeAs("videos/{$orgSlug}/player-uploads", $filename, 'spaces');
                Storage::disk('spaces')->setVisibility($path, 'public');
            } catch (Exception $e) {
                \Log::warning('DigitalOcean Spaces player upload failed, using local storage: '.$e->getMessage());
                $path = $file->storeAs("videos/{$orgSlug}/player-uploads", $filename, 'public');
            }
        } else {
            // Desarrollo/local: storage local directo
            $path = $file->storeAs("videos/{$orgSlug}/player-uploads", $filename, 'public');
        }

        // Generate thumbnail placeholder
        $thumbnailPath = $this->generateVideoThumbnail($filename);

        $video = Video::create([
            'title' => $request->title.' (Solicitud de Análisis)',
            'description' => $request->description."\n\nSolicitud específica: ".$request->analysis_request,
            'file_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'file_name' => $filename,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'analyzed_team_name' => $organizationName,
            'rival_team_name' => null,
            'category_id' => $request->category_id,
            'division' => 'unica', // Jugadores no especifican división, se asigna automáticamente
            'match_date' => now()->toDateString(),
            'status' => 'pending',
            'processing_status' => 'pending',
        ]);

        // Dispatch compression job based on organization strategy
        $this->dispatchCompressionJob($video);

        \Log::info("Player video {$video->id} uploaded successfully, compression decision applied");

        return redirect()->route('player.videos')
            ->with('success', 'Video subido exitosamente. Se está comprimiendo en segundo plano. Un analista lo revisará pronto.');
    }

    /**
     * Return recent distinct local team names used by the current user.
     * GET /api/local-teams/recent?q=...
     */
    public function recentLocalTeams(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = $request->get('q', '');

        $teams = Video::select('analyzed_team_name')
            ->where('uploaded_by', auth()->id())
            ->when($q, fn ($query) => $query->where('analyzed_team_name', 'like', "%{$q}%"))
            ->whereNotNull('analyzed_team_name')
            ->where('analyzed_team_name', '!=', '')
            ->distinct()
            ->orderByRaw('MAX(created_at) DESC')
            ->groupBy('analyzed_team_name')
            ->limit(10)
            ->pluck('analyzed_team_name')
            ->map(fn ($name) => ['id' => $name, 'text' => $name]);

        return response()->json($teams);
    }

    /**
     * Generate a thumbnail placeholder for the video
     * Since GD is not available, we return null and use CSS placeholders
     */
    private function generateVideoThumbnail($filename)
    {
        // Sin GD, retornamos null y usaremos placeholders CSS
        return null;
    }

    /**
     * Dispatch compression job based on organization compression strategy
     */
    private function dispatchCompressionJob(\App\Models\Video $video): void
    {
        $org = auth()->user()->currentOrganization();
        $fileSizeMB = $video->file_size / 1024 / 1024;

        switch ($org->compression_strategy) {
            case 'immediate':
                \App\Jobs\CompressVideoJob::dispatch($video->id);
                \Log::info("Video {$video->id} queued for immediate compression (strategy: immediate)");
                break;

            case 'nocturnal':
                \Log::info("Video {$video->id} queued for nocturnal compression (strategy: nocturnal, size: {$fileSizeMB}MB)");
                break;

            case 'hybrid':
                if ($fileSizeMB < $org->compression_hybrid_threshold) {
                    \App\Jobs\CompressVideoJob::dispatch($video->id);
                    \Log::info("Video {$video->id} queued for immediate compression (strategy: hybrid, size: {$fileSizeMB}MB < {$org->compression_hybrid_threshold}MB threshold)");
                } else {
                    \Log::info("Video {$video->id} queued for nocturnal compression (strategy: hybrid, size: {$fileSizeMB}MB >= {$org->compression_hybrid_threshold}MB threshold)");
                }
                break;
        }
    }
}
