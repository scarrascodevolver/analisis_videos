<?php

namespace App\Http\Controllers;

use App\Models\RivalTeam;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RivalTeamController extends Controller
{
    /**
     * Display a listing of rival teams
     */
    public function index()
    {
        $rivals = RivalTeam::orderBy('name')->paginate(20);

        return view('rival-teams.index', compact('rivals'));
    }

    /**
     * Show the form for creating a new rival team
     */
    public function create()
    {
        return view('rival-teams.create');
    }

    /**
     * Store a newly created rival team
     */
    public function store(Request $request)
    {
        $orgId = auth()->user()->currentOrganization()->id;

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $rival = RivalTeam::firstOrCreate(
            ['organization_id' => $orgId, 'name' => strtoupper(trim($request->name))],
            ['code' => $request->code, 'city' => $request->city, 'notes' => $request->notes]
        );

        // AJAX (fetch desde create.blade.php) espera JSON con id
        if ($request->expectsJson() || $request->wantsJson() || $request->header('Content-Type') === 'application/json') {
            return response()->json(['id' => $rival->id, 'name' => $rival->name]);
        }

        return redirect()->route('admin.rival-teams.index')
            ->with('success', 'Equipo rival creado exitosamente');
    }

    /**
     * Show the form for editing the specified rival team
     */
    public function edit(RivalTeam $rivalTeam)
    {
        return view('rival-teams.edit', compact('rivalTeam'));
    }

    /**
     * Update the specified rival team
     */
    public function update(Request $request, RivalTeam $rivalTeam)
    {
        $orgId = auth()->user()->currentOrganization()->id;

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rival_teams')->ignore($rivalTeam->id)->where(function ($query) use ($orgId) {
                    return $query->where('organization_id', $orgId);
                }),
            ],
            'code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $rivalTeam->update($request->only(['name', 'code', 'city', 'notes']));

        return redirect()->route('rival-teams.index')
            ->with('success', 'Equipo rival actualizado exitosamente');
    }

    /**
     * Remove the specified rival team from storage
     */
    public function destroy(RivalTeam $rivalTeam)
    {
        // Check if there are videos associated with this rival
        $videosCount = $rivalTeam->videos()->count();

        if ($videosCount > 0) {
            return back()->with('error', "No se puede eliminar: hay {$videosCount} video(s) asociado(s) a este rival");
        }

        $rivalTeam->delete();

        return redirect()->route('rival-teams.index')
            ->with('success', 'Equipo rival eliminado exitosamente');
    }

    /**
     * API endpoint for autocomplete (Select2)
     */
    public function autocomplete(Request $request)
    {
        $searchTerm = $request->get('q', '');

        $rivals = RivalTeam::search($searchTerm)
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'code', 'city']);

        // Format for Select2
        $results = $rivals->map(function ($rival) {
            return [
                'id' => $rival->id,
                'text' => $rival->display_name,
                'name' => $rival->name,
                'code' => $rival->code,
                'city' => $rival->city,
            ];
        });

        return response()->json($results);
    }
}
