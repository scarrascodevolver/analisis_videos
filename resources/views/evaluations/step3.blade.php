@extends('layouts.app')

@section('page_title', 'Autoevaluación - Paso 3')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ url('/evaluacion') }}">Autoevaluación</a></li>
    <li class="breadcrumb-item active">Paso 3</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Progreso</span>
                <span class="text-rugby font-weight-bold">Paso 3 de 5</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-rugby" role="progressbar" style="width: 60%"></div>
            </div>
        </div>

        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-brain"></i> Destrezas Mentales
                </h3>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Evalúa tus capacidades mentales del <strong>0 al 10</strong></p>

                @php
                    $habilidades = [
                        ['id' => 'autocontrol', 'nombre' => 'Autocontrol', 'icon' => 'fa-user-check'],
                        ['id' => 'concentracion', 'nombre' => 'Concentración', 'icon' => 'fa-eye'],
                        ['id' => 'toma_decisiones', 'nombre' => 'Toma de decisiones', 'icon' => 'fa-lightbulb'],
                        ['id' => 'liderazgo', 'nombre' => 'Liderazgo', 'icon' => 'fa-crown']
                    ];
                @endphp

                @foreach($habilidades as $hab)
                <div class="form-group mb-5">
                    <label class="font-weight-bold mb-3">
                        <i class="fas {{ $hab['icon'] }}"></i> {{ $hab['nombre'] }}
                    </label>
                    <div class="d-flex align-items-center">
                        <span class="mr-3 text-muted">0</span>
                        <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                               min="0" max="10" value="0" id="{{ $hab['id'] }}">
                        <span class="ml-3 text-muted">10</span>
                    </div>
                    <div class="text-center mt-2">
                        <span class="badge badge-lg badge-rugby" id="{{ $hab['id'] }}-value">0</span>
                    </div>
                </div>
                @endforeach

            </div>
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between">
                    <a href="{{ url('/evaluacion/paso-2') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </a>
                    <a href="{{ url('/evaluacion/paso-4') }}" class="btn btn-rugby">
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
        badge.removeClass('badge-danger badge-warning badge-rugby');
        if (value <= 3) {
            badge.addClass('badge-danger');
        } else if (value <= 6) {
            badge.addClass('badge-warning');
        } else {
            badge.addClass('badge-rugby');
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

@media (max-width: 768px) {
    .card-body { padding: 1.5rem !important; }
    .form-group { margin-bottom: 2rem !important; }
    .custom-range { height: 30px; }
}
</style>
