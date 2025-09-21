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
                                <div class="card-img-top video-thumbnail-container"
                                     style="height: 200px; overflow: hidden; background: #f8f9fa; position: relative;"
                                     data-video-url="{{ route('videos.stream', $assignment->video) }}"
                                     data-video-id="{{ $assignment->video->id }}"
                                     onclick="window.location.href='{{ route('assignments.show', $assignment) }}'">

                                    <!-- Video Thumbnail using native poster -->
                                    <video class="w-100 h-100"
                                           style="object-fit: cover;"
                                           preload="metadata"
                                           muted>
                                        <source src="{{ route('videos.stream', $assignment->video) }}#t=5" type="video/mp4">
                                    </video>

                                    <!-- Fallback placeholder (only if video fails) -->
                                    <div class="video-fallback d-flex align-items-center justify-content-center h-100"
                                         style="cursor: pointer; background: linear-gradient(135deg, #1e4d2b, #28a745); color: white; display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                                         onclick="window.location.href='{{ route('assignments.show', $assignment) }}'">
                                        <div class="text-center">
                                            <div class="play-button-circle mb-2">
                                                <i class="fas fa-play text-white"></i>
                                            </div>
                                            <small class="text-white font-weight-bold">CARGANDO...</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Video Info -->
                                <div class="card-body p-3">
                                    <h6 class="card-title font-weight-bold">{{ $assignment->video->title }}</h6>
                                    
                                    <!-- Rugby Situation Badge -->
                                    @if($assignment->video->rugbySituation)
                                        <span class="badge badge-rugby-light mb-2">
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
                    <a href="{{ route('videos.index') }}" class="btn btn-rugby">
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

    /* Rugby badge light */
    .badge-rugby-light {
        background: #28a745;
        color: white;
        font-size: 0.875em;
        font-weight: 500;
    }

    /* Rugby thumbnail placeholder */
    .rugby-thumbnail {
        background: #1e4d2b;
        position: relative;
        transition: all 0.3s ease;
    }

    .rugby-thumbnail:hover {
        background: #2d5a3d;
    }

    .play-button-circle {
        width: 60px;
        height: 60px;
        background: #28a745;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
        margin: 0 auto;
    }

    .rugby-thumbnail:hover .play-button-circle {
        transform: scale(1.1);
        background: #218838;
    }

    /* Video thumbnail improvements */
    .video-thumbnail-container {
        transition: all 0.3s ease;
    }

    .video-thumbnail-container:hover {
        transform: scale(1.02);
    }

    .video-thumbnail-img {
        transition: opacity 0.3s ease;
    }

    .video-thumbnail-container:hover .video-thumbnail-img {
        opacity: 0.9;
    }
</style>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎬 Iniciando sistema de thumbnails nativo en Mis Videos...');

    const videoThumbnails = document.querySelectorAll('.video-thumbnail');

    videoThumbnails.forEach((video, index) => {
        const container = video.closest('.video-thumbnail-container');
        const fallback = container.querySelector('.video-fallback');
        const videoId = container.dataset.videoId;

        console.log(`📹 Configurando thumbnail nativo para video ${videoId}`);

        // Error handler - mostrar fallback si video falla
        video.addEventListener('error', function() {
            console.log(`❌ Error cargando video ${videoId}, mostrando fallback`);
            video.style.display = 'none';
            if (fallback) {
                fallback.style.display = 'flex';
            }
        });

        // Success handler
        video.addEventListener('loadedmetadata', function() {
            console.log(`✅ Video ${videoId} cargado correctamente con thumbnail`);
        });
    });
});
</script>
@endsection