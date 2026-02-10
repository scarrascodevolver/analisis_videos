<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RugbySituation;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::with(['category', 'uploader', 'rugbySituation'])
            ->teamVisible(auth()->user());

        // Filter by rugby situation
        if ($request->filled('rugby_situation')) {
            $query->where('rugby_situation_id', $request->rugby_situation);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by team name (busca en analyzed_team_name o rival_team_name)
        if ($request->filled('team')) {
            $query->byTeamName($request->team);
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $videos = $query->latest()->paginate(9);

        // Get filter data
        $rugbySituations = RugbySituation::active()->ordered()->get()->groupBy('category');

        // Categories: Analysts and coaches see all, staff see only their category
        if (in_array(auth()->user()->role, ['analista', 'entrenador'])) {
            $categories = Category::all();
        } else {
            // Staff only see their assigned category
            $userCategoryId = auth()->user()->profile->user_category_id ?? null;
            $categories = $userCategoryId ? Category::where('id', $userCategoryId)->get() : collect();
        }

        return view('videos.index', compact('videos', 'rugbySituations', 'categories'));
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

        $rugbySituations = RugbySituation::active()->ordered()->get()->groupBy('category');

        // El equipo analizado es siempre la organización actual
        $currentOrg = auth()->user()->currentOrganization();
        $organizationName = $currentOrg ? $currentOrg->name : 'Mi Equipo';

        // Obtener jugadores y entrenadores para asignación (incluye staff que puede recibir asignaciones)
        $players = User::where(function ($query) {
            $query->where('role', 'jugador')
                ->orWhere('role', 'entrenador')
                ->orWhereHas('profile', function ($q) {
                    $q->where('can_receive_assignments', true);
                });
        })
            ->with('profile')
            ->orderBy('name')
            ->get();

        return view('videos.create', compact('categories', 'rugbySituations', 'players', 'organizationName'));
    }

    public function store(Request $request)
    {
        try {
            $currentOrg = auth()->user()->currentOrganization();

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'video_file' => 'required|file|mimes:mp4,mov,avi,webm,mkv|max:8388608', // 8GB max
                'rival_team_name' => 'nullable|string|max:255', // Texto libre para rival
                'category_id' => [
                    'required',
                    Rule::exists('categories', 'id')->where(function ($query) use ($currentOrg) {
                        $query->where('organization_id', $currentOrg->id);
                    }),
                ],
                'division' => 'nullable|in:primera,intermedia,unica',
                'rugby_situation_id' => 'nullable|exists:rugby_situations,id',
                'match_date' => 'required|date',
                'assigned_players' => 'nullable|array',
                'assigned_players.*' => 'exists:users,id',
                'assignment_notes' => 'nullable|string|max:1000',
                'visibility_type' => 'required|in:public,forwards,backs,specific',
            ], [
                'video_file.max' => 'El archivo de video no puede superar 8GB. Videos grandes serán comprimidos automáticamente.',
                'video_file.mimes' => 'El archivo debe ser un video en formato: MP4, MOV, AVI, WEBM o MKV.',
                'category_id.exists' => 'La categoría seleccionada no es válida para tu organización.',
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

        $file = $request->file('video_file');
        $originalName = $file->getClientOriginalName();

        // Sanitizar nombre de archivo: remover espacios y caracteres especiales
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $originalName);
        $sanitizedName = preg_replace('/_+/', '_', $sanitizedName); // Múltiples _ a uno solo

        $filename = time().'_'.$sanitizedName;

        // Obtener el slug de la organización actual para el path
        $currentOrg = auth()->user()->currentOrganization();
        $orgSlug = $currentOrg ? $currentOrg->slug : 'default';
        $organizationName = $currentOrg ? $currentOrg->name : 'Mi Equipo';

        // En producción: usar Spaces con fallback a local
        // En desarrollo: usar storage local directamente (más rápido)
        if (app()->environment('production')) {
            try {
                $path = $file->storeAs("videos/{$orgSlug}", $filename, 'spaces');
                Storage::disk('spaces')->setVisibility($path, 'public');
            } catch (Exception $e) {
                \Log::warning('DigitalOcean Spaces upload failed, using local storage: '.$e->getMessage());
                $path = $file->storeAs("videos/{$orgSlug}", $filename, 'public');
            }
        } else {
            // Desarrollo/local: storage local directo
            $path = $file->storeAs("videos/{$orgSlug}", $filename, 'public');
        }

        // Generate thumbnail placeholder
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
            'analyzed_team_name' => $organizationName, // Siempre es la organización
            'rival_team_name' => $request->rival_team_name,
            'category_id' => $request->category_id,
            'division' => $request->division,
            'rugby_situation_id' => $request->rugby_situation_id,
            'match_date' => $request->match_date,
            'status' => 'pending',
            'visibility_type' => $request->visibility_type,
            'processing_status' => 'pending',
        ]);

        // Dispatch compression job based on organization strategy
        $this->dispatchCompressionJob($video);

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
            $successMessage = "Video subido exitosamente y asignado a {$assignedCount} jugador(es). Se está comprimiendo en segundo plano para optimizar la reproducción.";
        } else {
            $visibilityMessages = [
                'public' => 'Video subido exitosamente y visible para todo el equipo. Se está comprimiendo en segundo plano.',
                'forwards' => 'Video subido exitosamente y visible para delanteros. Se está comprimiendo en segundo plano.',
                'backs' => 'Video subido exitosamente y visible para backs. Se está comprimiendo en segundo plano.',
                'specific' => 'Video subido exitosamente. Se está comprimiendo en segundo plano.',
            ];
            $successMessage = $visibilityMessages[$request->visibility_type] ?? 'Video subido exitosamente. Se está comprimiendo en segundo plano.';
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

    public function show(Video $video)
    {
        $video->load(['category', 'uploader']);

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

        return view('videos.show', compact('video', 'comments'));
    }

    public function edit(Video $video)
    {
        $categories = Category::all();

        // El equipo analizado es siempre la organización actual
        $currentOrg = auth()->user()->currentOrganization();
        $organizationName = $currentOrg ? $currentOrg->name : 'Mi Equipo';

        return view('videos.edit', compact('video', 'categories', 'organizationName'));
    }

    public function update(Request $request, Video $video)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rival_team_name' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
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
                ->with('success', "XML importado exitosamente: {$stats['clips_created']} clips creados, {$stats['categories_created']} categorías nuevas, {$stats['categories_reused']} categorías reutilizadas.");

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
