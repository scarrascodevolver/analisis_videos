@extends('layouts.app')

@section('page_title', 'Autoevaluación')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Autoevaluación</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="card card-rugby">
            <div class="card-header text-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-clipboard-check"></i> Autoevaluación de Jugador
                </h3>
            </div>
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <i class="fas fa-star fa-4x text-rugby mb-3"></i>
                </div>

                <h4 class="mb-4">Evalúa tu desempeño</h4>

                <p class="text-muted mb-4">
                    Este cuestionario te permitirá autoevaluar diferentes aspectos de tu rendimiento como jugador.
                    Responde honestamente calificándote del <strong>0 al 10</strong> en cada categoría.
                </p>

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    <strong>Importante:</strong> La evaluación consta de 5 secciones y tomará aproximadamente 5-10 minutos completarla.
                </div>

                <div class="mb-4">
                    <h5 class="text-rugby mb-3">Secciones a evaluar:</h5>
                    <div class="row text-left">
                        <div class="col-md-6 mb-2">
                            <i class="fas fa-check-circle text-success"></i> Acondicionamiento Físico
                        </div>
                        <div class="col-md-6 mb-2">
                            <i class="fas fa-check-circle text-success"></i> Destrezas Básicas
                        </div>
                        <div class="col-md-6 mb-2">
                            <i class="fas fa-check-circle text-success"></i> Destrezas Mentales
                        </div>
                        <div class="col-md-6 mb-2">
                            <i class="fas fa-check-circle text-success"></i> Otros Aspectos
                        </div>
                        <div class="col-md-6 mb-2">
                            <i class="fas fa-check-circle text-success"></i> Habilidades Específicas
                        </div>
                    </div>
                </div>

                <a href="{{ url('/evaluacion/paso-1') }}" class="btn btn-rugby btn-lg">
                    <i class="fas fa-play-circle"></i> Comenzar Evaluación
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
.text-rugby {
    color: #1e4d2b !important;
}
</style>
