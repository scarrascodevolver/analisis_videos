<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);

        $org  = auth()->user()->currentOrganization();
        $club = Club::create(['name' => $request->name]);

        return response()->json(['id' => $club->id, 'name' => $club->name, 'slug' => $club->slug]);
    }

    public function rename(Request $request, Club $club)
    {
        $this->authorize('update', $club);
        $request->validate(['name' => 'required|string|max:100']);

        $club->update([
            'name' => $request->name,
            'slug' => Club::makeUniqueSlug($request->name, $club->organization_id),
        ]);

        return response()->json(['ok' => true, 'name' => $club->name]);
    }

    public function destroy(Club $club)
    {
        $this->authorize('delete', $club);
        $club->delete();

        return response()->json(['ok' => true]);
    }

    public function autocomplete(Request $request)
    {
        $org   = auth()->user()->currentOrganization();
        $clubs = Club::where('name', 'like', '%' . $request->q . '%')
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name']);

        return response()->json($clubs);
    }
}
