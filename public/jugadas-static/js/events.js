/**
 * events.js - Event handlers e inicialización
 */

// ============================================
// EVENT HANDLERS PARA DIBUJO
// ============================================

canvas.on('mouse:down', function(opt) {
    // Modo asignar posesión
    if (isAssigningPossession) {
        const target = opt.target;
        if (target && target.isPlayer) {
            assignPossessionTo(target);
            deactivateAssignPossessionMode();
        }
        return;
    }

    // Modo crear pase
    if (isCreatingPass) {
        return;
    }

    // Modo dibujo
    if (!isDrawingMode) return;

    const pointer = canvas.getPointer(opt.e);
    const objCenter = selectedForDrawing.getCenterPoint();
    const distance = Math.sqrt(
        Math.pow(pointer.x - objCenter.x, 2) +
        Math.pow(pointer.y - objCenter.y, 2)
    );

    if (distance > 100) {
        console.log('⚠️ Comienza más cerca del jugador/balón (máx 100px)');
        return;
    }

    isMouseDown = true;
    currentPath = [{ x: pointer.x, y: pointer.y }];
});

canvas.on('mouse:move', function(opt) {
    if (!isDrawingMode || !isMouseDown) return;

    const pointer = canvas.getPointer(opt.e);
    const lastPoint = currentPath[currentPath.length - 1];

    const distance = Math.sqrt(
        Math.pow(pointer.x - lastPoint.x, 2) +
        Math.pow(pointer.y - lastPoint.y, 2)
    );

    if (distance > 8) {
        currentPath.push({ x: pointer.x, y: pointer.y });
        drawPreviewLine();
    }
});

canvas.on('mouse:up', function(opt) {
    // Handler para crear pases
    if (isCreatingPass) {
        const target = opt.target;
        if (target && target.isPlayer) {
            const destinationPlayer = target;

            if (destinationPlayer.playerNumber === ballPossession) {
                alert('⚠️ No puedes pasar a ti mismo.\n\nSelecciona otro jugador como destino.');
                return;
            }

            const originPlayer = findObjectById(ballPossession);

            if (!originPlayer) {
                alert('⚠️ Error: No se encontró el jugador con balón');
                deactivateCreatePassMode();
                return;
            }

            const fromId = originPlayer.playerNumber;
            const toId = destinationPlayer.playerNumber;

            canvas.discardActiveObject();
            canvas.renderAll();

            const timing = createPass(originPlayer, destinationPlayer);

            if (timing === undefined) {
                return;
            }

            deactivateCreatePassMode(fromId, toId, timing);
        }
        return;
    }

    // Handler para dibujo
    if (!isDrawingMode || !isMouseDown) return;

    isMouseDown = false;
    clearPreviewLine();

    if (currentPath.length < 2) {
        console.log('⚠️ Trazo muy corto, no guardado');
        deactivateDrawingMode();
        return;
    }

    let totalLength = 0;
    for (let i = 1; i < currentPath.length; i++) {
        totalLength += Math.sqrt(
            Math.pow(currentPath[i].x - currentPath[i-1].x, 2) +
            Math.pow(currentPath[i].y - currentPath[i-1].y, 2)
        );
    }

    if (totalLength < 10) {
        console.log('⚠️ Trazo muy corto (<10px), no guardado');
        deactivateDrawingMode();
        return;
    }

    const color = getPathColor(selectedForDrawing);
    const pathGroup = createPathWithArrow(currentPath, color);

    if (pathGroup) {
        canvas.add(pathGroup);
        pathGroup.sendToBack();

        canvas.getObjects().forEach(obj => {
            if (obj.type === 'image') {
                obj.sendToBack();
            }
        });

        saveMovement(selectedForDrawing, currentPath, pathGroup);
        canvas.renderAll();
    }

    deactivateDrawingMode();
});

// ============================================
// SELECTION HANDLERS
// ============================================

canvas.on('selection:created', function(e) {
    const obj = e.selected[0];
    if (obj && (obj.isPlayer || obj.isBall)) {
        $('#btnDrawMovement').prop('disabled', false);
    }
});

canvas.on('selection:updated', function(e) {
    const obj = e.selected[0];
    if (obj && (obj.isPlayer || obj.isBall)) {
        $('#btnDrawMovement').prop('disabled', false);
    }
});

canvas.on('selection:cleared', function() {
    selectedPlayer = null;
    if (!isDrawingMode) {
        $('#btnDrawMovement').prop('disabled', true);
    }
});

// ============================================
// BUTTON HANDLERS (se configuran en document.ready)
// ============================================

$(document).ready(function() {
    // Cargar cancha y jugadas guardadas
    drawRugbyField();
    loadPlays();

    // Botones de jugadores
    $('#btnAddPlayer').on('click', () => addPlayer());
    $('#btnAddBall').on('click', () => crearBalon());
    $('#btnDeleteSelected').on('click', deleteSelectedPlayer);
    $('#btnApplyFormacion').on('click', applyFormacion);
    $('#btnSavePlay').on('click', savePlay);
    $('#btnClearCanvas').on('click', clearCanvas);

    // Botón de dibujar movimiento
    $('#btnDrawMovement').on('click', function() {
        if (isDrawingMode) {
            deactivateDrawingMode();
        } else {
            activateDrawingMode();
        }
    });

    // Botón de limpiar movimientos
    $('#btnClearMovements').on('click', function() {
        if (movements.length === 0) {
            alert('⚠️ No hay trayectorias para borrar');
            return;
        }
        if (confirm('¿Borrar todas las trayectorias?')) {
            clearAllMovements();
            updatePlayButton();
        }
    });

    // Botones de animación
    $('#btnPlay').on('click', function() {
        if (!isPlaying && movements.length > 0) {
            playAllMovements();
        }
    });

    $('#btnReset').on('click', function() {
        resetToOriginalPositions();
    });

    // Botones de posesión
    $('#btnAssignPossession').on('click', function() {
        if (isAssigningPossession) {
            deactivateAssignPossessionMode();
        } else {
            activateAssignPossessionMode();
        }
    });

    $('#btnReleasePossession').on('click', function() {
        releasePossession();
    });

    // Botón de crear pase
    $('#btnCreatePass').on('click', function() {
        if (isCreatingPass) {
            deactivateCreatePassMode();
        } else {
            activateCreatePassMode();
        }
    });

    // Delegated events para cargar/eliminar jugadas
    $(document).on('click', '.load-play', function() {
        const playId = $(this).data('id');
        loadPlayById(playId);
    });

    $(document).on('click', '.delete-play', function() {
        const playId = $(this).data('id');
        deletePlayById(playId);
    });

    console.log('✅ Editor de Jugadas Rugby - Inicializado');
    console.log('🏉 Funcionalidades: Posiciones fijas, formaciones, guardar/cargar');
    console.log('🏈 Balón: Agregar, arrastrar, eliminar, posesión');
    console.log('✏️ Trayectorias: Dibujar movimientos para jugadores/balón');
    console.log('🔗 Pases: Crear pases entre jugadores');
    console.log('▶️ Animación: Movimientos simultáneos + pases');
    console.log('📐 Orientación: Izquierda → Derecha');
});

console.log('📦 events.js cargado');
