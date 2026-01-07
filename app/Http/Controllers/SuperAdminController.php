<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    /**
     * Dashboard con estadísticas globales
     */
    public function dashboard()
    {
        $stats = [
            'total_organizations' => Organization::count(),
            'active_organizations' => Organization::where('is_active', true)->count(),
            'total_users' => User::count(),
            'total_videos' => Video::withoutGlobalScope('organization')->count(),
            'recent_organizations' => Organization::latest()->take(5)->get(),
            'recent_users' => User::latest()->take(5)->get(),
        ];

        // Estadísticas por organización
        $orgStats = Organization::withCount(['users', 'videos'])->get();

        return view('super-admin.dashboard', compact('stats', 'orgStats'));
    }

    /**
     * Listar todas las organizaciones
     */
    public function organizations()
    {
        $organizations = Organization::withCount(['users', 'videos'])
            ->latest()
            ->paginate(10);

        return view('super-admin.organizations.index', compact('organizations'));
    }

    /**
     * Formulario para crear organización
     */
    public function createOrganization()
    {
        return view('super-admin.organizations.create');
    }

    /**
     * Guardar nueva organización
     */
    public function storeOrganization(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:organizations,slug',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        // Generar slug si no se proporciona
        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        // Asegurar que el slug sea único
        $originalSlug = $slug;
        $counter = 1;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('organizations/logos', 'public');
        }

        $organization = Organization::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'logo_path' => $logoPath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('super-admin.organizations')
            ->with('success', "Organización '{$organization->name}' creada exitosamente.");
    }

    /**
     * Formulario para editar organización
     */
    public function editOrganization(Organization $organization)
    {
        $organization->loadCount(['users', 'videos']);
        $admins = $organization->users()->wherePivot('role', 'admin')->get();

        return view('super-admin.organizations.edit', compact('organization', 'admins'));
    }

    /**
     * Actualizar organización
     */
    public function updateOrganization(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('organizations')->ignore($organization->id)],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($organization->logo_path) {
                \Storage::disk('public')->delete($organization->logo_path);
            }
            $organization->logo_path = $request->file('logo')->store('organizations/logos', 'public');
        }

        $organization->name = $validated['name'];
        if (!empty($validated['slug'])) {
            $organization->slug = $validated['slug'];
        }
        $organization->is_active = $request->boolean('is_active', true);
        $organization->save();

        return redirect()->route('super-admin.organizations')
            ->with('success', "Organización '{$organization->name}' actualizada exitosamente.");
    }

    /**
     * Eliminar organización
     */
    public function destroyOrganization(Organization $organization)
    {
        $name = $organization->name;

        // Verificar que no tenga usuarios o videos asociados
        if ($organization->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una organización con usuarios asociados.');
        }

        if ($organization->videos()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una organización con videos asociados.');
        }

        // Eliminar logo si existe
        if ($organization->logo_path) {
            \Storage::disk('public')->delete($organization->logo_path);
        }

        $organization->delete();

        return redirect()->route('super-admin.organizations')
            ->with('success', "Organización '{$name}' eliminada exitosamente.");
    }

    /**
     * Formulario para asignar administrador a una organización
     */
    public function assignAdminForm(Organization $organization)
    {
        // Usuarios que no están en esta organización
        $availableUsers = User::whereDoesntHave('organizations', function ($query) use ($organization) {
            $query->where('organizations.id', $organization->id);
        })->orderBy('name')->get();

        // Usuarios actuales de la organización
        $currentUsers = $organization->users()->orderBy('name')->get();

        return view('super-admin.organizations.assign-admin', compact('organization', 'availableUsers', 'currentUsers'));
    }

    /**
     * Asignar administrador a una organización
     */
    public function assignAdmin(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,analista,entrenador,jugador,staff',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Verificar si el usuario ya está en la organización
        if ($organization->users()->where('users.id', $user->id)->exists()) {
            // Actualizar rol
            $organization->users()->updateExistingPivot($user->id, [
                'role' => $validated['role'],
            ]);
            $message = "Rol de '{$user->name}' actualizado a '{$validated['role']}' en '{$organization->name}'.";
        } else {
            // Agregar usuario a la organización
            $organization->users()->attach($user->id, [
                'role' => $validated['role'],
                'is_current' => $user->organizations()->count() === 0, // Si es su primera org, hacerla current
            ]);
            $message = "Usuario '{$user->name}' agregado a '{$organization->name}' como '{$validated['role']}'.";
        }

        return redirect()->route('super-admin.organizations.assign-admin', $organization)
            ->with('success', $message);
    }

    /**
     * Listar todos los usuarios del sistema
     */
    public function users(Request $request)
    {
        $query = User::with('organizations');

        // Filtro por organización
        if ($request->filled('organization')) {
            $query->whereHas('organizations', function ($q) use ($request) {
                $q->where('organizations.id', $request->organization);
            });
        }

        // Filtro por rol
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Búsqueda
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->latest()->paginate(15);
        $organizations = Organization::orderBy('name')->get();

        return view('super-admin.users.index', compact('users', 'organizations'));
    }
}
