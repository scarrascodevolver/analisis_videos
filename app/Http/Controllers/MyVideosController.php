<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoAssignment;
use Illuminate\Http\Request;

class MyVideosController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Obtener videos asignados al usuario
        $assignedVideos = VideoAssignment::where('assigned_to', $user->id)
            ->whereHas('video')
            ->with(['video.category', 'video.rugbySituation', 'video.uploader', 'assignedBy'])
            ->latest()
            ->paginate(12, ['*'], 'assigned');

        // Estadísticas de asignaciones (solo videos existentes)
        $stats = [
            'total' => $user->assignedVideos()->whereHas('video')->count(),
            'pending' => $user->pendingAssignments()->count(),
            'completed' => 0, // Ya no hay estados de completado
            'overdue' => 0, // Ya no hay fechas límite
        ];

        return view('my-videos.index', compact('assignedVideos', 'stats'));
    }

    public function markAsCompleted(VideoAssignment $assignment)
    {
        // Verificar que el usuario puede marcar como completado
        if ($assignment->assigned_to !== auth()->id()) {
            abort(403, 'No tienes permiso para realizar esta acción');
        }

        $assignment->markAsCompleted();

        return back()->with('success', 'Video marcado como completado');
    }

    public function show(VideoAssignment $assignment)
    {
        // Verificar que el usuario puede ver este video
        if ($assignment->assigned_to !== auth()->id()) {
            abort(403, 'No tienes permiso para ver este video');
        }

        $video = $assignment->video->load(['category', 'rugbySituation', 'uploader', 'comments.user', 'comments.replies.user']);

        $comments = $video->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('timestamp_seconds')
            ->get();

        return view('videos.show', compact('video', 'comments', 'assignment'));
    }
}
