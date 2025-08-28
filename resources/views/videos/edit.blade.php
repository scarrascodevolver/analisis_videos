@extends('layouts.app')

@section('page_title', 'Editar Video')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}">Videos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('videos.show', $video) }}">{{ $video->title }}</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('main_content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-rugby">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i>
                        Editar Video: {{ $video->title }}
                    </h3>
                </div>
                <form action="{{ route('videos.update', $video) }}" method="POST" id="videoEditForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <!-- Video Information -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="title">
                                        <i class="fas fa-heading"></i> Título del Video *
                                    </label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $video->title) }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="match_date">
                                        <i class="fas fa-calendar"></i> Fecha del Partido *
                                    </label>
                                    <input type="date" class="form-control @error('match_date') is-invalid @enderror" 
                                           id="match_date" name="match_date" 
                                           value="{{ old('match_date', $video->match_date->format('Y-m-d')) }}" required>
                                    @error('match_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Team Selection -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="analyzed_team_id">
                                        <i class="fas fa-users"></i> Equipo Analizado *
                                    </label>
                                    <select class="form-control @error('analyzed_team_id') is-invalid @enderror" 
                                            id="analyzed_team_id" name="analyzed_team_id" required>
                                        <option value="">Seleccionar equipo...</option>
                                        @if($ownTeam)
                                            <option value="{{ $ownTeam->id }}" 
                                                    {{ old('analyzed_team_id', $video->analyzed_team_id) == $ownTeam->id ? 'selected' : '' }}>
                                                {{ $ownTeam->name }} (Nuestro Equipo)
                                            </option>
                                        @endif
                                        @foreach($rivalTeams as $team)
                                            <option value="{{ $team->id }}" 
                                                    {{ old('analyzed_team_id', $video->analyzed_team_id) == $team->id ? 'selected' : '' }}>
                                                {{ $team->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('analyzed_team_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rival_team_id">
                                        <i class="fas fa-shield-alt"></i> Equipo Rival
                                    </label>
                                    <select class="form-control @error('rival_team_id') is-invalid @enderror" 
                                            id="rival_team_id" name="rival_team_id">
                                        <option value="">Sin rival (entrenamiento)</option>
                                        @foreach($rivalTeams as $team)
                                            <option value="{{ $team->id }}" 
                                                    {{ old('rival_team_id', $video->rival_team_id) == $team->id ? 'selected' : '' }}>
                                                {{ $team->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('rival_team_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Category Selection -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_id">
                                        <i class="fas fa-tags"></i> Categoría *
                                    </label>
                                    <select class="form-control @error('category_id') is-invalid @enderror" 
                                            id="category_id" name="category_id" required>
                                        <option value="">Seleccionar categoría...</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                    {{ old('category_id', $video->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-info-circle"></i> Información del Video
                                    </label>
                                    <div class="bg-light p-3 rounded">
                                        <small class="d-block">
                                            <strong>Archivo:</strong> {{ $video->file_name }}
                                        </small>
                                        <small class="d-block">
                                            <strong>Tamaño:</strong> {{ number_format($video->file_size / 1024 / 1024, 2) }} MB
                                        </small>
                                        <small class="d-block">
                                            <strong>Tipo:</strong> {{ $video->mime_type }}
                                        </small>
                                        <small class="d-block">
                                            <strong>Subido:</strong> {{ $video->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="description">
                                <i class="fas fa-align-left"></i> Descripción del Video
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4" 
                                      placeholder="Describe el contenido del video, aspectos importantes a analizar, etc.">{{ old('description', $video->description) }}</textarea>
                            <small class="form-text text-muted">
                                Incluye información relevante sobre el partido, jugadas específicas, objetivos del análisis, etc.
                            </small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route('videos.show', $video) }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="submit" class="btn btn-rugby btn-lg">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Form validation
    $('#videoEditForm').on('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        const requiredFields = ['title', 'analyzed_team_id', 'category_id', 'match_date'];
        requiredFields.forEach(function(fieldName) {
            const field = $(`[name="${fieldName}"]`);
            if (!field.val() || field.val().trim() === '') {
                field.addClass('is-invalid');
                isValid = false;
            } else {
                field.removeClass('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Por favor completa todos los campos requeridos');
            return false;
        }
    });

    // Remove validation errors on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
});
</script>
@endsection