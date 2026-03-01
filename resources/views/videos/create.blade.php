@extends('layouts.app')

@section('page_title', 'Subir Video')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}">Videos</a></li>
    <li class="breadcrumb-item active">Subir Video</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
<div class="col-lg-10">

    {{-- ERRORES DE VALIDACIÓN --}}
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        <strong><i class="fas fa-exclamation-triangle mr-1"></i>Errores al guardar el video:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ZONA DE ARCHIVOS --}}
    <div class="card card-rugby mb-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cloud-upload-alt mr-2"></i>Seleccionar Videos</h3>
        </div>
        <div class="card-body p-3">

            {{-- Toggle: Subir Archivo / YouTube --}}
            <div class="d-flex mb-3" id="sourceToggle">
                <button type="button" id="btnSourceUpload"
                    class="btn btn-sm btn-rugby flex-fill mr-1 source-toggle-btn active"
                    onclick="setVideoSource('upload')">
                    <i class="fas fa-upload mr-1"></i> Subir Archivo
                </button>
                <button type="button" id="btnSourceYoutube"
                    class="btn btn-sm btn-outline-danger flex-fill ml-1 source-toggle-btn"
                    onclick="setVideoSource('youtube')">
                    <i class="fab fa-youtube mr-1"></i> Desde YouTube
                </button>
            </div>
            <input type="hidden" name="video_source" id="videoSourceInput" value="upload">

            {{-- SECCION: Subir Archivo --}}
            <div id="sectionUpload">
                {{-- Drop Zone (se oculta cuando hay archivos) --}}
                <div id="dropZone" class="drop-zone mb-3">
                    <i class="fas fa-film drop-zone-icon"></i>
                    <p class="mb-1 font-weight-bold">Arrastrá los archivos acá</p>
                    <p class="text-muted small mb-2">o hacé clic para seleccionar</p>
                    <input type="file" id="videoFilesInput" multiple accept=".mp4,.mov,.avi,.webm,.mkv" class="d-none">
                    <button type="button" class="btn btn-sm btn-rugby-outline" onclick="document.getElementById('videoFilesInput').click()">
                        <i class="fas fa-folder-open mr-1"></i> Elegir archivos
                    </button>
                    <p class="text-muted small mt-2 mb-0">MP4, MOV, AVI, WEBM, MKV · Máx. 8GB por archivo</p>
                </div>

                {{-- Botón compacto para agregar más (visible cuando hay archivos) --}}
                <div id="addMoreBtn" class="d-none mb-2">
                    <button type="button" class="btn btn-sm btn-rugby-outline"
                        onclick="document.getElementById('videoFilesInput').click()">
                        <i class="fas fa-plus mr-1"></i> Agregar más videos
                    </button>
                </div>

                {{-- Lista de archivos seleccionados --}}
                <div id="filesList" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small" id="filesCount"></span>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="clearAllFiles()">
                            <i class="fas fa-trash mr-1"></i>Limpiar todo
                        </button>
                    </div>
                    <div id="filesRows"></div>
                </div>
            </div>

            {{-- SECCION: YouTube URL --}}
            <div id="sectionYoutube" class="d-none">
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">
                        <i class="fab fa-youtube text-danger mr-1"></i> URL del video de YouTube
                    </label>
                    <input type="url" id="youtubeUrlInput" name="youtube_url"
                        class="form-control form-control-sm"
                        placeholder="https://www.youtube.com/watch?v=... o https://youtu.be/..."
                        oninput="onYoutubeUrlChange(this.value)">
                    <small class="text-muted d-block mt-1">
                        Formatos válidos: <code>youtube.com/watch?v=ID</code> · <code>youtu.be/ID</code> · <code>youtube.com/embed/ID</code>
                    </small>
                </div>

                {{-- Preview del video de YouTube --}}
                <div id="youtubePreviw" class="d-none mt-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img id="youtubeThumb" src="" alt="Preview"
                                style="width:120px;border-radius:6px;border:2px solid #00B7B5;">
                        </div>
                        <div class="col">
                            <p class="mb-1 small font-weight-bold text-success">
                                <i class="fas fa-check-circle mr-1"></i> URL válida — Video encontrado
                            </p>
                            <p class="mb-0 text-muted small" id="youtubeVideoIdLabel"></p>
                        </div>
                    </div>
                </div>

                {{-- Error URL inválida --}}
                <div id="youtubeError" class="d-none mt-2">
                    <p class="text-danger small mb-0">
                        <i class="fas fa-exclamation-circle mr-1"></i> URL no válida. Verificá que sea un link de YouTube correcto.
                    </p>
                </div>
            </div>

        </div>
    </div>

    {{-- DETALLES DEL PARTIDO --}}
    <div class="card card-rugby mb-3" id="detailsCard" style="display:none!important">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Detalles del Partido</h3>
            <span class="badge badge-secondary" id="detailsBadge"></span>
        </div>
        <div class="card-body p-3">

            <div class="row">
                {{-- Equipo Local --}}
                <div class="col-md-5">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">
                            <i class="fas fa-shield-alt text-success mr-1"></i>Equipo Local
                        </label>
                        <div class="ac-wrap">
                            <input type="text"
                                   id="local_team_name"
                                   name="local_team_name"
                                   class="form-control form-control-sm"
                                   placeholder="Equipo local..."
                                   value="{{ $isClub && $defaultTeam ? $defaultTeam : '' }}"
                                   autocomplete="off">
                            <div class="ac-dropdown" id="ac-local"></div>
                        </div>
                    </div>
                </div>

                {{-- VS --}}
                <div class="col-md-2 d-flex align-items-end justify-content-center mb-2">
                    <span class="font-weight-bold text-muted pb-1">VS</span>
                </div>

                {{-- Equipo Visitante --}}
                <div class="col-md-5">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">
                            <i class="fas fa-shield-alt text-danger mr-1"></i>Equipo Visitante
                        </label>
                        <div class="ac-wrap">
                            <input type="text"
                                   id="rival_team_input"
                                   name="rival_team_name"
                                   class="form-control form-control-sm"
                                   placeholder="Equipo visitante..."
                                   autocomplete="off">
                            <input type="hidden" id="rival_team_id" name="rival_team_id">
                            <div class="ac-dropdown" id="ac-rival"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Torneo --}}
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">
                            <i class="fas fa-trophy text-warning mr-1"></i>Torneo
                            @if(!$isClub)<span class="text-danger">*</span>@endif
                        </label>
                        <div class="ac-wrap">
                            <input type="text"
                                   id="tournament_input"
                                   name="tournament_name_input"
                                   class="form-control form-control-sm"
                                   placeholder="{{ $isClub ? 'Torneo (opcional)...' : 'Seleccioná o escribí el torneo...' }}"
                                   autocomplete="off">
                            <input type="hidden" id="tournament_id" name="tournament_id">
                            <div class="ac-dropdown" id="ac-tournament"></div>
                        </div>
                    </div>
                </div>

                {{-- Fecha --}}
                <div class="{{ $isClub ? 'col-md-3' : 'col-md-4' }}">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold"><i class="fas fa-calendar mr-1"></i>Fecha <span class="text-danger">*</span></label>
                        <input type="date" id="match_date" name="match_date"
                            class="form-control form-control-sm bg-dark border-secondary text-white"
                            value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                {{-- Categoría + División (clubs) | Equipo (asociaciones) --}}
                @if($isClub)
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold"><i class="fas fa-tag mr-1"></i>Categoría <span class="text-danger">*</span></label>
                        <select id="category_id" name="category_id" class="form-control form-control-sm" required>
                            <option value="">Seleccionar...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2" id="divisionCol" style="display:none">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">División</label>
                        <select id="division" name="division" class="form-control form-control-sm">
                            <option value="primera">Primera</option>
                            <option value="intermedia">Intermedia</option>
                        </select>
                    </div>
                </div>
                @else
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold"><i class="fas fa-shield-alt mr-1"></i>Equipo <span class="text-danger">*</span></label>
                        <select id="category_id" name="category_id" class="form-control form-control-sm" required>
                            <option value="">Seleccionar equipo...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
            </div>

            {{-- Visibilidad (solo clubs) --}}
            @if($isClub)
            <div class="form-group mb-2">
                <label class="small font-weight-bold"><i class="fas fa-eye mr-1"></i>¿Quién puede ver este video?</label>
                <div class="d-flex flex-wrap gap-2" id="visibilityOptions">
                    <label class="visibility-option active" data-value="public">
                        <input type="radio" name="visibility_type" value="public" checked class="d-none">
                        <i class="fas fa-users mr-1"></i>Todo el equipo
                    </label>
                    <label class="visibility-option" data-value="forwards">
                        <input type="radio" name="visibility_type" value="forwards" class="d-none">
                        <i class="fas fa-shield-alt mr-1"></i>Solo Delanteros
                    </label>
                    <label class="visibility-option" data-value="backs">
                        <input type="radio" name="visibility_type" value="backs" class="d-none">
                        <i class="fas fa-running mr-1"></i>Solo Backs
                    </label>
                    <label class="visibility-option" data-value="specific">
                        <input type="radio" name="visibility_type" value="specific" class="d-none">
                        <i class="fas fa-user-check mr-1"></i>Específicos
                    </label>
                </div>
            </div>

            {{-- Jugadores específicos --}}
            <div class="form-group mb-2 d-none" id="specificPlayersGroup">
                <label class="small font-weight-bold"><i class="fas fa-users mr-1"></i>Seleccionar Jugadores</label>
                <select id="assigned_players" name="assigned_players[]" multiple class="form-control form-control-sm select2-players" style="width:100%">
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">
                            {{ $player->name }}
                            @if($player->profile?->position) · {{ $player->profile->position }} @endif
                        </option>
                    @endforeach
                </select>
                <textarea id="assignment_notes" name="assignment_notes" rows="2"
                    class="form-control form-control-sm mt-2 bg-dark border-secondary text-white"
                    placeholder="Instrucciones para los jugadores (opcional)"></textarea>
            </div>
            @else
            {{-- Asociación: visibilidad pública por defecto --}}
            <input type="hidden" name="visibility_type" value="public">
            @endif

            {{-- Descripción --}}
            <div class="form-group mb-0">
                <label class="small font-weight-bold"><i class="fas fa-align-left mr-1"></i>Descripción <span class="text-muted">(opcional)</span></label>
                <textarea id="description" name="description" rows="2"
                    class="form-control form-control-sm bg-dark border-secondary text-white"
                    placeholder="Descripción del partido, jugadas clave, objetivos del análisis..."></textarea>
            </div>

        </div>
    </div>

    {{-- ACCIÓN DE SUBIDA --}}
    <div class="card card-rugby mb-3" id="uploadCard" style="display:none!important">
        <div class="card-body p-3">
            <div id="uploadProgress" class="d-none mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span id="uploadStatusText">Preparando subida...</span>
                    <span id="uploadPercent">0%</span>
                </div>
                <div class="progress" style="height:8px">
                    <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-rugby"
                        style="width:0%"></div>
                </div>
                <div id="uploadFileStatus" class="small text-muted mt-1"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('videos.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </a>
                <button type="button" id="uploadBtn" class="btn btn-rugby btn-sm px-4" onclick="startUpload()">
                    <i class="fas fa-cloud-upload-alt mr-1"></i>
                    <span id="uploadBtnText">Subir Videos</span>
                </button>
            </div>
        </div>
    </div>

</div>
</div>
@endsection

@push('styles')
<style>
/* Drop Zone */
.drop-zone {
    border: 2px dashed #005461;
    border-radius: 10px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    background: rgba(0,84,97,.05);
}
.drop-zone:hover, .drop-zone.dragover {
    border-color: #00B7B5;
    background: rgba(0,183,181,.08);
}
.drop-zone-icon {
    font-size: 2.5rem;
    color: #005461;
    display: block;
    margin-bottom: .5rem;
}

/* Fila de archivo */
.file-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: #1a1a1a;
    border: 1px solid #2d2d2d;
    border-radius: 6px;
    margin-bottom: 6px;
    font-size: .85rem;
}
.file-row:hover { border-color: #005461; }
.file-thumb {
    width: 48px;
    height: 32px;
    background: #111;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #00B7B5;
    font-size: 1rem;
}
.file-info { flex: 1; min-width: 0; }
.file-name {
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #fff;
    max-width: 280px;
}
.file-size { color: #888; font-size: .75rem; }
.file-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

/* Badge master/slave */
.badge-master { background: #856404; color: #fff; }
.badge-slave  { background: #1d5a8a; color: #fff; }

/* Botón XML */
.btn-xml {
    font-size: .75rem;
    padding: 2px 8px;
    border: 1px solid #005461;
    color: #00B7B5;
    background: transparent;
    border-radius: 4px;
    cursor: pointer;
    white-space: nowrap;
}
.btn-xml:hover { background: #005461; color: #fff; }
.btn-xml.has-xml { background: #005461; color: #fff; }

/* Select de rol */
.role-select {
    font-size: .78rem;
    padding: 2px 4px;
    background: #222;
    border: 1px solid #333;
    color: #ccc;
    border-radius: 4px;
    cursor: pointer;
}
.role-select:focus { outline: none; border-color: #005461; }

/* Botón quitar archivo */
.btn-remove-file {
    background: transparent;
    border: none;
    color: #666;
    padding: 2px 6px;
    cursor: pointer;
    border-radius: 4px;
}
.btn-remove-file:hover { color: #dc3545; background: rgba(220,53,69,.1); }

/* Radio selector — video principal */
.file-role-radio {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #444;
    background: transparent;
    cursor: pointer;
    flex-shrink: 0;
    transition: border-color .2s, background .2s;
    position: relative;
}
.file-role-radio::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 8px; height: 8px;
    border-radius: 50%;
    background: transparent;
    transition: background .2s;
}
.file-role-radio.is-principal {
    border-color: #b8860b;
    background: #b8860b;
    cursor: default;
}
.file-role-radio.is-principal::after { background: #fff; }
.file-role-radio:not(.is-principal):hover { border-color: #b8860b; }
.file-role-radio:not(.is-principal):hover::after { background: rgba(184,134,11,.4); }

/* Progreso por fila */
.row-progress {
    margin-top: 5px;
}
.row-progress-track {
    height: 5px;
    background: #2a2a2a;
    border-radius: 3px;
    overflow: hidden;
}
.row-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #005461, #00B7B5);
    border-radius: 3px;
    transition: width .4s ease;
}
.row-progress-label {
    display: flex;
    justify-content: space-between;
    font-size: .72rem;
    color: #888;
    margin-top: 2px;
}
.row-progress-label .pct { color: #00B7B5; font-weight: 600; }

/* Visibilidad */
.visibility-option {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border: 1px solid #333;
    border-radius: 20px;
    cursor: pointer;
    font-size: .82rem;
    color: #aaa;
    margin-right: 6px;
    margin-bottom: 4px;
    transition: all .2s;
    user-select: none;
}
.visibility-option:hover { border-color: #005461; color: #fff; }
.visibility-option.active { border-color: #00B7B5; color: #00B7B5; background: rgba(0,183,181,.1); }

/* Badge XML en fila */
.xml-badge {
    font-size: .7rem;
    color: #00B7B5;
    font-weight: 600;
    margin-left: 6px;
}

/* Colores rugby */
.btn-rugby { background: #005461; border-color: #005461; color: #fff; }
.btn-rugby:hover { background: #003d4a; border-color: #003d4a; color: #fff; }
.btn-rugby-outline { background: transparent; border: 1px solid #005461; color: #00B7B5; }
.btn-rugby-outline:hover { background: #005461; color: #fff; }
.bg-rugby { background-color: #005461 !important; }
.card-rugby { border-color: #005461; }
.card-rugby .card-header { background: #005461; border-color: #005461; }

/* Select2 dark — only used for assigned_players field */
.select2-container--bootstrap4 .select2-selection { background: #1a1a1a !important; border-color: #444 !important; color: #fff !important; }
.select2-container--bootstrap4 .select2-selection__rendered { color: #fff !important; }
.select2-dropdown { background: #1a1a1a !important; border-color: #444 !important; }
.select2-results__option { color: #ccc !important; }
.select2-results__option--highlighted { background: #005461 !important; color: #fff !important; }

/* Autocomplete personalizado */
.ac-wrap { position: relative; }
.ac-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0; right: 0;
    background: #1e1e1e;
    border: 1px solid #005461;
    border-top: none;
    border-radius: 0 0 6px 6px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 9999;
    box-shadow: 0 6px 16px rgba(0,0,0,.5);
}
.ac-item {
    padding: 8px 12px;
    font-size: .85rem;
    color: #ccc;
    cursor: pointer;
    transition: background .12s;
}
.ac-item:hover, .ac-item-active {
    background: #005461;
    color: #fff;
}
</style>
@endpush

@push('scripts')
{{-- tus-js-client: upload resumable directo a Cloudflare Stream --}}
<script src="https://cdn.jsdelivr.net/npm/tus-js-client@3/dist/tus.min.js"></script>
<script>
// ─── Estado global ───────────────────────────────────────────
const uploadState = {
    files: [],       // [{ id, file, title, role, xmlContent, xmlName }]
    isUploading: false,
    masterVideoId: null, // video_id del master (para vincular slaves)
};

// ─── Prevenir navegación accidental durante upload ────────────
window.addEventListener('beforeunload', function (e) {
    if (uploadState.isUploading) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// ─── Slave mode (cuando se llega desde Show.vue via "Subir ángulo") ──────────
const urlParams    = new URLSearchParams(window.location.search);
const masterVideoId = urlParams.get('master_video_id');
const isSlaveMode   = urlParams.get('is_slave') === '1' && !!masterVideoId;

// ─── Utilidad: escapar HTML para evitar XSS en innerHTML ─────
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ─── Config por tipo de org ───────────────────────────────────
const isClub = {{ json_encode($isClub) }};

// ─── Inicialización ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Slave mode: mostrar banner informativo y ajustar UI
    if (isSlaveMode) {
        const banner = document.createElement('div');
        banner.id = 'slaveModeBanner';
        banner.className = 'alert alert-info mb-3';
        banner.style.cssText = 'border-left: 4px solid #00B7B5; background: rgba(0,183,181,.1); color: #00B7B5; border-color: #00B7B5;';
        banner.innerHTML = `
            <div class="d-flex align-items-start">
                <i class="fas fa-video mr-2 mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Modo ángulo adicional</strong> —
                    El video se vinculará automáticamente al video master.
                    <div class="mt-2">
                        <label for="cameraAngleInput" style="font-size:.85rem;font-weight:600;color:#00B7B5;margin-bottom:4px;display:block;">
                            Nombre del ángulo <span style="color:#e74c3c">*</span>
                        </label>
                        <input type="text" id="cameraAngleInput"
                            placeholder="Ej: Tribuna lateral, Drone, Detrás del arco..."
                            style="width:100%;max-width:420px;background:#1a1a1a;border:1px solid #00B7B5;color:#fff;border-radius:6px;padding:6px 10px;font-size:.85rem;"
                            maxlength="100">
                        <small style="color:#888;display:block;margin-top:3px;">
                            Este nombre aparecerá en el badge de ángulos del partido.
                        </small>
                    </div>
                </div>
            </div>
        `;
        // Insertar al comienzo del contenido principal
        const mainRow = document.querySelector('.row.justify-content-center .col-lg-10');
        if (mainRow) mainRow.prepend(banner);
    }

    initDropZone();
    if (isClub) {
        initSelect2();
        initVisibility();
        initCategoryListener();
    }
});

function initDropZone() {
    const zone = document.getElementById('dropZone');
    const input = document.getElementById('videoFilesInput');

    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        addFiles(e.dataTransfer.files);
    });
    zone.addEventListener('click', e => {
        if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'INPUT') input.click();
    });
    input.addEventListener('change', () => { addFiles(input.files); input.value = ''; });
}

function initSelect2() {
    // Select2 solo para jugadores (los tres campos de partido usan autocomplete nativo)
    $('#assigned_players').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccionar jugadores...',
        allowClear: true,
    });
}

// ─── Autocomplete personalizado ──────────────────────────────
/**
 * initAutocomplete({ inputId, dropdownId, url, hiddenId, labelKey, idKey })
 * - Muestra dropdown con resultados al enfocar/escribir
 * - Al clickear una opción: rellena el input y guarda el ID en hiddenId (si aplica)
 * - Texto libre permitido (no fuerza selección de lista)
 */
function initAutocomplete({ inputId, dropdownId, url, hiddenId, labelKey = 'text', idKey = 'id' }) {
    const input    = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    const hidden   = hiddenId ? document.getElementById(hiddenId) : null;
    if (!input || !dropdown) return;

    let cache = {};
    let active = -1;

    function fetchResults(q) {
        const key = q.toLowerCase();
        if (cache[key]) { renderDropdown(cache[key]); return; }
        fetch(url + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                const results = data.results || data;
                cache[key] = results;
                renderDropdown(results);
            })
            .catch(() => {});
    }

    function renderDropdown(results) {
        active = -1;
        if (!results.length) { closeDropdown(); return; }
        dropdown.innerHTML = '';
        results.forEach((item, i) => {
            const label = item[labelKey] || item.text || item.id;
            const id    = item[idKey]    || item.id   || null;
            const li = document.createElement('div');
            li.className = 'ac-item';
            li.textContent = label;
            li.addEventListener('mousedown', function(e) {
                e.preventDefault(); // no blur antes de click
                input.value = label;
                if (hidden) hidden.value = id || '';
                closeDropdown();
            });
            dropdown.appendChild(li);
        });
        dropdown.style.display = 'block';
    }

    function closeDropdown() {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        active = -1;
    }

    // Navegación con teclado
    input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.ac-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            active = Math.min(active + 1, items.length - 1);
            items.forEach((el, i) => el.classList.toggle('ac-item-active', i === active));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            active = Math.max(active - 1, 0);
            items.forEach((el, i) => el.classList.toggle('ac-item-active', i === active));
        } else if (e.key === 'Enter' && active >= 0) {
            e.preventDefault();
            items[active].dispatchEvent(new MouseEvent('mousedown'));
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    input.addEventListener('focus', function() { fetchResults(this.value); });
    input.addEventListener('input', function() {
        if (hidden) hidden.value = ''; // limpiar ID si escribe libre
        fetchResults(this.value);
    });
    input.addEventListener('blur', function() {
        setTimeout(closeDropdown, 150); // delay para permitir click en item
    });
}

// Inicializar los tres campos
initAutocomplete({
    inputId: 'local_team_name',
    dropdownId: 'ac-local',
    url: '{{ route("api.local-teams.recent") }}',
    labelKey: 'text',
});

initAutocomplete({
    inputId: 'rival_team_input',
    dropdownId: 'ac-rival',
    url: '{{ route("api.rival-teams.autocomplete") }}',
    hiddenId: 'rival_team_id',
    labelKey: 'text',
});

initAutocomplete({
    inputId: 'tournament_input',
    dropdownId: 'ac-tournament',
    url: '{{ route("api.tournaments.autocomplete") }}',
    hiddenId: 'tournament_id',
    labelKey: 'text',
});

function initVisibility() {
    document.querySelectorAll('.visibility-option').forEach(label => {
        label.addEventListener('click', function () {
            document.querySelectorAll('.visibility-option').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            this.querySelector('input[type=radio]').checked = true;
            const isSpecific = this.dataset.value === 'specific';
            document.getElementById('specificPlayersGroup').classList.toggle('d-none', !isSpecific);
        });
    });
}

function initCategoryListener() {
    document.getElementById('category_id').addEventListener('change', function () {
        const text = this.options[this.selectedIndex]?.text || '';
        const showDiv = text.toLowerCase().includes('adult');
        document.getElementById('divisionCol').style.display = showDiv ? '' : 'none';
    });
}

// ─── Gestión de archivos ─────────────────────────────────────
const MAX_FILES = 4;

function addFiles(fileList) {
    const videoExts = ['mp4','mov','avi','webm','mkv'];
    Array.from(fileList).forEach(file => {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!videoExts.includes(ext)) return;
        if (file.size > 8 * 1024 * 1024 * 1024) { alert(`"${file.name}" supera 8GB.`); return; }
        if (uploadState.files.length >= MAX_FILES) {
            alert(`Máximo ${MAX_FILES} videos por partido (1 master + ${MAX_FILES - 1} ángulos).`);
            return;
        }
        const id = 'f_' + Date.now() + '_' + Math.random().toString(36).slice(2);
        const role = uploadState.files.length === 0 ? 'master' : 'slave';
        uploadState.files.push({ id, file, title: file.name.replace(/\.[^.]+$/, ''), role, xmlContent: null, xmlName: null });
        renderFileRow(uploadState.files[uploadState.files.length - 1]);
    });
    updateUI();
}

function getAngleLabel(index) {
    if (index === 0) return { icon: '⭐', text: 'Master', cls: 'badge-master' };
    return { icon: '📹', text: `Ángulo ${index}`, cls: 'badge-slave' };
}

function renderFileRow(item) {
    const sizeMB = (item.file.size / 1024 / 1024).toFixed(1);
    const sizeText = sizeMB > 1024 ? (sizeMB / 1024).toFixed(1) + ' GB' : sizeMB + ' MB';
    const isMaster = item.role === 'master';

    // Radio circle: relleno = principal, vacío+clickable = ángulo adicional
    const radioHtml = isMaster
        ? `<div class="file-role-radio is-principal" title="Video principal"></div>`
        : `<div class="file-role-radio" onclick="setAsMaster('${item.id}')" title="Definir como video principal"></div>`;

    // Botón XML inline — solo visible en el video principal
    const xmlBtnInline = isMaster ? `
        <button type="button" class="btn-xml ${item.xmlContent ? 'has-xml' : ''}" id="xmlbtn_${item.id}"
            onclick="triggerXml('${item.id}')"
            title="${item.xmlContent ? (item.xmlName || 'XML cargado') : 'Importar clips desde LongoMatch XML'}">
            ${item.xmlContent
                ? `<i class="fas fa-check-circle mr-1"></i>${item.xmlClipCount != null ? item.xmlClipCount + ' clips' : 'XML'}`
                : `<i class="fas fa-file-code mr-1"></i>XML`}
        </button>
        <span class="xml-badge ${item.xmlContent ? '' : 'd-none'}" id="xmlbadge_${item.id}">${item.xmlContent ? (item.xmlName?.length > 20 ? item.xmlName.slice(0,20)+'...' : (item.xmlName || '')) : ''}</span>
        <input type="file" accept=".xml" class="d-none" id="xmlinput_${item.id}" onchange="handleXml('${item.id}', this)">
    ` : '';

    const row = document.createElement('div');
    row.className = 'file-row';
    row.id = 'row_' + item.id;
    row.innerHTML = `
        ${radioHtml}
        <div class="file-thumb"><i class="fas fa-film"></i></div>
        <div class="file-info">
            <div class="d-flex align-items-center flex-wrap" style="gap:10px;margin-bottom:2px">
                <span class="file-name" title="${escapeHtml(item.file.name)}">${escapeHtml(item.file.name)}</span>
                ${xmlBtnInline}
            </div>
            <div class="file-size">${sizeText}</div>
            <div class="row-progress d-none" id="prog_${item.id}">
                <div class="row-progress-track">
                    <div class="row-progress-bar" id="progbar_${item.id}" style="width:0%"></div>
                </div>
                <div class="row-progress-label">
                    <span id="progstatus_${item.id}">Subiendo...</span>
                    <span class="pct" id="progpct_${item.id}">0%</span>
                </div>
            </div>
        </div>
        <div class="file-actions">
            <button type="button" class="btn-remove-file" onclick="removeFile('${item.id}')" title="Quitar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.getElementById('filesRows').appendChild(row);
}

/**
 * Cambia el video principal sin mover las filas de lugar.
 * Solo intercambia roles y transfiere el XML (que pertenece al partido, no a la camara).
 */
function setAsMaster(fileId) {
    const oldMaster = uploadState.files.find(f => f.role === 'master');
    const newMaster = uploadState.files.find(f => f.id === fileId);
    if (!newMaster || !oldMaster || oldMaster.id === newMaster.id) return;

    // Intercambiar roles (sin mover posiciones)
    oldMaster.role = 'slave';
    newMaster.role = 'master';

    // Transferir XML al nuevo principal
    if (oldMaster.xmlContent) {
        newMaster.xmlContent   = oldMaster.xmlContent;
        newMaster.xmlName      = oldMaster.xmlName;
        newMaster.xmlClipCount = oldMaster.xmlClipCount;
        oldMaster.xmlContent   = null;
        oldMaster.xmlName      = null;
        oldMaster.xmlClipCount = null;
    }

    reRenderAllRows();
}

/**
 * Clear and re-render all file rows from scratch.
 */
function reRenderAllRows() {
    document.getElementById('filesRows').innerHTML = '';
    uploadState.files.forEach(f => renderFileRow(f));
}

/**
 * Actualiza el indicador XML del row master despues de cargar un XML exitosamente.
 */
function updateXmlIndicator(fileId, xmlName, clipCount) {
    const btn = document.getElementById('xmlbtn_' + fileId);
    if (btn) {
        btn.classList.add('has-xml');
        btn.innerHTML = `<i class="fas fa-check-circle mr-1"></i>${clipCount} clips`;
        btn.title = xmlName || 'XML cargado';
    }
    const badge = document.getElementById('xmlbadge_' + fileId);
    if (badge) {
        const display = xmlName?.length > 20 ? xmlName.slice(0, 20) + '...' : (xmlName || 'XML');
        badge.textContent = display;
        badge.classList.remove('d-none');
    }
    const item = uploadState.files.find(f => f.id === fileId);
    if (item) item.xmlClipCount = clipCount;
}

function removeFile(id) {
    uploadState.files = uploadState.files.filter(f => f.id !== id);
    uploadState.files.forEach((f, i) => { f.role = i === 0 ? 'master' : 'slave'; });
    reRenderAllRows();
    updateUI();
}

function clearAllFiles() {
    uploadState.files = [];
    document.getElementById('filesRows').innerHTML = '';
    updateUI();
}

function updateUI() {
    const hasFiles = uploadState.files.length > 0;
    const isFull   = uploadState.files.length >= MAX_FILES;
    const count    = uploadState.files.length;

    document.getElementById('filesList').classList.toggle('d-none', !hasFiles);
    document.getElementById('dropZone').classList.toggle('d-none', hasFiles);

    const addBtn = document.getElementById('addMoreBtn');
    addBtn.classList.toggle('d-none', !hasFiles || isFull);

    let limitMsg = document.getElementById('limitMsg');
    if (!limitMsg) {
        limitMsg = document.createElement('p');
        limitMsg.id = 'limitMsg';
        limitMsg.className = 'small text-warning mb-2';
        limitMsg.innerHTML = `<i class="fas fa-info-circle mr-1"></i>Límite alcanzado (${MAX_FILES} videos). Para otro partido, completá esta subida primero.`;
        addBtn.parentNode.insertBefore(limitMsg, addBtn.nextSibling);
    }
    limitMsg.classList.toggle('d-none', !isFull);

    document.getElementById('filesCount').textContent =
        count === 1 ? '1 archivo · 1 Master' :
        `${count} archivos · 1 Master + ${count - 1} Ángulo${count > 2 ? 's' : ''}`;

    document.getElementById('detailsCard').style.removeProperty('display');
    document.getElementById('uploadCard').style.removeProperty('display');
    if (!hasFiles) {
        document.getElementById('detailsCard').style.display = 'none';
        document.getElementById('uploadCard').style.display = 'none';
    }

    document.getElementById('uploadBtnText').textContent =
        count > 1 ? `Subir Partido (${count} videos)` : 'Subir Video';
}

// ─── XML por archivo ─────────────────────────────────────────
function triggerXml(id) {
    document.getElementById('xmlinput_' + id).click();
}

function handleXml(id, input) {
    if (!input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function (e) {
        fetch('{{ route("api.xml.validate") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            body: JSON.stringify({ xml_content: e.target.result }),
        })
            .then(r => r.json())
            .then(data => {
                const item = uploadState.files.find(f => f.id === id);
                if (!item) return;
                if (data.valid) {
                    item.xmlContent = e.target.result;
                    item.xmlName = file.name;
                    updateXmlIndicator(id, file.name, data.preview?.clips_count ?? 0);
                } else {
                    alert('XML inválido: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(() => alert('Error al validar el XML'));
    };
    reader.readAsText(file);
}

// ─── Subida principal ────────────────────────────────────────
async function startUpload() {
    document.getElementById('uploadBtn').disabled = true;
    if (uploadState.isUploading) return;

    const matchDate = document.getElementById('match_date').value;
    const categoryId = document.getElementById('category_id')?.value;
    if (!categoryId) { alert(isClub ? 'Seleccioná una categoría.' : 'Seleccioná el equipo.'); return; }
    if (!matchDate) { alert('Ingresá la fecha del partido.'); return; }
    if (uploadState.files.length === 0) { alert('Seleccioná al menos un video.'); return; }

    uploadState.isUploading = true;
    uploadState.masterVideoId = null;

    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('uploadProgress').classList.remove('d-none');

    // Resolver rival y torneo una sola vez antes de iterar archivos
    const commonData = await resolveCommonData();

    let success = 0, failed = 0, lastVideoId = null;

    for (let i = 0; i < uploadState.files.length; i++) {
        const item = uploadState.files[i];
        setStatus(`Subiendo ${i + 1}/${uploadState.files.length}: ${item.file.name}`);
        showRowProgress(item.id);

        try {
            const result = await uploadToCloudflare(item, commonData, pct => updateRowProgress(item.id, pct));
            if (item.role === 'master') uploadState.masterVideoId = result.video_id;
            lastVideoId = result.video_id;
            success++;
        } catch (e) {
            console.error('Upload failed for', item.file.name, e);
            failed++;
            markRowFailed(item.id, e.message);
        }
    }

    if (failed === 0) {
        setStatus(`${success} video${success > 1 ? 's' : ''} enviado${success > 1 ? 's' : ''} correctamente`);
        const redirectId = uploadState.masterVideoId || lastVideoId;
        setTimeout(() => {
            window.location.href = redirectId
                ? '{{ url("videos") }}/' + redirectId
                : '{{ route("videos.index") }}';
        }, 1500);
    } else {
        setStatus(`⚠️ ${success} exitosos, ${failed} fallidos`);
        document.getElementById('uploadBtn').disabled = false;
        uploadState.isUploading = false;
    }
}

// ─── Advertencia visible de upload ───────────────────────────
function showUploadWarning(msg) {
    let warn = document.getElementById('uploadWarning');
    if (!warn) {
        warn = document.createElement('div');
        warn.id = 'uploadWarning';
        warn.className = 'alert alert-warning alert-sm py-2 px-3 mb-2';
        warn.style.fontSize = '.8rem';
        // Insertar antes del área del botón de subida
        const btn = document.getElementById('uploadBtn');
        if (btn) btn.closest('.card-body').prepend(warn);
    }
    warn.textContent = msg;
    warn.style.display = 'block';
}

// ─── Resolver rival/torneo nuevos antes de subir ─────────────
async function resolveCommonData() {
    const rivalIdInput     = document.getElementById('rival_team_id');
    const rivalTextInput   = document.getElementById('rival_team_input');
    const tournamentIdInput= document.getElementById('tournament_id');
    const tournamentInput  = document.getElementById('tournament_input');
    const visibilityEl     = document.querySelector('input[name=visibility_type]:checked');

    let rivalTeamId   = null;
    let rivalTeamName = null;
    let tournamentId  = null;

    const rivalId   = rivalIdInput ? rivalIdInput.value.trim() : '';
    const rivalText = rivalTextInput ? rivalTextInput.value.trim() : '';
    const tournId   = tournamentIdInput ? tournamentIdInput.value.trim() : '';
    const tournText = tournamentInput ? tournamentInput.value.trim() : '';

    // Rival: si hay ID en el campo hidden, es un rival existente
    if (rivalId) {
        rivalTeamId = rivalId;
    } else if (rivalText) {
        // Es un rival nuevo — intentar crearlo en el servidor
        try {
            const res  = await fetch('{{ route("admin.rival-teams.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
                body: JSON.stringify({ name: rivalText }),
            });
            const data = await res.json();
            rivalTeamId = data.id;
        } catch (e) {
            rivalTeamName = rivalText; // fallback texto libre
            showUploadWarning('No se pudo registrar el rival en la base de datos. El video se subirá con el nombre como texto libre.');
        }
    }

    // Torneo: si hay ID en el campo hidden, es un torneo existente
    if (tournId) {
        tournamentId = tournId;
    } else if (tournText) {
        // Es un torneo nuevo — intentar crearlo
        try {
            const res  = await fetch('{{ route("api.tournaments.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
                body: JSON.stringify({ name: tournText }),
            });
            const data = await res.json();
            tournamentId = data.id;
        } catch (e) {
            console.warn('Could not create tournament', e);
            showUploadWarning('No se pudo registrar el torneo. El video se subirá sin torneo asociado.');
        }
    }

    return {
        local_team_name:  document.getElementById('local_team_name').value,
        category_id:      document.getElementById('category_id')?.value || null,
        match_date:       document.getElementById('match_date').value,
        division:         isClub ? document.getElementById('division').value : null,
        description:      document.getElementById('description').value,
        visibility_type:  isClub ? (visibilityEl ? visibilityEl.value : 'public') : 'public',
        assignment_notes: isClub ? document.getElementById('assignment_notes').value : '',
        assigned_players: isClub ? Array.from(document.getElementById('assigned_players').selectedOptions).map(o => o.value) : [],
        rival_team_id:    rivalTeamId,
        rival_team_name:  rivalTeamName,
        tournament_id:    tournamentId,
    };
}

// ─── Upload a Cloudflare Stream (TUS) ────────────────────────
async function uploadToCloudflare(item, commonData, onProgress) {
    // Determinar rol efectivo del archivo considerando slave mode de URL
    // En slave mode, TODOS los archivos son slaves del master externo.
    const effectiveIsSlave = isSlaveMode || item.role !== 'master';
    const effectiveMasterVideoId = isSlaveMode
        ? parseInt(masterVideoId, 10)
        : (item.role !== 'master' ? (uploadState.masterVideoId || null) : null);
    const cameraAngleInput = document.getElementById('cameraAngleInput');
    const effectiveCameraAngle = effectiveIsSlave
        ? (cameraAngleInput?.value.trim() || item.title || 'Ángulo adicional')
        : null;

    // 1. Pedir endpoint TUS al servidor
    const initPayload = {
        title:            item.title,
        filename:         item.file.name,
        file_size:        item.file.size,
        mime_type:        item.file.type || 'video/mp4',
        category_id:      commonData.category_id,
        match_date:       commonData.match_date,
        visibility_type:  commonData.visibility_type,
        description:      commonData.description || '',
        local_team_name:  commonData.local_team_name || '',
        rival_team_id:    commonData.rival_team_id || null,
        rival_team_name:  commonData.rival_team_name || null,
        tournament_id:    commonData.tournament_id || null,
        division:         commonData.division || null,
        assignment_notes: commonData.assignment_notes || '',
        assigned_players: commonData.assigned_players,
        is_master:        !effectiveIsSlave,
        master_video_id:  effectiveMasterVideoId,
        camera_angle:     effectiveCameraAngle,
    };

    // 1. Crear video en Bunny y obtener credenciales TUS
    const initRes = await fetch('{{ route("api.upload.bunny.init") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify(initPayload),
    });

    if (!initRes.ok) {
        const err = await initRes.json().catch(() => ({}));
        throw new Error(err.message || `Init falló (HTTP ${initRes.status})`);
    }

    const { video_id, bunny_guid, upload_url, signature, expire, library_id } = await initRes.json();

    // 2. Subir el archivo directo a Bunny via TUS
    await new Promise((resolve, reject) => {
        const upload = new tus.Upload(item.file, {
            endpoint: upload_url,
            retryDelays: [0, 3000, 5000, 10000, 20000],
            chunkSize: 50 * 1024 * 1024, // 50MB por chunk
            headers: {
                AuthorizationSignature: signature,
                AuthorizationExpire:    String(expire),
                VideoId:                bunny_guid,
                LibraryId:              String(library_id),
            },
            metadata: {
                filename: item.file.name,
                filetype: item.file.type || 'video/mp4',
            },
            onProgress(bytesUploaded, bytesTotal) {
                onProgress(Math.round(bytesUploaded / bytesTotal * 100));
            },
            onSuccess() { resolve(); },
            onError(err) { reject(new Error('TUS error: ' + err.message)); },
        });
        upload.start();
    });

    onProgress(100);

    // 3. Notificar al servidor que terminó
    const completeRes = await fetch('{{ route("api.upload.bunny.complete") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({ video_id, bunny_guid }),
    });

    const completeData = await completeRes.json().catch(() => ({}));

    // 4. Si el master tiene XML, importarlo
    if (item.role === 'master' && item.xmlContent) {
        await importXml(video_id, item.xmlContent, item.xmlName);
    }

    return { video_id, ...completeData };
}

// ─── Importar XML post-upload ─────────────────────────────────
async function importXml(videoId, xmlContent, xmlName) {
    try {
        const formData = new FormData();
        const xmlBlob = new Blob([xmlContent], { type: 'text/xml' });
        formData.append('xml_file', xmlBlob, xmlName || 'import.xml');
        formData.append('_token', getCsrf());
        await fetch(`/videos/${videoId}/import-xml`, { method: 'POST', body: formData });
    } catch (e) {
        console.warn('XML import failed (non-critical):', e);
    }
}

// ─── UI helpers ───────────────────────────────────────────────
function setStatus(msg) { document.getElementById('uploadStatusText').textContent = msg; }

function showRowProgress(id) {
    document.getElementById('prog_' + id)?.classList.remove('d-none');
}

function updateRowProgress(id, pct) {
    const bar = document.getElementById('progbar_' + id);
    if (bar) bar.style.width = pct + '%';
    const pctEl = document.getElementById('progpct_' + id);
    if (pctEl) pctEl.textContent = pct + '%';
    const statusEl = document.getElementById('progstatus_' + id);
    if (statusEl) statusEl.textContent = pct >= 100 ? 'Procesando en Bunny...' : 'Subiendo...';
    document.getElementById('uploadPercent').textContent = pct + '%';
    document.getElementById('uploadProgressBar').style.width = pct + '%';
}

function markRowFailed(id, msg) {
    const row = document.getElementById('row_' + id);
    if (row) row.style.borderColor = '#dc3545';
    const statusEl = document.getElementById('progstatus_' + id);
    if (statusEl) { statusEl.textContent = msg || 'Error'; statusEl.style.color = '#dc3545'; }
    const bar = document.getElementById('progbar_' + id);
    if (bar) bar.style.background = '#dc3545';
}

function getCsrf() { return document.querySelector('meta[name=csrf-token]')?.content || ''; }

// ── YouTube source toggle ─────────────────────────────────────────────────────

function setVideoSource(source) {
    document.getElementById('videoSourceInput').value = source;

    const sectionUpload  = document.getElementById('sectionUpload');
    const sectionYoutube = document.getElementById('sectionYoutube');
    const btnUpload      = document.getElementById('btnSourceUpload');
    const btnYoutube     = document.getElementById('btnSourceYoutube');

    if (source === 'youtube') {
        sectionUpload.classList.add('d-none');
        sectionYoutube.classList.remove('d-none');
        btnUpload.classList.remove('btn-rugby');
        btnUpload.classList.add('btn-outline-secondary');
        btnYoutube.classList.remove('btn-outline-danger');
        btnYoutube.classList.add('btn-danger');
        // Limpiar archivos seleccionados si los hay
        clearAllFiles && clearAllFiles();
        // Mostrar cards de detalles y submit para YouTube
        document.getElementById('detailsCard').style.removeProperty('display');
        document.getElementById('uploadCard').style.removeProperty('display');
        document.getElementById('uploadBtnText').textContent = 'Guardar Video de YouTube';
        document.getElementById('uploadBtn').onclick = submitYoutubeVideo;
    } else {
        sectionYoutube.classList.add('d-none');
        sectionUpload.classList.remove('d-none');
        btnYoutube.classList.remove('btn-danger');
        btnYoutube.classList.add('btn-outline-danger');
        btnUpload.classList.remove('btn-outline-secondary');
        btnUpload.classList.add('btn-rugby');
        // Limpiar campo YouTube
        document.getElementById('youtubeUrlInput').value = '';
        document.getElementById('youtubePreviw').classList.add('d-none');
        document.getElementById('youtubeError').classList.add('d-none');
        // Restaurar botón a comportamiento de upload normal
        document.getElementById('uploadBtn').onclick = startUpload;
        // Ocultar cards si no hay archivos
        updateUI();
    }
}

function extractYoutubeId(url) {
    const patterns = [
        /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
        /youtube\.com\/watch\?.*&v=([a-zA-Z0-9_-]{11})/,
    ];
    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match) return match[1];
    }
    return null;
}

function onYoutubeUrlChange(url) {
    const preview = document.getElementById('youtubePreviw');
    const error   = document.getElementById('youtubeError');
    const thumb   = document.getElementById('youtubeThumb');
    const label   = document.getElementById('youtubeVideoIdLabel');

    if (!url.trim()) {
        preview.classList.add('d-none');
        error.classList.add('d-none');
        return;
    }

    const videoId = extractYoutubeId(url.trim());
    if (videoId) {
        thumb.src = `https://img.youtube.com/vi/${videoId}/mqdefault.jpg`;
        label.textContent = `Video ID: ${videoId}`;
        preview.classList.remove('d-none');
        error.classList.add('d-none');
    } else {
        preview.classList.add('d-none');
        error.classList.remove('d-none');
    }
}


async function submitYoutubeVideo() {
    const url = document.getElementById('youtubeUrlInput').value.trim();
    if (!url) {
        alert('Ingresá la URL del video de YouTube.');
        return;
    }
    const videoId = extractYoutubeId(url);
    if (!videoId) {
        alert('La URL de YouTube no es válida.');
        return;
    }

    const matchDate = document.getElementById('match_date').value;
    if (!matchDate) { alert('Ingresá la fecha del partido.'); return; }

    const categoryIdYt = document.getElementById('category_id')?.value;
    if (!categoryIdYt) { alert(isClub ? 'Seleccioná una categoría.' : 'Seleccioná el equipo.'); return; }

    // Reusar resolveCommonData para crear torneo/rival si fueron escritos manualmente
    const commonData = await resolveCommonData();

    // Construir y enviar el form con los datos del partido
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("videos.store") }}';

    const addField = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value || '';
        form.appendChild(input);
    };

    // Auto-generar título: "Local vs Rival (YYYY-MM-DD)"
    const localTeam = commonData.local_team_name || '';
    const rivalTeam = commonData.rival_team_name  || document.getElementById('rival_team_input')?.value || '';
    let autoTitle = localTeam && rivalTeam
        ? `${localTeam} vs ${rivalTeam}`
        : (localTeam || rivalTeam || 'Video YouTube');
    if (matchDate) autoTitle += ` (${matchDate})`;

    addField('_token',           getCsrf());
    addField('video_source',     'youtube');
    addField('youtube_url',      url);
    addField('title',            autoTitle);
    addField('local_team_name',  localTeam);
    addField('match_date',       matchDate);
    addField('description',      commonData.description);
    addField('category_id',      commonData.category_id);
    addField('rival_team_name',  rivalTeam);
    addField('rival_team_id',    commonData.rival_team_id);
    addField('tournament_id',    commonData.tournament_id);
    addField('division',         commonData.division);
    addField('assignment_notes', commonData.assignment_notes);
    addField('visibility_type',  commonData.visibility_type);

    commonData.assigned_players.forEach(p => addField('assigned_players[]', p));

    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
