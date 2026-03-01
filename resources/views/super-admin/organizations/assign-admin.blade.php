@extends('layouts.app')

@section('main_content')
<style>
    /* --- Avatar con iniciales --- */
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
        letter-spacing: 0.5px;
    }

    /* --- Tabla oscura con hover --- */
    .dark-table tbody tr {
        transition: background 0.15s ease;
    }
    .dark-table tbody tr:hover {
        background: #111827 !important;
    }
    .dark-table th,
    .dark-table td {
        border-color: #2d2d4e !important;
        vertical-align: middle;
    }

    /* --- Badges de conteo --- */
    .role-badge-count {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        background: #0f0f1a;
        border: 1px solid #2d2d4e;
        color: #ccc;
    }
    .role-badge-count .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb" style="background:transparent;">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}" style="color:#FFC300;">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.organizations') }}" style="color:#FFC300;">Organizaciones</a></li>
                    <li class="breadcrumb-item active" style="color:#aaa;">Gestionar Usuarios: {{ $organization->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0" style="color:#fff;">
                <i class="fas fa-users-cog mr-2" style="color:#FFC300;"></i>
                Gestionar Usuarios de {{ $organization->name }}
            </h1>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="row">
        <!-- Agregar Usuario Existente -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow" style="background:#1a1a2e; border:1px solid #2d2d4e;">
                <div class="card-header py-3" style="background:#005461; border-bottom:1px solid #2d2d4e;">
                    <h6 class="m-0 font-weight-bold" style="color:#fff;">
                        <i class="fas fa-user-plus mr-2"></i>Agregar Usuario Existente
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('super-admin.organizations.assign-admin.store', $organization) }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label style="color:#ccc;">Seleccionar Usuario</label>
                            <select class="form-control @error('user_id') is-invalid @enderror"
                                    id="user_id"
                                    name="user_id"
                                    style="background:#0f0f1a; border-color:#2d2d4e; color:#fff;"
                                    required>
                                <option value="">-- Seleccionar usuario --</option>
                                @foreach($availableUsers as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($availableUsers->isEmpty())
                                <small style="color:#888;">Todos los usuarios ya pertenecen a esta organización.</small>
                            @endif
                        </div>

                        <div class="form-group">
                            <label style="color:#ccc;">Rol Funcional</label>
                            <select class="form-control @error('role') is-invalid @enderror"
                                    id="role"
                                    name="role"
                                    style="background:#0f0f1a; border-color:#2d2d4e; color:#fff;"
                                    required>
                                <option value="analista">Analista</option>
                                <option value="entrenador">Entrenador</option>
                                <option value="jugador">Jugador</option>
                                <option value="staff">Staff</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="is_org_admin"
                                       name="is_org_admin"
                                       value="1">
                                <label class="custom-control-label" for="is_org_admin" style="color:#ccc;">
                                    <strong style="color:#fff;">Es administrador de la organización</strong>
                                </label>
                            </div>
                            <small style="color:#888;">Los administradores de organización pueden gestionar usuarios y configuración de su org.</small>
                        </div>

                        <button type="submit" class="btn btn-success btn-block" {{ $availableUsers->isEmpty() ? 'disabled' : '' }}>
                            <i class="fas fa-plus mr-1"></i> Agregar Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Crear Nuevo Usuario -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow" style="background:#1a1a2e; border:1px solid #2d2d4e;">
                <div class="card-header py-3" style="background:#FFC300; border-bottom:1px solid #2d2d4e;">
                    <h6 class="m-0 font-weight-bold" style="color:#fff;">
                        <i class="fas fa-user-edit mr-2"></i>Crear Nuevo Usuario
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('super-admin.organizations.create-user', $organization) }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label style="color:#ccc;">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="new_name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Nombre del usuario"
                                   style="background:#0f0f1a; border-color:#2d2d4e; color:#fff;"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label style="color:#ccc;">Email <span class="text-danger">*</span></label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="new_email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="usuario@ejemplo.com"
                                   style="background:#0f0f1a; border-color:#2d2d4e; color:#fff;"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label style="color:#ccc;">Contraseña</label>
                            <input type="text"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="new_password"
                                   name="password"
                                   placeholder="Dejar vacío para generar automáticamente"
                                   style="background:#0f0f1a; border-color:#2d2d4e; color:#fff;">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small style="color:#888;">Si se deja vacío, se generará una contraseña aleatoria y se mostrará una sola vez.</small>
                        </div>

                        <div class="form-group">
                            <label style="color:#ccc;">Rol Funcional <span class="text-danger">*</span></label>
                            <select class="form-control @error('role') is-invalid @enderror"
                                    id="new_role"
                                    name="role"
                                    style="background:#0f0f1a; border-color:#2d2d4e; color:#fff;"
                                    required>
                                <option value="analista" {{ old('role') == 'analista' ? 'selected' : '' }}>Analista</option>
                                <option value="entrenador" {{ old('role') == 'entrenador' ? 'selected' : '' }}>Entrenador</option>
                                <option value="jugador" {{ old('role') == 'jugador' ? 'selected' : '' }}>Jugador</option>
                                <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="new_is_org_admin"
                                       name="is_org_admin"
                                       value="1">
                                <label class="custom-control-label" for="new_is_org_admin" style="color:#ccc;">
                                    <strong style="color:#fff;">Es administrador de la organización</strong>
                                </label>
                            </div>
                            <small style="color:#888;">Los administradores de organización pueden gestionar usuarios y configuración de su org.</small>
                        </div>

                        <button type="submit" class="btn btn-block" style="background:#FFC300; color:#fff;">
                            <i class="fas fa-user-plus mr-1"></i> Crear y Asignar Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Usuarios Actuales -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow" style="background:#1a1a2e; border:1px solid #2d2d4e;">
                <div class="card-header py-3" style="background:#003d4a; border-bottom:1px solid #2d2d4e;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:8px;">
                        <h6 class="m-0 font-weight-bold" style="color:#fff;">
                            <i class="fas fa-users mr-2" style="color:#FFC300;"></i>
                            Usuarios de la Organización ({{ $currentUsers->count() }})
                        </h6>

                        {{-- Badges con conteo de roles --}}
                        @if($currentUsers->count() > 0)
                        @php
                            $roleCounts  = $currentUsers->groupBy(fn($u) => $u->pivot->role);
                            $roleDisplay = [
                                'analista'  => ['label' => 'Analistas',   'color' => '#4e73df'],
                                'entrenador'=> ['label' => 'Entrenadores','color' => '#1cc88a'],
                                'jugador'   => ['label' => 'Jugadores',   'color' => '#36b9cc'],
                                'staff'     => ['label' => 'Staff',       'color' => '#858796'],
                            ];
                        @endphp
                        <div class="d-flex flex-wrap" style="gap:6px;">
                            @foreach($roleDisplay as $roleKey => $roleInfo)
                                @if(isset($roleCounts[$roleKey]))
                                <span class="role-badge-count">
                                    <span class="dot" style="background:{{ $roleInfo['color'] }};"></span>
                                    {{ $roleCounts[$roleKey]->count() }} {{ $roleInfo['label'] }}
                                </span>
                                @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($currentUsers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 dark-table" style="color:#ccc;">
                                <thead>
                                    <tr style="background:#0f0f1a; color:#888;">
                                        <th style="border-color:#2d2d4e;">Usuario</th>
                                        <th style="border-color:#2d2d4e;">Email</th>
                                        <th class="text-center" style="border-color:#2d2d4e;">Rol</th>
                                        <th class="text-center" style="border-color:#2d2d4e;">Admin Org</th>
                                        <th class="text-center" style="border-color:#2d2d4e;">Org Actual</th>
                                        <th class="text-center" style="border-color:#2d2d4e;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($currentUsers as $user)
                                    @php
                                        $roleColors = [
                                            'analista'   => 'primary',
                                            'entrenador' => 'success',
                                            'jugador'    => 'info',
                                            'staff'      => 'secondary',
                                        ];
                                        $role = $user->pivot->role;

                                        // Color del avatar basado en el nombre
                                        $avatarPalette = [
                                            '#4e73df','#1cc88a','#36b9cc','#f6c23e',
                                            '#e74a3b','#6f42c1','#20c9a6','#fd7e14',
                                        ];
                                        $charSum = 0;
                                        foreach (mb_str_split($user->name) as $ch) {
                                            $charSum += mb_ord($ch);
                                        }
                                        $avatarColor   = $avatarPalette[$charSum % count($avatarPalette)];
                                        $initials      = strtoupper(
                                            mb_substr(explode(' ', trim($user->name))[0], 0, 1) .
                                            (isset(explode(' ', trim($user->name))[1])
                                                ? mb_substr(explode(' ', trim($user->name))[1], 0, 1)
                                                : '')
                                        );
                                    @endphp
                                    <tr style="border-color:#2d2d4e; background:#1a1a2e;">
                                        <td style="border-color:#2d2d4e;">
                                            <div class="d-flex align-items-center" style="gap:10px;">
                                                <span class="user-avatar" style="background:{{ $avatarColor }};">
                                                    {{ $initials }}
                                                </span>
                                                <div>
                                                    <strong style="color:#fff;">{{ $user->name }}</strong>
                                                    @if($user->is_super_admin)
                                                        <span class="badge badge-danger ml-1">Super Admin</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td style="border-color:#2d2d4e;"><small>{{ $user->email }}</small></td>
                                        <td class="text-center" style="border-color:#2d2d4e;">
                                            <span class="badge badge-{{ $roleColors[$role] ?? 'secondary' }}">
                                                {{ ucfirst($role) }}
                                            </span>
                                        </td>
                                        <td class="text-center" style="border-color:#2d2d4e;">
                                            @if($user->pivot->is_org_admin)
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-crown mr-1"></i>Admin
                                                </span>
                                            @else
                                                <span style="color:#555;">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center" style="border-color:#2d2d4e;">
                                            @if($user->pivot->is_current)
                                                <i class="fas fa-check-circle text-success" title="Org. actual del usuario"></i>
                                            @else
                                                <i class="fas fa-circle" style="color:#333;" title="No es la org. actual"></i>
                                            @endif
                                        </td>
                                        <td class="text-center" style="border-color:#2d2d4e;">
                                            @if($user->id !== auth()->id())
                                                <form action="{{ route('super-admin.organizations.remove-user', [$organization, $user]) }}"
                                                      method="POST"
                                                      style="display:inline;"
                                                      onsubmit="return confirm('¿Quitar a {{ addslashes($user->name) }} de {{ addslashes($organization->name) }}? El usuario no será eliminado del sistema.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm"
                                                            style="background:#2d2d4e; border:1px solid #3d3d5e; color:#e74a3b;"
                                                            title="Quitar de la organización">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span title="No puedes quitarte a ti mismo" style="color:#555; cursor:default;">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x mb-3" style="color:#2d2d4e;"></i>
                            <p style="color:#888;">No hay usuarios en esta organización</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <a href="{{ route('super-admin.organizations') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver a Organizaciones
            </a>
        </div>
    </div>
</div>
@endsection
