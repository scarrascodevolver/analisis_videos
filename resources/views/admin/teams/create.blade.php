@extends('layouts.app')

@section('page_title', 'Nuevo Equipo')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.teams.index') }}">Equipos</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">Crear Nuevo Equipo</h3>
            </div>
            <form action="{{ route('admin.teams.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Ej: Mi Club, Rival A, Club XYZ"
                               required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="abbreviation">Abreviación</label>
                        <input type="text"
                               class="form-control @error('abbreviation') is-invalid @enderror"
                               id="abbreviation"
                               name="abbreviation"
                               value="{{ old('abbreviation') }}"
                               placeholder="Ej: LT, RIV, CXY"
                               maxlength="10">
                        @error('abbreviation')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Máximo 10 caracteres para usar en reportes y vistas compactas
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="is_own_team"
                                   name="is_own_team"
                                   value="1"
                                   {{ old('is_own_team') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_own_team">
                                <strong>Es equipo propio</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Marca esta opción si este es uno de tus equipos (no un rival)
                        </small>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Crear Equipo
                    </button>
                    <a href="{{ route('admin.teams.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
