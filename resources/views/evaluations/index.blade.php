@extends('layouts.app')

@section('page_title', 'Evaluación de Jugadores')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Evaluación de Jugadores</li>
@endsection

@section('main_content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #1e4d2b; color: white;">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-users"></i> Evaluación de Jugadores
                    </h3>
                    <small class="d-block mt-1">Evalúa el desempeño de tus compañeros de categoría</small>
                </div>
                <div class="card-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs nav-tabs-horizontal" id="evaluationTabs" role="tablist">
                        <li class="nav-item flex-fill">
                            <a class="nav-link active text-center" id="forwards-tab" data-toggle="tab" href="#forwards" role="tab">
                                <i class="fas fa-shield-alt"></i> <span class="d-none d-sm-inline">Forwards</span><span class="d-inline d-sm-none">Fwd</span>
                                <span class="badge badge-pill" style="background-color: #1e4d2b; color: white;">
                                    {{ $forwardsProgress }}/{{ count($forwards) }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item flex-fill">
                            <a class="nav-link text-center" id="backs-tab" data-toggle="tab" href="#backs" role="tab">
                                <i class="fas fa-running"></i> <span class="d-none d-sm-inline">Backs</span><span class="d-inline d-sm-none">Bks</span>
                                <span class="badge badge-pill" style="background-color: #1e4d2b; color: white;">
                                    {{ $backsProgress }}/{{ count($backs) }}
                                </span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content mt-3" id="evaluationTabContent">
                        <!-- Forwards Tab -->
                        <div class="tab-pane fade show active" id="forwards" role="tabpanel">
                            @if($forwardsProgress > 0)
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar"
                                     style="background-color: #1e4d2b; width: {{ count($forwards) > 0 ? ($forwardsProgress / count($forwards) * 100) : 0 }}%">
                                    {{ count($forwards) > 0 ? round($forwardsProgress / count($forwards) * 100) : 0 }}%
                                </div>
                            </div>
                            @endif

                            @if(count($forwards) > 0)
                                @foreach($forwards as $player)
                                <div class="card mb-2 shadow-sm">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="d-flex align-items-center flex-grow-1 mr-3" style="min-width: 0;">
                                            <!-- Avatar -->
                                            @if($player->profile && $player->profile->avatar)
                                                <img src="{{ asset('storage/' . $player->profile->avatar) }}"
                                                     alt="Avatar"
                                                     class="img-circle elevation-2 mr-3 flex-shrink-0"
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <i class="fas fa-user-circle fa-3x text-muted mr-3 flex-shrink-0"></i>
                                            @endif

                                            <!-- Info del jugador -->
                                            <div style="min-width: 0; flex: 1;">
                                                <div class="player-name-truncate">
                                                    @if($player->evaluated)
                                                        <span style="font-size: 1.2em; color: #1e4d2b;">✅</span>
                                                    @else
                                                        <span style="font-size: 1.2em;">☐</span>
                                                    @endif
                                                    <strong>{{ $player->name }}</strong>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-shield-alt"></i> {{ $player->profile->position ?? 'N/A' }}
                                                    @if($player->profile->player_number)
                                                        <span class="ml-2">#{{ $player->profile->player_number }}</span>
                                                    @endif
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Botones -->
                                        <div class="flex-shrink-0">
                                            @if($player->evaluated)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Evaluado
                                                </span>
                                            @else
                                                <a href="{{ url('/evaluacion/wizard/' . $player->id) }}"
                                                   class="btn btn-sm text-white"
                                                   style="background-color: #1e4d2b;">
                                                    Evaluar
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="alert" style="background-color: #f0f0f0; border-left: 3px solid #1e4d2b;">
                                    <i class="fas fa-info-circle"></i> No hay forwards en tu categoría para evaluar.
                                </div>
                            @endif
                        </div>

                        <!-- Backs Tab -->
                        <div class="tab-pane fade" id="backs" role="tabpanel">
                            @if($backsProgress > 0)
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar"
                                     style="background-color: #1e4d2b; width: {{ count($backs) > 0 ? ($backsProgress / count($backs) * 100) : 0 }}%">
                                    {{ count($backs) > 0 ? round($backsProgress / count($backs) * 100) : 0 }}%
                                </div>
                            </div>
                            @endif

                            @if(count($backs) > 0)
                                @foreach($backs as $player)
                                <div class="card mb-2 shadow-sm">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="d-flex align-items-center flex-grow-1 mr-3" style="min-width: 0;">
                                            <!-- Avatar -->
                                            @if($player->profile && $player->profile->avatar)
                                                <img src="{{ asset('storage/' . $player->profile->avatar) }}"
                                                     alt="Avatar"
                                                     class="img-circle elevation-2 mr-3 flex-shrink-0"
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <i class="fas fa-user-circle fa-3x text-muted mr-3 flex-shrink-0"></i>
                                            @endif

                                            <!-- Info del jugador -->
                                            <div style="min-width: 0; flex: 1;">
                                                <div class="player-name-truncate">
                                                    @if($player->evaluated)
                                                        <span style="font-size: 1.2em; color: #1e4d2b;">✅</span>
                                                    @else
                                                        <span style="font-size: 1.2em;">☐</span>
                                                    @endif
                                                    <strong>{{ $player->name }}</strong>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-running"></i> {{ $player->profile->position ?? 'N/A' }}
                                                    @if($player->profile->player_number)
                                                        <span class="ml-2">#{{ $player->profile->player_number }}</span>
                                                    @endif
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Botones -->
                                        <div class="flex-shrink-0">
                                            @if($player->evaluated)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Evaluado
                                                </span>
                                            @else
                                                <a href="{{ url('/evaluacion/wizard/' . $player->id) }}"
                                                   class="btn btn-sm text-white"
                                                   style="background-color: #1e4d2b;">
                                                    Evaluar
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="alert" style="background-color: #f0f0f0; border-left: 3px solid #1e4d2b;">
                                    <i class="fas fa-info-circle"></i> No hay backs en tu categoría para evaluar.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
/* Tabs styling */
.nav-tabs .nav-link {
    color: #495057;
    border: none;
    border-bottom: 3px solid transparent;
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    border-bottom-color: #c8e6c9;
}

.nav-tabs .nav-link.active {
    color: #1e4d2b;
    font-weight: bold;
    border-color: transparent;
    border-bottom-color: #1e4d2b;
    background-color: transparent;
}

/* Tabs horizontales en móvil */
.nav-tabs-horizontal {
    display: flex;
    flex-wrap: nowrap;
}

.nav-tabs-horizontal .nav-item {
    flex: 1;
}

/* Responsive para tabs en móvil */
@media (max-width: 576px) {
    .nav-tabs-horizontal .nav-link {
        font-size: 0.9rem;
        padding: 0.5rem 0.25rem;
    }

    .nav-tabs-horizontal .badge {
        font-size: 0.75rem;
        padding: 0.15rem 0.4rem;
    }
}

/* Card hover effect */
.card:hover {
    transform: translateY(-2px);
    transition: all 0.2s ease;
}

/* Truncar nombres largos */
.player-name-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

/* Asegurar que botones estén alineados */
.flex-shrink-0 {
    flex-shrink: 0;
}

.flex-grow-1 {
    flex-grow: 1;
}
</style>
@endsection
