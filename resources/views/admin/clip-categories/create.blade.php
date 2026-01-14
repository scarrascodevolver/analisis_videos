@extends('layouts.app')

@section('page_title', 'Nueva Categoría de Clips')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.clip-categories.index') }}">Categorías de Clips</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-plus mr-2"></i>Nueva Categoría de Clips
                </h3>
            </div>
            <form action="{{ route('admin.clip-categories.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Ej: Try, Scrum, Lineout..."
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
                                           value="{{ old('color', '#007bff') }}"
                                           style="height: 38px; padding: 2px;">
                                    <input type="text"
                                           class="form-control"
                                           id="colorText"
                                           value="{{ old('color', '#007bff') }}"
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
                                       value="{{ old('hotkey') }}"
                                       maxlength="1"
                                       placeholder="Ej: t, s, l..."
                                       style="text-transform: lowercase;">
                                <small class="form-text text-muted">
                                    Presiona esta tecla en el reproductor para marcar rápidamente.
                                </small>
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
                                       value="{{ old('lead_seconds', 5) }}"
                                       min="0"
                                       max="30"
                                       required>
                                <small class="form-text text-muted">
                                    Segundos que incluir ANTES del momento marcado.
                                </small>
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
                                       value="{{ old('lag_seconds', 3) }}"
                                       min="0"
                                       max="30"
                                       required>
                                <small class="form-text text-muted">
                                    Segundos que incluir DESPUÉS del momento marcado.
                                </small>
                                @error('lag_seconds')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-secondary">
                        <strong>Vista previa del clip:</strong>
                        <span id="clipPreview">
                            Si marcas en <strong>10:00</strong>, el clip será de <strong>09:55</strong> a <strong>10:03</strong>
                            (<span id="durationPreview">8</span> segundos)
                        </span>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Guardar
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
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Consejos</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li class="mb-2"><strong>Lead/Lag:</strong> Define cuánto contexto incluir antes y después de la acción.</li>
                    <li class="mb-2"><strong>Tecla rápida:</strong> Usa letras que te recuerden a la acción (T=Try, S=Scrum).</li>
                    <li class="mb-2"><strong>Colores:</strong> Usa colores distintos para identificar rápido cada tipo.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('colorText').value = this.value;
});

function updatePreview() {
    const lead = parseInt(document.getElementById('lead_seconds').value) || 0;
    const lag = parseInt(document.getElementById('lag_seconds').value) || 0;
    const duration = lead + lag;

    const startMin = Math.floor((600 - lead) / 60);
    const startSec = (600 - lead) % 60;
    const endMin = Math.floor((600 + lag) / 60);
    const endSec = (600 + lag) % 60;

    const format = (m, s) => String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');

    document.getElementById('clipPreview').innerHTML =
        `Si marcas en <strong>10:00</strong>, el clip será de <strong>${format(startMin, startSec)}</strong> a <strong>${format(endMin, endSec)}</strong>
         (<span id="durationPreview">${duration}</span> segundos)`;
}

document.getElementById('lead_seconds').addEventListener('input', updatePreview);
document.getElementById('lag_seconds').addEventListener('input', updatePreview);
</script>
@endpush
@endsection
