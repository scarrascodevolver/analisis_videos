@extends('layouts.app')

@section('page_title', 'Editor de Jugadas')

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
</style>
<link rel="stylesheet" href="{{ asset('jugadas-static/css/jugadas.css') }}">
@endsection

@section('main_content')
<div class="jugadas-page">
    <div class="container-fluid">
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
    </div>
</div>
@endsection

@section('js')
{{-- Script para vista móvil --}}
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
@endsection
