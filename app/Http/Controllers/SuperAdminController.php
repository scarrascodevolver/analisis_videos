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
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:organizations,slug',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'is_active' => 'boolean',
            'create_admin' => 'boolean',
        ];

        // Si se quiere crear admin, validar campos adicionales
        if ($request->boolean('create_admin')) {
            $rules['admin_name'] = 'required|string|max:255';
            $rules['admin_email'] = 'required|email|unique:users,email';
            $rules['admin_password'] = 'nullable|string|min:8';
            $rules['admin_role'] = 'required|in:analista,entrenador,staff';
        }

        $validated = $request->validate($rules);

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

        $message = "Organización '{$organization->name}' creada exitosamente.";
        $generatedPassword = null;

        // Crear admin si se solicitó
        if ($request->boolean('create_admin')) {
            // Generar contraseña si no se proporcionó
            $password = $validated['admin_password'] ?? Str::random(12);
            $generatedPassword = empty($validated['admin_password']) ? $password : null;

            $admin = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => bcrypt($password),
                'role' => $validated['admin_role'],
            ]);

            // Asignar a la organización como admin
            $organization->users()->attach($admin->id, [
                'role' => $validated['admin_role'],
                'is_current' => true,
                'is_org_admin' => true,
            ]);

            $message .= " Admin '{$admin->name}' creado.";
            if ($generatedPassword) {
                $message .= " Contraseña generada: {$generatedPassword}";
            }
        }

        return redirect()->route('super-admin.organizations')
            ->with('success', $message);
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
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
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
        // Usuarios disponibles: no están en esta organización Y no son super_admin
        $availableUsers = User::where('is_super_admin', false)
            ->whereDoesntHave('organizations', function ($query) use ($organization) {
                $query->where('organizations.id', $organization->id);
            })
            ->orderBy('name')
            ->get();

        // Usuarios actuales: SOLO los que están en la tabla pivot para esta organización
        $currentUsers = $organization->users()
            ->orderBy('name')
            ->get();

        return view('super-admin.organizations.assign-admin', compact('organization', 'availableUsers', 'currentUsers'));
    }

    /**
     * Asignar usuario existente a una organización
     */
    public function assignAdmin(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:analista,entrenador,jugador,staff',
            'is_org_admin' => 'boolean',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $isOrgAdmin = $request->boolean('is_org_admin');

        // Verificar si el usuario ya está en la organización
        if ($organization->users()->where('users.id', $user->id)->exists()) {
            // Actualizar rol y is_org_admin
            $organization->users()->updateExistingPivot($user->id, [
                'role' => $validated['role'],
                'is_org_admin' => $isOrgAdmin,
            ]);
            $adminText = $isOrgAdmin ? ' (Admin de Org)' : '';
            $message = "Rol de '{$user->name}' actualizado a '{$validated['role']}'{$adminText} en '{$organization->name}'.";
        } else {
            // Agregar usuario a la organización
            $organization->users()->attach($user->id, [
                'role' => $validated['role'],
                'is_current' => $user->organizations()->count() === 0,
                'is_org_admin' => $isOrgAdmin,
            ]);
            $adminText = $isOrgAdmin ? ' como Admin de Org' : '';
            $message = "Usuario '{$user->name}' agregado a '{$organization->name}' como '{$validated['role']}'{$adminText}.";
        }

        return redirect()->route('super-admin.organizations.assign-admin', $organization)
            ->with('success', $message);
    }

    /**
     * Crear nuevo usuario y asignarlo a la organización
     */
    public function createUserForOrganization(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:analista,entrenador,jugador,staff',
            'is_org_admin' => 'boolean',
        ]);

        // Generar contraseña si no se proporcionó
        $password = $validated['password'] ?? Str::random(12);
        $generatedPassword = empty($validated['password']) ? $password : null;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($password),
            'role' => $validated['role'],
        ]);

        // Asignar a la organización
        $organization->users()->attach($user->id, [
            'role' => $validated['role'],
            'is_current' => true,
            'is_org_admin' => $request->boolean('is_org_admin'),
        ]);

        $message = "Usuario '{$user->name}' creado y asignado a '{$organization->name}'.";
        if ($generatedPassword) {
            $message .= " Contraseña generada: {$generatedPassword}";
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
