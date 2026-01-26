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
let isDraggingObject = false; // Flag para saber si estamos arrastrando un objeto existente
let currentAnnotation = null;
let savedAnnotations = [];
let currentDisplayedAnnotations = [];
let hasTemporaryDrawing = false;
let startX, startY;
let annotationsBySecond = new Map(); // Performance: Index annotations by second for O(1) lookup

// Undo/Redo state
let undoStack = [];
let redoStack = [];
const MAX_UNDO_STEPS = 50;

// Area tool state
let areaPoints = [];
let areaPreviewPath = null;

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

    // Listen for video seeked to show/hide annotations (timeupdate moved to time-manager)
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
        if (!annotationMode) return;

        const pointer = fabricCanvas.getPointer(event.e);
        const clickedObject = fabricCanvas.findTarget(event.e);

        // Si encontramos un objeto bajo el cursor, permitir arrastre (no dibujar)
        if (clickedObject && !clickedObject.isAreaPoint) {
            isDraggingObject = true;
            fabricCanvas.setActiveObject(clickedObject);
            fabricCanvas.renderAll();
            return;
        }

        isDraggingObject = false;
        startX = pointer.x;
        startY = pointer.y;

        // Handle area tool - click to add points
        if (currentTool === 'area') {
            handleAreaClick(pointer.x, pointer.y);
            return;
        }

        // Modo selección - no dibujar, solo permitir seleccionar/mover objetos
        if (currentTool === 'select') return;

        if (currentTool === 'free_draw') return;

        if (currentTool === 'text') {
            saveToUndoStack();
            startDrawing(currentTool, startX, startY);
            return;
        }

        isDrawing = true;
        saveToUndoStack();
        startDrawing(currentTool, startX, startY);
    });

    fabricCanvas.on('mouse:move', function (event) {
        if (!annotationMode) return;

        const pointer = fabricCanvas.getPointer(event.e);

        // Preview area polygon
        if (currentTool === 'area' && areaPoints.length > 0) {
            previewArea(pointer.x, pointer.y);
            return;
        }

        if (!isDrawing || currentTool === 'free_draw') return;
        updateDrawing(currentTool, pointer.x, pointer.y, startX, startY);
    });

    fabricCanvas.on('mouse:up', function (event) {
        if (!annotationMode) return;

        // Si estábamos arrastrando un objeto, solo resetear el flag
        if (isDraggingObject) {
            isDraggingObject = false;
            return;
        }

        isDrawing = false;
        currentAnnotation = null;

        if (currentTool === 'arrow' && fabricCanvas.getObjects().length > 0) {
            const line = fabricCanvas.getObjects()[fabricCanvas.getObjects().length - 1];
            if (line.type === 'line') {
                addArrowHead(line);
            }
        }
    });

    // Double-click to finish area
    fabricCanvas.on('mouse:dblclick', function(event) {
        if (!annotationMode) return;

        if (currentTool === 'area' && areaPoints.length >= 3) {
            finishArea();
        }
    });

    // Save to undo stack after free draw
    fabricCanvas.on('path:created', function() {
        if (currentTool === 'free_draw') {
            saveToUndoStack();
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

        // Reset area tool state when switching tools
        clearAreaPoints();

        // Mostrar tip para herramienta de área
        showAreaTip(tool === 'area');

        if (fabricCanvas) {
            fabricCanvas.isDrawingMode = false;
            fabricCanvas.selection = false;

            if (tool === 'free_draw') {
                fabricCanvas.isDrawingMode = true;
                fabricCanvas.freeDrawingBrush.width = 3;
                const colorPicker = document.getElementById('annotationColor');
                fabricCanvas.freeDrawingBrush.color = colorPicker ? colorPicker.value : '#ff0000';
            }
        }
    });

    // Update free draw color when color picker changes
    $('#annotationColor').on('change', function() {
        if (fabricCanvas && fabricCanvas.isDrawingMode) {
            fabricCanvas.freeDrawingBrush.color = this.value;
        }
    });

    // Save annotation
    $('#saveAnnotation').on('click', saveAnnotation);

    // Clear annotations
    $('#clearAnnotations').on('click', function () {
        if (fabricCanvas) {
            saveToUndoStack();
            fabricCanvas.clear();
            hasTemporaryDrawing = false;
        }
    });

    // Undo/Redo buttons
    $('#undoAnnotation').on('click', undo);
    $('#redoAnnotation').on('click', redo);

    // Keyboard shortcuts for undo/redo
    $(document).on('keydown', function(e) {
        if (!annotationMode) return;

        // Ignore if typing in input/textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

        // Ctrl+Z = Undo
        if (e.ctrlKey && e.key === 'z' && !e.shiftKey) {
            e.preventDefault();
            undo();
        }
        // Ctrl+Y or Ctrl+Shift+Z = Redo
        if ((e.ctrlKey && e.key === 'y') || (e.ctrlKey && e.shiftKey && e.key === 'z')) {
            e.preventDefault();
            redo();
        }

        // Enter = Finish area
        if (e.key === 'Enter' && currentTool === 'area' && areaPoints.length >= 3) {
            e.preventDefault();
            finishArea();
        }

        // Escape = Cancel current drawing
        if (e.key === 'Escape' && currentTool === 'area') {
            e.preventDefault();
            clearAreaPoints();
        }
    });

    // Spotlight button
    $(document).on('click', '.spotlight-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        addSpotlight();
    });

    // Symbol buttons
    $(document).on('click', '.symbol-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const symbol = $(this).data('symbol');
        addSymbol(symbol);
        // Cerrar el dropdown después de seleccionar
        $(this).closest('.dropdown-menu').removeClass('show');
        $(this).closest('.dropdown').find('.dropdown-toggle').attr('aria-expanded', 'false');
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
 * Handle click for area tool
 */
function handleAreaClick(x, y) {
    areaPoints.push({ x, y });

    // Show point indicator
    const pointMarker = new fabric.Circle({
        left: x,
        top: y,
        radius: 5,
        fill: '#ffc107',
        stroke: '#fff',
        strokeWidth: 2,
        originX: 'center',
        originY: 'center',
        selectable: false,
        evented: false,
        isAreaPoint: true
    });
    fabricCanvas.add(pointMarker);
    fabricCanvas.renderAll();

    hasTemporaryDrawing = true;
}

/**
 * Preview area polygon while moving mouse
 */
function previewArea(mouseX, mouseY) {
    // Remove previous preview
    if (areaPreviewPath) {
        fabricCanvas.remove(areaPreviewPath);
    }

    if (areaPoints.length < 1) return;

    const colorPicker = document.getElementById('annotationColor');
    const selectedColor = colorPicker ? colorPicker.value : '#ffc107';

    // Create temporary polygon including mouse position
    const points = [...areaPoints, { x: mouseX, y: mouseY }];

    areaPreviewPath = new fabric.Polygon(points, {
        fill: hexToRgba(selectedColor, 0.2),
        stroke: selectedColor,
        strokeWidth: 2,
        strokeDashArray: [5, 3],
        selectable: false,
        evented: false
    });

    fabricCanvas.add(areaPreviewPath);
    fabricCanvas.renderAll();
}

/**
 * Finish and create the area polygon
 */
function finishArea() {
    if (areaPoints.length < 3) {
        // Need at least 3 points for a polygon
        clearAreaPoints();
        return;
    }

    saveToUndoStack();

    const colorPicker = document.getElementById('annotationColor');
    const selectedColor = colorPicker ? colorPicker.value : '#ffc107';

    // Create final polygon
    const finalArea = new fabric.Polygon(areaPoints, {
        fill: hexToRgba(selectedColor, 0.3),
        stroke: selectedColor,
        strokeWidth: 3,
        selectable: true,
        evented: true
    });

    fabricCanvas.add(finalArea);
    finalArea.sendToBack();

    // Bring other objects to front
    fabricCanvas.getObjects().forEach(obj => {
        if (!obj.isAreaPoint && obj !== finalArea) {
            obj.bringToFront();
        }
    });

    clearAreaPoints();
    fabricCanvas.renderAll();

    // Ocultar tip y cambiar a modo selección
    showAreaTip(false);
    currentTool = 'select';
    $('.toolbar-btn[data-tool]').removeClass('active');
}

/**
 * Clear area points and preview
 */
function clearAreaPoints() {
    if (!fabricCanvas) return;

    // Remove point markers
    const pointMarkers = fabricCanvas.getObjects().filter(obj => obj.isAreaPoint);
    pointMarkers.forEach(marker => fabricCanvas.remove(marker));

    // Remove preview polygon
    if (areaPreviewPath) {
        fabricCanvas.remove(areaPreviewPath);
        areaPreviewPath = null;
    }

    areaPoints = [];
    fabricCanvas.renderAll();
}

/**
 * Show/hide area tool tip
 */
let areaTipTimeout = null;
function showAreaTip(show) {
    const tip = document.getElementById('areaTip');
    if (!tip) return;

    // Clear any existing timeout
    if (areaTipTimeout) {
        clearTimeout(areaTipTimeout);
        areaTipTimeout = null;
    }

    if (show) {
        tip.style.display = 'block';
        // Auto-hide after 4 seconds
        areaTipTimeout = setTimeout(() => {
            tip.style.display = 'none';
        }, 4000);
    } else {
        tip.style.display = 'none';
    }
}

/**
 * Helper: Convert hex color to rgba
 */
function hexToRgba(hex, alpha) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

/**
 * Save current canvas state to undo stack
 */
function saveToUndoStack() {
    if (!fabricCanvas) return;

    const canvasState = JSON.stringify(fabricCanvas.toJSON());
    undoStack.push(canvasState);

    // Limit stack size
    if (undoStack.length > MAX_UNDO_STEPS) {
        undoStack.shift();
    }

    // Clear redo stack when new action is performed
    redoStack = [];

    updateUndoRedoButtons();
}

/**
 * Undo last action
 */
function undo() {
    if (!fabricCanvas || undoStack.length === 0) return;

    // Save current state to redo stack
    const currentState = JSON.stringify(fabricCanvas.toJSON());
    redoStack.push(currentState);

    // Restore previous state
    const previousState = undoStack.pop();
    fabricCanvas.loadFromJSON(previousState, function() {
        fabricCanvas.renderAll();
        updateUndoRedoButtons();
    });
}

/**
 * Redo last undone action
 */
function redo() {
    if (!fabricCanvas || redoStack.length === 0) return;

    // Save current state to undo stack
    const currentState = JSON.stringify(fabricCanvas.toJSON());
    undoStack.push(currentState);

    // Restore next state
    const nextState = redoStack.pop();
    fabricCanvas.loadFromJSON(nextState, function() {
        fabricCanvas.renderAll();
        updateUndoRedoButtons();
    });
}

/**
 * Update undo/redo button states
 */
function updateUndoRedoButtons() {
    $('#undoAnnotation').prop('disabled', undoStack.length === 0);
    $('#redoAnnotation').prop('disabled', redoStack.length === 0);
}

/**
 * Add spotlight effect to canvas
 */
function addSpotlight() {
    if (!fabricCanvas) return;

    saveToUndoStack();

    const centerX = fabricCanvas.width / 2;
    const centerY = fabricCanvas.height / 2;

    // Create spotlight circle with glow effect
    const spotlight = new fabric.Circle({
        left: centerX,
        top: centerY,
        radius: 80,
        fill: 'transparent',
        stroke: '#00B7B5',
        strokeWidth: 4,
        originX: 'center',
        originY: 'center',
        selectable: true,
        evented: true,
        shadow: new fabric.Shadow({
            color: 'rgba(0, 183, 181, 0.6)',
            blur: 20,
            offsetX: 0,
            offsetY: 0
        })
    });

    // Add inner glow ring
    const innerRing = new fabric.Circle({
        left: centerX,
        top: centerY,
        radius: 75,
        fill: 'transparent',
        stroke: 'rgba(0, 183, 181, 0.3)',
        strokeWidth: 8,
        originX: 'center',
        originY: 'center',
        selectable: false,
        evented: false
    });

    const spotlightGroup = new fabric.Group([innerRing, spotlight], {
        left: centerX,
        top: centerY,
        originX: 'center',
        originY: 'center',
        selectable: true,
        evented: true
    });

    fabricCanvas.add(spotlightGroup);
    fabricCanvas.setActiveObject(spotlightGroup);
    fabricCanvas.renderAll();
    hasTemporaryDrawing = true;

    // Cambiar a modo selección para evitar dibujar accidentalmente
    currentTool = 'select';
    $('.toolbar-btn[data-tool]').removeClass('active');
}

/**
 * Add symbol to canvas
 */
function addSymbol(symbolType) {
    if (!fabricCanvas) return;

    saveToUndoStack();

    const centerX = fabricCanvas.width / 2;
    const centerY = fabricCanvas.height / 2;

    let symbol;

    switch(symbolType) {
        case 'tackle':
            // Impact/tackle symbol - starburst
            const points = [];
            const outerRadius = 25;
            const innerRadius = 12;
            const spikes = 8;

            for (let i = 0; i < spikes * 2; i++) {
                const radius = i % 2 === 0 ? outerRadius : innerRadius;
                const angle = (Math.PI / spikes) * i - Math.PI / 2;
                points.push({
                    x: centerX + radius * Math.cos(angle),
                    y: centerY + radius * Math.sin(angle)
                });
            }

            symbol = new fabric.Polygon(points, {
                fill: '#dc3545',
                stroke: '#fff',
                strokeWidth: 2,
                originX: 'center',
                originY: 'center',
                shadow: new fabric.Shadow({
                    color: 'rgba(220, 53, 69, 0.5)',
                    blur: 10,
                    offsetX: 0,
                    offsetY: 0
                })
            });
            break;

        case 'ball':
            // Rugby ball shape (ellipse)
            symbol = new fabric.Ellipse({
                left: centerX,
                top: centerY,
                rx: 20,
                ry: 12,
                fill: '#8B4513',
                stroke: '#fff',
                strokeWidth: 2,
                originX: 'center',
                originY: 'center',
                angle: -30
            });
            break;

        case 'x':
            // X mark for errors
            const line1 = new fabric.Line([-15, -15, 15, 15], {
                stroke: '#dc3545',
                strokeWidth: 6,
                strokeLineCap: 'round'
            });
            const line2 = new fabric.Line([15, -15, -15, 15], {
                stroke: '#dc3545',
                strokeWidth: 6,
                strokeLineCap: 'round'
            });
            symbol = new fabric.Group([line1, line2], {
                left: centerX,
                top: centerY,
                originX: 'center',
                originY: 'center',
                shadow: new fabric.Shadow({
                    color: 'rgba(0,0,0,0.4)',
                    blur: 4,
                    offsetX: 2,
                    offsetY: 2
                })
            });
            break;

        case 'check':
            // Checkmark for correct actions
            const checkPath = new fabric.Path('M -12 0 L -4 10 L 15 -12', {
                fill: 'transparent',
                stroke: '#28a745',
                strokeWidth: 6,
                strokeLineCap: 'round',
                strokeLineJoin: 'round'
            });
            symbol = new fabric.Group([checkPath], {
                left: centerX,
                top: centerY,
                originX: 'center',
                originY: 'center',
                shadow: new fabric.Shadow({
                    color: 'rgba(0,0,0,0.4)',
                    blur: 4,
                    offsetX: 2,
                    offsetY: 2
                })
            });
            break;

        default:
            return;
    }

    if (symbol) {
        symbol.set({
            selectable: true,
            evented: true,
            hasControls: false, // Sin controles de escala (más fácil de mover)
            hasBorders: true,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true
        });
        fabricCanvas.add(symbol);
        fabricCanvas.setActiveObject(symbol);
        fabricCanvas.renderAll();
        hasTemporaryDrawing = true;

        // Cambiar a modo selección para evitar dibujar accidentalmente
        currentTool = 'select';
        $('.toolbar-btn[data-tool]').removeClass('active');
    }
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

    // Hide delete button and area tip
    $('#deleteAnnotationBtn').hide();
    showAreaTip(false);

    // Reset state
    currentDisplayedAnnotations = [];
    hasTemporaryDrawing = false;

    // Clear area state
    areaPoints = [];
    areaPreviewPath = null;

    // Clear undo/redo stacks
    undoStack = [];
    redoStack = [];
    updateUndoRedoButtons();

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
                buildAnnotationIndex();
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
 * Build timestamp index for fast annotation lookup (Performance optimization)
 */
function buildAnnotationIndex() {
    annotationsBySecond.clear();

    savedAnnotations.forEach(annotation => {
        const startTime = Math.floor(parseFloat(annotation.timestamp));
        const durationSeconds = parseInt(annotation.duration_seconds) || 4;
        const isPermanent = annotation.is_permanent;

        // Index annotation for each second it's visible
        if (isPermanent) {
            // Permanent annotations: add to special key
            if (!annotationsBySecond.has('permanent')) {
                annotationsBySecond.set('permanent', []);
            }
            annotationsBySecond.get('permanent').push(annotation);
        } else {
            // Timed annotations: index for each second in range
            const endTime = startTime + durationSeconds;
            for (let t = startTime; t <= endTime; t++) {
                if (!annotationsBySecond.has(t)) {
                    annotationsBySecond.set(t, []);
                }
                annotationsBySecond.get(t).push(annotation);
            }
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

                // Rebuild index after deletion
                buildAnnotationIndex();
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
    const currentSecond = Math.floor(currentTime);

    // Performance optimization: Use indexed lookup instead of filter() - O(1) vs O(n)
    const activeAnnotations = [];

    // Get permanent annotations
    const permanentAnnotations = annotationsBySecond.get('permanent') || [];
    activeAnnotations.push(...permanentAnnotations);

    // Get annotations for current second (with tolerance for nearby seconds)
    for (let t = currentSecond - 1; t <= currentSecond + 1; t++) {
        const annotations = annotationsBySecond.get(t) || [];
        annotations.forEach(annotation => {
            // Avoid duplicates and verify exact timing with tolerance
            if (!activeAnnotations.includes(annotation)) {
                const startTime = parseFloat(annotation.timestamp);
                const durationSeconds = parseInt(annotation.duration_seconds) || 4;
                const endTime = startTime + durationSeconds;
                const TOLERANCE = 0.15;

                if (currentTime >= (startTime - TOLERANCE) && currentTime <= endTime) {
                    activeAnnotations.push(annotation);
                }
            }
        });
    }

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
