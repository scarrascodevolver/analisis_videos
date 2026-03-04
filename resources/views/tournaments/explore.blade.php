@extends('layouts.app')

@section('page_title', 'Torneos Disponibles')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Torneos Disponibles</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
<div class="col-lg-10">

    <div class="card card-rugby">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-globe mr-2"></i>Torneos Disponibles</h3>
            <small class="text-muted">Inscribite para recibir análisis de video</small>
        </div>
        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($tournaments->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-trophy fa-3x mb-3" style="color:#005461;opacity:.4"></i>
                    <p>No hay torneos públicos disponibles por el momento.</p>
                    <p class="small">Las asociaciones publicarán torneos cuando estén listos.</p>
                </div>
            @else
                <div class="row" id="tournamentsGrid">
                    @foreach($tournaments as $tournament)
                    <div class="col-md-6 col-lg-4 mb-4" id="tournament-card-{{ $tournament->id }}">
                        <div class="card h-100" style="border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.03);">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">{{ $tournament->name }}</h5>
                                        @if($tournament->season)
                                            <small class="text-muted">{{ $tournament->season }}</small>
                                        @endif
                                    </div>
                                    @if($tournament->registration_status === 'active')
                                        <span class="badge badge-success ml-2">Inscripto</span>
                                    @elseif($tournament->registration_status === 'pending')
                                        <span class="badge badge-warning ml-2">Pendiente</span>
                                    @endif
                                </div>

                                @if($tournament->organization)
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-building mr-1"></i>
                                        {{ $tournament->organization->name }}
                                    </p>
                                @endif

                                @if($tournament->divisions->isNotEmpty())
                                <div class="mb-2" style="display:flex;flex-wrap:wrap;gap:5px;">
                                    @foreach($tournament->divisions as $division)
                                    <span style="background:rgba(0,183,181,.12);border:1px solid rgba(0,183,181,.35);color:#00B7B5;border-radius:20px;padding:2px 10px;font-size:.75rem;">
                                        {{ $division->name }}
                                    </span>
                                    @endforeach
                                </div>
                                @endif

                                <p class="text-muted small mb-3">
                                    <i class="fas fa-video mr-1"></i>
                                    {{ $tournament->videos_count }} video(s)
                                </p>

                                <div class="mt-auto">
                                    @if($tournament->registration_status === 'active')
                                        <div class="mb-2">
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle mr-1"></i>Inscripto
                                            </span>
                                        </div>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm btn-block withdraw-btn"
                                                data-tournament-id="{{ $tournament->id }}"
                                                data-tournament-name="{{ $tournament->name }}">
                                            <i class="fas fa-sign-out-alt mr-1"></i> Darse de baja
                                        </button>
                                    @elseif($tournament->registration_status === 'pending')
                                        <span class="badge badge-warning d-block mb-2">
                                            <i class="fas fa-clock mr-1"></i>Pendiente de aprobación
                                        </span>
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm btn-block withdraw-btn"
                                                data-tournament-id="{{ $tournament->id }}"
                                                data-tournament-name="{{ $tournament->name }}">
                                            Cancelar solicitud
                                        </button>
                                    @else
                                        <button type="button"
                                                class="btn btn-rugby btn-sm btn-block register-btn"
                                                data-tournament-id="{{ $tournament->id }}"
                                                data-tournament-name="{{ $tournament->name }}">
                                            <i class="fas fa-sign-in-alt mr-1"></i> Inscribirse
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

</div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Inscribirse
    document.querySelectorAll('.register-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tournamentId   = this.dataset.tournamentId;
            const tournamentName = this.dataset.tournamentName;
            const card           = this;

            card.disabled = true;
            card.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Inscribiendo...';

            const payload = { tournament_id: tournamentId };

            fetch('{{ route("tournament-registrations.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    // Update badge to "Pendiente"
                    const cardEl = document.getElementById('tournament-card-' + tournamentId);
                    cardEl.querySelector('.badge')?.remove();
                    const title = cardEl.querySelector('.card-title');
                    if (title) {
                        const badge = document.createElement('span');
                        badge.className = 'badge badge-warning ml-2';
                        badge.textContent = 'Pendiente';
                        title.parentNode.appendChild(badge);
                    }

                    card.className = 'btn btn-outline-secondary btn-sm btn-block withdraw-btn';
                    card.dataset.tournamentId = tournamentId;
                    card.innerHTML = '<i class="fas fa-clock mr-1"></i> Pendiente — Cancelar';
                    card.disabled = false;
                    card.removeEventListener('click', arguments.callee);
                    card.addEventListener('click', handleWithdraw);

                    showToast('success', data.message);
                } else {
                    card.disabled = false;
                    card.innerHTML = '<i class="fas fa-sign-in-alt mr-1"></i> Inscribirse';
                    showToast('error', data.error || data.message || 'Error al inscribirse.');
                }
            })
            .catch(() => {
                card.disabled = false;
                card.innerHTML = '<i class="fas fa-sign-in-alt mr-1"></i> Inscribirse';
                showToast('error', 'Error de red. Intentá de nuevo.');
            });
        });
    });

    // Darse de baja
    document.querySelectorAll('.withdraw-btn').forEach(btn => {
        btn.addEventListener('click', handleWithdraw);
    });

    function handleWithdraw() {
        const tournamentId = this.dataset.tournamentId;
        if (!confirm('¿Confirmás que querés darte de baja de este torneo?')) return;

        const card = this;
        card.disabled = true;
        card.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...';

        fetch('{{ url("tournament-registrations/by-tournament") }}/' + tournamentId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                const cardEl = document.getElementById('tournament-card-' + tournamentId);
                cardEl.querySelectorAll('.badge-success, .badge-warning').forEach(b => b.remove());

                card.className = 'btn btn-rugby btn-sm btn-block register-btn';
                card.innerHTML = '<i class="fas fa-sign-in-alt mr-1"></i> Inscribirse';
                card.disabled = false;

                showToast('success', data.message);
            } else {
                card.disabled = false;
                card.innerHTML = '<i class="fas fa-sign-out-alt mr-1"></i> Darse de baja';
                showToast('error', data.error || 'Error al darse de baja.');
            }
        });
    }

    function showToast(type, message) {
        const div = document.createElement('div');
        div.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') +
                        ' position-fixed shadow' ;
        div.style.cssText = 'top:80px;right:20px;z-index:9999;max-width:360px;';
        div.textContent = message;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 4000);
    }
});
</script>
@endsection
