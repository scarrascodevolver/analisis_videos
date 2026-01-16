/**
 * field.js - Cargar cancha de rugby en el canvas
 */

function drawRugbyField() {
    console.log('🏟️ Intentando cargar cancha...');

    // Usar Image nativo para mejor control de errores
    const img = new Image();
    img.crossOrigin = 'anonymous';

    img.onload = function() {
        const fabricImg = new fabric.Image(img, {
            left: 0,
            top: 0,
            scaleX: canvas.width / img.width,
            scaleY: canvas.height / img.height,
            selectable: false,
            evented: false,
            hasControls: false,
            hasBorders: false
        });

        canvas.add(fabricImg);
        fabricImg.sendToBack();
        canvas.renderAll();
        console.log('✅ Cancha de rugby cargada en canvas');
    };

    img.onerror = function(e) {
        console.error('❌ Error cargando cancha:', e);
        // Dibujar cancha verde de respaldo
        canvas.backgroundColor = '#2d5a2d';
        canvas.renderAll();
        console.log('⚠️ Usando fondo verde de respaldo');
    };

    // Timeout para evitar cuelgue
    setTimeout(function() {
        if (!img.complete) {
            console.warn('⏱️ Timeout cargando imagen, usando respaldo');
            canvas.backgroundColor = '#2d5a2d';
            canvas.renderAll();
        }
    }, 5000);

    img.src = '/cancha_rugby.png';
}

console.log('📦 field.js cargado');
