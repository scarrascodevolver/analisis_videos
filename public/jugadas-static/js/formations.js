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
    const baseX = scaleX(350);
    const baseY = scaleY(325);
    const sp = getScaleFactor(); // spacing factor

    addPlayer('back', baseX - scaleX(50), baseY, 9);
    addPlayer('forward', baseX + scaleX(70), baseY, 8);
    addPlayer('forward', baseX + scaleX(110), baseY - scaleY(20), 4);
    addPlayer('forward', baseX + scaleX(110), baseY + scaleY(20), 5);
    addPlayer('forward', baseX + scaleX(110), baseY - scaleY(55), 6);
    addPlayer('forward', baseX + scaleX(110), baseY + scaleY(55), 7);
    addPlayer('forward', baseX + scaleX(150), baseY - scaleY(35), 1);
    addPlayer('forward', baseX + scaleX(150), baseY, 2);
    addPlayer('forward', baseX + scaleX(150), baseY + scaleY(35), 3);

    playerCounter = 10;
}

function formacionLineout() {
    const baseX = scaleX(500);
    const startY = scaleY(180);
    const spacing = scaleY(75);

    addPlayer('forward', baseX, startY, 4);
    addPlayer('forward', baseX, startY + spacing, 5);
    addPlayer('forward', baseX, startY + spacing * 2, 6);
    addPlayer('forward', baseX, startY + spacing * 3, 7);
    addPlayer('forward', baseX, startY + spacing * 4, 2);
    addPlayer('back', baseX - scaleX(100), startY + spacing * 2, 9);

    playerCounter = 10;
}

function formacionLineoutCompleto() {
    const baseX = scaleX(550);
    const startY = scaleY(130);
    const spacing = scaleY(60);

    addPlayer('forward', baseX, startY, 1);
    addPlayer('forward', baseX, startY + spacing, 4);
    addPlayer('forward', baseX, startY + spacing * 2, 5);
    addPlayer('forward', baseX, startY + spacing * 3, 6);
    addPlayer('forward', baseX, startY + spacing * 4, 7);
    addPlayer('forward', baseX, startY + spacing * 5, 8);
    addPlayer('forward', baseX, startY + spacing * 6, 3);
    addPlayer('forward', baseX, startY + spacing * 7, 2);
    addPlayer('back', baseX - scaleX(100), startY + spacing * 3.5, 9);

    playerCounter = 10;
}

function formacionRuck() {
    addPlayer('back', scaleX(350), scaleY(305), 15);
    addPlayer('back', scaleX(470), scaleY(50), 11);
    addPlayer('back', scaleX(530), scaleY(130), 13);
    addPlayer('back', scaleX(560), scaleY(560), 14);
    addPlayer('back', scaleX(590), scaleY(220), 12);
    addPlayer('back', scaleX(650), scaleY(305), 10);
    addPlayer('forward', scaleX(680), scaleY(450), 1);
    addPlayer('forward', scaleX(680), scaleY(500), 2);
    addPlayer('forward', scaleX(740), scaleY(220), 4);
    addPlayer('forward', scaleX(710), scaleY(270), 3);
    addPlayer('forward', scaleX(710), scaleY(195), 5);
    addPlayer('back', scaleX(830), scaleY(390), 9);
    addPlayer('forward', scaleX(880), scaleY(385), 8);
    addPlayer('forward', scaleX(920), scaleY(360), 7);
    addPlayer('forward', scaleX(920), scaleY(430), 6);

    playerCounter = 16;
}

function formacionMaul() {
    addPlayer('back', scaleX(370), scaleY(340), 15);
    addPlayer('back', scaleX(490), scaleY(70), 11);
    addPlayer('back', scaleX(550), scaleY(150), 13);
    addPlayer('back', scaleX(580), scaleY(235), 12);
    addPlayer('back', scaleX(640), scaleY(300), 10);
    addPlayer('back', scaleX(580), scaleY(550), 14);
    addPlayer('back', scaleX(710), scaleY(400), 9);
    addPlayer('forward', scaleX(750), scaleY(395), 8);
    addPlayer('forward', scaleX(770), scaleY(360), 1);
    addPlayer('forward', scaleX(780), scaleY(410), 2);
    addPlayer('forward', scaleX(770), scaleY(460), 6);
    addPlayer('forward', scaleX(810), scaleY(340), 4);
    addPlayer('forward', scaleX(820), scaleY(390), 5);
    addPlayer('forward', scaleX(830), scaleY(440), 7);
    addPlayer('forward', scaleX(850), scaleY(415), 3);

    playerCounter = 16;
}

// ============== SITUACIONES ESPECIALES ==============

function formacionKickoff() {
    addPlayer('back', scaleX(380), scaleY(80), 11);
    addPlayer('back', scaleX(750), scaleY(140), 14);
    addPlayer('back', scaleX(380), scaleY(190), 13);
    addPlayer('back', scaleX(750), scaleY(230), 12);
    addPlayer('back', scaleX(180), scaleY(280), 15);
    addPlayer('back', scaleX(550), scaleY(280), 10);
    addPlayer('forward', scaleX(650), scaleY(380), 1);
    addPlayer('forward', scaleX(680), scaleY(410), 2);
    addPlayer('forward', scaleX(710), scaleY(380), 3);
    addPlayer('forward', scaleX(780), scaleY(400), 4);
    addPlayer('forward', scaleX(820), scaleY(370), 5);
    addPlayer('back', scaleX(380), scaleY(420), 9);
    addPlayer('forward', scaleX(620), scaleY(480), 6);
    addPlayer('forward', scaleX(750), scaleY(480), 7);
    addPlayer('forward', scaleX(380), scaleY(540), 8);

    playerCounter = 16;
}

// ============== FORMACIONES BACKS ==============

function formacionBacks() {
    const baseX = scaleX(500);
    const baseY = scaleY(325);

    addPlayer('back', baseX, baseY, 9);
    addPlayer('back', baseX - scaleX(60), baseY - scaleY(50), 10);
    addPlayer('back', baseX - scaleX(120), baseY - scaleY(100), 12);
    addPlayer('back', baseX - scaleX(180), baseY - scaleY(150), 13);
    addPlayer('back', baseX - scaleX(240), baseY - scaleY(200), 11);
    addPlayer('back', baseX - scaleX(60), baseY + scaleY(50), 15);
    addPlayer('back', baseX - scaleX(180), baseY + scaleY(150), 14);

    playerCounter = 16;
}

// ============== FORMACIONES COMPLETAS ==============

function formacionFull15() {
    const scrumX = scaleX(750);
    const scrumY = scaleY(420);
    const backsY = scaleY(320);

    // Forwards
    addPlayer('forward', scrumX, scrumY - scaleY(35), 1);
    addPlayer('forward', scrumX, scrumY, 2);
    addPlayer('forward', scrumX, scrumY + scaleY(35), 3);
    addPlayer('forward', scrumX - scaleX(40), scrumY - scaleY(55), 6);
    addPlayer('forward', scrumX - scaleX(40), scrumY - scaleY(20), 4);
    addPlayer('forward', scrumX - scaleX(40), scrumY + scaleY(20), 5);
    addPlayer('forward', scrumX - scaleX(40), scrumY + scaleY(55), 7);
    addPlayer('forward', scrumX - scaleX(70), scrumY, 8);

    // Backs
    addPlayer('back', scrumX - scaleX(120), scrumY, 9);
    addPlayer('back', scrumX - scaleX(230), backsY, 10);
    addPlayer('back', scrumX - scaleX(280), backsY - scaleY(70), 12);
    addPlayer('back', scrumX - scaleX(330), backsY - scaleY(140), 13);
    addPlayer('back', scrumX - scaleX(380), backsY - scaleY(210), 11);
    addPlayer('back', scrumX - scaleX(430), backsY, 15);
    addPlayer('back', scrumX - scaleX(330), scrumY + scaleY(100), 14);

    playerCounter = 16;
}

console.log('📦 formations.js cargado');
