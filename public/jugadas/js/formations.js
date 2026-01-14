/**
 * formations.js - Formaciones predefinidas
 */

function applyFormacion() {
    const formacion = $('#formacionSelect').val();

    if (!formacion) {
        alert('⚠️ Selecciona una formación primero');
        return;
    }

    clearAllPlayers();

    switch(formacion) {
        case 'scrum':
            formacionScrum();
            break;
        case 'lineout':
            formacionLineout();
            break;
        case 'lineout_completo':
            formacionLineoutCompleto();
            break;
        case 'ruck':
            formacionRuck();
            break;
        case 'maul':
            formacionMaul();
            break;
        case 'backs':
            formacionBacks();
            break;
        case 'kickoff':
            formacionKickoff();
            break;
        case 'full15':
            formacionFull15();
            break;
    }

    setTimeout(() => {
        saveOriginalPositions();
    }, 100);
}

// ============== FORMACIONES FORWARDS ==============

function formacionScrum() {
    const baseX = 350;
    const baseY = 325;

    addPlayer('back', baseX - 50, baseY, 9);
    addPlayer('forward', baseX + 70, baseY, 8);
    addPlayer('forward', baseX + 110, baseY - 20, 4);
    addPlayer('forward', baseX + 110, baseY + 20, 5);
    addPlayer('forward', baseX + 110, baseY - 55, 6);
    addPlayer('forward', baseX + 110, baseY + 55, 7);
    addPlayer('forward', baseX + 150, baseY - 35, 1);
    addPlayer('forward', baseX + 150, baseY, 2);
    addPlayer('forward', baseX + 150, baseY + 35, 3);

    playerCounter = 10;
}

function formacionLineout() {
    const baseX = 500;
    const startY = 180;
    const spacing = 75;

    addPlayer('forward', baseX, startY, 4);
    addPlayer('forward', baseX, startY + spacing, 5);
    addPlayer('forward', baseX, startY + spacing * 2, 6);
    addPlayer('forward', baseX, startY + spacing * 3, 7);
    addPlayer('forward', baseX, startY + spacing * 4, 2);
    addPlayer('back', baseX - 100, startY + spacing * 2, 9);

    playerCounter = 10;
}

function formacionLineoutCompleto() {
    const baseX = 550;
    const startY = 130;
    const spacing = 60;

    addPlayer('forward', baseX, startY, 1);
    addPlayer('forward', baseX, startY + spacing, 4);
    addPlayer('forward', baseX, startY + spacing * 2, 5);
    addPlayer('forward', baseX, startY + spacing * 3, 6);
    addPlayer('forward', baseX, startY + spacing * 4, 7);
    addPlayer('forward', baseX, startY + spacing * 5, 8);
    addPlayer('forward', baseX, startY + spacing * 6, 3);
    addPlayer('forward', baseX, startY + spacing * 7, 2);
    addPlayer('back', baseX - 100, startY + spacing * 3.5, 9);

    playerCounter = 10;
}

function formacionRuck() {
    addPlayer('back', 350, 305, 15);
    addPlayer('back', 470, 50, 11);
    addPlayer('back', 530, 130, 13);
    addPlayer('back', 560, 560, 14);
    addPlayer('back', 590, 220, 12);
    addPlayer('back', 650, 305, 10);
    addPlayer('forward', 680, 450, 1);
    addPlayer('forward', 680, 500, 2);
    addPlayer('forward', 740, 220, 4);
    addPlayer('forward', 710, 270, 3);
    addPlayer('forward', 710, 195, 5);
    addPlayer('back', 830, 390, 9);
    addPlayer('forward', 880, 385, 8);
    addPlayer('forward', 920, 360, 7);
    addPlayer('forward', 920, 430, 6);

    playerCounter = 16;
}

function formacionMaul() {
    addPlayer('back', 370, 340, 15);
    addPlayer('back', 490, 70, 11);
    addPlayer('back', 550, 150, 13);
    addPlayer('back', 580, 235, 12);
    addPlayer('back', 640, 300, 10);
    addPlayer('back', 580, 550, 14);
    addPlayer('back', 710, 400, 9);
    addPlayer('forward', 750, 395, 8);
    addPlayer('forward', 770, 360, 1);
    addPlayer('forward', 780, 410, 2);
    addPlayer('forward', 770, 460, 6);
    addPlayer('forward', 810, 340, 4);
    addPlayer('forward', 820, 390, 5);
    addPlayer('forward', 830, 440, 7);
    addPlayer('forward', 850, 415, 3);

    playerCounter = 16;
}

// ============== SITUACIONES ESPECIALES ==============

function formacionKickoff() {
    addPlayer('back', 380, 80, 11);
    addPlayer('back', 750, 140, 14);
    addPlayer('back', 380, 190, 13);
    addPlayer('back', 750, 230, 12);
    addPlayer('back', 180, 280, 15);
    addPlayer('back', 550, 280, 10);
    addPlayer('forward', 650, 380, 1);
    addPlayer('forward', 680, 410, 2);
    addPlayer('forward', 710, 380, 3);
    addPlayer('forward', 780, 400, 4);
    addPlayer('forward', 820, 370, 5);
    addPlayer('back', 380, 420, 9);
    addPlayer('forward', 620, 480, 6);
    addPlayer('forward', 750, 480, 7);
    addPlayer('forward', 380, 540, 8);

    playerCounter = 16;
}

// ============== FORMACIONES BACKS ==============

function formacionBacks() {
    const baseX = 500;
    const baseY = 325;

    addPlayer('back', baseX, baseY, 9);
    addPlayer('back', baseX - 60, baseY - 50, 10);
    addPlayer('back', baseX - 120, baseY - 100, 12);
    addPlayer('back', baseX - 180, baseY - 150, 13);
    addPlayer('back', baseX - 240, baseY - 200, 11);
    addPlayer('back', baseX - 60, baseY + 50, 15);
    addPlayer('back', baseX - 180, baseY + 150, 14);

    playerCounter = 16;
}

// ============== FORMACIONES COMPLETAS ==============

function formacionFull15() {
    const scrumX = 750;
    const scrumY = 420;
    const backsY = 320;

    // Forwards
    addPlayer('forward', scrumX, scrumY - 35, 1);
    addPlayer('forward', scrumX, scrumY, 2);
    addPlayer('forward', scrumX, scrumY + 35, 3);
    addPlayer('forward', scrumX - 40, scrumY - 55, 6);
    addPlayer('forward', scrumX - 40, scrumY - 20, 4);
    addPlayer('forward', scrumX - 40, scrumY + 20, 5);
    addPlayer('forward', scrumX - 40, scrumY + 55, 7);
    addPlayer('forward', scrumX - 70, scrumY, 8);

    // Backs
    addPlayer('back', scrumX - 120, scrumY, 9);
    addPlayer('back', scrumX - 230, backsY, 10);
    addPlayer('back', scrumX - 280, backsY - 70, 12);
    addPlayer('back', scrumX - 330, backsY - 140, 13);
    addPlayer('back', scrumX - 380, backsY - 210, 11);
    addPlayer('back', scrumX - 430, backsY, 15);
    addPlayer('back', scrumX - 330, scrumY + 100, 14);

    playerCounter = 16;
}

console.log('📦 formations.js cargado');
