@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('main_content')
    <!-- Welcome Section -->
    <div class="row">
        <div class="col-12">
            <div class="card card-rugby">
                <div class="card-body">
                    <h4>🏉 Bienvenido al Sistema de Análisis de Rugby</h4>
                    <p class="text-muted">Equipo Los Troncos - Sistema de análisis de videos profesional</p>
                    <div class="mt-3">
                        <strong>Usuario:</strong> {{ $user->name }}<br>
                        <strong>Rol:</strong> 
                        <span class="badge badge-primary">{{ ucfirst($user->role) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Overview -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $teams->count() }}</h3>
                    <p>Equipos Registrados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $categories->count() }}</h3>
                    <p>Categorías</p>
                </div>
                <div class="icon">
                    <i class="fas fa-list"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>0</h3>
                    <p>Videos Disponibles</p>
                </div>
                <div class="icon">
                    <i class="fas fa-video"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>Activo</h3>
                    <p>Estado del Sistema</p>
                </div>
                <div class="icon">
                    <i class="fas fa-server"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Cards -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Información del Sistema
                    </h3>
                </div>
                <div class="card-body">
                    <p>Este sistema permite el análisis profesional de videos de rugby para el equipo <strong>Los Troncos</strong>.</p>
                    
                    <h6>Funcionalidades disponibles:</h6>
                    <ul>
                        <li>Análisis de videos con comentarios temporales</li>
                        <li>Asignación de videos a jugadores</li>
                        <li>Reportes de progreso</li>
                        <li>Análisis de equipos rivales</li>
                        <li>Dashboards específicos por rol</li>
                    </ul>

                    <div class="mt-3">
                        <h6>Tu rol: <span class="badge badge-primary">{{ ucfirst($user->role) }}</span></h6>
                        @switch($user->role)
                            @case('director_tecnico')
                                <p class="text-muted">Como Director Técnico, tienes acceso a reportes estratégicos y análisis generales del equipo.</p>
                                @break
                            @case('scout')
                                <p class="text-muted">Como Scout, puedes acceder a análisis de equipos rivales y reportes de scouting.</p>
                                @break
                            @case('aficionado')
                                <p class="text-muted">Como Aficionado, tienes acceso limitado a contenido público del equipo.</p>
                                @break
                            @default
                                <p class="text-muted">Acceso general al sistema de análisis de rugby.</p>
                        @endswitch
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i>
                        Equipos del Campeonato
                    </h3>
                </div>
                <div class="card-body">
                    @foreach($teams as $team)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">{{ $team->name }}</span>
                            @if($team->is_own_team)
                                <span class="badge badge-success">Nuestro Equipo</span>
                            @else
                                <span class="badge badge-secondary">Rival</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy"></i>
                        Categorías de Competencia
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($categories as $category)
                            <div class="col-md-3 mb-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-medal"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">{{ $category->name }}</span>
                                        <span class="info-box-number">0 videos</span>
                                        <span class="progress-description">
                                            {{ $category->description }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-envelope"></i>
                        Contacto y Soporte
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Analistas</h6>
                            <p class="text-muted">Para análisis de videos y asignaciones</p>
                            <a href="mailto:analista@lostroncos.cl" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-envelope"></i> Contactar
                            </a>
                        </div>
                        <div class="col-md-4">
                            <h6>Entrenadores</h6>
                            <p class="text-muted">Para planificación y estrategia</p>
                            <a href="mailto:entrenador@lostroncos.cl" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-envelope"></i> Contactar
                            </a>
                        </div>
                        <div class="col-md-4">
                            <h6>Soporte Técnico</h6>
                            <p class="text-muted">Para problemas del sistema</p>
                            <a href="mailto:soporte@lostroncos.cl" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-life-ring"></i> Soporte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection