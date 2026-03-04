<?php

namespace App\Http\Controllers;

use App\Models\ClipCategory;
use App\Models\Video;
use App\Models\VideoClip;
use App\Models\VideoOrgShare;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoClipController extends Controller
{
    // Lista clips de un video
    public function index(Video $video)
    {
        $clips = $video->clips()
            ->with('category', 'creator')
            ->visibleTo(auth()->user())
            ->ordered()
            ->get();

        $categories = ClipCategory::where('organization_id', auth()->user()->currentOrganization()->id)
            ->active()
            ->ordered()
            ->get();

        return view('videos.clips.index', compact('video', 'clips', 'categories'));
    }

    // API: Lista clips para el player
    // Visibilidad: mis clips + clips compartidos + clips de XML (scope='video')
    // Para videos compartidos entre orgs: se usan queries cross-org explícitas
    public function apiIndex(Video $video)
    {
        $user    = auth()->user();
        $userOrg = $user->currentOrganization();
        $userOrgId = $userOrg?->id;

        $isSharedVideo = $userOrgId && $video->organization_id !== $userOrgId;

        if (! $isSharedVideo) {
            // Caso normal: video propio de la org — usar el scope habitual
            $clips = $video->clips()
                ->with('category:id,name,slug,color,scope', 'creator:id,name')
                ->visibleTo($user)
                ->ordered()
                ->get();
        } else {
            // Caso cross-org: video compartido por una asociación
            // Mostrar TODOS los clips de la org dueña del video +
            // clips propios del club en este video.
            // Si el video fue compartido, sus clips se comparten completos.
            $clips = VideoClip::withoutGlobalScope('organization')
                ->with('category:id,name,slug,color,scope', 'creator:id,name')
                ->where('video_id', $video->id)
                ->where(function ($q) use ($userOrgId, $video) {
                    $q->where('organization_id', $video->organization_id) // todos los de la asoc
                      ->orWhere('organization_id', $userOrgId);           // propios del club
                })
                ->orderBy('start_time')
                ->get();
        }

        return response()->json($clips);
    }

    // Crear clip desde formulario
    public function store(Request $request, Video $video)
    {
        $request->validate([
            'clip_category_id' => 'required|exists:clip_categories,id',
            'start_time'       => 'required|numeric|min:0',
            'end_time'         => 'required|numeric|gt:start_time',
            'title'            => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:500',
            'is_highlight'     => 'boolean',
        ]);

        $clip = VideoClip::create([
            'video_id'         => $video->id,
            'clip_category_id' => $request->clip_category_id,
            'organization_id'  => auth()->user()->currentOrganization()->id,
            'created_by'       => auth()->id(),
            'start_time'       => $request->start_time,
            'end_time'         => $request->end_time,
            'title'            => $request->title,
            'notes'            => $request->notes,
            'is_highlight'     => $request->boolean('is_highlight'),
            'is_shared'        => false,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'clip'    => $clip->load('category'),
            ]);
        }

        return back()->with('success', 'Clip creado exitosamente');
    }

    // Crear clip rápido desde botonera (AJAX)
    public function quickStore(Request $request, Video $video)
    {
        $request->validate([
            'clip_category_id' => 'nullable|exists:clip_categories,id',
            'start_time'       => 'required|numeric|min:0',
            'end_time'         => 'required|numeric|gt:start_time',
        ]);

        $clip = VideoClip::create([
            'video_id'         => $video->id,
            'clip_category_id' => $request->clip_category_id,
            'organization_id'  => auth()->user()->currentOrganization()->id,
            'created_by'       => auth()->id(),
            'start_time'       => $request->start_time,
            'end_time'         => $request->end_time,
            'is_shared'        => false,
        ]);

        return response()->json([
            'success' => true,
            'clip'    => $clip->load('category:id,name,slug,color,scope'),
            'message' => 'Clip creado',
        ]);
    }

    // Actualizar clip (solo el creador)
    public function update(Request $request, Video $video, VideoClip $clip)
    {
        if ($clip->created_by !== auth()->id()) {
            return response()->json(['error' => 'No podés editar clips de otros analistas'], 403);
        }

        $request->validate([
            'clip_category_id' => 'sometimes|exists:clip_categories,id',
            'start_time'       => 'sometimes|numeric|min:0',
            'end_time'         => 'sometimes|numeric',
            'title'            => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:500',
            'rating'           => 'nullable|integer|min:1|max:5',
            'is_highlight'     => 'boolean',
        ]);

        $clip->update($request->only([
            'clip_category_id', 'start_time', 'end_time',
            'title', 'notes', 'rating', 'is_highlight',
        ]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'clip'    => $clip->fresh()->load('category'),
            ]);
        }

        return back()->with('success', 'Clip actualizado exitosamente');
    }

    // Eliminar clip (solo el creador)
    public function destroy(Request $request, Video $video, VideoClip $clip)
    {
        if ($clip->created_by !== auth()->id()) {
            return response()->json(['error' => 'No podés eliminar clips de otros analistas'], 403);
        }

        $clip->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Clip eliminado exitosamente');
    }

    // Compartir / privatizar TODOS los clips de una categoría del analista (toggle)
    public function toggleCategoryShare(Request $request, Video $video)
    {
        $request->validate([
            'clip_category_id' => 'required|integer|exists:clip_categories,id',
        ]);

        $userId     = auth()->id();
        $categoryId = $request->clip_category_id;

        // Si queda algún clip sin compartir → compartir todos. Si todos ya están → privatizar.
        $hasUnshared = VideoClip::where('video_id', $video->id)
            ->where('clip_category_id', $categoryId)
            ->where('created_by', $userId)
            ->where('is_shared', false)
            ->exists();

        $newState = $hasUnshared; // true = compartir, false = privatizar

        VideoClip::where('video_id', $video->id)
            ->where('clip_category_id', $categoryId)
            ->where('created_by', $userId)
            ->update(['is_shared' => $newState]);

        $count = VideoClip::where('video_id', $video->id)
            ->where('clip_category_id', $categoryId)
            ->where('created_by', $userId)
            ->count();

        return response()->json([
            'success'   => true,
            'is_shared' => $newState,
            'message'   => $newState
                ? "Categoría compartida ({$count} clips visibles para el equipo)"
                : "Categoría privatizada ({$count} clips solo visibles para vos)",
            'count'     => $count,
        ]);
    }

    // Compartir / privatizar un clip (toggle)
    public function toggleShare(Request $request, VideoClip $clip)
    {
        if ($clip->created_by !== auth()->id()) {
            return response()->json(['error' => 'No podés compartir clips de otros analistas'], 403);
        }

        $clip->update(['is_shared' => ! $clip->is_shared]);

        return response()->json([
            'success'   => true,
            'is_shared' => $clip->is_shared,
            'message'   => $clip->is_shared ? 'Clip compartido con el equipo' : 'Clip privatizado',
        ]);
    }

    // Marcar/desmarcar como destacado
    public function toggleHighlight(Request $request, VideoClip $clip)
    {
        $clip->update(['is_highlight' => ! $clip->is_highlight]);

        return response()->json([
            'success'      => true,
            'is_highlight' => $clip->is_highlight,
        ]);
    }

    // Clips por categoría (para filtrar)
    public function byCategory(ClipCategory $category)
    {
        $clips = VideoClip::where('clip_category_id', $category->id)
            ->where('organization_id', auth()->user()->currentOrganization()->id)
            ->visibleTo(auth()->user())
            ->with('video:id,title', 'category:id,name,color')
            ->ordered()
            ->get();

        return response()->json($clips);
    }

    // Generar o reutilizar link público de compartir para un clip
    // POST /api/clips/{clip}/share-link  (auth required)
    public function generateShareLink(VideoClip $clip): \Illuminate\Http\JsonResponse
    {
        $user      = auth()->user();
        $userOrg   = $user->currentOrganization();
        $userOrgId = $userOrg?->id;

        // Verificar que el usuario puede acceder al clip:
        //   - Es clip propio, o
        //   - El clip pertenece a su org, o
        //   - Tiene un VideoOrgShare activo para el video del clip
        $canAccess = $clip->created_by === $user->id
            || $clip->organization_id === $userOrgId
            || VideoOrgShare::where('video_id', $clip->video_id)
                ->where('target_organization_id', $userOrgId)
                ->where('status', 'active')
                ->exists();

        if (! $canAccess) {
            return response()->json(['error' => 'No tenés acceso a este clip.'], 403);
        }

        // Generar token si aún no existe
        if (! $clip->share_token) {
            $clip->update(['share_token' => (string) Str::uuid()]);
        }

        return response()->json([
            'url' => route('clips.public', $clip->share_token),
        ]);
    }

    // Vista pública de clip por token (no requiere auth)
    // GET /clips/{token}
    public function publicView(string $token): \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
    {
        $clip = VideoClip::withoutGlobalScopes()
            ->with([
                'category:id,name,color,scope',
                'creator:id,name',
                'video' => fn ($q) => $q->withoutGlobalScopes()->with('organization'),
            ])
            ->where('share_token', $token)
            ->firstOrFail();

        $video = $clip->video;

        if (! $video) {
            abort(404);
        }

        $bunnyService = \App\Services\BunnyStreamService::forOrganization($video->organization);

        $clipData = [
            'id'          => $clip->id,
            'start_time'  => $clip->start_time,
            'end_time'    => $clip->end_time,
            'title'       => $clip->title,
            'notes'       => $clip->notes,
            'category'    => $clip->category,
            'creator'     => $clip->creator,
            'video'       => [
                'id'            => $video->id,
                'title'         => $video->title,
                'bunny_hls_url' => $video->bunny_video_id && $video->bunny_status === 'ready'
                    ? $bunnyService->getHlsUrl($video->bunny_video_id)
                    : ($video->bunny_hls_url ?? null),
                'bunny_mp4_url' => $video->bunny_mp4_url,
                'stream_url'    => route('videos.stream', $video),
                'organization'  => [
                    'name' => $video->organization?->name,
                ],
            ],
        ];

        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json($clipData);
        }

        return view('clips.public', compact('clip', 'video', 'clipData'));
    }

    // Reordenar clips dentro de un video (drag & drop)
    // POST /api/videos/{video}/clips/reorder
    // Payload: { clips: [id1, id2, id3, ...] } — IDs en el nuevo orden
    public function reorder(Request $request, Video $video): \Illuminate\Http\JsonResponse
    {
        $ids = $request->validate([
            'clips'   => 'required|array',
            'clips.*' => 'integer',
        ])['clips'];

        foreach ($ids as $idx => $id) {
            VideoClip::where('id', $id)
                ->where('video_id', $video->id)
                ->update(['sort_order' => $idx + 1]);
        }

        return response()->json(['ok' => true]);
    }

    // Actualizar offset global de timeline para sincronización
    public function updateTimelineOffset(Request $request, Video $video)
    {
        $request->validate([
            'timeline_offset' => 'required|numeric|min:-600|max:600',
        ]);

        $video->update([
            'timeline_offset' => $request->timeline_offset,
        ]);

        return response()->json([
            'success'          => true,
            'message'          => 'Offset de timeline actualizado',
            'timeline_offset'  => $video->timeline_offset,
        ]);
    }
}
