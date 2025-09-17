<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Video;

class PlayerApiController extends Controller
{
    /**
     * Get all players for display
     */
    public function all(Request $request)
    {
        // Get all players (users with role 'jugador')
        $players = User::where('role', 'jugador')
            ->with(['profile.category'])
            ->withCount(['assignedVideos as video_count'])
            ->orderBy('name')
            ->get();

        // Format the response
        $formattedPlayers = $players->map(function($player) {
            return [
                'id' => $player->id,
                'name' => $player->name,
                'email' => $player->email,
                'profile' => [
                    'position' => $player->profile->position ?? null,
                    'secondary_position' => $player->profile->secondary_position ?? null,
                    'category' => $player->profile->category ? [
                        'id' => $player->profile->category->id,
                        'name' => $player->profile->category->name
                    ] : null
                ],
                'video_count' => $player->video_count
            ];
        });

        return response()->json([
            'players' => $formattedPlayers
        ]);
    }
    /**
     * Search players by name, position, or category
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'players' => []
            ]);
        }

        // Get players (users with role 'jugador')
        $players = User::where('role', 'jugador')
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->orWhereHas('profile', function($q) use ($query) {
                $q->where('position', 'LIKE', "%{$query}%")
                  ->orWhere('secondary_position', 'LIKE', "%{$query}%");
            })
            ->orWhereHas('profile.category', function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->with(['profile.category'])
            ->withCount(['assignedVideos as video_count'])
            ->orderBy('name')
            ->limit(20)
            ->get();

        // Format the response
        $formattedPlayers = $players->map(function($player) {
            return [
                'id' => $player->id,
                'name' => $player->name,
                'email' => $player->email,
                'profile' => [
                    'position' => $player->profile->position ?? null,
                    'secondary_position' => $player->profile->secondary_position ?? null,
                    'category' => $player->profile->category ? [
                        'id' => $player->profile->category->id,
                        'name' => $player->profile->category->name
                    ] : null
                ],
                'video_count' => $player->video_count
            ];
        });

        return response()->json([
            'players' => $formattedPlayers
        ]);
    }

    /**
     * Get videos assigned to a specific player
     */
    public function playerVideos(Request $request, User $player)
    {
        // Verify the player is actually a player
        if ($player->role !== 'jugador') {
            return response()->json([
                'error' => 'Usuario no es un jugador'
            ], 404);
        }

        // Get videos assigned to this player via VideoAssignment model
        $assignments = $player->assignedVideos()->with(['video.analyzedTeam', 'video.rivalTeam'])->get();

        // Format videos with assignment status
        $formattedVideos = $assignments->map(function($assignment) {
            if (!$assignment->video) {
                return null;
            }

            $video = $assignment->video;

            return [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'match_date' => $video->match_date,
                'analyzed_team' => $video->analyzedTeam ? [
                    'id' => $video->analyzedTeam->id,
                    'name' => $video->analyzedTeam->name
                ] : null,
                'rival_team' => $video->rivalTeam ? [
                    'id' => $video->rivalTeam->id,
                    'name' => $video->rivalTeam->name
                ] : null,
                'pivot' => [
                    'status' => 'assigned', // All assignments are simply 'assigned' since status was removed
                    'assigned_at' => $assignment->created_at ?? null
                ]
            ];
        })->filter()->sortByDesc('match_date')->values();

        // Calculate stats (simplified since status is no longer tracked)
        $stats = [
            'total' => $assignments->count(),
            'completed' => 0, // No status tracking means we can't determine completion
            'pending' => $assignments->count(), // All assignments are considered pending
        ];

        $stats['completion_rate'] = 0; // No completion tracking available

        return response()->json([
            'videos' => $formattedVideos,
            'stats' => $stats
        ]);
    }
}