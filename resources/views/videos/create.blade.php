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

    {{-- ZONA DE ARCHIVOS --}}
    <div class="card card-rugby mb-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cloud-upload-alt mr-2"></i>Seleccionar Videos</h3>
        </div>
        <div class="card-body p-3">

            {{-- Drop Zone --}}
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
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-dark border-secondary">
                                    <i class="fas fa-home text-success"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control bg-dark border-secondary text-white"
                                id="local_team_display" value="{{ $organizationName }}" readonly>
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
                        <select id="rival_team_id" name="rival_team_id" class="form-control form-control-sm select2-rival"
                            style="width:100%">
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Torneo --}}
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold"><i class="fas fa-trophy text-warning mr-1"></i>Torneo</label>
                        <select id="tournament_id" name="tournament_id" class="form-control form-control-sm select2-tournament"
                            style="width:100%">
                        </select>
                    </div>
                </div>

                {{-- Fecha --}}
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold"><i class="fas fa-calendar mr-1"></i>Fecha <span class="text-danger">*</span></label>
                        <input type="date" id="match_date" name="match_date"
                            class="form-control form-control-sm bg-dark border-secondary text-white"
                            value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                {{-- Categoría --}}
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

                {{-- División --}}
                <div class="col-md-2" id="divisionCol" style="display:none">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">División</label>
                        <select id="division" name="division" class="form-control form-control-sm">
                            <option value="primera">Primera</option>
                            <option value="intermedia">Intermedia</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Visibilidad --}}
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

/* Progreso por fila */
.row-progress {
    height: 3px;
    background: #333;
    border-radius: 2px;
    margin-top: 4px;
}
.row-progress-bar {
    height: 100%;
    background: #00B7B5;
    border-radius: 2px;
    transition: width .3s;
}

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

/* Colores rugby */
.btn-rugby { background: #005461; border-color: #005461; color: #fff; }
.btn-rugby:hover { background: #003d4a; border-color: #003d4a; color: #fff; }
.btn-rugby-outline { background: transparent; border: 1px solid #005461; color: #00B7B5; }
.btn-rugby-outline:hover { background: #005461; color: #fff; }
.bg-rugby { background-color: #005461 !important; }
.card-rugby { border-color: #005461; }
.card-rugby .card-header { background: #005461; border-color: #005461; }

/* Select2 dark */
.select2-container--bootstrap4 .select2-selection { background: #1a1a1a !important; border-color: #444 !important; color: #fff !important; }
.select2-container--bootstrap4 .select2-selection__rendered { color: #fff !important; }
.select2-dropdown { background: #1a1a1a !important; border-color: #444 !important; }
.select2-results__option { color: #ccc !important; }
.select2-results__option--highlighted { background: #005461 !important; color: #fff !important; }
</style>
@endpush

@push('scripts')
<script>
// ─── Estado global ───────────────────────────────────────────
const uploadState = {
    files: [],       // [{ id, file, title, role, xmlContent, xmlName }]
    isUploading: false,
    groupKey: null,
};

// ─── Inicialización ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initDropZone();
    initSelect2();
    initVisibility();
    initCategoryListener();
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
    // Rival
    $('#rival_team_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Buscar o ingresar rival...',
        allowClear: true,
        tags: true,
        ajax: {
            url: '{{ route("api.rival-teams.autocomplete") }}',
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results }),
        },
        createTag: params => ({ id: 'new:' + params.term, text: params.term + ' (nuevo)', newTag: true }),
    });

    // Torneo
    $('#tournament_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Buscar o crear torneo...',
        allowClear: true,
        tags: true,
        ajax: {
            url: '{{ route("api.tournaments.autocomplete") }}',
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results }),
        },
        createTag: params => ({ id: 'new:' + params.term, text: params.term + ' (nuevo)', newTag: true }),
    });

    // Jugadores
    $('#assigned_players').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccionar jugadores...',
        allowClear: true,
    });
}

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
function addFiles(fileList) {
    const videoExts = ['mp4','mov','avi','webm','mkv'];
    Array.from(fileList).forEach(file => {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!videoExts.includes(ext)) return;
        if (file.size > 8 * 1024 * 1024 * 1024) {
            alert(`"${file.name}" supera 8GB.`); return;
        }
        if (uploadState.files.length >= 10) { alert('Máximo 10 videos por subida.'); return; }

        const id = 'f_' + Date.now() + '_' + Math.random().toString(36).slice(2);
        const nameWithoutExt = file.name.replace(/\.[^.]+$/, '');
        uploadState.files.push({
            id, file,
            title: nameWithoutExt,
            role: uploadState.files.length === 0 ? 'master' : 'slave',
            xmlContent: null,
            xmlName: null,
        });
        renderFileRow(uploadState.files[uploadState.files.length - 1]);
    });
    updateUI();
}

function renderFileRow(item) {
    const sizeMB = (item.file.size / 1024 / 1024).toFixed(1);
    const sizeText = sizeMB > 1024 ? (sizeMB / 1024).toFixed(1) + ' GB' : sizeMB + ' MB';

    const row = document.createElement('div');
    row.className = 'file-row';
    row.id = 'row_' + item.id;
    row.innerHTML = `
        <div class="file-thumb"><i class="fas fa-film"></i></div>
        <div class="file-info">
            <div class="file-name" title="${item.file.name}">${item.file.name}</div>
            <div class="file-size">${sizeText}</div>
            <div class="row-progress d-none" id="prog_${item.id}"><div class="row-progress-bar" id="progbar_${item.id}" style="width:0%"></div></div>
        </div>
        <div class="file-actions">
            <button type="button" class="btn-xml" id="xmlbtn_${item.id}" onclick="triggerXml('${item.id}')">
                <i class="fas fa-file-code mr-1"></i>XML
            </button>
            <input type="file" accept=".xml" class="d-none" id="xmlinput_${item.id}" onchange="handleXml('${item.id}', this)">
            <select class="role-select" id="role_${item.id}" onchange="changeRole('${item.id}', this.value)">
                <option value="master" ${item.role === 'master' ? 'selected' : ''}>⭐ Master</option>
                <option value="slave"  ${item.role === 'slave'  ? 'selected' : ''}>📹 Ángulo</option>
                <option value="standalone" ${item.role === 'standalone' ? 'selected' : ''}>🎬 Individual</option>
            </select>
            <button type="button" class="btn-remove-file" onclick="removeFile('${item.id}')" title="Quitar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.getElementById('filesRows').appendChild(row);
}

function changeRole(id, role) {
    const item = uploadState.files.find(f => f.id === id);
    if (!item) return;

    // Solo puede haber un master
    if (role === 'master') {
        uploadState.files.forEach(f => {
            if (f.role === 'master' && f.id !== id) {
                f.role = 'slave';
                const sel = document.getElementById('role_' + f.id);
                if (sel) sel.value = 'slave';
            }
        });
    }
    item.role = role;

    // XML solo para master
    const xmlBtn = document.getElementById('xmlbtn_' + id);
    if (xmlBtn) xmlBtn.style.display = role === 'slave' ? 'none' : '';
}

function removeFile(id) {
    uploadState.files = uploadState.files.filter(f => f.id !== id);
    const row = document.getElementById('row_' + id);
    if (row) row.remove();

    // Si no queda master, el primero pasa a serlo
    if (uploadState.files.length > 0 && !uploadState.files.find(f => f.role === 'master')) {
        uploadState.files[0].role = 'master';
        const sel = document.getElementById('role_' + uploadState.files[0].id);
        if (sel) sel.value = 'master';
    }
    updateUI();
}

function clearAllFiles() {
    uploadState.files = [];
    document.getElementById('filesRows').innerHTML = '';
    updateUI();
}

function updateUI() {
    const hasFiles = uploadState.files.length > 0;
    document.getElementById('filesList').classList.toggle('d-none', !hasFiles);

    const count = uploadState.files.length;
    document.getElementById('filesCount').textContent =
        count === 1 ? '1 archivo seleccionado' : `${count} archivos seleccionados`;

    // Mostrar/ocultar cards de detalles y upload
    document.getElementById('detailsCard').style.removeProperty('display');
    document.getElementById('uploadCard').style.removeProperty('display');
    if (!hasFiles) {
        document.getElementById('detailsCard').style.display = 'none';
        document.getElementById('uploadCard').style.display = 'none';
    }

    // Texto del botón
    document.getElementById('uploadBtnText').textContent =
        count > 1 ? `Subir ${count} Videos` : 'Subir Video';
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
        // Validar XML via API
        const formData = new FormData();
        formData.append('xml_file', file);
        formData.append('_token', document.querySelector('meta[name=csrf-token]').content);

        fetch('{{ route("api.xml.validate") }}', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                const item = uploadState.files.find(f => f.id === id);
                if (!item) return;
                if (data.valid) {
                    item.xmlContent = e.target.result;
                    item.xmlName = file.name;
                    const btn = document.getElementById('xmlbtn_' + id);
                    btn.classList.add('has-xml');
                    btn.innerHTML = `<i class="fas fa-check-circle mr-1"></i>${data.clips_count} clips`;
                } else {
                    alert('XML inválido: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(() => alert('Error al validar el XML'));
    };
    reader.readAsText(file);
}

// ─── Subida ───────────────────────────────────────────────────
async function startUpload() {
    if (uploadState.isUploading) return;

    const categoryId = document.getElementById('category_id').value;
    const matchDate  = document.getElementById('match_date').value;

    if (!categoryId) { alert('Seleccioná una categoría.'); return; }
    if (!matchDate)  { alert('Ingresá la fecha del partido.'); return; }
    if (uploadState.files.length === 0) { alert('Seleccioná al menos un video.'); return; }

    uploadState.isUploading = true;
    uploadState.groupKey = `batch_${Date.now()}`;

    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('uploadProgress').classList.remove('d-none');

    const commonData = getCommonData();
    let success = 0, failed = 0;

    for (let i = 0; i < uploadState.files.length; i++) {
        const item = uploadState.files[i];
        setStatus(`Subiendo ${i + 1}/${uploadState.files.length}: ${item.file.name}`);
        showRowProgress(item.id);

        try {
            await uploadFile(item, commonData, (pct) => updateRowProgress(item.id, pct));
            success++;
        } catch(e) {
            console.error(e);
            failed++;
            markRowFailed(item.id);
        }
    }

    const total = uploadState.files.length;
    if (failed === 0) {
        setStatus(`✅ ${success} video${success > 1 ? 's' : ''} subido${success > 1 ? 's' : ''} correctamente`);
        setTimeout(() => window.location.href = '{{ route("videos.index") }}', 1500);
    } else {
        setStatus(`⚠️ ${success} exitosos, ${failed} fallidos`);
        document.getElementById('uploadBtn').disabled = false;
        uploadState.isUploading = false;
    }
}

function getCommonData() {
    const rivalSelect = document.getElementById('rival_team_id');
    const tournamentSelect = document.getElementById('tournament_id');
    const visibilityEl = document.querySelector('input[name=visibility_type]:checked');

    return {
        category_id:      document.getElementById('category_id').value,
        match_date:       document.getElementById('match_date').value,
        division:         document.getElementById('division').value,
        description:      document.getElementById('description').value,
        visibility_type:  visibilityEl ? visibilityEl.value : 'public',
        assignment_notes: document.getElementById('assignment_notes').value,
        assigned_players: Array.from(document.getElementById('assigned_players').selectedOptions).map(o => o.value),
        rival_team_id:    rivalSelect.value && !rivalSelect.value.startsWith('new:') ? rivalSelect.value : null,
        rival_team_name:  rivalSelect.value && rivalSelect.value.startsWith('new:') ? rivalSelect.value.replace('new:', '') : (rivalSelect.options[rivalSelect.selectedIndex]?.text || null),
        tournament_id:    tournamentSelect.value && !tournamentSelect.value.startsWith('new:') ? tournamentSelect.value : null,
        tournament_name:  tournamentSelect.value && tournamentSelect.value.startsWith('new:') ? tournamentSelect.value.replace('new:', '') : null,
    };
}

async function uploadFile(item, commonData, onProgress) {
    const MULTIPART_THRESHOLD = 100 * 1024 * 1024; // 100MB

    // Crear torneo si es nuevo
    let tournamentId = commonData.tournament_id;
    if (!tournamentId && commonData.tournament_name) {
        const res = await fetch('{{ route("api.tournaments.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            body: JSON.stringify({ name: commonData.tournament_name }),
        });
        const data = await res.json();
        tournamentId = data.id;
    }

    // Crear rival si es nuevo
    let rivalTeamId = commonData.rival_team_id;
    if (!rivalTeamId && commonData.rival_team_name) {
        const res = await fetch('{{ route("api.rival-teams.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            body: JSON.stringify({ name: commonData.rival_team_name }),
        });
        const data = await res.json();
        rivalTeamId = data.id;
    }

    const isMaster = item.role === 'master';
    const isGrouped = item.role !== 'standalone';

    const formData = new FormData();
    formData.append('video_file', item.file);
    formData.append('title', item.title || item.file.name.replace(/\.[^.]+$/, ''));
    formData.append('category_id', commonData.category_id);
    formData.append('match_date', commonData.match_date);
    formData.append('division', commonData.division || '');
    formData.append('description', commonData.description || '');
    formData.append('visibility_type', commonData.visibility_type);
    formData.append('assignment_notes', commonData.assignment_notes || '');
    formData.append('rival_team_id', rivalTeamId || '');
    formData.append('rival_team_name', commonData.rival_team_name || '');
    formData.append('tournament_id', tournamentId || '');
    formData.append('_token', getCsrf());

    if (isGrouped) {
        formData.append('is_master', isMaster ? '1' : '0');
        formData.append('group_key', uploadState.groupKey);
        if (!isMaster) formData.append('camera_angle', item.title || item.file.name);
    }

    commonData.assigned_players.forEach(p => formData.append('assigned_players[]', p));

    if (isMaster && item.xmlContent) {
        const xmlBlob = new Blob([item.xmlContent], { type: 'text/xml' });
        formData.append('xml_file', xmlBlob, item.xmlName || 'import.xml');
    }

    if (item.file.size >= MULTIPART_THRESHOLD) {
        return uploadMultipart(item.file, formData, onProgress);
    } else {
        return uploadSimple(formData, onProgress);
    }
}

function uploadSimple(formData, onProgress) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.upload.onprogress = e => { if (e.lengthComputable) onProgress(Math.round(e.loaded/e.total*100)); };
        xhr.onload = () => {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.success !== false) resolve(res); else reject(new Error(res.message));
            } catch(e) { if (xhr.status < 400) resolve({}); else reject(new Error('Error ' + xhr.status)); }
        };
        xhr.onerror = () => reject(new Error('Error de red'));
        xhr.open('POST', '{{ route("videos.store") }}');
        xhr.send(formData);
    });
}

async function uploadMultipart(file, baseFormData, onProgress) {
    const CHUNK = 100 * 1024 * 1024;
    const totalParts = Math.ceil(file.size / CHUNK);

    // Initiate
    const initData = new FormData();
    initData.append('file_name', file.name);
    initData.append('file_size', file.size);
    initData.append('content_type', file.type || 'video/mp4');
    initData.append('total_parts', totalParts);
    initData.append('_token', getCsrf());
    const initRes = await fetch('{{ route("api.upload.multipart.initiate") }}', { method: 'POST', body: initData });
    const { upload_id, key } = await initRes.json();

    // Get part URLs
    const urlData = new FormData();
    urlData.append('upload_id', upload_id);
    urlData.append('key', key);
    urlData.append('total_parts', totalParts);
    urlData.append('_token', getCsrf());
    const urlRes = await fetch('{{ route("api.upload.multipart.part-urls") }}', { method: 'POST', body: urlData });
    const { part_urls } = await urlRes.json();

    // Upload parts
    const parts = [];
    let uploaded = 0;
    for (let i = 0; i < totalParts; i++) {
        const start = i * CHUNK;
        const end = Math.min(start + CHUNK, file.size);
        const chunk = file.slice(start, end);

        const res = await fetch(part_urls[i], { method: 'PUT', body: chunk });
        const etag = res.headers.get('ETag');
        parts.push({ PartNumber: i + 1, ETag: etag });
        uploaded += (end - start);
        onProgress(Math.round(uploaded / file.size * 100));
    }

    // Complete
    const completeData = new FormData();
    completeData.append('upload_id', upload_id);
    completeData.append('key', key);
    completeData.append('parts', JSON.stringify(parts));
    completeData.append('_token', getCsrf());
    for (const [k, v] of baseFormData.entries()) completeData.append(k, v);
    completeData.delete('video_file');

    const completeRes = await fetch('{{ route("api.upload.multipart.complete") }}', { method: 'POST', body: completeData });
    return completeRes.json();
}

// ─── UI helpers ───────────────────────────────────────────────
function setStatus(msg) {
    document.getElementById('uploadStatusText').textContent = msg;
}
function showRowProgress(id) {
    document.getElementById('prog_' + id)?.classList.remove('d-none');
}
function updateRowProgress(id, pct) {
    const bar = document.getElementById('progbar_' + id);
    if (bar) bar.style.width = pct + '%';
    document.getElementById('uploadPercent').textContent = pct + '%';
    document.getElementById('uploadProgressBar').style.width = pct + '%';
}
function markRowFailed(id) {
    const row = document.getElementById('row_' + id);
    if (row) row.style.borderColor = '#dc3545';
}
function getCsrf() {
    return document.querySelector('meta[name=csrf-token]')?.content || '';
}
</script>
@endpush
