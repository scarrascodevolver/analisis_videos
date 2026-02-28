<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Show the user's profile
     */
    public function show()
    {
        $user = Auth::user();
        $user->load('profile.category');

        return view('profile.show', compact('user'));
    }

    /**
     * Show the profile edit form
     */
    public function edit()
    {
        $user = Auth::user();
        $user->load('profile');
        $categories = Category::all();

        return view('profile.edit', compact('user', 'categories'));
    }

    /**
     * Update the user's profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string',
            'secondary_position' => 'nullable|string',
            'player_number' => 'nullable|integer|min:1|max:99',
            'weight' => 'nullable|integer|min:30|max:200',
            'height' => 'nullable|integer|min:100|max:230',
            'date_of_birth' => 'nullable|date',
            'user_category_id' => 'required|exists:categories,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,heic|max:25600',
        ]);

        // Update user basic info
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        // Handle avatar upload
        $profileData = [
            'position' => $request->position,
            'secondary_position' => $request->secondary_position,
            'player_number' => $request->player_number,
            'weight' => $request->weight,
            'height' => $request->height,
            'date_of_birth' => $request->date_of_birth,
            'user_category_id' => $request->user_category_id,
        ];

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->profile && $user->profile->avatar) {
                Storage::disk('public')->delete($user->profile->avatar);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $profileData['avatar'] = $avatarPath;
        }

        // Update or create profile
        if ($user->profile) {
            $user->profile->update($profileData);
        } else {
            $user->profile()->create(array_merge($profileData, ['user_id' => $user->id]));
        }

        return redirect()->route('profile.show')->with('success', 'Perfil actualizado correctamente');
    }

    /**
     * Remove the user's avatar
     */
    public function removeAvatar()
    {
        $user = Auth::user();

        if ($user->profile && $user->profile->avatar) {
            Storage::disk('public')->delete($user->profile->avatar);
            $user->profile->update(['avatar' => null]);
        }

        return redirect()->route('profile.edit')->with('success', 'Avatar eliminado correctamente');
    }

    /**
     * Show the change password form
     */
    public function showChangePasswordForm()
    {
        return view('profile.change-password');
    }

    /**
     * Update the user's password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'La contraseña actual es requerida',
            'new_password.required' => 'La nueva contraseña es requerida',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres',
            'new_password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta']);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->route('profile.show')->with('success', 'Contraseña actualizada correctamente');
    }
}
