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
</style>
<link rel="stylesheet" href="{{ asset('jugadas-static/css/jugadas.css') }}">
@endsection

@section('main_content')
<div class="jugadas-page">
    <div class="container-fluid">
        <div class="row">
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
                        <i class="fas fa-save"></i> Guardar / Exportar
                    </div>
                    <input type="text" id="playNameInput" class="form-control form-control-sm mb-2" placeholder="Nombre...">
                    <select id="playCategory" class="form-control form-control-sm mb-2">
                        <option value="forwards">Forwards</option>
                        <option value="backs">Backs</option>
                        <option value="full_team">Equipo completo</option>
                    </select>
                    <button id="btnSavePlay" class="btn btn-rugby btn-block btn-sm mb-2">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button id="btnExportVideo" class="btn btn-outline-info btn-block btn-sm" title="Exportar animacion como video">
                        <i class="fas fa-video"></i> Exportar Video
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
