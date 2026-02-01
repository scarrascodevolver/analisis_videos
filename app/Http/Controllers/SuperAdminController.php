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

        // Estadísticas por organización (sin Global Scope para contar videos de TODAS las orgs)
        $orgStats = Organization::withCount([
            'users',
            'videos' => function ($query) {
                $query->withoutGlobalScope('organization');
            },
        ])->get();

        return view('super-admin.dashboard', compact('stats', 'orgStats'));
    }

    /**
     * Listar todas las organizaciones
     */
    public function organizations()
    {
        $organizations = Organization::withCount([
            'users',
            'videos' => function ($query) {
                $query->withoutGlobalScope('organization');
            },
        ])
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
            $slug = $originalSlug.'-'.$counter++;
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
        if (! empty($validated['slug'])) {
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
        // Usuarios disponibles: analistas y entrenadores que no están en esta organización
        // (Pueden trabajar para múltiples clubes)
        $availableUsers = User::where('is_super_admin', false)
            ->whereIn('role', ['analista', 'entrenador'])
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

        // Determinar organización a filtrar
        // Si no hay parámetro en URL, usar la org actual del super admin (excepto si explícitamente pide "todas")
        $selectedOrganization = null;

        if ($request->has('organization')) {
            // Usuario seleccionó manualmente (puede ser vacío = "Todas")
            $selectedOrganization = $request->organization ?: null;
        } else {
            // Primera carga: usar org actual del super admin
            $currentOrg = auth()->user()->currentOrganization();
            $selectedOrganization = $currentOrg ? $currentOrg->id : null;
        }

        // Aplicar filtro por organización si hay una seleccionada
        if ($selectedOrganization) {
            $query->whereHas('organizations', function ($q) use ($selectedOrganization) {
                $q->where('organizations.id', $selectedOrganization);
            });
        }

        // Filtro por rol
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Búsqueda
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $users = $query->latest()->paginate(15);
        $organizations = Organization::orderBy('name')->get();

        return view('super-admin.users.index', compact('users', 'organizations', 'selectedOrganization'));
    }

    /**
     * Eliminar usuario del sistema
     */
    public function destroyUser(User $user)
    {
        // No permitir eliminar super admins
        if ($user->is_super_admin) {
            return back()->with('error', 'No se puede eliminar un Super Admin.');
        }

        // No permitir eliminar tu propia cuenta
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $name = $user->name;

        // Desasociar de todas las organizaciones primero
        $user->organizations()->detach();

        // Eliminar perfil si existe
        if ($user->profile) {
            $user->profile->delete();
        }

        // Eliminar usuario
        $user->delete();

        return redirect()->route('super-admin.users')
            ->with('success', "Usuario '{$name}' eliminado exitosamente.");
    }

    /**
     * Estadísticas de almacenamiento por organización
     */
    public function storageStats()
    {
        // Obtener uso de almacenamiento por organización
        $storageByOrg = Organization::select('organizations.id', 'organizations.name', 'organizations.slug')
            ->selectRaw('COALESCE(SUM(videos.file_size), 0) as total_size')
            ->selectRaw('COUNT(videos.id) as video_count')
            ->selectRaw('COALESCE(AVG(videos.file_size), 0) as avg_size')
            ->leftJoin('videos', 'organizations.id', '=', 'videos.organization_id')
            ->groupBy('organizations.id', 'organizations.name', 'organizations.slug')
            ->orderByDesc('total_size')
            ->get();

        // Total global
        $totalStorage = Video::withoutGlobalScope('organization')->sum('file_size');
        $totalVideos = Video::withoutGlobalScope('organization')->count();

        // Videos sin organización (si existen)
        $orphanVideos = Video::withoutGlobalScope('organization')
            ->whereNull('organization_id')
            ->count();
        $orphanSize = Video::withoutGlobalScope('organization')
            ->whereNull('organization_id')
            ->sum('file_size');

        return view('super-admin.storage', compact(
            'storageByOrg',
            'totalStorage',
            'totalVideos',
            'orphanVideos',
            'orphanSize'
        ));
    }

    /**
     * Mostrar formulario de configuraciones de la organización
     */
    public function settingsForm(Organization $organization)
    {
        // Obtener todas las zonas horarias agrupadas por región
        $timezones = $this->getGroupedTimezones();

        return view('super-admin.organizations.settings', compact('organization', 'timezones'));
    }

    /**
     * Actualizar configuraciones de la organización
     */
    public function updateSettings(Request $request, Organization $organization)
    {
        $validated = $request->validate(Organization::compressionSettingsValidationRules());

        // Set default values for immediate strategy
        if ($validated['compression_strategy'] === 'immediate') {
            $validated['compression_start_hour'] = $validated['compression_start_hour'] ?? 3;
            $validated['compression_end_hour'] = $validated['compression_end_hour'] ?? 7;
            $validated['compression_hybrid_threshold'] = $validated['compression_hybrid_threshold'] ?? 500;
        }

        // Set default threshold for nocturnal strategy
        if ($validated['compression_strategy'] === 'nocturnal') {
            $validated['compression_hybrid_threshold'] = $validated['compression_hybrid_threshold'] ?? 500;
        }

        $organization->update($validated);

        return redirect()->route('super-admin.organizations.settings', $organization)
            ->with('success', 'Organization settings updated successfully. Compression schedule will apply from next hour.');
    }

    /**
     * Obtener zonas horarias agrupadas por región
     */
    private function getGroupedTimezones(): array
    {
        $timezones = timezone_identifiers_list();
        $grouped = [];

        foreach ($timezones as $timezone) {
            $parts = explode('/', $timezone);
            $region = $parts[0];

            if (! isset($grouped[$region])) {
                $grouped[$region] = [];
            }

            $grouped[$region][$timezone] = $timezone;
        }

        // Ordenar regiones principales primero
        $regionOrder = ['America', 'Europe', 'Asia', 'Africa', 'Pacific', 'Atlantic', 'Indian', 'Antarctica', 'UTC'];
        $sortedGrouped = [];

        foreach ($regionOrder as $region) {
            if (isset($grouped[$region])) {
                $sortedGrouped[$region] = $grouped[$region];
                unset($grouped[$region]);
            }
        }

        // Agregar regiones restantes
        foreach ($grouped as $region => $zones) {
            $sortedGrouped[$region] = $zones;
        }

        return $sortedGrouped;
    }
}
