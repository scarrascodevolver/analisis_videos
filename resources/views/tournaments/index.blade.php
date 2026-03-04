@extends('layouts.app')

@section('page_title', 'Gestión de Torneos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Torneos</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
<div class="col-lg-10">

    <div class="card card-rugby">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-trophy mr-2"></i>Torneos</h3>
            <div class="d-flex align-items-center" style="gap:12px;">
                <small class="text-muted d-none d-md-inline">Doble clic en Nombre o Temporada para editar</small>
                @if(auth()->user()->currentOrganization()?->isAsociacion())
                    <button type="button" class="btn btn-rugby btn-sm" data-toggle="modal" data-target="#modalNuevoTorneo">
                        <i class="fas fa-plus mr-1"></i> Nuevo Torneo
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">

            @if(auth()->user()->currentOrganization()?->isAsociacion())
                {{-- Tab navigation --}}
                <ul class="nav nav-tabs border-bottom-0 px-3 pt-2" id="tournamentTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#pane-torneos" role="tab">
                            <i class="fas fa-trophy mr-1"></i> Mis Torneos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-solicitudes" data-toggle="tab" href="#pane-solicitudes" role="tab">
                            <i class="fas fa-user-clock mr-1"></i> Solicitudes
                            @if($pendingRegistrations->count() > 0)
                                <span class="badge badge-danger ml-1">{{ $pendingRegistrations->count() }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-torneos" role="tabpanel">
            @endif

            {{-- Tournaments list --}}
            @if($tournaments->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-trophy fa-3x mb-3" style="color:#005461;opacity:.4"></i>
                    <p>No hay torneos registrados todavía.</p>
                    <p class="small">Creá el primer torneo con el botón "Nuevo Torneo".</p>
                </div>
            @else
                <div id="tournamentsList">
                    @foreach($tournaments as $tournament)
                    @php $regCount = $tournament->registrations->count(); @endphp
                    <div class="tournament-item" id="tr_{{ $tournament->id }}"
                         style="border-bottom:1px solid rgba(255,255,255,.07);padding:14px 20px;">

                        {{-- Fila superior: nombre + stats + acciones --}}
                        <div class="d-flex align-items-center" style="gap:12px;flex-wrap:wrap;">

                            {{-- Nombre editable --}}
                            <i class="fas fa-trophy" style="color:#b8860b;font-size:.9rem;flex-shrink:0;"></i>
                            <span class="editable-cell tournament-name-text"
                                  data-field="name"
                                  data-id="{{ $tournament->id }}"
                                  title="Doble clic para editar"
                                  style="font-weight:600;font-size:.95rem;cursor:default;">
                                {{ $tournament->name }}
                            </span>

                            {{-- Temporada --}}
                            <span class="editable-cell {{ $tournament->season ? '' : 'season-empty' }}"
                                  data-field="season"
                                  data-id="{{ $tournament->id }}"
                                  title="Doble clic para {{ $tournament->season ? 'editar' : 'agregar' }} temporada"
                                  style="font-size:.8rem;{{ $tournament->season ? 'color:rgba(255,255,255,.45);background:rgba(255,255,255,.07);padding:2px 8px;border-radius:10px;' : 'color:rgba(255,255,255,.2);border-bottom-style:dotted;' }}cursor:default;">
                                {{ $tournament->season ?: '—' }}
                            </span>

                            {{-- Spacer --}}
                            <div class="flex-grow-1"></div>

                            {{-- Videos count --}}
                            <span style="font-size:.8rem;color:rgba(255,255,255,.45);" title="{{ $tournament->videos_count }} video(s)">
                                <i class="fas fa-film mr-1"></i>{{ $tournament->videos_count }}
                            </span>

                            {{-- Inscriptos count --}}
                            <a href="{{ route('tournaments.show', $tournament) }}"
                               style="font-size:.8rem;text-decoration:none;white-space:nowrap;
                                      color:{{ $regCount > 0 ? '#00B7B5' : 'rgba(255,255,255,.35)' }};"
                               title="Ver clubes inscriptos">
                                <i class="fas fa-users mr-1"></i>{{ $regCount }}
                                {{ $regCount === 1 ? 'inscripto' : 'inscriptos' }}
                            </a>

                            {{-- Toggle público --}}
                            @if(auth()->user()->currentOrganization()?->isAsociacion())
                                <button type="button"
                                        class="btn btn-xs {{ $tournament->is_public ? 'btn-success' : 'btn-outline-secondary' }} btn-toggle-public"
                                        data-tournament-id="{{ $tournament->id }}"
                                        title="{{ $tournament->is_public ? 'Público — click para ocultar' : 'Privado — click para publicar' }}"
                                        style="min-width:80px;">
                                    <i class="fas {{ $tournament->is_public ? 'fa-globe' : 'fa-lock' }} mr-1"></i>
                                    {{ $tournament->is_public ? 'Público' : 'Privado' }}
                                </button>
                            @endif

                            {{-- Eliminar --}}
                            @if($tournament->videos_count > 0)
                                <button type="button"
                                        class="btn btn-xs btn-outline-secondary"
                                        disabled
                                        title="No se puede eliminar: tiene {{ $tournament->videos_count }} video(s)"
                                        data-toggle="tooltip">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @else
                                <form method="POST"
                                      action="{{ route('tournaments.destroy', $tournament) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el torneo {{ addslashes($tournament->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Eliminar torneo">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif

                        </div>

                        {{-- Fila inferior: divisiones --}}
                        @if(auth()->user()->currentOrganization()?->isAsociacion())
                        <div class="d-flex flex-wrap align-items-center mt-2" style="gap:6px;padding-left:22px;"
                             id="divpills_{{ $tournament->id }}">

                            @foreach($tournament->divisions as $div)
                                <span class="div-pill" data-div-id="{{ $div->id }}">
                                    {{ $div->name }}
                                    <a href="#" class="div-pill-remove"
                                       onclick="removeDivision(event, {{ $div->id }}, {{ $tournament->id }})">
                                        &times;
                                    </a>
                                </span>
                            @endforeach

                            {{-- Botón / input agregar división --}}
                            <button class="btn-add-div"
                                    id="btn-add-div-{{ $tournament->id }}"
                                    onclick="showAddDiv({{ $tournament->id }})">
                                <i class="fas fa-plus" style="font-size:.7rem;"></i> División
                            </button>
                            <span class="add-div-wrap" id="add-div-wrap-{{ $tournament->id }}"
                                  style="display:none;align-items:center;gap:6px;">
                                <input type="text"
                                       class="add-div-input"
                                       id="add-div-input-{{ $tournament->id }}"
                                       placeholder="Nombre de la división..."
                                       maxlength="100"
                                       data-tournament-id="{{ $tournament->id }}">
                                <button class="btn-div-ok" onclick="submitDivision({{ $tournament->id }})">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-div-cancel" onclick="cancelAddDiv({{ $tournament->id }})">
                                    &times;
                                </button>
                            </span>

                        </div>
                        @endif

                    </div>
                    @endforeach
                </div>
            @endif

            @if(auth()->user()->currentOrganization()?->isAsociacion())
                    </div>{{-- end pane-torneos --}}

                    <div class="tab-pane fade" id="pane-solicitudes" role="tabpanel">
                        @if($pendingRegistrations->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3" style="color:#005461;opacity:.4"></i>
                                <p>No hay solicitudes pendientes.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Torneo</th>
                                            <th>Club</th>
                                            <th>Solicitó</th>
                                            <th class="text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pendingRegistrations as $reg)
                                        <tr id="reg-row-{{ $reg->id }}">
                                            <td>{{ $reg->tournament->name }}</td>
                                            <td>
                                                @if($reg->clubOrganization->logo_path)
                                                    <img src="{{ asset('storage/' . $reg->clubOrganization->logo_path) }}"
                                                         style="width:24px;height:24px;object-fit:contain;border-radius:4px;margin-right:6px;">
                                                @endif
                                                {{ $reg->clubOrganization->name }}
                                            </td>
                                            <td class="text-muted small">{{ $reg->registered_at->diffForHumans() }}</td>
                                            <td class="text-right">
                                                <button class="btn btn-xs btn-success btn-approve-reg"
                                                        data-reg-id="{{ $reg->id }}">
                                                    <i class="fas fa-check"></i> Aprobar
                                                </button>
                                                <button class="btn btn-xs btn-outline-danger btn-reject-reg"
                                                        data-reg-id="{{ $reg->id }}">
                                                    <i class="fas fa-times"></i> Rechazar
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>{{-- end pane-solicitudes --}}
                </div>{{-- end tab-content --}}
            @endif

        </div>
    </div>

</div>
</div>
@endsection

{{-- ── Modal: Nuevo Torneo ──────────────────────────────────────────── --}}
@if(auth()->user()->currentOrganization()?->isAsociacion())
<div class="modal fade" id="modalNuevoTorneo" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content" style="background:#1a1a1a;border:1px solid rgba(255,255,255,.12);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.1);padding:12px 18px;">
                <h6 class="modal-title" style="color:#fff;">
                    <i class="fas fa-trophy mr-2" style="color:#b8860b;"></i> Nuevo Torneo
                </h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" style="padding:18px;">
                <div class="form-group mb-3">
                    <label style="font-size:.78rem;color:#aaa;font-weight:600;display:block;margin-bottom:4px;">
                        Nombre del torneo <span style="color:#dc3545;">*</span>
                    </label>
                    <input type="text" id="nt-name"
                           class="form-control form-control-sm"
                           style="background:#111;border:1px solid #444;color:#fff;"
                           placeholder="Ej: URBA 2026" maxlength="255">
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:.78rem;color:#aaa;font-weight:600;display:block;margin-bottom:4px;">
                        Temporada (opcional)
                    </label>
                    <input type="text" id="nt-season"
                           class="form-control form-control-sm"
                           style="background:#111;border:1px solid #444;color:#fff;"
                           placeholder="Ej: 2026" maxlength="20">
                </div>
                <div id="nt-error" class="text-danger small mt-2 d-none"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.1);padding:10px 18px;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-rugby btn-sm" id="nt-save-btn">
                    <i class="fas fa-save mr-1"></i> Crear
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Agregar Divisiones (post-creación) ─────────────────────── --}}
<div class="modal fade" id="modalNuevoTorneoDivisiones" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document" style="max-width:500px;">
        <div class="modal-content" style="background:#1a1a1a;border:1px solid rgba(255,255,255,.12);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.1);padding:12px 18px;">
                <h6 class="modal-title" style="color:#fff;">
                    <i class="fas fa-layer-group mr-2" style="color:#00B7B5;"></i>
                    Agregar divisiones al torneo
                </h6>
                <small id="nd-tournament-name" style="color:#aaa;margin-left:8px;"></small>
            </div>
            <div class="modal-body" style="padding:18px;">
                <p style="font-size:.83rem;color:#aaa;margin-bottom:14px;">
                    Agregá las divisiones del torneo. Podés saltear este paso si no aplica.
                </p>

                {{-- Sugerencias rápidas --}}
                <div style="margin-bottom:14px;">
                    <div style="font-size:.78rem;color:#aaa;font-weight:600;margin-bottom:8px;">Sugerencias rápidas:</div>
                    <div style="display:flex;flex-wrap:wrap;gap:7px;" id="nd-chips">
                        @foreach(['Adulta','M18','M16','M14','M12','M10','M8','Seven','Femenino'] as $suggestion)
                        <button type="button" class="nd-chip-btn"
                                data-name="{{ $suggestion }}"
                                style="background:transparent;border:1px solid rgba(0,183,181,.45);color:rgba(0,183,181,.8);border-radius:20px;padding:4px 14px;font-size:.82rem;cursor:pointer;transition:all .15s;">
                            {{ $suggestion }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Input nombre personalizado --}}
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                    <input type="text" id="nd-custom-input"
                           style="flex:1;background:#111;border:1px solid #444;color:#fff;border-radius:4px;padding:5px 12px;font-size:.85rem;outline:none;"
                           placeholder="Otra división...">
                    <button type="button" id="nd-add-btn"
                            style="background:rgba(0,183,181,.15);border:1px solid #00B7B5;color:#00B7B5;border-radius:4px;padding:5px 14px;font-size:.82rem;cursor:pointer;white-space:nowrap;">
                        <i class="fas fa-plus mr-1"></i> Agregar
                    </button>
                </div>

                {{-- Divisiones agregadas --}}
                <div id="nd-added-pills" style="display:flex;flex-wrap:wrap;gap:7px;min-height:28px;"></div>
                <div id="nd-div-error" class="text-danger small mt-1 d-none"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.1);padding:10px 18px;justify-content:space-between;align-items:center;">
                <a href="#" id="nd-skip-link"
                   style="font-size:.8rem;color:rgba(255,255,255,.4);text-decoration:none;">
                    Continuar sin divisiones
                </a>
                <button type="button" id="nd-continue-btn"
                        class="btn btn-rugby btn-sm">
                    <i class="fas fa-check mr-1"></i> Continuar
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
/* ── Tabs dark theme ──────────────────────────────────────── */
#tournamentTabs {
    border-bottom: 1px solid rgba(255,255,255,.1);
}
#tournamentTabs .nav-link {
    color: rgba(255,255,255,.5);
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    padding: 8px 16px;
    transition: color .2s;
}
#tournamentTabs .nav-link:hover {
    color: rgba(255,255,255,.8);
    background: rgba(255,255,255,.04);
}
#tournamentTabs .nav-link.active {
    color: #00B7B5;
    background: transparent;
    border-bottom: 2px solid #00B7B5;
}
.tab-content {
    background: transparent;
}

/* ── Division pills ───────────────────────────────────────── */
.div-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(0,183,181,.13);
    border: 1px solid rgba(0,183,181,.4);
    color: #00B7B5;
    border-radius: 20px;
    padding: 5px 14px;
    font-size: .88rem;
    font-weight: 500;
    line-height: 1.3;
}
.div-pill-remove {
    color: rgba(0,183,181,.6);
    text-decoration: none;
    font-size: 1rem;
    line-height: 1;
    margin-left: 2px;
}
.div-pill-remove:hover { color: #dc3545; text-decoration: none; }

.btn-add-div {
    background: transparent;
    border: 1px dashed rgba(0,183,181,.5);
    color: rgba(0,183,181,.75);
    border-radius: 20px;
    padding: 5px 13px;
    font-size: .82rem;
    cursor: pointer;
    transition: all .15s;
    line-height: 1.3;
}
.btn-add-div:hover {
    border-color: #00B7B5;
    color: #00B7B5;
    background: rgba(0,183,181,.08);
}

.add-div-input {
    background: #1a1a1a;
    border: 1px solid #00B7B5;
    color: #fff;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: .85rem;
    width: 200px;
    outline: none;
}
.add-div-input:focus { box-shadow: 0 0 0 2px rgba(0,183,181,.25); }

.btn-div-ok {
    background: rgba(0,183,181,.15);
    border: 1px solid #00B7B5;
    color: #00B7B5;
    border-radius: 50%;
    width: 28px; height: 28px;
    cursor: pointer;
    font-size: .8rem;
    display: inline-flex; align-items: center; justify-content: center;
}
.btn-div-ok:hover { background: #00B7B5; color: #fff; }

.btn-div-cancel {
    background: transparent;
    border: 1px solid #555;
    color: #888;
    border-radius: 50%;
    width: 28px; height: 28px;
    cursor: pointer;
    font-size: 1rem;
    display: inline-flex; align-items: center; justify-content: center;
    line-height: 1;
}
.btn-div-cancel:hover { border-color: #dc3545; color: #dc3545; }

.division-row td { border-top: none !important; padding-top: 4px !important; }

/* ── Editable cells ───────────────────────────────────────── */
.editable-cell {
    cursor: pointer;
    border-bottom: 1px dashed #444;
    padding: 2px 4px;
    border-radius: 3px;
    transition: background .15s;
}
.editable-cell:hover {
    background: rgba(212,160,23,.1);
    border-bottom-color: #b8860b;
}
.editable-cell.editing {
    display: none;
}
.edit-input {
    background: #1a1a1a;
    border: 1px solid #b8860b;
    color: #fff;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: .9rem;
    width: 100%;
    box-sizing: border-box;
}
.edit-input:focus { outline: none; box-shadow: 0 0 0 2px rgba(212,160,23,.3); }
.save-indicator {
    font-size: .72rem;
    color: #b8860b;
    margin-left: 6px;
}
.error-indicator {
    font-size: .72rem;
    color: #dc3545;
    margin-left: 6px;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Activate Bootstrap tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Inline editing via double-click
    document.querySelectorAll('.editable-cell').forEach(function (span) {
        span.addEventListener('dblclick', function () {
            startEdit(this);
        });
    });
});

function startEdit(span) {
    if (span.querySelector('input')) return; // already editing

    const field    = span.dataset.field;
    const id       = span.dataset.id;
    const original = span.textContent.trim() === '—' ? '' : span.textContent.trim();

    const input = document.createElement('input');
    input.type        = 'text';
    input.className   = 'edit-input';
    input.value       = original;
    input.maxLength   = field === 'season' ? 20 : 255;
    input.placeholder = field === 'name' ? 'Nombre del torneo' : 'ej. 2025';

    span.classList.add('editing');
    span.parentNode.insertBefore(input, span.nextSibling);
    input.focus();
    input.select();

    function save() {
        const newVal = input.value.trim();

        // Name is required
        if (field === 'name' && !newVal) {
            cancelEdit();
            return;
        }

        if (newVal === original) {
            cancelEdit();
            return;
        }

        const indicator = document.createElement('span');
        indicator.className = 'save-indicator';
        indicator.textContent = 'Guardando...';
        input.parentNode.insertBefore(indicator, input.nextSibling);

        // Build payload with current name + season from the same row
        const row     = document.getElementById('tr_' + id);
        const cells   = row.querySelectorAll('.editable-cell');
        const payload = {};
        cells.forEach(c => {
            const txt = c.textContent.trim();
            payload[c.dataset.field] = txt === '—' ? '' : txt;
        });
        payload[field] = newVal; // override with new value

        fetch('/tournaments/' + id, {
            method:  'PUT',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                span.textContent = newVal || '—';
                span.classList.remove('editing');
                // Update season span style when it transitions empty ↔ filled
                if (field === 'season') {
                    if (newVal) {
                        span.classList.remove('season-empty');
                        span.style.cssText = 'font-size:.8rem;color:rgba(255,255,255,.45);background:rgba(255,255,255,.07);padding:2px 8px;border-radius:10px;cursor:default;';
                    } else {
                        span.classList.add('season-empty');
                        span.style.cssText = 'font-size:.8rem;color:rgba(255,255,255,.2);border-bottom-style:dotted;cursor:default;';
                    }
                }
                input.remove();
                indicator.remove();
            } else {
                showError(indicator, 'Error al guardar');
            }
        })
        .catch(() => {
            showError(indicator, 'Error de red');
        });
    }

    function cancelEdit() {
        span.classList.remove('editing');
        input.remove();
    }

    function showError(indicator, msg) {
        indicator.className = 'error-indicator';
        indicator.textContent = msg;
        setTimeout(() => {
            span.classList.remove('editing');
            input.remove();
            indicator.remove();
        }, 2000);
    }

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter')  { e.preventDefault(); save(); }
        if (e.key === 'Escape') { cancelEdit(); }
    });
    input.addEventListener('blur', function () {
        // Small delay to allow Enter key to fire first
        setTimeout(save, 150);
    });
}

// ── Division inline management ──────────────────────────────
function showAddDiv(tournamentId) {
    document.getElementById('btn-add-div-' + tournamentId).style.display = 'none';
    const wrap = document.getElementById('add-div-wrap-' + tournamentId);
    wrap.style.display = 'inline-flex';
    document.getElementById('add-div-input-' + tournamentId).focus();
}

function cancelAddDiv(tournamentId) {
    document.getElementById('add-div-wrap-' + tournamentId).style.display = 'none';
    document.getElementById('btn-add-div-' + tournamentId).style.display = '';
    document.getElementById('add-div-input-' + tournamentId).value = '';
}

function submitDivision(tournamentId) {
    const input = document.getElementById('add-div-input-' + tournamentId);
    const name  = input.value.trim();
    if (!name) return;

    const csrf = document.querySelector('meta[name=csrf-token]').content;

    fetch('/api/tournaments/' + tournamentId + '/divisions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ name }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            const container = document.getElementById('divpills_' + tournamentId);
            const addWrap   = document.getElementById('add-div-wrap-' + tournamentId);

            const pill = document.createElement('span');
            pill.className      = 'div-pill';
            pill.dataset.divId  = data.division.id;
            pill.innerHTML      = '<i class="fas fa-layer-group" style="font-size:.75rem;opacity:.7;"></i> ' +
                data.division.name +
                ' <a href="#" class="div-pill-remove" onclick="removeDivision(event,' + data.division.id + ',' + tournamentId + ')">&times;</a>';

            container.insertBefore(pill, addWrap);
            cancelAddDiv(tournamentId);
        } else {
            alert(data.error || 'Error al crear la división.');
        }
    });
}

function removeDivision(event, divId, tournamentId) {
    event.preventDefault();
    if (!confirm('¿Eliminar esta división?')) return;

    const csrf = document.querySelector('meta[name=csrf-token]').content;

    fetch('/api/divisions/' + divId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf },
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            document.querySelectorAll('.div-pill[data-div-id="' + divId + '"]').forEach(el => el.remove());
        } else {
            alert(data.error || 'No se pudo eliminar.');
        }
    });
}

// Enter key en input de nueva división
document.addEventListener('keydown', function(e) {
    if (!e.target.classList.contains('add-div-input')) return;
    const tid = e.target.dataset.tournamentId;
    if (e.key === 'Enter') { e.preventDefault(); submitDivision(tid); }
    if (e.key === 'Escape') cancelAddDiv(tid);
});

// Approve/Reject registration requests
document.querySelectorAll('.btn-approve-reg, .btn-reject-reg').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const regId  = this.dataset.regId;
        const action = this.classList.contains('btn-approve-reg') ? 'approve' : 'reject';
        const self   = this;
        self.disabled = true;

        fetch('/tournament-registrations/' + regId + '/' + action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                // Remove the row
                const row = document.getElementById('reg-row-' + regId);
                if (row) row.remove();

                // Update badge count on Solicitudes tab
                const badge = document.querySelector('#tab-solicitudes .badge');
                if (badge) {
                    const current = parseInt(badge.textContent) - 1;
                    if (current <= 0) {
                        badge.remove();
                    } else {
                        badge.textContent = current;
                    }
                }

                // Update notification bell badge
                const bellBadge = document.querySelector('.navbar-badge');
                if (bellBadge) {
                    const current = parseInt(bellBadge.textContent) - 1;
                    if (current <= 0) {
                        bellBadge.remove();
                    } else {
                        bellBadge.textContent = current;
                    }
                }

                // Toast
                const toast = document.createElement('div');
                toast.className = 'alert alert-success position-fixed shadow';
                toast.style.cssText = 'top:70px;right:20px;z-index:9999;max-width:350px;';
                toast.textContent = data.message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        })
        .catch(() => { self.disabled = false; });
    });
});

// ── Nuevo Torneo modal ────────────────────────────────────────────────
(function () {
    var currentTournamentId = null;

    var ntSaveBtn = document.getElementById('nt-save-btn');
    if (!ntSaveBtn) return;

    ntSaveBtn.addEventListener('click', function () {
        var name   = (document.getElementById('nt-name').value || '').trim();
        var season = (document.getElementById('nt-season').value || '').trim();
        var errEl  = document.getElementById('nt-error');

        if (!name) {
            errEl.textContent = 'El nombre es obligatorio.';
            errEl.classList.remove('d-none');
            return;
        }
        errEl.classList.add('d-none');
        ntSaveBtn.disabled = true;

        fetch('/api/tournaments', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ name: name, season: season || null }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.id) {
                currentTournamentId = data.id;
                // Prepare divisions modal content
                document.getElementById('nd-tournament-name').textContent = '— ' + name;
                document.getElementById('nd-added-pills').innerHTML = '';
                document.getElementById('nd-custom-input').value = '';
                document.getElementById('nd-div-error').classList.add('d-none');
                document.querySelectorAll('.nd-chip-btn').forEach(function (c) {
                    c.disabled = false;
                    c.style.opacity = '1';
                });
                // Open divisions modal AFTER first modal fully hides (avoids Bootstrap animation conflict)
                $('#modalNuevoTorneo').one('hidden.bs.modal', function () {
                    $('#modalNuevoTorneoDivisiones').modal('show');
                });
                $('#modalNuevoTorneo').modal('hide');
            } else {
                errEl.textContent = data.message || 'Error al crear el torneo.';
                errEl.classList.remove('d-none');
                ntSaveBtn.disabled = false;
            }
        })
        .catch(function () {
            errEl.textContent = 'Error de red.';
            errEl.classList.remove('d-none');
            ntSaveBtn.disabled = false;
        });
    });

    document.getElementById('nt-name').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') document.getElementById('nt-save-btn').click();
    });

    // Reset modal when hidden
    $('#modalNuevoTorneo').on('hidden.bs.modal', function () {
        document.getElementById('nt-name').value    = '';
        document.getElementById('nt-season').value  = '';
        document.getElementById('nt-error').classList.add('d-none');
        ntSaveBtn.disabled = false;
    });

    // ── Divisiones modal logic ───────────────────────────────────────
    var csrf = document.querySelector('meta[name=csrf-token]').content;

    function addDivisionPill(divId, divName) {
        var pill = document.createElement('span');
        pill.className  = 'div-pill';
        pill.dataset.divId = divId;
        pill.innerHTML  = '<i class="fas fa-layer-group" style="font-size:.75rem;opacity:.7;"></i> ' +
            divName +
            ' <a href="#" class="div-pill-remove" onclick="(function(e,id){e.preventDefault();' +
            'e.currentTarget.closest(\'.div-pill\').remove();' +
            'var chipName=\'' + divName.replace(/'/g, "\\'") + '\';' +
            'document.querySelectorAll(\'.nd-chip-btn\').forEach(function(b){if(b.dataset.name===chipName){b.disabled=false;b.style.opacity=\'1\';}});' +
            '})(event,' + divId + ')">&times;</a>';
        document.getElementById('nd-added-pills').appendChild(pill);
    }

    function submitNewDivision(name, chipBtn) {
        if (!name || !currentTournamentId) return;

        fetch('/api/tournaments/' + currentTournamentId + '/divisions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ name: name }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) {
                addDivisionPill(data.division.id, data.division.name);
                if (chipBtn) {
                    chipBtn.disabled = true;
                    chipBtn.style.opacity = '0.4';
                }
                document.getElementById('nd-custom-input').value = '';
                document.getElementById('nd-div-error').classList.add('d-none');
            } else {
                var errEl = document.getElementById('nd-div-error');
                errEl.textContent = data.error || 'Error al crear la división.';
                errEl.classList.remove('d-none');
            }
        });
    }

    // Chip click
    document.querySelectorAll('.nd-chip-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (this.disabled) return;
            submitNewDivision(this.dataset.name, this);
        });
    });

    // Add custom division
    document.getElementById('nd-add-btn').addEventListener('click', function () {
        var val = (document.getElementById('nd-custom-input').value || '').trim();
        if (!val) return;
        submitNewDivision(val, null);
    });

    document.getElementById('nd-custom-input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('nd-add-btn').click(); }
    });

    // Continue / Skip
    function finishDivisions() {
        $('#modalNuevoTorneoDivisiones').modal('hide');
        window.location.reload();
    }

    document.getElementById('nd-continue-btn').addEventListener('click', finishDivisions);
    document.getElementById('nd-skip-link').addEventListener('click', function (e) {
        e.preventDefault();
        finishDivisions();
    });
})();

// Toggle público/privado para asociaciones
document.querySelectorAll('.btn-toggle-public').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const id = this.dataset.tournamentId;
        const self = this;
        self.disabled = true;

        fetch('/api/tournaments/' + id + '/toggle-public', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                if (data.is_public) {
                    self.className = 'btn btn-xs btn-success btn-toggle-public';
                    self.querySelector('i').className = 'fas fa-globe';
                    self.title = 'Público — click para ocultar';
                } else {
                    self.className = 'btn btn-xs btn-outline-secondary btn-toggle-public';
                    self.querySelector('i').className = 'fas fa-lock';
                    self.title = 'Privado — click para publicar';
                }
                // Toast
                const toast = document.createElement('div');
                toast.className = 'alert alert-success position-fixed shadow';
                toast.style.cssText = 'top:70px;right:20px;z-index:9999;max-width:350px;';
                toast.textContent = data.message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
            self.disabled = false;
        })
        .catch(() => { self.disabled = false; });
    });
});

</script>
@endpush
