<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentDivision;
use Illuminate\Http\Request;

class TournamentDivisionController extends Controller
{
    /**
     * GET /api/tournaments/{tournament}/divisions
     * Returns divisions for a tournament (used by share modal and explore page)
     */
    public function index(Tournament $tournament)
    {
        // Allow cross-org access — clubs need to see association's tournament divisions
        $divisions = TournamentDivision::where('tournament_id', $tournament->id)
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'order']);

        return response()->json(['divisions' => $divisions]);
    }

    /**
     * POST /api/tournaments/{tournament}/divisions
     * Create a division (association only)
     */
    public function store(Request $request, Tournament $tournament)
    {
        $org = auth()->user()->currentOrganization();

        if (! $org || $tournament->organization_id !== $org->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $request->validate([
            'name'  => 'required|string|max:100',
            'order' => 'nullable|integer|min:0',
        ]);

        $exists = TournamentDivision::where('tournament_id', $tournament->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Ya existe una división con ese nombre.'], 422);
        }

        $maxOrder = TournamentDivision::where('tournament_id', $tournament->id)->max('order') ?? 0;

        $division = TournamentDivision::create([
            'tournament_id' => $tournament->id,
            'name'          => $request->name,
            'order'         => $request->order ?? ($maxOrder + 1),
        ]);

        return response()->json(['ok' => true, 'division' => $division]);
    }

    /**
     * DELETE /api/divisions/{division}
     * Remove a division (only if no active registrations or shares)
     */
    public function destroy(TournamentDivision $division)
    {
        $org = auth()->user()->currentOrganization();
        $tournament = Tournament::withoutGlobalScope('organization')->find($division->tournament_id);

        if (! $org || ! $tournament || $tournament->organization_id !== $org->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $hasRegistrations = $division->registrations()->where('status', 'active')->exists();
        $hasShares = $division->videoShares()->where('status', 'active')->exists();

        if ($hasRegistrations || $hasShares) {
            return response()->json([
                'error' => 'No se puede eliminar: hay clubes inscriptos o videos compartidos en esta división.',
            ], 422);
        }

        $division->delete();

        return response()->json(['ok' => true]);
    }
}
