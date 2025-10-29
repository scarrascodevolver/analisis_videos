<?php

namespace App\Http\Controllers;

use App\Models\RugbySituation;
use Illuminate\Http\Request;

class RugbySituationController extends Controller
{
    /**
     * Display a listing of rugby situations
     */
    public function index()
    {
        $situations = RugbySituation::withCount('videos')->ordered()->get();
        return view('admin.situations.index', compact('situations'));
    }

    /**
     * Show the form for creating a new rugby situation
     */
    public function create()
    {
        return view('admin.situations.create');
    }

    /**
     * Store a newly created rugby situation in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Convert checkbox value
        $validated['active'] = $request->has('active') ? true : false;

        // Auto-assign sort_order if not provided
        if (!isset($validated['sort_order'])) {
            $maxOrder = RugbySituation::max('sort_order') ?? 0;
            $validated['sort_order'] = $maxOrder + 1;
        }

        RugbySituation::create($validated);

        return redirect()->route('admin.situations.index')
            ->with('success', 'Situación de rugby creada exitosamente.');
    }

    /**
     * Show the form for editing the specified rugby situation
     */
    public function edit(RugbySituation $situation)
    {
        $videosCount = $situation->videos()->count();
        return view('admin.situations.edit', compact('situation', 'videosCount'));
    }

    /**
     * Update the specified rugby situation in storage
     */
    public function update(Request $request, RugbySituation $situation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Convert checkbox value
        $validated['active'] = $request->has('active') ? true : false;

        $situation->update($validated);

        return redirect()->route('admin.situations.index')
            ->with('success', 'Situación de rugby actualizada exitosamente.');
    }

    /**
     * Remove the specified rugby situation from storage
     */
    public function destroy(RugbySituation $situation)
    {
        // Check if situation has videos
        $videosCount = $situation->videos()->count();

        if ($videosCount > 0) {
            return redirect()->route('admin.situations.index')
                ->with('error', "No se puede eliminar la situación. Tiene $videosCount videos asociados.");
        }

        $situation->delete();

        return redirect()->route('admin.situations.index')
            ->with('success', 'Situación de rugby eliminada exitosamente.');
    }

    /**
     * Reorder situations (AJAX endpoint)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:rugby_situations,id'
        ]);

        foreach ($validated['order'] as $index => $id) {
            RugbySituation::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
