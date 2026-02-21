<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryManagementController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index()
    {
        $categories = Category::withCount(['videos', 'userProfiles'])->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:500',
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit(Category $category)
    {
        $usersCount = $category->userProfiles()->count();
        $videosCount = $category->videos()->count();

        return view('admin.categories.edit', compact('category', 'usersCount', 'videosCount'));
    }

    /**
     * Update the specified category in storage
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
            'description' => 'nullable|string|max:500',
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    /**
     * Remove the specified category from storage
     */
    public function destroy(Category $category)
    {
        // Check if category has users or videos
        $usersCount = $category->userProfiles()->count();
        $videosCount = $category->videos()->count();

        if ($usersCount > 0 || $videosCount > 0) {
            return redirect()->route('admin.categories.index')
                ->with('error', "No se puede eliminar la categoría. Tiene $usersCount usuarios y $videosCount videos asociados.");
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }

    /**
     * Delete a category via AJAX from the folder context menu.
     * All associated videos are permanently deleted from Spaces, local storage,
     * and Bunny Stream before the category row is removed.
     * DELETE /api/categories/{category}
     */
    public function apiDestroy(Category $category): \Illuminate\Http\JsonResponse
    {
        set_time_limit(120);

        $videos = $category->videos()->with('organization')->get();
        $deletedCount = 0;

        foreach ($videos as $video) {
            // Spaces — archivo principal
            try {
                if ($video->file_path && \Storage::disk('spaces')->exists($video->file_path)) {
                    \Storage::disk('spaces')->delete($video->file_path);
                }
            } catch (\Exception $e) {
                \Log::warning("Category delete — Spaces delete failed for video {$video->id}: " . $e->getMessage());
            }

            // Almacenamiento local — archivo principal
            try {
                \Storage::disk('public')->delete($video->file_path);
            } catch (\Exception $e) {
                // silencioso
            }

            // Thumbnail
            if ($video->thumbnail_path) {
                try {
                    if (\Storage::disk('spaces')->exists($video->thumbnail_path)) {
                        \Storage::disk('spaces')->delete($video->thumbnail_path);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Category delete — Spaces thumbnail delete failed for video {$video->id}: " . $e->getMessage());
                }
                try {
                    \Storage::disk('public')->delete($video->thumbnail_path);
                } catch (\Exception $e) {
                    // silencioso
                }
            }

            // Archivo original (antes de compresión), si difiere del principal
            if ($video->original_file_path && $video->original_file_path !== $video->file_path) {
                try {
                    if (\Storage::disk('spaces')->exists($video->original_file_path)) {
                        \Storage::disk('spaces')->delete($video->original_file_path);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Category delete — Spaces original delete failed for video {$video->id}: " . $e->getMessage());
                }
                try {
                    \Storage::disk('public')->delete($video->original_file_path);
                } catch (\Exception $e) {
                    // silencioso
                }
            }

            // Bunny Stream
            if ($video->bunny_video_id) {
                try {
                    \App\Services\BunnyStreamService::forOrganization($video->organization)
                        ->deleteVideo($video->bunny_video_id);
                } catch (\Exception $e) {
                    \Log::warning("Category delete — Bunny delete failed for video {$video->id}: " . $e->getMessage());
                }
            }

            // Base de datos (activa el boot method del modelo: cancela jobs pendientes,
            // elimina asignaciones y demás relaciones en cascada)
            $video->delete();
            $deletedCount++;
        }

        $category->delete();

        return response()->json([
            'ok' => true,
            'message' => $deletedCount > 0
                ? "Categoría eliminada con {$deletedCount} video(s) borrados del servidor."
                : 'Categoría eliminada.',
            'videos_deleted' => $deletedCount,
        ]);
    }
}
