<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
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
