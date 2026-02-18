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
    @else
        <li class="breadcrumb-item active">Videos</li>
    @endif
@endsection

@section('main_content')

{{-- ═══════════════════════════════════════════════════════════
     NIVEL 1 — Carpetas de equipos (analistas/entrenadores)
═══════════════════════════════════════════════════════════ --}}
@if($view === 'teams')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-muted"><i class="fas fa-folder-open mr-2"></i>Mis equipos</h5>
    <a href="{{ route('videos.create') }}" class="btn btn-rugby btn-sm">
        <i class="fas fa-plus mr-1"></i> Subir Video
    </a>
</div>

@if($teams->isEmpty())
    <div class="card card-rugby">
        <div class="card-body text-center py-5">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aún no hay videos subidos</h5>
            <a href="{{ route('videos.create') }}" class="btn btn-rugby mt-2">
                <i class="fas fa-plus mr-1"></i> Subir primer video
            </a>
        </div>
    </div>
@else
    <div class="folder-grid">
        @foreach($teams as $team)
            <a href="{{ route('videos.index', ['team' => $team->analyzed_team_name]) }}"
               class="folder-card text-decoration-none">
                <div class="folder-icon-wrap">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="folder-name">{{ $team->analyzed_team_name }}</div>
                <div class="folder-meta">
                    {{ $team->videos_count }} {{ $team->videos_count == 1 ? 'partido' : 'partidos' }}
                    &middot;
                    {{ \Carbon\Carbon::parse($team->last_match)->format('M Y') }}
                </div>
            </a>
        @endforeach
    </div>
@endif


{{-- ═══════════════════════════════════════════════════════════
     NIVEL 2 — Carpetas de torneos dentro de un equipo
═══════════════════════════════════════════════════════════ --}}
@elseif($view === 'tournaments')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-muted">
        <i class="fas fa-folder-open mr-2"></i>{{ $team }}
    </h5>
    <a href="{{ route('videos.create') }}" class="btn btn-rugby btn-sm">
        <i class="fas fa-plus mr-1"></i> Subir Video
    </a>
</div>

@if($tournaments->isEmpty())
    <div class="card card-rugby">
        <div class="card-body text-center py-4">
            <p class="text-muted mb-0">No hay partidos para este equipo.</p>
        </div>
    </div>
@else
    <div class="folder-grid">
        @foreach($tournaments as $t)
            @php
                $tParam = $t->tournament_id ?? 'none';
            @endphp
            <a href="{{ route('videos.index', ['team' => $team, 'tournament' => $tParam]) }}"
               class="folder-card text-decoration-none">
                <div class="folder-icon-wrap {{ $t->tournament_id ? '' : 'folder-no-tournament' }}">
                    <i class="fas fa-{{ $t->tournament_id ? 'trophy' : 'folder' }}"></i>
                </div>
                <div class="folder-name">{{ $t->tournament_name }}</div>
                <div class="folder-meta">
                    {{ $t->videos_count }} {{ $t->videos_count == 1 ? 'partido' : 'partidos' }}
                    &middot;
                    {{ \Carbon\Carbon::parse($t->last_match)->format('M Y') }}
                </div>
            </a>
        @endforeach
    </div>
@endif


{{-- ═══════════════════════════════════════════════════════════
     NIVEL 3 — Lista de partidos dentro de equipo + torneo
═══════════════════════════════════════════════════════════ --}}
@elseif($view === 'matches')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-muted">
        <i class="fas fa-video mr-2"></i>
        {{ $tournament?->name ?? 'Sin torneo' }}
    </h5>
    <a href="{{ route('videos.create') }}" class="btn btn-rugby btn-sm">
        <i class="fas fa-plus mr-1"></i> Subir Video
    </a>
</div>

@if($videos->isEmpty())
    <div class="card card-rugby">
        <div class="card-body text-center py-4">
            <p class="text-muted mb-0">No hay partidos en este torneo.</p>
        </div>
    </div>
@else
    <div class="row">
        @foreach($videos as $video)
            @php
                $rawSize = $video->compressed_file_size ?? $video->file_size ?? 0;
                $sizeLabel = '';
                if ($rawSize > 0) {
                    $gb = $rawSize / 1073741824;
                    $sizeLabel = $gb >= 1
                        ? number_format($gb, 1) . ' GB'
                        : number_format($rawSize / 1048576, 0) . ' MB';
                }
            @endphp
            <div class="col-lg-2 col-md-3 col-sm-4 mb-3" id="video-card-{{ $video->id }}">
                <div class="card video-card h-100">
                    <div class="video-thumbnail-container"
                         onclick="window.location.href='{{ route('videos.show', $video) }}'">
                        @if($video->bunny_thumbnail)
                            <img src="{{ $video->bunny_thumbnail }}" alt="Thumbnail"
                                 class="w-100 h-100" style="object-fit:cover">
                        @else
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-dark">
                                <i class="fas fa-film fa-2x text-muted"></i>
                            </div>
                        @endif
                        {{-- Status badge --}}
                        @if($video->bunny_status && $video->bunny_status !== 'ready')
                            <span class="status-badge">
                                <i class="fas fa-spinner fa-spin mr-1"></i>
                                {{ $video->bunny_status === 'processing' ? 'Procesando' : 'Pendiente' }}
                            </span>
                        @endif
                        {{-- XML badge --}}
                        @if($video->clips_count > 0)
                            <span class="xml-badge" title="{{ $video->clips_count }} clips importados">
                                <i class="fas fa-list-ul mr-1"></i>XML
                            </span>
                        @endif
                    </div>
                    <div class="card-body py-2 px-3">
                        <h6 class="card-title mb-1 video-title" title="{{ $video->title }}">
                            {{ $video->title }}
                        </h6>
                        <p class="card-text mb-1">
                            <small class="text-muted">
                                {{ $video->analyzed_team_name }}
                                @if($video->rival_name) vs {{ $video->rival_name }} @endif
                            </small>
                        </p>
                        <div class="mb-1">
                            <span class="badge badge-rugby badge-sm">{{ $video->category->name ?? 'Sin categoría' }}</span>
                            @if($video->division && $video->category?->name === 'Adultas')
                                <span class="badge badge-secondary badge-sm ml-1">{{ ucfirst($video->division) }}</span>
                            @endif
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-calendar mr-1"></i>{{ $video->match_date->format('d/m/Y') }}
                            @if($sizeLabel)
                                &nbsp;·&nbsp;<i class="fas fa-hdd mr-1"></i>{{ $sizeLabel }}
                            @endif
                        </small>
                    </div>
                    <div class="card-footer py-2 px-3">
                        <a href="{{ route('videos.show', $video) }}" class="btn btn-rugby btn-sm">
                            <i class="fas fa-play mr-1"></i> Ver
                        </a>
                        @if(in_array(auth()->user()->role, ['analista', 'entrenador']) || auth()->id() === $video->uploaded_by)
                            <a href="{{ route('videos.edit', $video) }}" class="btn btn-rugby-light btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-delete btn-sm"
                                    data-toggle="modal" data-target="#deleteModal-{{ $video->id }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modales de eliminación --}}
    @foreach($videos as $video)
        <div class="modal fade" id="deleteModal-{{ $video->id }}" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header modal-header-rugby text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-trash mr-2"></i>Confirmar eliminación
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
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
    @endforeach

    <div class="d-flex justify-content-center mt-3">
        {{ $videos->appends(request()->query())->links('custom.pagination') }}
    </div>
@endif


{{-- ═══════════════════════════════════════════════════════════
     VISTA JUGADOR — Lista plana de videos asignados
═══════════════════════════════════════════════════════════ --}}
@else

    {{-- Filtros para jugadores --}}
    <div class="card card-rugby mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('videos.index') }}" class="d-flex flex-wrap" style="gap:8px">
                <input type="text" name="search" class="form-control form-control-sm"
                       style="max-width:220px" placeholder="Buscar..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-rugby btn-sm">
                    <i class="fas fa-search mr-1"></i> Buscar
                </button>
                @if(request('search'))
                    <a href="{{ route('videos.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times mr-1"></i> Limpiar
                    </a>
                @endif
            </form>
        </div>
    </div>

    @if($videos->isEmpty())
        <div class="card card-rugby">
            <div class="card-body text-center py-5">
                <i class="fas fa-video fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No tenés videos asignados aún</h5>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($videos as $video)
                <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                    <div class="card video-card h-100">
                        <div class="video-thumbnail-container"
                             onclick="window.location.href='{{ route('videos.show', $video) }}'">
                            @if($video->bunny_thumbnail)
                                <img src="{{ $video->bunny_thumbnail }}" alt="Thumbnail"
                                     class="w-100 h-100" style="object-fit:cover">
                            @else
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-dark">
                                    <i class="fas fa-film fa-2x text-muted"></i>
                                </div>
                            @endif
                        </div>
                        <div class="card-body py-2 px-3">
                            <h6 class="card-title mb-1 video-title">{{ $video->title }}</h6>
                            <small class="text-muted">
                                <i class="fas fa-calendar mr-1"></i>{{ $video->match_date->format('d/m/Y') }}
                            </small>
                        </div>
                        <div class="card-footer py-2 px-3">
                            <a href="{{ route('videos.show', $video) }}" class="btn btn-rugby btn-sm">
                                <i class="fas fa-play mr-1"></i> Ver
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $videos->links('custom.pagination') }}
        </div>
    @endif

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

/* ─── Tarjetas de video ────────────────────────────────── */
.video-thumbnail-container {
    height: 70px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    background: #111;
}
.status-badge {
    position: absolute;
    bottom: 5px;
    left: 5px;
    background: rgba(0,0,0,.7);
    color: #fff;
    font-size: .68rem;
    padding: 2px 7px;
    border-radius: 10px;
}
.xml-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(0, 183, 181, .85);
    color: #fff;
    font-size: .65rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 6px;
    letter-spacing: .03em;
}
.video-card {
    background: #1a1a1a;
    border: 1px solid #2d2d2d;
    transition: border-color .2s, transform .15s;
}
.video-card:hover { border-color: #005461; transform: translateY(-3px); }
.video-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: .78rem;
    line-height: 1.3;
}

/* ─── Colores rugby ────────────────────────────────────── */
.btn-rugby        { background:#005461; border-color:#005461; color:#fff; }
.btn-rugby:hover  { background:#003d4a; border-color:#003d4a; color:#fff; }
.btn-rugby-light  { background:#00B7B5; border-color:#00B7B5; color:#fff; }
.btn-rugby-light:hover { background:#009e9c; color:#fff; }
.btn-delete       { background:transparent; border-color:#2d4a4e; color:#6a9a9e; }
.btn-delete:hover { background:#1a2e30; border-color:#005461; color:#00B7B5; }
.modal-header-rugby { background:#005461; }
.card-rugby       { border-color:#005461; }
.card-rugby .card-header { background:#005461; }
.badge-rugby      { background:#005461; color:#fff; font-size:.8em; }
.badge-sm         { font-size:.75em; padding:.2rem .5rem; }
</style>
@endpush
