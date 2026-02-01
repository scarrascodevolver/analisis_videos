@extends('layouts.app')

@section('page_title', $video->title)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}">Videos</a></li>
    <li class="breadcrumb-item active">{{ $video->title }}</li>
@endsection

@section('main_content')
    <div class="row">
        <!-- Video Player Section -->
        <div class="col-lg-10" id="videoSection">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-play"></i>
                        {{ $video->title }}
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-eye"></i> <span id="viewCount">{{ $video->view_count }}</span> visualizaciones
                            • <i class="fas fa-users"></i> <span id="uniqueViewers">{{ $video->unique_viewers }}</span> usuarios
                        </small>
                    </h3>
                    <div class="card-tools">
                        @if(in_array(auth()->user()->role, ['analista', 'entrenador', 'jugador']))
                            <button id="viewStatsBtn" class="btn btn-sm btn-rugby-light mr-2" data-toggle="modal" data-target="#statsModal">
                                <i class="fas fa-eye"></i> Visualizaciones
                            </button>
                        @endif
                        @if(in_array(auth()->user()->role, ['analista', 'entrenador']))
                            <button id="addAngleBtn" class="btn btn-sm btn-rugby mr-2" data-toggle="modal" data-target="#associateAngleModal">
                                <i class="fas fa-video"></i> Agregar Ángulo
                            </button>
                        @endif
                        @if(auth()->user()->role === 'jugador')
                            <button id="toggleCommentsBtn" class="btn btn-sm btn-rugby-outline mr-2" title="Ocultar/Mostrar comentarios">
                                <i class="fas fa-eye-slash"></i> <span id="toggleCommentsText">Ocultar Comentarios</span>
                            </button>
                        @endif
                        @if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador' || auth()->id() === $video->uploaded_by)
                            <a href="{{ route('videos.edit', $video) }}" class="btn btn-sm btn-rugby-light">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        @endif
                        @if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador')
                            <button type="button" class="btn btn-sm btn-rugby-dark" data-toggle="modal" data-target="#deleteModal">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Video Player -->
                    <div class="video-container" style="position: relative; background: #000; border-radius: 8px; overflow: hidden;">
                        <video id="rugbyVideo" controls style="width: 100%; height: auto; display: block;"
                               preload="metadata"
                               crossorigin="anonymous"
                               x-webkit-airplay="allow">
                            <source src="{{ route('videos.stream', $video) }}" type="video/mp4">
                            Tu navegador no soporta la reproducción de video.
                            <p>Video no disponible. Archivo: {{ $video->file_path }}</p>
                        </video>

                        <!-- Loading Overlay for Slave Videos -->
                        <div id="slaveLoadingOverlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); z-index: 10; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; width: 80%; max-width: 400px;">
                                <div style="background: rgba(0, 91, 97, 0.9); border-radius: 12px; padding: 30px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);">
                                    <i class="fas fa-video fa-3x mb-3" style="color: #00B7B5;"></i>
                                    <h5 class="text-white mb-3" id="loadingOverlayTitle">🎬 Preparando ángulos de cámara</h5>
                                    <div class="progress mb-3" style="height: 8px; background: rgba(255,255,255,0.1);">
                                        <div id="loadingProgressBar" class="progress-bar" role="progressbar" style="width: 0%; background: linear-gradient(90deg, #00B7B5 0%, #005461 100%); transition: width 0.3s ease;"></div>
                                    </div>
                                    <p class="text-white mb-0" id="loadingOverlayText">Iniciando...</p>
                                    <small class="text-muted" id="loadingOverlayCount" style="display: block; margin-top: 8px;">0 de 0 listos</small>
                                </div>
                            </div>
                        </div>

                        @if($video->isPartOfGroup())
                        <!-- Inline Script: Show overlay IMMEDIATELY if video has slaves -->
                        <script>
                        (function() {
                            const overlay = document.getElementById('slaveLoadingOverlay');
                            const masterVideo = document.getElementById('rugbyVideo');

                            if (overlay && masterVideo) {
                                console.log('🎬 Video tiene slaves - mostrando overlay inmediatamente');
                                overlay.style.display = 'block';
                                masterVideo.removeAttribute('controls');
                                masterVideo.style.pointerEvents = 'none';
                            }
                        })();
                        </script>
                        @endif

                        <!-- Canvas overlay para anotaciones -->
                        <canvas id="annotationCanvas"
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5;">
                        </canvas>

                        @include('videos.partials.annotation-toolbar')

                        <!-- Video Utility Controls -->
                        <div class="video-utility-controls">
                            <!-- Picture-in-Picture Button -->
                            <button id="pipBtn" class="video-utility-btn" title="Picture-in-Picture (Mini ventana)">
                                <i class="fas fa-external-link-alt"></i>
                            </button>

                            <!-- Download Button -->
                            <button id="downloadBtn" class="video-utility-btn" title="Descargar video">
                                <i class="fas fa-download"></i>
                            </button>

                            <!-- Speed Control Button -->
                            <button id="speedControlBtn" class="video-utility-btn" title="Velocidad de reproducción">
                                <i class="fas fa-tachometer-alt"></i> <span id="currentSpeed">1x</span>
                            </button>
                            <div id="speedMenu" class="speed-menu">
                                <div class="speed-menu-title">Velocidad</div>
                                <button class="speed-option" data-speed="0.25">0.25x</button>
                                <button class="speed-option" data-speed="0.5">0.5x</button>
                                <button class="speed-option" data-speed="0.75">0.75x</button>
                                <button class="speed-option active" data-speed="1">1x</button>
                                <button class="speed-option" data-speed="1.25">1.25x</button>
                                <button class="speed-option" data-speed="1.5">1.5x</button>
                                <button class="speed-option" data-speed="2">2x</button>
                            </div>
                        </div>

                        <!-- Mobile Fullscreen Button -->
                        <div class="video-controls-overlay" style="position: absolute; bottom: 90px; right: 10px; z-index: 10;">
                            <button id="mobileFullscreenBtn" class="btn btn-sm btn-dark mr-2" title="Pantalla completa" style="display: none;">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button id="addCommentBtn" class="btn btn-sm btn-rugby font-weight-bold mr-2">
                                <i class="fas fa-comment-plus"></i> Comentar aquí
                            </button>
                            @if(in_array(auth()->user()->role, ['analista', 'entrenador']))
                                <button id="toggleAnnotationMode" class="btn btn-sm btn-rugby-outline font-weight-bold">
                                    <i class="fas fa-paint-brush"></i> Anotar
                                </button>
                            @endif
                        </div>
                    </div>

                    @if(in_array(auth()->user()->role, ['analista', 'entrenador']))
                        {{-- ═══════════════════════════════════════════════════════════ --}}
                        {{-- ORDEN DIFERENTE POR ROL:                                   --}}
                        {{-- - ENTRENADOR: Timeline primero (sync es prioridad)        --}}
                        {{-- - ANALISTA: Clips primero (crear clips es prioridad)      --}}
                        {{-- ═══════════════════════════════════════════════════════════ --}}

                        @if(auth()->user()->role === 'entrenador')
                            {{-- ══════════════════════════════════════════════════════ --}}
                            {{-- ENTRENADOR: ORDEN 1 - Timeline de Sincronización     --}}
                            {{-- ══════════════════════════════════════════════════════ --}}
                            <div id="clipTimelineWrapper">
                                <button id="toggleClipTimeline" class="btn btn-block text-left py-2 px-3" style="background: #1a1a1a; border: none; border-radius: 0; color: #fff; border-bottom: 1px solid #333;">
                                    <i class="fas fa-sliders-h mr-2" style="color: #ffc107;"></i>
                                    <strong>Sincronizar Línea de Tiempo</strong>
                                    <i id="clipTimelineArrow" class="fas fa-chevron-down float-right mt-1"></i>
                                </button>

                                <div id="clipTimelineContent" style="display: none; background: #0a0a0a; padding: 10px; max-height: 600px; overflow-y: auto;">
                                    <div class="timeline-control-bar" style="background: #1a1a1a; border: 1px solid #333; border-radius: 6px; padding: 8px 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                        <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 300px;">
                                            <label style="color: #aaa; font-size: 12px; margin: 0; white-space: nowrap;">
                                                <i class="fas fa-sync-alt" style="color: #ffc107;"></i> Offset:
                                            </label>
                                            <input type="range" id="timelineOffsetSlider" class="custom-range" min="-300" max="300" step="0.5" value="{{ $video->timeline_offset ?? 0 }}" style="flex: 1; cursor: pointer; max-width: 200px;">
                                            <span id="offsetDisplay" class="badge" style="background: #ffc107; color: #000; font-weight: bold; font-size: 11px; min-width: 45px; text-align: center;">{{ $video->timeline_offset ?? 0 }}s</span>
                                        </div>
                                        <div style="display: flex; gap: 6px;">
                                            <button id="applyOffsetBtn" class="btn btn-sm" style="background: #00B7B5; color: #fff; font-weight: 600; font-size: 11px; padding: 4px 12px; border: none;"><i class="fas fa-check"></i> Aplicar</button>
                                            <button id="resetOffsetBtn" class="btn btn-sm btn-secondary" style="font-size: 11px; padding: 4px 10px; border: none;" title="Resetear a 0s"><i class="fas fa-undo"></i></button>
                                        </div>
                                        <div id="offsetClipCount" style="color: #888; font-size: 11px; margin-left: auto; white-space: nowrap;"><i class="fas fa-film"></i> <span id="totalClipsCount">0</span> clips</div>
                                    </div>

                                    <div id="clipsTimelineLanes" style="background: #0f0f0f; border-radius: 6px; padding: 10px;">
                                        <div class="text-center py-4" style="color: #666;"><i class="fas fa-spinner fa-spin"></i> Cargando clips...</div>
                                    </div>

                                    <div id="timelineScale" class="d-flex justify-content-between mt-2 px-2" style="color: #555; font-size: 10px; margin-left: 120px;">
                                        <span>0:00</span><span id="scale25"></span><span id="scale50"></span><span id="scale75"></span><span id="scaleEnd"></span>
                                    </div>

                                    <div class="alert alert-dark py-2 mt-3 mb-0" style="background: #1a1a1a; border: 1px solid #333; font-size: 11px;">
                                        <i class="fas fa-lightbulb text-warning"></i> <strong>Cómo usar:</strong><br>
                                        • <strong>Ajusta el offset</strong> con el slider para sincronizar todos los clips<br>
                                        • <strong>Click en un clip</strong> (cuadrado de color) para reproducirlo<br>
                                        • <strong>Click en la barra</strong> para saltar a ese momento del video<br>
                                        • Los clips importados de XML son de solo lectura
                                    </div>
                                </div>
                            </div>

                            {{-- ══════════════════════════════════════════════════════ --}}
                            {{-- ENTRENADOR: ORDEN 2 - Panel de Clips                 --}}
                            {{-- ══════════════════════════════════════════════════════ --}}
                            <div id="clipPanelWrapper" style="background: #0f0f0f;">
                                <button id="toggleClipPanel" class="btn btn-block text-left py-2 px-3" style="background: #252525; border: none; border-radius: 0; color: #fff; border-bottom: 1px solid #333;">
                                    <i class="fas fa-film mr-2" style="color: #00B7B5;"></i>
                                    <strong>Modo Análisis - Clips</strong>
                                    <span id="clipCount" class="badge ml-2" style="background: #00B7B5;">0</span>
                                    <i id="clipPanelArrow" class="fas fa-chevron-up float-right mt-1"></i>
                                </button>

                                <div id="clipPanel" style="display: block; background: #0f0f0f;">
                                    <div class="p-3" style="color: #ccc;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div id="clipButtonsContainer" class="d-flex flex-wrap" style="gap: 8px; flex: 1;">
                                                <div style="color: #888;">Cargando categorías...</div>
                                            </div>
                                            <div class="ml-3" style="white-space: nowrap;">
                                                <button type="button" class="btn btn-sm" style="background: #005461; color: #fff; border: none;" onclick="openCategoryModal()" title="Crear nueva categoría">
                                                    <i class="fas fa-plus"></i> Crear
                                                </button>
                                                <button type="button" class="btn btn-sm ml-1" style="background: #003d4a; color: #fff; border: none;" data-toggle="modal" data-target="#manageCategoriesModal" title="Gestionar categorías">
                                                    <i class="fas fa-cog"></i> Editar
                                                </button>
                                            </div>
                                        </div>
                                        <small class="d-block mt-2" style="color: #666;">
                                            <i class="fas fa-info-circle"></i> Presiona una categoría para iniciar/terminar grabación. Ver clips en el tab lateral.
                                        </small>
                                    </div>
                                </div>
                            </div>

                        @else
                            {{-- ══════════════════════════════════════════════════════ --}}
                            {{-- ANALISTA: ORDEN 1 - Panel de Clips                   --}}
                            {{-- ══════════════════════════════════════════════════════ --}}
                            <div id="clipPanelWrapper" style="background: #0f0f0f;">
                                <button id="toggleClipPanel" class="btn btn-block text-left py-2 px-3" style="background: #252525; border: none; border-radius: 0; color: #fff; border-bottom: 1px solid #333;">
                                    <i class="fas fa-film mr-2" style="color: #00B7B5;"></i>
                                    <strong>Modo Análisis - Clips</strong>
                                    <span id="clipCount" class="badge ml-2" style="background: #00B7B5;">0</span>
                                    <i id="clipPanelArrow" class="fas fa-chevron-up float-right mt-1"></i>
                                </button>

                                <div id="clipPanel" style="display: block; background: #0f0f0f;">
                                    <div class="p-3" style="color: #ccc;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div id="clipButtonsContainer" class="d-flex flex-wrap" style="gap: 8px; flex: 1;">
                                                <div style="color: #888;">Cargando categorías...</div>
                                            </div>
                                            <div class="ml-3" style="white-space: nowrap;">
                                                <button type="button" class="btn btn-sm" style="background: #005461; color: #fff; border: none;" onclick="openCategoryModal()" title="Crear nueva categoría">
                                                    <i class="fas fa-plus"></i> Crear
                                                </button>
                                                <button type="button" class="btn btn-sm ml-1" style="background: #003d4a; color: #fff; border: none;" data-toggle="modal" data-target="#manageCategoriesModal" title="Gestionar categorías">
                                                    <i class="fas fa-cog"></i> Editar
                                                </button>
                                            </div>
                                        </div>
                                        <small class="d-block mt-2" style="color: #666;">
                                            <i class="fas fa-info-circle"></i> Presiona una categoría para iniciar/terminar grabación. Ver clips en el tab lateral.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            {{-- ══════════════════════════════════════════════════════ --}}
                            {{-- ANALISTA: ORDEN 2 - Timeline de Sincronización       --}}
                            {{-- ══════════════════════════════════════════════════════ --}}
                            <div id="clipTimelineWrapper">
                                <button id="toggleClipTimeline" class="btn btn-block text-left py-2 px-3" style="background: #1a1a1a; border: none; border-radius: 0; color: #fff; border-top: 1px solid #333;">
                                    <i class="fas fa-sliders-h mr-2" style="color: #ffc107;"></i>
                                    <strong>Sincronizar Línea de Tiempo</strong>
                                    <i id="clipTimelineArrow" class="fas fa-chevron-down float-right mt-1"></i>
                                </button>

                                <div id="clipTimelineContent" style="display: none; background: #0a0a0a; padding: 10px; max-height: 600px; overflow-y: auto;">
                                    <div class="timeline-control-bar" style="background: #1a1a1a; border: 1px solid #333; border-radius: 6px; padding: 8px 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                        <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 300px;">
                                            <label style="color: #aaa; font-size: 12px; margin: 0; white-space: nowrap;">
                                                <i class="fas fa-sync-alt" style="color: #ffc107;"></i> Offset:
                                            </label>
                                            <input type="range" id="timelineOffsetSlider" class="custom-range" min="-300" max="300" step="0.5" value="{{ $video->timeline_offset ?? 0 }}" style="flex: 1; cursor: pointer; max-width: 200px;">
                                            <span id="offsetDisplay" class="badge" style="background: #ffc107; color: #000; font-weight: bold; font-size: 11px; min-width: 45px; text-align: center;">{{ $video->timeline_offset ?? 0 }}s</span>
                                        </div>
                                        <div style="display: flex; gap: 6px;">
                                            <button id="applyOffsetBtn" class="btn btn-sm" style="background: #00B7B5; color: #fff; font-weight: 600; font-size: 11px; padding: 4px 12px; border: none;"><i class="fas fa-check"></i> Aplicar</button>
                                            <button id="resetOffsetBtn" class="btn btn-sm btn-secondary" style="font-size: 11px; padding: 4px 10px; border: none;" title="Resetear a 0s"><i class="fas fa-undo"></i></button>
                                        </div>
                                        <div id="offsetClipCount" style="color: #888; font-size: 11px; margin-left: auto; white-space: nowrap;"><i class="fas fa-film"></i> <span id="totalClipsCount">0</span> clips</div>
                                    </div>

                                    <div id="clipsTimelineLanes" style="background: #0f0f0f; border-radius: 6px; padding: 10px;">
                                        <div class="text-center py-4" style="color: #666;"><i class="fas fa-spinner fa-spin"></i> Cargando clips...</div>
                                    </div>

                                    <div id="timelineScale" class="d-flex justify-content-between mt-2 px-2" style="color: #555; font-size: 10px; margin-left: 120px;">
                                        <span>0:00</span><span id="scale25"></span><span id="scale50"></span><span id="scale75"></span><span id="scaleEnd"></span>
                                    </div>

                                    <div class="alert alert-dark py-2 mt-3 mb-0" style="background: #1a1a1a; border: 1px solid #333; font-size: 11px;">
                                        <i class="fas fa-lightbulb text-warning"></i> <strong>Cómo usar:</strong><br>
                                        • <strong>Ajusta el offset</strong> con el slider para sincronizar todos los clips<br>
                                        • <strong>Click en un clip</strong> (cuadrado de color) para reproducirlo<br>
                                        • <strong>Click en la barra</strong> para saltar a ese momento del video<br>
                                        • Los clips importados de XML son de solo lectura
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ══════════════════════════════════════════════════════ --}}
                        {{-- AMBOS ROLES: ORDEN 3 - Timeline de Comentarios       --}}
                        {{-- ══════════════════════════════════════════════════════ --}}
                        <div id="timelineWrapper">
                            <button id="toggleTimeline" class="btn btn-block text-left py-2 px-3" style="background: #1a1a1a; border: none; border-radius: 0; color: #fff; border-top: 1px solid #333;">
                                <i class="fas fa-comments mr-2" style="color: #00B7B5;"></i>
                                <strong>Timeline de Comentarios</strong>
                                <span id="commentCountBadge" class="badge ml-2" style="background: #00B7B5;">{{ $comments->count() }}</span>
                                <i id="timelineArrow" class="fas fa-chevron-up float-right mt-1"></i>
                            </button>

                            <div id="timelineContent" class="video-timeline p-3 position-relative" style="background: #1a1a1a; overflow: visible;">
                                {{-- Notificaciones arriba del timeline (casi en el video) --}}
                                <div id="commentNotifications" class="position-absolute" style="bottom: 100%; left: 10px; right: 10px; margin-bottom: 5px; pointer-events: none; z-index: 100;">
                                </div>
                                <div id="timelineMarkers" class="position-relative" style="height: 40px; background: #333; border-radius: 5px; margin: 10px 0; cursor: pointer;">
                                </div>
                                <div class="d-flex justify-content-between small" style="color: #888;">
                                    <span>00:00</span>
                                    <span id="videoDuration">{{ gmdate('H:i:s', $video->duration ?? 0) }}</span>
                                </div>
                            </div>
                        </div>

                    @else
                        {{-- ═══════════════════════════════════════════════════════════ --}}
                        {{-- JUGADOR: Solo Timeline de Comentarios (siempre visible)    --}}
                        {{-- ═══════════════════════════════════════════════════════════ --}}

                        <div class="video-timeline p-3 position-relative" style="background: #1a1a1a; overflow: visible;">
                            <h6 class="text-light mb-2"><i class="fas fa-clock"></i> Timeline de Comentarios</h6>
                            {{-- Notificaciones arriba del timeline (casi en el video) --}}
                            <div id="commentNotifications" class="position-absolute" style="bottom: 100%; left: 10px; right: 10px; margin-bottom: 5px; pointer-events: none; z-index: 100;">
                            </div>
                            <div id="timelineMarkers" class="position-relative" style="height: 40px; background: #333; border-radius: 5px; margin: 10px 0; cursor: pointer;">
                            </div>
                            <div class="d-flex justify-content-between small" style="color: #888;">
                                <span>00:00</span>
                                <span id="videoDuration">{{ gmdate('H:i:s', $video->duration ?? 0) }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Video Information -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle"></i> Información del Video</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Equipos:</strong></td>
                                    <td>
                                        {{ $video->analyzed_team_name }}
                                        @if($video->rival_name)
                                            vs {{ $video->rival_name }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Categoría:</strong></td>
                                    <td><span class="badge badge-rugby">{{ $video->category?->name ?? 'Sin categoría' }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha:</strong></td>
                                    <td>{{ $video->match_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Subido por:</strong></td>
                                    <td>
                                        {{ $video->uploader->name }}
                                        <span class="badge badge-sm badge-info">{{ ucfirst($video->uploader->role) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-align-left"></i> Descripción</h6>
                            <p class="text-muted">{{ $video->description ?? 'Sin descripción' }}</p>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-file"></i> {{ $video->file_name }} 
                                    ({{ number_format($video->file_size / 1024 / 1024, 2) }} MB)
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Multi-Camera Section --}}
            @include('videos.partials.multi-camera-section')

            {{-- Multi-Camera Player --}}
            @include('videos.partials.multi-camera-player')
        </div>

        @include('videos.partials.sidebar')
    </div>

    @include('videos.partials.modals.stats')
@endsection


@section('js')
<!-- Video Player Configuration -->
@php
    $currentOrgId = auth()->user()->currentOrganization()?->id;
    $orgUsers = \App\Models\User::select('id', 'name', 'role')
        ->whereHas('organizations', fn($q) => $q->where('organizations.id', $currentOrgId))
        ->get();
@endphp
<script>
window.VideoPlayer = {
    config: {
        videoId: {{ $video->id }},
        csrfToken: '{{ csrf_token() }}',
        comments: @json($comments),
        allUsers: @json($orgUsers),
        user: {
            id: {{ auth()->id() }},
            name: '{{ auth()->user()->name }}',
            role: '{{ auth()->user()->role }}',
            canViewStats: {{ in_array(auth()->user()->role, ['analista', 'entrenador', 'jugador']) ? 'true' : 'false' }},
            canCreateClips: {{ in_array(auth()->user()->role, ['analista', 'entrenador']) ? 'true' : 'false' }}
        },
        routes: {
            trackView: '{{ route("api.videos.track-view", $video) }}',
            updateDuration: '{{ route("api.videos.update-duration", $video) }}',
            markCompleted: '{{ route("api.videos.mark-completed", $video) }}',
            stats: '{{ route("api.videos.stats", $video) }}',
            clipCategories: '{{ route("api.clip-categories.index") }}',
            clips: '{{ route("api.clips.index", $video) }}',
            createClip: '{{ route("api.clips.quick-store", $video) }}',
            createCategory: '{{ route("admin.clip-categories.store") }}'
        }
    }
};
</script>

<!-- Video Player Scripts (Vite bundled) -->
@vite('resources/js/video-player/index.js')

@if(in_array(auth()->user()->role, ['analista', 'entrenador']))
<script>
// Toggle Timeline para analistas/entrenadores
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleTimeline');
    const content = document.getElementById('timelineContent');
    const arrow = document.getElementById('timelineArrow');

    if (toggleBtn && content) {
        toggleBtn.addEventListener('click', function() {
            const isVisible = content.style.display !== 'none';
            content.style.display = isVisible ? 'none' : 'block';
            if (arrow) {
                arrow.classList.toggle('fa-chevron-up', !isVisible);
                arrow.classList.toggle('fa-chevron-down', isVisible);
            }
        });
    }
});
</script>
@endif

<script>
// Auto-hide sidebar on video play (mejor experiencia de visualización)
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('rugbyVideo');
    if (video) {
        video.addEventListener('play', function() {
            document.body.classList.add('sidebar-collapse');
        });
    }
});
</script>

<script>
// Speed Control
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('rugbyVideo');
    const speedBtn = document.getElementById('speedControlBtn');
    const speedMenu = document.getElementById('speedMenu');
    const utilityControls = document.querySelector('.video-utility-controls');
    const currentSpeedDisplay = document.getElementById('currentSpeed');
    const speedOptions = document.querySelectorAll('.speed-option');

    if (!video || !speedBtn) return;

    // Toggle menu on button click
    speedBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        speedMenu.classList.toggle('show');
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!utilityControls.contains(e.target)) {
            speedMenu.classList.remove('show');
        }
    });

    // Handle speed option clicks
    speedOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.stopPropagation();
            const speed = parseFloat(this.dataset.speed);
            video.playbackRate = speed;

            // Update display
            currentSpeedDisplay.textContent = speed === 1 ? '1x' : speed + 'x';

            // Update active state
            speedOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');

            // Highlight button if not normal speed
            if (speed !== 1) {
                speedBtn.classList.add('speed-modified');
            } else {
                speedBtn.classList.remove('speed-modified');
            }

            // Close menu after selection
            speedMenu.classList.remove('show');
        });
    });

    // Keyboard shortcuts for speed
    document.addEventListener('keydown', function(e) {
        // Only when not typing in inputs
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

        // Shift + > (faster)
        if (e.key === '>' || (e.shiftKey && e.key === '.')) {
            e.preventDefault();
            changeSpeed(0.25);
        }
        // Shift + < (slower)
        if (e.key === '<' || (e.shiftKey && e.key === ',')) {
            e.preventDefault();
            changeSpeed(-0.25);
        }
    });

    function changeSpeed(delta) {
        let newSpeed = video.playbackRate + delta;
        newSpeed = Math.max(0.25, Math.min(2, newSpeed)); // Clamp between 0.25 and 2
        video.playbackRate = newSpeed;

        // Update display
        currentSpeedDisplay.textContent = newSpeed === 1 ? '1x' : newSpeed + 'x';

        // Update active state in menu
        speedOptions.forEach(opt => {
            opt.classList.toggle('active', parseFloat(opt.dataset.speed) === newSpeed);
        });

        // Highlight button
        speedBtn.classList.toggle('speed-modified', newSpeed !== 1);
    }
});
</script>

<script>
// Picture-in-Picture and Download Controls
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('rugbyVideo');
    const pipBtn = document.getElementById('pipBtn');
    const downloadBtn = document.getElementById('downloadBtn');

    if (!video) return;

    // ========== Picture-in-Picture ==========
    if (pipBtn) {
        // Check if PiP is supported
        if (!document.pictureInPictureEnabled) {
            pipBtn.disabled = true;
            pipBtn.title = 'Picture-in-Picture no soportado en este navegador';
        } else {
            pipBtn.addEventListener('click', async function() {
                try {
                    if (document.pictureInPictureElement) {
                        // Si ya está en PiP, salir
                        await document.exitPictureInPicture();
                    } else {
                        // Entrar en PiP
                        await video.requestPictureInPicture();
                    }
                } catch (error) {
                    console.error('Error con Picture-in-Picture:', error);
                    alert('No se pudo activar Picture-in-Picture. Asegúrate de que el video esté reproduciendo.');
                }
            });

            // Update button icon when entering/exiting PiP
            video.addEventListener('enterpictureinpicture', function() {
                pipBtn.innerHTML = '<i class="fas fa-compress"></i>';
                pipBtn.title = 'Salir de Picture-in-Picture';
            });

            video.addEventListener('leavepictureinpicture', function() {
                pipBtn.innerHTML = '<i class="fas fa-external-link-alt"></i>';
                pipBtn.title = 'Picture-in-Picture (Mini ventana)';
            });
        }
    }

    // ========== Download Video ==========
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            const videoSrc = video.querySelector('source').src;
            const videoTitle = '{{ $video->title }}';

            // Create download link
            const a = document.createElement('a');
            a.href = videoSrc;
            a.download = videoTitle.replace(/[^a-zA-Z0-9\s\-_]/g, '').replace(/\s+/g, '_') + '.mp4';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    }
});
</script>


<!-- Tribute.js CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.css">
<script src="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.min.js"></script>

<!-- GIF.js for clip export -->
<script src="https://cdn.jsdelivr.net/npm/gif.js@0.2.0/dist/gif.min.js"></script>

<!-- Video Player Styles -->
<link rel="stylesheet" href="{{ asset('css/video-player.css') }}">

<style>
/* Scrollbar visible para comentarios */
.comments-scroll-container::-webkit-scrollbar {
    width: 8px;
}
.comments-scroll-container::-webkit-scrollbar-track {
    background: #1a1a1a;
    border-radius: 4px;
}
.comments-scroll-container::-webkit-scrollbar-thumb {
    background: #00B7B5;
    border-radius: 4px;
}
.comments-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #009999;
}
/* Firefox */
.comments-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: #00B7B5 #1a1a1a;
}

/* Mejoras de usabilidad para clips en timeline */
.clip-block {
    min-width: 8px !important;
    margin-right: 2px;
}

.clip-block:hover {
    transform: scaleY(1.3);
    z-index: 100 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.8) !important;
    filter: brightness(1.2);
}
</style>

@include('videos.partials.modals.delete')

@include('videos.partials.modals.category')

@include('videos.partials.modals.manage-categories')

@include('videos.partials.modals.edit-clip')


{{-- Multi-Camera Sync Modal --}}
@include('videos.partials.sync-modal')

@endsection
