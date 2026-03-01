@extends('layouts.app')

@php
    $isViewer = auth()->user()->role === 'jugador';
@endphp

@section('page_title', $isViewer ? 'Jugadas del Equipo' : 'Editor de Jugadas')

@section('css')
<style>
    .jugadas-page {
        background: #0f0f0f;
        min-height: calc(100vh - 100px);
        padding: 15px;
        margin: -15px;
    }

    /* Vista móvil */
    .mobile-only {
        display: none;
    }

    @media (max-width: 991px) {
        .desktop-only {
            display: none !important;
        }
        .mobile-only {
            display: block;
        }
        .mobile-plays-list {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 15px;
        }
        .mobile-play-item {
            background: #252525;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 3px solid #D4A017;
        }
        .mobile-play-item .play-name {
            color: #fff;
            font-weight: 600;
            font-size: 16px;
        }
        .mobile-play-item .play-meta {
            color: #888;
            font-size: 12px;
            margin-top: 4px;
        }
        .mobile-play-item .btn-group {
            margin-top: 10px;
        }
    }

    /* Vista de solo lectura para jugadores */
    .viewer-plays-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .play-card {
        background: #1a1a1a;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #333;
        transition: all 0.3s ease;
    }

    .play-card:hover {
        transform: translateY(-3px);
        border-color: #D4A017;
        box-shadow: 0 8px 25px rgba(212, 160, 23, 0.2);
    }

    .play-card-thumbnail {
        width: 100%;
        height: 160px;
        background: #252525;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .play-card-thumbnail img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .play-card-thumbnail .no-thumbnail {
        color: #555;
        font-size: 48px;
    }

    .play-card-body {
        padding: 15px;
    }

    .play-card-title {
        color: #fff;
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 8px;
    }

    .play-card-meta {
        color: #888;
        font-size: 12px;
        margin-bottom: 12px;
    }

    .play-card-actions {
        display: flex;
        gap: 8px;
    }

    .play-card-actions .btn {
        flex: 1;
    }

    .category-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .category-forwards { background: #dc3545; color: #fff; }
    .category-backs { background: #007bff; color: #fff; }
    .category-full_team { background: #28a745; color: #fff; }

    /* Modal de visualización */
    #playViewerModal .modal-content {
        background: #1a1a1a;
        border: 1px solid #333;
    }

    #playViewerModal .modal-header {
        border-bottom: 1px solid #333;
    }

    #playViewerModal .modal-body {
        padding: 0;
    }

    #viewerCanvasContainer {
        width: 100%;
        background: #0f0f0f;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 400px;
    }

    #viewerCanvasContainer canvas {
        max-width: 100%;
        height: auto;
    }

    /* Mobile adjustments for play viewer modal */
    @media (max-width: 768px) {
        #playViewerModal .modal-dialog {
            margin: 10px;
            max-width: calc(100% - 20px);
        }

        #playViewerModal .modal-footer {
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px;
        }

        #playViewerModal .modal-footer .btn {
            flex: 1 1 45%;
            font-size: 12px;
            padding: 8px 10px;
        }

        #viewerCanvasContainer {
            min-height: 200px;
        }

        .play-card-actions .btn {
            font-size: 12px;
        }
    }
</style>
<link rel="stylesheet" href="{{ asset('jugadas-static/css/jugadas.css') }}">
@endsection

@section('main_content')
<div class="jugadas-page">
    <div class="container-fluid">
        @if($isViewer)
        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- VISTA JUGADOR: Solo lectura con grid de cards               --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div class="viewer-mode">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white mb-0">
                    <i class="fas fa-football-ball"></i> Jugadas del Equipo
                </h4>
                <span class="badge badge-rugby" id="viewerPlayCount">0 jugadas</span>
            </div>

            <div id="viewerPlaysGrid" class="viewer-plays-grid">
                <p class="text-muted text-center" style="grid-column: 1/-1;">
                    <i class="fas fa-spinner fa-spin"></i> Cargando jugadas...
                </p>
            </div>

            <div id="viewerEmptyState" style="display: none;" class="text-center py-5">
                <i class="fas fa-football-ball fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No hay jugadas disponibles</h5>
                <p class="text-muted">El entrenador aún no ha creado jugadas para el equipo.</p>
            </div>
        </div>

        {{-- Modal para ver jugada --}}
        <div class="modal fade" id="playViewerModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-play-circle"></i> <span id="viewerPlayName">Jugada</span>
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="viewerCanvasContainer">
                            <canvas id="viewerCanvas"></canvas>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary">
                        <button id="btnViewerPlay" class="btn btn-success">
                            <i class="fas fa-play-circle"></i> Reproducir Jugada
                        </button>
                        <button id="btnViewerExportGif" class="btn btn-warning">
                            <i class="fas fa-file-image"></i> Descargar GIF
                        </button>
                        <button id="btnViewerExportMp4" class="btn btn-info">
                            <i class="fas fa-video"></i> Descargar MP4
                        </button>
                        <button type="button" class="btn btn-outline-light" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @else
        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- VISTA ANALISTA/ENTRENADOR: Editor completo                  --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}

        {{-- Vista móvil: solo lista de jugadas --}}
        <div class="mobile-only">
            <div class="mobile-plays-list">
                <h4 class="text-white mb-3">
                    <i class="fas fa-football-ball"></i> Mis Jugadas
                </h4>
                <div class="alert alert-info py-2 mb-3">
                    <small><i class="fas fa-info-circle"></i> Para editar jugadas, usa un computador.</small>
                </div>
                <div id="mobilePlaysList">
                    <p class="text-muted text-center">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </p>
                </div>
            </div>
        </div>

        {{-- Vista desktop: editor completo --}}
        <div class="row desktop-only">
            <div class="col-md-10">
                <div class="canvas-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">
                            <i class="fas fa-football-ball"></i> Cancha de Rugby
                            <small class="text-muted">(Ataque: Izquierda → Derecha)</small>
                        </h4>
                        <button id="btnClearCanvas" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Limpiar Todo
                        </button>
                    </div>
                    <canvas id="playCanvas"></canvas>
                </div>
            </div>

            <div class="col-md-2">
                <div class="alert-info-rugby">
                    <i class="fas fa-info-circle"></i>
                    <strong>Fase 1:</strong> Posiciones fijas. Arrastra para mover.
                </div>

                <div class="tools-panel">
                    <div class="section-header">
                        <i class="fas fa-users"></i> Formaciones
                    </div>
                    <select id="formacionSelect" class="form-control form-control-sm mb-2">
                        <option value="">-- Selecciona --</option>
                        <optgroup label="Forwards">
                            <option value="scrum">Scrum</option>
                            <option value="lineout">Lineout 5</option>
                            <option value="lineout_completo">Lineout completo</option>
                            <option value="ruck">Ruck</option>
                            <option value="maul">Maul</option>
                        </optgroup>
                        <optgroup label="Backs">
                            <option value="backs">Backs linea ataque</option>
                        </optgroup>
                        <optgroup label="Situaciones Especiales">
                            <option value="kickoff">Kick-off (recepcion)</option>
                        </optgroup>
                        <optgroup label="Equipo Completo">
                            <option value="full15">15 jugadores</option>
                        </optgroup>
                    </select>
                    <button id="btnApplyFormacion" class="btn btn-rugby btn-block btn-sm">
                        <i class="fas fa-check-circle"></i> Aplicar
                    </button>
                </div>

                <div class="tools-panel">
                    <div class="section-header">
                        <i class="fas fa-user-plus"></i> Jugadores
                    </div>
                    <button id="btnAddPlayer" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-user-plus"></i> Agregar Jugador
                    </button>
                    <button id="btnAddBall" class="btn btn-warning btn-block mb-2">
                        <i class="fas fa-football-ball"></i> Balon
                    </button>
                    <button id="btnAssignPossession" class="btn btn-primary btn-block mb-2" disabled>
                        <i class="fas fa-hand-holding"></i> Asignar posesion
                    </button>
                    <button id="btnReleasePossession" class="btn btn-outline-secondary btn-block mb-2" style="display: none;">
                        <i class="fas fa-hand-paper"></i> Soltar balon
                    </button>
                    <button id="btnDeleteSelected" class="btn btn-danger btn-block">
                        <i class="fas fa-trash-alt"></i> Eliminar
                    </button>
                </div>

                <div class="tools-panel">
                    <div class="section-header">
                        <i class="fas fa-route"></i> Trayectorias
                    </div>
                    <button id="btnDrawMovement" class="btn btn-info btn-block mb-2" disabled>
                        <i class="fas fa-pencil-alt"></i> Dibujar movimiento
                    </button>
                    <button id="btnCreatePass" class="btn btn-primary btn-block mb-2" disabled>
                        <i class="fas fa-link"></i> Crear Pase
                    </button>
                    <div class="btn-group btn-block mb-2" role="group">
                        <button id="btnPlay" class="btn btn-success" disabled>
                            <i class="fas fa-play"></i> Play
                        </button>
                        <button id="btnReset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                    <div id="movementsList" class="movements-list mt-2 mb-2" style="max-height: 150px; overflow-y: auto; font-size: 12px;">
                        <small class="text-muted">Sin movimientos</small>
                    </div>
                    <button id="btnClearMovements" class="btn btn-outline-danger btn-block btn-sm">
                        <i class="fas fa-eraser"></i> Borrar todas
                    </button>
                    <small id="animationStatus" class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle"></i> Selecciona jugador/balon primero
                    </small>
                </div>

                <div class="tools-panel">
                    <div class="section-header">
                        <i class="fas fa-save"></i> Guardar
                    </div>
                    <input type="text" id="playNameInput" class="form-control form-control-sm mb-2" placeholder="Nombre...">
                    <select id="playCategory" class="form-control form-control-sm mb-2">
                        <option value="forwards">Forwards</option>
                        <option value="backs">Backs</option>
                        <option value="full_team">Equipo completo</option>
                    </select>
                    <button id="btnSavePlay" class="btn btn-rugby btn-block btn-sm">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>

                <div class="tools-panel">
                    <div class="section-header">
                        <i class="fas fa-folder-open"></i> Guardadas
                        <span class="badge badge-rugby float-right" id="playCount">0</span>
                    </div>
                    <div id="savedPlaysList" style="max-height: 250px; overflow-y: auto;">
                        <p class="text-muted text-center small mb-0">
                            <i class="fas fa-info-circle"></i> Sin jugadas
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('js')
@if($isViewer)
{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SCRIPTS PARA VISTA JUGADOR (solo lectura)                   --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/gif.js/0.2.0/gif.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('viewerPlaysGrid');
    const emptyState = document.getElementById('viewerEmptyState');
    const playCount = document.getElementById('viewerPlayCount');
    const modal = document.getElementById('playViewerModal');
    const viewerCanvas = document.getElementById('viewerCanvas');
    const ctx = viewerCanvas.getContext('2d');

    let currentPlayData = null;
    let animationId = null;
    let originalPositions = {};

    // Dimensiones de referencia del editor (típico en desktop)
    const REFERENCE_WIDTH = 1000;
    const REFERENCE_HEIGHT = 540;

    // Escalado para ajustar coordenadas al visor
    let scaleX = 1;
    let scaleY = 1;

    // Sistema de posesión del balón
    let currentBallHolder = null;
    const BALL_OFFSET_X = 25; // El balón va adelante del jugador
    const BALL_OFFSET_Y = 0;

    // Sistema de animación de pases
    let activePass = null; // { from, to, startFrame, duration, startPos }
    const PASS_DURATION_FRAMES = 20; // ~333ms a 60fps

    // Cargar jugadas
    async function loadPlays() {
        try {
            const response = await fetch('/api/jugadas');
            const data = await response.json();

            if (data.success && data.jugadas.length > 0) {
                playCount.textContent = data.jugadas.length + ' jugadas';
                renderPlaysGrid(data.jugadas);
            } else {
                grid.innerHTML = '';
                emptyState.style.display = 'block';
                playCount.textContent = '0 jugadas';
            }
        } catch (error) {
            console.error('Error cargando jugadas:', error);
            grid.innerHTML = '<p class="text-danger text-center" style="grid-column:1/-1;">Error al cargar jugadas</p>';
        }
    }

    // Renderizar grid de jugadas
    function renderPlaysGrid(jugadas) {
        const categoryLabels = {
            forwards: 'Forwards',
            backs: 'Backs',
            full_team: 'Equipo Completo'
        };

        grid.innerHTML = jugadas.map(j => `
            <div class="play-card" data-play-id="${j.id}">
                <div class="play-card-thumbnail">
                    ${j.thumbnail ? `<img src="${j.thumbnail}" alt="${j.name}">` : '<i class="fas fa-football-ball no-thumbnail"></i>'}
                </div>
                <div class="play-card-body">
                    <div class="play-card-title">${j.name}</div>
                    <div class="play-card-meta">
                        <span class="category-badge category-${j.category}">${categoryLabels[j.category] || j.category}</span>
                        <span class="ml-2"><i class="fas fa-user"></i> ${j.user}</span>
                    </div>
                    <div class="play-card-actions">
                        <button class="btn btn-sm btn-rugby view-play-btn" data-play='${JSON.stringify(j)}'>
                            <i class="fas fa-play-circle"></i> Ver Jugada
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        // Event listeners para ver jugada
        grid.querySelectorAll('.view-play-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const playData = JSON.parse(this.dataset.play);
                openPlayViewer(playData);
            });
        });
    }

    // Calcular bounding box de todos los elementos
    function calculateBoundingBox(data) {
        let maxX = 0, maxY = 0;

        if (data.players) {
            data.players.forEach(p => {
                maxX = Math.max(maxX, p.x + 20);
                maxY = Math.max(maxY, p.y + 20);
            });
        }

        if (data.ball) {
            maxX = Math.max(maxX, data.ball.x + 15);
            maxY = Math.max(maxY, data.ball.y + 15);
        }

        if (data.movements) {
            data.movements.forEach(m => {
                if (m.points) {
                    m.points.forEach(p => {
                        maxX = Math.max(maxX, p.x + 20);
                        maxY = Math.max(maxY, p.y + 20);
                    });
                }
            });
        }

        // Usar mínimo las dimensiones de referencia
        return {
            width: Math.max(maxX, REFERENCE_WIDTH),
            height: Math.max(maxY, REFERENCE_HEIGHT)
        };
    }

    // Abrir modal de visualización
    function openPlayViewer(play) {
        currentPlayData = play;
        document.getElementById('viewerPlayName').textContent = play.name;

        // Configurar canvas con aspect ratio correcto
        viewerCanvas.width = 800;
        viewerCanvas.height = 432; // 800 / 1.85 = 432

        // Calcular escala basada en el contenido
        const bbox = calculateBoundingBox(play.data);
        scaleX = viewerCanvas.width / bbox.width;
        scaleY = viewerCanvas.height / bbox.height;

        // Inicializar posiciones y renderizar estado inicial
        initPositions(play.data);
        render(play.data);

        $(modal).modal('show');
    }

    // Posiciones actuales para animación (ya escaladas)
    let currentPositions = {};

    // Escalar coordenadas
    function scale(x, y) {
        return { x: x * scaleX, y: y * scaleY };
    }

    // Inicializar posiciones desde los datos (aplicando escala)
    function initPositions(data) {
        currentPositions = {};
        if (data.players) {
            data.players.forEach(p => {
                const pos = scale(p.x, p.y);
                currentPositions[p.number] = pos;
            });
        }

        // Determinar quién tiene el balón inicialmente
        // Prioridad: originalBallHolder > primer pase.from > ballPossession
        const passes = (data.movements || []).filter(m => m.type === 'pass');
        if (data.originalBallHolder) {
            currentBallHolder = data.originalBallHolder;
        } else if (passes.length > 0) {
            currentBallHolder = passes[0].from;
        } else if (data.ballPossession) {
            currentBallHolder = data.ballPossession;
        } else {
            currentBallHolder = null;
        }

        // Posicionar el balón
        if (data.ball) {
            if (currentBallHolder && currentPositions[currentBallHolder]) {
                // El balón sigue al jugador que lo tiene
                const holderPos = currentPositions[currentBallHolder];
                currentPositions['ball'] = {
                    x: holderPos.x + BALL_OFFSET_X * scaleX,
                    y: holderPos.y + BALL_OFFSET_Y * scaleY
                };
            } else {
                // Balón libre (sin poseedor)
                const pos = scale(data.ball.x, data.ball.y);
                currentPositions['ball'] = pos;
            }
        }
    }

    // Renderizar estado actual
    function render(data) {
        // Limpiar canvas con color de campo (verde más claro)
        ctx.fillStyle = '#3d7a3d';
        ctx.fillRect(0, 0, viewerCanvas.width, viewerCanvas.height);

        // Dibujar líneas del campo
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
        ctx.lineWidth = 1;
        for (let i = 0; i <= 10; i++) {
            const x = (viewerCanvas.width / 10) * i;
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, viewerCanvas.height);
            ctx.stroke();
        }

        // Dibujar postes H (goalposts) - Vista desde arriba (cenital)
        const postColor = 'rgba(255, 255, 255, 0.7)';
        const postWidth = Math.max(3, 4 * scaleX);
        const postLength = viewerCanvas.width * 0.04; // Largo de cada poste horizontal
        const crossbarLength = viewerCanvas.height * 0.06; // Largo del crossbar vertical
        const centerY = viewerCanvas.height * 0.5;

        ctx.strokeStyle = postColor;
        ctx.lineWidth = postWidth;
        ctx.lineCap = 'round';

        // H izquierda (zona de try izquierda) - vista desde arriba (H acostada)
        const leftX = viewerCanvas.width * 0.01;
        ctx.beginPath();
        // Poste superior (línea horizontal)
        ctx.moveTo(leftX, centerY - crossbarLength/2);
        ctx.lineTo(leftX + postLength, centerY - crossbarLength/2);
        // Poste inferior (línea horizontal)
        ctx.moveTo(leftX, centerY + crossbarLength/2);
        ctx.lineTo(leftX + postLength, centerY + crossbarLength/2);
        // Crossbar (línea vertical en el centro conectando los postes)
        ctx.moveTo(leftX + postLength/2, centerY - crossbarLength/2);
        ctx.lineTo(leftX + postLength/2, centerY + crossbarLength/2);
        ctx.stroke();

        // H derecha (zona de try derecha) - vista desde arriba (H acostada)
        const rightX = viewerCanvas.width * 0.99;
        ctx.beginPath();
        // Poste superior (línea horizontal)
        ctx.moveTo(rightX, centerY - crossbarLength/2);
        ctx.lineTo(rightX - postLength, centerY - crossbarLength/2);
        // Poste inferior (línea horizontal)
        ctx.moveTo(rightX, centerY + crossbarLength/2);
        ctx.lineTo(rightX - postLength, centerY + crossbarLength/2);
        // Crossbar (línea vertical en el centro conectando los postes)
        ctx.moveTo(rightX - postLength/2, centerY - crossbarLength/2);
        ctx.lineTo(rightX - postLength/2, centerY + crossbarLength/2);
        ctx.stroke();

        // Dibujar trayectorias de movimiento (líneas punteadas, escaladas)
        if (data.movements) {
            data.movements.forEach(m => {
                if (m.type === 'movement' && m.points && m.points.length > 1) {
                    ctx.beginPath();
                    ctx.strokeStyle = 'rgba(212, 160, 23, 0.5)';
                    ctx.lineWidth = 2;
                    ctx.setLineDash([5, 5]);
                    const firstPt = scale(m.points[0].x, m.points[0].y);
                    ctx.moveTo(firstPt.x, firstPt.y);
                    m.points.forEach(p => {
                        const sp = scale(p.x, p.y);
                        ctx.lineTo(sp.x, sp.y);
                    });
                    ctx.stroke();
                    ctx.setLineDash([]);
                }
            });
        }

        // Tamaño de jugadores escalado
        const playerRadius = Math.max(12, 16 * Math.min(scaleX, scaleY));
        const fontSize = Math.max(9, 11 * Math.min(scaleX, scaleY));

        // Dibujar jugadores
        if (data.players) {
            data.players.forEach(p => {
                const pos = currentPositions[p.number] || scale(p.x, p.y);
                const color = p.type === 'forward' ? '#dc3545' : '#007bff';

                // Círculo del jugador
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, playerRadius, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();

                // Número
                ctx.fillStyle = '#fff';
                ctx.font = `bold ${fontSize}px Arial`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(p.number, pos.x, pos.y);
            });
        }

        // Dibujar balón
        if (data.ball) {
            const pos = currentPositions['ball'] || scale(data.ball.x, data.ball.y);
            const ballW = Math.max(8, 10 * scaleX);
            const ballH = Math.max(6, 7 * scaleY);
            ctx.beginPath();
            ctx.ellipse(pos.x, pos.y, ballW, ballH, Math.PI / 4, 0, Math.PI * 2);
            ctx.fillStyle = '#8B4513';
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 1;
            ctx.stroke();
        }
    }

    // Función de easing (easeOutQuad)
    function easeOutQuad(t) {
        return t * (2 - t);
    }

    // Reproducir animación
    document.getElementById('btnViewerPlay').addEventListener('click', function() {
        if (!currentPlayData || !currentPlayData.data) return;

        const data = currentPlayData.data;
        const allMovements = data.movements || [];

        // Separar movimientos de jugadores y pases
        const playerMovements = allMovements.filter(m => m.type === 'movement');
        const passes = allMovements.filter(m => m.type === 'pass');

        if (playerMovements.length === 0 && passes.length === 0) {
            alert('Esta jugada no tiene movimientos animados');
            return;
        }

        // Cancelar animación anterior
        if (animationId) cancelAnimationFrame(animationId);

        // Resetear posiciones y estado de pases
        initPositions(data);
        activePass = null;

        const totalFrames = 120;
        let frame = 0;

        // Sistema de pases SECUENCIALES
        const numPasses = passes.length;
        const passFrames = [];
        if (numPasses > 0) {
            for (let i = 0; i < numPasses; i++) {
                // Distribuir pases, dejando espacio para la animación del pase
                const passFrame = Math.floor(((i + 1) / (numPasses + 1)) * totalFrames * 0.80);
                passFrames.push({
                    ...passes[i],
                    triggerFrame: passFrame,
                    started: false,
                    completed: false
                });
            }
        }

        // Determinar el poseedor inicial
        if (passes.length > 0) {
            currentBallHolder = passes[0].from;
        }

        console.log('🏈 Animación iniciada');
        console.log('   Poseedor inicial:', currentBallHolder);
        console.log('   Pases programados:', passFrames.map(p => `${p.from}→${p.to} @frame ${p.triggerFrame}`));

        function animate() {
            frame++;
            const progress = Math.min(frame / totalFrames, 1);

            // Animar cada movimiento de jugador
            playerMovements.forEach(m => {
                if (m.playerId && m.points && m.points.length > 1) {
                    const pathLength = m.points.length - 1;
                    const pathProgress = progress * pathLength;
                    const segmentIndex = Math.min(Math.floor(pathProgress), pathLength - 1);
                    const segmentProgress = pathProgress - segmentIndex;

                    const startPoint = m.points[segmentIndex];
                    const endPoint = m.points[segmentIndex + 1] || startPoint;

                    const interpX = startPoint.x + (endPoint.x - startPoint.x) * segmentProgress;
                    const interpY = startPoint.y + (endPoint.y - startPoint.y) * segmentProgress;
                    currentPositions[m.playerId] = scale(interpX, interpY);
                }
            });

            // Iniciar pases cuando llegue su momento
            passFrames.forEach(pass => {
                if (!pass.started && frame >= pass.triggerFrame) {
                    console.log(`   🏈 Pase iniciado: ${pass.from} → ${pass.to} (frame ${frame})`);
                    // Guardar posición inicial del balón para la animación
                    const fromPos = currentPositions[pass.from] || currentPositions['ball'];
                    activePass = {
                        from: pass.from,
                        to: pass.to,
                        startFrame: frame,
                        startPos: { x: fromPos.x + BALL_OFFSET_X * scaleX, y: fromPos.y }
                    };
                    pass.started = true;
                }
            });

            // Animar el pase activo (balón volando)
            if (activePass) {
                const passProgress = (frame - activePass.startFrame) / PASS_DURATION_FRAMES;

                if (passProgress >= 1) {
                    // Pase completado
                    console.log(`   ✓ Pase completado → ${activePass.to}`);
                    currentBallHolder = activePass.to;
                    activePass = null;
                } else {
                    // Balón en vuelo - interpolar hacia el receptor
                    const toPos = currentPositions[activePass.to];
                    if (toPos) {
                        const easedProgress = easeOutQuad(passProgress);
                        const targetX = toPos.x + BALL_OFFSET_X * scaleX;
                        const targetY = toPos.y + BALL_OFFSET_Y * scaleY;

                        currentPositions['ball'] = {
                            x: activePass.startPos.x + (targetX - activePass.startPos.x) * easedProgress,
                            y: activePass.startPos.y + (targetY - activePass.startPos.y) * easedProgress
                        };
                    }
                }
            }

            // Si no hay pase activo, el balón sigue al poseedor
            if (!activePass && currentBallHolder && currentPositions[currentBallHolder]) {
                const holderPos = currentPositions[currentBallHolder];
                currentPositions['ball'] = {
                    x: holderPos.x + BALL_OFFSET_X * scaleX,
                    y: holderPos.y + BALL_OFFSET_Y * scaleY
                };
            }

            render(data);

            if (frame < totalFrames) {
                animationId = requestAnimationFrame(animate);
            } else {
                console.log('✅ Animación completada');
            }
        }

        animate();
    });

    // Exportar GIF
    document.getElementById('btnViewerExportGif').addEventListener('click', async function() {
        if (!currentPlayData || !currentPlayData.data) return;

        const data = currentPlayData.data;
        const allMovements = data.movements || [];

        const playerMovements = allMovements.filter(m => m.type === 'movement');
        const passes = allMovements.filter(m => m.type === 'pass');

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

        try {
            const gif = new GIF({
                workers: 4,
                quality: 15,
                width: viewerCanvas.width,
                height: viewerCanvas.height,
                workerScript: '/js/gif.worker.js'
            });

            const totalFrames = 45;
            const GIF_PASS_DURATION = 8; // Frames para animación de pase en GIF

            // Sistema de pases SECUENCIALES para GIF
            const numPasses = passes.length;
            const passFrames = [];
            for (let i = 0; i < numPasses; i++) {
                const passFrame = Math.floor(((i + 1) / (numPasses + 1)) * totalFrames * 0.80);
                passFrames.push({
                    ...passes[i],
                    triggerFrame: passFrame,
                    endFrame: passFrame + GIF_PASS_DURATION
                });
            }

            // Poseedor inicial
            let gifBallHolder = passes.length > 0 ? passes[0].from : currentBallHolder;
            let gifActivePass = null;

            for (let frame = 0; frame <= totalFrames; frame++) {
                const progress = frame / totalFrames;

                // Resetear posiciones para este frame
                initPositions(data);

                // Calcular posiciones animadas para este frame
                playerMovements.forEach(m => {
                    if (m.playerId && m.points && m.points.length > 1) {
                        const pathLength = m.points.length - 1;
                        const pathProgress = progress * pathLength;
                        const segmentIndex = Math.min(Math.floor(pathProgress), pathLength - 1);
                        const segmentProgress = pathProgress - segmentIndex;

                        const startPoint = m.points[segmentIndex];
                        const endPoint = m.points[segmentIndex + 1] || startPoint;

                        const interpX = startPoint.x + (endPoint.x - startPoint.x) * segmentProgress;
                        const interpY = startPoint.y + (endPoint.y - startPoint.y) * segmentProgress;
                        currentPositions[m.playerId] = scale(interpX, interpY);
                    }
                });

                // Verificar si hay un pase que deba iniciar
                passFrames.forEach(pass => {
                    if (frame >= pass.triggerFrame && frame < pass.endFrame && !gifActivePass) {
                        const fromPos = currentPositions[pass.from];
                        if (fromPos) {
                            gifActivePass = {
                                ...pass,
                                startPos: { x: fromPos.x + BALL_OFFSET_X * scaleX, y: fromPos.y }
                            };
                        }
                    }
                });

                // Animar pase activo
                if (gifActivePass) {
                    const passProgress = (frame - gifActivePass.triggerFrame) / GIF_PASS_DURATION;

                    if (passProgress >= 1) {
                        gifBallHolder = gifActivePass.to;
                        gifActivePass = null;
                    } else {
                        const toPos = currentPositions[gifActivePass.to];
                        if (toPos) {
                            const easedProgress = easeOutQuad(passProgress);
                            const targetX = toPos.x + BALL_OFFSET_X * scaleX;
                            const targetY = toPos.y + BALL_OFFSET_Y * scaleY;

                            currentPositions['ball'] = {
                                x: gifActivePass.startPos.x + (targetX - gifActivePass.startPos.x) * easedProgress,
                                y: gifActivePass.startPos.y + (targetY - gifActivePass.startPos.y) * easedProgress
                            };
                        }
                    }
                }

                // Si no hay pase activo, balón sigue al poseedor
                if (!gifActivePass && gifBallHolder && currentPositions[gifBallHolder]) {
                    const holderPos = currentPositions[gifBallHolder];
                    currentPositions['ball'] = {
                        x: holderPos.x + BALL_OFFSET_X * scaleX,
                        y: holderPos.y + BALL_OFFSET_Y * scaleY
                    };
                }

                render(data);
                gif.addFrame(ctx, { copy: true, delay: 65 });
            }

            gif.on('finished', function(blob) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = currentPlayData.name.replace(/[^a-zA-Z0-9]/g, '_') + '.gif';
                // Safari requires anchor to be in DOM for click to work
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-image"></i> Descargar GIF';
            });

            gif.render();
        } catch (error) {
            console.error('Error exportando GIF:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-file-image"></i> Descargar GIF';
            alert('Error al exportar GIF');
        }
    });

    // Exportar MP4 usando MediaRecorder
    document.getElementById('btnViewerExportMp4').addEventListener('click', async function() {
        if (!currentPlayData || !currentPlayData.data) return;

        const data = currentPlayData.data;
        const allMovements = data.movements || [];

        const playerMovements = allMovements.filter(m => m.type === 'movement');
        const passes = allMovements.filter(m => m.type === 'pass');

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Grabando...';

        try {
            // Check MediaRecorder support
            if (!window.MediaRecorder) {
                throw new Error('Tu navegador no soporta grabación de video');
            }

            // Get canvas stream
            const stream = viewerCanvas.captureStream(30); // 30 FPS

            // Try to use webm/vp9, fallback to webm/vp8, then webm
            let mimeType = 'video/webm;codecs=vp9';
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/webm;codecs=vp8';
                if (!MediaRecorder.isTypeSupported(mimeType)) {
                    mimeType = 'video/webm';
                }
            }

            const mediaRecorder = new MediaRecorder(stream, { mimeType });
            const chunks = [];

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) {
                    chunks.push(e.data);
                }
            };

            mediaRecorder.onstop = async () => {
                const blob = new Blob(chunks, { type: mimeType });
                const filename = currentPlayData.name.replace(/[^a-zA-Z0-9]/g, '_');

                // Try server-side MP4 conversion first
                try {
                    const formData = new FormData();
                    formData.append('video', blob, 'video.webm');
                    formData.append('filename', filename);

                    const response = await fetch('/api/jugadas/convert-to-mp4', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await response.json();
                    console.log('🔄 Respuesta conversión MP4:', data);

                    if (data.success) {
                        // Convert base64 to blob and download
                        const byteCharacters = atob(data.video);
                        const byteNumbers = new Array(byteCharacters.length);
                        for (let i = 0; i < byteCharacters.length; i++) {
                            byteNumbers[i] = byteCharacters.charCodeAt(i);
                        }
                        const byteArray = new Uint8Array(byteNumbers);
                        const mp4Blob = new Blob([byteArray], { type: 'video/mp4' });

                        const url = URL.createObjectURL(mp4Blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = data.filename;
                        a.style.display = 'none';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    } else {
                        // Fallback: download WebM directly
                        console.warn('Conversión fallida, descargando WebM:', data.message);
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename + '.webm';
                        a.style.display = 'none';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    }
                } catch (conversionError) {
                    console.warn('MP4 conversion failed, downloading WebM:', conversionError);
                    // Fallback: download WebM
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename + '.webm';
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }

                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-video"></i> Descargar MP4';
            };

            // Start recording
            mediaRecorder.start();

            // Animation parameters (optimized for faster export)
            const MP4_ANIMATION_DURATION = 2500; // 2.5 seconds
            const MP4_PASS_DURATION = 200; // ms
            const mp4StartTime = performance.now();

            // Setup passes with timing
            const numPasses = passes.length;
            const mp4PassTimes = passes.map((pass, i) => ({
                from: pass.from,
                to: pass.to,
                triggerTime: ((i + 1) / (numPasses + 1)) * MP4_ANIMATION_DURATION * 0.80
            }));

            // Get initial ball holder
            let mp4BallHolder = null;
            if (passes.length > 0 && passes[0].from) {
                mp4BallHolder = passes[0].from;
            } else if (data.originalBallHolder) {
                mp4BallHolder = data.originalBallHolder;
            } else if (data.ballPossession) {
                mp4BallHolder = data.ballPossession;
            }

            let mp4ActivePass = null;

            function animateForRecording(timestamp) {
                const elapsed = timestamp - mp4StartTime;
                const progress = Math.min(elapsed / MP4_ANIMATION_DURATION, 1);

                // Reset positions from original data
                initPositions(data);

                // Calculate animated positions for players with movements
                playerMovements.forEach(m => {
                    if (m.playerId && m.points && m.points.length > 1) {
                        const pathLength = m.points.length - 1;
                        const pathProgress = progress * pathLength;
                        const segmentIndex = Math.min(Math.floor(pathProgress), pathLength - 1);
                        const segmentProgress = pathProgress - segmentIndex;

                        const startPoint = m.points[segmentIndex];
                        const endPoint = m.points[segmentIndex + 1];

                        if (startPoint && endPoint && typeof startPoint.x === 'number' && typeof endPoint.x === 'number') {
                            const interpX = startPoint.x + (endPoint.x - startPoint.x) * segmentProgress;
                            const interpY = startPoint.y + (endPoint.y - startPoint.y) * segmentProgress;
                            currentPositions[m.playerId] = scale(interpX, interpY);
                        }
                    }
                });

                // Check for pass triggers
                for (let i = 0; i < mp4PassTimes.length; i++) {
                    const pass = mp4PassTimes[i];
                    if (elapsed >= pass.triggerTime && elapsed < pass.triggerTime + MP4_PASS_DURATION && !mp4ActivePass) {
                        const fromPos = currentPositions[pass.from];
                        if (fromPos && typeof fromPos.x === 'number') {
                            mp4ActivePass = {
                                from: pass.from,
                                to: pass.to,
                                startPos: { x: fromPos.x + BALL_OFFSET_X * scaleX, y: fromPos.y + BALL_OFFSET_Y * scaleY },
                                startTime: elapsed
                            };
                            break;
                        }
                    }
                }

                // Animate active pass
                if (mp4ActivePass) {
                    const passElapsed = elapsed - mp4ActivePass.startTime;
                    const passProgress = passElapsed / MP4_PASS_DURATION;

                    if (passProgress >= 1) {
                        mp4BallHolder = mp4ActivePass.to;
                        mp4ActivePass = null;
                    } else {
                        const toPos = currentPositions[mp4ActivePass.to];
                        if (toPos && typeof toPos.x === 'number' && mp4ActivePass.startPos) {
                            const easedProgress = easeOutQuad(passProgress);
                            const targetX = toPos.x + BALL_OFFSET_X * scaleX;
                            const targetY = toPos.y + BALL_OFFSET_Y * scaleY;

                            currentPositions['ball'] = {
                                x: mp4ActivePass.startPos.x + (targetX - mp4ActivePass.startPos.x) * easedProgress,
                                y: mp4ActivePass.startPos.y + (targetY - mp4ActivePass.startPos.y) * easedProgress
                            };
                        }
                    }
                }

                // Ball follows holder if no active pass
                if (!mp4ActivePass && mp4BallHolder) {
                    const holderPos = currentPositions[mp4BallHolder];
                    if (holderPos && typeof holderPos.x === 'number') {
                        currentPositions['ball'] = {
                            x: holderPos.x + BALL_OFFSET_X * scaleX,
                            y: holderPos.y + BALL_OFFSET_Y * scaleY
                        };
                    }
                }

                render(data);

                if (progress < 1) {
                    requestAnimationFrame(animateForRecording);
                } else {
                    // Stop recording after animation completes
                    setTimeout(() => {
                        mediaRecorder.stop();
                    }, 100);
                }
            }

            requestAnimationFrame(animateForRecording);

        } catch (error) {
            console.error('Error exportando MP4:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-video"></i> Descargar MP4';
            alert('Error al exportar video: ' + error.message);
        }
    });

    // Cargar al inicio
    loadPlays();
});
</script>

@else
{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SCRIPTS PARA VISTA EDITOR (analista/entrenador)             --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@php
    // Version para cache-busting (cambiar al hacer deploy)
    $jsVersion = '2026.01.17';
@endphp
<script>
    const isMobileView = window.innerWidth < 992;
</script>
<script src="{{ asset('jugadas-static/js/app.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/field.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/players.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/ball.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/movements.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/animation.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/passes.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/formations.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/storage.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/export.js') }}?v={{ $jsVersion }}"></script>
<script src="{{ asset('jugadas-static/js/events.js') }}?v={{ $jsVersion }}"></script>
@endif
@endsection
