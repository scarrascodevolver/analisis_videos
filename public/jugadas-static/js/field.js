/**
 * field.js - Cargar cancha de rugby en el canvas
 */

function drawRugbyField() {
    fabric.Image.fromURL('/cancha_rugby.png', function(img) {
        img.set({
            left: 0,
            top: 0,
            scaleX: canvas.width / img.width,
            scaleY: canvas.height / img.height,
            selectable: false,
            evented: false,
            hasControls: false,
            hasBorders: false
        });

        canvas.add(img);
        img.sendToBack();
        canvas.renderAll();
        console.log('✅ Cancha de rugby cargada en canvas');
    });
}

console.log('📦 field.js cargado');
