/**
 * Video Player - Annotations Module
 * Handles drawing annotations on video using Fabric.js
 */

import { formatTime, getVideo, getConfig } from './utils.js';

// Module state
let annotationMode = false;
let fabricCanvas = null;
let currentTool = 'arrow';
let isDrawing = false;
let currentAnnotation = null;
let savedAnnotations = [];
let currentDisplayedAnnotations = [];
let hasTemporaryDrawing = false;
let startX, startY;

/**
 * Initialize annotations system
 */
export function initAnnotations() {
    // Check if Fabric.js is available
    if (typeof fabric === 'undefined') {
        console.warn('Fabric.js not loaded, annotations disabled');
        return;
    }

    const video = getVideo();
    if (!video) return;

    // Initialize on document ready
    setTimeout(() => {
        const canvas = document.getElementById('annotationCanvas');
        if (canvas) {
            initAnnotationSystem();
            setupCanvasEvents();
        }
    }, 500);

    // Setup UI event handlers
    setupToolbarEvents();
    setupDeleteEvents();

    // Listen for video timeupdate to show/hide annotations
    video.addEventListener('timeupdate', checkAndShowAnnotations);
    video.addEventListener('seeked', checkAndShowAnnotations);

    // Expose functions globally for compatibility
    window.savedAnnotations = savedAnnotations;
    window.currentDisplayedAnnotations = currentDisplayedAnnotations;
    window.checkAndShowAnnotations = checkAndShowAnnotations;
    window.displayAnnotation = displayAnnotation;
    window.clearDisplayedAnnotation = clearDisplayedAnnotation;
}

/**
 * Initialize Fabric.js canvas
 */
function initAnnotationSystem() {
    const canvas = document.getElementById('annotationCanvas');
    const video = getVideo();
    if (!canvas || !video) return;

    const videoContainer = video.parentElement;

    function resizeCanvas() {
        canvas.width = video.offsetWidth;
        canvas.height = video.offsetHeight;
        canvas.style.width = video.offsetWidth + 'px';
        canvas.style.height = video.offsetHeight + 'px';

        if (fabricCanvas) {
            fabricCanvas.setDimensions({
                width: video.offsetWidth,
                height: video.offsetHeight
            });

            const canvasContainer = document.querySelector('.canvas-container');
            if (canvasContainer && canvasContainer.parentElement !== videoContainer) {
                videoContainer.appendChild(canvasContainer);
                canvasContainer.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    z-index: 5;
                `;
            }
        }
    }

    // Initialize Fabric.js canvas
    fabricCanvas = new fabric.Canvas('annotationCanvas');
    fabricCanvas.selection = false;
    fabricCanvas.isDrawingMode = false;

    // Move canvas container to video container
    setTimeout(() => {
        const canvasContainer = document.querySelector('.canvas-container');
        if (canvasContainer && canvasContainer.parentElement !== videoContainer) {
            videoContainer.appendChild(canvasContainer);
        }
    }, 100);

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    video.addEventListener('loadedmetadata', resizeCanvas);

    // Load existing annotations
    loadExistingAnnotations();
}

/**
 * Setup canvas mouse events
 */
function setupCanvasEvents() {
    if (!fabricCanvas) return;

    fabricCanvas.on('mouse:down', function (event) {
        if (!annotationMode || currentTool === 'free_draw') return;

        const pointer = fabricCanvas.getPointer(event.e);
        startX = pointer.x;
        startY = pointer.y;

        if (currentTool === 'text') {
            startDrawing(currentTool, startX, startY);
            return;
        }

        isDrawing = true;
        startDrawing(currentTool, startX, startY);
    });

    fabricCanvas.on('mouse:move', function (event) {
        if (!annotationMode || !isDrawing || currentTool === 'free_draw') return;

        const pointer = fabricCanvas.getPointer(event.e);
        updateDrawing(currentTool, pointer.x, pointer.y, startX, startY);
    });

    fabricCanvas.on('mouse:up', function (event) {
        if (!annotationMode) return;

        isDrawing = false;
        currentAnnotation = null;

        if (currentTool === 'arrow' && fabricCanvas.getObjects().length > 0) {
            const line = fabricCanvas.getObjects()[fabricCanvas.getObjects().length - 1];
            if (line.type === 'line') {
                addArrowHead(line);
            }
        }
    });
}

/**
 * Setup toolbar event handlers
 */
function setupToolbarEvents() {
    // Toggle annotation mode
    $('#toggleAnnotationMode').on('click', function () {
        if (!annotationMode) {
            enterAnnotationMode();
        } else {
            exitAnnotationMode();
        }
    });

    // Close annotation mode
    $('#closeAnnotationMode').on('click', exitAnnotationMode);

    // Tool selection
    $('.toolbar-btn[data-tool]').on('click', function () {
        const tool = $(this).data('tool');
        currentTool = tool;

        $('.toolbar-btn[data-tool]').removeClass('active');
        $(this).addClass('active');

        if (fabricCanvas) {
            fabricCanvas.isDrawingMode = false;
            fabricCanvas.selection = false;

            if (tool === 'free_draw') {
                fabricCanvas.isDrawingMode = true;
                fabricCanvas.freeDrawingBrush.width = 3;
                fabricCanvas.freeDrawingBrush.color = '#ff0000';
            }
        }
    });

    // Save annotation
    $('#saveAnnotation').on('click', saveAnnotation);

    // Clear annotations
    $('#clearAnnotations').on('click', function () {
        if (fabricCanvas) {
            fabricCanvas.clear();
            hasTemporaryDrawing = false;
        }
    });

    // Timestamp button clicks in annotation list
    $(document).on('click', '.timestamp-btn-annotation', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const video = getVideo();
        const timestamp = $(this).data('timestamp');
        if (video) {
            video.currentTime = timestamp;
            if (video.paused) {
                video.play();
            }
        }
    });
}

/**
 * Setup delete event handlers
 */
function setupDeleteEvents() {
    // Delete button on video overlay
    $(document).off('click', '#deleteAnnotationBtn').on('click', '#deleteAnnotationBtn', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const annotationId = $(this).data('annotation-id');

        if (annotationId) {
            deleteAnnotation(annotationId);
        } else if (currentDisplayedAnnotations.length > 0) {
            let message = '¿Cual anotacion deseas eliminar?\n\n';
            currentDisplayedAnnotations.forEach((ann, index) => {
                const userName = ann.user ? ann.user.name : 'Desconocido';
                const timestamp = formatTime(parseFloat(ann.timestamp));
                const type = ann.is_permanent ? 'Permanente' : `${ann.duration_seconds}s`;
                message += `${index + 1}. ${timestamp} - ${type} (${userName})\n`;
            });
            message += `\nIngresa el numero (1-${currentDisplayedAnnotations.length}):`;

            const choice = prompt(message);
            const choiceNum = parseInt(choice);

            if (choiceNum >= 1 && choiceNum <= currentDisplayedAnnotations.length) {
                const selectedAnnotation = currentDisplayedAnnotations[choiceNum - 1];
                deleteAnnotation(selectedAnnotation.id);
            } else if (choice !== null) {
                alert('Numero invalido');
            }
        }
    });

    // Delete buttons in annotation list
    $(document).on('click', '.delete-annotation-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const annotationId = $(this).data('annotation-id');
        if (annotationId) {
            deleteAnnotation(annotationId);
        }
    });
}

/**
 * Enter annotation mode
 */
function enterAnnotationMode() {
    const video = getVideo();
    annotationMode = true;

    if (video) video.pause();

    $('#annotationToolbar').fadeIn(300);
    $('.canvas-container').css('pointer-events', 'auto');

    if (fabricCanvas) {
        fabricCanvas.upperCanvasEl.style.pointerEvents = 'auto';
        fabricCanvas.lowerCanvasEl.style.pointerEvents = 'auto';
    }

    $('#toggleAnnotationMode')
        .removeClass('btn-rugby-outline')
        .addClass('btn-rugby-light')
        .html('<i class="fas fa-check"></i> Anotando');

    if (!fabricCanvas) {
        initAnnotationSystem();
    }
}

/**
 * Exit annotation mode
 */
export function exitAnnotationMode() {
    annotationMode = false;

    $('#annotationToolbar').fadeOut(300);
    $('.canvas-container').css('pointer-events', 'none');

    if (fabricCanvas) {
        fabricCanvas.upperCanvasEl.style.pointerEvents = 'none';
        fabricCanvas.lowerCanvasEl.style.pointerEvents = 'none';
        // Clear temporary drawings from canvas
        fabricCanvas.clear();
    }

    $('#toggleAnnotationMode')
        .removeClass('btn-rugby-light')
        .addClass('btn-rugby-outline')
        .html('<i class="fas fa-paint-brush"></i> Anotar');

    // Hide delete button
    $('#deleteAnnotationBtn').hide();

    // Reset state
    currentDisplayedAnnotations = [];
    hasTemporaryDrawing = false;

    // Re-check for saved annotations at current time
    setTimeout(() => checkAndShowAnnotations(), 100);
}

/**
 * Start drawing a shape
 */
function startDrawing(tool, startX, startY) {
    const colorPicker = document.getElementById('annotationColor');
    const selectedColor = colorPicker ? colorPicker.value : '#ff0000';

    const options = {
        left: startX,
        top: startY,
        fill: 'transparent',
        stroke: selectedColor,
        strokeWidth: 3,
        selectable: true,
        evented: true
    };

    switch (tool) {
        case 'arrow':
            currentAnnotation = new fabric.Line([startX, startY, startX, startY], {
                ...options,
                strokeWidth: 4
            });
            break;
        case 'circle':
            currentAnnotation = new fabric.Circle({
                ...options,
                radius: 1,
                left: startX,
                top: startY
            });
            break;
        case 'line':
            currentAnnotation = new fabric.Line([startX, startY, startX, startY], options);
            break;
        case 'rectangle':
            currentAnnotation = new fabric.Rect({
                ...options,
                width: 1,
                height: 1
            });
            break;
        case 'text':
            const textValue = prompt('Ingresa el texto:') || 'Texto';
            currentAnnotation = new fabric.Text(textValue, {
                left: startX,
                top: startY,
                fill: selectedColor,
                fontSize: 20,
                fontFamily: 'Arial',
                selectable: true,
                evented: true
            });
            break;
    }

    if (currentAnnotation) {
        fabricCanvas.add(currentAnnotation);
        fabricCanvas.renderAll();
        hasTemporaryDrawing = true;
    }
}

/**
 * Update drawing while dragging
 */
function updateDrawing(tool, currentX, currentY, startX, startY) {
    if (!currentAnnotation) return;

    switch (tool) {
        case 'arrow':
        case 'line':
            currentAnnotation.set({ x2: currentX, y2: currentY });
            break;
        case 'circle':
            const radius = Math.sqrt(Math.pow(currentX - startX, 2) + Math.pow(currentY - startY, 2));
            currentAnnotation.set({ radius: radius });
            break;
        case 'rectangle':
            currentAnnotation.set({
                width: Math.abs(currentX - startX),
                height: Math.abs(currentY - startY),
                left: Math.min(startX, currentX),
                top: Math.min(startY, currentY)
            });
            break;
    }

    fabricCanvas.renderAll();
}

/**
 * Add arrow head to a line
 */
function addArrowHead(line) {
    const x1 = line.x1;
    const y1 = line.y1;
    const x2 = line.x2;
    const y2 = line.y2;
    const colorPicker = document.getElementById('annotationColor');
    const selectedColor = colorPicker ? colorPicker.value : '#ff0000';

    const angle = Math.atan2(y2 - y1, x2 - x1);
    const headLength = 20;

    const arrowHead = new fabric.Polygon([
        { x: x2, y: y2 },
        { x: x2 - headLength * Math.cos(angle - Math.PI / 6), y: y2 - headLength * Math.sin(angle - Math.PI / 6) },
        { x: x2 - headLength * Math.cos(angle + Math.PI / 6), y: y2 - headLength * Math.sin(angle + Math.PI / 6) }
    ], {
        fill: selectedColor,
        stroke: selectedColor,
        strokeWidth: 2,
        selectable: true,
        evented: true
    });

    fabricCanvas.add(arrowHead);
    fabricCanvas.renderAll();
}

/**
 * Save annotation to server
 */
function saveAnnotation() {
    if (!fabricCanvas || fabricCanvas.getObjects().length === 0) {
        alert('No hay anotaciones para guardar');
        return;
    }

    const config = getConfig();
    const video = getVideo();
    const timestamp = video ? video.currentTime : 0;
    const durationSelect = document.getElementById('annotationDuration');
    const selectedDuration = durationSelect ? durationSelect.value : '4';

    const annotationData = {
        canvas_data: fabricCanvas.toJSON(),
        canvas_width: fabricCanvas.width,
        canvas_height: fabricCanvas.height,
        video_width: video ? video.videoWidth : 0,
        video_height: video ? video.videoHeight : 0
    };

    const postData = {
        video_id: config.videoId,
        timestamp: timestamp,
        annotation_type: 'canvas',
        annotation_data: annotationData
    };

    if (selectedDuration === 'permanent') {
        postData.is_permanent = true;
    } else {
        postData.duration_seconds = parseInt(selectedDuration);
        postData.is_permanent = false;
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        url: '/api/annotations',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(postData),
        success: function (response) {
            if (response.success) {
                if (typeof toastr !== 'undefined') {
                    toastr.success('Anotacion guardada exitosamente');
                }
                loadExistingAnnotations();
                exitAnnotationMode();
                hasTemporaryDrawing = false;
            } else {
                alert('Error al guardar la anotacion');
            }
        },
        error: function (xhr) {
            console.error('Error saving annotation:', xhr);
            let errorMsg = 'Error de conexion al guardar la anotacion';
            if (xhr.responseText) {
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    if (errorData.message) {
                        errorMsg = errorData.message;
                    }
                } catch (e) { }
            }
            alert(errorMsg);
        }
    });
}

/**
 * Load existing annotations from server
 */
export function loadExistingAnnotations() {
    const config = getConfig();

    $.ajax({
        url: `/api/annotations/video/${config.videoId}`,
        method: 'GET',
        success: function (response) {
            if (response.success) {
                savedAnnotations = response.annotations;
                window.savedAnnotations = savedAnnotations;
                renderAnnotationsList();

                if (fabricCanvas) {
                    checkAndShowAnnotations();
                }
            }
        },
        error: function (xhr) {
            console.error('Error loading annotations:', xhr);
        }
    });
}

/**
 * Render annotations list in sidebar
 */
function renderAnnotationsList() {
    const annotationsList = document.getElementById('annotationsList');
    const annotationsCount = document.getElementById('annotationsCount');
    const noAnnotationsMessage = document.getElementById('noAnnotationsMessage');

    if (!annotationsList) return;

    if (annotationsCount) {
        annotationsCount.textContent = savedAnnotations.length;
    }

    const existingItems = annotationsList.querySelectorAll('.annotation-item');
    existingItems.forEach(item => item.remove());

    if (savedAnnotations.length === 0) {
        if (noAnnotationsMessage) noAnnotationsMessage.style.display = 'block';
        return;
    }

    if (noAnnotationsMessage) noAnnotationsMessage.style.display = 'none';

    const sortedAnnotations = [...savedAnnotations].sort((a, b) =>
        parseFloat(a.timestamp) - parseFloat(b.timestamp)
    );

    sortedAnnotations.forEach(annotation => {
        const timestamp = parseFloat(annotation.timestamp);
        const duration = parseInt(annotation.duration_seconds) || 4;
        const isPermanent = annotation.is_permanent;

        const item = document.createElement('div');
        item.className = 'annotation-item border-bottom p-2';
        item.setAttribute('data-annotation-id', annotation.id);

        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <button class="btn btn-sm btn-info timestamp-btn-annotation mr-2"
                            data-timestamp="${timestamp}"
                            title="Ir al momento de esta anotacion">
                        <i class="fas fa-clock"></i> ${formatTime(timestamp)}
                    </button>
                    <span class="badge badge-${isPermanent ? 'primary' : 'secondary'} ml-1">
                        ${isPermanent ? 'Permanente' : duration + 's'}
                    </span>
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-user"></i> ${annotation.user ? annotation.user.name : 'Desconocido'}
                    </small>
                </div>
                <button class="btn btn-sm btn-outline-danger delete-annotation-btn"
                        data-annotation-id="${annotation.id}"
                        title="Eliminar anotacion">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        annotationsList.appendChild(item);
    });
}

/**
 * Delete annotation
 */
function deleteAnnotation(annotationId) {
    if (!confirm('¿Estas seguro de eliminar esta anotacion? Esta accion no se puede deshacer.')) {
        return;
    }

    $('.delete-annotation-btn').prop('disabled', true).addClass('disabled');
    $('#deleteAnnotationBtn').prop('disabled', true).addClass('disabled');

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        url: `/api/annotations/${annotationId}`,
        method: 'DELETE',
        success: function (response) {
            if (response.success) {
                const index = savedAnnotations.findIndex(a => a.id == annotationId);
                if (index !== -1) {
                    savedAnnotations.splice(index, 1);
                    window.savedAnnotations = savedAnnotations;
                }

                renderAnnotationsList();

                if (typeof toastr !== 'undefined') {
                    toastr.success('Anotacion eliminada exitosamente');
                }

                checkAndShowAnnotations();

                $('.delete-annotation-btn').prop('disabled', false).removeClass('disabled');
                $('#deleteAnnotationBtn').prop('disabled', false).removeClass('disabled');
            }
        },
        error: function (xhr) {
            $('.delete-annotation-btn').prop('disabled', false).removeClass('disabled');
            $('#deleteAnnotationBtn').prop('disabled', false).removeClass('disabled');

            if (xhr.status === 500 || xhr.status === 404) {
                loadExistingAnnotations();
                if (typeof toastr !== 'undefined') {
                    toastr.warning('La anotacion ya no existe. Lista actualizada.');
                }
            } else if (xhr.status === 403) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('No tienes permisos para eliminar esta anotacion');
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error al eliminar la anotacion');
                }
            }
        }
    });
}

/**
 * Check and show annotations based on current video time
 */
export function checkAndShowAnnotations() {
    if (annotationMode || !fabricCanvas) return;
    if (hasTemporaryDrawing) return;

    const video = getVideo();
    if (!video) return;

    const currentTime = video.currentTime;

    const activeAnnotations = savedAnnotations.filter(annotation => {
        const startTime = parseFloat(annotation.timestamp);
        const durationSeconds = parseInt(annotation.duration_seconds) || 4;
        const endTime = annotation.is_permanent ? Infinity : startTime + durationSeconds;

        const TOLERANCE = 0.15;
        const isActive = currentTime >= (startTime - TOLERANCE) && currentTime <= endTime;

        return isActive;
    });

    const activeIds = activeAnnotations.map(a => a.id).sort().join(',');
    const displayedIds = currentDisplayedAnnotations.map(a => a.id).sort().join(',');

    if (activeIds !== displayedIds) {
        if (activeAnnotations.length > 0) {
            displayMultipleAnnotations(activeAnnotations);
            currentDisplayedAnnotations = activeAnnotations;

            const deleteBtn = document.getElementById('deleteAnnotationBtn');
            if (deleteBtn) {
                deleteBtn.style.display = 'block';

                if (activeAnnotations.length === 1) {
                    $(deleteBtn).data('annotation-id', activeAnnotations[0].id);
                    deleteBtn.innerHTML = '<i class="fas fa-times-circle"></i> Eliminar Anotacion';
                } else {
                    $(deleteBtn).removeData('annotation-id');
                    deleteBtn.innerHTML = `<i class="fas fa-times-circle"></i> ${activeAnnotations.length} Anotaciones`;
                }
            }
        } else {
            clearDisplayedAnnotation();
            currentDisplayedAnnotations = [];

            const deleteBtn = document.getElementById('deleteAnnotationBtn');
            if (deleteBtn) {
                deleteBtn.style.display = 'none';
                deleteBtn.removeAttribute('data-annotation-id');
            }
        }
    }
}

/**
 * Display multiple annotations on canvas
 */
function displayMultipleAnnotations(annotations) {
    if (!fabricCanvas) return;

    fabricCanvas.clear();

    annotations.forEach((annotation) => {
        if (annotation.annotation_data && annotation.annotation_data.canvas_data) {
            const canvasData = annotation.annotation_data.canvas_data;

            fabric.util.enlivenObjects(canvasData.objects || [], function (objects) {
                objects.forEach(function (obj) {
                    fabricCanvas.add(obj);
                });
                fabricCanvas.renderAll();
            }, null);
        }
    });
}

/**
 * Display single annotation (compatibility)
 */
function displayAnnotation(annotation) {
    displayMultipleAnnotations([annotation]);
}

/**
 * Clear displayed annotation
 */
function clearDisplayedAnnotation() {
    if (!fabricCanvas) return;
    fabricCanvas.clear();
}

/**
 * Get saved annotations
 */
export function getSavedAnnotations() {
    return savedAnnotations;
}
