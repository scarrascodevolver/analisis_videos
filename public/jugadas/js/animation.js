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

    console.log('▶️ Iniciando reproducción UNIFICADA');
    console.log(`   📍 ${movementsList.length} movimientos simultáneos`);
    console.log(`   🏈 ${passesList.length} pases programados`);


    $('#animationStatus').html(
        `<i class="fas fa-bolt text-warning"></i> Ejecutando ${movementsList.length} movimientos + ${passesList.length} pases...`
    );

    const PASS_DURATION = 800;
    let lastTiming = -1;
    let sameTimingCount = 0;
    let maxPassEndTime = 0;

    // Pre-calcular duración de cada movimiento
    const movementDurations = {};
    movementsList.forEach(movement => {
        let totalDistance = 0;
        for (let i = 1; i < movement.points.length; i++) {
            totalDistance += Math.sqrt(
                Math.pow(movement.points[i].x - movement.points[i-1].x, 2) +
                Math.pow(movement.points[i].y - movement.points[i-1].y, 2)
            );
        }
        movementDurations[movement.playerId] = (totalDistance / PLAYER_SPEED) * 1000;
    });

    passesList.forEach((pass, index) => {
        const timing = Math.min(pass.timing || 50, 99);

        if (timing === lastTiming) {
            sameTimingCount++;
        } else {
            sameTimingCount = 0;
            lastTiming = timing;
        }

        // Usar la duración del movimiento del jugador que PASA (from)
        const passerDuration = movementDurations[pass.from] || ANIMATION_DURATION;
        const baseDelay = (timing / 100) * passerDuration;
        const sequenceOffset = sameTimingCount * (PASS_DURATION + 100);
        const delay = baseDelay + sequenceOffset;

        // Calcular cuándo termina este pase (delay + duración)
        const passEndTime = delay + PASS_DURATION;
        if (passEndTime > maxPassEndTime) {
            maxPassEndTime = passEndTime;
        }

        console.log(`      Pase ${pass.from}→${pass.to}: timing ${timing}% de ${passerDuration.toFixed(0)}ms = delay ${delay.toFixed(0)}ms`);

        setTimeout(() => {
            if (isPlaying) {
                console.log(`    🏈 Ejecutando pase ${pass.from}→${pass.to} (al ${timing}%)`);
                executePassDuringAnimation(pass);
            }
        }, delay);
    });

    // Calcular duración máxima de movimientos (solo los reales, no ANIMATION_DURATION)
    const durationValues = Object.values(movementDurations);
    const maxMovementDuration = durationValues.length > 0 ? Math.max(...durationValues) : ANIMATION_DURATION;

    // Calcular tiempo extra: cuánto más allá de los movimientos necesitamos esperar para los pases
    const extraTimeForPasses = Math.max(0, maxPassEndTime - maxMovementDuration + 100);

    console.log(`   ⏱️ maxMovementDuration: ${maxMovementDuration.toFixed(0)}ms, maxPassEndTime: ${maxPassEndTime.toFixed(0)}ms, extra: ${extraTimeForPasses.toFixed(0)}ms`);

    playAllMovementsSimultaneously(movementsList, () => {
        setTimeout(() => {
            finishPlayback();
        }, extraTimeForPasses);
    });
}

function executePassDuringAnimation(passAction) {
    const fromPlayer = findObjectById(passAction.from);
    const toPlayer = findObjectById(passAction.to);

    if (!rugbyBall || !fromPlayer || !toPlayer) {
        console.error('❌ Error: Objetos no encontrados para el pase');
        return;
    }

    const fromCenter = fromPlayer.getCenterPoint();
    const toCenter = toPlayer.getCenterPoint();

    const passLine = new fabric.Line([
        fromCenter.x, fromCenter.y,
        toCenter.x, toCenter.y
    ], {
        stroke: '#0066CC',
        strokeWidth: 3,
        strokeDashArray: [8, 4],
        selectable: false,
        evented: false,
        opacity: 0.9
    });

    const angle = Math.atan2(toCenter.y - fromCenter.y, toCenter.x - fromCenter.x);
    const arrowHead = new fabric.Triangle({
        left: toCenter.x,
        top: toCenter.y,
        fill: '#0066CC',
        width: 15,
        height: 18,
        angle: (angle * 180 / Math.PI) + 90,
        originX: 'center',
        originY: 'center',
        selectable: false,
        evented: false,
        opacity: 0.9
    });

    canvas.add(passLine, arrowHead);
    canvas.renderAll();

    // Guardar posición inicial del balón y target inicial
    const startX = rugbyBall.left;
    const startY = rugbyBall.top;
    const initialTargetX = toCenter.x + BALL_OFFSET_X;
    const initialTargetY = toCenter.y + BALL_OFFSET_Y;

    // Calcular offset inicial (distancia del balón al target inicial)
    const initialOffsetX = startX - initialTargetX;
    const initialOffsetY = startY - initialTargetY;

    fabric.util.animate({
        startValue: 0,
        endValue: 1,
        duration: 800,
        easing: fabric.util.ease.easeOutQuad,
        onChange: function(value) {
            // Obtener posición ACTUAL del receptor (que se está moviendo)
            const currentToCenter = toPlayer.getCenterPoint();

            // LEAD FACTOR: El balón apunta ADELANTE del receptor
            // Esto compensa el movimiento del receptor durante el pase
            // Se reduce a 0 al final para llegar exacto al target
            const leadFactor = (1 - value) * 20;

            // Target = centro del receptor + offset normal + lead
            const currentTargetX = currentToCenter.x + BALL_OFFSET_X + leadFactor;
            const currentTargetY = currentToCenter.y + BALL_OFFSET_Y;

            // Interpolación simple desde inicio hacia el target con lead
            rugbyBall.set({
                left: startX + (currentTargetX - startX) * value,
                top: startY + (currentTargetY - startY) * value
            });
            rugbyBall.dirty = true;

            // Actualizar también la línea visual del pase
            const currentFromCenter = fromPlayer.getCenterPoint();
            passLine.set({
                x1: currentFromCenter.x,
                y1: currentFromCenter.y,
                x2: currentToCenter.x,
                y2: currentToCenter.y
            });

            // Actualizar flecha
            const newAngle = Math.atan2(
                currentToCenter.y - currentFromCenter.y,
                currentToCenter.x - currentFromCenter.x
            );
            arrowHead.set({
                left: currentToCenter.x,
                top: currentToCenter.y,
                angle: (newAngle * 180 / Math.PI) + 90
            });

            canvas.renderAll();
        },
        onComplete: function() {
            canvas.remove(passLine);
            canvas.remove(arrowHead);

            // Asegurar posición final exacta al frente del receptor
            const finalToCenter = toPlayer.getCenterPoint();
            rugbyBall.set({
                left: finalToCenter.x + BALL_OFFSET_X,
                top: finalToCenter.y + BALL_OFFSET_Y
            });

            playbackBallHolder = passAction.to;
            console.log(`    ✓ Pase completado → J${playbackBallHolder}`);
            console.log(`    📍 Balón en: (${rugbyBall.left.toFixed(0)}, ${rugbyBall.top.toFixed(0)})`);
            console.log(`    👤 Receptor en: (${finalToCenter.x.toFixed(0)}, ${finalToCenter.y.toFixed(0)})`);
            canvas.renderAll();
        }
    });
}

function playAllMovementsSimultaneously(movementsList, callback) {
    if (movementsList.length === 0) {
        callback();
        return;
    }

    const animationPromises = [];
    let maxEndTime = 0;

    movementsList.forEach(movement => {
        const obj = findObjectById(movement.playerId);
        if (!obj) {
            console.warn('⚠️ Objeto no encontrado:', movement.playerId);
            return;
        }

        // Calcular distancia total de la trayectoria
        let totalDistance = 0;
        for (let i = 1; i < movement.points.length; i++) {
            totalDistance += Math.sqrt(
                Math.pow(movement.points[i].x - movement.points[i-1].x, 2) +
                Math.pow(movement.points[i].y - movement.points[i-1].y, 2)
            );
        }

        // Duración basada en velocidad constante (distancia / velocidad)
        const movementDuration = (totalDistance / PLAYER_SPEED) * 1000; // en ms

        // Delay de inicio (% del tiempo base de animación)
        const startDelay = (movement.startDelay || 0) / 100 * ANIMATION_DURATION;

        // Calcular cuándo termina este movimiento
        const endTime = startDelay + movementDuration;
        if (endTime > maxEndTime) {
            maxEndTime = endTime;
        }

        console.log(`    📏 J${movement.playerId}: ${totalDistance.toFixed(0)}px, ${movementDuration.toFixed(0)}ms, delay ${startDelay.toFixed(0)}ms`);

        const promise = new Promise((resolve) => {
            // Aplicar delay de inicio
            setTimeout(() => {
                animateObjectAlongPathUnified(obj, movement.points, movementDuration, () => {
                    console.log(`    ✓ J${movement.playerId} completado`);
                    resolve();
                }, movement.playerId);
            }, startDelay);
        });

        animationPromises.push(promise);
    });

    Promise.all(animationPromises).then(() => {
        callback();
    });
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

                    ballPossession = originalBallHolder;
                    players.forEach(p => p.hasBallPossession = false);
                    holder.hasBallPossession = true;
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
