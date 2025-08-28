@extends('layouts.app')

@section('page_title', 'Mis Videos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Mis Videos</li>
@endsection

@section('main_content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Total Asignados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-video"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['pending'] }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['completed'] }}</h3>
                    <p>Completados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $stats['overdue'] }}</h3>
                    <p>Atrasados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Assigned Videos -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-circle"></i>
                Videos Asignados para {{ auth()->user()->name }}
            </h3>
        </div>
        <div class="card-body">
            @if($assignedVideos->count() > 0)
                <div class="row">
                    @foreach($assignedVideos as $assignment)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card video-card h-100 
                                {{ $assignment->status === 'completed' ? 'border-success' : 
                                   ($assignment->due_date && $assignment->due_date < now() && $assignment->status !== 'completed' ? 'border-danger' : 'border-warning') }}">
                                
                                <!-- Video Thumbnail -->
                                <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-play-circle fa-4x text-white opacity-75"></i>
                                </div>

                                <!-- Video Info -->
                                <div class="card-body p-3">
                                    <h6 class="card-title font-weight-bold">{{ $assignment->video->title }}</h6>
                                    
                                    <!-- Rugby Situation Badge -->
                                    @if($assignment->video->rugbySituation)
                                        <span class="badge mb-2" style="background-color: {{ $assignment->video->rugbySituation->color }}; color: white;">
                                            {{ $assignment->video->rugbySituation->name }}
                                        </span>
                                    @endif

                                    <!-- Assignment Status -->
                                    <div class="mb-2">
                                        @if($assignment->status === 'completed')
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Completado
                                            </span>
                                        @elseif($assignment->due_date && $assignment->due_date < now())
                                            <span class="badge badge-danger">
                                                <i class="fas fa-exclamation-triangle"></i> Atrasado
                                            </span>
                                        @else
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Pendiente
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Teams -->
                                    <p class="card-text small text-muted mb-2">
                                        <i class="fas fa-users"></i>
                                        {{ $assignment->video->analyzedTeam->name }}
                                        @if($assignment->video->rivalTeam)
                                            vs {{ $assignment->video->rivalTeam->name }}
                                        @endif
                                    </p>

                                    <!-- Assigned By -->
                                    <p class="card-text small text-muted mb-2">
                                        <i class="fas fa-user"></i>
                                        Asignado por: {{ $assignment->assignedBy->name }}
                                    </p>

                                    <!-- Due Date -->
                                    @if($assignment->due_date)
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fas fa-calendar"></i>
                                            Fecha límite: {{ $assignment->due_date->format('d/m/Y') }}
                                        </p>
                                    @endif

                                    <!-- Notes -->
                                    @if($assignment->notes)
                                        <div class="alert alert-info alert-sm p-2 mt-2">
                                            <small><strong>Instrucciones:</strong><br>{{ $assignment->notes }}</small>
                                        </div>
                                    @endif
                                </div>

                                <!-- Actions -->
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100">
                                        <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-play"></i> Ver Video
                                        </a>
                                        @if($assignment->status !== 'completed')
                                            <form action="{{ route('assignments.complete', $assignment) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-success btn-sm" 
                                                        onclick="return confirm('¿Marcar como completado?')">
                                                    <i class="fas fa-check"></i> Completar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $assignedVideos->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-video fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No tienes videos asignados</h4>
                    <p class="text-muted">Los analistas y entrenadores te asignarán videos para análisis aquí.</p>
                    <a href="{{ route('videos.index') }}" class="btn btn-primary">
                        <i class="fas fa-video"></i> Ver Videos del Equipo
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('css')
<style>
    .video-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .video-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .alert-sm {
        font-size: 0.875rem;
    }
</style>
@endsection