<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoViewController extends Controller
{
    /**
     * Track a video view
     */
    public function track(Request $request, Video $video)
    {
        $userId = Auth::id();

        // Check cooldown period (30 minutes)
        if (VideoView::isWithinCooldown($video->id, $userId, 30)) {
            return response()->json([
                'success' => true,
                'message' => 'View within cooldown period',
                'cooldown' => true,
            ]);
        }

        // Create new view record
        $view = VideoView::create([
            'video_id' => $video->id,
            'user_id' => $userId,
            'viewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'View tracked successfully',
            'cooldown' => false,
            'view_id' => $view->id,
            'total_views' => $video->view_count,
            'unique_viewers' => $video->unique_viewers,
        ]);
    }

    /**
     * Update watch duration
     */
    public function updateDuration(Request $request, Video $video)
    {
        $validated = $request->validate([
            'view_id' => 'required|exists:video_views,id',
            'duration' => 'required|integer|min:0',
        ]);

        $view = VideoView::findOrFail($validated['view_id']);

        // Only update if it belongs to the authenticated user
        if ($view->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $view->update([
            'watch_duration' => $validated['duration'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Duration updated',
        ]);
    }

    /**
     * Mark video as completed
     */
    public function markCompleted(Request $request, Video $video)
    {
        $validated = $request->validate([
            'view_id' => 'required|exists:video_views,id',
        ]);

        $view = VideoView::findOrFail($validated['view_id']);

        // Only update if it belongs to the authenticated user
        if ($view->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $view->update([
            'completed' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Video marked as completed',
        ]);
    }

    /**
     * Get video statistics (for analysts, coaches and players)
     */
    public function getStats(Video $video)
    {
        // Check permissions
        if (! in_array(Auth::user()->role, ['analista', 'entrenador', 'jugador'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = $video->getViewStats();

        return response()->json([
            'success' => true,
            'total_views' => $video->view_count,
            'unique_viewers' => $video->unique_viewers,
            'stats' => $stats,
        ]);
    }
}
