@extends('layouts.app')

@section('page_title', 'Autoevaluación - Paso 4')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ url('/evaluacion') }}">Autoevaluación</a></li>
    <li class="breadcrumb-item active">Paso 4</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Progreso</span>
                <span class="text-rugby font-weight-bold">Paso 4 de 5</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-rugby" role="progressbar" style="width: 80%"></div>
            </div>
        </div>

        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-clipboard-list"></i> Otros Aspectos
                </h3>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Evalúa otros aspectos importantes del <strong>0 al 10</strong></p>

                @php
                    $habilidades = [
                        ['id' => 'disciplina', 'nombre' => 'Disciplina', 'icon' => 'fa-tasks'],
                        ['id' => 'compromiso', 'nombre' => 'Compromiso', 'icon' => 'fa-handshake'],
                        ['id' => 'puntualidad', 'nombre' => 'Puntualidad', 'icon' => 'fa-clock'],
                        ['id' => 'actitud_positiva', 'nombre' => 'Actitud positiva', 'icon' => 'fa-smile'],
                        ['id' => 'actitud_negativa', 'nombre' => 'Actitud negativa', 'icon' => 'fa-frown', 'warning' => true],
                        ['id' => 'comunicacion', 'nombre' => 'Comunicación', 'icon' => 'fa-comments']
                    ];
                @endphp

                @foreach($habilidades as $hab)
                <div class="form-group mb-5">
                    <label class="font-weight-bold mb-3">
                        <i class="fas {{ $hab['icon'] }}"></i> {{ $hab['nombre'] }}
                        @if(isset($hab['warning']))
                            <span class="badge badge-warning ml-2">
                                <i class="fas fa-exclamation-triangle"></i> Resta puntos
                            </span>
                        @endif
                    </label>
                    <div class="d-flex align-items-center">
                        <span class="mr-3 text-muted">0</span>
                        <input type="range" class="custom-range flex-grow-1 evaluation-slider {{ isset($hab['warning']) ? 'warning-slider' : '' }}"
                               min="0" max="10" value="0" id="{{ $hab['id'] }}">
                        <span class="ml-3 text-muted">10</span>
                    </div>
                    <div class="text-center mt-2">
                        <span class="badge badge-lg badge-rugby" id="{{ $hab['id'] }}-value">0</span>
                    </div>
                    @if(isset($hab['warning']))
                        <small class="text-danger d-block text-center mt-2">
                            <i class="fas fa-info-circle"></i> Mientras mayor sea este valor, más puntos se restarán
                        </small>
                    @endif
                </div>
                @endforeach

            </div>
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between">
                    <a href="{{ url('/evaluacion/paso-3') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </a>
                    <a href="{{ url('/evaluacion/paso-5') }}" class="btn btn-rugby">
                        Siguiente <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    $('.evaluation-slider').on('input', function() {
        const id = $(this).attr('id');
        const value = $(this).val();
        $(`#${id}-value`).text(value);

        const badge = $(`#${id}-value`);
        const isWarning = $(this).hasClass('warning-slider');

        badge.removeClass('badge-danger badge-warning badge-rugby badge-success');

        if (isWarning) {
            // Para actitud negativa, invertir colores
            if (value >= 7) {
                badge.addClass('badge-danger');
            } else if (value >= 4) {
                badge.addClass('badge-warning');
            } else {
                badge.addClass('badge-success');
            }
        } else {
            // Colores normales
            if (value <= 3) {
                badge.addClass('badge-danger');
            } else if (value <= 6) {
                badge.addClass('badge-warning');
            } else {
                badge.addClass('badge-rugby');
            }
        }
    });
});
</script>
@endsection

<style>
.bg-rugby { background-color: #1e4d2b !important; }
.text-rugby { color: #1e4d2b !important; }
.badge-lg { font-size: 1.2rem; padding: 0.5rem 1rem; min-width: 50px; }
.custom-range::-webkit-slider-thumb { background-color: #1e4d2b; }
.custom-range::-moz-range-thumb { background-color: #1e4d2b; }
.custom-range::-ms-thumb { background-color: #1e4d2b; }

.warning-slider::-webkit-slider-thumb { background-color: #dc3545; }
.warning-slider::-moz-range-thumb { background-color: #dc3545; }
.warning-slider::-ms-thumb { background-color: #dc3545; }

@media (max-width: 768px) {
    .card-body { padding: 1.5rem !important; }
    .form-group { margin-bottom: 2rem !important; }
    .custom-range { height: 30px; }
}
</style>
