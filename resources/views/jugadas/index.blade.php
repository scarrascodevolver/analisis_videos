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
            border-left: 3px solid #00B7B5;
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
        border-color: #00B7B5;
        box-shadow: 0 8px 25px rgba(0, 183, 181, 0.2);
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
                            <i class="fas fa-play"></i> Reproducir
                        </button>
                        <button id="btnViewerReset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reiniciar
                        </button>
                        <button id="btnViewerExportGif" class="btn btn-info">
                            <i class="fas fa-file-image"></i> Descargar GIF
                        </button>
                        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cerrar</button>
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
                            <i class="fas fa-play"></i> Ver
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

    // Abrir modal de visualización
    function openPlayViewer(play) {
        currentPlayData = play;
        document.getElementById('viewerPlayName').textContent = play.name;

        // Configurar canvas
        viewerCanvas.width = 800;
        viewerCanvas.height = 500;

        // Renderizar estado inicial
        renderPlayState(play.data);
        saveOriginalPositions(play.data);

        $(modal).modal('show');
    }

    // Guardar posiciones originales para reset
    function saveOriginalPositions(data) {
        originalPositions = {};
        if (data.players) {
            data.players.forEach(p => {
                originalPositions['player_' + p.number] = { x: p.x, y: p.y };
            });
        }
        if (data.ball) {
            originalPositions['ball'] = { x: data.ball.x, y: data.ball.y };
        }
    }

    // Renderizar estado de la jugada
    function renderPlayState(data) {
        // Limpiar canvas
        ctx.fillStyle = '#2d5a27';
        ctx.fillRect(0, 0, viewerCanvas.width, viewerCanvas.height);

        // Dibujar líneas del campo
        drawFieldLines();

        // Dibujar movimientos (líneas)
        if (data.movements) {
            data.movements.forEach(m => {
                ctx.beginPath();
                ctx.strokeStyle = m.type === 'pass' ? '#ffc107' : '#00B7B5';
                ctx.lineWidth = 2;
                ctx.setLineDash(m.type === 'pass' ? [5, 5] : []);

                const points = m.points || [{ x: m.startX, y: m.startY }, { x: m.endX, y: m.endY }];
                if (points.length > 0) {
                    ctx.moveTo(points[0].x, points[0].y);
                    points.forEach(p => ctx.lineTo(p.x, p.y));
                }
                ctx.stroke();
                ctx.setLineDash([]);
            });
        }

        // Dibujar jugadores
        if (data.players) {
            data.players.forEach(p => {
                const pos = originalPositions['player_' + p.number] || p;
                drawPlayer(pos.x, pos.y, p.number, p.color || '#00B7B5');
            });
        }

        // Dibujar balón
        if (data.ball) {
            const pos = originalPositions['ball'] || data.ball;
            drawBall(pos.x, pos.y);
        }
    }

    // Dibujar líneas del campo
    function drawFieldLines() {
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
        ctx.lineWidth = 2;
        // Líneas horizontales
        for (let i = 0; i <= 10; i++) {
            const x = (viewerCanvas.width / 10) * i;
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, viewerCanvas.height);
            ctx.stroke();
        }
    }

    // Dibujar jugador
    function drawPlayer(x, y, number, color) {
        ctx.beginPath();
        ctx.arc(x, y, 18, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        ctx.stroke();

        ctx.fillStyle = '#fff';
        ctx.font = 'bold 12px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(number, x, y);
    }

    // Dibujar balón
    function drawBall(x, y) {
        ctx.beginPath();
        ctx.ellipse(x, y, 12, 8, Math.PI / 4, 0, Math.PI * 2);
        ctx.fillStyle = '#8B4513';
        ctx.fill();
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 1;
        ctx.stroke();
    }

    // Reproducir animación
    document.getElementById('btnViewerPlay').addEventListener('click', function() {
        if (!currentPlayData || !currentPlayData.data.movements) return;

        const movements = currentPlayData.data.movements;
        let currentStep = 0;
        const totalSteps = 60; // frames de animación

        function animate() {
            currentStep++;
            const progress = currentStep / totalSteps;

            // Actualizar posiciones
            movements.forEach(m => {
                const targetKey = m.playerId ? 'player_' + m.playerId : (m.targetType === 'ball' ? 'ball' : null);
                if (targetKey && originalPositions[targetKey]) {
                    const start = originalPositions[targetKey];
                    const endX = m.endX || (m.points ? m.points[m.points.length - 1].x : start.x);
                    const endY = m.endY || (m.points ? m.points[m.points.length - 1].y : start.y);

                    originalPositions[targetKey] = {
                        x: start.x + (endX - start.x) * progress,
                        y: start.y + (endY - start.y) * progress
                    };
                }
            });

            renderPlayState(currentPlayData.data);

            if (currentStep < totalSteps) {
                animationId = requestAnimationFrame(animate);
            }
        }

        // Reset y comenzar
        saveOriginalPositions(currentPlayData.data);
        animate();
    });

    // Reiniciar
    document.getElementById('btnViewerReset').addEventListener('click', function() {
        if (animationId) cancelAnimationFrame(animationId);
        if (currentPlayData) {
            saveOriginalPositions(currentPlayData.data);
            renderPlayState(currentPlayData.data);
        }
    });

    // Exportar GIF
    document.getElementById('btnViewerExportGif').addEventListener('click', async function() {
        if (!currentPlayData) return;

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

        try {
            const gif = new GIF({
                workers: 2,
                quality: 10,
                width: viewerCanvas.width,
                height: viewerCanvas.height,
                workerScript: '/js/gif.worker.js'
            });

            // Reset posiciones
            saveOriginalPositions(currentPlayData.data);

            const movements = currentPlayData.data.movements || [];
            const totalFrames = 30;

            for (let frame = 0; frame <= totalFrames; frame++) {
                const progress = frame / totalFrames;

                // Actualizar posiciones para este frame
                movements.forEach(m => {
                    const targetKey = m.playerId ? 'player_' + m.playerId : (m.targetType === 'ball' ? 'ball' : null);
                    if (targetKey) {
                        const start = currentPlayData.data.players?.find(p => 'player_' + p.number === targetKey) ||
                                      (targetKey === 'ball' ? currentPlayData.data.ball : null);
                        if (start) {
                            const endX = m.endX || (m.points ? m.points[m.points.length - 1].x : start.x);
                            const endY = m.endY || (m.points ? m.points[m.points.length - 1].y : start.y);
                            originalPositions[targetKey] = {
                                x: start.x + (endX - start.x) * progress,
                                y: start.y + (endY - start.y) * progress
                            };
                        }
                    }
                });

                renderPlayState(currentPlayData.data);
                gif.addFrame(ctx, { copy: true, delay: 100 });
            }

            gif.on('finished', function(blob) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = currentPlayData.name.replace(/[^a-zA-Z0-9]/g, '_') + '.gif';
                a.click();
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

    // Cargar al inicio
    loadPlays();
});
</script>

@else
{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SCRIPTS PARA VISTA EDITOR (analista/entrenador)             --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<script>
    const isMobileView = window.innerWidth < 992;
</script>
<script src="{{ asset('jugadas-static/js/app.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/field.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/players.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/ball.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/movements.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/animation.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/passes.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/formations.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/storage.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/export.js') }}?v={{ time() }}"></script>
<script src="{{ asset('jugadas-static/js/events.js') }}?v={{ time() }}"></script>
@endif
@endsection
