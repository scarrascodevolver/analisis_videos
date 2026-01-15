/**
 * storage.js - Guardar y cargar jugadas (API Backend)
 */

// Cache de jugadas cargadas
let jugadasCache = [];

function getCSRFToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

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

    // Datos de jugadores
    const playersData = players.map(p => ({
        number: p.playerNumber,
        type: p.playerType,
        x: Math.round(p.left),
        y: Math.round(p.top)
    }));

    // Thumbnail
    const thumbnail = canvas.toDataURL({ format: 'png', quality: 0.6 });

    // Datos del balón
    const ballData = rugbyBall ? {
        x: Math.round(rugbyBall.left),
        y: Math.round(rugbyBall.top),
        isBall: true
    } : null;

    // Datos de movimientos
    const movementsData = movements.map(action => {
        if (action.type === 'movement') {
            return {
                type: 'movement',
                playerId: action.playerId,
                playerType: action.playerType,
                points: action.points,
                hasBall: action.hasBall || false,
                startDelay: action.startDelay || 0,
                speed: action.speed || PLAYER_SPEED
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

    // Datos completos de la jugada
    const playData = {
        players: playersData,
        ball: ballData,
        ballPossession: ballPossession,
        originalBallHolder: originalBallHolder,
        originalPositions: originalPositionsData,
        movements: movementsData
    };

    // Deshabilitar botón mientras guarda
    const $btnSave = $('#btnSavePlay');
    $btnSave.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

    // Enviar al backend
    $.ajax({
        url: '/api/jugadas',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCSRFToken(),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        data: JSON.stringify({
            name: playName,
            category: playCategory,
            data: playData,
            thumbnail: thumbnail
        }),
        success: function(response) {
            if (response.success) {
                $('#playNameInput').val('');
                loadPlays();
                alert(`✅ Jugada guardada: ${playName}`);
            } else {
                alert('❌ Error al guardar: ' + (response.message || 'Error desconocido'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error guardando jugada:', xhr.status, error);
            let errorMsg = 'Error al guardar la jugada';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.status === 419) {
                errorMsg = 'Token CSRF expirado. Recarga la página.';
            } else if (xhr.status === 401) {
                errorMsg = 'Sesión expirada. Inicia sesión nuevamente.';
            } else if (xhr.status === 500) {
                errorMsg = 'Error interno del servidor.';
            }
            alert('❌ ' + errorMsg);
        },
        complete: function() {
            $btnSave.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
        }
    });
}

function loadPlays() {
    const container = $('#savedPlaysList');
    container.html('<p class="text-muted text-center small mb-0"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>');

    $.ajax({
        url: '/api/jugadas',
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': getCSRFToken(),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                jugadasCache = response.jugadas;
                renderPlaysList(response.jugadas);
            } else {
                container.html('<p class="text-danger text-center small mb-0"><i class="fas fa-exclamation-circle"></i> Error al cargar</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error cargando jugadas:', xhr.status, error);
            container.html('<p class="text-danger text-center small mb-0"><i class="fas fa-exclamation-circle"></i> Error de conexión</p>');
        }
    });
}

function renderPlaysList(jugadas) {
    const container = $('#savedPlaysList');

    if (!jugadas || jugadas.length === 0) {
        container.html('<p class="text-muted text-center small mb-0"><i class="fas fa-info-circle"></i> Sin jugadas</p>');
        $('#playCount').text('0');
        return;
    }

    $('#playCount').text(jugadas.length);

    let html = '';
    jugadas.forEach(play => {
        const categoryIcon = play.categoryIcon || '⚪';
        const dateStr = play.created_at || '';

        html += `
            <div class="saved-play-item">
                <strong>${categoryIcon} ${play.name}</strong><br>
                <small class="text-muted">
                    ${play.data.players ? play.data.players.length : 0} jug. · ${play.user} · ${dateStr}
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
    // Buscar en cache
    const play = jugadasCache.find(p => p.id === playId);

    if (!play || !play.data) {
        alert('❌ Jugada no encontrada');
        return;
    }

    const data = play.data;

    // Limpiar canvas
    clearAllPlayers();

    // Restaurar jugadores
    if (data.players) {
        data.players.forEach(p => {
            addPlayer(p.type, p.x, p.y, p.number);
        });
    }

    // Restaurar balón
    if (data.ball && data.ball.isBall) {
        crearBalon(data.ball.x, data.ball.y);
    }

    // Restaurar movimientos
    if (data.movements && data.movements.length > 0) {
        data.movements.forEach(action => {
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
                        speed: action.speed || PLAYER_SPEED,
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

    // Restaurar posesión del balón
    if (data.ballPossession !== null && data.ballPossession !== undefined) {
        const originalHolder = data.originalBallHolder || data.ballPossession;
        const playerOriginal = players.find(p => p.playerNumber === originalHolder);
        if (playerOriginal && rugbyBall) {
            assignPossessionTo(playerOriginal);
        }

        if (data.ballPossession !== originalHolder) {
            ballPossession = data.ballPossession;
            const finalHolder = players.find(p => p.playerNumber === data.ballPossession);
            if (finalHolder) {
                finalHolder.hasBallPossession = true;
            }
        }
    }

    // Restaurar posiciones originales desde la jugada guardada
    if (data.originalPositions && Object.keys(data.originalPositions).length > 0) {
        originalPositions = {};
        Object.keys(data.originalPositions).forEach(key => {
            originalPositions[key] = {
                left: data.originalPositions[key].left,
                top: data.originalPositions[key].top
            };
        });
    } else {
        saveOriginalPositions();
    }

    updatePossessionUI();
    $('#playNameInput').val(play.name);

    alert('✅ Jugada cargada: ' + play.name);
}

function deletePlayById(playId) {
    if (!confirm('¿Eliminar esta jugada?')) return;

    $.ajax({
        url: '/api/jugadas/' + playId,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': getCSRFToken()
        },
        success: function(response) {
            if (response.success) {
                loadPlays();
            } else {
                alert('❌ Error al eliminar');
            }
        },
        error: function(xhr) {
            console.error('Error eliminando jugada:', xhr.status);
            alert('❌ Error al eliminar la jugada');
        }
    });
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

console.log('📦 storage.js cargado (API Backend)');
