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

{{-- Sección: Recibidos de Torneos (árbol Torneo → División → Videos) --}}
@if(isset($receivedByTournament) && $receivedByTournament->isNotEmpty())
    <div class="mt-4 mb-3" style="border-top:1px solid rgba(255,255,255,.08);padding-top:1.5rem;">
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-share-alt mr-2" style="color:#00B7B5;"></i>
            <h6 class="mb-0 font-weight-bold" style="color:#00B7B5;letter-spacing:.05em;font-size:.85rem;">
                RECIBIDOS DE TORNEOS
            </h6>
        </div>

        @foreach($receivedByTournament as $tIdx => $tournamentGroup)
            @php $tName = $tournamentGroup['tournament']?->name ?? 'Sin torneo'; @endphp
            <div class="mb-3">
                {{-- Torneo header --}}
                <div class="d-flex align-items-center mb-2" style="gap:8px;">
                    <i class="fas fa-trophy" style="color:#b8860b;font-size:.9rem;"></i>
                    <span style="font-weight:700;font-size:.95rem;color:#f0f0f0;">{{ $tName }}</span>
                </div>

                {{-- Carpetas por división (colapsables) --}}
                <div style="padding-left:16px;display:flex;flex-direction:column;gap:6px;">
                @foreach($tournamentGroup['divisions'] as $dIdx => $divisionGroup)
                    @php
                        $dName  = $divisionGroup['division']?->name ?? 'Sin división';
                        $videos = $divisionGroup['videos'];
                        $dCount = $videos->count();
                        $folderId = 'div-folder-' . $tIdx . '-' . $dIdx;
                    @endphp

                    {{-- Carpeta colapsable --}}
                    <div>
                        <div class="div-folder-header" onclick="toggleDivFolder('{{ $folderId }}')"
                             style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;cursor:pointer;transition:background .15s;user-select:none;"
                             onmouseover="this.style.background='rgba(0,183,181,.07)'"
                             onmouseout="this.style.background='rgba(255,255,255,.04)'">
                            <i class="fas fa-folder" id="{{ $folderId }}-icon"
                               style="color:#00B7B5;font-size:.95rem;transition:transform .2s;"></i>
                            <span style="font-weight:600;font-size:.88rem;color:#e0e0e0;flex-grow:1;">{{ $dName }}</span>
                            <span style="font-size:.75rem;background:rgba(0,183,181,.15);color:#00B7B5;border:1px solid rgba(0,183,181,.3);border-radius:10px;padding:1px 8px;">
                                {{ $dCount }} {{ $dCount === 1 ? 'video' : 'videos' }}
                            </span>
                            <i class="fas fa-chevron-right" id="{{ $folderId }}-chevron"
                               style="color:rgba(255,255,255,.3);font-size:.75rem;transition:transform .2s;"></i>
                        </div>

                        {{-- Videos (ocultos por defecto) --}}
                        <div id="{{ $folderId }}" style="display:none;padding:12px 4px 4px;">
                            <div class="match-grid">
                                @foreach($videos as $sv)
                                    @php
                                        $svSize = '';
                                        $svTotal = $sv->total_size ?? 0;
                                        if ($svTotal > 0) {
                                            $svSize = $svTotal >= 1073741824
                                                ? number_format($svTotal / 1073741824, 1) . ' GB'
                                                : number_format($svTotal / 1048576, 0) . ' MB';
                                        }
                                    @endphp
                                    <div class="match-card" onclick="window.location.href='{{ route('videos.show', $sv) }}'">
                                        <div class="match-card-thumb">
                                            @if($sv->bunny_thumbnail)
                                                <img src="{{ $sv->bunny_thumbnail }}" alt="Thumbnail">
                                            @else
                                                <div class="match-thumb-placeholder"><i class="fas fa-film"></i></div>
                                            @endif
                                            <div class="match-play-overlay"><i class="fas fa-play-circle"></i></div>
                                        </div>
                                        <div class="match-card-body">
                                            <div class="match-fixture">
                                                <span class="fixture-team fixture-local">{{ $sv->analyzed_team_name ?? 'Local' }}</span>
                                                <span class="fixture-vs">VS</span>
                                                <span class="fixture-team fixture-rival">{{ $sv->rivalTeam?->name ?? $sv->rival_name ?? 'Rival' }}</span>
                                            </div>
                                            <div class="match-card-meta">
                                                <i class="fas fa-calendar mr-1"></i>{{ $sv->match_date?->format('d/m/Y') ?? '—' }}
                                                @if($svSize)<span class="mx-1">·</span><i class="fas fa-hdd mr-1"></i>{{ $svSize }}@endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>
            </div>
        @endforeach
    </div>
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
                {{-- Botones flotantes --}}
                @if(in_array(auth()->user()->role, ['analista', 'entrenador']) || auth()->id() === $video->uploaded_by)
                    <a href="{{ route('videos.edit', $video) }}"
                       class="match-edit-btn" title="Editar"
                       onclick="event.stopPropagation()">
                        <i class="fas fa-pencil-alt"></i>
                    </a>
                    <button type="button"
                            class="match-delete-btn" title="Eliminar"
                            onclick="event.stopPropagation();$('#deleteModal-{{ $video->id }}').modal('show')">
                        <i class="fas fa-trash"></i>
                    </button>
                    {{-- Enviar a club: disponible desde dentro del video (botón en VideoHeader) --}}
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

    {{-- Modales de eliminación --}}
    @foreach($matches as $video)
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

    {{-- Modal para compartir video con club --}}
    @include('videos.partials.share-modal')
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
                            onclick="event.stopPropagation();$('#deleteModal-{{ $video->id }}').modal('show')">
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
                    @if($video->tournament)
                        <div class="mt-1">
                            <span style="background:rgba(0,183,181,.12);border:1px solid rgba(0,183,181,.3);color:#00B7B5;border-radius:10px;padding:1px 8px;font-size:.7rem;">
                                <i class="fas fa-trophy mr-1" style="font-size:.65rem;"></i>{{ $video->tournament->name }}
                            </span>
                        </div>
                    @endif
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

    {{-- Videos compartidos por asociaciones (solo si existen) --}}
    @if(isset($sharedVideos) && $sharedVideos->isNotEmpty())
        <div class="mt-4 mb-2">
            <h6 style="color:#00B7B5; font-size:0.85rem; font-weight:600; letter-spacing:.05em;">
                <i class="fas fa-share-alt mr-2"></i>RECIBIDOS DE ASOCIACIONES
            </h6>
        </div>
        <div class="match-grid">
            @foreach($sharedVideos as $share)
                @php
                    $sv = $share->video;
                    $svGroup = $sv->videoGroups->first();
                    $sv->total_size = $svGroup && $svGroup->videos->isNotEmpty()
                        ? $svGroup->videos->sum(fn ($v) => $v->compressed_file_size ?? $v->file_size ?? 0)
                        : ($sv->compressed_file_size ?? $sv->file_size ?? 0);
                    $sv->angles_count = $svGroup ? $svGroup->videos->count() : 1;
                    $svSize = $sv->total_size > 0
                        ? ($sv->total_size >= 1073741824
                            ? number_format($sv->total_size / 1073741824, 1) . ' GB'
                            : number_format($sv->total_size / 1048576, 0) . ' MB')
                        : '';
                @endphp
                <div class="match-card" onclick="window.location.href='{{ route('videos.show', $sv) }}'">
                    {{-- Thumbnail --}}
                    <div class="match-card-thumb">
                        @if($sv->bunny_thumbnail)
                            <img src="{{ $sv->bunny_thumbnail }}" alt="Thumbnail">
                        @else
                            <div class="match-thumb-placeholder"><i class="fas fa-film"></i></div>
                        @endif
                        <div class="match-play-overlay"><i class="fas fa-play-circle"></i></div>
                        {{-- Badge de origen --}}
                        <div style="position:absolute;bottom:6px;left:6px;">
                            @include('videos.partials.shared-badge', ['share' => $share])
                        </div>
                    </div>
                    {{-- Info --}}
                    <div class="match-card-body">
                        <div class="match-fixture">
                            <span class="fixture-team fixture-local">{{ $sv->analyzed_team_name ?? 'Local' }}</span>
                            <span class="fixture-vs">VS</span>
                            <span class="fixture-team fixture-rival">{{ $sv->rivalTeam?->name ?? $sv->rival_name ?? 'Rival' }}</span>
                        </div>
                        <div class="match-card-meta">
                            <i class="fas fa-calendar mr-1"></i>{{ $sv->match_date?->format('d/m/Y') ?? '—' }}
                            @if($svSize)
                                <span class="mx-1">·</span><i class="fas fa-hdd mr-1"></i>{{ $svSize }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endif


{{-- ═══════════════════════════════════════════════════════════
     CLUB — Videos recibidos de una asociación específica
═══════════════════════════════════════════════════════════ --}}
@elseif($view === 'received_videos')
@include('videos.partials.folder-header', [
    'title' => isset($division)
        ? 'División ' . $division->name . ' — ' . ($division->tournament->name ?? '')
        : 'Recibidos de ' . ($sourceOrg->name ?? ''),
    'icon' => 'share-alt',
    'back' => route('videos.index')
])

@if($sharedVideos->isEmpty())
    @include('videos.partials.empty-folder', ['msg' => 'No hay videos compartidos de esta asociación.'])
@else
    <div class="match-grid">
        @foreach($sharedVideos as $share)
            @php
                $sv = $share->video;
                $totalSize = $sv->total_size ?? 0;
                $sizeLabel = '';
                if ($totalSize > 0) {
                    $gb = $totalSize / 1073741824;
                    $sizeLabel = $gb >= 1
                        ? number_format($gb, 1) . ' GB'
                        : number_format($totalSize / 1048576, 0) . ' MB';
                }
            @endphp
            <div class="match-card" onclick="window.location.href='{{ route('videos.show', $sv) }}'">
                <div class="match-card-thumb">
                    @if($sv->bunny_thumbnail)
                        <img src="{{ $sv->bunny_thumbnail }}" alt="Thumbnail">
                    @else
                        <div class="match-thumb-placeholder"><i class="fas fa-film"></i></div>
                    @endif
                    <div class="match-play-overlay"><i class="fas fa-play-circle"></i></div>
                    {{-- Badge origen --}}
                    <div style="position:absolute;bottom:6px;left:6px;">
                        @include('videos.partials.shared-badge', ['share' => $share])
                    </div>
                    @if($sv->tournament)
                        <div style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,.6);border-radius:4px;padding:2px 6px;font-size:.65rem;color:#fff;">
                            {{ $sv->tournament->name }}
                        </div>
                    @endif
                </div>
                <div class="match-card-body">
                    <div class="match-fixture">{{ $sv->title }}</div>
                    @if($sv->match_date ?? null)
                        <div class="match-meta">{{ \Carbon\Carbon::parse($sv->match_date)->format('d/m/Y') }}</div>
                    @endif
                    @if($sizeLabel)
                        <div class="match-meta text-muted">{{ $sizeLabel }}</div>
                    @endif
                </div>
            </div>
        @endforeach
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

{{-- Modal: Crear Torneo (solo asociaciones) — Paso 1: Nombre + Temporada --}}
@if(isset($view) && $view === 'asoc_tournaments')
<div class="modal fade" id="createTournamentModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="background:#1a1a1a;border:1px solid rgba(255,255,255,.12);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.1);padding:12px 18px;">
                <h6 class="modal-title" style="color:#fff;">
                    <i class="fas fa-trophy mr-2" style="color:#b8860b;"></i>Nuevo Torneo
                </h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" style="padding:18px;">
                <div class="form-group mb-3">
                    <label style="font-size:.78rem;color:#aaa;font-weight:600;display:block;margin-bottom:4px;">
                        Nombre <span style="color:#dc3545;">*</span>
                    </label>
                    <input type="text" id="newTournamentName"
                           class="form-control form-control-sm"
                           style="background:#111;border:1px solid #444;color:#fff;"
                           placeholder="Ej: URBA 2026" maxlength="255">
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:.78rem;color:#aaa;font-weight:600;display:block;margin-bottom:4px;">
                        Temporada (opcional)
                    </label>
                    <input type="text" id="newTournamentSeason"
                           class="form-control form-control-sm"
                           style="background:#111;border:1px solid #444;color:#fff;"
                           placeholder="Ej: 2026" maxlength="20">
                </div>
                <div id="tournamentError" class="text-danger small mt-2 d-none"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.1);padding:10px 18px;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-rugby btn-sm" id="saveTournamentBtn">
                    <i class="fas fa-arrow-right mr-1"></i> Siguiente
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Crear Torneo — Paso 2: Divisiones + Visibilidad --}}
<div class="modal fade" id="createTournamentDivisionsModal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document" style="max-width:500px;">
        <div class="modal-content" style="background:#1a1a1a;border:1px solid rgba(255,255,255,.12);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.1);padding:12px 18px;">
                <h6 class="modal-title" style="color:#fff;">
                    <i class="fas fa-layer-group mr-2" style="color:#00B7B5;"></i>
                    Configurar torneo
                </h6>
                <small id="ctd-tournament-name" style="color:#aaa;margin-left:8px;"></small>
            </div>
            <div class="modal-body" style="padding:18px;">
                <p style="font-size:.83rem;color:#aaa;margin-bottom:14px;">
                    Agregá las divisiones del torneo (opcional).
                </p>

                {{-- Input para agregar división --}}
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <input type="text" id="ctd-custom-input"
                           style="flex:1;background:#111;border:1px solid #444;color:#fff;border-radius:4px;padding:5px 12px;font-size:.85rem;outline:none;"
                           placeholder="Nombre de la división (ej: Adulta, M18...)">
                    <button type="button" id="ctd-add-btn"
                            style="background:rgba(0,183,181,.15);border:1px solid #00B7B5;color:#00B7B5;border-radius:4px;padding:5px 14px;font-size:.82rem;cursor:pointer;white-space:nowrap;">
                        <i class="fas fa-plus mr-1"></i> Agregar
                    </button>
                </div>

                {{-- Divisiones agregadas --}}
                <div id="ctd-added-pills" style="display:flex;flex-wrap:wrap;gap:7px;min-height:24px;margin-bottom:4px;"></div>
                <div id="ctd-div-error" class="text-danger small mt-1 d-none"></div>

                {{-- Visibilidad --}}
                <div style="margin-top:16px;padding:12px 14px;background:rgba(255,255,255,.04);border-radius:6px;border:1px solid rgba(255,255,255,.08);">
                    <div style="font-size:.78rem;color:#aaa;font-weight:600;margin-bottom:10px;">
                        <i class="fas fa-globe mr-1"></i> ¿Los clubes pueden inscribirse?
                    </div>
                    <div style="display:flex;gap:8px;">
                        <label id="ctd-opt-private" style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid rgba(0,183,181,.4);background:rgba(0,183,181,.08);flex:1;transition:all .15s;">
                            <input type="radio" name="ctd-visibility" value="private" checked style="accent-color:#00B7B5;">
                            <span>
                                <i class="fas fa-lock mr-1" style="color:rgba(255,255,255,.5);font-size:.8rem;"></i>
                                <span style="font-size:.83rem;color:rgba(255,255,255,.8);">Privado por ahora</span>
                            </span>
                        </label>
                        <label id="ctd-opt-public" style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid rgba(255,255,255,.1);background:transparent;flex:1;transition:all .15s;">
                            <input type="radio" name="ctd-visibility" value="public" style="accent-color:#00B7B5;">
                            <span>
                                <i class="fas fa-globe mr-1" style="color:rgba(0,183,181,.7);font-size:.8rem;"></i>
                                <span style="font-size:.83rem;color:rgba(255,255,255,.8);">Publicar ahora</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.1);padding:10px 18px;justify-content:space-between;align-items:center;">
                <a href="#" id="ctd-skip-link" style="font-size:.8rem;color:rgba(255,255,255,.4);text-decoration:none;">
                    Continuar sin divisiones
                </a>
                <button type="button" id="ctd-continue-btn" class="btn btn-rugby btn-sm">
                    <i class="fas fa-check mr-1"></i> Guardar y continuar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var currentTournamentId = null;
    var csrf = '{{ csrf_token() }}';

    // ── Paso 1: Crear torneo ──────────────────────────────────────────
    var saveBtn = document.getElementById('saveTournamentBtn');
    var nameInput = document.getElementById('newTournamentName');
    var seasonInput = document.getElementById('newTournamentSeason');
    var errEl = document.getElementById('tournamentError');

    function doCreate() {
        var name = nameInput.value.trim();
        var season = seasonInput.value.trim();
        if (!name) { errEl.textContent = 'El nombre es obligatorio.'; errEl.classList.remove('d-none'); return; }
        errEl.classList.add('d-none');
        saveBtn.disabled = true;

        fetch('{{ route("api.tournaments.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ name: name, season: season || null })
        })
        .then(r => r.json())
        .then(data => {
            if (data.id) {
                currentTournamentId = data.id;
                document.getElementById('ctd-tournament-name').textContent = '— ' + name;
                document.getElementById('ctd-added-pills').innerHTML = '';
                document.getElementById('ctd-custom-input').value = '';
                document.getElementById('ctd-div-error').classList.add('d-none');
                // Reset visibility
                document.querySelector('input[name="ctd-visibility"][value="private"]').checked = true;
                document.getElementById('ctd-opt-private').style.cssText = 'display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid rgba(0,183,181,.4);background:rgba(0,183,181,.08);flex:1;transition:all .15s;';
                document.getElementById('ctd-opt-public').style.cssText  = 'display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid rgba(255,255,255,.1);background:transparent;flex:1;transition:all .15s;';

                $('#createTournamentModal').one('hidden.bs.modal', function () {
                    setTimeout(function () { $('#createTournamentDivisionsModal').modal('show'); }, 150);
                });
                $('#createTournamentModal').modal('hide');
            } else {
                errEl.textContent = data.message || 'Error al crear.';
                errEl.classList.remove('d-none');
                saveBtn.disabled = false;
            }
        })
        .catch(() => { errEl.textContent = 'Error de red.'; errEl.classList.remove('d-none'); saveBtn.disabled = false; });
    }

    saveBtn.addEventListener('click', doCreate);
    nameInput.addEventListener('keydown', e => { if (e.key === 'Enter') doCreate(); });
    seasonInput.addEventListener('keydown', e => { if (e.key === 'Enter') doCreate(); });

    // Reset modal 1 on hide
    $('#createTournamentModal').on('hidden.bs.modal', function () {
        nameInput.value = '';
        seasonInput.value = '';
        errEl.classList.add('d-none');
        saveBtn.disabled = false;
    });

    // ── Paso 2: Divisiones ────────────────────────────────────────────
    function addDivPill(divId, divName) {
        var pill = document.createElement('span');
        pill.style.cssText = 'display:inline-flex;align-items:center;gap:6px;background:rgba(0,183,181,.15);border:1px solid rgba(0,183,181,.5);color:#00B7B5;border-radius:20px;padding:4px 12px;font-size:.85rem;font-weight:500;';
        pill.innerHTML = divName + ' <a href="#" style="color:rgba(0,183,181,.7);text-decoration:none;font-size:1rem;line-height:1;" onclick="(function(e){e.preventDefault();e.target.closest(\'span\').remove();})(event)">&times;</a>';
        document.getElementById('ctd-added-pills').appendChild(pill);
    }

    function submitDiv(name) {
        if (!name || !currentTournamentId) return;
        fetch('/api/tournaments/' + currentTournamentId + '/divisions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ name: name })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                addDivPill(data.division.id, data.division.name);
                document.getElementById('ctd-custom-input').value = '';
                document.getElementById('ctd-div-error').classList.add('d-none');
            } else {
                var e = document.getElementById('ctd-div-error');
                e.textContent = data.error || 'Error al crear la división.';
                e.classList.remove('d-none');
            }
        });
    }

    document.getElementById('ctd-add-btn').addEventListener('click', function () {
        var val = document.getElementById('ctd-custom-input').value.trim();
        if (val) submitDiv(val);
    });
    document.getElementById('ctd-custom-input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('ctd-add-btn').click(); }
    });

    // Visibilidad
    document.querySelectorAll('input[name="ctd-visibility"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var isPrivate = this.value === 'private';
            document.getElementById('ctd-opt-private').style.cssText = 'display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid ' + (isPrivate ? 'rgba(0,183,181,.4);background:rgba(0,183,181,.08)' : 'rgba(255,255,255,.1);background:transparent') + ';flex:1;transition:all .15s;';
            document.getElementById('ctd-opt-public').style.cssText  = 'display:flex;align-items:center;gap:8px;cursor:pointer;padding:7px 14px;border-radius:6px;border:1px solid ' + (!isPrivate ? 'rgba(0,183,181,.4);background:rgba(0,183,181,.08)' : 'rgba(255,255,255,.1);background:transparent') + ';flex:1;transition:all .15s;';
        });
    });

    // Finalizar
    function finish() {
        var makePublic = document.querySelector('input[name="ctd-visibility"]:checked').value === 'public';
        function done() { $('#createTournamentDivisionsModal').modal('hide'); window.location.reload(); }
        if (makePublic && currentTournamentId) {
            fetch('/api/tournaments/' + currentTournamentId + '/toggle-public', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }
            }).finally(done);
        } else { done(); }
    }

    document.getElementById('ctd-continue-btn').addEventListener('click', finish);
    document.getElementById('ctd-skip-link').addEventListener('click', function (e) { e.preventDefault(); finish(); });
})();
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
    border-color: var(--color-primary);
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
.video-card:hover { border-color: var(--color-primary); transform: translateY(-2px); }
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
.btn-rugby        { background:var(--color-primary); border-color:var(--color-primary); color:#fff; }
.btn-rugby:hover  { background:var(--color-primary-hover); border-color:var(--color-primary-hover); color:#fff; }
.btn-rugby-light  { background:#b8860b; border-color:#b8860b; color:#fff; }
.btn-rugby-light:hover { background:var(--color-accent); color:#fff; }
.btn-delete       { background:transparent; border-color:#2d4a4e; color:#6a9a9e; }
.btn-delete:hover { background:#1a2e30; border-color:var(--color-primary); color:#b8860b; }
.modal-header-rugby { background:var(--color-primary); }
.card-rugby       { border-color:var(--color-primary); }
.card-rugby .card-header { background:var(--color-primary); }
.badge-rugby      { background:var(--color-primary); color:#fff; font-size:.8em; }
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
    border-color: var(--color-primary);
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
.match-edit-btn:hover { background: #b8860b; color: #fff; text-decoration: none; }
/* Botón eliminar flotante — apilado bajo el botón editar (derecha) */
.match-delete-btn {
    position: absolute;
    top: 42px; right: 8px;
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
    color: #b8860b;
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
    color: #b8860b; background: #0a3038;
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

    // Eliminar video desde modal de confirmación
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-video');
        if (!btn) return;

        const url     = btn.dataset.url;
        const videoId = btn.dataset.videoId;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Eliminando...';

        fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'No se pudo eliminar.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash mr-1"></i> Eliminar';
            }
        })
        .catch(() => {
            alert('Error de red. Intente nuevamente.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash mr-1"></i> Eliminar';
        });
    });

    // Eliminar carpeta (contexto derecho)
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

@push('scripts')
<script>
function toggleDivFolder(id) {
    var content = document.getElementById(id);
    var icon    = document.getElementById(id + '-icon');
    var chevron = document.getElementById(id + '-chevron');
    var isOpen  = content.style.display !== 'none';

    content.style.display   = isOpen ? 'none' : 'block';
    icon.className           = isOpen ? 'fas fa-folder' : 'fas fa-folder-open';
    icon.style.color         = isOpen ? '#00B7B5' : '#00d4d1';
    chevron.style.transform  = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
}
</script>
@endpush
