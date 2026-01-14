@extends('layouts.app')

@section('page_title', 'Editar Categoría de Clips')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.clip-categories.index') }}">Categorías de Clips</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-edit mr-2"></i>Editar: {{ $category->name }}
                </h3>
            </div>
            <form action="{{ route('admin.clip-categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $category->name) }}"
                               required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="color">Color <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="color"
                                           class="form-control @error('color') is-invalid @enderror"
                                           id="color"
                                           name="color"
                                           value="{{ old('color', $category->color) }}"
                                           style="height: 38px; padding: 2px;">
                                    <input type="text"
                                           class="form-control"
                                           id="colorText"
                                           value="{{ old('color', $category->color) }}"
                                           readonly
                                           style="max-width: 100px;">
                                </div>
                                @error('color')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="hotkey">Tecla Rápida</label>
                                <input type="text"
                                       class="form-control @error('hotkey') is-invalid @enderror"
                                       id="hotkey"
                                       name="hotkey"
                                       value="{{ old('hotkey', $category->hotkey) }}"
                                       maxlength="1"
                                       style="text-transform: lowercase;">
                                @error('hotkey')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lead_seconds">Lead (segundos antes) <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control @error('lead_seconds') is-invalid @enderror"
                                       id="lead_seconds"
                                       name="lead_seconds"
                                       value="{{ old('lead_seconds', $category->lead_seconds) }}"
                                       min="0"
                                       max="30"
                                       required>
                                @error('lead_seconds')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lag_seconds">Lag (segundos después) <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control @error('lag_seconds') is-invalid @enderror"
                                       id="lag_seconds"
                                       name="lag_seconds"
                                       value="{{ old('lag_seconds', $category->lag_seconds) }}"
                                       min="0"
                                       max="30"
                                       required>
                                @error('lag_seconds')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">
                                Categoría activa (visible en botonera)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('admin.clip-categories.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información</h5>
            </div>
            <div class="card-body">
                <p><strong>Clips con esta categoría:</strong></p>
                <h3 class="text-center">{{ $category->clips()->count() }}</h3>
                <hr>
                <p><strong>Creada por:</strong><br>{{ $category->creator->name ?? 'Sistema' }}</p>
                <p><strong>Creada:</strong><br>{{ $category->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('colorText').value = this.value;
});
</script>
@endpush
@endsection
