/**
 * animation.js - Sistema de animación y reproducción
 */

function saveOriginalPositions() {
    canvas.discardActiveObject();
    canvas.renderAll();

    originalPositions = {};

    players.forEach(p => {
        originalPositions[p.playerNumber] = {
            left: p.left,
            top: p.top
        };
    });

    if (rugbyBall) {
        originalPositions['ball'] = {
            left: rugbyBall.left,
            top: rugbyBall.top
        };
    }

    console.log('💾 Posiciones originales guardadas:', Object.keys(originalPositions).length, 'objetos');
}

function findObjectById(id) {
    if (id === 'ball') return rugbyBall;
    return players.find(p => p.playerNumber === id);
}

function animateObjectAlongPath(obj, points, duration, callback, hasBall = false) {
    if (!obj || points.length < 2) {
        if (callback) callback();
        return;
    }

    const totalPoints = points.length;
    const timePerSegment = duration / (totalPoints - 1);
    let currentPointIndex = 0;

    function animateToNextPoint() {
        if (currentPointIndex >= totalPoints - 1) {
            if (callback) callback();
            return;
        }

        const startPoint = points[currentPointIndex];
        const endPoint = points[currentPointIndex + 1];

        const distance = Math.sqrt(
            Math.pow(endPoint.x - startPoint.x, 2) +
            Math.pow(endPoint.y - startPoint.y, 2)
        );

        const segmentTime = Math.max(20, timePerSegment * (distance / 20));

        fabric.util.animate({
            startValue: 0,
            endValue: 1,
            duration: segmentTime,
            easing: fabric.util.ease.easeInOutQuad,
            onChange: function(value) {
                const newLeft = startPoint.x + (endPoint.x - startPoint.x) * value;
                const newTop = startPoint.y + (endPoint.y - startPoint.y) * value;

                obj.set({ left: newLeft, top: newTop });
                obj.setCoords();

                if (hasBall && rugbyBall) {
                    rugbyBall.set({
                        left: newLeft + BALL_OFFSET_X,
                        top: newTop + BALL_OFFSET_Y
                    });
                    rugbyBall.dirty = true;
                    rugbyBall.setCoords();
                }

                canvas.renderAll();
            },
            onComplete: function() {
                currentPointIndex++;
                animateToNextPoint();
            }
        });
    }

    animateToNextPoint();
}

function getMovementsAndPasses() {
    const movementsList = movements.filter(m => m.type === 'movement');
    const passesList = movements.filter(m => m.type === 'pass');
    return { movementsList, passesList };
}

function playAllMovements() {
    if (isPlaying || movements.length === 0) return;

    if (Object.keys(originalPositions).length === 0) {
        saveOriginalPositions();
    }

    isPlaying = true;

    const { movementsList, passesList } = getMovementsAndPasses();

    const firstPass = passesList[0];
    if (firstPass) {
        playbackBallHolder = firstPass.from;
        console.log(`🏈 Poseedor inicial (del primer pase): J${playbackBallHolder}`);
    } else {
        playbackBallHolder = ballPossession;
        console.log(`🏈 Poseedor inicial (actual): J${playbackBallHolder}`);
    }

    $('#btnPlay').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Reproduciendo...');
    $('#btnReset').prop('disabled', true);
    $('#btnDrawMovement').prop('disabled', true);
    $('#btnCreatePass').prop('disabled', true);

    console.log('▶️ Iniciando reproducción con ESCALONAMIENTO');
    console.log(`   📍 ${movementsList.length} movimientos`);
    console.log(`   🏈 ${passesList.length} pases programados`);

    $('#animationStatus').html(
        `<i class="fas fa-bolt text-warning"></i> Ejecutando ${movementsList.length} movimientos + ${passesList.length} pases...`
    );

    const PASS_DURATION = 800;

    // Pre-calcular duración de movimientos por jugador (usando velocidad individual)
    const movementDurations = {};
    movementsList.forEach(movement => {
        let totalDistance = 0;
        for (let i = 1; i < movement.points.length; i++) {
            totalDistance += Math.sqrt(
                Math.pow(movement.points[i].x - movement.points[i-1].x, 2) +
                Math.pow(movement.points[i].y - movement.points[i-1].y, 2)
            );
        }
        const speed = movement.speed || PLAYER_SPEED;
        const duration = (totalDistance / speed) * 1000;
        if (movementDurations[movement.playerId]) {
            movementDurations[movement.playerId] += duration;
        } else {
            movementDurations[movement.playerId] = duration;
        }
    });

    // ============================================
    // HÍBRIDO: Delays mínimos para que nadie esté detenido al recibir
    // Todos empiezan casi juntos, pero con ajustes mínimos
    // ============================================
    const movementStartDelays = {};  // Delays mínimos calculados
    const passArrivalTimes = {};     // Cuándo llega el balón a cada receptor

    // Primero: calcular cuándo llega cada pase (secuencialmente)
    let currentPassTime = 0;

    passesList.forEach((pass, index) => {
        const timing = Math.min(pass.timing || 50, 99) / 100;
        const passerDuration = movementDurations[pass.from] || ANIMATION_DURATION;

        if (index === 0) {
            // Primer pase: el pasador pasa en timing% de su movimiento
            currentPassTime = timing * passerDuration;
            movementStartDelays[pass.from] = 0;
            console.log(`   📊 J${pass.from}: pasa en t=${currentPassTime.toFixed(0)}ms`);
        } else {
            // Pases siguientes: después de recibir el anterior + pequeño delay
            const quickPassDelay = (1 - timing) * 200;
            currentPassTime = currentPassTime + PASS_DURATION + quickPassDelay;
        }

        // Cuándo llega el balón al receptor
        const arrivalTime = currentPassTime + PASS_DURATION;
        passArrivalTimes[pass.to] = arrivalTime;

        // Calcular delay mínimo para que el receptor no esté detenido
        const receiverDuration = movementDurations[pass.to] || ANIMATION_DURATION;

        // Si el receptor termina antes de que llegue el balón, necesita delay
        // delay = arrivalTime - duration (para terminar justo cuando llega el balón)
        // Agregamos un margen de 200ms para que siga en movimiento
        const minDelay = Math.max(0, arrivalTime - receiverDuration + 200);
        movementStartDelays[pass.to] = minDelay;

        console.log(`   📊 J${pass.to}: recibe en t=${arrivalTime.toFixed(0)}ms, dur=${receiverDuration.toFixed(0)}ms, delay=${minDelay.toFixed(0)}ms`);
    });

    // Jugadores sin pases empiezan en t=0
    movementsList.forEach(movement => {
        if (movementStartDelays[movement.playerId] === undefined) {
            movementStartDelays[movement.playerId] = 0;
        }
    });

    // ============================================
    // Programar ejecución de pases (SECUENCIALES)
    // Cada pase ocurre después de que el anterior se complete
    // ============================================
    let maxPassEndTime = 0;
    currentPassTime = 0;  // Resetear para programación

    passesList.forEach((pass, index) => {
        const timing = Math.min(pass.timing || 50, 99) / 100;
        const passerDuration = movementDurations[pass.from] || ANIMATION_DURATION;
        let passTime;

        if (index === 0) {
            // Primer pase: el pasador original pasa en timing% de su movimiento
            passTime = timing * passerDuration;
        } else {
            // Pases siguientes: ocurren después de recibir el pase anterior
            // El nuevo pasador pasa rápido (timing bajo = pasa más rápido después de recibir)
            const quickPassDelay = (1 - timing) * 300; // timing alto = pasa casi inmediato
            passTime = currentPassTime + PASS_DURATION + quickPassDelay;
        }

        currentPassTime = passTime;
        const passEndTime = passTime + PASS_DURATION;
        if (passEndTime > maxPassEndTime) {
            maxPassEndTime = passEndTime;
        }

        console.log(`      🏈 Pase ${pass.from}→${pass.to} programado para t=${passTime.toFixed(0)}ms`);

        setTimeout(() => {
            if (isPlaying) {
                console.log(`    🏈 Ejecutando pase ${pass.from}→${pass.to}`);
                executePassDuringAnimation(pass);
            }
        }, passTime);
    });

    // Calcular duración total de la animación
    let maxEndTime = 0;
    Object.keys(movementStartDelays).forEach(playerId => {
        const startDelay = movementStartDelays[playerId];
        const duration = movementDurations[playerId] || ANIMATION_DURATION;
        const endTime = startDelay + duration;
        if (endTime > maxEndTime) {
            maxEndTime = endTime;
        }
    });

    const totalDuration = Math.max(maxEndTime, maxPassEndTime) + 100;
    console.log(`   ⏱️ Duración total estimada: ${totalDuration.toFixed(0)}ms`);

    // Iniciar animaciones con escalonamiento
    playAllMovementsStaggered(movementsList, movementDurations, movementStartDelays, () => {
        setTimeout(() => {
            finishPlayback();
        }, Math.max(0, maxPassEndTime - maxEndTime + 100));
    });
}

function executePassDuringAnimation(passAction) {
    const fromPlayer = findObjectById(passAction.from);
    const toPlayer = findObjectById(passAction.to);

    if (!rugbyBall || !fromPlayer || !toPlayer) {
        console.error('❌ Error: Objetos no encontrados para el pase');
        return;
    }

    // Guardar posición inicial del balón
    const startX = rugbyBall.left;
    const startY = rugbyBall.top;

    fabric.util.animate({
        startValue: 0,
        endValue: 1,
        duration: 800,
        easing: fabric.util.ease.easeOutQuad,
        onChange: function(value) {
            // Obtener posición ACTUAL del receptor (que se está moviendo)
            const currentToCenter = toPlayer.getCenterPoint();

            // LEAD FACTOR: El balón apunta ADELANTE del receptor
            const leadFactor = (1 - value) * 20;

            // Target = centro del receptor + offset normal + lead
            const currentTargetX = currentToCenter.x + BALL_OFFSET_X + leadFactor;
            const currentTargetY = currentToCenter.y + BALL_OFFSET_Y;

            // Interpolación desde inicio hacia el target
            rugbyBall.set({
                left: startX + (currentTargetX - startX) * value,
                top: startY + (currentTargetY - startY) * value
            });
            rugbyBall.dirty = true;
            canvas.renderAll();
        },
        onComplete: function() {
            // Asegurar posición final exacta al frente del receptor
            const finalToCenter = toPlayer.getCenterPoint();
            rugbyBall.set({
                left: finalToCenter.x + BALL_OFFSET_X,
                top: finalToCenter.y + BALL_OFFSET_Y
            });

            playbackBallHolder = passAction.to;
            console.log(`    ✓ Pase completado → J${playbackBallHolder}`);
            canvas.renderAll();
        }
    });
}

/**
 * Nueva función: Ejecuta movimientos con escalonamiento calculado
 * Soporta SEÑUELOS: si un jugador tiene 2+ movimientos y está en cadena de pases,
 * el primer movimiento es señuelo (t=0) y el segundo espera el pase
 */
function playAllMovementsStaggered(movementsList, movementDurations, movementStartDelays, callback) {
    if (movementsList.length === 0) {
        callback();
        return;
    }

    // Agrupar movimientos por jugador
    const movementsByPlayer = {};
    movementsList.forEach(movement => {
        const id = movement.playerId;
        if (!movementsByPlayer[id]) {
            movementsByPlayer[id] = [];
        }
        movementsByPlayer[id].push(movement);
    });

    const animationPromises = [];

    Object.keys(movementsByPlayer).forEach(playerIdStr => {
        const playerMovements = movementsByPlayer[playerIdStr];
        const playerId = playerIdStr === 'ball' ? 'ball' : parseInt(playerIdStr);
        const obj = findObjectById(playerId);

        if (!obj) {
            console.warn('⚠️ Objeto no encontrado:', playerId);
            return;
        }

        // Obtener el delay de inicio calculado para este jugador
        const startDelay = movementStartDelays[playerId] || movementStartDelays[playerIdStr] || 0;

        // ============================================
        // SEÑUELO: Si tiene 2+ movimientos Y está en cadena de pases (delay > 0)
        // ============================================
        const isDecoy = playerMovements.length >= 2 && startDelay > 0;

        if (isDecoy) {
            // MODO SEÑUELO: Movimientos separados
            console.log(`    🎭 J${playerId}: SEÑUELO detectado (${playerMovements.length} movimientos)`);

            // Movimiento 1: Señuelo - empieza en t=0
            const decoyMovement = playerMovements[0];
            const decoyPoints = decoyMovement.points;
            const decoyDistance = calculatePathDistance(decoyPoints);
            const decoySpeed = decoyMovement.speed || PLAYER_SPEED;
            const decoyDuration = (decoyDistance / decoySpeed) * 1000;

            console.log(`       └─ Mov 1 (señuelo): inicio=0ms, dur=${decoyDuration.toFixed(0)}ms`);

            const decoyPromise = new Promise((resolve) => {
                animateObjectAlongPathUnified(obj, decoyPoints, decoyDuration, () => {
                    console.log(`    ✓ J${playerId} señuelo completado`);
                    resolve();
                }, playerId);
            });
            animationPromises.push(decoyPromise);

            // Movimiento 2+: Ataque - empieza cuando recibe el pase
            let attackPoints = [];
            for (let i = 1; i < playerMovements.length; i++) {
                if (i === 1) {
                    attackPoints = [...playerMovements[i].points];
                } else {
                    attackPoints = attackPoints.concat(playerMovements[i].points.slice(1));
                }
            }

            const attackDistance = calculatePathDistance(attackPoints);
            const attackSpeed = playerMovements[1].speed || PLAYER_SPEED;
            const attackDuration = (attackDistance / attackSpeed) * 1000;

            console.log(`       └─ Mov 2 (ataque): inicio=${startDelay.toFixed(0)}ms, dur=${attackDuration.toFixed(0)}ms`);

            const attackPromise = new Promise((resolve) => {
                setTimeout(() => {
                    animateObjectAlongPathUnified(obj, attackPoints, attackDuration, () => {
                        console.log(`    ✓ J${playerId} ataque completado`);
                        resolve();
                    }, playerId);
                }, startDelay);
            });
            animationPromises.push(attackPromise);

        } else {
            // MODO NORMAL: Combinar movimientos
            let allPoints = [];
            playerMovements.forEach((movement, idx) => {
                if (idx === 0) {
                    allPoints = [...movement.points];
                } else {
                    allPoints = allPoints.concat(movement.points.slice(1));
                }
            });

            const totalDistance = calculatePathDistance(allPoints);
            const playerSpeed = playerMovements[0].speed || PLAYER_SPEED;
            const movementDuration = (totalDistance / playerSpeed) * 1000;

            const speedLabel = { 100: '🐢', 200: '🏃', 300: '🏃‍♂️', 400: '⚡' }[playerSpeed] || '🏃';
            console.log(`    📏 J${playerId}: inicio=${startDelay.toFixed(0)}ms, dist=${totalDistance.toFixed(0)}px, dur=${movementDuration.toFixed(0)}ms ${speedLabel}`);

            const promise = new Promise((resolve) => {
                setTimeout(() => {
                    animateObjectAlongPathUnified(obj, allPoints, movementDuration, () => {
                        console.log(`    ✓ J${playerId} completado`);
                        resolve();
                    }, playerId);
                }, startDelay);
            });

            animationPromises.push(promise);
        }
    });

    Promise.all(animationPromises).then(() => {
        callback();
    });
}

function playAllMovementsSimultaneously(movementsList, callback) {
    if (movementsList.length === 0) {
        callback();
        return;
    }

    // Agrupar movimientos por jugador
    const movementsByPlayer = {};
    movementsList.forEach(movement => {
        const id = movement.playerId;
        if (!movementsByPlayer[id]) {
            movementsByPlayer[id] = [];
        }
        movementsByPlayer[id].push(movement);
    });

    const animationPromises = [];

    // Para cada jugador, encadenar sus movimientos secuencialmente
    Object.keys(movementsByPlayer).forEach(playerIdStr => {
        const playerMovements = movementsByPlayer[playerIdStr];
        // Convertir a número para comparaciones correctas (excepto 'ball')
        const playerId = playerIdStr === 'ball' ? 'ball' : parseInt(playerIdStr);
        const obj = findObjectById(playerId);

        if (!obj) {
            console.warn('⚠️ Objeto no encontrado:', playerId);
            return;
        }

        if (playerMovements.length === 1) {
            // Un solo movimiento: comportamiento normal
            const movement = playerMovements[0];
            const totalDistance = calculatePathDistance(movement.points);
            const playerSpeed = movement.speed || PLAYER_SPEED;
            const movementDuration = (totalDistance / playerSpeed) * 1000;
            const startDelay = (movement.startDelay || 0) / 100 * ANIMATION_DURATION;

            console.log(`    📏 J${playerId}: ${totalDistance.toFixed(0)}px, ${movementDuration.toFixed(0)}ms`);

            const promise = new Promise((resolve) => {
                setTimeout(() => {
                    animateObjectAlongPathUnified(obj, movement.points, movementDuration, () => {
                        console.log(`    ✓ J${playerId} completado`);
                        resolve();
                    }, playerId);
                }, startDelay);
            });

            animationPromises.push(promise);
        } else {
            // Múltiples movimientos: encadenar secuencialmente
            console.log(`    📏 J${playerId}: ${playerMovements.length} movimientos en secuencia`);

            const promise = new Promise((resolve) => {
                let movementIndex = 0;

                function executeNextMovement() {
                    if (movementIndex >= playerMovements.length) {
                        console.log(`    ✓ J${playerId} todos los movimientos completados`);
                        resolve();
                        return;
                    }

                    const movement = playerMovements[movementIndex];
                    const totalDistance = calculatePathDistance(movement.points);
                    const playerSpeed = movement.speed || PLAYER_SPEED;
                    const movementDuration = (totalDistance / playerSpeed) * 1000;

                    console.log(`      → J${playerId} mov ${movementIndex + 1}/${playerMovements.length}: ${totalDistance.toFixed(0)}px, ${movementDuration.toFixed(0)}ms`);

                    animateObjectAlongPathUnified(obj, movement.points, movementDuration, () => {
                        console.log(`      ✓ J${playerId} mov ${movementIndex + 1} completado`);
                        movementIndex++;
                        executeNextMovement();
                    }, playerId);
                }

                // Iniciar la cadena de movimientos
                executeNextMovement();
            });

            animationPromises.push(promise);
        }
    });

    Promise.all(animationPromises).then(() => {
        callback();
    });
}

function calculatePathDistance(points) {
    let totalDistance = 0;
    for (let i = 1; i < points.length; i++) {
        totalDistance += Math.sqrt(
            Math.pow(points[i].x - points[i-1].x, 2) +
            Math.pow(points[i].y - points[i-1].y, 2)
        );
    }
    return totalDistance;
}

function animateObjectAlongPathUnified(obj, points, duration, callback, playerId) {
    if (!obj || points.length < 2) {
        if (callback) callback();
        return;
    }

    let totalDistance = 0;
    for (let i = 1; i < points.length; i++) {
        totalDistance += Math.sqrt(
            Math.pow(points[i].x - points[i-1].x, 2) +
            Math.pow(points[i].y - points[i-1].y, 2)
        );
    }

    const startTime = Date.now();

    function animate() {
        const now = Date.now();
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const targetDistance = progress * totalDistance;

        let accumulatedDistance = 0;
        let currentPoint = points[0];

        for (let i = 1; i < points.length; i++) {
            const segmentDistance = Math.sqrt(
                Math.pow(points[i].x - points[i-1].x, 2) +
                Math.pow(points[i].y - points[i-1].y, 2)
            );

            if (accumulatedDistance + segmentDistance >= targetDistance) {
                const segmentProgress = (targetDistance - accumulatedDistance) / segmentDistance;
                currentPoint = {
                    x: points[i-1].x + (points[i].x - points[i-1].x) * segmentProgress,
                    y: points[i-1].y + (points[i].y - points[i-1].y) * segmentProgress
                };
                break;
            }

            accumulatedDistance += segmentDistance;
            currentPoint = points[i];
        }

        obj.set({ left: currentPoint.x, top: currentPoint.y });
        obj.setCoords();

        if (playerId === playbackBallHolder && rugbyBall) {
            rugbyBall.set({
                left: currentPoint.x + BALL_OFFSET_X,
                top: currentPoint.y + BALL_OFFSET_Y
            });
            rugbyBall.dirty = true;
            rugbyBall.setCoords();
        }

        canvas.renderAll();

        if (progress < 1) {
            requestAnimationFrame(animate);
        } else {
            if (callback) callback();
        }
    }

    requestAnimationFrame(animate);
}

function finishPlayback() {
    isPlaying = false;
    currentAnimationIndex = 0;

    $('#btnPlay').prop('disabled', false).html('<i class="fas fa-play"></i> Play');
    $('#btnReset').prop('disabled', false);
    updatePlayButton();
    updatePassButton();

    $('#animationStatus').html('<i class="fas fa-check-circle text-success"></i> Reproducción completa');
    console.log('✅ Reproducción completada');

    setTimeout(() => {
        if (!isPlaying) {
            $('#animationStatus').html('<i class="fas fa-info-circle"></i> Selecciona jugador/balón primero');
        }
    }, 2000);
}

function resetToOriginalPositions() {
    if (isPlaying) {
        alert('⚠️ Espera a que termine la reproducción');
        return;
    }

    canvas.discardActiveObject();
    canvas.renderAll();

    if (Object.keys(originalPositions).length === 0) {
        if (players.length === 0) {
            alert('⚠️ No hay jugadores en el canvas');
            return;
        }
        saveOriginalPositions();
        console.log('💾 Posiciones actuales guardadas como referencia');
        $('#animationStatus').html('<i class="fas fa-save text-info"></i> Posiciones guardadas como referencia');
        setTimeout(() => {
            $('#animationStatus').html('<i class="fas fa-info-circle"></i> Selecciona jugador/balón primero');
        }, 1500);
        return;
    }

    console.log('⟲ Restaurando posiciones originales...');
    let restoredCount = 0;

    players.forEach(p => {
        const origPos = originalPositions[p.playerNumber];
        if (origPos) {
            console.log(`  └─ Jugador ${p.playerNumber}: (${p.left.toFixed(0)},${p.top.toFixed(0)}) → (${origPos.left.toFixed(0)},${origPos.top.toFixed(0)})`);
            p.set({ left: origPos.left, top: origPos.top });
            p.dirty = true;
            p.setCoords();
            restoredCount++;
        }
    });

    if (rugbyBall) {
        if (originalBallHolder !== null) {
            const holder = players.find(p => p.playerNumber === originalBallHolder);
            if (holder) {
                const holderOrigPos = originalPositions[holder.playerNumber];
                if (holderOrigPos) {
                    const newBallLeft = holderOrigPos.left + BALL_OFFSET_X;
                    const newBallTop = holderOrigPos.top + BALL_OFFSET_Y;
                    console.log(`  └─ Balón (con J${originalBallHolder}): (${rugbyBall.left.toFixed(0)},${rugbyBall.top.toFixed(0)}) → (${newBallLeft.toFixed(0)},${newBallTop.toFixed(0)})`);
                    rugbyBall.set({ left: newBallLeft, top: newBallTop });
                    rugbyBall.dirty = true;
                    rugbyBall.setCoords();
                    restoredCount++;

                    // Recalcular posesión según cadena de pases (para permitir fases como ruck)
                    recalculateBallPossession();

                    // Actualizar UI del jugador con posesión
                    players.forEach(p => p.hasBallPossession = (p.playerNumber === ballPossession));
                    updatePossessionUI();
                }
            }
        } else if (originalPositions['ball']) {
            const ballOrig = originalPositions['ball'];
            console.log(`  └─ Balón (libre): (${rugbyBall.left.toFixed(0)},${rugbyBall.top.toFixed(0)}) → (${ballOrig.left.toFixed(0)},${ballOrig.top.toFixed(0)})`);
            rugbyBall.set({ left: ballOrig.left, top: ballOrig.top });
            rugbyBall.dirty = true;
            rugbyBall.setCoords();
            restoredCount++;
        }
    }

    canvas.requestRenderAll();
    canvas.renderAll();

    console.log(`✅ ${restoredCount} objetos restaurados`);

    $('#animationStatus').html(`<i class="fas fa-undo text-success"></i> ${restoredCount} posiciones restauradas`);
    setTimeout(() => {
        $('#animationStatus').html('<i class="fas fa-info-circle"></i> Selecciona jugador/balón primero');
    }, 1500);
}

function updatePlayButton() {
    const hasMovements = movements.length > 0;
    $('#btnPlay').prop('disabled', !hasMovements || isPlaying);
}

console.log('📦 animation.js cargado');
