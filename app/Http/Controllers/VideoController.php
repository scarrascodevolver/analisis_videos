<?php

namespace App\Http\Controllers;

use App\Jobs\CompressVideoJob;
use App\Models\User;
use App\Models\Video;
use App\Models\Team;
use App\Models\Category;
use App\Models\VideoComment;
use App\Models\RugbySituation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::with(['analyzedTeam', 'rivalTeam', 'category', 'uploader', 'rugbySituation'])
                      ->teamVisible(auth()->user());

        // Filter by rugby situation
        if ($request->filled('rugby_situation')) {
            $query->where('rugby_situation_id', $request->rugby_situation);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by team
        if ($request->filled('team')) {
            $query->where(function ($q) use ($request) {
                $q->where('analyzed_team_id', $request->team)
                    ->orWhere('rival_team_id', $request->team);
            });
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
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

        $teams = Team::all();

        return view('videos.index', compact('videos', 'rugbySituations', 'categories', 'teams'));
    }

    public function create()
    {
        $teams = Team::all();

        // Categories: Analysts and coaches see all, staff see only their category
        if (in_array(auth()->user()->role, ['analista', 'entrenador'])) {
            $categories = Category::all();
        } else {
            // Staff only see their assigned category
            $userCategoryId = auth()->user()->profile->user_category_id ?? null;
            $categories = $userCategoryId ? Category::where('id', $userCategoryId)->get() : collect();
        }
        $ownTeam = Team::where('is_own_team', true)->first();
        $rivalTeams = Team::where('is_own_team', false)->get();
        $rugbySituations = RugbySituation::active()->ordered()->get()->groupBy('category');

        // Obtener jugadores y entrenadores para asignación (incluye staff que puede recibir asignaciones)
        $players = User::where(function($query) {
                $query->where('role', 'jugador')
                      ->orWhere('role', 'entrenador')
                      ->orWhereHas('profile', function($q) {
                          $q->where('can_receive_assignments', true);
                      });
            })
            ->with('profile')
            ->orderBy('name')
            ->get();

        return view('videos.create', compact('teams', 'categories', 'ownTeam', 'rivalTeams', 'rugbySituations', 'players'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'video_file' => 'required|file|mimes:mp4,mov,avi,webm,mkv|max:8388608', // 8GB max
                'analyzed_team_id' => 'nullable|exists:teams,id', // Ahora es opcional
                'rival_team_id' => 'nullable|exists:teams,id',
                'rival_team_name' => 'nullable|string|max:255', // Texto libre para rival
                'category_id' => 'required|exists:categories,id',
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
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        $file = $request->file('video_file');
        $originalName = $file->getClientOriginalName();

        // Sanitizar nombre de archivo: remover espacios y caracteres especiales
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $originalName);
        $sanitizedName = preg_replace('/_+/', '_', $sanitizedName); // Múltiples _ a uno solo

        $filename = time() . '_' . $sanitizedName;

        // Obtener el slug de la organización actual para el path
        $currentOrg = auth()->user()->currentOrganization();
        $orgSlug = $currentOrg ? $currentOrg->slug : 'default';

        // En producción: usar Spaces con fallback a local
        // En desarrollo: usar storage local directamente (más rápido)
        if (app()->environment('production')) {
            try {
                $path = $file->storeAs("videos/{$orgSlug}", $filename, 'spaces');
                Storage::disk('spaces')->setVisibility($path, 'public');
            } catch (Exception $e) {
                \Log::warning('DigitalOcean Spaces upload failed, using local storage: ' . $e->getMessage());
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
            'analyzed_team_id' => $request->analyzed_team_id,
            'rival_team_id' => $request->rival_team_id,
            'rival_team_name' => $request->rival_team_name, // Texto libre para rival
            'category_id' => $request->category_id,
            'division' => $request->division,
            'rugby_situation_id' => $request->rugby_situation_id,
            'match_date' => $request->match_date,
            'status' => 'pending',
            'visibility_type' => $request->visibility_type,
            'processing_status' => 'pending'
        ]);

        // Dispatch compression job to queue
        CompressVideoJob::dispatch($video->id);

        \Log::info("Video {$video->id} uploaded successfully, compression job dispatched to queue");

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
                'specific' => 'Video subido exitosamente. Se está comprimiendo en segundo plano.'
            ];
            $successMessage = $visibilityMessages[$request->visibility_type] ?? 'Video subido exitosamente. Se está comprimiendo en segundo plano.';
        }

        // Check if request is AJAX
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'video_id' => $video->id,
                'redirect' => route('videos.show', $video)
            ]);
        }

        return redirect()->route('videos.show', $video)
            ->with('success', $successMessage);
    }

    public function show(Video $video)
    {
        $video->load(['analyzedTeam', 'rivalTeam', 'category', 'uploader']);

        // Cargar comentarios principales con todas las respuestas anidadas recursivamente + menciones
        $comments = $video->comments()
            ->whereNull('parent_id')
            ->with(['user', 'mentionedUsers', 'replies' => function($query) {
                // Cargar respuestas recursivamente con todos sus niveles + menciones
                $query->with(['user', 'mentionedUsers', 'replies' => function($q) {
                    $q->with(['user', 'mentionedUsers', 'replies' => function($q2) {
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
        $teams = Team::all();
        $categories = Category::all();
        $ownTeam = Team::where('is_own_team', true)->first();
        $rivalTeams = Team::where('is_own_team', false)->get();

        return view('videos.edit', compact('video', 'teams', 'categories', 'ownTeam', 'rivalTeams'));
    }

    public function update(Request $request, Video $video)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'analyzed_team_id' => 'nullable|exists:teams,id', // Ahora es opcional
            'rival_team_id' => 'nullable|exists:teams,id',
            'rival_team_name' => 'nullable|string|max:255', // Texto libre para rival
            'category_id' => 'required|exists:categories,id',
            'match_date' => 'required|date',
        ]);

        $video->update($request->only([
            'title',
            'description',
            'analyzed_team_id',
            'rival_team_id',
            'rival_team_name',
            'category_id',
            'match_date'
        ]));

        return redirect()->route('videos.show', $video)
            ->with('success', 'Video actualizado exitosamente');
    }

    public function destroy(Video $video)
    {
        // Delete file from storage - try Spaces first, then local
        try {
            if (Storage::disk('spaces')->exists($video->file_path)) {
                Storage::disk('spaces')->delete($video->file_path);
            }
        } catch (Exception $e) {
            \Log::warning('DigitalOcean Spaces delete failed: ' . $e->getMessage());
        }

        // Also try deleting from local storage (for old files or fallback)
        try {
            Storage::disk('public')->delete($video->file_path);
        } catch (Exception $e) {
            \Log::warning('Local storage delete failed: ' . $e->getMessage());
        }

        $video->delete();

        return redirect()->route('videos.index')
            ->with('success', 'Video eliminado exitosamente');
    }


    public function playerUpload()
    {
        $teams = Team::all();
        $categories = Category::all();
        $ownTeam = Team::where('is_own_team', true)->first();

        return view('videos.player-upload', compact('teams', 'categories', 'ownTeam'));
    }

    public function playerStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video_file' => 'required|file|mimes:mp4,mov,avi,webm,mkv|max:8388608', // 8GB max
            'category_id' => 'required|exists:categories,id',
            'analysis_request' => 'required|string'
        ], [
            'video_file.max' => 'El archivo de video no puede superar 8GB. Videos grandes serán comprimidos automáticamente.',
            'video_file.mimes' => 'El archivo debe ser un video en formato: MP4, MOV, AVI, WEBM o MKV.',
        ]);

        $file = $request->file('video_file');
        $originalName = $file->getClientOriginalName();

        // Sanitizar nombre de archivo: remover espacios y caracteres especiales
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $originalName);
        $sanitizedName = preg_replace('/_+/', '_', $sanitizedName); // Múltiples _ a uno solo

        $filename = time() . '_player_' . $sanitizedName;

        // Obtener el slug de la organización actual para el path
        $currentOrg = auth()->user()->currentOrganization();
        $orgSlug = $currentOrg ? $currentOrg->slug : 'default';

        // En producción: usar Spaces con fallback a local
        // En desarrollo: usar storage local directamente (más rápido)
        if (app()->environment('production')) {
            try {
                $path = $file->storeAs("videos/{$orgSlug}/player-uploads", $filename, 'spaces');
                Storage::disk('spaces')->setVisibility($path, 'public');
            } catch (Exception $e) {
                \Log::warning('DigitalOcean Spaces player upload failed, using local storage: ' . $e->getMessage());
                $path = $file->storeAs("videos/{$orgSlug}/player-uploads", $filename, 'public');
            }
        } else {
            // Desarrollo/local: storage local directo
            $path = $file->storeAs("videos/{$orgSlug}/player-uploads", $filename, 'public');
        }

        // Generate thumbnail placeholder
        $thumbnailPath = $this->generateVideoThumbnail($filename);

        $ownTeam = Team::where('is_own_team', true)->first();

        $video = Video::create([
            'title' => $request->title . ' (Solicitud de Análisis)',
            'description' => $request->description . "\n\nSolicitud específica: " . $request->analysis_request,
            'file_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'file_name' => $filename,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'analyzed_team_id' => $ownTeam->id,
            'rival_team_id' => null,
            'category_id' => $request->category_id,
            'division' => 'unica', // Jugadores no especifican división, se asigna automáticamente
            'match_date' => now()->toDateString(),
            'status' => 'pending',
            'processing_status' => 'pending'
        ]);

        // Dispatch compression job to queue
        CompressVideoJob::dispatch($video->id);

        \Log::info("Player video {$video->id} uploaded successfully, compression job dispatched to queue");

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
}
