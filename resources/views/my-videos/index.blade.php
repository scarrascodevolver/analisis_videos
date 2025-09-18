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

                                    <!-- Generated Thumbnail (will be populated by JS) -->
                                    <img class="video-thumbnail-img w-100 h-100"
                                         style="object-fit: cover; cursor: pointer; display: none;">

                                    <!-- Hidden Video for Thumbnail Generation -->
                                    <video class="video-hidden"
                                           style="display: none;"
                                           preload="metadata"
                                           muted>
                                        <source src="{{ route('videos.stream', $assignment->video) }}" type="{{ $assignment->video->mime_type }}">
                                    </video>

                                    <!-- Placeholder while loading -->
                                    <div class="d-flex align-items-center justify-content-center h-100 rugby-thumbnail"
                                         style="cursor: pointer;">
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
    console.log('🎬 Iniciando generación de thumbnails en Mis Videos...');

    // Detección de dispositivos móviles
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    console.log('📱 Device detection for my-videos thumbnails:', isMobile ? 'MOBILE' : 'DESKTOP');

    const thumbnailContainers = document.querySelectorAll('.video-thumbnail-container');

    thumbnailContainers.forEach((container, index) => {
        if (isMobile) {
            // En móvil: usar placeholder mejorado inmediatamente
            setupMobilePlaceholder(container);
        } else {
            // En PC: generar thumbnail automático con delay
            setTimeout(() => {
                generateThumbnail(container);
            }, index * 800); // Más tiempo entre videos para evitar sobrecarga
        }
    });

    function setupMobilePlaceholder(container) {
        const placeholder = container.querySelector('.rugby-thumbnail');
        const videoId = container.dataset.videoId;

        if (!placeholder) return;

        console.log(`📱 Setting up mobile placeholder for my-video ${videoId}`);

        // LIMPIAR contenido existente para empezar desde cero
        placeholder.innerHTML = '';

        // Agregar efecto visual mejorado PRIMERO
        placeholder.style.cssText = `
            background: linear-gradient(135deg, #1e4d2b 0%, #28a745 100%);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            border-radius: 8px;
        `;

        // Crear contenido HTML completamente nuevo
        placeholder.innerHTML = `
            <div style="text-align: center; color: white; z-index: 10; position: relative;">
                <div style="font-size: 32px; margin-bottom: 8px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                    🏉
                </div>
                <div style="font-size: 14px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.7); letter-spacing: 1px;">
                    VIDEO RUGBY
                </div>
            </div>
        `;

        // Mantener funcionalidad de click
        const originalOnclick = container.getAttribute('onclick');
        if (originalOnclick) {
            placeholder.setAttribute('onclick', originalOnclick);
        }

        console.log(`✅ Mobile placeholder setup complete for my-video ${videoId}`);
    }

    function generateThumbnail(container) {
        const video = container.querySelector('.video-hidden');
        const thumbnailImg = container.querySelector('.video-thumbnail-img');
        const placeholder = container.querySelector('.rugby-thumbnail');
        const videoId = container.dataset.videoId;

        if (!video || !thumbnailImg || !placeholder) return;

        // Cuando el video tiene metadata
        video.addEventListener('loadedmetadata', function() {
            console.log(`📹 Video ${videoId} metadata cargada`);

            // Ir al segundo 5 para mejor thumbnail
            video.currentTime = Math.min(5, video.duration / 4);
        });

        // Cuando llegamos al tiempo deseado
        video.addEventListener('seeked', function() {
            console.log(`🎯 Video ${videoId} positioned para thumbnail`);

            // Crear canvas para capturar frame
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Dimensiones del canvas (manteniendo aspect ratio)
            canvas.width = 320;
            canvas.height = 200;

            // Dibujar frame del video
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convertir a imagen
            const dataURL = canvas.toDataURL('image/jpeg', 0.8);

            // Mostrar thumbnail
            thumbnailImg.src = dataURL;
            thumbnailImg.style.display = 'block';
            placeholder.style.display = 'none';

            console.log(`✅ Thumbnail generado para video ${videoId}`);
        });

        // Error handler
        video.addEventListener('error', function(e) {
            console.log(`❌ Error cargando video ${videoId}:`, e);

            // Mantener placeholder pero cambiar texto
            const text = placeholder.querySelector('small');
            if (text) {
                text.textContent = 'VIDEO RUGBY';
            }
        });

        // Timeout fallback
        setTimeout(() => {
            if (thumbnailImg.style.display === 'none') {
                console.log(`⏰ Timeout para video ${videoId}, manteniendo placeholder`);
                const text = placeholder.querySelector('small');
                if (text) {
                    text.textContent = 'VIDEO RUGBY';
                }
            }
        }, 12000); // 12 segundos timeout para dar más tiempo

        // Iniciar carga
        video.load();
    }
});
</script>
@endsection