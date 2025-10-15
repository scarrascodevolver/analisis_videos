<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Category;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index()
    {
        $users = User::with(['profile.category'])->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $categories = Category::all();
        return view('admin.users.create', compact('categories'));
    }

    /**
     * Store a newly created user in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'in:jugador,entrenador,analista,staff,director_club,director_tecnico'],
            'user_category_id' => ['nullable', 'exists:categories,id'],
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        // Create user profile
        UserProfile::create([
            'user_id' => $user->id,
            'user_category_id' => $validated['user_category_id'] ?? null,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load([
            'profile.category',
            'uploadedVideos.category',
            'assignedVideos.video.category',
            'videoComments',
            'videoAnnotations',
            'assignedByMe'
        ]);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $categories = Category::all();
        $user->load('profile');
        return view('admin.users.edit', compact('user', 'categories'));
    }

    /**
     * Update the specified user in storage
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:jugador,entrenador,analista,staff,director_club,director_tecnico'],
            'user_category_id' => ['nullable', 'exists:categories,id'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        // Update user
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Update or create profile
        if ($user->profile) {
            $user->profile->update([
                'user_category_id' => $validated['user_category_id'] ?? null,
            ]);
        } else {
            UserProfile::create([
                'user_id' => $user->id,
                'user_category_id' => $validated['user_category_id'] ?? null,
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified user from storage
     */
    public function destroy(User $user)
    {
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        // Delete user (cascade will delete profile)
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
