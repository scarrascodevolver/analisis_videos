<?php

namespace App\Http\Controllers;

use App\Models\ClipCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClipCategoryController extends Controller
{
    public function index()
    {
        $categories = ClipCategory::where('organization_id', auth()->user()->currentOrganization()->id)
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
        $rules = [
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
            'hotkey' => 'nullable|string|max:1',
            'lead_seconds' => 'nullable|integer|min:0|max:30',
            'lag_seconds' => 'nullable|integer|min:0|max:30',
        ];

        // Para AJAX, manejar errores de validación como JSON
        if ($request->wantsJson() || $request->ajax()) {
            $validator = \Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
        } else {
            $request->validate($rules);
        }

        $orgId = auth()->user()->currentOrganization()->id;

        // Verificar hotkey único en la org
        if ($request->hotkey) {
            $exists = ClipCategory::where('organization_id', $orgId)
                ->where('hotkey', strtolower($request->hotkey))
                ->exists();

            if ($exists) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Esta tecla ya está asignada a otra categoría',
                    ], 422);
                }

                return back()->withErrors(['hotkey' => 'Esta tecla ya está asignada a otra categoría']);
            }
        }

        $maxOrder = ClipCategory::where('organization_id', $orgId)->max('sort_order') ?? 0;

        $category = ClipCategory::create([
            'organization_id' => $orgId,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color,
            'hotkey' => $request->hotkey ? strtolower($request->hotkey) : null,
            'lead_seconds' => $request->lead_seconds ?? 3,
            'lag_seconds' => $request->lag_seconds ?? 3,
            'sort_order' => $maxOrder + 1,
            'created_by' => auth()->id(),
        ]);

        // Respuesta JSON para AJAX
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'category' => $category,
                'message' => 'Categoría creada exitosamente',
            ]);
        }

        return redirect()->route('admin.clip-categories.index')
            ->with('success', 'Categoría creada exitosamente');
    }

    public function edit(ClipCategory $clipCategory)
    {
        return view('admin.clip-categories.edit', ['category' => $clipCategory]);
    }

    public function update(Request $request, ClipCategory $clipCategory)
    {
        $rules = [
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
            'hotkey' => 'nullable|string|max:1',
            'lead_seconds' => 'nullable|integer|min:0|max:30',
            'lag_seconds' => 'nullable|integer|min:0|max:30',
            'is_active' => 'boolean',
        ];

        // Para AJAX, manejar errores de validación como JSON
        if ($request->wantsJson() || $request->ajax()) {
            $validator = \Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
        } else {
            $request->validate($rules);
        }

        $orgId = auth()->user()->currentOrganization()->id;

        // Verificar hotkey único (excluyendo el actual)
        if ($request->hotkey) {
            $exists = ClipCategory::where('organization_id', $orgId)
                ->where('hotkey', strtolower($request->hotkey))
                ->where('id', '!=', $clipCategory->id)
                ->exists();

            if ($exists) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Esta tecla ya está asignada a otra categoría',
                    ], 422);
                }

                return back()->withErrors(['hotkey' => 'Esta tecla ya está asignada a otra categoría']);
            }
        }

        $clipCategory->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color,
            'hotkey' => $request->hotkey ? strtolower($request->hotkey) : null,
            'lead_seconds' => $request->lead_seconds ?? $clipCategory->lead_seconds,
            'lag_seconds' => $request->lag_seconds ?? $clipCategory->lag_seconds,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Respuesta JSON para AJAX
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'category' => $clipCategory->fresh(),
                'message' => 'Categoría actualizada exitosamente',
            ]);
        }

        return redirect()->route('admin.clip-categories.index')
            ->with('success', 'Categoría actualizada exitosamente');
    }

    public function destroy(Request $request, ClipCategory $clipCategory)
    {
        // No eliminar si tiene clips
        if ($clipCategory->clips()->count() > 0) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: tiene clips asociados',
                ], 422);
            }

            return back()->withErrors(['error' => 'No se puede eliminar: tiene clips asociados']);
        }

        $clipCategory->delete();

        // Respuesta JSON para AJAX
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente',
            ]);
        }

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
                ->where('organization_id', auth()->user()->currentOrganization()->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    // API: Obtener categorías para el player
    public function apiIndex()
    {
        $categories = ClipCategory::where('organization_id', auth()->user()->currentOrganization()->id)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'color', 'hotkey', 'lead_seconds', 'lag_seconds']);

        return response()->json($categories);
    }
}
