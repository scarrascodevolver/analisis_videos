@extends('layouts.app')

@section('page_title', 'Autoevaluación - Paso 1')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ url('/evaluacion') }}">Autoevaluación</a></li>
    <li class="breadcrumb-item active">Paso 1</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Progreso</span>
                <span class="text-rugby font-weight-bold">Paso 1 de 5</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-rugby" role="progressbar" style="width: 20%"></div>
            </div>
        </div>

        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-running"></i> Acondicionamiento Físico
                </h3>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Evalúa tu condición física del <strong>0 al 10</strong></p>

                <!-- Resistencia -->
                <div class="form-group mb-5">
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-heart"></i> Resistencia
                    </label>
                    <div class="d-flex align-items-center">
                        <span class="mr-3 text-muted">0</span>
                        <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                               min="0" max="10" value="0" id="resistencia">
                        <span class="ml-3 text-muted">10</span>
                    </div>
                    <div class="text-center mt-2">
                        <span class="badge badge-lg badge-rugby" id="resistencia-value">0</span>
                    </div>
                </div>

                <!-- Velocidad -->
                <div class="form-group mb-5">
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-bolt"></i> Velocidad
                    </label>
                    <div class="d-flex align-items-center">
                        <span class="mr-3 text-muted">0</span>
                        <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                               min="0" max="10" value="0" id="velocidad">
                        <span class="ml-3 text-muted">10</span>
                    </div>
                    <div class="text-center mt-2">
                        <span class="badge badge-lg badge-rugby" id="velocidad-value">0</span>
                    </div>
                </div>

                <!-- Musculatura -->
                <div class="form-group mb-5">
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-dumbbell"></i> Musculatura
                    </label>
                    <div class="d-flex align-items-center">
                        <span class="mr-3 text-muted">0</span>
                        <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                               min="0" max="10" value="0" id="musculatura">
                        <span class="ml-3 text-muted">10</span>
                    </div>
                    <div class="text-center mt-2">
                        <span class="badge badge-lg badge-rugby" id="musculatura-value">0</span>
                    </div>
                </div>

            </div>
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between">
                    <a href="{{ url('/evaluacion') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </a>
                    <a href="{{ url('/evaluacion/paso-2') }}" class="btn btn-rugby">
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

.badge-lg {
    font-size: 1.2rem;
    padding: 0.5rem 1rem;
    min-width: 50px;
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
        height: 30px;
    }
}
</style>
