@extends('layouts.app')

@section('page_title', $tournament->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('tournaments.index') }}">Torneos</a></li>
    <li class="breadcrumb-item active">{{ $tournament->name }}</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
<div class="col-lg-10">

    {{-- Header card --}}
    <div class="card card-rugby mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:12px;">
                <div>
                    <h4 class="mb-0 font-weight-bold">{{ $tournament->name }}</h4>
                    @if($tournament->season)
                        <small class="text-muted">Temporada {{ $tournament->season }}</small>
                    @endif
                </div>
                <div class="d-flex align-items-center" style="gap:10px;">
                    {{-- Public toggle --}}
                    <button type="button"
                            class="btn btn-sm {{ $tournament->is_public ? 'btn-success' : 'btn-outline-secondary' }} btn-toggle-public"
                            data-tournament-id="{{ $tournament->id }}"
                            title="{{ $tournament->is_public ? 'Público — click para ocultar' : 'Privado — click para publicar' }}">
                        <i class="fas {{ $tournament->is_public ? 'fa-globe' : 'fa-lock' }} mr-1"></i>
                        {{ $tournament->is_public ? 'Público' : 'Privado' }}
                    </button>
                    <a href="{{ route('tournaments.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Divisions card --}}
    <div class="card card-rugby mb-3">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-layer-group mr-2"></i>Divisiones
            </h3>
        </div>
        <div class="card-body">
            {{-- Active divisions --}}
            <div class="mb-3" id="divisions-container">
                @forelse($tournament->divisions as $div)
                    <span class="badge badge-secondary division-chip mr-1 mb-1"
                          data-div-id="{{ $div->id }}"
                          style="font-size:.85rem;padding:6px 12px;">
                        {{ $div->name }}
                        <a href="#" class="ml-2 text-danger"
                           style="text-decoration:none;"
                           onclick="event.preventDefault();removeDivision({{ $div->id }})">
                            &times;
                        </a>
                    </span>
                @empty
                    <p class="text-muted mb-2" id="no-divisions-msg">
                        <i class="fas fa-info-circle mr-1"></i>
                        No hay divisiones todavía. Agregá una abajo.
                    </p>
                @endforelse
            </div>

            {{-- Suggestions --}}
            @if(!empty($remainingSuggestions))
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">Sugeridas:</small>
                    <div id="suggestions-container">
                        @foreach($remainingSuggestions as $sug)
                            <span class="badge mr-1 mb-1 suggestion-chip"
                                  data-name="{{ $sug }}"
                                  style="font-size:.8rem;padding:5px 10px;cursor:pointer;border:1px solid #444;background:transparent;color:#aaa;border-radius:4px;"
                                  onclick="quickAdd('{{ $sug }}')">
                                + {{ $sug }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Custom input --}}
            <div class="d-flex align-items-center" style="gap:8px;max-width:360px;">
                <input type="text"
                       id="div-name-input"
                       class="form-control form-control-sm"
                       placeholder="División personalizada (ej: División Honor)"
                       maxlength="100"
                       style="background:#1a1a1a;border-color:#444;color:#fff;">
                <button type="button" class="btn btn-sm btn-rugby" onclick="addDivision()" style="white-space:nowrap;">
                    <i class="fas fa-plus mr-1"></i>Agregar
                </button>
            </div>
            <div id="div-error" class="text-danger small mt-1" style="display:none;"></div>
        </div>
    </div>

    {{-- Enrolled clubs per division --}}
    @if($tournament->divisions->isNotEmpty())
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-2"></i>Clubes Inscriptos
                </h3>
            </div>
            <div class="card-body p-0">
                @php
                    $hasAny = false;
                    foreach($tournament->divisions as $div) {
                        if($div->registrations->isNotEmpty()) { $hasAny = true; break; }
                    }
                @endphp

                @if(!$hasAny)
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-user-plus fa-2x mb-2" style="opacity:.3;"></i>
                        <p class="mb-0">Ningún club inscripto todavía.</p>
                        <small>Los clubes verán este torneo en "Torneos Disponibles" si está marcado como <strong>Público</strong>.</small>
                    </div>
                @else
                    @foreach($tournament->divisions as $div)
                        @if($div->registrations->isNotEmpty())
                            <div class="px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,.07);">
                                <h6 class="mb-2 mt-1" style="color:#00B7B5;font-size:.85rem;font-weight:600;letter-spacing:.05em;">
                                    <i class="fas fa-layer-group mr-1"></i>{{ $div->name }}
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            @foreach($div->registrations as $reg)
                                                <tr id="reg-row-{{ $reg->id }}">
                                                    <td style="width:36px;">
                                                        @if($reg->clubOrganization->logo_path)
                                                            <img src="{{ asset('storage/' . $reg->clubOrganization->logo_path) }}"
                                                                 style="width:28px;height:28px;object-fit:contain;border-radius:4px;">
                                                        @else
                                                            <i class="fas fa-shield-alt text-muted"></i>
                                                        @endif
                                                    </td>
                                                    <td>{{ $reg->clubOrganization->name }}</td>
                                                    <td>
                                                        @if($reg->status === 'pending')
                                                            <span class="badge badge-warning">Pendiente</span>
                                                        @else
                                                            <span class="badge badge-success">Activo</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small">
                                                        {{ $reg->registered_at->diffForHumans() }}
                                                    </td>
                                                    <td class="text-right">
                                                        @if($reg->status === 'pending')
                                                            <button class="btn btn-xs btn-success btn-approve-reg"
                                                                    data-reg-id="{{ $reg->id }}">
                                                                <i class="fas fa-check"></i> Aprobar
                                                            </button>
                                                            <button class="btn btn-xs btn-outline-danger btn-reject-reg"
                                                                    data-reg-id="{{ $reg->id }}">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @else
                                                            <button class="btn btn-xs btn-outline-secondary btn-revoke-reg"
                                                                    data-reg-id="{{ $reg->id }}"
                                                                    title="Dar de baja">
                                                                <i class="fas fa-user-minus"></i>
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    @endif

</div>
</div>
@endsection

@push('scripts')
<script>
const TOURNAMENT_ID = {{ $tournament->id }};
const CSRF = document.querySelector('meta[name=csrf-token]').content;

// ── Divisions ─────────────────────────────────────────────
function quickAdd(name) {
    document.getElementById('div-name-input').value = name;
    addDivision();
}

function addDivision() {
    const input = document.getElementById('div-name-input');
    const name = input.value.trim();
    if (!name) return;

    const errEl = document.getElementById('div-error');
    errEl.style.display = 'none';

    fetch('/api/tournaments/' + TOURNAMENT_ID + '/divisions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            // Remove "no divisions" message if present
            const msg = document.getElementById('no-divisions-msg');
            if (msg) msg.remove();

            // Add chip
            const chip = document.createElement('span');
            chip.className = 'badge badge-secondary division-chip mr-1 mb-1';
            chip.dataset.divId = data.division.id;
            chip.style.cssText = 'font-size:.85rem;padding:6px 12px;';
            chip.innerHTML = data.division.name +
                ' <a href="#" class="ml-2 text-danger" style="text-decoration:none;" onclick="event.preventDefault();removeDivision(' + data.division.id + ')">&times;</a>';
            document.getElementById('divisions-container').appendChild(chip);

            // Remove from suggestions
            document.querySelectorAll('.suggestion-chip').forEach(s => {
                if (s.dataset.name.toLowerCase() === name.toLowerCase()) s.remove();
            });

            input.value = '';
        } else {
            errEl.textContent = data.error || 'Error al crear la división.';
            errEl.style.display = 'block';
        }
    });
}

function removeDivision(divId) {
    if (!confirm('¿Eliminar esta división? Solo se puede si no tiene clubes inscriptos.')) return;

    fetch('/api/divisions/' + divId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            document.querySelectorAll('.division-chip[data-div-id="' + divId + '"]').forEach(c => c.remove());
        } else {
            alert(data.error || 'No se pudo eliminar.');
        }
    });
}

// Enter key on input
document.getElementById('div-name-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); addDivision(); }
});

// ── Registrations: Approve / Reject ────────────────────────
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-approve-reg, .btn-reject-reg, .btn-revoke-reg');
    if (!btn) return;

    const regId = btn.dataset.regId;
    let action = 'approve';
    if (btn.classList.contains('btn-reject-reg')) action = 'reject';
    if (btn.classList.contains('btn-revoke-reg')) action = 'reject'; // reuse reject endpoint

    if (action === 'reject' && !confirm('¿Confirmar esta acción?')) return;
    btn.disabled = true;

    fetch('/tournament-registrations/' + regId + '/' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            const row = document.getElementById('reg-row-' + regId);
            if (action === 'approve') {
                // Update status badge and change buttons
                row.querySelector('.badge').className = 'badge badge-success';
                row.querySelector('.badge').textContent = 'Activo';
                row.querySelector('td:last-child').innerHTML =
                    '<button class="btn btn-xs btn-outline-secondary btn-revoke-reg" data-reg-id="' + regId + '" title="Dar de baja"><i class="fas fa-user-minus"></i></button>';
            } else {
                row.remove();
            }
            // Update navbar bell badge
            const bellBadge = document.querySelector('.navbar-badge');
            if (bellBadge) {
                const cur = parseInt(bellBadge.textContent) - 1;
                if (cur <= 0) bellBadge.remove(); else bellBadge.textContent = cur;
            }
        } else {
            btn.disabled = false;
            alert(data.error || 'Error.');
        }
    });
});

// ── Public toggle ────────────────────────────────────────
document.querySelector('.btn-toggle-public')?.addEventListener('click', function() {
    const self = this;
    self.disabled = true;
    fetch('/api/tournaments/' + TOURNAMENT_ID + '/toggle-public', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            if (data.is_public) {
                self.className = 'btn btn-sm btn-success btn-toggle-public';
                self.innerHTML = '<i class="fas fa-globe mr-1"></i>Público';
            } else {
                self.className = 'btn btn-sm btn-outline-secondary btn-toggle-public';
                self.innerHTML = '<i class="fas fa-lock mr-1"></i>Privado';
            }
        }
        self.disabled = false;
    })
    .catch(() => { self.disabled = false; });
});
</script>
@endpush
