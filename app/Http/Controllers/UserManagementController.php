<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Category;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Yajra\DataTables\Facades\DataTables;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        // Categorías para filtros
        $categories = Category::orderBy('name', 'asc')->get();

        // Si es petición de DataTables, devolver JSON
        if ($request->ajax()) {
            return $this->getUsersDataTable($request);
        }

        return view('admin.users.index', compact('categories'));
    }

    /**
     * Get users data for DataTables
     */
    private function getUsersDataTable(Request $request)
    {
        $query = User::with(['profile.category']);

        // Filtro por rol
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filtro por categoría
        if ($request->filled('category_id')) {
            $query->whereHas('profile', function($q) use ($request) {
                $q->where('user_category_id', $request->category_id);
            });
        }

        return DataTables::of($query)
            ->addColumn('avatar', function($user) {
                if ($user->profile && $user->profile->avatar) {
                    return '<img src="' . asset('storage/' . $user->profile->avatar) . '"
                            class="img-circle"
                            style="width: 25px; height: 25px; object-fit: cover;"
                            alt="Avatar">';
                }
                return '';
            })
            ->editColumn('name', function($user) {
                return '<strong>' . e($user->name) . '</strong>';
            })
            ->addColumn('role_badge', function($user) {
                $roleColors = [
                    'jugador' => 'primary',
                    'entrenador' => 'success',
                    'analista' => 'warning',
                    'staff' => 'info',
                    'director_club' => 'danger',
                    'director_tecnico' => 'secondary'
                ];
                $color = $roleColors[$user->role] ?? 'dark';
                return '<span class="badge badge-' . $color . '">' . ucfirst($user->role) . '</span>';
            })
            ->addColumn('category_badge', function($user) {
                if ($user->profile && $user->profile->category) {
                    return '<span class="badge badge-info">' . e($user->profile->category->name) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->editColumn('created_at', function($user) {
                return '<small class="text-muted">' . $user->created_at->format('d/m/Y') . '</small>';
            })
            ->addColumn('actions', function($user) {
                $canDelete = $user->id !== auth()->id();
                return view('admin.users._actions', compact('user', 'canDelete'))->render();
            })
            ->filter(function ($query) use ($request) {
                // Búsqueda global en nombre y email
                if ($request->has('search') && $request->search['value'] != null) {
                    $search = $request->search['value'];
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->rawColumns(['avatar', 'name', 'role_badge', 'category_badge', 'created_at', 'actions'])
            ->make(true);
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
        // Limpiar campos de contraseña vacíos antes de validar
        if (empty($request->password)) {
            $request->merge(['password' => null, 'password_confirmation' => null]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:jugador,entrenador,analista,staff,director_club,director_tecnico'],
            'user_category_id' => ['nullable', 'exists:categories,id'],
            'password' => ['nullable', 'confirmed', 'min:8'],
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
