@extends('layouts.app')

@section('page_title', 'Videos del Equipo')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Videos del Equipo</li>
@endsection

@section('main_content')
    <!-- Filters (only for non-players) -->
    @if(auth()->user()->role !== 'jugador')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter"></i> Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('videos.index') }}" class="row" id="filter-form">
                        <div class="col-md-3 mb-2">
                            <input type="text" name="search" id="search-input" class="form-control" placeholder="Buscar por título..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <select name="rugby_situation" id="situation-select" class="form-control">
                                <option value="">Situación</option>
                                @foreach($rugbySituations as $categoryName => $situations)
                                    <optgroup label="{{ $categoryName }}">
                                        @foreach($situations as $situation)
                                            <option value="{{ $situation->id }}" {{ request('rugby_situation') == $situation->id ? 'selected' : '' }}>
                                                {{ $situation->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        @if(auth()->user()->role === 'analista')
                        <div class="col-md-2 mb-2">
                            <select name="category" id="category-select" class="form-control">
                                <option value="">Categoría</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="division" id="division-select" class="form-control">
                                <option value="">División</option>
                                <option value="primera" {{ request('division') == 'primera' ? 'selected' : '' }}>Primera</option>
                                <option value="intermedia" {{ request('division') == 'intermedia' ? 'selected' : '' }}>Intermedia</option>
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2 mb-2">
                            <select name="team" id="team-select" class="form-control">
                                <option value="">Equipo</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" {{ request('team') == $team->id ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <a href="{{ route('videos.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Videos List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-video"></i>
                        Lista de Videos
                    </h3>
                    <div class="card-tools">
                        @if(auth()->user()->role === 'analista')
                            <a href="{{ route('videos.create') }}" class="btn btn-rugby">
                                <i class="fas fa-plus"></i> Subir Video
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($videos) && $videos->count() > 0)
                        <div class="row">
                            @foreach($videos as $video)
                                <div class="col-lg-4 col-md-6 col-sm-12 mb-2">
                                    <div class="card video-card h-100">
                                        <!-- Video Thumbnail -->
                                        <div class="card-img-top video-thumbnail-container"
                                             style="height: 120px; overflow: hidden; position: relative; cursor: pointer;"
                                             onclick="window.location.href='{{ route('videos.show', $video) }}'">

                                            <video class="w-100 h-100"
                                                   style="object-fit: cover;"
                                                   preload="metadata"
                                                   muted>
                                                <source src="{{ route('videos.stream', $video) }}#t=5" type="video/mp4">
                                            </video>
                                        </div>
                                        <div class="card-body py-1 px-2">
                                            <h6 class="card-title mb-1 video-title">{{ $video->title }}</h6>
                                            <p class="card-text mb-1">
                                                <small class="text-muted">
                                                    {{ $video->analyzedTeam->name }}
                                                    @if($video->rivalTeam)
                                                        vs {{ $video->rivalTeam->name }}
                                                    @endif
                                                </small>
                                            </p>
                                            <div class="mb-1">
                                                <span class="badge badge-rugby badge-sm">{{ $video->category->name }}</span>
                                                @if($video->division && $video->category->name === 'Adultas')
                                                    <span class="badge badge-secondary badge-sm ml-1">
                                                        {{ ucfirst($video->division) }}
                                                    </span>
                                                @endif
                                                @if($video->rugbySituation)
                                                    <span class="badge badge-rugby-light badge-sm ml-1">
                                                        {{ $video->rugbySituation->name }}
                                                    </span>
                                                @endif

                                                {{-- Badge de Visibilidad --}}
                                                @if($video->visibility_type && $video->visibility_type !== 'public')
                                                    @if($video->visibility_type === 'forwards')
                                                        <span class="badge badge-secondary badge-sm ml-1" title="Solo visible para Forwards">
                                                            <i class="fas fa-shield-alt"></i> Forwards
                                                        </span>
                                                    @elseif($video->visibility_type === 'backs')
                                                        <span class="badge badge-info badge-sm ml-1" title="Solo visible para Backs">
                                                            <i class="fas fa-running"></i> Backs
                                                        </span>
                                                    @elseif($video->visibility_type === 'specific')
                                                        <span class="badge badge-dark badge-sm ml-1" title="Asignado a jugadores específicos">
                                                            <i class="fas fa-user-check"></i> Específico
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>
                                            <p class="card-text mb-1">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> {{ $video->match_date->format('d/m/Y') }}
                                                </small>
                                            </p>
                                        </div>
                                        <div class="card-footer">
                                            <a href="{{ route('videos.show', $video) }}" class="btn btn-rugby btn-sm">
                                                <i class="fas fa-play"></i> Ver Video
                                            </a>
                                            @if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador' || auth()->id() === $video->uploaded_by)
                                                <a href="{{ route('videos.edit', $video) }}" class="btn btn-rugby-light btn-sm">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            @endif
                                            @if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador')
                                                <button type="button" class="btn btn-rugby-dark btn-sm" data-toggle="modal" data-target="#deleteModal-{{ $video->id }}">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Modales de Confirmación para Eliminar Videos -->
                        @if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador')
                            @foreach($videos as $video)
                                <div class="modal fade" id="deleteModal-{{ $video->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel-{{ $video->id }}" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="deleteModalLabel-{{ $video->id }}">
                                                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                                                </div>
                                                <h5 class="text-center mb-3">¿Estás seguro de eliminar este video?</h5>
                                                <div class="alert alert-warning">
                                                    <strong>Video:</strong> {{ $video->title }}<br>
                                                    <strong>Archivo:</strong> {{ $video->file_name }}<br>
                                                    <strong>Tamaño:</strong> {{ number_format($video->file_size / 1048576, 2) }} MB<br>
                                                    <strong>Fecha:</strong> {{ $video->match_date->format('d/m/Y') }}
                                                </div>
                                                <p class="text-danger text-center">
                                                    <strong>⚠️ Esta acción no se puede deshacer.</strong><br>
                                                    Se eliminará el video, todos sus comentarios y asignaciones.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-rugby-outline" data-dismiss="modal">
                                                    <i class="fas fa-times"></i> Cancelar
                                                </button>
                                                <form method="POST" action="{{ route('videos.destroy', $video) }}" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-rugby-dark">
                                                        <i class="fas fa-trash"></i> Eliminar Video
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @if(method_exists($videos, 'links'))
                            <div class="d-flex justify-content-center">
                                {{ $videos->links('custom.pagination') }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-video fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay videos disponibles</h5>
                            @if(auth()->user()->role === 'analista')
                                <p class="text-muted">Comienza subiendo tu primer video de análisis</p>
                                <a href="{{ route('videos.create') }}" class="btn btn-rugby">
                                    <i class="fas fa-plus"></i> Subir Primer Video
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

<style>
/* Rugby badges */
.badge-rugby {
    background: #1e4d2b;
    color: white;
    font-size: 0.875em;
    font-weight: 500;
}

.badge-rugby-light {
    background: #28a745;
    color: white;
    font-size: 0.875em;
    font-weight: 500;
}

.badge-sm {
    font-size: 0.75em;
    padding: 0.25rem 0.5rem;
}

/* Video title overflow fix */
.video-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
    max-height: 2.4em;
    font-size: 0.9rem;
}

/* Rugby button variations */
.btn-rugby-light {
    background: #28a745;
    border: none;
    color: white;
    border-radius: 6px;
    font-weight: 500;
}

.btn-rugby-light:hover {
    background: #218838;
    color: white;
}

.btn-rugby-dark {
    background: #0d2818;
    border: none;
    color: white;
    border-radius: 6px;
    font-weight: 500;
}

.btn-rugby-dark:hover {
    background: #1a4028;
    color: white;
}

.btn-rugby-outline {
    background: transparent;
    border: 2px solid #1e4d2b;
    color: #1e4d2b;
    border-radius: 6px;
    font-weight: 500;
}

.btn-rugby-outline:hover {
    background: #1e4d2b;
    border-color: #1e4d2b;
    color: white;
}

/* Video card improvements */
.video-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.video-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.video-card .card-img-top {
    transition: all 0.3s ease;
}

.video-card .card-img-top:hover {
    transform: scale(1.02);
}

.video-card .card-img-top img {
    transition: opacity 0.3s ease;
}

.video-card .card-img-top:hover img {
    opacity: 0.9;
}

/* Rugby thumbnail placeholder */
.rugby-thumbnail {
    background: #1e4d2b;
    position: relative;
}

.play-button-circle {
    width: 50px;
    height: 50px;
    background: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content-center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.rugby-thumbnail:hover .play-button-circle {
    transform: scale(1.1);
    background: #218838;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtros automáticos
    let filterTimeout;

    function autoFilter() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            document.getElementById('filter-form').submit();
        }, 500);
    }

    // Event listeners para filtros
    document.getElementById('search-input').addEventListener('input', autoFilter);
    document.getElementById('situation-select').addEventListener('change', function() {
        document.getElementById('filter-form').submit();
    });

    const categorySelect = document.getElementById('category-select');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
    }

    const divisionSelect = document.getElementById('division-select');
    if (divisionSelect) {
        divisionSelect.addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
    }

    document.getElementById('team-select').addEventListener('change', function() {
        document.getElementById('filter-form').submit();
    });

});
</script>

@endsection