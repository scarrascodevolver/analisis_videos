@extends('layouts.app')

@section('page_title', 'Nueva Asignación de Video')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('analyst.assignments.index') }}">Asignaciones</a></li>
    <li class="breadcrumb-item active">Nueva Asignación</li>
@endsection

@section('main_content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-rugby">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-plus"></i>
                        Asignar Video a Jugador
                    </h3>
                </div>
                <form action="{{ route('analyst.assignments.store') }}" method="POST" id="assignmentForm">
                    @csrf
                    <div class="card-body">
                        <!-- Video Selection -->
                        <div class="form-group">
                            <label for="video_id">
                                <i class="fas fa-video"></i> Video para Analizar *
                            </label>
                            <select class="form-control @error('video_id') is-invalid @enderror" 
                                    id="video_id" name="video_id" required>
                                <option value="">Seleccionar video...</option>
                                @foreach($videos as $video)
                                    <option value="{{ $video->id }}" {{ old('video_id') == $video->id ? 'selected' : '' }}
                                            data-teams="{{ $video->analyzedTeam->name }}{{ $video->rivalTeam ? ' vs ' . $video->rivalTeam->name : '' }}"
                                            data-category="{{ $video->category->name }}"
                                            data-date="{{ $video->match_date->format('d/m/Y') }}">
                                        {{ $video->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('video_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="videoInfo" class="mt-2" style="display: none;">
                                <div class="bg-light p-3 rounded">
                                    <strong>Información del Video:</strong><br>
                                    <span id="videoTeams"></span><br>
                                    <span id="videoCategory"></span><br>
                                    <span id="videoDate"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Player Selection -->
                        <div class="form-group">
                            <label for="player_id">
                                <i class="fas fa-user"></i> Jugador Asignado *
                            </label>
                            <select class="form-control @error('player_id') is-invalid @enderror" 
                                    id="player_id" name="player_id" required>
                                <option value="">Seleccionar jugador...</option>
                                @foreach($players as $player)
                                    <option value="{{ $player->id }}" {{ old('player_id') == $player->id ? 'selected' : '' }}>
                                        {{ $player->name }}
                                        @if($player->profile)
                                            - {{ $player->profile->position ?? 'Sin posición' }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('player_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Assignment Details -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priority">
                                        <i class="fas fa-exclamation-circle"></i> Prioridad *
                                    </label>
                                    <select class="form-control @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority" required>
                                        <option value="media" {{ old('priority', 'media') == 'media' ? 'selected' : '' }}>Media</option>
                                        <option value="baja" {{ old('priority') == 'baja' ? 'selected' : '' }}>Baja</option>
                                        <option value="alta" {{ old('priority') == 'alta' ? 'selected' : '' }}>Alta</option>
                                        <option value="critica" {{ old('priority') == 'critica' ? 'selected' : '' }}>Crítica</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">
                                        <i class="fas fa-calendar"></i> Fecha Límite *
                                    </label>
                                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                           id="due_date" name="due_date" 
                                           value="{{ old('due_date', date('Y-m-d', strtotime('+7 days'))) }}" 
                                           min="{{ date('Y-m-d', strtotime('tomorrow')) }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Focus Areas -->
                        <div class="form-group">
                            <label>
                                <i class="fas fa-bullseye"></i> Áreas de Enfoque
                            </label>
                            <div class="form-check-list">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="focus_areas[]" 
                                                   value="tecnico" id="focus_tecnico"
                                                   {{ in_array('tecnico', old('focus_areas', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="focus_tecnico">
                                                <i class="fas fa-cogs text-info"></i> Técnico
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="focus_areas[]" 
                                                   value="tactico" id="focus_tactico"
                                                   {{ in_array('tactico', old('focus_areas', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="focus_tactico">
                                                <i class="fas fa-chess text-warning"></i> Táctico
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="focus_areas[]" 
                                                   value="fisico" id="focus_fisico"
                                                   {{ in_array('fisico', old('focus_areas', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="focus_fisico">
                                                <i class="fas fa-dumbbell text-success"></i> Físico
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="focus_areas[]" 
                                                   value="mental" id="focus_mental"
                                                   {{ in_array('mental', old('focus_areas', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="focus_mental">
                                                <i class="fas fa-brain text-purple"></i> Mental
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="focus_areas[]" 
                                                   value="liderazgo" id="focus_liderazgo"
                                                   {{ in_array('liderazgo', old('focus_areas', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="focus_liderazgo">
                                                <i class="fas fa-crown text-warning"></i> Liderazgo
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Selecciona las áreas en las que el jugador debe enfocarse durante el análisis
                            </small>
                        </div>

                        <!-- Instructions -->
                        <div class="form-group">
                            <label for="instructions">
                                <i class="fas fa-clipboard-list"></i> Instrucciones Específicas
                            </label>
                            <textarea class="form-control @error('instructions') is-invalid @enderror" 
                                      id="instructions" name="instructions" rows="4" 
                                      placeholder="Proporciona instrucciones específicas sobre qué analizar, momentos clave del video, objetivos del análisis, etc.">{{ old('instructions') }}</textarea>
                            <small class="form-text text-muted">
                                Incluye detalles sobre aspectos específicos a analizar, timestamps importantes, o objetivos de mejora
                            </small>
                            @error('instructions')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route('analyst.assignments.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="submit" class="btn btn-rugby btn-lg">
                                    <i class="fas fa-user-plus"></i> Crear Asignación
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
    // Show video information when selected
    $('#video_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            const teams = selectedOption.data('teams');
            const category = selectedOption.data('category');
            const date = selectedOption.data('date');
            
            $('#videoTeams').html('<strong>Equipos:</strong> ' + teams);
            $('#videoCategory').html('<strong>Categoría:</strong> ' + category);
            $('#videoDate').html('<strong>Fecha:</strong> ' + date);
            $('#videoInfo').show();
        } else {
            $('#videoInfo').hide();
        }
    });

    // Form validation
    $('#assignmentForm').on('submit', function(e) {
        if (!$('#video_id').val()) {
            e.preventDefault();
            alert('Por favor selecciona un video');
            return false;
        }
        
        if (!$('#player_id').val()) {
            e.preventDefault();
            alert('Por favor selecciona un jugador');
            return false;
        }
    });

    // Auto-suggest focus areas based on video category
    $('#video_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const category = selectedOption.data('category');
        
        // Clear all checkboxes first
        $('input[name="focus_areas[]"]').prop('checked', false);
        
        // Auto-select based on category
        if (category && category.includes('Adulta')) {
            $('#focus_tecnico, #focus_tactico').prop('checked', true);
        } else if (category && category.includes('Juveniles')) {
            $('#focus_tecnico, #focus_fisico').prop('checked', true);
        } else if (category && category.includes('Femenino')) {
            $('#focus_tactico, #focus_mental').prop('checked', true);
        }
    });
});
</script>
@endsection