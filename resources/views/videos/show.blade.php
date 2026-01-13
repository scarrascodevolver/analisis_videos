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
                        <button id="toggleCommentsBtn" class="btn btn-sm btn-rugby-outline mr-2" title="Ocultar/Mostrar comentarios">
                            <i class="fas fa-eye-slash"></i> <span id="toggleCommentsText">Ocultar Comentarios</span>
                        </button>
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
                        <video id="rugbyVideo" controls style="width: 100%; height: 550px; display: block;"
                               preload="metadata"
                               crossorigin="anonymous"
                               x-webkit-airplay="allow">
                            <source src="{{ route('videos.stream', $video) }}" type="video/mp4">
                            Tu navegador no soporta la reproducción de video.
                            <p>Video no disponible. Archivo: {{ $video->file_path }}</p>
                        </video>

                        <!-- Canvas overlay para anotaciones -->
                        <canvas id="annotationCanvas"
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5;">
                        </canvas>

                        <!-- Toolbar de anotaciones (oculto por defecto) -->
                        <div id="annotationToolbar" class="annotation-toolbar" style="display: none;">
                            <div class="toolbar-container">
                                <div class="toolbar-title">
                                    <i class="fas fa-paint-brush"></i> Herramientas de Anotación
                                    <button id="closeAnnotationMode" class="btn btn-sm btn-outline-light ml-2">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="toolbar-buttons">
                                    <button id="annotationArrow" class="toolbar-btn active" data-tool="arrow">
                                        <i class="fas fa-arrow-right"></i> Flecha
                                    </button>
                                    <button id="annotationCircle" class="toolbar-btn" data-tool="circle">
                                        <i class="fas fa-circle"></i> Círculo
                                    </button>
                                    <button id="annotationLine" class="toolbar-btn" data-tool="line">
                                        <i class="fas fa-minus"></i> Línea
                                    </button>
                                    <button id="annotationText" class="toolbar-btn" data-tool="text">
                                        <i class="fas fa-font"></i> Texto
                                    </button>
                                    <div class="toolbar-separator"></div>
                                    <div class="color-picker-container">
                                        <label style="color: white; font-size: 11px;">Color:</label>
                                        <input type="color" id="annotationColor" value="#ff0000" style="width: 30px; height: 25px; border: none; border-radius: 3px;">
                                    </div>
                                    <div class="toolbar-separator"></div>
                                    <div class="duration-picker-container">
                                        <label style="color: white; font-size: 11px;">Duración:</label>
                                        <select id="annotationDuration" style="width: 60px; height: 25px; border: none; border-radius: 3px; background: white;">
                                            <option value="1">1s</option>
                                            <option value="2">2s</option>
                                            <option value="4" selected>4s</option>
                                            <option value="6">6s</option>
                                            <option value="8">8s</option>
                                            <option value="10">10s</option>
                                            <option value="permanent">Fijo</option>
                                        </select>
                                    </div>
                                    <div class="toolbar-separator"></div>
                                    <button id="saveAnnotation" class="toolbar-btn save-btn">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                    <button id="clearAnnotations" class="toolbar-btn clear-btn">
                                        <i class="fas fa-trash"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        
                        <!-- Delete Annotation Button (visible solo cuando hay anotación) -->
                        <button id="deleteAnnotationBtn" class="btn btn-sm btn-danger"
                                style="position: absolute; top: 10px; right: 10px; z-index: 20; display: none;"
                                title="Eliminar anotación visible">
                            <i class="fas fa-times-circle"></i> Eliminar Anotación
                        </button>

                        <!-- Mobile Fullscreen Button -->
                        <div class="video-controls-overlay" style="position: absolute; bottom: 60px; right: 10px; z-index: 10;">
                            <button id="mobileFullscreenBtn" class="btn btn-sm btn-dark mr-2" title="Pantalla completa" style="display: none;">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button id="addCommentBtn" class="btn btn-sm btn-rugby font-weight-bold mr-2">
                                <i class="fas fa-comment-plus"></i> Comentar aquí
                            </button>
                            @if(in_array(auth()->user()->role, ['analista', 'entrenador']))
                                <button id="toggleAnnotationMode" class="btn btn-sm btn-warning font-weight-bold">
                                    <i class="fas fa-paint-brush"></i> Anotar
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Timeline with Comments -->
                    <div class="video-timeline p-3 position-relative" style="background: #f8f9fa;">
                        <h6><i class="fas fa-clock"></i> Timeline de Comentarios</h6>
                        <div id="timelineMarkers" class="position-relative" style="height: 40px; background: #dee2e6; border-radius: 5px; margin: 10px 0; cursor: pointer;">
                            <!-- Comment markers will be added here via JavaScript -->
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>00:00</span>
                            <span id="videoDuration">{{ gmdate('H:i:s', $video->duration ?? 0) }}</span>
                        </div>
                        
                        <!-- Comment Notifications Area (positioned in video-timeline area) -->
                        <div id="commentNotifications" class="position-absolute" style="top: 60px; left: 20px; right: 20px; bottom: 10px; pointer-events: none; z-index: 25;">
                            <!-- Active comment notifications will appear here -->
                        </div>
                    </div>
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
                                    <td><span class="badge badge-rugby">{{ $video->category->name }}</span></td>
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
        </div>

        <!-- Comments Section -->
        <div class="col-lg-2" id="commentsSection">
            <!-- Add Comment Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments"></i>
                        Agregar Comentario
                    </h5>
                </div>
                <div class="card-body py-2 px-3">
                    <form id="commentForm" action="{{ route('video.comments.store', $video) }}" method="POST" data-video-id="{{ $video->id }}">
                        @csrf
                        <div class="form-group mb-2">
                            <label class="mb-1">Timestamp</label>
                            <div class="input-group">
                                <input type="number" id="timestamp_seconds" name="timestamp_seconds" 
                                       class="form-control" min="0" value="0" required>
                                <div class="input-group-append">
                                    <button type="button" id="useCurrentTime" class="btn btn-outline-secondary">
                                        <i class="fas fa-clock"></i> Actual
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted" id="timestampDisplay">00:00</small>
                        </div>

                        <div class="form-group mb-2">
                            <label class="mb-1">Comentario <small class="text-muted">(Usa @ para mencionar usuarios)</small></label>
                            <textarea name="comment" class="form-control" rows="3"
                                      placeholder="Describe lo que observas... (Escribe @ para mencionar)" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label class="mb-1">Categoría</label>
                                    <select name="category" class="form-control" required>
                                        <option value="tecnico">Técnico</option>
                                        <option value="tactico">Táctico</option>
                                        <option value="fisico">Físico</option>
                                        <option value="mental">Mental</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label class="mb-1">Prioridad</label>
                                    <select name="priority" class="form-control" required>
                                        <option value="media">Media</option>
                                        <option value="baja">Baja</option>
                                        <option value="alta">Alta</option>
                                        <option value="critica">Crítica</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-rugby btn-sm btn-block">
                            <i class="fas fa-comment"></i> Agregar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Comments List -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i>
                        Comentarios ({{ $comments->count() }})
                    </h5>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    @forelse($comments as $comment)
                        <div class="comment-item border-bottom p-2" data-timestamp="{{ $comment->timestamp_seconds }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <button class="btn btn-sm btn-primary timestamp-btn mr-2" 
                                                data-timestamp="{{ $comment->timestamp_seconds }}">
                                            {{ $comment->formatted_timestamp }}
                                        </button>
                                        <span class="badge badge-{{ 
                                            $comment->category === 'tecnico' ? 'info' : 
                                            ($comment->category === 'tactico' ? 'warning' : 
                                            ($comment->category === 'fisico' ? 'success' : 'purple')) 
                                        }}">
                                            {{ ucfirst($comment->category) }}
                                        </span>
                                        <span class="badge badge-{{ 
                                            $comment->priority === 'critica' ? 'danger' : 
                                            ($comment->priority === 'alta' ? 'warning' : 
                                            ($comment->priority === 'media' ? 'info' : 'secondary')) 
                                        }} ml-1">
                                            {{ ucfirst($comment->priority) }}
                                        </span>
                                    </div>
                                    <p class="mb-2">{{ $comment->comment }}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> {{ $comment->user->name }}
                                        <span class="badge badge-sm badge-{{ 
                                            $comment->user->role === 'analista' ? 'primary' : 
                                            ($comment->user->role === 'entrenador' ? 'success' : 'info') 
                                        }}">
                                            {{ ucfirst($comment->user->role) }}
                                        </span>
                                    </small>
                                    <small class="text-muted ml-2">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </small>

                                    <!-- Badges de menciones -->
                                    @if($comment->mentionedUsers && $comment->mentionedUsers->count() > 0)
                                        <div class="mt-2">
                                            <span class="badge badge-light border">
                                                <i class="fas fa-at text-primary"></i>
                                                Menciona a:
                                                @foreach($comment->mentionedUsers as $mentionedUser)
                                                    <span class="badge badge-{{
                                                        $mentionedUser->role === 'jugador' ? 'info' :
                                                        ($mentionedUser->role === 'entrenador' ? 'success' : 'primary')
                                                    }} ml-1">
                                                        {{ $mentionedUser->name }}
                                                    </span>
                                                @endforeach
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <button class="dropdown-item dropdown-item-sm reply-btn" data-comment-id="{{ $comment->id }}">
                                            <i class="fas fa-reply"></i> Responder
                                        </button>
                                        @if($comment->user_id === auth()->id())
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item dropdown-item-sm text-danger delete-comment-btn"
                                                    data-comment-id="{{ $comment->id }}">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Reply Form (Hidden by default) -->
                            <div class="reply-form mt-3" id="replyForm{{ $comment->id }}" style="display: none;">
                                <form class="reply-form-submit" data-comment-id="{{ $comment->id }}" data-video-id="{{ $video->id }}">
                                    @csrf
                                    <textarea class="form-control form-control-sm mb-2" name="reply_comment" rows="2"
                                              placeholder="Escribe tu respuesta..." required></textarea>
                                    <button class="btn btn-rugby btn-sm" type="submit">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </form>
                            </div>

                            <!-- Replies -->
                            @if($comment->replies->count() > 0)
                                <div class="replies ml-4 mt-3">
                                    @foreach($comment->replies as $reply)
                                        @include('videos.partials.reply', ['reply' => $reply, 'video' => $video])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>No hay comentarios aún.</p>
                            <p>Sé el primero en agregar un comentario de análisis.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Annotations List -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-pen"></i>
                        Anotaciones (<span id="annotationsCount">0</span>)
                    </h5>
                </div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;" id="annotationsList">
                    <div class="text-center p-3 text-muted" id="noAnnotationsMessage">
                        <i class="fas fa-pen-fancy fa-2x mb-2"></i>
                        <p>No hay anotaciones aún.</p>
                    </div>
                    <!-- Las anotaciones se cargarán aquí via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Visualizaciones -->
    @if(in_array(auth()->user()->role, ['analista', 'entrenador', 'jugador']))
    <div class="modal fade" id="statsModal" tabindex="-1" role="dialog" aria-labelledby="statsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--color-primary, #005461) 0%, var(--color-accent, #4B9DA9) 100%); color: white;">
                    <h5 class="modal-title" id="statsModalLabel">
                        <i class="fas fa-eye"></i> Visualizaciones del Video
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-eye"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Visualizaciones</span>
                                    <span class="info-box-number" id="modalTotalViews">{{ $video->view_count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Usuarios Únicos</span>
                                    <span class="info-box-number" id="modalUniqueViewers">{{ $video->unique_viewers }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-3"><i class="fas fa-list"></i> Detalle por Usuario</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm" id="statsTable">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th class="text-center">Vistas</th>
                                    <th>Última Visualización</th>
                                </tr>
                            </thead>
                            <tbody id="statsTableBody">
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando visualizaciones...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-rugby" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection


@section('js')
<!-- Video Player Configuration -->
<script>
window.VideoPlayer = {
    config: {
        videoId: {{ $video->id }},
        csrfToken: '{{ csrf_token() }}',
        comments: @json($comments),
        allUsers: @json(\App\Models\User::select('id', 'name', 'role')->get()),
        user: {
            id: {{ auth()->id() }},
            name: '{{ auth()->user()->name }}',
            role: '{{ auth()->user()->role }}',
            canViewStats: {{ in_array(auth()->user()->role, ['analista', 'entrenador', 'jugador']) ? 'true' : 'false' }}
        },
        routes: {
            trackView: '{{ route("api.videos.track-view", $video) }}',
            updateDuration: '{{ route("api.videos.update-duration", $video) }}',
            markCompleted: '{{ route("api.videos.mark-completed", $video) }}',
            stats: '{{ route("api.videos.stats", $video) }}'
        }
    }
};
</script>

<!-- Video Player Scripts -->
<script src="{{ asset('js/video-player.js') }}"></script>


<!-- Tribute.js CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.css">
<script src="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.min.js"></script>


<!-- Video Player Styles -->
<link rel="stylesheet" href="{{ asset('css/video-player.css') }}">

<!-- Modal de Confirmación para Eliminar Video -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                </div>
                <h5 class="text-center mb-3">¿Estás seguro de eliminar este video?</h5>
                <div class="alert alert-warning">
                    <strong>Video:</strong> {{ $video->title }}<br>
                    <strong>Archivo:</strong> {{ $video->file_name }}<br>
                    <strong>Tamaño:</strong> {{ number_format($video->file_size / 1048576, 2) }} MB
                </div>
                <p class="text-danger text-center">
                    <strong>⚠️ Esta acción no se puede deshacer.</strong><br>
                    Se eliminará el video, todos sus comentarios y asignaciones.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-rugby-outline" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <form method="POST" action="{{ route('videos.destroy', $video) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-rugby-dark">
                        <i class="fas fa-trash"></i> Eliminar Video
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
