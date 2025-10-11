<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    /**
     * Display a listing of divisions
     */
    public function index()
    {
        $divisions = Division::withCount('videos')->ordered()->get();
        return view('admin.divisions.index', compact('divisions'));
    }

    /**
     * Show the form for creating a new division
     */
    public function create()
    {
        return view('admin.divisions.create');
    }

    /**
     * Store a newly created division in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ]);

        // Convert checkbox value
        $validated['active'] = $request->has('active') ? true : false;

        // Auto-assign sort_order if not provided
        if (!isset($validated['sort_order'])) {
            $maxOrder = Division::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxOrder + 1;
        }

        Division::create($validated);

        return redirect()->route('admin.divisions.index')
            ->with('success', 'División creada exitosamente.');
    }

    /**
     * Show the form for editing the specified division
     */
    public function edit(Division $division)
    {
        $videosCount = $division->videos()->count();
        return view('admin.divisions.edit', compact('division', 'videosCount'));
    }

    /**
     * Update the specified division in storage
     */
    public function update(Request $request, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name,' . $division->id,
            'description' => 'nullable|string|max:500',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ]);

        // Convert checkbox value
        $validated['active'] = $request->has('active') ? true : false;

        $division->update($validated);

        return redirect()->route('admin.divisions.index')
            ->with('success', 'División actualizada exitosamente.');
    }

    /**
     * Remove the specified division from storage
     */
    public function destroy(Division $division)
    {
        // Check if division has videos
        $videosCount = $division->videos()->count();

        if ($videosCount > 0) {
            return redirect()->route('admin.divisions.index')
                ->with('error', "No se puede eliminar la división. Tiene $videosCount videos asociados.");
        }

        $division->delete();

        return redirect()->route('admin.divisions.index')
            ->with('success', 'División eliminada exitosamente.');
    }
}
