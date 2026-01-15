/**
 * export.js - Exportar jugadas a video (WebM/MP4)
 */

let mediaRecorder = null;
let recordedChunks = [];
let isRecording = false;
let exportCallback = null;

/**
 * Inicia la grabación del canvas
 */
function startRecording() {
    const canvasElement = document.getElementById('playCanvas');

    if (!canvasElement) {
        console.error('❌ Canvas no encontrado');
        return false;
    }

    // Obtener stream del canvas a 30fps
    const stream = canvasElement.captureStream(30);

    // Detectar formato soportado
    let mimeType = 'video/webm;codecs=vp9';
    if (!MediaRecorder.isTypeSupported(mimeType)) {
        mimeType = 'video/webm;codecs=vp8';
        if (!MediaRecorder.isTypeSupported(mimeType)) {
            mimeType = 'video/webm';
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/mp4';
                if (!MediaRecorder.isTypeSupported(mimeType)) {
                    alert('❌ Tu navegador no soporta grabación de video');
                    return false;
                }
            }
        }
    }

    console.log('🎬 Iniciando grabación con:', mimeType);

    recordedChunks = [];

    try {
        mediaRecorder = new MediaRecorder(stream, {
            mimeType: mimeType,
            videoBitsPerSecond: 2500000 // 2.5 Mbps para buena calidad
        });
    } catch (e) {
        console.error('❌ Error creando MediaRecorder:', e);
        alert('❌ Error al iniciar grabación: ' + e.message);
        return false;
    }

    mediaRecorder.ondataavailable = function(event) {
        if (event.data.size > 0) {
            recordedChunks.push(event.data);
        }
    };

    mediaRecorder.onstop = function() {
        console.log('🎬 Grabación detenida, procesando...');
        downloadRecording();
    };

    mediaRecorder.onerror = function(event) {
        console.error('❌ Error en MediaRecorder:', event.error);
        isRecording = false;
    };

    mediaRecorder.start(100); // Capturar datos cada 100ms
    isRecording = true;

    return true;
}

/**
 * Detiene la grabación
 */
function stopRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        isRecording = false;
        console.log('🛑 Grabación finalizada');
    }
}

/**
 * Descarga el video grabado
 */
function downloadRecording() {
    if (recordedChunks.length === 0) {
        console.error('❌ No hay datos grabados');
        alert('❌ No se grabó ningún dato');
        return;
    }

    const mimeType = mediaRecorder.mimeType || 'video/webm';
    const blob = new Blob(recordedChunks, { type: mimeType });

    // Determinar extensión
    let extension = 'webm';
    if (mimeType.includes('mp4')) {
        extension = 'mp4';
    }

    // Generar nombre con fecha
    const playName = $('#playNameInput').val().trim() || 'jugada';
    const date = new Date().toISOString().slice(0, 10);
    const filename = `${playName}_${date}.${extension}`;

    // Crear URL y descargar
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    console.log(`✅ Video descargado: ${filename} (${(blob.size / 1024 / 1024).toFixed(2)} MB)`);

    // Restaurar UI
    $('#btnExportVideo').prop('disabled', false).html('<i class="fas fa-video"></i> Exportar Video');
    $('#animationStatus').html(`<i class="fas fa-check-circle text-success"></i> Video exportado: ${filename}`);

    if (exportCallback) {
        exportCallback();
        exportCallback = null;
    }
}

/**
 * Exporta la jugada actual como video
 * Flujo: Reset -> Esperar -> Grabar -> Animar -> Detener -> Descargar
 */
function exportVideo() {
    // Validaciones
    if (movements.length === 0) {
        alert('⚠️ Primero dibuja algunos movimientos para exportar');
        return;
    }

    if (isPlaying) {
        alert('⚠️ Espera a que termine la animación actual');
        return;
    }

    if (isRecording) {
        alert('⚠️ Ya hay una grabación en progreso');
        return;
    }

    // Deshabilitar botón
    $('#btnExportVideo').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Preparando...');

    console.log('🎥 Iniciando exportación de video...');

    // 1. Resetear posiciones primero
    if (Object.keys(originalPositions).length > 0) {
        resetToOriginalPositionsSync();
    }

    // 2. Esperar un frame para que el canvas se actualice
    setTimeout(() => {
        // 3. Iniciar grabación
        if (!startRecording()) {
            $('#btnExportVideo').prop('disabled', false).html('<i class="fas fa-video"></i> Exportar Video');
            return;
        }

        $('#btnExportVideo').html('<i class="fas fa-circle text-danger"></i> Grabando...');
        $('#animationStatus').html('<i class="fas fa-video text-danger"></i> Grabando animación...');

        // 4. Pequeña pausa antes de animar (para capturar estado inicial)
        setTimeout(() => {
            // 5. Ejecutar animación con callback para detener grabación
            playAnimationForExport();
        }, 500);

    }, 100);
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

    // Recalcular posesión
    if (typeof recalculateBallPossession === 'function') {
        recalculateBallPossession();
    }

    canvas.renderAll();
}

/**
 * Ejecuta la animación para exportación
 * Similar a playAllMovements pero con callback al finalizar
 */
function playAnimationForExport() {
    if (movements.length === 0) {
        stopRecording();
        return;
    }

    // Guardar callback original de finishPlayback
    const originalFinishPlayback = window.finishPlayback;

    // Override temporal para capturar el fin de la animación
    window.finishPlayback = function() {
        // Restaurar función original
        window.finishPlayback = originalFinishPlayback;

        // Pequeña pausa después de la animación para capturar estado final
        setTimeout(() => {
            stopRecording();
            // Llamar al finish original
            if (originalFinishPlayback) {
                originalFinishPlayback();
            }
        }, 1000);
    };

    // Iniciar la animación normal
    playAllMovements();
}

/**
 * Obtener información del formato soportado
 */
function getSupportedFormat() {
    const formats = [
        'video/webm;codecs=vp9',
        'video/webm;codecs=vp8',
        'video/webm',
        'video/mp4'
    ];

    for (const format of formats) {
        if (MediaRecorder.isTypeSupported(format)) {
            return format;
        }
    }
    return null;
}

/**
 * Verificar si el navegador soporta exportación
 */
function canExportVideo() {
    if (!window.MediaRecorder) {
        return false;
    }
    return getSupportedFormat() !== null;
}

// Verificar soporte al cargar
$(document).ready(function() {
    if (!canExportVideo()) {
        $('#btnExportVideo').prop('disabled', true).attr('title', 'Tu navegador no soporta exportación de video');
        console.warn('⚠️ MediaRecorder no soportado en este navegador');
    } else {
        console.log('✅ Exportación de video disponible:', getSupportedFormat());
    }
});

console.log('📦 export.js cargado');
