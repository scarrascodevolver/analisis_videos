@extends('layouts.app')

@section('page_title', 'Nuevo Usuario')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">Crear Nuevo Usuario</h3>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
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
                               value="{{ old('email') }}"
                               required>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña <span class="text-danger">*</span></label>
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               id="password"
                               name="password"
                               required>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Mínimo 8 caracteres
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="role">Rol <span class="text-danger">*</span></label>
                        <select class="form-control @error('role') is-invalid @enderror"
                                id="role"
                                name="role"
                                required>
                            <option value="">Seleccione un rol...</option>
                            <option value="jugador" {{ old('role') == 'jugador' ? 'selected' : '' }}>Jugador</option>
                            <option value="entrenador" {{ old('role') == 'entrenador' ? 'selected' : '' }}>Entrenador</option>
                            <option value="analista" {{ old('role') == 'analista' ? 'selected' : '' }}>Analista</option>
                            <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="director_club" {{ old('role') == 'director_club' ? 'selected' : '' }}>Director de Club</option>
                            <option value="director_tecnico" {{ old('role') == 'director_tecnico' ? 'selected' : '' }}>Director Técnico</option>
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
                                <option value="{{ $category->id }}" {{ old('user_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_category_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Requerido para jugadores y entrenadores. Determina qué videos pueden ver.
                        </small>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Crear Usuario
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
