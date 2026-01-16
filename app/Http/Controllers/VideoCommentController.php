<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoComment;
use App\Models\User;
use App\Models\VideoAssignment;
use App\Models\CommentMention;
use App\Notifications\VideoCommentMention as VideoCommentMentionNotification;
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

        $comment = VideoComment::create([
            'video_id' => $video->id,
            'user_id' => auth()->id(),
            'parent_id' => $request->parent_id,
            'comment' => $request->comment,
            'timestamp_seconds' => $request->timestamp_seconds,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'pendiente'
        ]);

        // 🎯 DETECTAR MENCIONES con @
        $this->processMentions($comment, $video, $request->comment);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'comment' => $comment->load('user', 'mentionedUsers'),
                'formatted_timestamp' => $comment->formatted_timestamp,
                'mentioned_users' => $comment->mentionedUsers->pluck('name')
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

    /**
     * Procesar menciones en comentarios con @
     */
    protected function processMentions(VideoComment $comment, Video $video, string $commentText)
    {
        // Regex mejorado: Captura @NombrePalabra hasta encontrar:
        // - Un espacio seguido de palabra minúscula (ej: "que", "mira", "prueba")
        // - Doble espacio
        // - Signos de puntuación (. , ! ? ;)
        // - Final de línea
        // Máximo 4 palabras para evitar capturar frases completas
        preg_match_all('/@([\wáéíóúÁÉÍÓÚñÑ]+(?:\s+[A-ZÁÉÍÓÚÑ][\wáéíóúÁÉÍÓÚñÑ]*){0,3})(?=\s+[a-záéíóúñ]|\s{2,}|[.,!?;\n]|$)/u', $commentText, $matches);

        if (empty($matches[1])) {
            return; // No hay menciones
        }

        $mentionedNames = array_unique($matches[1]);

        // Buscar usuarios mencionados por nombre, solo de la misma organización
        $currentOrg = auth()->user()->currentOrganization();

        if (!$currentOrg) {
            return; // No hay organización actual
        }

        $mentionedUsers = User::whereIn('name', $mentionedNames)
            ->whereHas('organizations', function($query) use ($currentOrg) {
                $query->where('organizations.id', $currentOrg->id);
            })
            ->get();

        if ($mentionedUsers->isEmpty()) {
            return; // Ningún usuario encontrado en la organización
        }

        foreach ($mentionedUsers as $user) {
            // No te puedes mencionar a ti mismo
            if ($user->id === auth()->id()) {
                continue;
            }

            // 1. Crear registro de mención
            CommentMention::create([
                'comment_id' => $comment->id,
                'mentioned_user_id' => $user->id,
                'mentioned_by_user_id' => auth()->id(),
                'is_read' => false,
            ]);

            // 2. Enviar notificación (email + database)
            $user->notify(new VideoCommentMentionNotification($video, $comment, auth()->user()));

            // 3. Si el mencionado es JUGADOR → crear assignment para que aparezca en "Mis Videos"
            if ($user->role === 'jugador') {
                VideoAssignment::firstOrCreate(
                    [
                        'video_id' => $video->id,
                        'assigned_to' => $user->id,
                    ],
                    [
                        'assigned_by' => auth()->id(),
                        'comment_id' => $comment->id,
                        'notes' => "Mencionado en comentario por " . auth()->user()->name,
                    ]
                );
            }
        }
    }
}
