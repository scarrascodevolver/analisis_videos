/**
 * passes.js - Sistema de pases
 */

function updatePassButton() {
    const canCreatePass = ballPossession !== null && rugbyBall !== null;
    $('#btnCreatePass').prop('disabled', !canCreatePass);
}

function activateCreatePassMode() {
    if (ballPossession === null) {
        alert('⚠️ Primero asigna el balón a un jugador');
        return;
    }

    isCreatingPass = true;
    canvas.defaultCursor = 'crosshair';
    canvas.hoverCursor = 'crosshair';

    $('#btnCreatePass')
        .removeClass('btn-primary')
        .addClass('btn-warning')
        .html('❌ Cancelar Pase');

    $('#btnDrawMovement').prop('disabled', true);
    $('#btnAssignPossession').prop('disabled', true);

    console.log(`🏈 Modo pase activado. Jugador ${ballPossession} tiene el balón.`);
    console.log('👉 Haz click en el jugador DESTINO del pase');

    showPassMessage(`Jugador ${ballPossession} pasará. Click en destino...`);
    $('#animationStatus').html(`<i class="fas fa-football-ball text-primary"></i> J${ballPossession} pasará. Click destino...`);
}

function deactivateCreatePassMode(fromPlayerId = null, toPlayerId = null, timing = null) {
    isCreatingPass = false;
    canvas.defaultCursor = 'default';
    canvas.hoverCursor = 'move';

    $('#btnCreatePass')
        .removeClass('btn-warning')
        .addClass('btn-primary')
        .html('🔗 Crear Pase');

    const hasSelection = canvas.getActiveObject();
    $('#btnDrawMovement').prop('disabled', !hasSelection);
    updatePossessionUI();

    hidePassMessage();

    if (fromPlayerId && toPlayerId && timing !== null) {
        showPassMessage(
            `✅ Pase ${fromPlayerId}→${toPlayerId} al ${timing}% creado. Todos los movimientos serán simultáneos.`,
            4000
        );
        $('#animationStatus').html(
            `<i class="fas fa-check-circle text-success"></i> Pase al ${timing}% creado. Dibuja más movimientos...`
        );
    } else {
        showPassMessage('✅ Pase creado. Puedes seguir dibujando movimientos.', 3000);
        $('#animationStatus').html('<i class="fas fa-check-circle text-success"></i> Pase creado. Dibuja más movimientos...');
    }

    setTimeout(() => {
        if (!isCreatingPass && !isDrawingMode) {
            $('#animationStatus').html('<i class="fas fa-info-circle"></i> Selecciona jugador/balón primero');
        }
    }, 4000);

    console.log('🔗 Modo pase desactivado');
}

function showPassMessage(text, duration = 0) {
    let messageDiv = document.getElementById('passMessage');

    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.id = 'passMessage';
        messageDiv.style.cssText = `
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 102, 204, 0.95);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border: 2px solid white;
            transition: opacity 0.3s ease;
        `;
        document.body.appendChild(messageDiv);
    }

    if (text.includes('✅')) {
        messageDiv.style.background = 'rgba(40, 167, 69, 0.95)';
    } else {
        messageDiv.style.background = 'rgba(0, 102, 204, 0.95)';
    }

    messageDiv.textContent = '🏈 ' + text;
    messageDiv.style.display = 'block';
    messageDiv.style.opacity = '1';

    if (duration > 0) {
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 300);
        }, duration);
    }
}

function hidePassMessage() {
    const messageDiv = document.getElementById('passMessage');
    if (messageDiv) {
        messageDiv.style.display = 'none';
    }
}

function createPass(fromPlayer, toPlayer, timing = null) {
    if (!fromPlayer || !toPlayer) {
        console.error('❌ Error: Jugadores no válidos para el pase');
        return;
    }

    if (timing === null) {
        const inputTiming = prompt(
            `🏈 Pase ${fromPlayer.playerNumber} → ${toPlayer.playerNumber}\n\n` +
            `¿En qué momento del movimiento ocurre el pase?\n` +
            `(0% = inicio, 50% = mitad, 100% = final)\n\n` +
            `Ingresa un número entre 0 y 100:`,
            '50'
        );

        if (inputTiming === null) {
            console.log('❌ Pase cancelado');
            return;
        }

        timing = parseInt(inputTiming) || 50;
        timing = Math.max(0, Math.min(100, timing));
    }

    console.log(`🏈 Creando pase: ${fromPlayer.playerNumber} → ${toPlayer.playerNumber} al ${timing}%`);

    movements.push({
        id: ++movementIdCounter,
        type: 'pass',
        from: fromPlayer.playerNumber,
        to: toPlayer.playerNumber,
        timing: timing
    });

    transferPossessionLogically(toPlayer);

    console.log(`✅ Pase creado al ${timing}% (flecha se mostrará durante Play)`);
    console.log(`💡 Tip: Ahora puedes dibujar más movimientos para J${fromPlayer.playerNumber} o J${toPlayer.playerNumber}`);

    updatePlayButton();
    updatePassButton();
    renderMovementsList();

    canvas.renderAll();

    return timing;
}

console.log('📦 passes.js cargado');
