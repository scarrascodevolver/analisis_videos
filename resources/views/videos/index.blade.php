@extends('layouts.app')

@section('page_title', 'Videos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Videos</li>
@endsection

@section('main_content')
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter"></i> Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('videos.index') }}" class="row">
                        <div class="col-md-3 mb-2">
                            <input type="text" name="search" class="form-control" placeholder="Buscar por título..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <select name="rugby_situation" class="form-control">
                                <option value="">Todas las situaciones</option>
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
                        <div class="col-md-2 mb-2">
                            <select name="category" class="form-control">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="team" class="form-control">
                                <option value="">Todos los equipos</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" {{ request('team') == $team->id ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-rugby mr-2">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('videos.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                                <div class="col-md-4 mb-4">
                                    <div class="card video-card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $video->title }}</h6>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    {{ $video->analyzedTeam->name }}
                                                    @if($video->rivalTeam)
                                                        vs {{ $video->rivalTeam->name }}
                                                    @endif
                                                </small>
                                            </p>
                                            <div class="mb-2">
                                                <span class="badge badge-primary">{{ $video->category->name }}</span>
                                                @if($video->rugbySituation)
                                                    <span class="badge ml-1" style="background-color: {{ $video->rugbySituation->color }}; color: white;">
                                                        {{ $video->rugbySituation->name }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> {{ $video->match_date->format('d/m/Y') }}
                                                </small>
                                            </p>
                                        </div>
                                        <div class="card-footer">
                                            <a href="{{ route('videos.show', $video) }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-play"></i> Ver Video
                                            </a>
                                            @if(auth()->user()->role === 'analista' && $video->uploaded_by === auth()->id())
                                                <a href="{{ route('videos.edit', $video) }}" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if(method_exists($videos, 'links'))
                            <div class="d-flex justify-content-center">
                                {{ $videos->links() }}
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
@endsection