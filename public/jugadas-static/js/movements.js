/**
 * movements.js - Sistema de trayectorias/movimientos
 */

function activateDrawingMode() {
    const activeObject = canvas.getActiveObject();

    if (!activeObject || (!activeObject.isPlayer && !activeObject.isBall)) {
        alert('⚠️ Selecciona un jugador o balón primero');
        return;
    }

    selectedForDrawing = activeObject;
    isDrawingMode = true;
    currentPath = [];

    canvas.defaultCursor = 'crosshair';
    canvas.hoverCursor = 'crosshair';
    canvas.selection = false;

    $('#btnDrawMovement').removeClass('btn-info').addClass('btn-warning')
        .html('<i class="fas fa-times"></i> Cancelar dibujo');

    console.log('✏️ Modo dibujo activado para:',
        activeObject.isBall ? 'Balón' : `Jugador ${activeObject.playerNumber}`);
}

function deactivateDrawingMode() {
    isDrawingMode = false;
    currentPath = [];
    selectedForDrawing = null;

    canvas.defaultCursor = 'default';
    canvas.hoverCursor = 'move';
    canvas.selection = true;

    const hasSelection = canvas.getActiveObject();
    $('#btnDrawMovement').removeClass('btn-warning').addClass('btn-info')
        .html('<i class="fas fa-pencil-alt"></i> Dibujar movimiento')
        .prop('disabled', !hasSelection);

    canvas.renderAll();
}

function getPathColor(obj) {
    if (obj.isBall) return '#FF8C00';
    return '#DC3545';
}

function createPathWithArrow(points, color) {
    if (points.length < 2) return null;

    let pathString = `M ${points[0].x} ${points[0].y}`;
    for (let i = 1; i < points.length; i++) {
        pathString += ` L ${points[i].x} ${points[i].y}`;
    }

    const path = new fabric.Path(pathString, {
        fill: null,
        stroke: color,
        strokeWidth: 3,
        strokeLineCap: 'round',
        strokeLineJoin: 'round',
        selectable: false,
        evented: false,
        opacity: 0.8
    });

    const lastPoint = points[points.length - 1];
    const prevPoint = points[points.length - 2];
    const angle = Math.atan2(lastPoint.y - prevPoint.y, lastPoint.x - prevPoint.x) * 180 / Math.PI;

    const arrowHead = new fabric.Triangle({
        left: lastPoint.x,
        top: lastPoint.y,
        width: 12,
        height: 15,
        fill: color,
        angle: angle + 90,
        originX: 'center',
        originY: 'center',
        selectable: false,
        evented: false
    });

    const group = new fabric.Group([path, arrowHead], {
        selectable: false,
        evented: false,
        isMovement: true
    });

    return group;
}

function saveMovement(obj, points, pathObject) {
    const hasBall = obj.isPlayer && ballPossession === obj.playerNumber;
    const playerId = obj.isBall ? 'ball' : obj.playerNumber;

    const movement = {
        id: ++movementIdCounter,
        type: 'movement',
        playerId: playerId,
        playerType: obj.isBall ? 'ball' : obj.playerType,
        points: points.map(p => ({ x: Math.round(p.x), y: Math.round(p.y) })),
        hasBall: hasBall,
        startDelay: 0,
        speed: PLAYER_SPEED, // Velocidad por defecto (200 px/s)
        pathObject: pathObject
    };

    movements.push(movement);

    if (hasBall) {
        console.log(`💾 Movimiento guardado: J${movement.playerId} (${points.length} puntos) 🏈 con balón`);
    } else {
        console.log(`💾 Movimiento guardado: J${movement.playerId} (${points.length} puntos)`);
    }
    console.log(`💡 Puedes dibujar otro movimiento para J${movement.playerId} o crear un pase`);

    updatePlayButton();
    updatePassButton();
    renderMovementsList();

    return movement;
}

function clearAllMovements() {
    movements.forEach(action => {
        if (action.type === 'movement' && action.pathObject) {
            canvas.remove(action.pathObject);
        }
    });

    movements = [];
    movementIdCounter = 0;
    canvas.renderAll();
    console.log('🧹 Trayectorias borradas');

    updatePlayButton();
    updatePassButton();
    renderMovementsList();
}

function deleteMovementById(id) {
    const index = movements.findIndex(m => m.id === id);
    if (index === -1) {
        console.warn(`⚠️ No se encontró elemento con ID ${id}`);
        return;
    }

    const action = movements[index];
    const wasPass = action.type === 'pass';

    if (action.type === 'movement' && action.pathObject) {
        canvas.remove(action.pathObject);
        canvas.renderAll();
        console.log(`🗑️ Movimiento J${action.playerId} eliminado (ID: ${id})`);
    } else if (action.type === 'pass') {
        console.log(`🗑️ Pase ${action.from}→${action.to} eliminado (ID: ${id})`);
    }

    movements.splice(index, 1);

    if (wasPass) {
        recalculateBallPossession();
    }

    updatePlayButton();
    updatePassButton();
    renderMovementsList();
}

function recalculateBallPossession() {
    const passes = movements.filter(m => m.type === 'pass');
    let newHolder;

    if (passes.length === 0) {
        newHolder = originalBallHolder;
        console.log(`🔄 Sin pases, balón vuelve al poseedor original: J${originalBallHolder}`);
    } else {
        const passMap = {};
        passes.forEach(p => {
            passMap[p.from] = p.to;
        });

        let currentHolder = originalBallHolder;
        while (passMap[currentHolder]) {
            currentHolder = passMap[currentHolder];
        }
        newHolder = currentHolder;
        console.log(`🔄 Recalculando cadena de pases → balón con J${newHolder}`);
    }

    if (newHolder !== ballPossession) {
        if (ballPossession !== null) {
            const oldPlayer = players.find(p => p.playerNumber === ballPossession);
            if (oldPlayer) {
                oldPlayer.off('moving');
                oldPlayer.hasBallPossession = false;
            }
        }

        ballPossession = newHolder;
        const newPlayer = players.find(p => p.playerNumber === ballPossession);
        if (newPlayer) {
            newPlayer.hasBallPossession = true;
        }

        console.log(`✅ Posesión actualizada a J${ballPossession}`);
        updatePossessionUI();
    }
}

function renderMovementsList() {
    const container = document.getElementById('movementsList');
    if (!container) return;

    if (movements.length === 0) {
        container.innerHTML = '<small class="text-muted">Sin movimientos</small>';
        return;
    }

    // Invertir para mostrar los más recientes primero
    const reversedMovements = [...movements].reverse();

    let html = '<div class="list-group list-group-flush">';
    reversedMovements.forEach((action) => {
        if (action.type === 'movement') {
            const ballIcon = action.hasBall ? ' 🏈' : '';
            const ballClass = action.hasBall ? 'border-warning' : 'border-success';
            const currentSpeed = action.speed || PLAYER_SPEED;
            html += `
                <div class="list-group-item py-1 px-2 ${ballClass}"
                     style="background: #2d2d2d; border-left: 3px solid; margin-bottom: 2px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="color: #28a745; font-weight: bold;">
                            J${action.playerId}${ballIcon}
                        </span>
                        <button class="btn btn-sm text-danger p-0" onclick="deleteMovementById(${action.id})" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <div class="d-flex align-items-center mt-1">
                        <small class="text-muted mr-1" style="font-size: 10px;">🏃</small>
                        <select class="form-control form-control-sm" style="font-size: 10px; padding: 0 2px; height: 20px; background: #3d4248; color: #e9ecef; border: none;"
                                onchange="updateMovementSpeed(${action.id}, this.value)" title="Velocidad">
                            <option value="100" ${currentSpeed === 100 ? 'selected' : ''}>Lento</option>
                            <option value="200" ${currentSpeed === 200 ? 'selected' : ''}>Normal</option>
                            <option value="300" ${currentSpeed === 300 ? 'selected' : ''}>Rápido</option>
                            <option value="400" ${currentSpeed === 400 ? 'selected' : ''}>Sprint</option>
                        </select>
                    </div>
                </div>
            `;
        } else if (action.type === 'pass') {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center py-1 px-2 border-primary"
                     style="background: #1a3a5c; border-left: 3px solid; margin-bottom: 2px;">
                    <span style="color: #66b3ff; font-weight: bold;">
                        🔗 Pase ${action.from}→${action.to} <small>(${action.timing || 50}%)</small>
                    </span>
                    <button class="btn btn-sm text-danger p-0 ml-2" onclick="deleteMovementById(${action.id})" title="Eliminar pase">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
        }
    });
    html += '</div>';

    container.innerHTML = html;
}

function drawPreviewLine() {
    clearPreviewLine();

    if (currentPath.length < 2) return;

    let pathString = `M ${currentPath[0].x} ${currentPath[0].y}`;
    for (let i = 1; i < currentPath.length; i++) {
        pathString += ` L ${currentPath[i].x} ${currentPath[i].y}`;
    }

    const color = getPathColor(selectedForDrawing);

    previewLine = new fabric.Path(pathString, {
        fill: null,
        stroke: color,
        strokeWidth: 3,
        strokeDashArray: [5, 5],
        selectable: false,
        evented: false,
        opacity: 0.6
    });

    canvas.add(previewLine);
    canvas.renderAll();
}

function clearPreviewLine() {
    if (previewLine) {
        canvas.remove(previewLine);
        previewLine = null;
    }
}

function updateMovementSpeed(id, speed) {
    const movement = movements.find(m => m.id === id);
    if (movement && movement.type === 'movement') {
        movement.speed = parseInt(speed);
        const speedLabels = { 100: 'Lento', 200: 'Normal', 300: 'Rápido', 400: 'Sprint' };
        console.log(`🏃 Velocidad J${movement.playerId} actualizada: ${speedLabels[movement.speed] || movement.speed}px/s`);
    }
}

console.log('📦 movements.js cargado');
