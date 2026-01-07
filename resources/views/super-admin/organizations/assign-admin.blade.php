@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.organizations') }}">Organizaciones</a></li>
                    <li class="breadcrumb-item active">Gestionar Usuarios: {{ $organization->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="fas fa-users-cog text-success mr-2"></i>
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
            <div class="card shadow">
                <div class="card-header py-3 bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-user-plus mr-2"></i>Agregar Usuario Existente
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('super-admin.organizations.assign-admin.store', $organization) }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="user_id">Seleccionar Usuario</label>
                            <select class="form-control @error('user_id') is-invalid @enderror"
                                    id="user_id"
                                    name="user_id"
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
                                <small class="text-muted">Todos los usuarios ya pertenecen a esta organización.</small>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="role">Rol Funcional</label>
                            <select class="form-control @error('role') is-invalid @enderror"
                                    id="role"
                                    name="role"
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
                                <label class="custom-control-label" for="is_org_admin">
                                    <strong>Es administrador de la organización</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Los administradores de organización pueden gestionar usuarios y configuración de su org.
                            </small>
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
            <div class="card shadow">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-user-edit mr-2"></i>Crear Nuevo Usuario
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('super-admin.organizations.create-user', $organization) }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="new_name">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="new_name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Nombre del usuario"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="new_email">Email <span class="text-danger">*</span></label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="new_email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="usuario@ejemplo.com"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="new_password">Contraseña</label>
                            <input type="text"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="new_password"
                                   name="password"
                                   placeholder="Dejar vacío para generar automáticamente">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Si se deja vacío, se generará una contraseña aleatoria y se mostrará una sola vez.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="new_role">Rol Funcional <span class="text-danger">*</span></label>
                            <select class="form-control @error('role') is-invalid @enderror"
                                    id="new_role"
                                    name="role"
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
                                <label class="custom-control-label" for="new_is_org_admin">
                                    <strong>Es administrador de la organización</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Los administradores de organización pueden gestionar usuarios y configuración de su org.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
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
            <div class="card shadow">
                <div class="card-header py-3 bg-secondary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-users mr-2"></i>Usuarios de la Organización ({{ $currentUsers->count() }})
                    </h6>
                </div>
                <div class="card-body">
                    @if($currentUsers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th class="text-center">Rol</th>
                                        <th class="text-center">Admin Org</th>
                                        <th class="text-center">Org Actual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($currentUsers as $user)
                                    <tr>
                                        <td>
                                            <strong>{{ $user->name }}</strong>
                                            @if($user->is_super_admin)
                                                <span class="badge badge-danger ml-1">Super Admin</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $user->email }}</small></td>
                                        <td class="text-center">
                                            @php
                                                $roleColors = [
                                                    'analista' => 'primary',
                                                    'entrenador' => 'success',
                                                    'jugador' => 'info',
                                                    'staff' => 'secondary',
                                                ];
                                                $role = $user->pivot->role;
                                            @endphp
                                            <span class="badge badge-{{ $roleColors[$role] ?? 'secondary' }}">
                                                {{ ucfirst($role) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($user->pivot->is_org_admin)
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-crown mr-1"></i>Admin
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($user->pivot->is_current)
                                                <i class="fas fa-check-circle text-success" title="Org. actual del usuario"></i>
                                            @else
                                                <i class="fas fa-circle text-muted" title="No es la org. actual"></i>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay usuarios en esta organización</p>
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
