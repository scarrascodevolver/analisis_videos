@extends('layouts.app')

@section('page_title', 'Subir Video')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}">Videos del Equipo</a></li>
    <li class="breadcrumb-item active">Subir Video</li>
@endsection

@section('main_content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-rugby">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload"></i>
                        Subir Nuevo Video de Análisis
                    </h3>
                </div>
                <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data" id="videoUploadForm">
                    @csrf
                    <div class="card-body">
                        <!-- Video File Upload -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="video_file">
                                        <i class="fas fa-video"></i> Archivo de Video *
                                    </label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('video_file') is-invalid @enderror" 
                                               id="video_file" name="video_file" accept=".mp4,.mov,.avi" required>
                                        <label class="custom-file-label" for="video_file">Seleccionar archivo de video...</label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Formatos soportados: MP4, MOV, AVI. Tamaño máximo: 1GB
                                    </small>
                                    @error('video_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Video Information -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="title">
                                        <i class="fas fa-heading"></i> Título del Video *
                                    </label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title') }}" 
                                           placeholder="Ej: Análisis Scrum Los Troncos vs DOBS" required>
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
                                           id="match_date" name="match_date" value="{{ old('match_date', date('Y-m-d')) }}" required>
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
                                                    {{ old('analyzed_team_id', $ownTeam->id) == $ownTeam->id ? 'selected' : '' }}>
                                                {{ $ownTeam->name }} (Nuestro Equipo)
                                            </option>
                                        @endif
                                        @foreach($rivalTeams as $team)
                                            <option value="{{ $team->id }}" 
                                                    {{ old('analyzed_team_id') == $team->id ? 'selected' : '' }}>
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
                                                    {{ old('rival_team_id') == $team->id ? 'selected' : '' }}>
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
                                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                                    <label for="rugby_situation_id">
                                        <i class="fas fa-football-ball"></i> Situación de Rugby
                                    </label>
                                    <select class="form-control @error('rugby_situation_id') is-invalid @enderror" 
                                            id="rugby_situation_id" name="rugby_situation_id">
                                        <option value="">Sin situación específica</option>
                                        @foreach($rugbySituations as $categoryName => $situations)
                                            <optgroup label="{{ $categoryName }}">
                                                @foreach($situations as $situation)
                                                    <option value="{{ $situation->id }}" 
                                                            data-color="{{ $situation->color }}"
                                                            {{ old('rugby_situation_id') == $situation->id ? 'selected' : '' }}>
                                                        {{ $situation->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('rugby_situation_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Opcional: Especifica la situación de rugby para mejor análisis
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Rugby Situation Preview -->
                        <div class="row" id="rugbySituationPreview" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Situación seleccionada:</strong>
                                    <span id="situationPreviewText"></span>
                                    <span id="situationPreviewBadge" class="badge ml-2"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Match Date -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="match_date">
                                        <i class="fas fa-calendar"></i> Fecha del Partido *
                                    </label>
                                    <input type="date" class="form-control @error('match_date') is-invalid @enderror" 
                                           id="match_date" name="match_date" value="{{ old('match_date') }}" required>
                                    @error('match_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-info-circle"></i> Información de Categorías
                                    </label>
                                    <div class="bg-light p-3 rounded">
                                        @foreach($categories as $category)
                                            <small class="d-block">
                                                <strong>{{ $category->name }}:</strong> {{ $category->description }}
                                            </small>
                                        @endforeach
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
                                      placeholder="Describe el contenido del video, aspectos importantes a analizar, etc.">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">
                                Incluye información relevante sobre el partido, jugadas específicas, objetivos del análisis, etc.
                            </small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Upload Progress -->
                        <div class="form-group" id="uploadProgress" style="display: none;">
                            <label>Progreso de Subida</label>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%">
                                    0%
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route('videos.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="submit" class="btn btn-rugby btn-lg" id="uploadBtn">
                                    <i class="fas fa-upload"></i> Subir Video
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
    // Custom file input
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
        
        // Show file info
        if (this.files && this.files[0]) {
            var file = this.files[0];
            var size = (file.size / (1024 * 1024)).toFixed(2);
            var info = fileName + ' (' + size + ' MB)';
            $(this).siblings('.custom-file-label').html(info);
        }
    });

    // Form validation
    $('#videoUploadForm').on('submit', function(e) {
        var fileInput = $('#video_file')[0];
        if (!fileInput.files || !fileInput.files[0]) {
            e.preventDefault();
            alert('Por favor selecciona un archivo de video');
            return false;
        }

        var file = fileInput.files[0];
        var maxSize = 1024 * 1024 * 1024; // 1GB
        
        if (file.size > maxSize) {
            e.preventDefault();
            alert('El archivo es demasiado grande. El tamaño máximo es 1GB.');
            return false;
        }

        // Show upload progress
        $('#uploadProgress').show();
        $('#uploadBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subiendo...');
    });

    // Rugby situation preview
    $('#rugby_situation_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var situationName = selectedOption.text();
        var situationColor = selectedOption.data('color');
        var categoryName = selectedOption.parent('optgroup').attr('label');
        
        if ($(this).val()) {
            $('#rugbySituationPreview').show();
            $('#situationPreviewText').text(situationName);
            $('#situationPreviewBadge').text(categoryName).css('background-color', situationColor);
        } else {
            $('#rugbySituationPreview').hide();
        }
    });

    // Auto-generate title based on selections
    $('#analyzed_team_id, #rival_team_id, #category_id, #rugby_situation_id').on('change', function() {
        var analyzedTeam = $('#analyzed_team_id option:selected').text();
        var rivalTeam = $('#rival_team_id option:selected').text();
        var category = $('#category_id option:selected').text();
        var rugbySituation = $('#rugby_situation_id option:selected').text();
        
        if (analyzedTeam && analyzedTeam !== 'Seleccionar equipo...') {
            var title = 'Análisis ' + category;
            
            if (rugbySituation && rugbySituation !== 'Sin situación específica') {
                title = rugbySituation + ' - ' + category;
            }
            
            if (rivalTeam && rivalTeam !== 'Sin rival (entrenamiento)') {
                title += ' - ' + analyzedTeam + ' vs ' + rivalTeam;
            } else {
                title += ' - ' + analyzedTeam;
            }
            
            if ($('#title').val() === '' || $('#title').data('auto-generated')) {
                $('#title').val(title).data('auto-generated', true);
            }
        }
    });
});
</script>
@endsection