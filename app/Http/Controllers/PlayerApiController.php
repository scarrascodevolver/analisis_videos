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
        // Obtener la organización actual del usuario autenticado
        $currentOrg = auth()->user()->currentOrganization();

        if (!$currentOrg) {
            return response()->json([
                'players' => [],
                'error' => 'No hay organización seleccionada'
            ]);
        }

        // Get all players, coaches and staff (users with role 'jugador', 'entrenador' or staff that can receive assignments)
        // Filtrados por organización actual
        $players = User::whereHas('organizations', function($q) use ($currentOrg) {
                $q->where('organizations.id', $currentOrg->id);
            })
            ->where(function($query) {
                $query->where('role', 'jugador')
                      ->orWhere('role', 'entrenador')
                      ->orWhereHas('profile', function($q) {
                          $q->where('can_receive_assignments', true);
                      });
            })
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
                    'avatar' => $player->profile->avatar ?? null,
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

        // Obtener la organización actual del usuario autenticado
        $currentOrg = auth()->user()->currentOrganization();

        if (!$currentOrg) {
            return response()->json([
                'players' => [],
                'error' => 'No hay organización seleccionada'
            ]);
        }

        // Get players, coaches and staff filtrados por organización actual
        $players = User::whereHas('organizations', function($orgQuery) use ($currentOrg) {
                $orgQuery->where('organizations.id', $currentOrg->id);
            })
            ->where(function($mainQuery) {
                $mainQuery->where('role', 'jugador')
                          ->orWhere('role', 'entrenador')
                          ->orWhereHas('profile', function($q) {
                              $q->where('can_receive_assignments', true);
                          });
            })
            ->where(function($searchQuery) use ($query) {
                $searchQuery->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhereHas('profile', function($q) use ($query) {
                      $q->where('position', 'LIKE', "%{$query}%")
                        ->orWhere('secondary_position', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('profile.category', function($q) use ($query) {
                      $q->where('name', 'LIKE', "%{$query}%");
                  });
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
                    'avatar' => $player->profile->avatar ?? null,
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
        // Verificar que el jugador pertenece a la organización actual
        $currentOrg = auth()->user()->currentOrganization();

        if (!$currentOrg) {
            return response()->json([
                'error' => 'No hay organización seleccionada'
            ], 400);
        }

        // Verificar que el jugador pertenece a la misma organización
        $playerBelongsToOrg = $player->organizations()->where('organizations.id', $currentOrg->id)->exists();

        if (!$playerBelongsToOrg) {
            return response()->json([
                'error' => 'Jugador no encontrado'
            ], 404);
        }

        // Verify the user is actually a player, coach or staff that can receive assignments
        if (!in_array($player->role, ['jugador', 'entrenador']) && !($player->profile && $player->profile->can_receive_assignments)) {
            return response()->json([
                'error' => 'Usuario no puede recibir asignaciones de videos'
            ], 404);
        }

        // Get videos assigned to this player via VideoAssignment model
        $assignments = $player->assignedVideos()->with(['video'])->get();

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
                'analyzed_team' => $video->analyzed_team_name,
                'rival_team' => $video->rival_team_name,
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