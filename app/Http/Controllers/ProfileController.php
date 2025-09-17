<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;

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
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string',
            'secondary_position' => 'nullable|string',
            'player_number' => 'nullable|integer|min:1|max:99',
            'weight' => 'nullable|integer|min:40|max:200',
            'height' => 'nullable|integer|min:150|max:220',
            'date_of_birth' => 'nullable|date',
            'user_category_id' => 'required|exists:categories,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
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
}