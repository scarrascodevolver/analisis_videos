@extends('layouts.app')

@section('page_title', 'Autoevaluación - Paso 2')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ url('/evaluacion') }}">Autoevaluación</a></li>
    <li class="breadcrumb-item active">Paso 2</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Progreso</span>
                <span class="text-rugby font-weight-bold">Paso 2 de 5</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-rugby" role="progressbar" style="width: 40%"></div>
            </div>
        </div>

        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-football-ball"></i> Destrezas Básicas
                </h3>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Evalúa tus habilidades técnicas del <strong>0 al 10</strong></p>

                @php
                    $habilidades = [
                        ['id' => 'recepcion_pelota', 'nombre' => 'Recepción de pelota', 'icon' => 'fa-hand-paper'],
                        ['id' => 'pase_dos_lados', 'nombre' => 'Pase para los dos lados', 'icon' => 'fa-exchange-alt'],
                        ['id' => 'juego_aereo', 'nombre' => 'Juego aéreo', 'icon' => 'fa-plane'],
                        ['id' => 'tackle', 'nombre' => 'Tackle', 'icon' => 'fa-shield-alt'],
                        ['id' => 'ruck', 'nombre' => 'Ruck', 'icon' => 'fa-users'],
                        ['id' => 'duelos', 'nombre' => 'Duelos', 'icon' => 'fa-fist-raised'],
                        ['id' => 'carreras', 'nombre' => 'Carreras', 'icon' => 'fa-running'],
                        ['id' => 'conocimiento_plan', 'nombre' => 'Conocimiento plan de juego', 'icon' => 'fa-book'],
                        ['id' => 'entendimiento_juego', 'nombre' => 'Entendimiento del juego', 'icon' => 'fa-brain'],
                        ['id' => 'reglamento', 'nombre' => 'Reglamento', 'icon' => 'fa-balance-scale']
                    ];
                @endphp

                @foreach($habilidades as $hab)
                <div class="form-group mb-4">
                    <label class="font-weight-bold mb-2">
                        <i class="fas {{ $hab['icon'] }}"></i> {{ $hab['nombre'] }}
                    </label>
                    <div class="d-flex align-items-center">
                        <span class="mr-2 text-muted small">0</span>
                        <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                               min="0" max="10" value="0" id="{{ $hab['id'] }}">
                        <span class="ml-2 text-muted small">10</span>
                        <span class="badge badge-rugby ml-3" id="{{ $hab['id'] }}-value" style="min-width: 35px;">0</span>
                    </div>
                </div>
                @endforeach

            </div>
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between">
                    <a href="{{ url('/evaluacion/paso-1') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </a>
                    <a href="{{ url('/evaluacion/paso-3') }}" class="btn btn-rugby">
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
    // Update badge values when sliders change
    $('.evaluation-slider').on('input', function() {
        const id = $(this).attr('id');
        const value = $(this).val();
        $(`#${id}-value`).text(value);

        // Change badge color based on value
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
.bg-rugby {
    background-color: #1e4d2b !important;
}

.text-rugby {
    color: #1e4d2b !important;
}

.custom-range::-webkit-slider-thumb {
    background-color: #1e4d2b;
}

.custom-range::-moz-range-thumb {
    background-color: #1e4d2b;
}

.custom-range::-ms-thumb {
    background-color: #1e4d2b;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem !important;
    }

    .form-group {
        margin-bottom: 2rem !important;
    }

    .custom-range {
        height: 25px;
    }

    .badge {
        font-size: 0.9rem;
    }
}
</style>
