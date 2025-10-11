@extends('layouts.app')

@section('page_title', 'Editar Equipo')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.teams.index') }}">Equipos</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">Editar Equipo: {{ $team->name }}</h3>
            </div>
            <form action="{{ route('admin.teams.update', $team) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Este equipo aparece en <strong>{{ $analyzedVideosCount }}</strong> videos analizados
                        y <strong>{{ $rivalVideosCount }}</strong> videos como rival.
                    </div>

                    <div class="form-group">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $team->name) }}"
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
                               value="{{ old('abbreviation', $team->abbreviation) }}"
                               maxlength="10">
                        @error('abbreviation')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="is_own_team"
                                   name="is_own_team"
                                   value="1"
                                   {{ old('is_own_team', $team->is_own_team) ? 'checked' : '' }}>
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
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('admin.teams.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    @if($analyzedVideosCount == 0 && $rivalVideosCount == 0)
                        <button type="button"
                                class="btn btn-danger float-right"
                                onclick="document.getElementById('delete-form').submit();">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    @endif
                </div>
            </form>

            @if($analyzedVideosCount == 0 && $rivalVideosCount == 0)
                <form id="delete-form"
                      action="{{ route('admin.teams.destroy', $team) }}"
                      method="POST"
                      class="d-none"
                      onsubmit="return confirm('¿Estás seguro de eliminar este equipo?');">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
