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
                        <video id="rugbyVideo" controls style="width: 100%; height: 500px; display: block;" preload="metadata">
                            <source src="{{ route('videos.stream', $video) }}" type="{{ $video->mime_type }}">
                            <source src="{{ asset('storage/' . $video->file_path) }}" type="{{ $video->mime_type }}">
                            Tu navegador no soporta la reproducción de video.
                            <p>Video no disponible. Archivo: {{ $video->file_path }}</p>
                        </video>
                        
                        <!-- Fullscreen Comment Notifications -->
                        <div id="fullscreenNotifications" class="position-absolute" style="bottom: 60px; left: 10px; right: 10px; top: 10px; pointer-events: none; z-index: 9999; display: none;">
                            <!-- Fullscreen notifications will appear here -->
                        </div>
                        
                        <!-- Add Comment Button Overlay -->
                        <div class="video-controls-overlay" style="position: absolute; bottom: 60px; right: 10px; z-index: 10;">
                            <button id="addCommentBtn" class="btn btn-sm btn-rugby font-weight-bold">
                                <i class="fas fa-comment-plus"></i> Comentar aquí
                            </button>
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
    const timestampInput = document.getElementById('timestamp_seconds');
    const timestampDisplay = document.getElementById('timestampDisplay');
    
    console.log('✅ JavaScript loaded - timeline funcional');
    
    // Basic time formatting function
    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    // Update timestamp input to current video time
    video.addEventListener('timeupdate', function() {
        if (document.activeElement !== timestampInput) {
            timestampInput.value = Math.floor(video.currentTime);
            timestampDisplay.textContent = formatTime(Math.floor(video.currentTime));
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

    // Comment timestamp buttons
    $('.timestamp-btn').on('click', function() {
        const timestamp = $(this).data('timestamp');
        video.currentTime = timestamp;
        if (video.paused) {
            video.play();
        }
    });

    // Toggle comments section
    $('#toggleCommentsBtn').on('click', function() {
        const commentsSection = $('#commentsSection');
        const videoSection = $('#videoSection');
        const toggleBtn = $('#toggleCommentsBtn');
        const toggleText = $('#toggleCommentsText');
        const toggleIcon = toggleBtn.find('i');
        
        if (commentsSection.is(':visible')) {
            commentsSection.hide();
            videoSection.removeClass('col-lg-8').addClass('col-lg-12');
            toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            toggleText.text('Mostrar Comentarios');
            toggleBtn.removeClass('btn-warning').addClass('btn-success');
            $('#rugbyVideo').css('height', '600px');
        } else {
            commentsSection.show();
            videoSection.removeClass('col-lg-12').addClass('col-lg-8');
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            toggleText.text('Ocultar Comentarios');
            toggleBtn.removeClass('btn-success').addClass('btn-warning');
            $('#rugbyVideo').css('height', '500px');
        }
    });

    // Timeline de comentarios
    function createTimelineMarkers() {
        const timeline = document.getElementById('timelineMarkers');
        const videoDuration = video.duration;
        
        if (!videoDuration || videoDuration === 0) {
            console.log('⚠️ No se puede crear timeline - duración no disponible');
            return;
        }

        console.log('🔧 Creando timeline con duración:', videoDuration);
        
        // Clear existing content
        timeline.innerHTML = '';
        
        // Create progress bar
        const progressContainer = document.createElement('div');
        progressContainer.style.cssText = `
            position: relative;
            width: 100%;
            height: 100%;
            background: #dee2e6;
            border-radius: 5px;
            cursor: pointer;
        `;
        
        // Progress bar
        const progressBar = document.createElement('div');
        progressBar.id = 'progressBar';
        progressBar.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: #1e4d2b;
            border-radius: 5px;
            transition: width 0.1s ease;
        `;
        
        // Progress indicator
        const progressIndicator = document.createElement('div');
        progressIndicator.id = 'progressIndicator';
        progressIndicator.style.cssText = `
            position: absolute;
            top: -5px;
            left: 0%;
            width: 4px;
            height: 50px;
            background: #1e4d2b;
            border-radius: 2px;
            transition: left 0.1s ease;
            transform: translateX(-50%);
        `;
        
        progressContainer.appendChild(progressBar);
        progressContainer.appendChild(progressIndicator);
        
        // Add comment markers
        const comments = @json($comments);
        comments.forEach(comment => {
            const position = (comment.timestamp_seconds / videoDuration) * 100;
            
            const marker = document.createElement('div');
            marker.className = 'comment-marker';
            marker.setAttribute('data-timestamp', comment.timestamp_seconds);
            marker.setAttribute('data-comment', comment.comment);
            marker.style.cssText = `
                position: absolute;
                top: -5px;
                left: ${position}%;
                width: 8px;
                height: 50px;
                background: #28a745;
                border: 2px solid #fff;
                border-radius: 4px;
                cursor: pointer;
                transform: translateX(-50%);
                z-index: 10;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            `;
            
            // Tooltip on hover
            marker.title = `${formatTime(comment.timestamp_seconds)}: ${comment.comment.substring(0, 50)}...`;
            
            // Click to seek
            marker.addEventListener('click', function(e) {
                e.stopPropagation();
                video.currentTime = comment.timestamp_seconds;
                if (video.paused) {
                    video.play();
                }
            });
            
            progressContainer.appendChild(marker);
        });
        
        // Timeline click to seek
        progressContainer.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const percentage = clickX / rect.width;
            const newTime = percentage * videoDuration;
            
            video.currentTime = newTime;
            console.log('🎯 Timeline click seek to:', formatTime(newTime));
        });
        
        timeline.appendChild(progressContainer);
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

    // Initialize timeline when video metadata loads
    video.addEventListener('loadedmetadata', function() {
        console.log('📹 Video metadata loaded, duration:', video.duration);
        if (video.duration && !isNaN(video.duration)) {
            createTimelineMarkers();
        }
    });

    // Update timeline progress
    video.addEventListener('timeupdate', function() {
        updateProgressIndicator();
        checkAndShowCommentNotifications();
    });

    // Force timeline creation if video is already loaded
    if (video.readyState >= 2) {
        console.log('📹 Video already loaded');
        createTimelineMarkers();
    }
    
    // Also try after a delay
    setTimeout(function() {
        if (video.duration && !isNaN(video.duration) && !document.getElementById('progressBar')) {
            console.log('⏰ Creating timeline after delay');
            createTimelineMarkers();
        }
    }, 1000);

    // Comment notifications system
    const commentsData = @json($comments);
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
            Math.abs(comment.timestamp_seconds - currentTime) <= 1
        );
        
        // Show notifications for current comments
        currentComments.forEach(comment => {
            showCommentNotification(comment);
        });
    }
    
    function showCommentNotification(comment) {
        const notificationArea = document.getElementById('commentNotifications');
        
        if (!video.duration) return;
        
        // Calculate position relative to the notifications area
        const notificationAreaWidth = notificationArea.offsetWidth;
        const relativePosition = (comment.timestamp_seconds / video.duration) * notificationAreaWidth;
        
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
        
        notification.style.cssText = `
            position: absolute;
            top: 10px;
            left: ${relativePosition}px;
            transform: translateX(-50%);
            max-width: 320px;
            min-width: 250px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid #28a745;
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
                        <span class="badge badge-${categoryColors[comment.category] || 'secondary'} mr-2" style="font-size: 10px;">
                            ${comment.category.charAt(0).toUpperCase() + comment.category.slice(1)}
                        </span>
                        <span class="badge badge-${priorityColors[comment.priority] || 'secondary'}" style="font-size: 10px;">
                            ${comment.priority.charAt(0).toUpperCase() + comment.priority.slice(1)}
                        </span>
                    </div>
                    <p class="mb-2 text-dark" style="font-size: 13px; line-height: 1.3; font-weight: 500;">
                        ${comment.comment.length > 80 ? comment.comment.substring(0, 80) + '...' : comment.comment}
                    </p>
                    <small class="text-muted" style="font-size: 11px;">
                        <i class="fas fa-user"></i> ${comment.user.name} 
                        <span class="ml-2"><i class="fas fa-clock"></i> ${formatTime(comment.timestamp_seconds)}</span>
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
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    }
    
    function hideAllNotifications() {
        const notificationArea = document.getElementById('commentNotifications');
        while (notificationArea.firstChild) {
            notificationArea.removeChild(notificationArea.firstChild);
        }
    }
    
    // Close notification function (global)
    window.closeNotification = function(commentId) {
        const notification = document.getElementById(`notification-${commentId}`);
        if (notification && notification.parentNode) {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    };
});
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateX(-50%) translateY(-10px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
}

.comment-notification {
    transition: opacity 0.3s ease;
}

/* Badge colors for mental category */
.badge-purple {
    background-color: #6f42c1;
    color: white;
}

/* Rugby badge */
.badge-rugby {
    background: #1e4d2b;
    color: white;
    font-size: 0.875em;
    font-weight: 500;
}

/* Rugby button variations */
.btn-rugby-light {
    background: #28a745;
    border: none;
    color: white;
    border-radius: 6px;
    font-weight: 500;
}

.btn-rugby-light:hover {
    background: #218838;
    color: white;
}

.btn-rugby-dark {
    background: #0d2818;
    border: none;
    color: white;
    border-radius: 6px;
    font-weight: 500;
}

.btn-rugby-dark:hover {
    background: #1a4028;
    color: white;
}

.btn-rugby-outline {
    background: transparent;
    border: 2px solid #1e4d2b;
    color: #1e4d2b;
    border-radius: 6px;
    font-weight: 500;
}

.btn-rugby-outline:hover {
    background: #1e4d2b;
    border-color: #1e4d2b;
    color: white;
}
</style>

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
