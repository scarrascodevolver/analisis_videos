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
            <small class="text-muted">Doble clic en Nombre o Temporada para editar</small>
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

            {{-- Tournaments table --}}
            @if($tournaments->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-trophy fa-3x mb-3" style="color:#005461;opacity:.4"></i>
                    <p>No hay torneos registrados todavía.</p>
                    <p class="small">Los torneos se crean automáticamente al subir videos.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tournamentsTable">
                        <thead>
                            <tr>
                                <th style="width:35%">Nombre</th>
                                <th style="width:20%">Temporada</th>
                                <th style="width:10%" class="text-center">Videos</th>
                                @if(auth()->user()->currentOrganization()?->isAsociacion())
                                    <th style="width:15%" class="text-center">Público</th>
                                @endif
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tournaments as $tournament)
                            {{-- Fila principal --}}
                            <tr id="tr_{{ $tournament->id }}">
                                {{-- Nombre (editable inline) --}}
                                <td>
                                    <span class="editable-cell"
                                          data-field="name"
                                          data-id="{{ $tournament->id }}"
                                          title="Doble clic para editar">
                                        {{ $tournament->name }}
                                    </span>
                                </td>

                                {{-- Temporada (editable inline) --}}
                                <td>
                                    <span class="editable-cell"
                                          data-field="season"
                                          data-id="{{ $tournament->id }}"
                                          title="Doble clic para editar">
                                        {{ $tournament->season ?: '—' }}
                                    </span>
                                </td>

                                {{-- Contador de videos --}}
                                <td class="text-center">
                                    @if($tournament->videos_count > 0)
                                        <span class="badge badge-info">{{ $tournament->videos_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>

                                {{-- Toggle público (solo asociaciones) --}}
                                @if(auth()->user()->currentOrganization()?->isAsociacion())
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-xs {{ $tournament->is_public ? 'btn-success' : 'btn-outline-secondary' }} btn-toggle-public"
                                                data-tournament-id="{{ $tournament->id }}"
                                                title="{{ $tournament->is_public ? 'Público — click para ocultar' : 'Privado — click para publicar' }}">
                                            <i class="fas {{ $tournament->is_public ? 'fa-globe' : 'fa-lock' }}"></i>
                                        </button>
                                    </td>
                                @endif

                                {{-- Acciones --}}
                                <td class="text-right">
                                    @if($tournament->videos_count > 0)
                                        <button type="button"
                                                class="btn btn-xs btn-outline-secondary"
                                                disabled
                                                title="No se puede eliminar: tiene {{ $tournament->videos_count }} video(s) asociado(s)"
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
                                </td>
                            </tr>

                            {{-- Fila de divisiones (solo asociaciones) --}}
                            @if(auth()->user()->currentOrganization()?->isAsociacion())
                            <tr class="division-row" id="divrow_{{ $tournament->id }}">
                                <td colspan="5" class="py-2 px-4"
                                    style="background:rgba(0,183,181,.04);border-top:none;">
                                    <div class="d-flex flex-wrap align-items-center" style="gap:8px;"
                                         id="divpills_{{ $tournament->id }}">

                                        @foreach($tournament->divisions as $div)
                                            <span class="div-pill" data-div-id="{{ $div->id }}">
                                                <i class="fas fa-layer-group" style="font-size:.75rem;opacity:.7;"></i>
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
                                            <i class="fas fa-plus" style="font-size:.75rem;"></i> División
                                        </button>
                                        <span class="add-div-wrap" id="add-div-wrap-{{ $tournament->id }}"
                                              style="display:none;align-items:center;gap:6px;">
                                            <input type="text"
                                                   class="add-div-input"
                                                   id="add-div-input-{{ $tournament->id }}"
                                                   placeholder="Nombre de la división..."
                                                   maxlength="100"
                                                   data-tournament-id="{{ $tournament->id }}">
                                            <button class="btn-div-ok"
                                                    onclick="submitDivision({{ $tournament->id }})">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn-div-cancel"
                                                    onclick="cancelAddDiv({{ $tournament->id }})">
                                                &times;
                                            </button>
                                        </span>

                                        {{-- Ver inscriptos --}}
                                        @php
                                            $regCount = $tournament->divisions->sum(fn($d) => $d->registrations->count());
                                        @endphp
                                        <a href="{{ route('tournaments.show', $tournament) }}"
                                           class="ml-auto"
                                           style="font-size:.8rem;color:rgba(255,255,255,.4);white-space:nowrap;text-decoration:none;padding:4px 10px;border:1px solid rgba(255,255,255,.12);border-radius:12px;transition:all .15s;"
                                           onmouseover="this.style.color='#fff';this.style.borderColor='rgba(255,255,255,.3)'"
                                           onmouseout="this.style.color='rgba(255,255,255,.4)';this.style.borderColor='rgba(255,255,255,.12)'">
                                            <i class="fas fa-users mr-1"></i>
                                            Inscriptos
                                            @if($regCount > 0)
                                                <span style="background:rgba(0,183,181,.3);color:#00B7B5;border-radius:8px;padding:0 5px;font-size:.75rem;">{{ $regCount }}</span>
                                            @endif
                                        </a>

                                    </div>
                                </td>
                            </tr>
                            @endif

                            @endforeach
                        </tbody>
                    </table>
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
