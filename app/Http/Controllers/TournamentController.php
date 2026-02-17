<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    /**
     * Show the tournament management index.
     * GET /tournaments
     */
    public function index(): \Illuminate\View\View
    {
        $tournaments = Tournament::withCount('videos')->orderBy('name')->get();

        return view('tournaments.index', compact('tournaments'));
    }

    /**
     * Update a tournament's name and season inline.
     * PUT /tournaments/{tournament}
     */
    public function update(Request $request, Tournament $tournament): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'season' => 'nullable|string|max:20',
        ]);

        $tournament->update($request->only('name', 'season'));

        return response()->json(['success' => true]);
    }

    /**
     * Delete a tournament. Refuses if it has associated videos.
     * DELETE /tournaments/{tournament}
     */
    public function destroy(Tournament $tournament): \Illuminate\Http\RedirectResponse
    {
        if ($tournament->videos()->exists()) {
            return back()->with('error', 'No se puede eliminar: el torneo tiene videos asociados.');
        }

        $tournament->delete();

        return back()->with('success', 'Torneo eliminado correctamente.');
    }

    /**
     * Autocomplete para Select2 — busca torneos de la organización actual.
     * GET /api/tournaments/autocomplete?q=...
     */
    public function autocomplete(Request $request)
    {
        $q = $request->input('q', '');

        $tournaments = Tournament::where(function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                ->orWhere('season', 'like', "%{$q}%");
        })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'season']);

        return response()->json([
            'results' => $tournaments->map(fn ($t) => [
                'id'   => $t->id,
                'text' => $t->season ? "{$t->name} ({$t->season})" : $t->name,
            ]),
        ]);
    }

    /**
     * Crear torneo desde el formulario si no existe.
     * POST /api/tournaments
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'season' => 'nullable|string|max:20',
        ]);

        $org = auth()->user()->currentOrganization();

        $tournament = Tournament::firstOrCreate(
            [
                'organization_id' => $org->id,
                'name'            => $request->name,
                'season'          => $request->season,
            ]
        );

        return response()->json([
            'id'   => $tournament->id,
            'text' => $tournament->season
                ? "{$tournament->name} ({$tournament->season})"
                : $tournament->name,
        ]);
    }
}
