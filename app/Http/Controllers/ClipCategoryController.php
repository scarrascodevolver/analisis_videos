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
            'scope' => 'nullable|in:organization,user,video',
            'video_id' => 'nullable|integer|exists:videos,id',
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
        $userId = auth()->id();
        $scope = $request->scope ?? ClipCategory::SCOPE_ORGANIZATION;
        $videoId = $request->video_id;

        // Validar scope y video_id
        if ($scope === ClipCategory::SCOPE_VIDEO && !$videoId) {
            return $this->errorResponse($request, 'Se requiere video_id para categorías de video');
        }

        // Verificar hotkey único según el scope
        if ($request->hotkey) {
            $hotkeyExists = $this->checkHotkeyExists(
                strtolower($request->hotkey),
                $scope,
                $orgId,
                $userId,
                $videoId
            );

            if ($hotkeyExists) {
                return $this->errorResponse($request, 'Esta tecla ya está asignada a otra categoría');
            }
        }

        // Generar slug único según el scope
        $slug = $this->generateUniqueSlug($request->name, $scope, $orgId, $userId, $videoId);

        $maxOrder = ClipCategory::where('organization_id', $orgId)->max('sort_order') ?? 0;

        $categoryData = [
            'organization_id' => $orgId,
            'scope' => $scope,
            'name' => $request->name,
            'slug' => $slug,
            'color' => $request->color,
            'hotkey' => $request->hotkey ? strtolower($request->hotkey) : null,
            'lead_seconds' => $request->lead_seconds ?? 3,
            'lag_seconds' => $request->lag_seconds ?? 3,
            'sort_order' => $maxOrder + 1,
            'created_by' => $userId,
        ];

        // Agregar user_id o video_id según el scope
        if ($scope === ClipCategory::SCOPE_USER) {
            $categoryData['user_id'] = $userId;
        } elseif ($scope === ClipCategory::SCOPE_VIDEO) {
            $categoryData['video_id'] = $videoId;
        }

        $category = ClipCategory::create($categoryData);

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
        $userId = auth()->id();

        // Verificar hotkey único (excluyendo el actual)
        if ($request->hotkey) {
            $hotkeyExists = $this->checkHotkeyExists(
                strtolower($request->hotkey),
                $clipCategory->scope,
                $orgId,
                $userId,
                $clipCategory->video_id,
                $clipCategory->id
            );

            if ($hotkeyExists) {
                return $this->errorResponse($request, 'Esta tecla ya está asignada a otra categoría');
            }
        }

        // Generar slug único si cambió el nombre
        $slug = $clipCategory->slug;
        if ($request->name !== $clipCategory->name) {
            $slug = $this->generateUniqueSlug(
                $request->name,
                $clipCategory->scope,
                $orgId,
                $clipCategory->user_id,
                $clipCategory->video_id,
                $clipCategory->id
            );
        }

        $clipCategory->update([
            'name' => $request->name,
            'slug' => $slug,
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

    /**
     * API: Obtener categorías para el player
     * Devuelve categorías agrupadas por scope para el video actual
     */
    public function apiIndex(Request $request)
    {
        $orgId = auth()->user()->currentOrganization()->id;
        $userId = auth()->id();
        $videoId = $request->query('video_id');

        // Obtener todas las categorías visibles en este contexto
        $categories = ClipCategory::withoutGlobalScopes()
            ->forContext($orgId, $userId, $videoId)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'color', 'hotkey', 'lead_seconds', 'lag_seconds', 'scope']);

        // Agrupar por scope para la UI
        $grouped = [
            'templates' => $categories->where('scope', ClipCategory::SCOPE_ORGANIZATION)->values(),
            'personal' => $categories->where('scope', ClipCategory::SCOPE_USER)->values(),
            'video' => $categories->where('scope', ClipCategory::SCOPE_VIDEO)->values(),
        ];

        return response()->json([
            'categories' => $categories,
            'grouped' => $grouped,
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate a unique slug based on scope
     */
    private function generateUniqueSlug(
        string $name,
        string $scope,
        int $orgId,
        ?int $userId = null,
        ?int $videoId = null,
        ?int $excludeId = null
    ): string {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while ($this->slugExists($slug, $scope, $orgId, $userId, $videoId, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists in the given scope
     */
    private function slugExists(
        string $slug,
        string $scope,
        int $orgId,
        ?int $userId = null,
        ?int $videoId = null,
        ?int $excludeId = null
    ): bool {
        $query = ClipCategory::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('scope', $scope);

        // Filter by the appropriate ID based on scope
        switch ($scope) {
            case ClipCategory::SCOPE_ORGANIZATION:
                $query->where('organization_id', $orgId);
                break;
            case ClipCategory::SCOPE_USER:
                $query->where('user_id', $userId);
                break;
            case ClipCategory::SCOPE_VIDEO:
                $query->where('video_id', $videoId);
                break;
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if a hotkey already exists in the given scope
     */
    private function checkHotkeyExists(
        string $hotkey,
        string $scope,
        int $orgId,
        ?int $userId = null,
        ?int $videoId = null,
        ?int $excludeId = null
    ): bool {
        $query = ClipCategory::withoutGlobalScopes()
            ->where('hotkey', $hotkey)
            ->where('scope', $scope);

        // Filter by the appropriate ID based on scope
        switch ($scope) {
            case ClipCategory::SCOPE_ORGANIZATION:
                $query->where('organization_id', $orgId);
                break;
            case ClipCategory::SCOPE_USER:
                $query->where('user_id', $userId);
                break;
            case ClipCategory::SCOPE_VIDEO:
                $query->where('video_id', $videoId);
                break;
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Return error response based on request type
     */
    private function errorResponse(Request $request, string $message)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return back()->withErrors(['error' => $message]);
    }
}
