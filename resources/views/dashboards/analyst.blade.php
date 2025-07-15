@extends('layouts.app')

@section('page_title', 'Dashboard Analista')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Dashboard Analista</li>
@endsection

@section('main_content')
    <!-- Welcome Section -->
    <div class="row">
        <div class="col-12">
            <div class="card card-rugby">
                <div class="card-body">
                    <h4>🏉 Bienvenido, {{ $user->name }}</h4>
                    <p class="text-muted">Panel de control para análisis de videos de rugby - Los Troncos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box info-box-rugby">
                <div class="inner">
                    <h3>{{ $totalVideos }}</h3>
                    <p>Videos Subidos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-video"></i>
                </div>
                <a href="{{ route('videos.index') }}" class="small-box-footer">
                    Ver todos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $pendingAssignments }}</h3>
                    <p>Asignaciones Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <a href="{{ route('assignments.index') }}" class="small-box-footer">
                    Ver todas <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $teams->count() }}</h3>
                    <p>Equipos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('teams.index') }}" class="small-box-footer">
                    Ver equipos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $categories->count() }}</h3>
                    <p>Categorías</p>
                </div>
                <div class="icon">
                    <i class="fas fa-list"></i>
                </div>
                <a href="{{ route('categories.index') }}" class="small-box-footer">
                    Ver categorías <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload"></i>
                        Acciones Rápidas
                    </h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('videos.create') }}" class="btn btn-rugby btn-lg btn-block mb-3">
                        <i class="fas fa-video"></i> Subir Nuevo Video
                    </a>
                    <button class="btn btn-outline-success btn-lg btn-block mb-3" disabled>
                        <i class="fas fa-user-plus"></i> Asignar Video a Jugador (Próximamente)
                    </button>
                    <a href="{{ route('analyst.reports') }}" class="btn btn-outline-info btn-lg btn-block">
                        <i class="fas fa-chart-bar"></i> Generar Reporte
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock"></i>
                        Videos Recientes
                    </h3>
                </div>
                <div class="card-body">
                    @forelse($uploadedVideos as $video)
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded video-card">
                            <div>
                                <h6 class="mb-1">{{ $video->title }}</h6>
                                <small class="text-muted">
                                    {{ $video->analyzedTeam->name }} vs {{ $video->rivalTeam->name ?? 'N/A' }}
                                </small><br>
                                <span class="badge badge-primary">{{ $video->category->name }}</span>
                            </div>
                            <div class="text-right">
                                <small class="text-muted d-block">{{ $video->created_at->diffForHumans() }}</small>
                                <a href="{{ route('videos.show', $video) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center">No has subido videos aún.</p>
                        <div class="text-center">
                            <a href="{{ route('videos.create') }}" class="btn btn-rugby">
                                <i class="fas fa-plus"></i> Subir Primer Video
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i>
                        Actividad Reciente
                    </h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="time-label">
                            <span class="bg-success">Hoy</span>
                        </div>
                        <div>
                            <i class="fas fa-video bg-blue"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> hace 2 horas</span>
                                <h3 class="timeline-header">Video subido exitosamente</h3>
                                <div class="timeline-body">
                                    Se subió el video "Análisis Scrum Los Troncos vs DOBS"
                                </div>
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-user bg-green"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fas fa-clock"></i> hace 4 horas</span>
                                <h3 class="timeline-header">Video asignado</h3>
                                <div class="timeline-body">
                                    Asignaste un video de análisis a Jugador Ejemplo
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection