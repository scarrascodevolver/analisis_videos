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
}
