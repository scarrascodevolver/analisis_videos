@extends('layouts.app')

@section('page_title', 'Nueva División')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.divisions.index') }}">Divisiones</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">Crear Nueva División</h3>
            </div>
            <form action="{{ route('admin.divisions.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Ej: Primera, Segunda, Intermedia, Desarrollo"
                               required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Nombre de la división de competencia
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  placeholder="Descripción opcional de la división">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Descripción opcional para aclarar características de esta división
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Orden de Visualización</label>
                        <input type="number"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               id="sort_order"
                               name="sort_order"
                               value="{{ old('sort_order', 0) }}"
                               min="0">
                        @error('sort_order')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Número para ordenar las divisiones (menor número aparece primero)
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="active"
                                   name="active"
                                   value="1"
                                   {{ old('active', true) ? 'checked' : '' }}>
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
                        <i class="fas fa-save"></i> Crear División
                    </button>
                    <a href="{{ route('admin.divisions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
