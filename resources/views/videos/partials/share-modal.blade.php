{{--
    Modal: Enviar video a uno o más clubes registrados al torneo.
    Flujo: opcional filtrar por división → checkboxes de clubes → notas → enviar
    Se incluye una vez al final de la sección asoc_matches.
--}}
<div class="modal fade" id="shareVideoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="background:#1a1a1a; border:1px solid rgba(255,255,255,0.12);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.1);">
                <h5 class="modal-title">
                    <i class="fas fa-share-alt mr-2" style="color:#00B7B5;"></i>
                    Enviar video a club(es)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                {{-- Nombre del video --}}
                <p class="mb-3 text-muted small">
                    Video: <strong id="shareVideoTitle" class="text-white"></strong>
                </p>

                {{-- Filtro por división (opcional) --}}
                <div class="form-group mb-3">
                    <label class="text-muted small font-weight-bold">
                        <i class="fas fa-filter mr-1"></i> Filtrar por división (opcional)
                    </label>
                    <select id="shareDivisionFilter" class="form-control form-control-sm bg-dark border-secondary text-white">
                        <option value="">— Todas las divisiones —</option>
                    </select>
                </div>

                {{-- Encabezado de la lista --}}
                <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="shareSelectAll">
                        <label class="custom-control-label text-white font-weight-bold small" for="shareSelectAll">
                            Seleccionar todos
                        </label>
                    </div>
                    <span id="shareSelectedCount" class="text-muted small">0 seleccionados</span>
                </div>

                {{-- Lista de clubes con checkboxes --}}
                <div id="shareClubList"
                     style="max-height:240px; overflow-y:auto; border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:6px 10px; background:#111;">
                    <div class="text-muted small text-center py-3">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Cargando clubes...
                    </div>
                </div>

                {{-- Notas opcionales --}}
                <div class="form-group mt-3 mb-0">
                    <label class="text-muted small font-weight-bold">Notas (opcional)</label>
                    <textarea id="shareNotes" class="form-control bg-dark border-secondary text-white"
                              rows="2" placeholder="Ej: Para el preparador físico" maxlength="500"></textarea>
                </div>

                {{-- Feedback --}}
                <div id="shareFeedback" class="d-none mt-3"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.1);">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm" id="shareSubmitBtn"
                        style="background:#00B7B5; color:#fff; border:none;" disabled>
                    <i class="fas fa-paper-plane mr-1"></i>
                    <span id="shareSubmitLabel">Seleccioná al menos un club</span>
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
    let allClubs            = [];   // todos los clubes del torneo (con division_id)
    let filteredClubs       = [];   // clubes visibles según el filtro

    // ──────────────────────────────────────────────
    // Abrir modal
    // ──────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-share-video');
        if (!btn) return;
        e.stopPropagation();

        currentVideoId      = btn.dataset.videoId;
        currentTournamentId = btn.dataset.tournamentId;

        document.getElementById('shareVideoTitle').textContent = btn.dataset.videoTitle || '';
        document.getElementById('shareNotes').value = '';
        document.getElementById('shareDivisionFilter').innerHTML = '<option value="">— Todas las divisiones —</option>';
        document.getElementById('shareSelectAll').checked        = false;
        allClubs     = [];
        filteredClubs = [];
        renderClubs([]);
        hideFeedback();
        resetSubmitBtn();

        $('#shareVideoModal').modal('show');

        // Cargar clubes + divisiones en paralelo
        Promise.all([
            fetch('/api/tournaments/' + currentTournamentId + '/registered-clubs', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(r => r.json()),

            fetch('/api/tournaments/' + currentTournamentId + '/divisions', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(r => r.json()),
        ])
        .then(([clubsData, divsData]) => {
            allClubs      = clubsData.clubs || [];
            filteredClubs = allClubs;

            // Poblar filtro de divisiones con las que tienen clubes
            const divFilter = document.getElementById('shareDivisionFilter');
            const divIds    = new Set(allClubs.map(c => c.division_id).filter(Boolean));

            if (divsData.divisions) {
                divsData.divisions
                    .filter(d => divIds.has(d.id))
                    .forEach(d => {
                        const opt       = document.createElement('option');
                        opt.value       = d.id;
                        opt.textContent = d.name;
                        divFilter.appendChild(opt);
                    });
            }

            renderClubs(filteredClubs);
        })
        .catch(() => {
            document.getElementById('shareClubList').innerHTML =
                '<p class="text-danger small text-center py-3">Error al cargar los clubes.</p>';
        });
    });

    // ──────────────────────────────────────────────
    // Filtro de división
    // ──────────────────────────────────────────────
    document.getElementById('shareDivisionFilter').addEventListener('change', function () {
        const divId = this.value ? parseInt(this.value) : null;
        filteredClubs = divId
            ? allClubs.filter(c => c.division_id === divId)
            : allClubs;

        document.getElementById('shareSelectAll').checked = false;
        renderClubs(filteredClubs);
    });

    // ──────────────────────────────────────────────
    // Seleccionar todos (solo los visibles/filtrados)
    // ──────────────────────────────────────────────
    document.getElementById('shareSelectAll').addEventListener('change', function () {
        const checked   = this.checked;
        const checkboxes = document.querySelectorAll('#shareClubList .club-checkbox');
        checkboxes.forEach(cb => { cb.checked = checked; });
        updateSubmitBtn();
    });

    // ──────────────────────────────────────────────
    // Delegación de eventos en checkboxes de clubes
    // ──────────────────────────────────────────────
    document.getElementById('shareClubList').addEventListener('change', function (e) {
        if (e.target.classList.contains('club-checkbox')) {
            updateSelectAllState();
            updateSubmitBtn();
        }
    });

    // ──────────────────────────────────────────────
    // Enviar
    // ──────────────────────────────────────────────
    document.getElementById('shareSubmitBtn').addEventListener('click', function () {
        const selectedIds = getSelectedClubIds();
        if (selectedIds.length === 0) return;

        const notes = document.getElementById('shareNotes').value.trim();

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...';

        fetch('/videos/' + currentVideoId + '/share-multiple', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                target_organization_ids: selectedIds,
                notes: notes || null,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showFeedback('success', data.message);
                this.style.display = 'none';
                setTimeout(() => $('#shareVideoModal').modal('hide'), 2000);
            } else {
                showFeedback('danger', data.error || 'Error al enviar el video.');
                resetSubmitBtn();
            }
        })
        .catch(() => {
            showFeedback('danger', 'Error de red. Intentá de nuevo.');
            resetSubmitBtn();
        });
    });

    // ──────────────────────────────────────────────
    // Resetear modal al cerrar
    // ──────────────────────────────────────────────
    document.getElementById('shareVideoModal').addEventListener('hidden.bs.modal', function () {
        resetSubmitBtn();
        hideFeedback();
    });

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────
    function renderClubs(clubs) {
        const list = document.getElementById('shareClubList');

        if (!clubs || clubs.length === 0) {
            list.innerHTML = '<p class="text-muted small text-center py-3">No hay clubes inscriptos' +
                (document.getElementById('shareDivisionFilter').value ? ' en esta división' : ' en el torneo') + '.</p>';
            updateSubmitBtn();
            return;
        }

        list.innerHTML = clubs.map(club => `
            <div class="d-flex align-items-center py-1" style="border-bottom:1px solid rgba(255,255,255,0.05);">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox"
                           class="custom-control-input club-checkbox"
                           id="club_${club.id}"
                           value="${club.id}">
                    <label class="custom-control-label text-white small" for="club_${club.id}">
                        ${escapeHtml(club.name)}
                        ${club.division_name
                            ? `<span class="badge ml-1" style="background:rgba(0,183,181,0.18); color:#00B7B5; font-size:10px;">${escapeHtml(club.division_name)}</span>`
                            : ''}
                    </label>
                </div>
            </div>
        `).join('');

        updateSelectAllState();
        updateSubmitBtn();
    }

    function getSelectedClubIds() {
        return Array.from(document.querySelectorAll('#shareClubList .club-checkbox:checked'))
            .map(cb => parseInt(cb.value));
    }

    function updateSelectAllState() {
        const checkboxes = document.querySelectorAll('#shareClubList .club-checkbox');
        const checked    = document.querySelectorAll('#shareClubList .club-checkbox:checked');
        const selectAll  = document.getElementById('shareSelectAll');
        if (checkboxes.length === 0) {
            selectAll.checked       = false;
            selectAll.indeterminate = false;
        } else if (checked.length === checkboxes.length) {
            selectAll.checked       = true;
            selectAll.indeterminate = false;
        } else if (checked.length > 0) {
            selectAll.checked       = false;
            selectAll.indeterminate = true;
        } else {
            selectAll.checked       = false;
            selectAll.indeterminate = false;
        }
    }

    function updateSubmitBtn() {
        const count  = getSelectedClubIds().length;
        const btn    = document.getElementById('shareSubmitBtn');
        const label  = document.getElementById('shareSubmitLabel');
        const counter = document.getElementById('shareSelectedCount');

        counter.textContent = count + ' seleccionado' + (count !== 1 ? 's' : '');

        if (count === 0) {
            btn.disabled    = true;
            label.textContent = 'Seleccioná al menos un club';
        } else {
            btn.disabled    = false;
            label.textContent = 'Enviar a ' + count + ' club' + (count !== 1 ? 'es' : '');
        }
    }

    function resetSubmitBtn() {
        const btn  = document.getElementById('shareSubmitBtn');
        btn.disabled      = true;
        btn.style.display = '';
        btn.innerHTML     = '<i class="fas fa-paper-plane mr-1"></i> <span id="shareSubmitLabel">Seleccioná al menos un club</span>';
        document.getElementById('shareSelectedCount').textContent = '0 seleccionados';
    }

    function showFeedback(type, msg) {
        const el    = document.getElementById('shareFeedback');
        el.className = 'alert alert-' + type + ' small py-2';
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function hideFeedback() {
        const el    = document.getElementById('shareFeedback');
        el.className = 'd-none';
        el.textContent = '';
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
})();
</script>
@endpush
