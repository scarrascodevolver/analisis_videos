/**
 * ball.js - Gestión del balón y sistema de posesión
 */

function crearBalon(x = null, y = null) {
    if (rugbyBall) {
        alert('⚠️ Ya existe un balón en la cancha');
        return null;
    }

    // Usar tamaños escalados
    const ballSize = getBallSize();
    const strokeWidth = Math.max(1, Math.round(2 * getScaleFactor()));

    // Posición por defecto escalada
    const defaultX = x !== null ? x : scaleX(600);
    const defaultY = y !== null ? y : scaleY(325);

    const ball = new fabric.Ellipse({
        rx: ballSize.rx,
        ry: ballSize.ry,
        fill: '#FFD700',
        stroke: '#8B4513',
        strokeWidth: strokeWidth,
        left: defaultX,
        top: defaultY,
        angle: -30,
        originX: 'center',
        originY: 'center',
        hasControls: false,
        hasBorders: true,
        borderColor: '#FF0000',
        borderScaleFactor: 3,
        lockRotation: true,
        isBall: true,
        shadow: new fabric.Shadow({
            color: 'rgba(0, 0, 0, 0.5)',
            blur: Math.round(8 * getScaleFactor()),
            offsetX: 2,
            offsetY: 2
        })
    });

    canvas.add(ball);
    rugbyBall = ball;
    canvas.renderAll();
    updatePossessionUI();
    updatePassButton();
    console.log('🏈 Balón agregado en:', defaultX, defaultY);
    return ball;
}

function eliminarBalon() {
    if (rugbyBall) {
        clearPossession();
        canvas.remove(rugbyBall);
        rugbyBall = null;
        canvas.renderAll();
        updatePossessionUI();
        console.log('🏈 Balón eliminado');
    }
}

// ============================================
// SISTEMA DE POSESIÓN
// ============================================

function updatePossessionUI() {
    $('#btnAssignPossession').prop('disabled', !rugbyBall);

    if (ballPossession !== null) {
        $('#btnReleasePossession').show();
        $('#btnAssignPossession').hide();
    } else {
        $('#btnReleasePossession').hide();
        if (rugbyBall) {
            $('#btnAssignPossession').show();
        }
    }
}

function activateAssignPossessionMode() {
    if (!rugbyBall) {
        alert('⚠️ Primero agrega un balón al canvas');
        return;
    }

    isAssigningPossession = true;
    canvas.defaultCursor = 'crosshair';
    canvas.hoverCursor = 'crosshair';

    $('#btnAssignPossession')
        .removeClass('btn-outline-warning')
        .addClass('btn-warning')
        .html('<i class="fas fa-times"></i> Cancelar');

    console.log('🏈 Modo asignación: Click en un jugador para darle el balón');
}

function deactivateAssignPossessionMode() {
    isAssigningPossession = false;
    canvas.defaultCursor = 'default';
    canvas.hoverCursor = 'move';

    $('#btnAssignPossession')
        .removeClass('btn-warning')
        .addClass('btn-outline-warning')
        .html('<i class="fas fa-hand-holding"></i> Asignar posesión');
}

function assignPossessionTo(player) {
    if (!rugbyBall || !player || !player.isPlayer) {
        console.log('⚠️ No se puede asignar: rugbyBall=', !!rugbyBall, 'player=', !!player);
        return;
    }

    console.log('🏈 Iniciando asignación de balón a jugador', player.playerNumber);

    // Guardar posiciones originales para el Reset (si aún no se han guardado)
    if (typeof saveOriginalPositions === 'function' && Object.keys(originalPositions).length === 0) {
        saveOriginalPositions();
        console.log('  └─ Posiciones originales guardadas automáticamente');
    }

    if (ballPossession !== null) {
        const prevPlayer = players.find(p => p.playerNumber === ballPossession);
        if (prevPlayer) {
            prevPlayer.off('moving');
            prevPlayer.hasBallPossession = false;
            console.log('  └─ Removido listener del jugador anterior:', ballPossession);
        }
    }

    ballPossession = player.playerNumber;
    originalBallHolder = player.playerNumber;
    player.hasBallPossession = true;

    const playerCenter = player.getCenterPoint();
    console.log('  └─ Centro del jugador:', playerCenter.x.toFixed(0), playerCenter.y.toFixed(0));

    const oldPos = { left: rugbyBall.left, top: rugbyBall.top };
    const newLeft = playerCenter.x + BALL_OFFSET_X;
    const newTop = playerCenter.y + BALL_OFFSET_Y;

    rugbyBall.set({ left: newLeft, top: newTop });
    rugbyBall.dirty = true;
    rugbyBall.setCoords();
    canvas.bringToFront(rugbyBall);

    console.log('  └─ Balón movido de (' + oldPos.left.toFixed(0) + ',' + oldPos.top.toFixed(0) +
        ') a (' + newLeft.toFixed(0) + ',' + newTop.toFixed(0) + ')');

    player.on('moving', function() {
        if (ballPossession === this.playerNumber && rugbyBall) {
            const center = this.getCenterPoint();
            rugbyBall.set({
                left: center.x + BALL_OFFSET_X,
                top: center.y + BALL_OFFSET_Y
            });
            rugbyBall.dirty = true;
            rugbyBall.setCoords();
        }
    });

    canvas.renderAll();
    updatePossessionUI();
    updatePassButton();
    console.log('✅ Posesión asignada a jugador', ballPossession);
}

function removePossessionListener(playerId) {
    const player = players.find(p => p.playerNumber === playerId);
    if (player) {
        player.off('moving');
        player.hasBallPossession = false;
    }
}

function transferPossessionLogically(toPlayer) {
    if (!rugbyBall || !toPlayer || !toPlayer.isPlayer) {
        console.log('⚠️ No se puede transferir: rugbyBall=', !!rugbyBall, 'toPlayer=', !!toPlayer);
        return;
    }

    console.log(`🔄 Transferencia LÓGICA de posesión a jugador ${toPlayer.playerNumber}`);
    console.log(`   ⚠️ El balón NO se moverá hasta Play`);

    if (ballPossession !== null) {
        const prevPlayer = players.find(p => p.playerNumber === ballPossession);
        if (prevPlayer) {
            prevPlayer.off('moving');
            prevPlayer.hasBallPossession = false;
            console.log(`   └─ Removido listener del jugador anterior: ${ballPossession}`);
        }
    }

    ballPossession = toPlayer.playerNumber;
    toPlayer.hasBallPossession = true;
    updatePossessionUI();

    console.log(`✅ Posesión LÓGICA transferida a jugador ${ballPossession}`);
    console.log(`   📍 Balón físico sigue en: (${rugbyBall.left.toFixed(0)}, ${rugbyBall.top.toFixed(0)})`);
}

function releasePossession() {
    if (ballPossession === null) return;

    removePossessionListener(ballPossession);
    const previousOwner = ballPossession;
    ballPossession = null;
    originalBallHolder = null;

    updatePossessionUI();
    updatePassButton();
    canvas.renderAll();
    console.log('🏈 Balón soltado por jugador', previousOwner);
}

function clearPossession() {
    if (ballPossession !== null) {
        removePossessionListener(ballPossession);
        ballPossession = null;
    }
    originalBallHolder = null;
    updatePossessionUI();
    updatePassButton();
}

console.log('📦 ball.js cargado');
