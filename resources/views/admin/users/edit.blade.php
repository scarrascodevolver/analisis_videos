@extends('layouts.app')

@section('page_title', 'Editar Usuario')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">Editar Usuario: {{ $user->name }}</h3>
            </div>
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $user->name) }}"
                               required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email', $user->email) }}"
                               required>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Nueva Contraseña</label>
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               id="password"
                               name="password">
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Déjalo en blanco si no deseas cambiar la contraseña
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirmar Contraseña</label>
                        <input type="password"
                               class="form-control"
                               id="password_confirmation"
                               name="password_confirmation">
                        <small class="form-text text-muted">
                            Requerido solo si cambias la contraseña
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="role">Rol <span class="text-danger">*</span></label>
                        <select class="form-control @error('role') is-invalid @enderror"
                                id="role"
                                name="role"
                                required>
                            <option value="jugador" {{ old('role', $user->role) == 'jugador' ? 'selected' : '' }}>Jugador</option>
                            <option value="entrenador" {{ old('role', $user->role) == 'entrenador' ? 'selected' : '' }}>Entrenador</option>
                            <option value="analista" {{ old('role', $user->role) == 'analista' ? 'selected' : '' }}>Analista</option>
                            <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="director_club" {{ old('role', $user->role) == 'director_club' ? 'selected' : '' }}>Director de Club</option>
                            <option value="director_tecnico" {{ old('role', $user->role) == 'director_tecnico' ? 'selected' : '' }}>Director Técnico</option>
                        </select>
                        @error('role')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="user_category_id">Categoría de Usuario</label>
                        <select class="form-control @error('user_category_id') is-invalid @enderror"
                                id="user_category_id"
                                name="user_category_id">
                            <option value="">Sin categoría asignada</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('user_category_id', $user->profile->user_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_category_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    @if($user->id !== auth()->id())
                        <button type="button"
                                class="btn btn-danger float-right"
                                onclick="document.getElementById('delete-form').submit();">
                            <i class="fas fa-trash"></i> Eliminar Usuario
                        </button>
                    @endif
                </div>
            </form>

            @if($user->id !== auth()->id())
                <form id="delete-form"
                      action="{{ route('admin.users.destroy', $user) }}"
                      method="POST"
                      class="d-none"
                      onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
