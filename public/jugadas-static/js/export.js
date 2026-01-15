/**
 * export.js - Exportar jugadas a video MP4
 */

let mediaRecorder = null;
let recordedChunks = [];
let isRecording = false;
let currentExportName = '';
let exportResolve = null;

// Variable para saber si grabamos en MP4 nativo (Safari)
let recordingMimeType = '';

/**
 * Inicia la grabación del canvas
 */
function startRecording() {
    const canvasElement = document.getElementById('playCanvas');

    if (!canvasElement) {
        console.error('Canvas no encontrado');
        return false;
    }

    // Obtener stream del canvas a 30fps
    const stream = canvasElement.captureStream(30);

    // Detectar formato soportado
    // Safari soporta MP4 nativo, Chrome/Firefox soportan WebM
    let mimeType = '';

    // Intentar WebM primero (Chrome, Firefox)
    if (MediaRecorder.isTypeSupported('video/webm;codecs=vp9')) {
        mimeType = 'video/webm;codecs=vp9';
    } else if (MediaRecorder.isTypeSupported('video/webm;codecs=vp8')) {
        mimeType = 'video/webm;codecs=vp8';
    } else if (MediaRecorder.isTypeSupported('video/webm')) {
        mimeType = 'video/webm';
    } else if (MediaRecorder.isTypeSupported('video/mp4')) {
        // Safari iOS/macOS - graba en MP4 nativo
        mimeType = 'video/mp4';
    }

    if (!mimeType) {
        console.error('No se encontró formato de video soportado');
        return false;
    }

    recordingMimeType = mimeType;
    recordedChunks = [];

    try {
        mediaRecorder = new MediaRecorder(stream, {
            mimeType: mimeType,
            videoBitsPerSecond: 3000000 // 3 Mbps para buena calidad
        });
    } catch (e) {
        console.error('Error creando MediaRecorder:', e);
        return false;
    }

    mediaRecorder.ondataavailable = function(event) {
        if (event.data.size > 0) {
            recordedChunks.push(event.data);
        }
    };

    mediaRecorder.onstop = function() {
        if (exportResolve) {
            const blob = new Blob(recordedChunks, { type: 'video/webm' });
            exportResolve(blob);
            exportResolve = null;
        }
    };

    mediaRecorder.onerror = function(event) {
        console.error('Error en MediaRecorder:', event.error);
        isRecording = false;
        if (exportResolve) {
            exportResolve(null);
            exportResolve = null;
        }
    };

    mediaRecorder.start(100);
    isRecording = true;
    return true;
}

/**
 * Detiene la grabación y retorna una promesa con el blob
 */
function stopRecording() {
    return new Promise((resolve) => {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            exportResolve = resolve;
            mediaRecorder.stop();
            isRecording = false;
        } else {
            resolve(null);
        }
    });
}

/**
 * Verifica si el formato grabado es MP4 nativo (Safari)
 */
function isNativeMp4() {
    return recordingMimeType && recordingMimeType.includes('mp4');
}

/**
 * Descarga el video directamente (para Safari que ya graba en MP4)
 */
function downloadDirectly(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.mp4';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    return { success: true, filename: filename + '.mp4', size: blob.size };
}

/**
 * Convierte WebM a MP4 en el servidor
 */
async function convertToMp4(webmBlob, filename) {
    const formData = new FormData();
    formData.append('video', webmBlob, 'video.webm');
    formData.append('filename', filename);

    try {
        const response = await fetch('/api/jugadas/convert-to-mp4', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Convertir base64 a blob y descargar
            const byteCharacters = atob(data.video);
            const byteNumbers = new Array(byteCharacters.length);
            for (let i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            const mp4Blob = new Blob([byteArray], { type: 'video/mp4' });

            // Descargar
            const url = URL.createObjectURL(mp4Blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            return { success: true, filename: data.filename, size: data.size };
        } else {
            return { success: false, error: data.message };
        }
    } catch (error) {
        console.error('Error convirtiendo a MP4:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Exporta una jugada por su ID
 */
async function exportPlayById(playId, playName) {
    // Buscar jugada en cache
    const play = jugadasCache.find(p => p.id === playId);

    if (!play || !play.data) {
        alert('❌ Jugada no encontrada');
        return;
    }

    if (!play.data.movements || play.data.movements.length === 0) {
        alert('⚠️ Esta jugada no tiene movimientos para exportar');
        return;
    }

    if (isPlaying || isRecording) {
        alert('⚠️ Espera a que termine la operación actual');
        return;
    }

    // Deshabilitar botón y mostrar progreso
    const $btn = $(`.export-play[data-id="${playId}"]`);
    const originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $('#animationStatus').html('<i class="fas fa-spinner fa-spin text-warning"></i> Preparando exportación...');

    currentExportName = playName || 'jugada';

    try {
        // 1. Cargar la jugada (sin mostrar alert)
        await loadPlayForExport(play);

        // 2. Esperar que el canvas se renderice
        await sleep(300);

        // 3. Reset a posiciones originales
        resetToOriginalPositionsSync();
        await sleep(200);

        // 4. Iniciar grabación
        $('#animationStatus').html('<i class="fas fa-circle text-danger"></i> Grabando...');
        if (!startRecording()) {
            throw new Error('No se pudo iniciar la grabación');
        }

        // 5. Esperar un momento para capturar estado inicial
        await sleep(500);

        // 6. Ejecutar animación y esperar que termine
        await playAnimationAndWait();

        // 7. Esperar un momento para capturar estado final
        await sleep(500);

        // 8. Detener grabación y obtener blob
        const videoBlob = await stopRecording();

        if (!videoBlob) {
            throw new Error('No se pudo grabar el video');
        }

        let result;

        // 9. Si es Safari (MP4 nativo), descargar directamente sin conversión
        if (isNativeMp4()) {
            $('#animationStatus').html('<i class="fas fa-download text-info"></i> Descargando MP4...');
            result = downloadDirectly(videoBlob, currentExportName);
        } else {
            // Chrome/Firefox: Convertir WebM a MP4 en el servidor
            $('#animationStatus').html('<i class="fas fa-cog fa-spin text-info"></i> Convirtiendo a MP4...');
            result = await convertToMp4(videoBlob, currentExportName);
        }

        if (result.success) {
            const sizeMB = (result.size / 1024 / 1024).toFixed(2);
            $('#animationStatus').html(`<i class="fas fa-check-circle text-success"></i> Exportado: ${result.filename} (${sizeMB} MB)`);
            console.log(`✅ Video exportado: ${result.filename}`);
        } else {
            throw new Error(result.error || 'Error en la conversión');
        }

    } catch (error) {
        console.error('Error exportando:', error);
        $('#animationStatus').html(`<i class="fas fa-exclamation-triangle text-danger"></i> Error: ${error.message}`);
        alert('❌ Error al exportar: ' + error.message);
    } finally {
        $btn.prop('disabled', false).html(originalHtml);
        isRecording = false;
    }
}

/**
 * Carga una jugada sin mostrar alert
 */
function loadPlayForExport(play) {
    return new Promise((resolve) => {
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

        // Restaurar posiciones originales
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

        resolve();
    });
}

/**
 * Reset síncrono para exportación
 */
function resetToOriginalPositionsSync() {
    if (Object.keys(originalPositions).length === 0) return;

    canvas.discardActiveObject();

    players.forEach(p => {
        const origPos = originalPositions[p.playerNumber];
        if (origPos) {
            p.set({ left: origPos.left, top: origPos.top });
            p.dirty = true;
            p.setCoords();
        }
    });

    if (rugbyBall && originalBallHolder !== null) {
        const holder = players.find(p => p.playerNumber === originalBallHolder);
        if (holder) {
            const holderOrigPos = originalPositions[holder.playerNumber];
            if (holderOrigPos) {
                rugbyBall.set({
                    left: holderOrigPos.left + BALL_OFFSET_X,
                    top: holderOrigPos.top + BALL_OFFSET_Y
                });
                rugbyBall.dirty = true;
                rugbyBall.setCoords();
            }
        }
    } else if (rugbyBall && originalPositions['ball']) {
        const ballOrig = originalPositions['ball'];
        rugbyBall.set({ left: ballOrig.left, top: ballOrig.top });
        rugbyBall.dirty = true;
        rugbyBall.setCoords();
    }

    if (typeof recalculateBallPossession === 'function') {
        recalculateBallPossession();
    }

    canvas.renderAll();
}

/**
 * Ejecuta la animación y espera a que termine
 */
function playAnimationAndWait() {
    return new Promise((resolve) => {
        if (movements.length === 0) {
            resolve();
            return;
        }

        // Guardar callback original
        const originalFinishPlayback = window.finishPlayback;

        // Override temporal
        window.finishPlayback = function() {
            window.finishPlayback = originalFinishPlayback;
            if (originalFinishPlayback) {
                originalFinishPlayback();
            }
            resolve();
        };

        // Iniciar animación
        playAllMovements();
    });
}

/**
 * Helper para esperar
 */
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Verificar si el navegador soporta exportación
 */
function canExportVideo() {
    return window.MediaRecorder && MediaRecorder.isTypeSupported('video/webm');
}

// Verificar soporte al cargar
$(document).ready(function() {
    if (!canExportVideo()) {
        $('.export-play').prop('disabled', true).attr('title', 'Tu navegador no soporta exportación de video');
    }
});

console.log('📦 export.js cargado (MP4 Server Conversion)');
