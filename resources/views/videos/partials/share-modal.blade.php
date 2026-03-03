{{--
    Modal: Enviar video a un club registrado a una división del torneo.
    Flujo: seleccionar división → seleccionar club → notas → enviar
    Se incluye una vez al final de la sección asoc_matches.
--}}
<div class="modal fade" id="shareVideoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background:#1a1a1a; border:1px solid rgba(255,255,255,0.12);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.1);">
                <h5 class="modal-title">
                    <i class="fas fa-share-alt mr-2" style="color:#00B7B5;"></i>
                    Enviar video a club
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                {{-- Nombre del video a compartir --}}
                <p class="mb-3 text-muted small">
                    Video: <strong id="shareVideoTitle" class="text-white"></strong>
                </p>

                {{-- Selector de división --}}
                <div class="form-group">
                    <label class="text-muted small font-weight-bold">
                        <i class="fas fa-layer-group mr-1"></i> División *
                    </label>
                    <select id="shareDivisionSelect" class="form-control bg-dark border-secondary text-white">
                        <option value="">Cargando divisiones...</option>
                    </select>
                </div>

                {{-- Selector de club (se habilita tras elegir división) --}}
                <div class="form-group">
                    <label class="text-muted small font-weight-bold">
                        <i class="fas fa-building mr-1"></i> Club *
                    </label>
                    <select id="shareClubSelect" class="form-control bg-dark border-secondary text-white" disabled>
                        <option value="">Primero elegí una división</option>
                    </select>
                </div>

                {{-- Notas opcionales --}}
                <div class="form-group">
                    <label class="text-muted small font-weight-bold">Notas (opcional)</label>
                    <textarea id="shareNotes" class="form-control bg-dark border-secondary text-white"
                              rows="2" placeholder="Ej: Para el preparador físico" maxlength="500"></textarea>
                </div>

                {{-- Mensaje de feedback --}}
                <div id="shareFeedback" class="d-none mt-2"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.1);">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm" id="shareSubmitBtn"
                        style="background:#00B7B5; color:#fff; border:none;">
                    <i class="fas fa-paper-plane mr-1"></i> Enviar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    let currentVideoId      = null;
    let currentTournamentId = null;

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-share-video');
        if (!btn) return;

        e.stopPropagation();

        currentVideoId      = btn.dataset.videoId;
        currentTournamentId = btn.dataset.tournamentId;
        const videoTitle    = btn.dataset.videoTitle;

        document.getElementById('shareVideoTitle').textContent = videoTitle;
        document.getElementById('shareNotes').value = '';
        hideFeedback();

        const divSelect  = document.getElementById('shareDivisionSelect');
        const clubSelect = document.getElementById('shareClubSelect');

        divSelect.innerHTML  = '<option value="">Cargando divisiones...</option>';
        clubSelect.innerHTML = '<option value="">Primero elegí una división</option>';
        clubSelect.disabled  = true;

        $('#shareVideoModal').modal('show');

        // Load divisions for this tournament
        fetch('/api/tournaments/' + currentTournamentId + '/divisions', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            divSelect.innerHTML = '<option value="">— Seleccioná la división —</option>';
            if (data.divisions && data.divisions.length > 0) {
                data.divisions.forEach(div => {
                    const opt = document.createElement('option');
                    opt.value = div.id;
                    opt.textContent = div.name;
                    divSelect.appendChild(opt);
                });
            } else {
                divSelect.innerHTML = '<option value="">No hay divisiones en este torneo</option>';
            }
        })
        .catch(() => {
            divSelect.innerHTML = '<option value="">Error al cargar divisiones</option>';
        });
    });

    // When a division is selected, load its registered clubs
    document.getElementById('shareDivisionSelect').addEventListener('change', function () {
        const divisionId = this.value;
        const clubSelect = document.getElementById('shareClubSelect');

        if (!divisionId) {
            clubSelect.innerHTML = '<option value="">Primero elegí una división</option>';
            clubSelect.disabled = true;
            return;
        }

        clubSelect.innerHTML = '<option value="">Cargando clubes...</option>';
        clubSelect.disabled  = true;

        fetch('/api/divisions/' + divisionId + '/registered-clubs', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            clubSelect.innerHTML = '<option value="">— Seleccioná un club —</option>';
            if (data.clubs && data.clubs.length > 0) {
                data.clubs.forEach(club => {
                    const opt = document.createElement('option');
                    opt.value = club.id;
                    opt.textContent = club.name;
                    clubSelect.appendChild(opt);
                });
                clubSelect.disabled = false;
            } else {
                clubSelect.innerHTML = '<option value="">No hay clubes inscriptos en esta división</option>';
            }
        })
        .catch(() => {
            clubSelect.innerHTML = '<option value="">Error al cargar clubes</option>';
        });
    });

    // Enviar
    document.getElementById('shareSubmitBtn').addEventListener('click', function () {
        const divisionId = document.getElementById('shareDivisionSelect').value;
        const clubId     = document.getElementById('shareClubSelect').value;
        const notes      = document.getElementById('shareNotes').value;

        if (!divisionId) {
            showFeedback('warning', 'Seleccioná una división.');
            return;
        }

        if (!clubId) {
            showFeedback('warning', 'Seleccioná un club.');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...';

        fetch('/videos/' + currentVideoId + '/share', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                target_organization_id: clubId,
                division_id:            divisionId,
                notes:                  notes || null,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showFeedback('success', data.message);
                this.style.display = 'none';
                setTimeout(() => $('#shareVideoModal').modal('hide'), 1800);
            } else {
                showFeedback('danger', data.error || 'Error al enviar el video.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Enviar';
            }
        })
        .catch(() => {
            showFeedback('danger', 'Error de red. Intentá de nuevo.');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Enviar';
        });
    });

    // Resetear modal al cerrar
    document.getElementById('shareVideoModal').addEventListener('hidden.bs.modal', function () {
        const btn = document.getElementById('shareSubmitBtn');
        btn.disabled = false;
        btn.style.display = '';
        btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Enviar';
        hideFeedback();
    });

    function showFeedback(type, msg) {
        const el = document.getElementById('shareFeedback');
        el.className = 'alert alert-' + type + ' small py-2';
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function hideFeedback() {
        const el = document.getElementById('shareFeedback');
        el.className = 'd-none';
        el.textContent = '';
    }
})();
</script>
@endpush
