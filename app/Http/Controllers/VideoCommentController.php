<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoComment;
use Illuminate\Http\Request;

class VideoCommentController extends Controller
{
    public function store(Request $request, Video $video)
    {
        $request->validate([
            'comment' => 'required|string',
            'timestamp_seconds' => 'required|integer|min:0',
            'category' => 'required|in:tecnico,tactico,fisico,mental,general',
            'priority' => 'required|in:baja,media,alta,critica',
            'parent_id' => 'nullable|exists:video_comments,id'
        ]);

        // If it's a reply, ensure the parent belongs to the same video
        if ($request->parent_id) {
            $parentComment = \App\Models\VideoComment::find($request->parent_id);
            if ($parentComment->video_id !== $video->id) {
                return back()->withErrors(['parent_id' => 'Comentario padre inválido']);
            }
        }

        $comment = \App\Models\VideoComment::create([
            'video_id' => $video->id,
            'user_id' => auth()->id(),
            'parent_id' => $request->parent_id,
            'comment' => $request->comment,
            'timestamp_seconds' => $request->timestamp_seconds,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'pendiente'
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'comment' => $comment->load('user'),
                'formatted_timestamp' => $comment->formatted_timestamp
            ]);
        }

        return back()->with('success', 'Comentario agregado exitosamente');
    }

    public function update(Request $request, \App\Models\VideoComment $comment)
    {
        $request->validate([
            'comment' => 'required|string',
            'category' => 'required|in:tecnico,tactico,fisico,mental,general',
            'priority' => 'required|in:baja,media,alta,critica',
            'status' => 'required|in:pendiente,en_revision,completado'
        ]);

        $comment->update($request->only(['comment', 'category', 'priority', 'status']));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'comment' => $comment->fresh()->load('user')
            ]);
        }

        return back()->with('success', 'Comentario actualizado exitosamente');
    }

    public function destroy(\App\Models\VideoComment $comment)
    {
        // Solo el autor del comentario puede eliminarlo
        if ($comment->user_id !== auth()->id()) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permisos para eliminar este comentario'
                ], 403);
            }

            return back()->withErrors(['error' => 'No tienes permisos para eliminar este comentario']);
        }

        $comment->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Comentario eliminado exitosamente');
    }

    public function markComplete(\App\Models\VideoComment $comment)
    {
        $comment->update(['status' => 'completado']);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'comment' => $comment->fresh()->load('user')
            ]);
        }

        return back()->with('success', 'Comentario marcado como completado');
    }
}
