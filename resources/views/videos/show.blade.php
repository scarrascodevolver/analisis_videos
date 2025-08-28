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
        <div class="col-lg-8" id="videoSection">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-play"></i>
                        {{ $video->title }}
                    </h3>
                    <div class="card-tools">
                        <button id="toggleCommentsBtn" class="btn btn-sm btn-warning mr-2" title="Ocultar/Mostrar comentarios">
                            <i class="fas fa-eye-slash"></i> <span id="toggleCommentsText">Ocultar Comentarios</span>
                        </button>
                        @if(auth()->user()->role === 'analista' || auth()->id() === $video->uploaded_by)
                            <a href="{{ route('videos.edit', $video) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        @endif
                        <a href="{{ route('videos.analytics', $video) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-chart-line"></i> Analytics
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Video Player -->
                    <div class="video-container" style="position: relative; background: #000; border-radius: 8px; overflow: hidden;">
                        <video id="rugbyVideo" controls style="width: 100%; height: 500px; display: block;" preload="auto">
                            <source src="{{ asset('storage/' . $video->file_path) }}" type="{{ $video->mime_type }}">
                            <source src="{{ url('storage/' . $video->file_path) }}" type="{{ $video->mime_type }}">
                            Tu navegador no soporta la reproducción de video.
                            <p>Video no disponible. Archivo: {{ $video->file_path }}</p>
                        </video>
                        
                        <!-- Fullscreen Comment Notifications -->
                        <div id="fullscreenNotifications" class="position-absolute" style="bottom: 60px; left: 10px; right: 10px; top: 10px; pointer-events: none; z-index: 9999; display: none;">
                            <!-- Fullscreen notifications will appear here -->
                        </div>
                        
                        
                        <!-- Video Controls Enhancement -->
                        <div class="video-controls-overlay" style="position: absolute; bottom: 60px; left: 10px; right: 10px; z-index: 10;">
                            <div class="d-flex justify-content-between align-items-center text-white">
                                <div class="bg-dark px-3 py-2 rounded" style="background: rgba(0,0,0,0.8) !important;">
                                    <button id="playPauseBtn" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <span id="currentTime" class="ml-2 font-weight-bold">00:00</span>
                                    <span>/</span>
                                    <span id="duration" class="font-weight-bold">00:00</span>
                                </div>
                                <div>
                                    <button id="addCommentBtn" class="btn btn-sm btn-rugby font-weight-bold">
                                        <i class="fas fa-comment-plus"></i> Comentar aquí
                                    </button>
                                </div>
                            </div>
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
                                        {{ $video->analyzedTeam->name }}
                                        @if($video->rivalTeam)
                                            vs {{ $video->rivalTeam->name }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Categoría:</strong></td>
                                    <td><span class="badge badge-primary">{{ $video->category->name }}</span></td>
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
        <div class="col-lg-4" id="commentsSection">
            <!-- Add Comment Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments"></i>
                        Agregar Comentario
                    </h5>
                </div>
                <div class="card-body">
                    <form id="commentForm" action="{{ route('video.comments.store', $video) }}" method="POST" data-video-id="{{ $video->id }}">
                        @csrf
                        <div class="form-group">
                            <label>Timestamp</label>
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

                        <div class="form-group">
                            <label>Comentario</label>
                            <textarea name="comment" class="form-control" rows="3" 
                                      placeholder="Describe lo que observas..." required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Categoría</label>
                                    <select name="category" class="form-control" required>
                                        <option value="tecnico">Técnico</option>
                                        <option value="tactico">Táctico</option>
                                        <option value="fisico">Físico</option>
                                        <option value="mental">Mental</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Prioridad</label>
                                    <select name="priority" class="form-control" required>
                                        <option value="media">Media</option>
                                        <option value="baja">Baja</option>
                                        <option value="alta">Alta</option>
                                        <option value="critica">Crítica</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-rugby btn-block">
                            <i class="fas fa-comment"></i> Agregar Comentario
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
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    @forelse($comments as $comment)
                        <div class="comment-item border-bottom p-3" data-timestamp="{{ $comment->timestamp_seconds }}">
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
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item reply-btn" data-comment-id="{{ $comment->id }}">
                                            <i class="fas fa-reply"></i> Responder
                                        </button>
                                        @if(auth()->id() === $comment->user_id || auth()->user()->role === 'analista')
                                            <button class="dropdown-item mark-complete-btn" data-comment-id="{{ $comment->id }}">
                                                <i class="fas fa-check"></i> Marcar completado
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Reply Form (Hidden by default) -->
                            <div class="reply-form mt-3" id="replyForm{{ $comment->id }}" style="display: none;">
                                <form class="reply-form-submit" data-comment-id="{{ $comment->id }}" data-video-id="{{ $video->id }}">
                                    @csrf
                                    <div class="input-group">
                                        <textarea class="form-control" name="reply_comment" rows="2" 
                                                  placeholder="Escribe tu respuesta..." required></textarea>
                                        <div class="input-group-append">
                                            <button class="btn btn-rugby" type="submit">
                                                <i class="fas fa-reply"></i> Responder
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Replies -->
                            @if($comment->replies->count() > 0)
                                <div class="replies ml-4 mt-3">
                                    @foreach($comment->replies as $reply)
                                        <div class="reply border-left border-primary pl-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <p class="mb-1">{{ $reply->comment }}</p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user"></i> {{ $reply->user->name }}
                                                        <span class="badge badge-sm badge-{{ 
                                                            $reply->user->role === 'analista' ? 'primary' : 
                                                            ($reply->user->role === 'entrenador' ? 'success' : 'info') 
                                                        }}">
                                                            {{ ucfirst($reply->user->role) }}
                                                        </span>
                                                        - {{ $reply->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
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
        </div>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    const video = document.getElementById('rugbyVideo');
    const playPauseBtn = document.getElementById('playPauseBtn');
    const currentTimeSpan = document.getElementById('currentTime');
    const durationSpan = document.getElementById('duration');
    const timestampInput = document.getElementById('timestamp_seconds');
    const timestampDisplay = document.getElementById('timestampDisplay');
    
    // Force timeline creation after a small delay
    setTimeout(function() {
        if (video.duration && !isNaN(video.duration)) {
            createTimelineMarkers();
        }
    }, 500);

    // Video control functions
    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    // Video event listeners
    // Initialize video on page load
    if (video.readyState >= 2) {
        console.log('Video already loaded');
        durationSpan.textContent = formatTime(video.duration);
        createTimelineMarkers();
    }

    video.addEventListener('loadedmetadata', function() {
        console.log('Video metadata loaded, duration:', video.duration);
        if (video.duration && !isNaN(video.duration)) {
            durationSpan.textContent = formatTime(video.duration);
            createTimelineMarkers();
        }
    });

    video.addEventListener('error', function(e) {
        console.error('Video error:', e);
        
        // Show more detailed error info
        const errorDetails = video.error ? `Código: ${video.error.code}, Mensaje: ${video.error.message}` : 'Error desconocido';
        console.error('Detalles del error:', errorDetails);
        console.error('Video src:', video.src);
        
        alert('Error al cargar el video. Verifica que el archivo existe y el formato es compatible.');
    });

    video.addEventListener('timeupdate', function() {
        currentTimeSpan.textContent = formatTime(video.currentTime);
        updateProgressIndicator();
        
        // Auto-update timestamp input to current video time
        if (document.activeElement !== timestampInput) {
            timestampInput.value = Math.floor(video.currentTime);
            timestampDisplay.textContent = formatTime(Math.floor(video.currentTime));
        }
        
        // Check for comments at current time and show notifications
        checkAndShowCommentNotifications();
        checkAndShowFullscreenNotifications();
    });

    video.addEventListener('play', function() {
        playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
    });

    video.addEventListener('pause', function() {
        playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
    });

    // Play/Pause button
    playPauseBtn.addEventListener('click', function() {
        if (video.paused) {
            video.play();
        } else {
            video.pause();
        }
    });

    // Use current time button
    document.getElementById('useCurrentTime').addEventListener('click', function() {
        const currentSeconds = Math.floor(video.currentTime || 0);
        timestampInput.value = currentSeconds;
        timestampDisplay.textContent = formatTime(currentSeconds);
    });

    // Update timestamp display when input changes
    timestampInput.addEventListener('input', function() {
        const seconds = parseInt(this.value) || 0;
        timestampDisplay.textContent = formatTime(seconds);
    });

    // Timeline markers creation
    function createTimelineMarkers() {
        const timeline = document.getElementById('timelineMarkers');
        const videoDuration = video.duration;
        
        // Clear existing markers
        timeline.innerHTML = '';
        
        // Create clickable timeline bar (background)
        const timelineBar = document.createElement('div');
        timelineBar.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: #dee2e6;
            border-radius: 5px;
            cursor: pointer;
        `;
        timeline.appendChild(timelineBar);
        
        // Create progress bar
        const progressBar = document.createElement('div');
        progressBar.id = 'progressBar';
        progressBar.style.cssText = `
            position: absolute;
            top: 15px;
            left: 0;
            height: 10px;
            width: 0%;
            background: #007bff;
            border-radius: 5px;
            transition: width 0.1s ease;
        `;
        timeline.appendChild(progressBar);
        
        // Add click handler for timeline seeking
        timeline.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const percentage = clickX / rect.width;
            const newTime = percentage * videoDuration;
            video.currentTime = newTime;
        });
        
        // Create progress indicator (current position)
        const progressIndicator = document.createElement('div');
        progressIndicator.id = 'progressIndicator';
        progressIndicator.style.cssText = `
            position: absolute;
            top: 10px;
            left: 0%;
            width: 20px;
            height: 20px;
            background: #ff6b35;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
            z-index: 15;
            transform: translateX(-50%);
        `;
        timeline.appendChild(progressIndicator);
        
        // Add comment markers
        @if($comments->count() > 0)
        @foreach($comments as $comment)
            const marker{{ $loop->index }} = document.createElement('div');
            const position{{ $loop->index }} = ({{ $comment->timestamp_seconds }} / videoDuration) * 100;
            marker{{ $loop->index }}.style.cssText = `
                position: absolute;
                left: ${position{{ $loop->index }}}%;
                top: 5px;
                width: 4px;
                height: 30px;
                background: #28a745;
                cursor: pointer;
                border: 1px solid white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                z-index: 10;
                transform: translateX(-50%);
            `;
            marker{{ $loop->index }}.setAttribute('data-toggle', 'tooltip');
            marker{{ $loop->index }}.setAttribute('title', '{{ $comment->formatted_timestamp }} - {{ ucfirst($comment->category) }}');
            marker{{ $loop->index }}.addEventListener('click', function(e) {
                e.stopPropagation();
                video.currentTime = {{ $comment->timestamp_seconds }};
                video.play();
            });
            timeline.appendChild(marker{{ $loop->index }});
        @endforeach
        @endif
    }
    
    // Update progress indicator and bar
    function updateProgressIndicator() {
        const progressIndicator = document.getElementById('progressIndicator');
        const progressBar = document.getElementById('progressBar');
        
        if (video.duration) {
            const percentage = (video.currentTime / video.duration) * 100;
            
            if (progressIndicator) {
                progressIndicator.style.left = percentage + '%';
            }
            
            if (progressBar) {
                progressBar.style.width = percentage + '%';
            }
        }
    }

    // Comment timestamp buttons
    $('.timestamp-btn').on('click', function() {
        const timestamp = $(this).data('timestamp');
        video.currentTime = timestamp;
        video.play();
    });

    // Comment form submission
    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!timestampInput.value) {
            alert('Por favor selecciona un timestamp para el comentario');
            return false;
        }

        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload to show new comment
                } else {
                    alert('Error al agregar comentario');
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                let errorMsg = 'Error al agregar comentario';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                alert(errorMsg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-comment"></i> Agregar Comentario');
            }
        });
    });

    // Add comment at current position
    $('#addCommentBtn').on('click', function() {
        const currentSeconds = Math.floor(video.currentTime);
        timestampInput.value = currentSeconds;
        timestampDisplay.textContent = formatTime(currentSeconds);
        
        // Scroll to comment form
        $('html, body').animate({
            scrollTop: $('#commentForm').offset().top - 100
        }, 500);
    });

    // Reply functionality
    $('.reply-btn').on('click', function() {
        const commentId = $(this).data('comment-id');
        const replyForm = $('#replyForm' + commentId);
        
        // Toggle reply form visibility
        if (replyForm.is(':visible')) {
            replyForm.slideUp();
        } else {
            // Hide other reply forms
            $('.reply-form').slideUp();
            replyForm.slideDown();
            replyForm.find('textarea').focus();
        }
    });

    // Handle reply form submission
    $(document).on('submit', '.reply-form-submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const commentId = form.data('comment-id');
        const videoId = form.data('video-id');
        const replyText = form.find('textarea[name="reply_comment"]').val();
        const submitBtn = form.find('button[type="submit"]');
        
        if (!replyText.trim()) {
            alert('Por favor escribe una respuesta');
            return;
        }
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');
        
        $.ajax({
            url: `{{ route('video.comments.store', $video) }}`,
            method: 'POST',
            data: {
                comment: replyText,
                parent_id: commentId,
                timestamp_seconds: 0, // Replies don't need specific timestamps
                category: 'general',
                priority: 'media',
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload to show new reply
                } else {
                    alert('Error al enviar respuesta');
                }
            },
            error: function(xhr) {
                console.error('Error completo:', xhr);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                
                let errorMsg = 'Error al enviar respuesta';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('\\n');
                } else if (xhr.status === 422) {
                    errorMsg = 'Error de validación. Verifica los datos enviados.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Error interno del servidor. Revisa los logs.';
                }
                alert(errorMsg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-reply"></i> Responder');
            }
        });
    });

    // Mark complete functionality  
    $('.mark-complete-btn').on('click', function() {
        const commentId = $(this).data('comment-id');
        // AJAX call to mark comment as complete
        console.log('Mark complete:', commentId);
    });

    // Comment notifications system
    const commentsData = [
        @foreach($comments as $comment)
        {
            id: {{ $comment->id }},
            timestamp: {{ $comment->timestamp_seconds }},
            comment: @json($comment->comment),
            category: '{{ $comment->category }}',
            priority: '{{ $comment->priority }}',
            user: '{{ $comment->user->name }}',
            user_role: '{{ $comment->user->role }}'
        },
        @endforeach
    ];
    
    let activeNotifications = [];
    let lastCheckedTime = -1;
    
    function checkAndShowCommentNotifications() {
        const currentTime = Math.floor(video.currentTime);
        
        // Only check once per second
        if (currentTime === lastCheckedTime) return;
        lastCheckedTime = currentTime;
        
        // Hide any active notifications when moving to different timestamp
        hideAllNotifications();
        
        // Find comments at current time (exact match or ±1 second)
        const currentComments = commentsData.filter(comment => 
            Math.abs(comment.timestamp - currentTime) <= 1
        );
        
        // Show notifications for current comments
        currentComments.forEach(comment => {
            showCommentNotification(comment);
        });
    }
    
    function showCommentNotification(comment) {
        const notificationArea = document.getElementById('commentNotifications');
        const timeline = document.getElementById('timelineMarkers');
        
        if (!video.duration) return;
        
        // Calculate exact position where the green marker is
        const timelineRect = timeline.getBoundingClientRect();
        const timelineWidth = timelineRect.width;
        const position = (comment.timestamp / video.duration) * 100;
        const leftPosition = (position / 100) * timelineWidth;
        
        // Create notification element
        const notification = document.createElement('div');
        notification.id = `notification-${comment.id}`;
        notification.className = 'comment-notification';
        
        // Category colors
        const categoryColors = {
            'tecnico': 'info',
            'tactico': 'warning', 
            'fisico': 'success',
            'mental': 'purple',
            'general': 'secondary'
        };
        
        const priorityColors = {
            'critica': 'danger',
            'alta': 'warning',
            'media': 'info', 
            'baja': 'secondary'
        };
        
        // Calculate position relative to the notifications area, not timeline
        const notificationAreaWidth = notificationArea.offsetWidth;
        const relativePosition = (comment.timestamp / video.duration) * notificationAreaWidth;
        
        notification.style.cssText = `
            position: absolute;
            top: 10px;
            left: ${relativePosition}px;
            transform: translateX(-50%);
            max-width: 320px;
            min-width: 250px;
            background: rgba(255, 255, 255, 0.85);
            border: 2px solid rgba(40, 167, 69, 0.7);
            border-radius: 12px;
            padding: 12px 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: fadeIn 0.5s ease;
            pointer-events: auto;
            backdrop-filter: blur(3px);
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge badge-${categoryColors[comment.category]} mr-2" style="font-size: 10px;">
                            ${comment.category.charAt(0).toUpperCase() + comment.category.slice(1)}
                        </span>
                        <span class="badge badge-${priorityColors[comment.priority]}" style="font-size: 10px;">
                            ${comment.priority.charAt(0).toUpperCase() + comment.priority.slice(1)}
                        </span>
                    </div>
                    <p class="mb-2 text-dark" style="font-size: 13px; line-height: 1.3; font-weight: 500;">
                        ${comment.comment.length > 80 ? comment.comment.substring(0, 80) + '...' : comment.comment}
                    </p>
                    <small class="text-muted" style="font-size: 11px;">
                        <i class="fas fa-user"></i> ${comment.user} 
                        <span class="ml-2"><i class="fas fa-clock"></i> ${formatTime(comment.timestamp)}</span>
                    </small>
                </div>
                <button class="btn btn-sm btn-link text-muted p-1 ml-2" 
                        style="font-size: 12px; opacity: 0.8;" 
                        onclick="closeNotification(${comment.id})"
                        title="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        notificationArea.appendChild(notification);
    }
    
    function hideAllNotifications() {
        const notificationArea = document.getElementById('commentNotifications');
        while (notificationArea.firstChild) {
            notificationArea.removeChild(notificationArea.firstChild);
        }
    }
    
    // Fullscreen notifications system
    let isFullscreen = false;
    let fullscreenLastCheckedTime = -1;
    
    // Detect fullscreen changes (multiple browser support)
    function handleFullscreenChange() {
        isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || 
                         document.mozFullScreenElement || document.msFullscreenElement);
        const fullscreenArea = document.getElementById('fullscreenNotifications');
        if (fullscreenArea) {
            fullscreenArea.style.display = isFullscreen ? 'block' : 'none';
        }
        if (!isFullscreen) {
            hideAllFullscreenNotifications();
        }
        console.log('Fullscreen status:', isFullscreen);
    }
    
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);
    
    function checkAndShowFullscreenNotifications() {
        if (!isFullscreen) return;
        
        const currentTime = Math.floor(video.currentTime);
        
        // Only check once per second
        if (currentTime === fullscreenLastCheckedTime) return;
        fullscreenLastCheckedTime = currentTime;
        
        // Hide any active fullscreen notifications when moving to different timestamp
        hideAllFullscreenNotifications();
        
        // Find comments at current time (exact match or ±1 second)
        const currentComments = commentsData.filter(comment => 
            Math.abs(comment.timestamp - currentTime) <= 1
        );
        
        // Show fullscreen notifications for current comments
        currentComments.forEach(comment => {
            showFullscreenNotification(comment);
        });
    }
    
    function showFullscreenNotification(comment) {
        const fullscreenArea = document.getElementById('fullscreenNotifications');
        if (!fullscreenArea || !video.duration) return;
        
        console.log('Creando notificación fullscreen para:', comment.comment);
        
        // Calculate position across the full width
        const screenWidth = window.innerWidth || document.documentElement.clientWidth;
        const relativePosition = (comment.timestamp / video.duration) * (screenWidth * 0.8); // 80% of screen width
        
        // Create notification element
        const notification = document.createElement('div');
        notification.id = `fullscreen-notification-${comment.id}`;
        notification.className = 'fullscreen-comment-notification';
        
        // Category colors
        const categoryColors = {
            'tecnico': 'info',
            'tactico': 'warning', 
            'fisico': 'success',
            'mental': 'purple',
            'general': 'secondary'
        };
        
        const priorityColors = {
            'critica': 'danger',
            'alta': 'warning',
            'media': 'info', 
            'baja': 'secondary'
        };
        
        notification.style.cssText = `
            position: fixed !important;
            bottom: 100px !important;
            left: ${relativePosition}px !important;
            transform: translateX(-50%) !important;
            max-width: 500px !important;
            min-width: 350px !important;
            background: rgba(0, 0, 0, 0.9) !important;
            color: white !important;
            border: 3px solid #28a745 !important;
            border-radius: 20px !important;
            padding: 20px 25px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.6) !important;
            z-index: 99999 !important;
            animation: bounceIn 0.8s ease !important;
            pointer-events: auto !important;
            backdrop-filter: blur(8px) !important;
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge badge-${categoryColors[comment.category]} mr-2" style="font-size: 14px; padding: 6px 12px;">
                            ${comment.category.charAt(0).toUpperCase() + comment.category.slice(1)}
                        </span>
                        <span class="badge badge-${priorityColors[comment.priority]}" style="font-size: 14px; padding: 6px 12px;">
                            ${comment.priority.charAt(0).toUpperCase() + comment.priority.slice(1)}
                        </span>
                    </div>
                    <p class="mb-3 text-white" style="font-size: 18px; line-height: 1.4; font-weight: 600;">
                        ${comment.comment.length > 120 ? comment.comment.substring(0, 120) + '...' : comment.comment}
                    </p>
                    <small class="text-light" style="font-size: 15px; opacity: 0.9;">
                        <i class="fas fa-user"></i> ${comment.user} 
                        <span class="ml-3"><i class="fas fa-clock"></i> ${formatTime(comment.timestamp)}</span>
                    </small>
                </div>
                <button class="btn btn-sm btn-link text-light p-2 ml-3" 
                        style="font-size: 16px; opacity: 0.8;" 
                        onclick="closeFullscreenNotification(${comment.id})"
                        title="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Add to body instead of container for fullscreen
        document.body.appendChild(notification);
        console.log('Notificación agregada al body');
    }
    
    function hideAllFullscreenNotifications() {
        // Remove all fullscreen notifications from body
        const fullscreenNotifications = document.querySelectorAll('[id^="fullscreen-notification-"]');
        fullscreenNotifications.forEach(notification => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        });
        console.log('Eliminadas todas las notificaciones fullscreen');
    }
    
    // Close fullscreen notification function (global)
    window.closeFullscreenNotification = function(commentId) {
        const notification = document.getElementById(`fullscreen-notification-${commentId}`);
        if (notification && notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    };
    
    // Close notification function (global)
    window.closeNotification = function(commentId) {
        const notification = document.getElementById(`notification-${commentId}`);
        if (notification && notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    };

    // Toggle comments section
    $('#toggleCommentsBtn').on('click', function() {
        const commentsSection = $('#commentsSection');
        const videoSection = $('#videoSection');
        const toggleBtn = $('#toggleCommentsBtn');
        const toggleText = $('#toggleCommentsText');
        const toggleIcon = toggleBtn.find('i');
        
        if (commentsSection.is(':visible')) {
            // Hide comments and expand video
            commentsSection.hide();
            videoSection.removeClass('col-lg-8').addClass('col-lg-12');
            toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            toggleText.text('Mostrar Comentarios');
            toggleBtn.removeClass('btn-warning').addClass('btn-success');
            
            // Resize video player
            $('#rugbyVideo').css('height', '600px');
            
        } else {
            // Show comments and restore video size
            commentsSection.show();
            videoSection.removeClass('col-lg-12').addClass('col-lg-8');
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            toggleText.text('Ocultar Comentarios');
            toggleBtn.removeClass('btn-success').addClass('btn-warning');
            
            // Restore video player size
            $('#rugbyVideo').css('height', '500px');
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Video speed controls (optional enhancement)
    const speedControls = ['0.5x', '0.75x', '1x', '1.25x', '1.5x', '2x'];
    // Implementation for speed controls can be added here
});
</script>
@endsection