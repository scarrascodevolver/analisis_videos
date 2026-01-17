<?php $__env->startSection('page_title', $video->title); ?>

<?php $__env->startSection('breadcrumbs'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('videos.index')); ?>"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('videos.index')); ?>">Videos</a></li>
    <li class="breadcrumb-item active"><?php echo e($video->title); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('main_content'); ?>
    <div class="row">
        <!-- Video Player Section -->
        <div class="col-lg-10" id="videoSection">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-play"></i>
                        <?php echo e($video->title); ?>

                        <br>
                        <small class="text-muted">
                            <i class="fas fa-eye"></i> <span id="viewCount"><?php echo e($video->view_count); ?></span> visualizaciones
                            • <i class="fas fa-users"></i> <span id="uniqueViewers"><?php echo e($video->unique_viewers); ?></span> usuarios
                        </small>
                    </h3>
                    <div class="card-tools">
                        <?php if(in_array(auth()->user()->role, ['analista', 'entrenador', 'jugador'])): ?>
                            <button id="viewStatsBtn" class="btn btn-sm btn-rugby-light mr-2" data-toggle="modal" data-target="#statsModal">
                                <i class="fas fa-eye"></i> Visualizaciones
                            </button>
                        <?php endif; ?>
                        <button id="toggleCommentsBtn" class="btn btn-sm btn-rugby-outline mr-2" title="Ocultar/Mostrar comentarios">
                            <i class="fas fa-eye-slash"></i> <span id="toggleCommentsText">Ocultar Comentarios</span>
                        </button>
                        <?php if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador' || auth()->id() === $video->uploaded_by): ?>
                            <a href="<?php echo e(route('videos.edit', $video)); ?>" class="btn btn-sm btn-rugby-light">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        <?php endif; ?>
                        <?php if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador'): ?>
                            <button type="button" class="btn btn-sm btn-rugby-dark" data-toggle="modal" data-target="#deleteModal">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Video Player -->
                    <div class="video-container" style="position: relative; background: #000; border-radius: 8px; overflow: hidden;">
                        <video id="rugbyVideo" controls style="width: 100%; height: 550px; display: block;"
                               preload="metadata"
                               crossorigin="anonymous"
                               x-webkit-airplay="allow">
                            <source src="<?php echo e(route('videos.stream', $video)); ?>" type="video/mp4">
                            Tu navegador no soporta la reproducción de video.
                            <p>Video no disponible. Archivo: <?php echo e($video->file_path); ?></p>
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
                                    
                                    <button id="annotationArrow" class="toolbar-btn active" data-tool="arrow" title="Flecha recta">
                                        <i class="fas fa-arrow-right"></i> Flecha
                                    </button>
                                    <button id="annotationCircle" class="toolbar-btn" data-tool="circle" title="Círculo">
                                        <i class="fas fa-circle"></i> Círculo
                                    </button>
                                    <button id="annotationRectangle" class="toolbar-btn" data-tool="rectangle" title="Rectángulo">
                                        <i class="fas fa-square"></i> Rect
                                    </button>
                                    <button id="annotationLine" class="toolbar-btn" data-tool="line" title="Línea recta">
                                        <i class="fas fa-minus"></i> Línea
                                    </button>
                                    <button id="annotationText" class="toolbar-btn" data-tool="text" title="Texto">
                                        <i class="fas fa-font"></i> Texto
                                    </button>
                                    <button id="annotationFreeDraw" class="toolbar-btn" data-tool="free_draw" title="Dibujo libre">
                                        <i class="fas fa-pencil-alt"></i> Libre
                                    </button>
                                    <div class="toolbar-separator"></div>
                                    
                                    <button id="annotationArea" class="toolbar-btn" data-tool="area" title="Marcar área (click puntos, doble-click o Enter para cerrar)">
                                        <i class="fas fa-draw-polygon"></i> Área
                                    </button>
                                    
                                    <button id="annotationSpotlight" class="toolbar-btn spotlight-btn" title="Spotlight (foco)">
                                        <i class="fas fa-bullseye"></i> Foco
                                    </button>
                                    <div class="toolbar-separator"></div>
                                    
                                    <div class="dropdown d-inline-block">
                                        <button class="toolbar-btn dropdown-toggle" type="button" data-toggle="dropdown" title="Símbolos">
                                            <i class="fas fa-icons"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-dark" style="background: #252525; border: 1px solid #444;">
                                            <button class="dropdown-item text-white symbol-btn" data-symbol="tackle">
                                                <i class="fas fa-bolt" style="color: #dc3545;"></i> Tackle / Impacto
                                            </button>
                                            <button class="dropdown-item text-white symbol-btn" data-symbol="ball">
                                                <i class="fas fa-football-ball" style="color: #8B4513;"></i> Balón
                                            </button>
                                            <button class="dropdown-item text-white symbol-btn" data-symbol="x">
                                                <i class="fas fa-times" style="color: #dc3545;"></i> Error / Fallo
                                            </button>
                                            <button class="dropdown-item text-white symbol-btn" data-symbol="check">
                                                <i class="fas fa-check" style="color: #28a745;"></i> Correcto / Bien
                                            </button>
                                        </div>
                                    </div>
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
                                    
                                    <button id="undoAnnotation" class="toolbar-btn" title="Deshacer (Ctrl+Z)" disabled>
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <button id="redoAnnotation" class="toolbar-btn" title="Rehacer (Ctrl+Y)" disabled>
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <button id="saveAnnotation" class="toolbar-btn save-btn">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                    <button id="clearAnnotations" class="toolbar-btn clear-btn">
                                        <i class="fas fa-trash"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tip flotante para herramienta de área -->
                        <div id="areaTip" class="area-tip" style="display: none;">
                            <i class="fas fa-lightbulb"></i>
                            Clic para agregar puntos. <strong>Doble clic</strong> o <strong>Enter</strong> para cerrar.
                        </div>


                        <!-- Delete Annotation Button (visible solo cuando hay anotación) -->
                        <button id="deleteAnnotationBtn" class="btn btn-sm btn-danger"
                                style="position: absolute; top: 10px; right: 10px; z-index: 20; display: none;"
                                title="Eliminar anotación visible">
                            <i class="fas fa-times-circle"></i> Eliminar Anotación
                        </button>

                        <!-- Speed Control Button -->
                        <div id="speedControlWrapper" class="speed-control-wrapper">
                            <button id="speedControlBtn" class="speed-control-btn" title="Velocidad de reproducción">
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
                        <div class="video-controls-overlay" style="position: absolute; bottom: 60px; right: 10px; z-index: 10;">
                            <button id="mobileFullscreenBtn" class="btn btn-sm btn-dark mr-2" title="Pantalla completa" style="display: none;">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button id="addCommentBtn" class="btn btn-sm btn-rugby font-weight-bold mr-2">
                                <i class="fas fa-comment-plus"></i> Comentar aquí
                            </button>
                            <?php if(in_array(auth()->user()->role, ['analista', 'entrenador'])): ?>
                                <button id="toggleAnnotationMode" class="btn btn-sm btn-rugby-outline font-weight-bold">
                                    <i class="fas fa-paint-brush"></i> Anotar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if(in_array(auth()->user()->role, ['analista', 'entrenador'])): ?>
                        
                        
                        

                        
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

                        
                        <div id="timelineWrapper">
                            <button id="toggleTimeline" class="btn btn-block text-left py-2 px-3" style="background: #1a1a1a; border: none; border-radius: 0; color: #fff; border-top: 1px solid #333;">
                                <i class="fas fa-comments mr-2" style="color: #00B7B5;"></i>
                                <strong>Timeline de Comentarios</strong>
                                <span id="commentCountBadge" class="badge ml-2" style="background: #00B7B5;"><?php echo e($comments->count()); ?></span>
                                <i id="timelineArrow" class="fas fa-chevron-up float-right mt-1"></i>
                            </button>

                            <div id="timelineContent" class="video-timeline p-3 position-relative" style="background: #1a1a1a; overflow: visible;">
                                
                                <div id="commentNotifications" class="position-absolute" style="bottom: 100%; left: 10px; right: 10px; margin-bottom: 5px; pointer-events: none; z-index: 100;">
                                </div>
                                <div id="timelineMarkers" class="position-relative" style="height: 40px; background: #333; border-radius: 5px; margin: 10px 0; cursor: pointer;">
                                </div>
                                <div class="d-flex justify-content-between small" style="color: #888;">
                                    <span>00:00</span>
                                    <span id="videoDuration"><?php echo e(gmdate('H:i:s', $video->duration ?? 0)); ?></span>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        
                        
                        

                        <div class="video-timeline p-3 position-relative" style="background: #1a1a1a; overflow: visible;">
                            <h6 class="text-light mb-2"><i class="fas fa-clock"></i> Timeline de Comentarios</h6>
                            
                            <div id="commentNotifications" class="position-absolute" style="bottom: 100%; left: 10px; right: 10px; margin-bottom: 5px; pointer-events: none; z-index: 100;">
                            </div>
                            <div id="timelineMarkers" class="position-relative" style="height: 40px; background: #333; border-radius: 5px; margin: 10px 0; cursor: pointer;">
                            </div>
                            <div class="d-flex justify-content-between small" style="color: #888;">
                                <span>00:00</span>
                                <span id="videoDuration"><?php echo e(gmdate('H:i:s', $video->duration ?? 0)); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
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
                                        <?php echo e($video->analyzed_team_name); ?>

                                        <?php if($video->rival_name): ?>
                                            vs <?php echo e($video->rival_name); ?>

                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Categoría:</strong></td>
                                    <td><span class="badge badge-rugby"><?php echo e($video->category->name); ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha:</strong></td>
                                    <td><?php echo e($video->match_date->format('d/m/Y')); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Subido por:</strong></td>
                                    <td>
                                        <?php echo e($video->uploader->name); ?>

                                        <span class="badge badge-sm badge-info"><?php echo e(ucfirst($video->uploader->role)); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-align-left"></i> Descripción</h6>
                            <p class="text-muted"><?php echo e($video->description ?? 'Sin descripción'); ?></p>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-file"></i> <?php echo e($video->file_name); ?> 
                                    (<?php echo e(number_format($video->file_size / 1024 / 1024, 2)); ?> MB)
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Section -->
        <div class="col-lg-2" id="sidebarSection">
            <?php if(in_array(auth()->user()->role, ['analista', 'entrenador'])): ?>
            <!-- Tabs para alternar entre Comentarios y Clips -->
            <!-- Analistas/Entrenadores: Clips primero -->
            <div class="sidebar-tabs mb-2" style="display: flex; border-radius: 8px; overflow: hidden; background: #1a1a1a;">
                <button type="button" class="sidebar-tab" data-tab="comments" style="flex: 1; padding: 10px; border: none; background: #252525; color: #888; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    <i class="fas fa-comments"></i> Comentarios
                </button>
                <button type="button" class="sidebar-tab active" data-tab="clips" style="flex: 1; padding: 10px; border: none; background: #005461; color: #fff; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    <i class="fas fa-film"></i> Clips <span id="sidebarClipCount" class="badge badge-light ml-1">0</span>
                </button>
            </div>
            <?php endif; ?>

            <!-- Tab Content: Comentarios -->
            <!-- Analistas/Entrenadores: oculto por defecto (Clips visible), Jugadores: visible -->
            <div id="tabComments" class="tab-content-sidebar" <?php if(in_array(auth()->user()->role, ['analista', 'entrenador'])): ?> style="display: none;" <?php endif; ?>>
            <!-- Add Comment Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments"></i>
                        Agregar Comentario
                    </h5>
                </div>
                <div class="card-body py-2 px-3">
                    <form id="commentForm" action="<?php echo e(route('video.comments.store', $video)); ?>" method="POST" data-video-id="<?php echo e($video->id); ?>">
                        <?php echo csrf_field(); ?>
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
                        Comentarios (<?php echo e($comments->count()); ?>)
                    </h5>
                </div>
                <div class="card-body p-0 comments-scroll-container" style="max-height: 400px; overflow-y: scroll; overflow-x: hidden;">
                    <?php $__empty_1 = true; $__currentLoopData = $comments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="comment-item border-bottom p-2" data-timestamp="<?php echo e($comment->timestamp_seconds); ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <button class="btn btn-sm btn-rugby-light timestamp-btn mr-2" 
                                                data-timestamp="<?php echo e($comment->timestamp_seconds); ?>">
                                            <?php echo e($comment->formatted_timestamp); ?>

                                        </button>
                                        <span class="badge badge-<?php echo e($comment->category === 'tecnico' ? 'info' : 
                                            ($comment->category === 'tactico' ? 'warning' : 
                                            ($comment->category === 'fisico' ? 'success' : 'purple'))); ?>">
                                            <?php echo e(ucfirst($comment->category)); ?>

                                        </span>
                                        <span class="badge badge-<?php echo e($comment->priority === 'critica' ? 'danger' : 
                                            ($comment->priority === 'alta' ? 'warning' : 
                                            ($comment->priority === 'media' ? 'info' : 'secondary'))); ?> ml-1">
                                            <?php echo e(ucfirst($comment->priority)); ?>

                                        </span>
                                    </div>
                                    <p class="mb-2"><?php echo e($comment->comment); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> <?php echo e($comment->user->name); ?>

                                        <span class="badge badge-sm badge-<?php echo e($comment->user->role === 'analista' ? 'primary' : 
                                            ($comment->user->role === 'entrenador' ? 'success' : 'info')); ?>">
                                            <?php echo e(ucfirst($comment->user->role)); ?>

                                        </span>
                                    </small>
                                    <small class="text-muted ml-2">
                                        <?php echo e($comment->created_at->diffForHumans()); ?>

                                    </small>

                                    <!-- Badges de menciones -->
                                    <?php if($comment->mentionedUsers && $comment->mentionedUsers->count() > 0): ?>
                                        <div class="mt-2">
                                            <span class="badge badge-light border">
                                                <i class="fas fa-at text-primary"></i>
                                                Menciona a:
                                                <?php $__currentLoopData = $comment->mentionedUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mentionedUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <span class="badge badge-<?php echo e($mentionedUser->role === 'jugador' ? 'info' :
                                                        ($mentionedUser->role === 'entrenador' ? 'success' : 'primary')); ?> ml-1">
                                                        <?php echo e($mentionedUser->name); ?>

                                                    </span>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <button class="dropdown-item dropdown-item-sm reply-btn" data-comment-id="<?php echo e($comment->id); ?>">
                                            <i class="fas fa-reply"></i> Responder
                                        </button>
                                        <?php if($comment->user_id === auth()->id()): ?>
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item dropdown-item-sm text-danger delete-comment-btn"
                                                    data-comment-id="<?php echo e($comment->id); ?>">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Reply Form (Hidden by default) -->
                            <div class="reply-form mt-3" id="replyForm<?php echo e($comment->id); ?>" style="display: none;">
                                <form class="reply-form-submit" data-comment-id="<?php echo e($comment->id); ?>" data-video-id="<?php echo e($video->id); ?>">
                                    <?php echo csrf_field(); ?>
                                    <textarea class="form-control form-control-sm mb-2" name="reply_comment" rows="2"
                                              placeholder="Escribe tu respuesta..." required></textarea>
                                    <button class="btn btn-rugby btn-sm" type="submit">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </form>
                            </div>

                            <!-- Replies -->
                            <?php if($comment->replies->count() > 0): ?>
                                <div class="replies ml-4 mt-3">
                                    <?php $__currentLoopData = $comment->replies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reply): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php echo $__env->make('videos.partials.reply', ['reply' => $reply, 'video' => $video], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>No hay comentarios aún.</p>
                            <p>Sé el primero en agregar un comentario de análisis.</p>
                        </div>
                    <?php endif; ?>
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
                <div class="card-body p-0 comments-scroll-container" style="max-height: 300px; overflow-y: auto;" id="annotationsList">
                    <div class="text-center p-3 text-muted" id="noAnnotationsMessage">
                        <i class="fas fa-pen-fancy fa-2x mb-2"></i>
                        <p>No hay anotaciones aún.</p>
                    </div>
                    <!-- Las anotaciones se cargarán aquí via JavaScript -->
                </div>
            </div>
            </div><!-- End tabComments -->

            <?php if(in_array(auth()->user()->role, ['analista', 'entrenador'])): ?>
            <!-- Tab Content: Clips -->
            <!-- Analistas/Entrenadores: visible por defecto -->
            <div id="tabClips" class="tab-content-sidebar" style="display: block;">
                <div class="card" style="background: #1a1a1a; border: 1px solid #333;">
                    <div class="card-header py-2" style="background: #252525; border-bottom: 1px solid #333;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0" style="color: #fff;">
                                <i class="fas fa-film" style="color: #00B7B5;"></i> Clips del Video
                            </h6>
                            <select id="sidebarClipFilter" class="form-control form-control-sm" style="width: auto; background: #333; color: #fff; border: none; font-size: 11px;">
                                <option value="">Todos</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                        <div id="sidebarClipsList" style="color: #ccc;">
                            <div class="text-center py-4" style="color: #666;">
                                <i class="fas fa-film fa-2x mb-2"></i>
                                <p class="mb-0">Sin clips aún</p>
                                <small>Usa la botonera bajo el video para crear clips</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-2" style="background: #1a1a1a; border: 1px solid #333;">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-around text-center" style="color: #888; font-size: 12px;">
                            <div>
                                <div id="sidebarTotalClips" style="font-size: 18px; font-weight: bold; color: #00B7B5;">0</div>
                                <div>Total</div>
                            </div>
                            <div>
                                <div id="sidebarHighlights" style="font-size: 18px; font-weight: bold; color: #ffc107;">0</div>
                                <div>Destacados</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Visualizaciones -->
    <?php if(in_array(auth()->user()->role, ['analista', 'entrenador', 'jugador'])): ?>
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
                                    <span class="info-box-number" id="modalTotalViews"><?php echo e($video->view_count); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Usuarios Únicos</span>
                                    <span class="info-box-number" id="modalUniqueViewers"><?php echo e($video->unique_viewers); ?></span>
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
    <?php endif; ?>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('js'); ?>
<!-- Video Player Configuration -->
<?php
    $currentOrgId = auth()->user()->currentOrganization()?->id;
    $orgUsers = \App\Models\User::select('id', 'name', 'role')
        ->whereHas('organizations', fn($q) => $q->where('organizations.id', $currentOrgId))
        ->get();
?>
<script>
window.VideoPlayer = {
    config: {
        videoId: <?php echo e($video->id); ?>,
        csrfToken: '<?php echo e(csrf_token()); ?>',
        comments: <?php echo json_encode($comments, 15, 512) ?>,
        allUsers: <?php echo json_encode($orgUsers, 15, 512) ?>,
        user: {
            id: <?php echo e(auth()->id()); ?>,
            name: '<?php echo e(auth()->user()->name); ?>',
            role: '<?php echo e(auth()->user()->role); ?>',
            canViewStats: <?php echo e(in_array(auth()->user()->role, ['analista', 'entrenador', 'jugador']) ? 'true' : 'false'); ?>,
            canCreateClips: <?php echo e(in_array(auth()->user()->role, ['analista', 'entrenador']) ? 'true' : 'false'); ?>

        },
        routes: {
            trackView: '<?php echo e(route("api.videos.track-view", $video)); ?>',
            updateDuration: '<?php echo e(route("api.videos.update-duration", $video)); ?>',
            markCompleted: '<?php echo e(route("api.videos.mark-completed", $video)); ?>',
            stats: '<?php echo e(route("api.videos.stats", $video)); ?>',
            clipCategories: '<?php echo e(route("api.clip-categories.index")); ?>',
            clips: '<?php echo e(route("api.clips.index", $video)); ?>',
            createClip: '<?php echo e(route("api.clips.quick-store", $video)); ?>'
        }
    }
};
</script>

<!-- Video Player Scripts (Vite bundled) -->
<?php echo app('Illuminate\Foundation\Vite')('resources/js/video-player/index.js'); ?>

<?php if(in_array(auth()->user()->role, ['analista', 'entrenador'])): ?>
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
<?php endif; ?>

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
    const speedWrapper = document.getElementById('speedControlWrapper');
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
        if (!speedWrapper.contains(e.target)) {
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

<?php if(in_array(auth()->user()->role, ['analista', 'entrenador'])): ?>
<script>
// Sidebar Tabs para Comentarios/Clips
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.sidebar-tab');
    const tabComments = document.getElementById('tabComments');
    const tabClips = document.getElementById('tabClips');
    const sidebarClipsList = document.getElementById('sidebarClipsList');
    const sidebarClipFilter = document.getElementById('sidebarClipFilter');
    const sidebarClipCount = document.getElementById('sidebarClipCount');
    const sidebarTotalClips = document.getElementById('sidebarTotalClips');
    const sidebarHighlights = document.getElementById('sidebarHighlights');

    let sidebarClipsData = [];
    let sidebarCategoriesData = [];

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
            // Load categories for filter
            if (sidebarCategoriesData.length === 0) {
                const catResponse = await fetch('<?php echo e(route("api.clip-categories.index")); ?>');
                sidebarCategoriesData = await catResponse.json();

                // Populate filter dropdown
                sidebarClipFilter.innerHTML = '<option value="">Todos</option>' +
                    sidebarCategoriesData.map(cat =>
                        `<option value="${cat.id}" style="color: ${cat.color};">${cat.name}</option>`
                    ).join('');
            }

            // Load clips
            const response = await fetch('<?php echo e(route("api.clips.index", $video)); ?>');
            sidebarClipsData = await response.json();

            renderSidebarClips();
        } catch (error) {
            console.error('Error loading sidebar clips:', error);
        }
    }

    // Render clips in sidebar
    function renderSidebarClips(filterCategoryId = null) {
        let clips = [...sidebarClipsData].sort((a, b) => b.id - a.id); // Newest first

        if (filterCategoryId) {
            clips = clips.filter(c => c.clip_category_id == filterCategoryId);
        }

        // Update counts
        sidebarClipCount.textContent = sidebarClipsData.length;
        sidebarTotalClips.textContent = sidebarClipsData.length;
        sidebarHighlights.textContent = sidebarClipsData.filter(c => c.is_highlight).length;

        if (clips.length === 0) {
            sidebarClipsList.innerHTML = `
                <div class="text-center py-4" style="color: #666;">
                    <i class="fas fa-film fa-2x mb-2"></i>
                    <p class="mb-0">Sin clips</p>
                    <small>${filterCategoryId ? 'Prueba otro filtro' : 'Usa la botonera para crear clips'}</small>
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
                                <button class="sidebar-export-gif-btn" data-clip-id="${clip.id}" data-start="${clip.start_time}" data-end="${clip.end_time}" data-title="${clip.title || 'clip'}"
                                        style="background: none; border: none; color: #17a2b8; padding: 2px 5px; cursor: pointer; font-size: 11px;"
                                        title="Exportar GIF">
                                    <i class="fas fa-file-image"></i>
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

        // Add click handlers to play clips
        document.querySelectorAll('.sidebar-clip-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Ignore if clicking delete button
                if (e.target.closest('.sidebar-delete-clip-btn')) return;

                const start = parseFloat(this.dataset.start);
                const end = parseFloat(this.dataset.end);
                const video = document.getElementById('rugbyVideo');
                if (video) {
                    video.currentTime = start;
                    video.play();

                    // Auto-pause at end
                    const checkEnd = setInterval(() => {
                        if (video.currentTime >= end) {
                            video.pause();
                            clearInterval(checkEnd);
                        }
                    }, 100);
                }
            });
        });

        // Add click handlers for delete buttons
        document.querySelectorAll('.sidebar-delete-clip-btn').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.stopPropagation();
                if (!confirm('¿Eliminar este clip?')) return;

                const clipId = this.dataset.clipId;
                try {
                    const response = await fetch(`/videos/<?php echo e($video->id); ?>/clips/${clipId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        sidebarClipsData = sidebarClipsData.filter(c => c.id != clipId);
                        renderSidebarClips(sidebarClipFilter.value || null);
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
    }

    // Filter change
    if (sidebarClipFilter) {
        sidebarClipFilter.addEventListener('change', function() {
            renderSidebarClips(this.value || null);
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
        sidebarClipsData = []; // Reset to force reload
        loadSidebarClips(); // Always reload, even if tab hidden (data will be ready when shown)
    };

    // Auto-cargar clips al inicio si el tab está visible (para analistas)
    if (tabClips && tabClips.style.display !== 'none') {
        loadSidebarClips();
    }
});
</script>
<?php endif; ?>

<!-- Tribute.js CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.css">
<script src="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.min.js"></script>

<!-- GIF.js for clip export -->
<script src="https://cdn.jsdelivr.net/npm/gif.js@0.2.0/dist/gif.min.js"></script>

<!-- Video Player Styles -->
<link rel="stylesheet" href="<?php echo e(asset('css/video-player.css')); ?>">

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
                    <strong>Video:</strong> <?php echo e($video->title); ?><br>
                    <strong>Archivo:</strong> <?php echo e($video->file_name); ?><br>
                    <strong>Tamaño:</strong> <?php echo e(number_format($video->file_size / 1048576, 2)); ?> MB
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
                <form method="POST" action="<?php echo e(route('videos.destroy', $video)); ?>" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-rugby-dark">
                        <i class="fas fa-trash"></i> Eliminar Video
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Categoría -->
<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background: #1a1a1a; color: #fff;">
            <div class="modal-header" style="border-bottom: 1px solid #333; background: #005461;">
                <h5 class="modal-title" id="categoryModalTitle"><i class="fas fa-plus"></i> Crear Categoría</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="catId" value="">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="catName" class="form-control" style="background: #252525; color: #fff; border-color: #333;" required maxlength="50" placeholder="Ej: Try, Scrum, Tackle...">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Color <span class="text-danger">*</span></label>
                            <input type="color" name="color" id="catColor" class="form-control" style="height: 40px; background: #252525; border-color: #333;" value="#005461" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tecla rápida</label>
                            <input type="text" name="hotkey" id="catHotkey" class="form-control" style="background: #252525; color: #fff; border-color: #333;" maxlength="1" placeholder="Ej: t, s, k...">
                            <small style="color: #888;">Una letra para activar con teclado</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Segundos antes (lead)</label>
                            <input type="number" name="lead_seconds" id="catLead" class="form-control" style="background: #252525; color: #fff; border-color: #333;" value="3" min="0" max="30">
                            <small style="color: #888;">Retrocede al iniciar</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Segundos después (lag)</label>
                            <input type="number" name="lag_seconds" id="catLag" class="form-control" style="background: #252525; color: #fff; border-color: #333;" value="3" min="0" max="30">
                            <small style="color: #888;">Avanza al terminar</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #333;">
                <button type="button" class="btn" style="background: #333; color: #fff;" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" style="background: #00B7B5; color: #fff;" id="saveCategoryBtn">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gestionar Categorías -->
<div class="modal fade" id="manageCategoriesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="background: #1a1a1a; color: #fff;">
            <div class="modal-header" style="border-bottom: 1px solid #333; background: #003d4a;">
                <h5 class="modal-title"><i class="fas fa-cog"></i> Gestionar Categorías</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                <div id="categoriesListModal">
                    <div class="text-center py-3" style="color: #888;">
                        <i class="fas fa-spinner fa-spin"></i> Cargando categorías...
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #333;">
                <button type="button" class="btn" style="background: #333; color: #fff;" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

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

    if (category) {
        // Modo editar
        modalTitle.innerHTML = '<i class="fas fa-edit"></i> Editar Categoría';
        catId.value = category.id;
        catName.value = category.name;
        catColor.value = category.color;
        catHotkey.value = category.hotkey || '';
        catLead.value = category.lead_seconds;
        catLag.value = category.lag_seconds;
    } else {
        // Modo crear
        modalTitle.innerHTML = '<i class="fas fa-plus"></i> Crear Categoría';
        catId.value = '';
        catName.value = '';
        catColor.value = '#005461';
        catHotkey.value = '';
        catLead.value = '3';
        catLag.value = '3';
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
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
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

            if (!name) {
                alert('El nombre es requerido');
                return;
            }

            const data = { name, color, hotkey, lead_seconds, lag_seconds };
            const isEdit = !!catId;
            const url = isEdit
                ? `/admin/clip-categories/${catId}`
                : '<?php echo e(route("admin.clip-categories.store")); ?>';
            const method = isEdit ? 'PUT' : 'POST';

            try {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
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
            const response = await fetch('<?php echo e(route("api.clip-categories.index")); ?>');
            const categories = await response.json();

            if (categories.length === 0) {
                categoriesListModal.innerHTML = '<div class="text-center py-3" style="color: #888;">No hay categorías creadas</div>';
                return;
            }

            categoriesListModal.innerHTML = categories.map(cat => `
                <div class="d-flex justify-content-between align-items-center p-2 mb-2" style="background: #252525; border-radius: 5px;" id="category-row-${cat.id}">
                    <div class="d-flex align-items-center">
                        <span class="mr-3" style="width: 30px; height: 30px; background: ${cat.color}; border-radius: 5px;"></span>
                        <div>
                            <strong>${cat.name}</strong>
                            ${cat.hotkey ? `<span class="badge ml-2" style="background: #333;">[${cat.hotkey.toUpperCase()}]</span>` : ''}
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
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\rugbyhub\resources\views/videos/show.blade.php ENDPATH**/ ?>