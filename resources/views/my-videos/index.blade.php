@extends('layouts.app')

@section('page_title', 'Mis Videos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Mis Videos</li>
@endsection

@section('main_content')

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
                            <div class="card video-card h-100">
                                
                                <!-- Video Thumbnail -->
                                <a href="{{ route('assignments.show', $assignment) }}" class="d-block">
                                    <div class="card-img-top bg-dark d-flex align-items-center justify-content-center video-thumbnail" style="height: 200px; cursor: pointer;">
                                        <i class="fas fa-play-circle fa-4x text-white opacity-75"></i>
                                    </div>
                                </a>

                                <!-- Video Info -->
                                <div class="card-body p-3">
                                    <h6 class="card-title font-weight-bold">{{ $assignment->video->title }}</h6>
                                    
                                    <!-- Rugby Situation Badge -->
                                    @if($assignment->video->rugbySituation)
                                        <span class="badge mb-2" style="background-color: {{ $assignment->video->rugbySituation->color }}; color: white;">
                                            {{ $assignment->video->rugbySituation->name }}
                                        </span>
                                    @endif


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


                                    <!-- Notes -->
                                    @if($assignment->notes)
                                        <div class="alert alert-info alert-sm p-2 mt-2">
                                            <small><strong>Instrucciones:</strong><br>{{ $assignment->notes }}</small>
                                        </div>
                                    @endif
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
    .video-thumbnail {
        transition: all 0.3s ease;
    }
    .video-thumbnail:hover {
        background-color: #2a2a2a !important;
    }
    .video-thumbnail:hover i {
        opacity: 1 !important;
        transform: scale(1.1);
        color: #1e4d2b !important;
    }
    .alert-sm {
        font-size: 0.875rem;
    }
</style>
@endsection