@extends('layouts.app')

@section('page_title', 'Autoevaluación - Paso 5')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ url('/evaluacion') }}">Autoevaluación</a></li>
    <li class="breadcrumb-item active">Paso 5</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Progreso</span>
                <span class="text-rugby font-weight-bold">Paso 5 de 5</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-rugby" role="progressbar" style="width: 100%"></div>
            </div>
        </div>

        <!-- DEMO: Selector de posición (en producción esto vendrá del perfil del usuario) -->
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle"></i> <strong>Demo:</strong> Selecciona tu posición para ver las preguntas específicas
            <div class="btn-group mt-2 d-block" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnForward">Forward</button>
                <button type="button" class="btn btn-sm btn-outline-success" id="btnBack">Back</button>
            </div>
        </div>

        <!-- FORWARDS Section -->
        <div class="card card-rugby" id="forwardsSection" style="display: none;">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-shield-alt"></i> Habilidades de Forward
                </h3>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Evalúa tus habilidades específicas como Forward del <strong>0 al 10</strong></p>

                @php
                    $forwardsSkills = [
                        ['id' => 'scrum_tecnica', 'nombre' => 'Scrum - Técnica', 'icon' => 'fa-users'],
                        ['id' => 'scrum_empuje', 'nombre' => 'Scrum - Empuje', 'icon' => 'fa-fist-raised'],
                        ['id' => 'line_levantar', 'nombre' => 'Lineout - Levantar', 'icon' => 'fa-hands-helping'],
                        ['id' => 'line_saltar', 'nombre' => 'Lineout - Saltar', 'icon' => 'fa-arrow-up'],
                        ['id' => 'line_lanzamiento', 'nombre' => 'Lineout - Lanzamiento', 'icon' => 'fa-bullseye']
                    ];
                @endphp

                @foreach($forwardsSkills as $skill)
                <div class="form-group mb-5">
                    <label class="font-weight-bold mb-3">
                        <i class="fas {{ $skill['icon'] }}"></i> {{ $skill['nombre'] }}
                    </label>
                    <div class="d-flex align-items-center">
                        <span class="mr-3 text-muted">0</span>
                        <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                               min="0" max="10" value="0" id="{{ $skill['id'] }}">
                        <span class="ml-3 text-muted">10</span>
                    </div>
                    <div class="text-center mt-2">
                        <span class="badge badge-lg badge-rugby" id="{{ $skill['id'] }}-value">0</span>
                    </div>
                </div>
                @endforeach

            </div>
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between">
                    <a href="{{ url('/evaluacion/paso-4') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </a>
                    <a href="{{ url('/evaluacion/completada') }}" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle"></i> Enviar Evaluación
                    </a>
                </div>
            </div>
        </div>

        <!-- BACKS Section -->
        <div class="card card-rugby" id="backsSection" style="display: none;">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-running"></i> Habilidades de Back
                </h3>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Evalúa tus habilidades específicas como Back del <strong>0 al 10</strong></p>

                @php
                    $backsSkills = [
                        ['id' => 'kick_salidas', 'nombre' => 'Kick - Salidas', 'icon' => 'fa-flag'],
                        ['id' => 'kick_aire', 'nombre' => 'Kick - Aire', 'icon' => 'fa-cloud'],
                        ['id' => 'kick_rastron', 'nombre' => 'Kick - Rastrón', 'icon' => 'fa-angle-double-right'],
                        ['id' => 'kick_palos', 'nombre' => 'Kick - Palos', 'icon' => 'fa-crosshairs'],
                        ['id' => 'kick_drop', 'nombre' => 'Kick - Drop', 'icon' => 'fa-football-ball']
                    ];
                @endphp

                @foreach($backsSkills as $skill)
                <div class="form-group mb-5">
                    <label class="font-weight-bold mb-3">
                        <i class="fas {{ $skill['icon'] }}"></i> {{ $skill['nombre'] }}
                    </label>
                    <div class="d-flex align-items-center">
                        <span class="mr-3 text-muted">0</span>
                        <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                               min="0" max="10" value="0" id="{{ $skill['id'] }}">
                        <span class="ml-3 text-muted">10</span>
                    </div>
                    <div class="text-center mt-2">
                        <span class="badge badge-lg badge-rugby" id="{{ $skill['id'] }}-value">0</span>
                    </div>
                </div>
                @endforeach

            </div>
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between">
                    <a href="{{ url('/evaluacion/paso-4') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </a>
                    <a href="{{ url('/evaluacion/completada') }}" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle"></i> Enviar Evaluación
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
    // Demo toggle between forwards/backs
    $('#btnForward').on('click', function() {
        $('#forwardsSection').show();
        $('#backsSection').hide();
        $(this).addClass('active').removeClass('btn-outline-primary').addClass('btn-primary');
        $('#btnBack').removeClass('active btn-success').addClass('btn-outline-success');
    });

    $('#btnBack').on('click', function() {
        $('#backsSection').show();
        $('#forwardsSection').hide();
        $(this).addClass('active').removeClass('btn-outline-success').addClass('btn-success');
        $('#btnForward').removeClass('active btn-primary').addClass('btn-outline-primary');
    });

    // Update badge values when sliders change
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

    // Show forwards by default
    $('#btnForward').click();
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
