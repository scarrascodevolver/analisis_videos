/**
 * storage.js - Guardar y cargar jugadas
 */

function savePlay() {
    const playName = $('#playNameInput').val().trim();
    const playCategory = $('#playCategory').val();

    if (!playName) {
        alert('⚠️ Ingresa un nombre para la jugada');
        return;
    }

    if (players.length === 0) {
        alert('⚠️ Agrega al menos un jugador antes de guardar');
        return;
    }

    const playersData = players.map(p => ({
        number: p.playerNumber,
        type: p.playerType,
        x: Math.round(p.left),
        y: Math.round(p.top)
    }));

    const thumbnail = canvas.toDataURL({ format: 'png', quality: 0.6 });

    const categoryIcon = {
        'forwards': '🟣',
        'backs': '🟢',
        'full_team': '⚪'
    }[playCategory] || '⚪';

    const ballData = rugbyBall ? {
        x: Math.round(rugbyBall.left),
        y: Math.round(rugbyBall.top),
        isBall: true
    } : null;

    const movementsData = movements.map(action => {
        if (action.type === 'movement') {
            return {
                type: 'movement',
                playerId: action.playerId,
                playerType: action.playerType,
                points: action.points,
                hasBall: action.hasBall || false,
                startDelay: action.startDelay || 0
            };
        } else if (action.type === 'pass') {
            return {
                type: 'pass',
                from: action.from,
                to: action.to,
                timing: action.timing
            };
        }
        return action;
    });

    // Guardar posiciones originales (para reset)
    const originalPositionsData = {};
    Object.keys(originalPositions).forEach(key => {
        originalPositionsData[key] = {
            left: originalPositions[key].left,
            top: originalPositions[key].top
        };
    });

    const play = {
        id: Date.now(),
        name: playName,
        category: playCategory,
        categoryIcon: categoryIcon,
        players: playersData,
        ball: ballData,
        ballPossession: ballPossession,
        originalBallHolder: originalBallHolder,
        originalPositions: originalPositionsData,
        movements: movementsData,
        thumbnail: thumbnail,
        created_at: new Date().toISOString()
    };

    let plays = JSON.parse(localStorage.getItem('rugbyPlays') || '[]');
    plays.push(play);
    localStorage.setItem('rugbyPlays', JSON.stringify(plays));

    $('#playNameInput').val('');
    loadPlays();

    alert(`✅ Jugada guardada: ${playName} (${categoryIcon} ${playCategory})`);
}

function loadPlays() {
    const plays = JSON.parse(localStorage.getItem('rugbyPlays') || '[]');
    const container = $('#savedPlaysList');

    if (plays.length === 0) {
        container.html('<p class="text-muted text-center small mb-0"><i class="fas fa-info-circle"></i> Sin jugadas</p>');
        $('#playCount').text('0');
        return;
    }

    $('#playCount').text(plays.length);

    let html = '';
    plays.reverse().forEach(play => {
        const date = new Date(play.created_at).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
        const categoryIcon = play.categoryIcon || '⚪';

        html += `
            <div class="saved-play-item">
                <strong>${categoryIcon} ${play.name}</strong><br>
                <small class="text-muted">
                    ${play.players.length} jug. · ${date}
                </small>
                <div class="mt-1">
                    <button class="btn btn-sm btn-info load-play" data-id="${play.id}" title="Cargar">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-play" data-id="${play.id}" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });

    container.html(html);
}

function loadPlayById(playId) {
    const plays = JSON.parse(localStorage.getItem('rugbyPlays') || '[]');
    const play = plays.find(p => p.id === playId);

    if (!play) {
        alert('❌ Jugada no encontrada');
        return;
    }

    clearAllPlayers();

    play.players.forEach(p => {
        addPlayer(p.type, p.x, p.y, p.number);
    });

    if (play.ball && play.ball.isBall) {
        crearBalon(play.ball.x, play.ball.y);
    }

    if (play.movements && play.movements.length > 0) {
        play.movements.forEach(action => {
            if (action.type === 'movement' || !action.type) {
                let color;
                if (action.playerType === 'ball') {
                    color = '#FF8C00';
                } else if (action.playerType === 'forward') {
                    color = '#1e4d2b';
                } else {
                    color = '#28a745';
                }

                const pathGroup = createPathWithArrow(action.points, color);
                if (pathGroup) {
                    canvas.add(pathGroup);
                    pathGroup.sendToBack();

                    movements.push({
                        id: ++movementIdCounter,
                        type: 'movement',
                        playerId: action.playerId,
                        playerType: action.playerType,
                        points: action.points,
                        hasBall: action.hasBall || false,
                        startDelay: action.startDelay || 0,
                        pathObject: pathGroup
                    });
                }
            } else if (action.type === 'pass') {
                movements.push({
                    id: ++movementIdCounter,
                    type: 'pass',
                    from: action.from,
                    to: action.to,
                    timing: action.timing
                });
            }
        });

        canvas.getObjects().forEach(obj => {
            if (obj.type === 'image') {
                obj.sendToBack();
            }
        });

        canvas.renderAll();
        updatePlayButton();
        updatePassButton();
        renderMovementsList();
    }

    if (play.ballPossession !== null && play.ballPossession !== undefined) {
        const originalHolder = play.originalBallHolder || play.ballPossession;
        const playerOriginal = players.find(p => p.playerNumber === originalHolder);
        if (playerOriginal && rugbyBall) {
            assignPossessionTo(playerOriginal);
        }

        if (play.ballPossession !== originalHolder) {
            ballPossession = play.ballPossession;
            const finalHolder = players.find(p => p.playerNumber === play.ballPossession);
            if (finalHolder) {
                finalHolder.hasBallPossession = true;
            }
        }
    }

    // Restaurar posiciones originales desde la jugada guardada
    if (play.originalPositions && Object.keys(play.originalPositions).length > 0) {
        // Usar las posiciones originales guardadas
        originalPositions = {};
        Object.keys(play.originalPositions).forEach(key => {
            originalPositions[key] = {
                left: play.originalPositions[key].left,
                top: play.originalPositions[key].top
            };
        });
        console.log('📍 Posiciones originales restauradas desde jugada guardada:', Object.keys(originalPositions).length, 'objetos');
    } else {
        // Jugadas antiguas sin originalPositions guardado - usar posiciones actuales
        saveOriginalPositions();
        console.log('📍 Jugada antigua - posiciones actuales guardadas como originales');
    }

    updatePossessionUI();

    $('#playNameInput').val(play.name);

    alert('✅ Jugada cargada: ' + play.name);
}

function deletePlayById(playId) {
    if (!confirm('¿Eliminar esta jugada?')) return;

    let plays = JSON.parse(localStorage.getItem('rugbyPlays') || '[]');
    plays = plays.filter(p => p.id !== playId);
    localStorage.setItem('rugbyPlays', JSON.stringify(plays));

    loadPlays();
}

function clearCanvas() {
    if (players.length === 0) {
        alert('⚠️ El canvas ya está vacío');
        return;
    }

    if (confirm('¿Limpiar toda la cancha y jugadores?')) {
        clearAllPlayers();
    }
}

console.log('📦 storage.js cargado');
