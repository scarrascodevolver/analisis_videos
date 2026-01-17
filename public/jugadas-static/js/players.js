/**
 * players.js - Gestión de jugadores
 */

function addPlayer(type = 'player', x = null, y = null, num = null) {
    const color = '#DC3545';
    const playerNum = num !== null ? num : playerCounter;

    // Usar tamaños escalados según el canvas
    const playerRadius = getPlayerRadius();
    const playerFont = getPlayerFontSize();
    const strokeWidth = Math.max(2, Math.round(3 * getScaleFactor()));

    const circle = new fabric.Circle({
        radius: playerRadius,
        fill: color,
        stroke: 'white',
        strokeWidth: strokeWidth,
        originX: 'center',
        originY: 'center',
        shadow: new fabric.Shadow({
            color: 'rgba(0, 0, 0, 0.8)',
            blur: Math.round(10 * getScaleFactor()),
            offsetX: 2,
            offsetY: 2
        })
    });

    const text = new fabric.Text(playerNum.toString(), {
        fontSize: playerFont,
        fill: 'white',
        fontWeight: 'bold',
        originX: 'center',
        originY: 'center'
    });

    // Posición por defecto escalada
    const defaultX = x !== null ? x : scaleX(300) + Math.random() * scaleX(400);
    const defaultY = y !== null ? y : scaleY(200) + Math.random() * scaleY(200);

    const player = new fabric.Group([circle, text], {
        left: defaultX,
        top: defaultY,
        hasControls: false,
        hasBorders: true,
        borderColor: '#ffeb3b',
        borderScaleFactor: 3,
        cornerColor: '#DC3545',
        transparentCorners: false,
        lockRotation: true,
        playerType: type,
        playerNumber: playerNum,
        isPlayer: true
    });

    player.on('selected', function() {
        selectedPlayer = player;
    });

    canvas.add(player);
    players.push(player);

    if (num === null) {
        playerCounter++;
    }

    canvas.renderAll();
    return player;
}

function deleteSelectedPlayer() {
    const activeObject = canvas.getActiveObject();

    if (activeObject && activeObject.isPlayer) {
        canvas.remove(activeObject);
        players = players.filter(p => p !== activeObject);
        selectedPlayer = null;
        canvas.renderAll();
    } else if (activeObject && activeObject.isBall) {
        eliminarBalon();
    } else {
        alert('⚠️ Selecciona un jugador o balón primero (click sobre él)');
    }
}

function clearAllPlayers() {
    players.forEach(player => canvas.remove(player));
    players = [];
    playerCounter = 1;
    selectedPlayer = null;
    eliminarBalon();
    clearAllMovements();
    // Limpiar posiciones originales para que Reset funcione correctamente
    originalPositions = {};
    canvas.renderAll();
}

console.log('📦 players.js cargado');
