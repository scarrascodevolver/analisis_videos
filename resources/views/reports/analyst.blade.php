@extends('layouts.app')

@section('page_title', 'Reportes de Análisis')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Reportes</li>
@endsection

@section('main_content')
    <div class="row">
        <!-- Stats Cards -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalVideos }}</h3>
                    <p>Total Videos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-video"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $completedAssignments }}</h3>
                    <p>Completadas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $pendingAssignments }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $totalAssignments }}</h3>
                    <p>Total Asignaciones</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i>
                        Reportes de Análisis
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-video"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Videos Subidos</span>
                                    <span class="info-box-number">{{ $totalVideos }}</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">
                                        Total de videos en el sistema
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tasa de Completado</span>
                                    <span class="info-box-number">
                                        {{ $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100, 1) : 0 }}%
                                    </span>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" 
                                             style="width: {{ $totalAssignments > 0 ? ($completedAssignments / $totalAssignments) * 100 : 0 }}%"></div>
                                    </div>
                                    <span class="progress-description">
                                        {{ $completedAssignments }} de {{ $totalAssignments }} asignaciones completadas
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <h5>Funcionalidades de Reportes</h5>
                        <p class="text-muted">Esta sección se expandirá con reportes detallados y analytics avanzados.</p>
                        
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                                        <h6>Progreso de Jugadores</h6>
                                        <p class="text-muted small">Seguimiento del progreso individual</p>
                                        <button class="btn btn-outline-primary btn-sm" disabled>
                                            Próximamente
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                        <h6>Reportes PDF</h6>
                                        <p class="text-muted small">Exportar análisis en PDF</p>
                                        <button class="btn btn-outline-danger btn-sm" disabled>
                                            Próximamente
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-pie fa-3x text-success mb-3"></i>
                                        <h6>Analytics Avanzados</h6>
                                        <p class="text-muted small">Métricas detalladas del equipo</p>
                                        <button class="btn btn-outline-success btn-sm" disabled>
                                            Próximamente
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection