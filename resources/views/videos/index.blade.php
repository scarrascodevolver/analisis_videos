@extends('layouts.app')

@section('page_title', 'Videos')

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a>
    </li>
    @if(isset($team))
        <li class="breadcrumb-item">
            <a href="{{ route('videos.index') }}">Videos</a>
        </li>
        @if(isset($tournament) || isset($tournamentParam))
            <li class="breadcrumb-item">
                <a href="{{ route('videos.index', ['team' => $team]) }}">{{ $team }}</a>
            </li>
            <li class="breadcrumb-item active">
                {{ $tournament?->name ?? 'Sin torneo' }}
            </li>
        @else
            <li class="breadcrumb-item active">{{ $team }}</li>
        @endif
    @elseif(isset($tournament))
        <li class="breadcrumb-item">
            <a href="{{ route('videos.index') }}">Videos</a>
        </li>
        <li class="breadcrumb-item active">{{ $tournament->name }}</li>
    @else
        <li class="breadcrumb-item active">Videos</li>
    @endif
@endsection

@section('main_content')

{{-- ═══════════════════════════════════════════════════════════
     CLUB — Nivel 1: Carpetas de categorías
═══════════════════════════════════════════════════════════ --}}
@if($view === 'club_categories')
@include('videos.partials.folder-header', [
    'title' => 'Categorías',
    'icon' => 'layer-group',
    'extraBtn' => ['label' => 'Nueva Categoría', 'icon' => 'folder-plus', 'modal' => '#createCategoryModal']
])
@if($categories->isEmpty())
    @include('videos.partials.empty-folder', ['msg' => 'No hay categorías creadas aún.'])
@else
    <div class="folder-grid">
        @foreach($categories as $cat)
            <div class="folder-card-wrap">
                <a href="{{ route('videos.index', ['category' => $cat->id]) }}"
                   class="folder-card text-decoration-none"
                   data-rename-url="{{ route('api.categories.rename', $cat) }}"
                   data-rename-id="{{ $cat->id }}"
                   data-rename-name="{{ $cat->name }}"
                   data-delete-url="{{ route('api.categories.delete', $cat) }}"
                   data-videos-count="{{ $cat->videos_count }}">
                    <div class="folder-icon-wrap"><i class="fas fa-folder"></i></div>
                    <div class="folder-name">{{ $cat->name }}</div>
                    <div class="folder-meta">{{ $cat->videos_count }} partidos</div>
                </a>
            </div>
        @endforeach
    </div>
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary py-2">
                <h6 class="modal-title"><i class="fas fa-folder-plus mr-2 text-success"></i>Nueva Categoría</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body py-2">
                <div class="form-group mb-0">
                    <input type="text" id="newCategoryName" class="form-control form-control-sm bg-dark border-secondary text-white"
                        placeholder="Ej: Masculino, Juveniles..." maxlength="255">
                    <div id="categoryError" class="text-danger small mt-1 d-none"></div>
                </div>
            </div>
            <div class="modal-footer border-secondary py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-rugby btn-sm" id="saveCategoryBtn">
                    <i class="fas fa-save mr-1"></i> Crear
                </button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('saveCategoryBtn').addEventListener('click', function() {
    const name = document.getElementById('newCategoryName').value.trim();
    const errEl = document.getElementById('categoryError');
    if (!name) { errEl.textContent = 'Ingresa un nombre.'; errEl.classList.remove('d-none'); return; }
    errEl.classList.add('d-none');
    this.disabled = true;
    fetch('{{ route("api.categories.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ name })
    })
    .then(r => r.json())
    .then(data => {
        if (data.id) { window.location.reload(); }
        else { errEl.textContent = data.message || 'Error al crear.'; errEl.classList.remove('d-none'); this.disabled = false; }
    })
    .catch(() => { errEl.textContent = 'Error de red.'; errEl.classList.remove('d-none'); this.disabled = false; });
});
document.getElementById('newCategoryName').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('saveCategoryBtn').click();
});
</script>
@endpush
@endif

{{-- ═══════════════════════════════════════════════════════════
     ASOCIACIÓN — Nivel 1: Carpetas de torneos
═══════════════════════════════════════════════════════════ --}}
@elseif($view === 'asoc_tournaments')
@include('videos.partials.folder-header', [
    'title' => 'Torneos',
    'icon' => 'trophy',
    'extraBtn' => ['label' => 'Nuevo Torneo', 'icon' => 'plus', 'modal' => '#createTournamentModal']
])
@if($tournaments->isEmpty())
    @include('videos.partials.empty-folder', ['msg' => 'No hay torneos creados aún.'])
@else
    <div class="folder-grid">
        @foreach($tournaments as $t)
            <div class="folder-card-wrap">
                <a href="{{ route('videos.index', ['tournament' => $t->id]) }}"
                   class="folder-card text-decoration-none"
                   data-rename-url="{{ route('api.tournaments.rename', $t) }}"
                   data-rename-id="{{ $t->id }}"
                   data-rename-name="{{ $t->name }}"
                   data-delete-url="{{ route('api.tournaments.delete', $t) }}"
                   data-videos-count="{{ $t->videos_count }}">
                    <div class="folder-icon-wrap"><i class="fas fa-trophy"></i></div>
                    <div class="folder-name">{{ $t->name }}</div>
                    <div class="folder-meta">{{ $t->videos_count }} videos</div>
                </a>
            </div>
        @endforeach
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════
     ASOCIACIÓN — Nivel 2: Partidos dentro de un torneo
═══════════════════════════════════════════════════════════ --}}
@elseif($view === 'asoc_matches')
@include('videos.partials.folder-header', ['title' => $tournament->name, 'icon' => 'trophy', 'back' => route('videos.index')])
@if($matches->isEmpty())
    @include('videos.partials.empty-folder', [
        'msg' => 'Todavía no hay partidos en este torneo.',
        'action' => route('videos.create'),
        'actionLabel' => 'Subir el primer partido'
    ])
@else
    <div class="match-grid">
        @foreach($matches as $video)
            @php
                $totalSize = $video->total_size ?? 0;
                $sizeLabel = '';
                if ($totalSize > 0) {
                    $gb = $totalSize / 1073741824;
                    $sizeLabel = $gb >= 1
                        ? number_format($gb, 1) . ' GB'
                        : number_format($totalSize / 1048576, 0) . ' MB';
                }
                $anglesCount = $video->angles_count ?? 1;

                // Nombres de ángulos para tooltip del badge
                $angleNames = [];
                $firstGroup = $video->videoGroups->first();
                if ($firstGroup) {
                    foreach ($firstGroup->videos as $gv) {
                        $angle = $gv->pivot->camera_angle ?? null;
                        if ($angle) $angleNames[] = $angle;
                    }
                }
                $anglesTooltip = !empty($angleNames)
                    ? implode(' · ', $angleNames)
                    : $anglesCount . ' ángulos';
            @endphp
            <div class="match-card" onclick="window.location.href='{{ route('videos.show', $video) }}'">
                {{-- Botón editar flotante --}}
                @if(in_array(auth()->user()->role, ['analista', 'entrenador']) || auth()->id() === $video->uploaded_by)
                    <a href="{{ route('videos.edit', $video) }}"
                       class="match-edit-btn" title="Editar"
                       onclick="event.stopPropagation()">
                        <i class="fas fa-pencil-alt"></i>
                    </a>
                @endif

                {{-- Thumbnail 16:9 --}}
                <div class="match-card-thumb">
                    @if($video->bunny_thumbnail)
                        <img src="{{ $video->bunny_thumbnail }}" alt="Thumbnail">
                    @else
                        <div class="match-thumb-placeholder">
                            <i class="fas fa-film"></i>
                        </div>
                    @endif
                    {{-- Play overlay al hover --}}
                    <div class="match-play-overlay">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    {{-- Status --}}
                    @if($video->bunny_status === 'error')
                        <span class="status-badge" style="background:rgba(220,53,69,.85)">
                            <i class="fas fa-exclamation-circle mr-1"></i>Error
                        </span>
                    @elseif($video->bunny_status && !in_array($video->bunny_status, ['ready', 'completed']))
                        <span class="status-badge">
                            <i class="fas fa-spinner fa-spin mr-1"></i>Procesando
                        </span>
                    @endif
                    {{-- XML --}}
                    @if($video->clips_count > 0)
                        <span class="xml-badge"><i class="fas fa-list-ul mr-1"></i>XML</span>
                    @endif
                    {{-- Ángulos --}}
                    @if($anglesCount > 1)
                        <span class="angles-badge" title="{{ $anglesTooltip }}">
                            <i class="fas fa-video mr-1"></i>{{ $anglesCount }}
                        </span>
                    @endif
                </div>

                {{-- Info fixture --}}
                <div class="match-card-body">
                    <div class="match-fixture">
                        <span class="fixture-team fixture-local">{{ $video->analyzed_team_name ?? 'Local' }}</span>
                        <span class="fixture-vs">VS</span>
                        <span class="fixture-team fixture-rival">{{ $video->rival_name ?? 'Rival' }}</span>
                    </div>
                    <div class="match-card-meta">
                        <i class="fas fa-calendar mr-1"></i>{{ $video->match_date->format('d/m/Y') }}
                        @if($video->division)
                            <span class="mx-1">·</span>{{ ucfirst($video->division) }}
                        @endif
                        @if($sizeLabel)
                            <span class="mx-1">·</span><i class="fas fa-hdd mr-1"></i>{{ $sizeLabel }}
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════
     CLUB — Nivel 2: Videos de una categoría (mismo diseño que asoc_matches)
═══════════════════════════════════════════════════════════ --}}
@elseif($view === 'matches')
@include('videos.partials.folder-header', ['title' => $category->name, 'icon' => 'layer-group', 'back' => route('videos.index')])

@if($videos->isEmpty())
    @include('videos.partials.empty-folder', [
        'msg' => 'Todavía no hay videos en esta categoría.',
        'action' => route('videos.create'),
        'actionLabel' => 'Subir el primer video'
    ])
@else
    <div class="match-grid">
        @foreach($videos as $video)
            @php
                $totalSize = $video->total_size ?? 0;
                $sizeLabel = '';
                if ($totalSize > 0) {
                    $gb = $totalSize / 1073741824;
                    $sizeLabel = $gb >= 1
                        ? number_format($gb, 1) . ' GB'
                        : number_format($totalSize / 1048576, 0) . ' MB';
                }
                $anglesCount = $video->angles_count ?? 1;

                $angleNames = [];
                $firstGroup = $video->videoGroups->first();
                if ($firstGroup) {
                    foreach ($firstGroup->videos as $gv) {
                        $angle = $gv->pivot->camera_angle ?? null;
                        if ($angle) $angleNames[] = $angle;
                    }
                }
                $anglesTooltip = !empty($angleNames)
                    ? implode(' · ', $angleNames)
                    : $anglesCount . ' ángulos';
            @endphp
            <div class="match-card" onclick="window.location.href='{{ route('videos.show', $video) }}'">
                {{-- Botones flotantes --}}
                @if(in_array(auth()->user()->role, ['analista', 'entrenador']) || auth()->id() === $video->uploaded_by)
                    <a href="{{ route('videos.edit', $video) }}"
                       class="match-edit-btn" title="Editar"
                       onclick="event.stopPropagation()">
                        <i class="fas fa-pencil-alt"></i>
                    </a>
                    <button type="button"
                            class="match-delete-btn" title="Eliminar"
                            data-toggle="modal" data-target="#deleteModal-{{ $video->id }}"
                            onclick="event.stopPropagation()">
                        <i class="fas fa-trash"></i>
                    </button>
                @endif

                {{-- Thumbnail 16:9 --}}
                <div class="match-card-thumb">
                    @if($video->bunny_thumbnail)
                        <img src="{{ $video->bunny_thumbnail }}" alt="Thumbnail">
                    @else
                        <div class="match-thumb-placeholder">
                            <i class="fas fa-film"></i>
                        </div>
                    @endif
                    <div class="match-play-overlay">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    {{-- Status --}}
                    @if($video->bunny_status === 'error')
                        <span class="status-badge" style="background:rgba(220,53,69,.85)">
                            <i class="fas fa-exclamation-circle mr-1"></i>Error
                        </span>
                    @elseif($video->bunny_status && !in_array($video->bunny_status, ['ready', 'completed']))
                        <span class="status-badge">
                            <i class="fas fa-spinner fa-spin mr-1"></i>Procesando
                        </span>
                    @endif
                    {{-- XML --}}
                    @if($video->clips_count > 0)
                        <span class="xml-badge"><i class="fas fa-list-ul mr-1"></i>XML</span>
                    @endif
                    {{-- Ángulos --}}
                    @if($anglesCount > 1)
                        <span class="angles-badge" title="{{ $anglesTooltip }}">
                            <i class="fas fa-video mr-1"></i>{{ $anglesCount }}
                        </span>
                    @endif
                </div>

                {{-- Info fixture --}}
                <div class="match-card-body">
                    <div class="match-fixture">
                        <span class="fixture-team fixture-local">{{ $video->analyzed_team_name ?? 'Local' }}</span>
                        <span class="fixture-vs">VS</span>
                        <span class="fixture-team fixture-rival">{{ $video->rival_name ?? 'Rival' }}</span>
                    </div>
                    <div class="match-card-meta">
                        <i class="fas fa-calendar mr-1"></i>{{ $video->match_date->format('d/m/Y') }}
                        @if($video->division)
                            <span class="mx-1">·</span>{{ ucfirst($video->division) }}
                        @endif
                        @if($sizeLabel)
                            <span class="mx-1">·</span><i class="fas fa-hdd mr-1"></i>{{ $sizeLabel }}
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modales de eliminación --}}
    @foreach($videos as $video)
        @if(in_array(auth()->user()->role, ['analista', 'entrenador']) || auth()->id() === $video->uploaded_by)
        <div class="modal fade" id="deleteModal-{{ $video->id }}" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header modal-header-rugby text-white">
                        <h5 class="modal-title"><i class="fas fa-trash mr-2"></i>Confirmar eliminación</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-center mb-3">¿Eliminar <strong>{{ $video->title }}</strong>?</p>
                        <p class="text-muted text-center small mb-0">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-delete btn-sm btn-delete-video"
                                data-video-id="{{ $video->id }}"
                                data-url="{{ route('videos.destroy', $video) }}">
                            <i class="fas fa-trash mr-1"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach

    <div class="d-flex justify-content-center mt-3">
        {{ $videos->appends(request()->query())->links('custom.pagination') }}
    </div>
@endif


{{-- ═══════════════════════════════════════════════════════════
     JUGADOR (ASOCIACIÓN) — Nivel 1: Torneos de su equipo
═══════════════════════════════════════════════════════════ --}}
@elseif($view === 'player_tournaments')
@include('videos.partials.folder-header', ['title' => 'Mis Torneos', 'icon' => 'trophy'])
@if($tournaments->isEmpty())
    @include('videos.partials.empty-folder', ['msg' => 'No hay torneos disponibles para tu equipo aún.'])
@else
    <div class="folder-grid">
        @foreach($tournaments as $t)
            <div class="folder-card-wrap">
                <a href="{{ route('videos.index', ['tournament' => $t->id]) }}"
                   class="folder-card text-decoration-none">
                    <div class="folder-icon-wrap"><i class="fas fa-trophy"></i></div>
                    <div class="folder-name">{{ $t->name }}</div>
                    <div class="folder-meta">{{ $t->videos_count }} partidos</div>
                </a>
            </div>
        @endforeach
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════
     JUGADOR — Misma vista de cards que analistas/entrenadores
═══════════════════════════════════════════════════════════ --}}
@elseif($view === 'player_matches')

    {{-- Header cuando viene de un torneo (asociación) --}}
    @if(isset($tournament))
        @include('videos.partials.folder-header', ['title' => $tournament->name, 'icon' => 'trophy', 'back' => route('videos.index')])
    @endif

    {{-- Barra de búsqueda --}}
    <div class="d-flex align-items-center mb-3" style="gap:8px">
        <form method="GET" action="{{ route('videos.index') }}" class="d-flex" style="gap:8px;flex:1">
            <input type="text" name="search" class="form-control form-control-sm bg-dark border-secondary text-white"
                   style="max-width:260px" placeholder="Buscar por título, local o rival..."
                   value="{{ request('search') }}">
            <button type="submit" class="btn btn-rugby btn-sm">
                <i class="fas fa-search mr-1"></i>Buscar
            </button>
            @if(request('search'))
                <a href="{{ route('videos.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times mr-1"></i>Limpiar
                </a>
            @endif
        </form>
    </div>

    @if($videos->isEmpty())
        @include('videos.partials.empty-folder', ['msg' => 'No hay videos disponibles aún.'])
    @else
        <div class="match-grid">
            @foreach($videos as $video)
                @php
                    $totalSize = $video->total_size ?? 0;
                    $sizeLabel = '';
                    if ($totalSize > 0) {
                        $gb = $totalSize / 1073741824;
                        $sizeLabel = $gb >= 1
                            ? number_format($gb, 1) . ' GB'
                            : number_format($totalSize / 1048576, 0) . ' MB';
                    }
                    $anglesCount = $video->angles_count ?? 1;
                @endphp
                <div class="match-card" onclick="window.location.href='{{ route('videos.show', $video) }}'">
                    {{-- Thumbnail 16:9 --}}
                    <div class="match-card-thumb">
                        @if($video->bunny_thumbnail)
                            <img src="{{ $video->bunny_thumbnail }}" alt="Thumbnail">
                        @else
                            <div class="match-thumb-placeholder">
                                <i class="fas fa-film"></i>
                            </div>
                        @endif
                        <div class="match-play-overlay">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        {{-- Status --}}
                        @if($video->bunny_status === 'error')
                            <span class="status-badge" style="background:rgba(220,53,69,.85)">
                                <i class="fas fa-exclamation-circle mr-1"></i>Error
                            </span>
                        @elseif($video->bunny_status && !in_array($video->bunny_status, ['ready', 'completed']))
                            <span class="status-badge">
                                <i class="fas fa-spinner fa-spin mr-1"></i>Procesando
                            </span>
                        @endif
                        {{-- XML --}}
                        @if($video->clips_count > 0)
                            <span class="xml-badge"><i class="fas fa-list-ul mr-1"></i>XML</span>
                        @endif
                        {{-- Ángulos --}}
                        @if($anglesCount > 1)
                            <span class="angles-badge">
                                <i class="fas fa-video mr-1"></i>{{ $anglesCount }}
                            </span>
                        @endif
                    </div>

                    {{-- Info fixture --}}
                    <div class="match-card-body">
                        <div class="match-fixture">
                            <span class="fixture-team fixture-local">{{ $video->analyzed_team_name ?? 'Local' }}</span>
                            <span class="fixture-vs">VS</span>
                            <span class="fixture-team fixture-rival">{{ $video->rival_name ?? 'Rival' }}</span>
                        </div>
                        <div class="match-card-meta">
                            <i class="fas fa-calendar mr-1"></i>{{ $video->match_date->format('d/m/Y') }}
                            @if($video->division)
                                <span class="mx-1">·</span>{{ ucfirst($video->division) }}
                            @endif
                            @if($sizeLabel)
                                <span class="mx-1">·</span><i class="fas fa-hdd mr-1"></i>{{ $sizeLabel }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $videos->appends(request()->query())->links('custom.pagination') }}
        </div>
    @endif

@endif

{{-- Modal: Crear Torneo (solo asociaciones) --}}
@if(isset($view) && $view === 'asoc_tournaments')
<div class="modal fade" id="createTournamentModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary py-2">
                <h6 class="modal-title"><i class="fas fa-trophy mr-2 text-warning"></i>Nuevo Torneo</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body py-2">
                <div class="form-group mb-0">
                    <input type="text" id="newTournamentName" class="form-control form-control-sm bg-dark border-secondary text-white"
                        placeholder="Nombre del torneo..." maxlength="255">
                    <div id="tournamentError" class="text-danger small mt-1 d-none"></div>
                </div>
            </div>
            <div class="modal-footer border-secondary py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-rugby btn-sm" id="saveTournamentBtn">
                    <i class="fas fa-save mr-1"></i> Crear
                </button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('saveTournamentBtn').addEventListener('click', function() {
    const name = document.getElementById('newTournamentName').value.trim();
    const errEl = document.getElementById('tournamentError');
    if (!name) { errEl.textContent = 'Ingresa un nombre.'; errEl.classList.remove('d-none'); return; }
    errEl.classList.add('d-none');
    this.disabled = true;
    fetch('{{ route("api.tournaments.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ name })
    })
    .then(r => r.json())
    .then(data => {
        if (data.id) { window.location.reload(); }
        else { errEl.textContent = data.message || 'Error al crear.'; errEl.classList.remove('d-none'); this.disabled = false; }
    })
    .catch(() => { errEl.textContent = 'Error de red.'; errEl.classList.remove('d-none'); this.disabled = false; });
});
document.getElementById('newTournamentName').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('saveTournamentBtn').click();
});
</script>
@endpush
@endif

@endsection

@push('styles')
<style>
/* ─── Carpetas ─────────────────────────────────────────── */
.folder-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
}
.folder-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px 12px 16px;
    background: #1a1a1a;
    border: 1px solid #2d2d2d;
    border-radius: 10px;
    cursor: pointer;
    transition: border-color .2s, background .2s, transform .15s;
    color: inherit;
}
.folder-card:hover {
    border-color: #005461;
    background: #1e2e30;
    transform: translateY(-3px);
    color: inherit;
}
.folder-icon-wrap {
    font-size: 3rem;
    color: #b8860b;
    margin-bottom: 10px;
    line-height: 1;
}
.folder-no-tournament {
    color: #555;
}
.folder-name {
    font-size: .88rem;
    font-weight: 600;
    color: #e0e0e0;
    text-align: center;
    word-break: break-word;
    margin-bottom: 4px;
}
.folder-meta {
    font-size: .75rem;
    color: #777;
    text-align: center;
}

/* ─── Grid de videos ───────────────────────────────────── */
.video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}

/* ─── Tarjetas de video ────────────────────────────────── */
.video-thumbnail-container {
    height: 85px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    background: #111;
}
.status-badge {
    position: absolute;
    bottom: 3px;
    left: 3px;
    background: rgba(0,0,0,.75);
    color: #fff;
    font-size: .6rem;
    padding: 1px 5px;
    border-radius: 8px;
}
.xml-badge {
    position: absolute;
    top: 3px;
    left: 3px;
    background: rgba(212, 160, 23, .85);
    color: #fff;
    font-size: .58rem;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 5px;
    letter-spacing: .03em;
}
.video-card {
    background: #1a1a1a;
    border: 1px solid #2d2d2d;
    transition: border-color .2s, transform .15s;
}
.video-card:hover { border-color: #005461; transform: translateY(-2px); }
.video-card .card-body { min-height: 80px; }
.video-meta {
    font-size: .7rem;
    color: #777;
    margin-top: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.btn-xs { padding: 2px 7px; font-size: .72rem; }
.video-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: .8rem;
    line-height: 1.3;
}

/* ─── Colores rugby ────────────────────────────────────── */
.btn-rugby        { background:#005461; border-color:#005461; color:#fff; }
.btn-rugby:hover  { background:#003d4a; border-color:#003d4a; color:#fff; }
.btn-rugby-light  { background:#D4A017; border-color:#D4A017; color:#fff; }
.btn-rugby-light:hover { background:#009e9c; color:#fff; }
.btn-delete       { background:transparent; border-color:#2d4a4e; color:#6a9a9e; }
.btn-delete:hover { background:#1a2e30; border-color:#005461; color:#D4A017; }
.modal-header-rugby { background:#005461; }
.card-rugby       { border-color:#005461; }
.card-rugby .card-header { background:#005461; }
.badge-rugby      { background:#005461; color:#fff; font-size:.8em; }
.badge-sm         { font-size:.75em; padding:.2rem .5rem; }

/* ─── Match grid (asociaciones) ───────────────────────── */
.match-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}
.match-card {
    background: #1a1a1a;
    border: 1px solid #2d2d2d;
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    position: relative;
    transition: border-color .2s, transform .15s, box-shadow .2s;
}
.match-card:hover {
    border-color: #005461;
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,84,97,.25);
}
/* Botón editar flotante */
.match-edit-btn {
    position: absolute;
    top: 8px; right: 8px;
    z-index: 10;
    width: 28px; height: 28px;
    background: rgba(0,0,0,.65);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #aaa; font-size: .7rem;
    transition: background .2s, color .2s;
    text-decoration: none;
}
.match-edit-btn:hover { background: #D4A017; color: #fff; text-decoration: none; }
/* Botón eliminar flotante (club view) */
.match-delete-btn {
    position: absolute;
    top: 8px; left: 8px;
    z-index: 10;
    width: 28px; height: 28px;
    background: rgba(0,0,0,.65);
    border: none;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #aaa; font-size: .7rem;
    cursor: pointer;
    transition: background .2s, color .2s;
    padding: 0;
}
.match-delete-btn:hover { background: rgba(220,53,69,.85); color: #fff; }
/* Thumbnail 16:9 */
.match-card-thumb {
    position: relative;
    width: 100%;
    padding-top: 56.25%;
    background: #111;
    overflow: hidden;
}
.match-card-thumb img {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: cover;
}
.match-thumb-placeholder {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    color: #333; font-size: 2rem;
}
/* Play overlay al hover */
.match-play-overlay {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,.4);
    display: flex; align-items: center; justify-content: center;
    opacity: 0;
    transition: opacity .2s;
    font-size: 2.8rem;
    color: rgba(255,255,255,.9);
}
.match-card:hover .match-play-overlay { opacity: 1; }
/* Ángulos badge */
.angles-badge {
    position: absolute;
    bottom: 3px; right: 3px;
    background: rgba(0,84,97,.92);
    color: #D4A017;
    font-size: .58rem; font-weight: 700;
    padding: 2px 6px; border-radius: 5px;
}
/* Info fixture */
.match-card-body { padding: 8px 10px 10px; }
.match-fixture {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 5px;
    margin-bottom: 5px;
}
.fixture-team {
    font-size: .75rem; font-weight: 700; color: #e0e0e0;
    flex: 1; line-height: 1.25;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.fixture-local { text-align: right; }
.fixture-rival { text-align: left; }
.fixture-vs {
    font-size: .58rem; font-weight: 800;
    color: #D4A017; background: #0a3038;
    padding: 2px 6px; border-radius: 10px;
    flex-shrink: 0; letter-spacing: .06em;
}
.match-card-meta {
    font-size: .67rem; color: #666; text-align: center;
}

/* ─── Context menu ─────────────────────────────────────── */
#folder-context-menu {
    position: fixed;
    background: #1e1e1e;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 4px 0;
    min-width: 160px;
    z-index: 9999;
    box-shadow: 0 8px 24px rgba(0,0,0,.5);
    display: none;
}
#folder-context-menu li {
    list-style: none;
    padding: 8px 16px;
    cursor: pointer;
    font-size: .85rem;
    color: #ccc;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background .15s;
}
#folder-context-menu li:hover { background: #005461; color: #fff; }
#folder-context-menu li.ctx-item--danger { color: #e07070; }
#folder-context-menu li.ctx-item--danger:hover { background: #6b1a1a; color: #fff; }
</style>
@endpush

{{-- Context menu HTML --}}
<ul id="folder-context-menu" style="display:none">
    <li id="ctx-rename"><i class="fas fa-pencil-alt"></i> Renombrar</li>
    <li class="ctx-divider" style="height:1px;background:#333;margin:4px 0;padding:0;pointer-events:none;"></li>
    <li id="ctx-delete" class="ctx-item--danger"><i class="fas fa-trash-alt"></i> Eliminar</li>
</ul>

@push('scripts')
<script>
(function () {
    const menu    = document.getElementById('folder-context-menu');
    let activeEl  = null;

    // Abrir menú con click derecho en carpetas que tengan data-rename-url
    document.addEventListener('contextmenu', function (e) {
        const card = e.target.closest('[data-rename-url]');
        if (!card) { menu.style.display = 'none'; return; }

        e.preventDefault();
        activeEl = card;

        menu.style.display = 'block';
        menu.style.left    = Math.min(e.clientX, window.innerWidth  - 180) + 'px';
        menu.style.top     = Math.min(e.clientY, window.innerHeight - 80)  + 'px';
    });

    // Cerrar al hacer click en cualquier otro lado
    document.addEventListener('click', () => menu.style.display = 'none');

    // Renombrar
    document.getElementById('ctx-rename').addEventListener('click', async function () {
        if (!activeEl) return;

        const url     = activeEl.dataset.renameUrl;
        const current = activeEl.dataset.renameName;
        const newName = prompt('Nuevo nombre:', current);

        if (!newName || newName.trim() === current.trim()) return;

        try {
            const res = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ name: newName.trim() }),
            });

            const data = await res.json();
            if (data.ok) {
                // Actualizar el texto visible sin recargar
                activeEl.dataset.renameName = data.name;
                const nameEl = activeEl.querySelector('.folder-name');
                if (nameEl) nameEl.textContent = data.name;
            }
        } catch (err) {
            alert('Error al renombrar. Intente nuevamente.');
        }
    });

    // Eliminar
    document.getElementById('ctx-delete').addEventListener('click', async function () {
        if (!activeEl) return;

        const deleteUrl   = activeEl.dataset.deleteUrl;
        const videosCount = parseInt(activeEl.dataset.videosCount || '0', 10);
        const folderName  = activeEl.dataset.renameName || activeEl.querySelector('.folder-name')?.textContent?.trim() || 'este elemento';

        if (!deleteUrl) {
            alert('No se puede eliminar este elemento.');
            return;
        }

        let confirmed = false;
        if (videosCount > 0) {
            confirmed = confirm(
                '"' + folderName + '" tiene ' + videosCount + ' video(s).\n' +
                'Al eliminarlo, los videos se eliminarán PERMANENTEMENTE del servidor y de Bunny.\n' +
                'Esta acción no se puede deshacer.\n\n' +
                '¿Confirmar eliminación?'
            );
        } else {
            confirmed = confirm('¿Eliminar "' + folderName + '"?');
        }

        if (!confirmed) return;

        // Indicador visual de procesamiento mientras el servidor elimina los archivos
        const wrap = activeEl.closest('.folder-card-wrap');
        if (wrap) {
            wrap.style.opacity  = '0.4';
            wrap.style.cursor   = 'wait';
            wrap.style.pointerEvents = 'none';
        }

        try {
            const res = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            const data = await res.json();

            if (data.ok) {
                // Fade out y eliminar el wrap del DOM
                if (wrap) {
                    wrap.style.transition = 'opacity .3s ease';
                    wrap.style.opacity    = '0';
                    setTimeout(() => wrap.remove(), 310);
                }
            } else {
                // Restaurar el estado visual si el servidor devuelve error
                if (wrap) {
                    wrap.style.opacity       = '1';
                    wrap.style.cursor        = '';
                    wrap.style.pointerEvents = '';
                }
                alert(data.message || 'No se pudo eliminar.');
            }
        } catch (err) {
            // Restaurar el estado visual ante errores de red
            if (wrap) {
                wrap.style.opacity       = '1';
                wrap.style.cursor        = '';
                wrap.style.pointerEvents = '';
            }
            alert('Error de red. Intente nuevamente.');
        }
    });
})();
</script>
@endpush
