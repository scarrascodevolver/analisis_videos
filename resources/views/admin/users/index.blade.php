@extends('layouts.app')

@section('page_title', 'Gestión de Usuarios')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item active">Usuarios</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card card-rugby">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Usuarios del Sistema</h3>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-rugby btn-sm">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Buscador y Filtros -->
                <form action="{{ route('admin.users.index') }}" method="GET" class="mb-3">
                    <div class="row">
                        <!-- Buscador -->
                        <div class="col-md-4 mb-2">
                            <div class="input-group">
                                <input type="text"
                                       name="search"
                                       class="form-control"
                                       placeholder="Buscar por nombre o email..."
                                       value="{{ request('search') }}">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-rugby">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Filtro por Rol -->
                        <div class="col-md-3 mb-2">
                            <select name="role" class="form-control" onchange="this.form.submit()">
                                <option value="">Todos los roles</option>
                                <option value="jugador" {{ request('role') == 'jugador' ? 'selected' : '' }}>Jugador</option>
                                <option value="entrenador" {{ request('role') == 'entrenador' ? 'selected' : '' }}>Entrenador</option>
                                <option value="analista" {{ request('role') == 'analista' ? 'selected' : '' }}>Analista</option>
                                <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="director_club" {{ request('role') == 'director_club' ? 'selected' : '' }}>Director Club</option>
                                <option value="director_tecnico" {{ request('role') == 'director_tecnico' ? 'selected' : '' }}>Director Técnico</option>
                            </select>
                        </div>

                        <!-- Filtro por Categoría -->
                        <div class="col-md-3 mb-2">
                            <select name="category_id" class="form-control" onchange="this.form.submit()">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Botón limpiar filtros -->
                        <div class="col-md-2 mb-2">
                            @if(request('search') || request('role') || request('category_id'))
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-block">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <!-- Contador de resultados -->
                <div class="mb-2">
                    <small class="text-muted">
                        Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de {{ $users->total() }} usuarios
                    </small>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="rugby-green">
                            <tr>
                                <th width="5%">ID</th>
                                <th width="20%">Nombre</th>
                                <th width="20%">Email</th>
                                <th width="15%">Rol</th>
                                <th width="15%">Categoría</th>
                                <th width="15%" class="text-center">Registro</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>
                                        @if($user->profile && $user->profile->avatar)
                                            <img src="{{ asset('storage/' . $user->profile->avatar) }}"
                                                 alt="Avatar"
                                                 class="img-circle mr-2"
                                                 style="width: 25px; height: 25px; object-fit: cover;">
                                        @endif
                                        <strong>{{ $user->name }}</strong>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @php
                                            $roleColors = [
                                                'jugador' => 'primary',
                                                'entrenador' => 'success',
                                                'analista' => 'warning',
                                                'staff' => 'info',
                                                'director_club' => 'danger',
                                                'director_tecnico' => 'secondary'
                                            ];
                                            $color = $roleColors[$user->role] ?? 'dark';
                                        @endphp
                                        <span class="badge badge-{{ $color }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->profile && $user->profile->category)
                                            <span class="badge badge-info">
                                                {{ $user->profile->category->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ $user->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.users.show', $user) }}"
                                               class="btn btn-info btn-sm"
                                               title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="btn btn-warning btn-sm"
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($user->id !== auth()->id())
                                                <form action="{{ route('admin.users.destroy', $user) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-danger btn-sm"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
