@extends('layouts.app')

@section('page_title', 'Editar Situación de Rugby')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.situations.index') }}">Situaciones</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">Editar Situación: {{ $situation->name }}</h3>
            </div>
            <form action="{{ route('admin.situations.update', $situation) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Esta situación aparece en <strong>{{ $videosCount }} videos</strong>.
                    </div>

                    <div class="form-group">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $situation->name) }}"
                               required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="category">Categoría <span class="text-danger">*</span></label>
                        <select class="form-control @error('category') is-invalid @enderror"
                                id="category"
                                name="category"
                                required>
                            <option value="">Seleccione una categoría...</option>
                            <option value="Fase Fija" {{ old('category', $situation->category) == 'Fase Fija' ? 'selected' : '' }}>Fase Fija</option>
                            <option value="Fase Dinámica" {{ old('category', $situation->category) == 'Fase Dinámica' ? 'selected' : '' }}>Fase Dinámica</option>
                            <option value="Ataque" {{ old('category', $situation->category) == 'Ataque' ? 'selected' : '' }}>Ataque</option>
                            <option value="Defensa" {{ old('category', $situation->category) == 'Defensa' ? 'selected' : '' }}>Defensa</option>
                            <option value="Transición" {{ old('category', $situation->category) == 'Transición' ? 'selected' : '' }}>Transición</option>
                            <option value="Penal/Indisciplina" {{ old('category', $situation->category) == 'Penal/Indisciplina' ? 'selected' : '' }}>Penal/Indisciplina</option>
                            <option value="Otros" {{ old('category', $situation->category) == 'Otros' ? 'selected' : '' }}>Otros</option>
                        </select>
                        @error('category')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3">{{ old('description', $situation->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="color">Color <span class="text-danger">*</span></label>
                        <input type="color"
                               class="form-control @error('color') is-invalid @enderror"
                               id="color"
                               name="color"
                               value="{{ old('color', $situation->color) }}"
                               style="height: 50px;"
                               required>
                        @error('color')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Orden de Visualización</label>
                        <input type="number"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               id="sort_order"
                               name="sort_order"
                               value="{{ old('sort_order', $situation->sort_order) }}"
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
                                   {{ old('active', $situation->active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="active">
                                <strong>Situación activa</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Solo situaciones activas aparecerán en los formularios
                        </small>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('admin.situations.index') }}" class="btn btn-secondary">
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
                      action="{{ route('admin.situations.destroy', $situation) }}"
                      method="POST"
                      class="d-none"
                      onsubmit="return confirm('¿Estás seguro de eliminar esta situación?');">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
