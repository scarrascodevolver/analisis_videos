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
            </div>
        </div>
    </div>
</div>
@endsection
