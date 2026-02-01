<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    /**
     * Marcar una notificación específica como leída
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notificación no encontrada',
        ], 404);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas',
        ]);
    }

    /**
     * Obtener todas las notificaciones del usuario
     */
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }
}
