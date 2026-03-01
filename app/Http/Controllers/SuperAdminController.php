<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    /**
     * Base query de organizaciones según el rol del usuario actual.
     * Super admin: todas. Org manager: solo las que creó.
     */
    private function orgQuery()
    {
        $query = Organization::query();
        if (! auth()->user()->isSuperAdmin()) {
            $query->where('created_by', auth()->id());
        }

        return $query;
    }

    /**
     * Verifica que el org_manager tenga acceso a una organización específica.
     */
    private function authorizeOrgAccess(Organization $organization): void
    {
        if (! auth()->user()->isSuperAdmin() && $organization->created_by !== auth()->id()) {
            abort(403, 'No tenés acceso a esta organización.');
        }
    }

    /**
     * Dashboard con estadísticas (filtrado por org_manager si aplica)
     */
    public function dashboard()
    {
        $baseQuery = $this->orgQuery();

        $totalClubs = (clone $baseQuery)->where('type', 'club')->count();
        $totalAsociaciones = (clone $baseQuery)->where('type', 'asociacion')->count();

        $orgIds = (clone $baseQuery)->pluck('id');

        $totalUsers = User::whereHas('organizations', fn ($q) => $q->whereIn('organizations.id', $orgIds))->count();
        $totalVideos = Video::withoutGlobalScope('organization')->whereIn('organization_id', $orgIds)->count();
        $totalStorageBytes = Video::withoutGlobalScope('organization')->whereIn('organization_id', $orgIds)->sum('file_size');

        // Desglose de usuarios por rol (solo super admin ve global)
        $usersByRole = auth()->user()->isSuperAdmin()
            ? User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role')
            : collect();

        // Orgs con estadísticas
        $orgStats = (clone $baseQuery)->withCount([
            'users',
            'videos' => fn ($q) => $q->withoutGlobalScope('organization'),
        ])
            ->orderByDesc('created_at')
            ->get();

        $recentOrgs = (clone $baseQuery)->latest()->take(5)->get();

        $orgsWithoutBunny = auth()->user()->isSuperAdmin()
            ? Organization::whereNull('bunny_library_id')->count()
            : 0;

        return view('super-admin.dashboard', compact(
            'totalClubs', 'totalAsociaciones', 'totalUsers', 'totalVideos',
            'totalStorageBytes', 'usersByRole', 'orgStats', 'recentOrgs', 'orgsWithoutBunny'
        ));
    }

    /**
     * Listar organizaciones
     */
    public function organizations()
    {
        $organizations = $this->orgQuery()->withCount([
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
            'type' => 'required|in:club,asociacion',
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
            'type' => $validated['type'],
            'slug' => $slug,
            'logo_path' => $logoPath,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        // Crear library en Bunny Stream automáticamente
        $bunnyWarning = null;
        try {
            $libraryName = ucfirst($organization->type).' - '.$organization->name;
            $bunnyData = BunnyStreamService::createLibrary($libraryName);

            $organization->update([
                'bunny_library_id' => $bunnyData['library_id'],
                'bunny_api_key' => $bunnyData['api_key'],
                'bunny_cdn_hostname' => $bunnyData['cdn_hostname'],
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo crear la library en Bunny para org '.$organization->id, [
                'error' => $e->getMessage(),
            ]);
            $bunnyWarning = 'La organización fue creada pero no se pudo crear la library en Bunny Stream. Configurá las credenciales manualmente.';
        }

        // Crear categorías por defecto según tipo de organización
        // Usamos la relación para que Eloquent asigne organization_id directamente
        // (evita el problema de mass assignment: organization_id no está en $fillable de Category)
        if ($organization->type === Organization::TYPE_CLUB) {
            foreach (['Masculino', 'Juveniles', 'Femenino'] as $catName) {
                $organization->categories()->create(['name' => $catName]);
            }
        }

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

        $redirect = redirect()->route('super-admin.organizations')->with('success', $message);

        if ($bunnyWarning) {
            $redirect = $redirect->with('warning', $bunnyWarning);
        }

        return $redirect;
    }

    /**
     * Formulario para editar organización
     */
    public function editOrganization(Organization $organization)
    {
        $this->authorizeOrgAccess($organization);
        $organization->loadCount(['users', 'videos']);
        $admins = $organization->users()->wherePivot('role', 'admin')->get();

        return view('super-admin.organizations.edit', compact('organization', 'admins'));
    }

    /**
     * Actualizar organización
     */
    public function updateOrganization(Request $request, Organization $organization)
    {
        $this->authorizeOrgAccess($organization);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:club,asociacion',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('organizations')->ignore($organization->id)],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                \Storage::disk('public')->delete($organization->logo_path);
            }
            $organization->logo_path = $request->file('logo')->store('organizations/logos', 'public');
        }

        $organization->name = $validated['name'];
        $organization->type = $validated['type'];
        if (! empty($validated['slug'])) {
            $organization->slug = $validated['slug'];
        }
        $organization->is_active = $request->boolean('is_active', true);
        $organization->save();

        return redirect()->route('super-admin.organizations')
            ->with('success', "Organización '{$organization->name}' actualizada exitosamente.");
    }

    /**
     * Eliminar organización con borrado completo en cascada.
     * Requiere confirmación enviando el nombre exacto de la org.
     */
    public function destroyOrganization(Request $request, Organization $organization)
    {
        $this->authorizeOrgAccess($organization);
        // Confirmación de seguridad: el nombre debe coincidir exactamente
        if ($request->input('confirm_name') !== $organization->name) {
            return back()->with('error', 'El nombre ingresado no coincide. La organización NO fue eliminada.');
        }

        $name = $organization->name;
        $videosCount = $organization->videos()->withoutGlobalScope('organization')->count();
        $usersCount = $organization->users()->count();

        // 1. Eliminar cada video de Bunny Stream
        $bunnyService = BunnyStreamService::forOrganization($organization);
        $organization->videos()->withoutGlobalScope('organization')
            ->whereNotNull('bunny_video_id')
            ->each(function ($video) use ($bunnyService) {
                try {
                    $bunnyService->deleteVideo($video->bunny_video_id);
                } catch (\Throwable $e) {
                    Log::warning('No se pudo eliminar video de Bunny', [
                        'bunny_video_id' => $video->bunny_video_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        // 2. Eliminar todos los videos de la DB
        $organization->videos()->withoutGlobalScope('organization')->delete();

        // 3. Desasociar usuarios de la org (no se eliminan, pueden pertenecer a otras orgs)
        $organization->users()->detach();

        // 4. Eliminar logo si existe
        if ($organization->logo_path) {
            \Storage::disk('public')->delete($organization->logo_path);
        }

        // 5. Eliminar library completa en Bunny Stream
        if ($organization->bunny_library_id) {
            try {
                BunnyStreamService::deleteLibrary($organization->bunny_library_id);
            } catch (\Throwable $e) {
                Log::error('No se pudo eliminar la library en Bunny para org '.$organization->id, [
                    'library_id' => $organization->bunny_library_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 6. Eliminar la organización
        $organization->delete();

        Log::info('Organización eliminada por super admin', [
            'name' => $name,
            'users_count' => $usersCount,
            'videos_count' => $videosCount,
            'deleted_by' => auth()->id(),
        ]);

        return redirect()->route('super-admin.organizations')
            ->with('success', "Organización '{$name}' eliminada. Se eliminaron {$videosCount} videos y se desasociaron {$usersCount} usuarios.");
    }

    /**
     * Formulario para asignar administrador a una organización
     */
    public function assignAdminForm(Organization $organization)
    {
        $this->authorizeOrgAccess($organization);
        // Usuarios disponibles: analistas, entrenadores y super_admins que no están en esta organización
        // Los super_admins pueden unirse con un rol funcional (analista/entrenador/staff)
        $availableUsers = User::whereDoesntHave('organizations', function ($query) use ($organization) {
                $query->where('organizations.id', $organization->id);
            })
            ->where(function ($q) {
                $q->whereIn('role', ['analista', 'entrenador', 'staff', 'jugador'])
                  ->orWhere('is_super_admin', true);
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
        $this->authorizeOrgAccess($organization);
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
        $this->authorizeOrgAccess($organization);
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
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Solo el Super Admin puede ver todos los usuarios del sistema.');
        }

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
     * Activar o desactivar el rol Org Manager de un usuario
     */
    public function toggleOrgManager(User $user)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Solo el Super Admin puede cambiar este rol.');
        }

        if ($user->is_super_admin) {
            return back()->with('error', 'Un Super Admin no necesita el rol de Org Manager.');
        }

        $user->update(['is_org_manager' => ! $user->is_org_manager]);

        $estado = $user->is_org_manager ? 'activado' : 'desactivado';

        return back()->with('success', "Rol Org Manager {$estado} para {$user->name}.");
    }

    /**
     * Estadísticas de almacenamiento por organización
     */
    public function storageStats()
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Solo el Super Admin puede ver las estadísticas de almacenamiento.');
        }

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
        $this->authorizeOrgAccess($organization);
        $timezones = $this->getGroupedTimezones();

        return view('super-admin.organizations.settings', compact('organization', 'timezones'));
    }

    /**
     * Actualizar configuraciones de la organización
     */
    public function updateSettings(Request $request, Organization $organization)
    {
        $this->authorizeOrgAccess($organization);
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
