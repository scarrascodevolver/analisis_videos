@extends('layouts.app')

@section('page_title', 'Editar División')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.divisions.index') }}">Divisiones</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">Editar División: {{ $division->name }}</h3>
            </div>
            <form action="{{ route('admin.divisions.update', $division) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Esta división tiene <strong>{{ $videosCount }} videos</strong> asociados.
                    </div>

                    <div class="form-group">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $division->name) }}"
                               required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3">{{ old('description', $division->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Orden de Visualización</label>
                        <input type="number"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               id="sort_order"
                               name="sort_order"
                               value="{{ old('sort_order', $division->sort_order) }}"
                               min="0">
                        @error('sort_order')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="active"
                                   name="active"
                                   value="1"
                                   {{ old('active', $division->active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="active">
                                <strong>División activa</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Solo divisiones activas aparecerán en los formularios
                        </small>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('admin.divisions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    @if($videosCount == 0)
                        <button type="button"
                                class="btn btn-danger float-right"
                                onclick="document.getElementById('delete-form').submit();">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    @endif
                </div>
            </form>

            @if($videosCount == 0)
                <form id="delete-form"
                      action="{{ route('admin.divisions.destroy', $division) }}"
                      method="POST"
                      class="d-none"
                      onsubmit="return confirm('¿Estás seguro de eliminar esta división?');">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
