@extends('layouts.app')

@section('page_title', 'Dashboard Jugador')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Dashboard Jugador</li>
@endsection

@section('main_content')
    <!-- Welcome Section -->
    <div class="row">
        <div class="col-12">
            <div class="card card-rugby">
                <div class="card-body">
                    <h4>🏉 Bienvenido, {{ $user->name }}</h4>
                    <p class="text-muted">Tu espacio personal para revisar análisis y mejorar tu rendimiento</p>
                    @if($user->profile)
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <strong>Posición:</strong> {{ $user->profile->position ?? 'No especificada' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Nivel:</strong> 
                                <span class="badge badge-success">{{ ucfirst($user->profile->experience_level) }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $assignedVideos->count() }}</h3>
                    <p>Videos Asignados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-video"></i>
                </div>
                <a href="{{ route('player.videos') }}" class="small-box-footer">
                    Ver todos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $completedAssignments }}</h3>
                    <p>Completados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="{{ route('player.completed') }}" class="small-box-footer">
                    Ver historial <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $pendingAssignments }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <a href="{{ route('player.pending') }}" class="small-box-footer">
                    Ver pendientes <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload"></i>
                        Solicitar Análisis
                    </h3>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted mb-3">¿Tienes un video que quieres que analicen?</p>
                    <a href="{{ route('player.upload') }}" class="btn btn-rugby btn-lg">
                        <i class="fas fa-video"></i> Subir Video
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tasks"></i>
                        Videos Asignados Recientemente
                    </h3>
                </div>
                <div class="card-body">
                    @forelse($assignedVideos as $assignment)
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded video-card">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $assignment->video->title }}</h6>
                                <small class="text-muted d-block">
                                    Asignado por: {{ $assignment->assignedBy->name }}
                                </small>
                                <small class="text-muted">
                                    {{ $assignment->video->analyzedTeam->name }} 
                                    - {{ $assignment->video->category->name }}
                                </small>
                                @if($assignment->notes)
                                    <div class="mt-2">
                                        <small><strong>Notas:</strong> {{ $assignment->notes }}</small>
                                    </div>
                                @endif
                            </div>
                            <div class="text-right ml-3">
                                <div class="mb-2">
                                    @if($assignment->status === 'completed')
                                        <span class="badge badge-success">Completado</span>
                                    @elseif($assignment->status === 'in_progress')
                                        <span class="badge badge-warning">En Progreso</span>
                                    @else
                                        <span class="badge badge-secondary">Asignado</span>
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('videos.show', $assignment->video) }}" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-play"></i> Ver Video
                                    </a>
                                </div>
                                @if($assignment->due_date)
                                    <small class="text-muted d-block mt-1">
                                        Vence: {{ $assignment->due_date->format('d/m/Y') }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No tienes videos asignados en este momento.</p>
                            <p>Los analistas te asignarán videos para revisar y estudiar.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i>
                        Tu Progreso
                    </h3>
                </div>
                <div class="card-body">
                    @php
                        $totalAssignments = $completedAssignments + $pendingAssignments;
                        $progressPercentage = $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100) : 0;
                    @endphp
                    <div class="progress mb-3">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: {{ $progressPercentage }}%" 
                             aria-valuenow="{{ $progressPercentage }}" 
                             aria-valuemin="0" aria-valuemax="100">
                            {{ $progressPercentage }}%
                        </div>
                    </div>
                    <p class="text-muted">
                        Has completado {{ $completedAssignments }} de {{ $totalAssignments }} asignaciones
                    </p>
                    
                    <!-- Weekly Goal -->
                    <div class="mt-4">
                        <h6>Meta Semanal</h6>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 70%">
                                70% (7/10 videos)
                            </div>
                        </div>
                        <small class="text-muted">Objetivo: revisar 10 videos por semana</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-star"></i>
                        Logros Recientes
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-medal text-warning fa-2x mr-3"></i>
                        <div>
                            <h6 class="mb-0">Primer Video Revisado</h6>
                            <small class="text-muted">Completaste tu primera asignación</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-fire text-danger fa-2x mr-3"></i>
                        <div>
                            <h6 class="mb-0">Racha de 3 días</h6>
                            <small class="text-muted">Has revisado videos 3 días consecutivos</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-comments text-info fa-2x mr-3"></i>
                        <div>
                            <h6 class="mb-0">Comentarista Activo</h6>
                            <small class="text-muted">Has hecho más de 10 comentarios</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection