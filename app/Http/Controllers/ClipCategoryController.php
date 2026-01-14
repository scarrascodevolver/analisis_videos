<?php

namespace App\Http\Controllers;

use App\Models\ClipCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClipCategoryController extends Controller
{
    public function index()
    {
        $categories = ClipCategory::where('organization_id', auth()->user()->current_organization_id)
            ->ordered()
            ->get();

        return view('admin.clip-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.clip-categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
            'hotkey' => 'nullable|string|size:1',
            'lead_seconds' => 'required|integer|min:0|max:30',
            'lag_seconds' => 'required|integer|min:0|max:30',
        ]);

        $orgId = auth()->user()->current_organization_id;

        // Verificar hotkey único en la org
        if ($request->hotkey) {
            $exists = ClipCategory::where('organization_id', $orgId)
                ->where('hotkey', strtolower($request->hotkey))
                ->exists();

            if ($exists) {
                return back()->withErrors(['hotkey' => 'Esta tecla ya está asignada a otra categoría']);
            }
        }

        $maxOrder = ClipCategory::where('organization_id', $orgId)->max('sort_order') ?? 0;

        ClipCategory::create([
            'organization_id' => $orgId,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color,
            'hotkey' => $request->hotkey ? strtolower($request->hotkey) : null,
            'lead_seconds' => $request->lead_seconds,
            'lag_seconds' => $request->lag_seconds,
            'sort_order' => $maxOrder + 1,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.clip-categories.index')
            ->with('success', 'Categoría creada exitosamente');
    }

    public function edit(ClipCategory $clipCategory)
    {
        return view('admin.clip-categories.edit', ['category' => $clipCategory]);
    }

    public function update(Request $request, ClipCategory $clipCategory)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
            'hotkey' => 'nullable|string|size:1',
            'lead_seconds' => 'required|integer|min:0|max:30',
            'lag_seconds' => 'required|integer|min:0|max:30',
            'is_active' => 'boolean',
        ]);

        $orgId = auth()->user()->current_organization_id;

        // Verificar hotkey único (excluyendo el actual)
        if ($request->hotkey) {
            $exists = ClipCategory::where('organization_id', $orgId)
                ->where('hotkey', strtolower($request->hotkey))
                ->where('id', '!=', $clipCategory->id)
                ->exists();

            if ($exists) {
                return back()->withErrors(['hotkey' => 'Esta tecla ya está asignada a otra categoría']);
            }
        }

        $clipCategory->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color,
            'hotkey' => $request->hotkey ? strtolower($request->hotkey) : null,
            'lead_seconds' => $request->lead_seconds,
            'lag_seconds' => $request->lag_seconds,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.clip-categories.index')
            ->with('success', 'Categoría actualizada exitosamente');
    }

    public function destroy(ClipCategory $clipCategory)
    {
        // No eliminar si tiene clips
        if ($clipCategory->clips()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar: tiene clips asociados']);
        }

        $clipCategory->delete();

        return redirect()->route('admin.clip-categories.index')
            ->with('success', 'Categoría eliminada exitosamente');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:clip_categories,id',
        ]);

        foreach ($request->order as $index => $id) {
            ClipCategory::where('id', $id)
                ->where('organization_id', auth()->user()->current_organization_id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    // API: Obtener categorías para el player
    public function apiIndex()
    {
        $categories = ClipCategory::where('organization_id', auth()->user()->current_organization_id)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'color', 'hotkey', 'lead_seconds', 'lag_seconds']);

        return response()->json($categories);
    }
}
