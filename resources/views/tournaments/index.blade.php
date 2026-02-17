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
                                <th style="width:40%">Nombre</th>
                                <th style="width:20%">Temporada</th>
                                <th style="width:15%" class="text-center">Videos</th>
                                <th style="width:25%" class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tournaments as $tournament)
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>

</div>
</div>
@endsection

@push('styles')
<style>
.editable-cell {
    cursor: pointer;
    border-bottom: 1px dashed #444;
    padding: 2px 4px;
    border-radius: 3px;
    transition: background .15s;
}
.editable-cell:hover {
    background: rgba(0,183,181,.1);
    border-bottom-color: #00B7B5;
}
.editable-cell.editing {
    display: none;
}
.edit-input {
    background: #1a1a1a;
    border: 1px solid #00B7B5;
    color: #fff;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: .9rem;
    width: 100%;
    box-sizing: border-box;
}
.edit-input:focus { outline: none; box-shadow: 0 0 0 2px rgba(0,183,181,.3); }
.save-indicator {
    font-size: .72rem;
    color: #00B7B5;
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
</script>
@endpush
