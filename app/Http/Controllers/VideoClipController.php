<?php

namespace App\Http\Controllers;

use App\Models\ClipCategory;
use App\Models\Video;
use App\Models\VideoClip;
use Illuminate\Http\Request;

class VideoClipController extends Controller
{
    // Lista clips de un video
    public function index(Video $video)
    {
        $clips = $video->clips()
            ->with('category', 'creator')
            ->ordered()
            ->get();

        $categories = ClipCategory::where('organization_id', auth()->user()->currentOrganization()->id)
            ->active()
            ->ordered()
            ->get();

        return view('videos.clips.index', compact('video', 'clips', 'categories'));
    }

    // API: Lista clips para el player
    public function apiIndex(Video $video)
    {
        $clips = $video->clips()
            ->with('category:id,name,slug,color')
            ->ordered()
            ->get();

        return response()->json($clips);
    }

    // Crear clip desde formulario
    public function store(Request $request, Video $video)
    {
        $request->validate([
            'clip_category_id' => 'required|exists:clip_categories,id',
            'start_time' => 'required|numeric|min:0',
            'end_time' => 'required|numeric|gt:start_time',
            'title' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'is_highlight' => 'boolean',
        ]);

        $clip = VideoClip::create([
            'video_id' => $video->id,
            'clip_category_id' => $request->clip_category_id,
            'organization_id' => auth()->user()->currentOrganization()->id,
            'created_by' => auth()->id(),
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'title' => $request->title,
            'notes' => $request->notes,
            'is_highlight' => $request->boolean('is_highlight'),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'clip' => $clip->load('category'),
            ]);
        }

        return back()->with('success', 'Clip creado exitosamente');
    }

    // Crear clip rápido desde botonera (AJAX)
    public function quickStore(Request $request, Video $video)
    {
        $request->validate([
            'clip_category_id' => 'required|exists:clip_categories,id',
            'start_time' => 'required|numeric|min:0',
            'end_time' => 'required|numeric|gt:start_time',
        ]);

        $clip = VideoClip::create([
            'video_id' => $video->id,
            'clip_category_id' => $request->clip_category_id,
            'organization_id' => auth()->user()->currentOrganization()->id,
            'created_by' => auth()->id(),
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return response()->json([
            'success' => true,
            'clip' => $clip->load('category:id,name,slug,color'),
            'message' => 'Clip creado',
        ]);
    }

    // Actualizar clip
    public function update(Request $request, Video $video, VideoClip $clip)
    {
        $request->validate([
            'clip_category_id' => 'sometimes|exists:clip_categories,id',
            'start_time' => 'sometimes|numeric|min:0',
            'end_time' => 'sometimes|numeric',
            'title' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'rating' => 'nullable|integer|min:1|max:5',
            'is_highlight' => 'boolean',
        ]);

        $clip->update($request->only([
            'clip_category_id', 'start_time', 'end_time',
            'title', 'notes', 'rating', 'is_highlight',
        ]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'clip' => $clip->fresh()->load('category'),
            ]);
        }

        return back()->with('success', 'Clip actualizado exitosamente');
    }

    // Eliminar clip
    public function destroy(Request $request, Video $video, VideoClip $clip)
    {
        $clip->delete();

        if ($request->ajax() || $request->wantsJson() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Clip eliminado exitosamente');
    }

    // Marcar/desmarcar como destacado
    public function toggleHighlight(Request $request, VideoClip $clip)
    {
        $clip->update(['is_highlight' => ! $clip->is_highlight]);

        return response()->json([
            'success' => true,
            'is_highlight' => $clip->is_highlight,
        ]);
    }

    // Clips por categoría (para filtrar)
    public function byCategory(ClipCategory $category)
    {
        $clips = VideoClip::where('clip_category_id', $category->id)
            ->where('organization_id', auth()->user()->currentOrganization()->id)
            ->with('video:id,title', 'category:id,name,color')
            ->ordered()
            ->get();

        return response()->json($clips);
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
            'success' => true,
            'message' => 'Offset de timeline actualizado',
            'timeline_offset' => $video->timeline_offset,
        ]);
    }
}
