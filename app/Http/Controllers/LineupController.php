<?php

namespace App\Http\Controllers;

use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\RivalTeam;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LineupController extends Controller
{
    /**
     * GET /api/videos/{video}/lineups
     * Return all lineups (local + rival) for a video with their players.
     */
    public function index(Video $video): JsonResponse
    {
        $this->authorize('view', $video);

        $lineups = $video->lineups()
            ->with([
                'players.user:id,name',
                'players.rivalPlayer:id,name,shirt_number,usual_position',
            ])
            ->get();

        return response()->json(['lineups' => $lineups]);
    }

    /**
     * POST /api/videos/{video}/lineups
     * Create or update a lineup (local or rival) for a video.
     */
    public function store(Request $request, Video $video): JsonResponse
    {
        $this->authorize('update', $video);

        $validated = $request->validate([
            'team_type' => 'required|in:local,rival',
            'formation' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $lineup = $video->lineups()->updateOrCreate(
            ['team_type' => $validated['team_type']],
            [
                'organization_id' => auth()->user()->currentOrganization()->id,
                'created_by' => auth()->id(),
                'formation' => $validated['formation'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'lineup' => $lineup->load('players.user', 'players.rivalPlayer'),
        ]);
    }

    /**
     * POST /api/lineups/{lineup}/players
     * Add a player to a lineup.
     */
    public function addPlayer(Request $request, Lineup $lineup): JsonResponse
    {
        $this->authorize('update', $lineup->video);

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'rival_player_id' => 'nullable|integer|exists:rival_players,id',
            'player_name' => 'nullable|string|max:100',
            'shirt_number' => 'nullable|integer|min:1|max:23',
            'position_number' => 'nullable|integer|min:1|max:15',
            'status' => 'required|in:starter,substitute,unavailable',
        ]);

        // Remove existing player in the same shirt slot if present
        if (! empty($validated['shirt_number'])) {
            $lineup->players()->where('shirt_number', $validated['shirt_number'])->delete();
        }

        $player = $lineup->players()->create($validated);

        return response()->json([
            'player' => $player->load('user', 'rivalPlayer'),
        ]);
    }

    /**
     * PUT /api/lineup-players/{player}
     * Update an existing lineup player entry.
     */
    public function updatePlayer(Request $request, LineupPlayer $player): JsonResponse
    {
        $this->authorize('update', $player->lineup->video);

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'rival_player_id' => 'nullable|integer|exists:rival_players,id',
            'player_name' => 'nullable|string|max:100',
            'shirt_number' => 'nullable|integer|min:1|max:23',
            'position_number' => 'nullable|integer|min:1|max:15',
            'status' => 'nullable|in:starter,substitute,unavailable',
            'substitution_minute' => 'nullable|integer|min:0|max:120',
        ]);

        $player->update($validated);

        return response()->json([
            'player' => $player->fresh()->load('user', 'rivalPlayer'),
        ]);
    }

    /**
     * DELETE /api/lineup-players/{player}
     * Remove a player from a lineup.
     */
    public function removePlayer(LineupPlayer $player): JsonResponse
    {
        $this->authorize('update', $player->lineup->video);

        $player->delete();

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/rival-teams/{rivalTeam}/players
     * Return all known players for a rival team (reusable across matches).
     */
    public function rivalTeamPlayers(RivalTeam $rivalTeam): JsonResponse
    {
        $players = $rivalTeam->rivalPlayers()
            ->orderBy('shirt_number')
            ->get(['id', 'name', 'shirt_number', 'usual_position']);

        return response()->json(['players' => $players]);
    }

    /**
     * POST /api/rival-teams/{rivalTeam}/players
     * Create a new rival player and associate them with a rival team.
     */
    public function storeRivalPlayer(Request $request, RivalTeam $rivalTeam): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'shirt_number' => 'nullable|integer|min:1|max:23',
            'usual_position' => 'nullable|integer|min:1|max:15',
            'notes' => 'nullable|string|max:200',
        ]);

        $validated['organization_id'] = auth()->user()->currentOrganization()->id;

        $player = $rivalTeam->rivalPlayers()->create($validated);

        return response()->json(['player' => $player]);
    }
}
