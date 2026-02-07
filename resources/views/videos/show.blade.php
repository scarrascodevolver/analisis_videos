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
                               x-webkit-airplay="allow"
                               data-video-title="{{ $video->title }}">
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
// Sidebar Tabs para Comentarios/Clips
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.sidebar-tab');
    const tabComments = document.getElementById('tabComments');
    const tabClips = document.getElementById('tabClips');
    const sidebarClipsList = document.getElementById('sidebarClipsList');
    const sidebarClipCount = document.getElementById('sidebarClipCount');
    const sidebarTotalClips = document.getElementById('sidebarTotalClips');
    const sidebarHighlights = document.getElementById('sidebarHighlights');

    // Exposed globally for visual timeline editor access
    window.sidebarClipsData = window.sidebarClipsData || [];
    window.sidebarCategoriesData = window.sidebarCategoriesData || [];

    // Tab switching
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;

            // Update button styles
            tabButtons.forEach(b => {
                b.style.background = '#252525';
                b.style.color = '#888';
                b.classList.remove('active');
            });
            this.style.background = '#005461';
            this.style.color = '#fff';
            this.classList.add('active');

            // Show/hide content
            if (tab === 'comments') {
                tabComments.style.display = 'block';
                tabClips.style.display = 'none';
            } else {
                tabComments.style.display = 'none';
                tabClips.style.display = 'block';
                loadSidebarClips();
            }
        });
    });

    // Load clips for sidebar
    async function loadSidebarClips() {
        try {
            // Load categories (needed for visual timeline)
            if (window.sidebarCategoriesData.length === 0) {
                const catResponse = await fetch('{{ route("api.clip-categories.index") }}');
                window.sidebarCategoriesData = await catResponse.json();
            }

            // Load clips
            const response = await fetch('{{ route("api.clips.index", $video) }}');
            window.sidebarClipsData = await response.json();

            renderSidebarClips();

            // Also update visual timeline if visible
            if (typeof window.renderVisualTimeline === 'function') {
                window.renderVisualTimeline();
            }
        } catch (error) {
            console.error('Error loading sidebar clips:', error);
        }
    }

    // Render clips in sidebar - USES VIRTUAL SCROLL for large lists
    function renderSidebarClips() {
        let clips = [...window.sidebarClipsData].sort((a, b) => a.start_time - b.start_time); // Chronological order

        // Update counts
        sidebarClipCount.textContent = window.sidebarClipsData.length;
        sidebarTotalClips.textContent = window.sidebarClipsData.length;
        sidebarHighlights.textContent = window.sidebarClipsData.filter(c => c.is_highlight).length;

        // Use clip-manager's renderClipsList() which has virtual scrolling
        if (typeof window.renderClipsList === 'function') {
            console.log('✅ Using clip-manager renderClipsList() with virtual scroll');
            window.renderClipsList(clips);
        } else {
            console.warn('⚠️ clip-manager not loaded, falling back to standard render');

            // Fallback: Standard render (only if clip-manager not loaded)
            if (clips.length === 0) {
                sidebarClipsList.innerHTML = `
                    <div class="text-center py-4" style="color: #666;">
                        <i class="fas fa-film fa-2x mb-2"></i>
                        <p class="mb-0">Sin clips</p>
                        <small>Usa la botonera para crear clips</small>
                    </div>`;
                return;
            }

            sidebarClipsList.innerHTML = clips.map(clip => `
                <div class="sidebar-clip-item"
                     data-clip-id="${clip.id}"
                     data-start="${clip.start_time}"
                     data-end="${clip.end_time}"
                     style="padding: 10px; border-bottom: 1px solid #333; cursor: pointer; transition: background 0.2s;"
                     onmouseover="this.style.background='#252525'"
                     onmouseout="this.style.background='transparent'">
                    <div class="d-flex align-items-center">
                        <span style="width: 8px; height: 30px; background: ${clip.category?.color || '#666'}; border-radius: 3px; margin-right: 10px;"></span>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="font-weight: 600; font-size: 12px; color: #fff;">
                                    ${clip.category?.name || 'Sin categoría'}
                                </span>
                                <div>
                                    ${clip.is_highlight ? '<i class="fas fa-star" style="color: #ffc107; font-size: 10px; margin-right: 5px;"></i>' : ''}
                                    <button class="sidebar-edit-clip-btn" data-clip-id="${clip.id}" data-start="${clip.start_time}" data-end="${clip.end_time}" data-title="${clip.title || ''}" data-notes="${clip.notes || ''}" data-category-id="${clip.clip_category_id}"
                                            style="background: none; border: none; color: #00B7B5; padding: 2px 5px; cursor: pointer; font-size: 11px;"
                                            title="Editar clip">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="sidebar-delete-clip-btn" data-clip-id="${clip.id}"
                                            style="background: none; border: none; color: #666; padding: 2px 5px; cursor: pointer; font-size: 11px;"
                                            title="Eliminar clip">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div style="font-size: 11px; color: #888;">
                                ${formatTime(clip.start_time)} - ${formatTime(clip.end_time)}
                                <span style="color: #666; margin-left: 5px;">(${(clip.end_time - clip.start_time).toFixed(1)}s)</span>
                            </div>
                            ${clip.title ? `<div style="font-size: 11px; color: #aaa; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${clip.title}</div>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');

            // Add click handlers to play clips (fallback only)
            document.querySelectorAll('.sidebar-clip-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    // Ignore if clicking delete button
                    if (e.target.closest('.sidebar-delete-clip-btn')) return;

                    const start = parseFloat(this.dataset.start);
                    const video = document.getElementById('rugbyVideo');
                    if (video) {
                        video.currentTime = start;

                        // Small delay to ensure seek completes before playing
                        setTimeout(() => {
                            const playPromise = video.play();
                            if (playPromise !== undefined) {
                                playPromise.catch(error => {
                                    console.warn('Play was prevented:', error);
                                });
                            }
                        }, 50);
                    }
                });
            });
        }

        // Add click handlers for delete buttons
        document.querySelectorAll('.sidebar-delete-clip-btn').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.stopPropagation();
                if (!confirm('¿Eliminar este clip?')) return;

                const clipId = this.dataset.clipId;
                try {
                    const response = await fetch(`/videos/{{ $video->id }}/clips/${clipId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        window.sidebarClipsData = window.sidebarClipsData.filter(c => c.id != clipId);
                        renderSidebarClips();
                        // Sync with clip-manager local array
                        if (typeof window.removeClipFromLocalArray === 'function') {
                            window.removeClipFromLocalArray(parseInt(clipId));
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });

        // Add click handlers for GIF export buttons
        document.querySelectorAll('.sidebar-export-gif-btn').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.stopPropagation();
                const startTime = parseFloat(this.dataset.start);
                const endTime = parseFloat(this.dataset.end);
                const title = this.dataset.title;
                await exportClipAsGif(startTime, endTime, title, this);
            });
        });

        // Add click handlers for edit buttons
        document.querySelectorAll('.sidebar-edit-clip-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                openEditClipModal(
                    this.dataset.clipId,
                    parseFloat(this.dataset.start),
                    parseFloat(this.dataset.end),
                    this.dataset.title,
                    this.dataset.notes,
                    this.dataset.categoryId
                );
            });
        });
    }

    // Helper function
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Export clip as GIF
    async function exportClipAsGif(startTime, endTime, title, buttonEl) {
        const video = document.getElementById('rugbyVideo');
        if (!video) {
            alert('No se encontró el video');
            return;
        }

        if (typeof GIF === 'undefined') {
            alert('Librería GIF no disponible. Recarga la página.');
            return;
        }

        // Show loading state
        const originalContent = buttonEl.innerHTML;
        buttonEl.disabled = true;
        buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        // Save current video state
        const wasPlaying = !video.paused;
        const originalTime = video.currentTime;
        video.pause();

        // Calculate dimensions (max 480px width)
        const maxWidth = 480;
        const scale = Math.min(1, maxWidth / video.videoWidth);
        const width = Math.floor(video.videoWidth * scale);
        const height = Math.floor(video.videoHeight * scale);

        // Create canvas
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        // GIF settings
        const fps = 10;
        const frameInterval = 1 / fps;
        const duration = endTime - startTime;
        const totalFrames = Math.min(Math.floor(duration * fps), 100);

        const gif = new GIF({
            workers: 2,
            quality: 10,
            width: width,
            height: height,
            workerScript: '/js/gif.worker.js'
        });

        let framesAdded = 0;

        try {
            for (let i = 0; i < totalFrames; i++) {
                const frameTime = startTime + (i * frameInterval);
                await seekToTime(video, frameTime);
                ctx.drawImage(video, 0, 0, width, height);
                gif.addFrame(ctx, { copy: true, delay: Math.floor(1000 / fps) });
                framesAdded++;

                if (i % 10 === 0) {
                    buttonEl.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
                }
            }

            buttonEl.innerHTML = '<i class="fas fa-cog fa-spin"></i>';

            gif.on('finished', function(blob) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const safeTitle = (title || 'clip').replace(/[^a-zA-Z0-9\s\-_]/g, '').replace(/\s+/g, '_').substring(0, 50);
                a.download = `${safeTitle}_${formatTime(Math.floor(startTime))}-${formatTime(Math.floor(endTime))}.gif`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                buttonEl.disabled = false;
                buttonEl.innerHTML = originalContent;

                video.currentTime = originalTime;
                if (wasPlaying) video.play();
            });

            gif.render();

        } catch (error) {
            console.error('Error exporting GIF:', error);
            buttonEl.disabled = false;
            buttonEl.innerHTML = originalContent;
            video.currentTime = originalTime;
            if (wasPlaying) video.play();
        }
    }

    function seekToTime(video, time) {
        return new Promise((resolve) => {
            const onSeeked = () => {
                video.removeEventListener('seeked', onSeeked);
                setTimeout(resolve, 50);
            };
            video.addEventListener('seeked', onSeeked);
            video.currentTime = time;
        });
    }

    // Expose function to refresh sidebar clips from outside
    window.refreshSidebarClips = function() {
        window.sidebarClipsData = []; // Reset to force reload
        loadSidebarClips(); // Always reload, even if tab hidden (data will be ready when shown)
    };

    // Auto-cargar clips al inicio si el tab está visible (para analistas)
    if (tabClips && tabClips.style.display !== 'none') {
        loadSidebarClips();
    }
});
</script>
@endif

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

<script>
// Abrir modal de categoría (crear o editar)
function openCategoryModal(category = null) {
    const modalTitle = document.getElementById('categoryModalTitle');
    const catId = document.getElementById('catId');
    const catName = document.getElementById('catName');
    const catColor = document.getElementById('catColor');
    const catHotkey = document.getElementById('catHotkey');
    const catLead = document.getElementById('catLead');
    const catLag = document.getElementById('catLag');

    // Get scope radio buttons
    const scopeOrg = document.getElementById('scopeOrg');
    const scopeUser = document.getElementById('scopeUser');
    const scopeVideo = document.getElementById('scopeVideo');

    if (category) {
        // Modo editar
        modalTitle.innerHTML = '<i class="fas fa-edit"></i> Editar Categoría';
        catId.value = category.id;
        catName.value = category.name;
        catColor.value = category.color;
        catHotkey.value = category.hotkey || '';
        catLead.value = category.lead_seconds;
        catLag.value = category.lag_seconds;

        // Set scope based on category (disable changing scope on edit)
        const scope = category.scope || 'organization';
        if (scopeOrg) scopeOrg.checked = (scope === 'organization');
        if (scopeUser) scopeUser.checked = (scope === 'user');
        if (scopeVideo) scopeVideo.checked = (scope === 'video');

        // Disable scope selection on edit (can't change scope after creation)
        document.querySelectorAll('input[name="scope"]').forEach(radio => {
            radio.disabled = true;
        });
    } else {
        // Modo crear
        modalTitle.innerHTML = '<i class="fas fa-plus"></i> Crear Categoría';
        catId.value = '';
        catName.value = '';
        catColor.value = '#005461';
        catHotkey.value = '';
        catLead.value = '3';
        catLag.value = '3';

        // Reset scope to default (organization) and enable selection
        if (scopeOrg) scopeOrg.checked = true;
        if (scopeUser) scopeUser.checked = false;
        if (scopeVideo) scopeVideo.checked = false;
        document.querySelectorAll('input[name="scope"]').forEach(radio => {
            radio.disabled = false;
        });
    }

    // Cerrar modal de gestión si está abierto
    $('#manageCategoriesModal').modal('hide');

    // Abrir modal de categoría
    $('#categoryModal').modal('show');
}

// Eliminar categoría
async function deleteCategory(categoryId, categoryName) {
    if (!confirm(`¿Eliminar la categoría "${categoryName}"?\n\nLos clips existentes de esta categoría NO se eliminarán.`)) {
        return;
    }

    try {
        const response = await fetch(`/admin/clip-categories/${categoryId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (response.ok && result.success) {
            // Remover la fila del modal
            const row = document.getElementById(`category-row-${categoryId}`);
            if (row) {
                row.remove();
            }

            // Recargar categorías en el player
            if (typeof window.loadCategories === 'function') {
                window.loadCategories();
            }
        } else {
            alert(result.message || 'Error al eliminar categoría');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar categoría');
    }
}

// Manejo de modales de categorías
document.addEventListener('DOMContentLoaded', function() {
    const categoryForm = document.getElementById('categoryForm');
    const saveBtn = document.getElementById('saveCategoryBtn');
    const categoriesListModal = document.getElementById('categoriesListModal');

    // Guardar categoría (crear o editar)
    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            const catId = document.getElementById('catId').value;
            const name = document.getElementById('catName').value.trim();
            const color = document.getElementById('catColor').value;
            const hotkey = document.getElementById('catHotkey').value.trim();
            const lead_seconds = parseInt(document.getElementById('catLead').value) || 3;
            const lag_seconds = parseInt(document.getElementById('catLag').value) || 3;
            const scope = document.querySelector('input[name="scope"]:checked')?.value || 'organization';
            const videoId = document.getElementById('catVideoId')?.value;

            if (!name) {
                alert('El nombre es requerido');
                return;
            }

            const data = { name, color, hotkey, lead_seconds, lag_seconds, scope };

            // Add video_id only if scope is 'video'
            if (scope === 'video' && videoId) {
                data.video_id = parseInt(videoId);
            }
            const isEdit = !!catId;
            const url = isEdit
                ? `/admin/clip-categories/${catId}`
                : '{{ route("admin.clip-categories.store") }}';
            const method = isEdit ? 'PUT' : 'POST';

            try {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    $('#categoryModal').modal('hide');
                    // Recargar categorías en el player
                    if (typeof window.loadCategories === 'function') {
                        window.loadCategories();
                    } else {
                        location.reload();
                    }
                } else {
                    alert(result.message || 'Error al guardar categoría');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar categoría');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Guardar';
            }
        });
    }

    // Cargar lista de categorías al abrir modal de gestión
    $('#manageCategoriesModal').on('show.bs.modal', async function() {
        try {
            const videoId = window.VideoPlayer?.config?.videoId || {{ $video->id }};
            const response = await fetch(`{{ route("api.clip-categories.index") }}?video_id=${videoId}`);
            const data = await response.json();
            const categories = data.categories || data;

            if (categories.length === 0) {
                categoriesListModal.innerHTML = '<div class="text-center py-3" style="color: #888;">No hay categorías creadas</div>';
                return;
            }

            const getScopeLabel = (scope) => {
                switch(scope) {
                    case 'organization': return '<i class="fas fa-building"></i> Plantilla';
                    case 'user': return '<i class="fas fa-user"></i> Personal';
                    case 'video': return '<i class="fas fa-video"></i> Este video';
                    default: return '';
                }
            };

            const getScopeBadgeStyle = (scope) => {
                switch(scope) {
                    case 'organization': return 'background: #005461; color: #fff;';
                    case 'user': return 'background: #8b5cf6; color: #fff;';
                    case 'video': return 'background: #f97316; color: #fff;';
                    default: return 'background: #333; color: #fff;';
                }
            };

            categoriesListModal.innerHTML = categories.map(cat => `
                <div class="d-flex justify-content-between align-items-center p-2 mb-2" style="background: #252525; border-radius: 5px;" id="category-row-${cat.id}">
                    <div class="d-flex align-items-center">
                        <span class="mr-3" style="width: 30px; height: 30px; background: ${cat.color}; border-radius: 5px;"></span>
                        <div>
                            <strong>${cat.name}</strong>
                            ${cat.hotkey ? `<span class="badge ml-2" style="background: #333;">[${cat.hotkey.toUpperCase()}]</span>` : ''}
                            <span class="badge ml-2" style="${getScopeBadgeStyle(cat.scope)}; font-size: 10px;">${getScopeLabel(cat.scope)}</span>
                            <br>
                            <small style="color: #888;">Lead: ${cat.lead_seconds}s | Lag: ${cat.lag_seconds}s</small>
                        </div>
                    </div>
                    <div>
                        <button type="button" onclick='openCategoryModal(${JSON.stringify(cat)})' class="btn btn-sm" style="background: #005461; color: #fff;" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" onclick='deleteCategory(${cat.id}, "${cat.name}")' class="btn btn-sm btn-danger ml-1" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error('Error:', error);
            categoriesListModal.innerHTML = '<div class="text-center py-3 text-danger">Error al cargar categorías</div>';
        }
    });
});

// ============================================
// EDIT CLIP MODAL FUNCTIONALITY
// ============================================
@if(in_array(auth()->user()->role, ['analista', 'entrenador']))
(function() {
    const video = document.getElementById('rugbyVideo');
    const editClipModal = document.getElementById('editClipModal');
    const editClipId = document.getElementById('editClipId');
    const editClipStart = document.getElementById('editClipStart');
    const editClipEnd = document.getElementById('editClipEnd');
    const editClipTitle = document.getElementById('editClipTitle');
    const editClipNotes = document.getElementById('editClipNotes');
    const editClipStartFormatted = document.getElementById('editClipStartFormatted');
    const editClipEndFormatted = document.getElementById('editClipEndFormatted');

    // Format seconds to MM:SS
    function formatTimeEdit(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    // Update formatted time displays
    function updateFormattedTimes() {
        const start = parseFloat(editClipStart.value) || 0;
        const end = parseFloat(editClipEnd.value) || 0;
        editClipStartFormatted.textContent = formatTimeEdit(start);
        editClipEndFormatted.textContent = formatTimeEdit(end);
    }

    // Open edit modal
    window.openEditClipModal = function(clipId, start, end, title, notes, categoryId) {
        editClipId.value = clipId;
        editClipStart.value = start.toFixed(1);
        editClipEnd.value = end.toFixed(1);
        editClipTitle.value = title || '';
        editClipNotes.value = notes || '';
        updateFormattedTimes();
        $('#editClipModal').modal('show');
    };

    // Input change handlers
    if (editClipStart) {
        editClipStart.addEventListener('input', updateFormattedTimes);
    }
    if (editClipEnd) {
        editClipEnd.addEventListener('input', updateFormattedTimes);
    }

    // Use current time buttons
    document.getElementById('useCurrentStartBtn')?.addEventListener('click', function() {
        if (video) {
            editClipStart.value = video.currentTime.toFixed(1);
            updateFormattedTimes();
        }
    });

    document.getElementById('useCurrentEndBtn')?.addEventListener('click', function() {
        if (video) {
            editClipEnd.value = video.currentTime.toFixed(1);
            updateFormattedTimes();
        }
    });

    // Adjust buttons (+/- 0.5s)
    document.getElementById('adjustClipMinus')?.addEventListener('click', function() {
        const start = parseFloat(editClipStart.value) || 0;
        editClipStart.value = Math.max(0, start - 0.5).toFixed(1);
        updateFormattedTimes();
    });

    document.getElementById('adjustClipPlus')?.addEventListener('click', function() {
        const end = parseFloat(editClipEnd.value) || 0;
        editClipEnd.value = (end + 0.5).toFixed(1);
        updateFormattedTimes();
    });

    // Preview button
    document.getElementById('previewClipBtn')?.addEventListener('click', function() {
        if (!video) return;

        const start = parseFloat(editClipStart.value) || 0;
        const end = parseFloat(editClipEnd.value) || start + 5;

        video.currentTime = start;
        video.play();

        // Auto-pause at end
        const checkEnd = setInterval(() => {
            if (video.currentTime >= end || video.paused) {
                video.pause();
                clearInterval(checkEnd);
            }
        }, 100);
    });

    // Save button
    document.getElementById('saveEditClipBtn')?.addEventListener('click', async function() {
        const clipId = editClipId.value;
        const startTime = parseFloat(editClipStart.value);
        const endTime = parseFloat(editClipEnd.value);
        const title = editClipTitle.value.trim();
        const notes = editClipNotes.value.trim();

        if (startTime >= endTime) {
            alert('El tiempo de inicio debe ser menor al tiempo de fin');
            return;
        }

        const saveBtn = this;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

        try {
            const response = await fetch(`/videos/{{ $video->id }}/clips/${clipId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    start_time: startTime,
                    end_time: endTime,
                    title: title,
                    notes: notes
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                $('#editClipModal').modal('hide');

                // Update local data and re-render
                if (window.sidebarClipsData && window.sidebarClipsData.length > 0) {
                    const clipIndex = window.sidebarClipsData.findIndex(c => c.id == clipId);
                    if (clipIndex !== -1) {
                        window.sidebarClipsData[clipIndex].start_time = startTime;
                        window.sidebarClipsData[clipIndex].end_time = endTime;
                        window.sidebarClipsData[clipIndex].title = title;
                        window.sidebarClipsData[clipIndex].notes = notes;
                    }
                    if (typeof renderSidebarClips === 'function') {
                        renderSidebarClips();
                    }
                }

                // Sync with clip-manager if available
                if (typeof window.updateClipInLocalArray === 'function') {
                    window.updateClipInLocalArray(parseInt(clipId), { start_time: startTime, end_time: endTime, title, notes });
                }
            } else {
                alert(result.message || 'Error al guardar clip');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al guardar clip');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        }
    });
})();
@endif

// ============================================
// VISUAL TIMELINE EDITOR - Carriles por Categoría + Offset Global
// ============================================
(function() {
    const video = document.getElementById('rugbyVideo');
    const toggleBtn = document.getElementById('toggleClipTimeline');
    const content = document.getElementById('clipTimelineContent');
    const arrow = document.getElementById('clipTimelineArrow');
    const lanesContainer = document.getElementById('clipsTimelineLanes');
    const offsetSlider = document.getElementById('timelineOffsetSlider');
    const offsetDisplay = document.getElementById('offsetDisplay');
    const applyOffsetBtn = document.getElementById('applyOffsetBtn');
    const resetOffsetBtn = document.getElementById('resetOffsetBtn');

    if (!toggleBtn || !content) return;

    let videoDuration = {{ $video->duration ?? 300 }};
    let currentOffset = {{ $video->timeline_offset ?? 0 }};
    let tempOffset = currentOffset;

    // Toggle panel
    toggleBtn.addEventListener('click', async function() {
        const isVisible = content.style.display !== 'none';
        content.style.display = isVisible ? 'none' : 'block';
        arrow.classList.toggle('fa-chevron-down', isVisible);
        arrow.classList.toggle('fa-chevron-up', !isVisible);

        if (!isVisible) {
            // Load clips if not loaded yet
            if (!window.sidebarClipsData || window.sidebarClipsData.length === 0) {
                lanesContainer.innerHTML = `
                    <div class="text-center py-4" style="color: #666;">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <p class="mb-0">Cargando clips...</p>
                    </div>`;

                // Trigger load and wait a bit
                if (typeof window.refreshSidebarClips === 'function') {
                    window.refreshSidebarClips();
                    setTimeout(() => renderTimelineLanes(), 500);
                } else {
                    renderTimelineLanes();
                }
            } else {
                renderTimelineLanes();
            }
        }
    });

    // Offset Slider - Update display in real time
    if (offsetSlider) {
        offsetSlider.addEventListener('input', function() {
            tempOffset = parseFloat(this.value);
            offsetDisplay.textContent = `${tempOffset > 0 ? '+' : ''}${tempOffset}s`;
        });
    }

    // Apply Offset Button
    if (applyOffsetBtn) {
        applyOffsetBtn.addEventListener('click', async function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aplicando...';

            try {
                const response = await fetch(`/api/videos/{{ $video->id }}/clips/timeline-offset`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        timeline_offset: tempOffset
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    currentOffset = tempOffset;
                    renderTimelineLanes(); // Re-render con nuevo offset

                    if (typeof toastr !== 'undefined') {
                        toastr.success('Offset aplicado exitosamente');
                    }
                } else {
                    alert(result.message || 'Error al aplicar offset');
                    offsetSlider.value = currentOffset;
                    tempOffset = currentOffset;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al aplicar offset');
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-check"></i> Aplicar';
            }
        });
    }

    // Reset Offset Button
    if (resetOffsetBtn) {
        resetOffsetBtn.addEventListener('click', function() {
            offsetSlider.value = 0;
            tempOffset = 0;
            offsetDisplay.textContent = '0s';
        });
    }

    // Update video duration when metadata loads
    if (video) {
        video.addEventListener('loadedmetadata', function() {
            videoDuration = video.duration || {{ $video->duration ?? 300 }};
        });

        // Update playheads when video time changes
        video.addEventListener('timeupdate', function() {
            if (content.style.display !== 'none') {
                updateAllPlayheads();
            }
        });
    }

    function formatTimeShort(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Update playheads in all lanes
    function updateAllPlayheads() {
        if (!video) return;
        const percent = (video.currentTime / videoDuration) * 100;
        document.querySelectorAll('.lane-playhead').forEach(ph => {
            ph.style.left = `${percent}%`;
        });
    }

    // Global render function
    window.renderVisualTimeline = function() {
        renderTimelineLanes();
    };

    // Render Timeline with Lanes by Category (compact layout)
    function renderTimelineLanes() {
        if (!lanesContainer || !window.sidebarClipsData) return;

        // Update clip count in control bar
        const clipCountEl = document.getElementById('totalClipsCount');
        if (clipCountEl) {
            clipCountEl.textContent = window.sidebarClipsData.length;
        }

        // Build category map
        const categoryMap = {};
        if (window.sidebarCategoriesData && window.sidebarCategoriesData.length > 0) {
            window.sidebarCategoriesData.forEach(cat => {
                categoryMap[cat.id] = { color: cat.color, name: cat.name, clips: [] };
            });
        }

        if (window.sidebarClipsData.length === 0) {
            lanesContainer.innerHTML = `
                <div class="text-center py-4" style="color: #666;">
                    <i class="fas fa-film fa-2x mb-2"></i>
                    <p class="mb-0">No hay clips para sincronizar</p>
                </div>`;
            return;
        }

        // Group clips by category
        window.sidebarClipsData.forEach(clip => {
            const catId = clip.clip_category_id;
            if (categoryMap[catId]) {
                categoryMap[catId].clips.push(clip);
            } else {
                // Unknown category - create temporary one
                if (!categoryMap['unknown']) {
                    categoryMap['unknown'] = { color: '#666', name: 'Sin categoría', clips: [] };
                }
                categoryMap['unknown'].clips.push(clip);
            }
        });

        // Update time scale
        const scale25 = document.getElementById('scale25');
        const scale50 = document.getElementById('scale50');
        const scale75 = document.getElementById('scale75');
        const scaleEnd = document.getElementById('scaleEnd');

        if (scale25) scale25.textContent = formatTimeShort(videoDuration * 0.25);
        if (scale50) scale50.textContent = formatTimeShort(videoDuration * 0.5);
        if (scale75) scale75.textContent = formatTimeShort(videoDuration * 0.75);
        if (scaleEnd) scaleEnd.textContent = formatTimeShort(videoDuration);

        // Render lanes with compact layout (category on left, timeline on right)
        let html = '';
        Object.keys(categoryMap).forEach(catId => {
            const category = categoryMap[catId];
            if (category.clips.length === 0) return; // Skip empty categories

            const clipsCount = category.clips.length;

            html += `
                <div class="timeline-lane" data-category-id="${catId}" style="display: flex; align-items: stretch; margin-bottom: 2px; background: #1a1a1a; border-radius: 4px; overflow: hidden; min-height: 32px;">
                    {{-- Category Label (left column) --}}
                    <div class="lane-label" style="width: 110px; min-width: 110px; background: #0f0f0f; border-right: 3px solid ${category.color}; padding: 6px 8px; display: flex; flex-direction: column; justify-content: center;">
                        <span style="color: ${category.color}; font-weight: bold; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${category.name}
                        </span>
                        <span style="color: #666; font-size: 9px; margin-top: 2px;">
                            ${clipsCount} clip${clipsCount > 1 ? 's' : ''}
                        </span>
                    </div>

                    {{-- Timeline Bar (right column) --}}
                    <div class="lane-bar" data-category-id="${catId}" style="flex: 1; position: relative; background: #252525; overflow: visible;">
                        {{-- Time markers --}}
                        <div style="position: absolute; top: 0; left: 25%; bottom: 0; width: 1px; background: rgba(255,255,255,0.06);"></div>
                        <div style="position: absolute; top: 0; left: 50%; bottom: 0; width: 1px; background: rgba(255,255,255,0.06);"></div>
                        <div style="position: absolute; top: 0; left: 75%; bottom: 0; width: 1px; background: rgba(255,255,255,0.06);"></div>

                        {{-- Playhead --}}
                        <div class="lane-playhead" style="position: absolute; top: 0; bottom: 0; width: 2px; background: #ff0000; z-index: 10; left: 0%;"></div>

                        {{-- Clips in this lane --}}
                        ${renderClipsInLane(category.clips, category.color)}
                    </div>
                </div>
            `;
        });

        lanesContainer.innerHTML = html;

        // Initialize interactions
        initLaneClicks();
    }

    // Render clips in a single lane (compact version)
    function renderClipsInLane(clips, categoryColor) {
        let html = '';

        clips.forEach(clip => {
            // Parse clip times as floats (may come as strings from API)
            const clipStart = parseFloat(clip.start_time) || 0;
            const clipEnd = parseFloat(clip.end_time) || 0;

            // Apply offset to clip times
            const adjustedStart = Math.max(0, clipStart + currentOffset);
            const adjustedEnd = clipEnd + currentOffset;

            const startPercent = (adjustedStart / videoDuration) * 100;
            const widthPercent = ((adjustedEnd - adjustedStart) / videoDuration) * 100;

            // Check if clip is out of bounds
            if (adjustedStart >= videoDuration || adjustedEnd <= 0) {
                return; // Skip clips outside video duration
            }

            const duration = (adjustedEnd - adjustedStart).toFixed(1);

            html += `
                <div class="clip-block"
                     data-clip-id="${clip.id}"
                     data-start="${clipStart}"
                     data-end="${clipEnd}"
                     data-category-id="${clip.clip_category_id}"
                     title="${clip.title || ''}\n${formatTimeShort(adjustedStart)} - ${formatTimeShort(adjustedEnd)} (${duration}s)"
                     style="position: absolute; top: 2px; bottom: 2px; left: ${Math.max(0, Math.min(startPercent, 100))}%; width: ${Math.max(widthPercent, 0.5)}%; background: ${categoryColor}; cursor: pointer; box-shadow: 0 1px 4px rgba(0,0,0,0.5); z-index: 5; transition: all 0.15s ease;">
                </div>
            `;
        });

        return html;
    }

    // Initialize lane bar clicks (seek to position in video or play clip)
    function initLaneClicks() {
        document.querySelectorAll('.lane-bar').forEach(bar => {
            bar.addEventListener('click', function(e) {
                // Check if clicking on a clip
                if (e.target.closest('.clip-block')) {
                    // Play clip from start - video continues normally
                    const clipBlock = e.target.closest('.clip-block');
                    const start = parseFloat(clipBlock.dataset.start) + currentOffset;

                    if (video) {
                        // Jump to clip start
                        video.currentTime = Math.max(0, start);

                        // Wait for seek to complete before starting playback
                        setTimeout(() => {
                            const playPromise = video.play();
                            if (playPromise !== undefined) {
                                playPromise.catch(error => {
                                    console.warn('Play was prevented:', error);
                                });
                            }
                        }, 50);
                    }
                    return;
                }

                // Seek to clicked position in timeline
                const rect = this.getBoundingClientRect();
                const percent = (e.clientX - rect.left) / rect.width;
                const seekTime = percent * videoDuration;

                if (video) {
                    video.currentTime = Math.max(0, Math.min(seekTime, videoDuration));
                }
            });
        });
    }


})();
</script>

{{-- Multi-Camera Sync Modal --}}
@include('videos.partials.sync-modal')

@endsection
