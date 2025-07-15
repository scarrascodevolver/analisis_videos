@extends('layouts.app')

@section('page_title', 'Equipos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Equipos</li>
@endsection

@section('main_content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i>
                        Equipos de Rugby
                    </h3>
                </div>
                <div class="card-body">
                    @if(isset($teams) && $teams->count() > 0)
                        <div class="row">
                            @foreach($teams as $team)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card {{ $team->is_own_team ? 'card-rugby' : '' }} h-100">
                                        <div class="card-body text-center">
                                            @if($team->is_own_team)
                                                <i class="fas fa-shield-alt fa-3x text-rugby mb-3"></i>
                                                <h5 class="card-title text-rugby">{{ $team->name }}</h5>
                                                <span class="badge badge-rugby">Nuestro Equipo</span>
                                            @else
                                                <i class="fas fa-users fa-3x text-secondary mb-3"></i>
                                                <h5 class="card-title">{{ $team->name }}</h5>
                                                <span class="badge badge-secondary">Rival</span>
                                            @endif
                                            
                                            @if($team->description)
                                                <p class="card-text mt-3">{{ $team->description }}</p>
                                            @endif
                                            
                                            <div class="mt-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i>
                                                    Agregado {{ $team->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay equipos registrados</h5>
                            <p class="text-muted">Los equipos se configuran automáticamente en el sistema</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row">
        <div class="col-lg-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $teams->where('is_own_team', true)->count() }}</h3>
                    <p>Nuestro Equipo</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $teams->where('is_own_team', false)->count() }}</h3>
                    <p>Equipos Rivales</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
@endsection