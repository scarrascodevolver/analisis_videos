<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamManagementController extends Controller
{
    /**
     * Display a listing of teams
     */
    public function index()
    {
        $teams = Team::withCount(['analyzedVideos', 'rivalVideos'])->get();
        return view('admin.teams.index', compact('teams'));
    }

    /**
     * Show the form for creating a new team
     */
    public function create()
    {
        return view('admin.teams.create');
    }

    /**
     * Store a newly created team in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'abbreviation' => 'nullable|string|max:10',
            'is_own_team' => 'boolean',
        ]);

        // Convert checkbox value
        $validated['is_own_team'] = $request->has('is_own_team') ? true : false;

        Team::create($validated);

        return redirect()->route('admin.teams.index')
            ->with('success', 'Equipo creado exitosamente.');
    }

    /**
     * Show the form for editing the specified team
     */
    public function edit(Team $team)
    {
        $analyzedVideosCount = $team->analyzedVideos()->count();
        $rivalVideosCount = $team->rivalVideos()->count();

        return view('admin.teams.edit', compact('team', 'analyzedVideosCount', 'rivalVideosCount'));
    }

    /**
     * Update the specified team in storage
     */
    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'abbreviation' => 'nullable|string|max:10',
            'is_own_team' => 'boolean',
        ]);

        // Convert checkbox value
        $validated['is_own_team'] = $request->has('is_own_team') ? true : false;

        $team->update($validated);

        return redirect()->route('admin.teams.index')
            ->with('success', 'Equipo actualizado exitosamente.');
    }

    /**
     * Remove the specified team from storage
     */
    public function destroy(Team $team)
    {
        // Check if team has videos
        $analyzedVideosCount = $team->analyzedVideos()->count();
        $rivalVideosCount = $team->rivalVideos()->count();
        $totalVideos = $analyzedVideosCount + $rivalVideosCount;

        if ($totalVideos > 0) {
            return redirect()->route('admin.teams.index')
                ->with('error', "No se puede eliminar el equipo. Tiene $totalVideos videos asociados.");
        }

        $team->delete();

        return redirect()->route('admin.teams.index')
            ->with('success', 'Equipo eliminado exitosamente.');
    }
}
