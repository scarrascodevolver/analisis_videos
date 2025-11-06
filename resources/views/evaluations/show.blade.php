@extends('layouts.app')

@section('page_title', 'Detalle de Evaluación - ' . $player->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('evaluations.dashboard') }}">Resultados de Evaluaciones</a></li>
    <li class="breadcrumb-item active">{{ $player->name }}</li>
@endsection

@section('main_content')
<div class="container-fluid py-4">
    <!-- Header del Jugador -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <!-- Avatar -->
                        <div class="mr-3">
                            @if($player->profile && $player->profile->avatar)
                                <img src="{{ asset('storage/' . $player->profile->avatar) }}"
                                     alt="Avatar"
                                     class="img-circle elevation-2"
                                     style="width: 80px; height: 80px; object-fit: cover;">
                            @else
                                <i class="fas fa-user-circle fa-5x text-muted"></i>
                            @endif
                        </div>

                        <!-- Info del Jugador -->
                        <div class="flex-grow-1">
                            <h2 class="mb-1">{{ $player->name }}</h2>
                            <div class="text-muted">
                                <i class="fas {{ $isForward ? 'fa-shield-alt' : 'fa-running' }}"></i>
                                {{ $player->profile->position ?? 'N/A' }}
                                @if($player->profile->player_number)
                                    <span class="ml-2">#{{ $player->profile->player_number }}</span>
                                @endif
                                <span class="ml-3">
                                    <i class="fas fa-users"></i>
                                    {{ $player->profile->category->name ?? 'Sin categoría' }}
                                </span>
                            </div>
                        </div>

                        <!-- Promedio Total -->
                        <div class="text-center">
                            <h1 class="mb-0" style="font-size: 3rem; color: {{
                                $totalScore >= 7 ? '#1e4d2b' : ($totalScore >= 5 ? '#ffc107' : '#dc3545')
                            }}">
                                {{ number_format($totalScore, 1) }}
                            </h1>
                            <small class="text-muted">Promedio Total</small>
                            <div class="mt-2">
                                <span class="badge badge-secondary">
                                    {{ $evaluationCount }} {{ $evaluationCount == 1 ? 'evaluación' : 'evaluaciones' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categorías de Evaluación -->
    <div class="row">
        <!-- Acondicionamiento Físico -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header" style="background-color: #1e4d2b; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-running"></i>
                        Acondicionamiento Físico
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($averages['acondicionamiento'] as $key => $value)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-capitalize">{{ str_replace('_', ' ', $key) }}</small>
                                <small><strong>{{ number_format($value, 1) }}</strong> / 10</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar"
                                     style="width: {{ ($value / 10) * 100 }}%; background-color: {{
                                         $value >= 7 ? '#1e4d2b' : ($value >= 5 ? '#ffc107' : '#dc3545')
                                     }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Destrezas Mentales -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header" style="background-color: #1e4d2b; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-brain"></i>
                        Destrezas Mentales
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($averages['destrezas_mentales'] as $key => $value)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-capitalize">{{ str_replace('_', ' ', $key) }}</small>
                                <small><strong>{{ number_format($value, 1) }}</strong> / 10</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar"
                                     style="width: {{ ($value / 10) * 100 }}%; background-color: {{
                                         $value >= 7 ? '#1e4d2b' : ($value >= 5 ? '#ffc107' : '#dc3545')
                                     }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Destrezas Básicas -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #1e4d2b; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-football-ball"></i>
                        Destrezas Básicas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($averages['destrezas_basicas'] as $key => $value)
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-capitalize">{{ str_replace('_', ' ', $key) }}</small>
                                    <small><strong>{{ number_format($value, 1) }}</strong> / 10</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar"
                                         style="width: {{ ($value / 10) * 100 }}%; background-color: {{
                                             $value >= 7 ? '#1e4d2b' : ($value >= 5 ? '#ffc107' : '#dc3545')
                                         }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Otros Aspectos -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header" style="background-color: #1e4d2b; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-star"></i>
                        Otros Aspectos
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($averages['otros_aspectos'] as $key => $value)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-capitalize">{{ str_replace('_', ' ', $key) }}</small>
                                <small><strong>{{ number_format($value, 1) }}</strong> / 10</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar"
                                     style="width: {{ ($value / 10) * 100 }}%; background-color: {{
                                         $key == 'actitud_negativa'
                                         ? ($value <= 3 ? '#1e4d2b' : ($value <= 5 ? '#ffc107' : '#dc3545'))
                                         : ($value >= 7 ? '#1e4d2b' : ($value >= 5 ? '#ffc107' : '#dc3545'))
                                     }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Habilidades Específicas (Forwards o Backs) -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header" style="background-color: #1e4d2b; color: white;">
                    <h5 class="mb-0">
                        <i class="fas {{ $isForward ? 'fa-shield-alt' : 'fa-bolt' }}"></i>
                        Habilidades {{ $isForward ? 'Forwards' : 'Backs' }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($isForward)
                        @foreach($averages['habilidades_forwards'] as $key => $value)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-capitalize">{{ str_replace('_', ' ', $key) }}</small>
                                    <small>
                                        @if($value !== null)
                                            <strong>{{ number_format($value, 1) }}</strong> / 10
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </div>
                                @if($value !== null)
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar"
                                             style="width: {{ ($value / 10) * 100 }}%; background-color: {{
                                                 $value >= 7 ? '#1e4d2b' : ($value >= 5 ? '#ffc107' : '#dc3545')
                                             }}">
                                        </div>
                                    </div>
                                @else
                                    <div class="progress" style="height: 8px; background-color: #e9ecef;">
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        @foreach($averages['habilidades_backs'] as $key => $value)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-capitalize">{{ str_replace('_', ' ', $key) }}</small>
                                    <small>
                                        @if($value !== null)
                                            <strong>{{ number_format($value, 1) }}</strong> / 10
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </div>
                                @if($value !== null)
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar"
                                             style="width: {{ ($value / 10) * 100 }}%; background-color: {{
                                                 $value >= 7 ? '#1e4d2b' : ($value >= 5 ? '#ffc107' : '#dc3545')
                                             }}">
                                        </div>
                                    </div>
                                @else
                                    <div class="progress" style="height: 8px; background-color: #e9ecef;">
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Lista de Evaluadores -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #1e4d2b; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i>
                        Evaluadores ({{ $evaluationCount }})
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($evaluations as $evaluation)
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    @if($evaluation->evaluator->profile && $evaluation->evaluator->profile->avatar)
                                        <img src="{{ asset('storage/' . $evaluation->evaluator->profile->avatar) }}"
                                             alt="Avatar"
                                             class="img-circle elevation-1 mr-2"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <i class="fas fa-user-circle fa-2x text-muted mr-2"></i>
                                    @endif
                                    <div>
                                        <div><strong>{{ $evaluation->evaluator->name }}</strong></div>
                                        <small class="text-muted">
                                            Puntaje: <span style="color: {{
                                                $evaluation->total_score >= 7 ? '#1e4d2b' :
                                                ($evaluation->total_score >= 5 ? '#ffc107' : '#dc3545')
                                            }}">
                                                {{ number_format($evaluation->total_score, 1) }}
                                            </span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón de regreso -->
    <div class="row">
        <div class="col-12">
            <a href="{{ route('evaluations.dashboard') }}" class="btn text-white" style="background-color: #1e4d2b;">
                <i class="fas fa-arrow-left"></i> Volver a Resultados
            </a>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
/* Card hover effect */
.card:hover {
    transform: translateY(-2px);
    transition: all 0.2s ease;
}

/* Progress bar animations */
.progress-bar {
    transition: width 0.5s ease;
}
</style>
@endsection
