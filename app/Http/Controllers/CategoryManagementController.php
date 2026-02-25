<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\VideoDeletionService;
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
    public function apiDestroy(Category $category, VideoDeletionService $videoDeletionService): \Illuminate\Http\JsonResponse
    {
        set_time_limit(120);

        $videos = $category->videos()->with('organization')->get();
        $deletedCount = 0;
        $failed = [];
        $warnings = [];

        foreach ($videos as $video) {
            try {
                $result = $videoDeletionService->deleteVideo($video);
                $deletedCount++;
                if (! empty($result['warnings'])) {
                    $warnings[$video->id] = $result['warnings'];
                }
            } catch (\Throwable $e) {
                \Log::error("Category delete failed for video {$video->id}", [
                    'category_id' => $category->id,
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ]);

                $failed[] = [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (! empty($failed)) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo eliminar la categoría porque algunos videos fallaron al borrarse.',
                'videos_deleted' => $deletedCount,
                'failed' => $failed,
                'warnings' => $warnings,
            ], 500);
        }

        $category->delete();

        return response()->json([
            'ok' => true,
            'message' => $deletedCount > 0
                ? "Categoría eliminada con {$deletedCount} video(s) borrados del servidor."
                : 'Categoría eliminada.',
            'videos_deleted' => $deletedCount,
            'warnings' => $warnings,
        ]);
    }
}
