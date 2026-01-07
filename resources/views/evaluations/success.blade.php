@extends('layouts.app')

@section('page_title', 'Evaluación Completada')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ url('/evaluacion') }}">Autoevaluación</a></li>
    <li class="breadcrumb-item active">Completada</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card card-rugby text-center">
            <div class="card-body p-5">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                </div>

                <h2 class="text-rugby mb-4">¡Evaluación Enviada!</h2>

                <p class="text-muted mb-4">
                    Tu autoevaluación ha sido registrada exitosamente.
                    Los entrenadores podrán revisar tus respuestas para brindarte un mejor seguimiento.
                </p>

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    <strong>Importante:</strong> No podrás modificar tus respuestas hasta que el entrenador habilite una nueva evaluación.
                </div>

                <div class="alert alert-success">
                    <i class="fas fa-calendar-check"></i>
                    Evaluación completada el {{ now()->format('d/m/Y H:i') }}
                </div>

                <div class="mt-4">
                    <a href="{{ url('/dashboard') }}" class="btn btn-rugby btn-lg">
                        <i class="fas fa-home"></i> Volver al Dashboard
                    </a>
                </div>

                <div class="mt-3">
                    <a href="{{ url('/videos') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-video"></i> Ver Videos
                    </a>
                </div>
            </div>
        </div>

        <!-- Info adicional -->
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-lightbulb text-warning"></i> ¿Qué sigue?
                </h5>
                <ul class="mb-0">
                    <li>Tu entrenador revisará tu evaluación</li>
                    <li>Recibirás feedback personalizado</li>
                    <li>Se identificarán áreas de mejora</li>
                    <li>Se establecerán objetivos de entrenamiento</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
.text-rugby {
    color: var(--color-primary, #005461) !important;
}
</style>
