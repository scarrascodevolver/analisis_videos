@extends('layouts.app')

@section('page_title', 'Detalle de Asignación')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('analyst.assignments.index') }}">Asignaciones</a></li>
    <li class="breadcrumb-item active">Detalle</li>
@endsection

@section('main_content')
    <div class="row">
        <!-- Assignment Details -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tasks"></i>
                        Detalle de Asignación
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ 
                            $assignment->status === 'completado' ? 'success' : 
                            ($assignment->status === 'en_progreso' ? 'primary' : 
                            ($assignment->status === 'asignado' ? 'warning' : 'secondary')) 
                        }} badge-lg">
                            {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Video Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-video text-primary"></i> Video Asignado</h5>
                            <div class="bg-light p-3 rounded">
                                <h6><strong>{{ $assignment->video->title }}</strong></h6>
                                <p class="mb-1">
                                    <strong>Equipos:</strong> {{ $assignment->video->analyzedTeam->name }}
                                    @if($assignment->video->rivalTeam)
                                        vs {{ $assignment->video->rivalTeam->name }}
                                    @endif
                                </p>
                                <p class="mb-1"><strong>Categoría:</strong> {{ $assignment->video->category->name }}</p>
                                <p class="mb-0"><strong>Fecha del partido:</strong> {{ $assignment->video->match_date->format('d/m/Y') }}</p>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('videos.show', $assignment->video) }}" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Ver Video
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-users text-info"></i> Participantes</h5>
                            <div class="bg-light p-3 rounded">
                                <div class="mb-2">
                                    <strong>Jugador:</strong>
                                    <div class="d-flex align-items-center mt-1">
                                        <i class="fas fa-user text-info mr-2"></i>
                                        {{ $assignment->player->name }}
                                        @if($assignment->player->profile)
                                            <small class="text-muted ml-2">({{ $assignment->player->profile->position ?? 'Sin posición' }})</small>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <strong>Analista:</strong>
                                    <div class="d-flex align-items-center mt-1">
                                        <i class="fas fa-user-tie text-success mr-2"></i>
                                        {{ $assignment->analyst->name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment Details -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5><i class="fas fa-info-circle text-warning"></i> Detalles de la Asignación</h5>
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <td><strong>Prioridad:</strong></td>
                                            <td>
                                                <span class="badge badge-{{ 
                                                    $assignment->priority === 'critica' ? 'danger' : 
                                                    ($assignment->priority === 'alta' ? 'warning' : 
                                                    ($assignment->priority === 'media' ? 'info' : 'secondary')) 
                                                }}">
                                                    {{ ucfirst($assignment->priority) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Fecha de asignación:</strong></td>
                                            <td>{{ $assignment->assigned_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Fecha límite:</strong></td>
                                            <td class="{{ $assignment->due_date->isPast() && $assignment->status !== 'completado' ? 'text-danger' : '' }}">
                                                {{ $assignment->due_date->format('d/m/Y') }}
                                                @if($assignment->due_date->isPast() && $assignment->status !== 'completado')
                                                    <i class="fas fa-exclamation-triangle text-danger ml-1"></i>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($assignment->accepted_at)
                                            <tr>
                                                <td><strong>Aceptada el:</strong></td>
                                                <td>{{ $assignment->accepted_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        @endif
                                        @if($assignment->completed_at)
                                            <tr>
                                                <td><strong>Completada el:</strong></td>
                                                <td>{{ $assignment->completed_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Focus Areas -->
                    @if($assignment->focus_areas)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5><i class="fas fa-bullseye text-danger"></i> Áreas de Enfoque</h5>
                                <div class="d-flex flex-wrap">
                                    @foreach(json_decode($assignment->focus_areas) as $area)
                                        <span class="badge badge-outline-{{ 
                                            $area === 'tecnico' ? 'info' : 
                                            ($area === 'tactico' ? 'warning' : 
                                            ($area === 'fisico' ? 'success' : 
                                            ($area === 'mental' ? 'purple' : 'warning'))) 
                                        }} mr-2 mb-2">
                                            <i class="fas fa-{{ 
                                                $area === 'tecnico' ? 'cogs' : 
                                                ($area === 'tactico' ? 'chess' : 
                                                ($area === 'fisico' ? 'dumbbell' : 
                                                ($area === 'mental' ? 'brain' : 'crown'))) 
                                            }}"></i>
                                            {{ ucfirst($area) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Instructions -->
                    @if($assignment->instructions)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5><i class="fas fa-clipboard-list text-secondary"></i> Instrucciones del Analista</h5>
                                <div class="bg-light p-3 rounded">
                                    {{ $assignment->instructions }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Player Analysis (if completed) -->
                    @if($assignment->status === 'completado' && $assignment->player_notes)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5><i class="fas fa-user-edit text-success"></i> Análisis del Jugador</h5>
                                <div class="bg-success-light p-3 rounded">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6>Notas y Observaciones:</h6>
                                            <p>{{ $assignment->player_notes }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>Autoevaluación:</h6>
                                            <div class="text-center">
                                                <h3 class="text-success">{{ $assignment->self_evaluation }}/10</h3>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: {{ ($assignment->self_evaluation / 10) * 100 }}%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($assignment->areas_identified)
                                        <div class="mt-3">
                                            <h6>Áreas Identificadas para Mejora:</h6>
                                            <div class="d-flex flex-wrap">
                                                @foreach(json_decode($assignment->areas_identified) as $area)
                                                    <span class="badge badge-success mr-2">{{ ucfirst($area) }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions Panel -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-cogs"></i>
                        Acciones
                    </h5>
                </div>
                <div class="card-body">
                    @if(auth()->user()->role === 'analista')
                        @if($assignment->status !== 'completado')
                            <a href="{{ route('analyst.assignments.edit', $assignment) }}" 
                               class="btn btn-warning btn-block mb-2">
                                <i class="fas fa-edit"></i> Editar Asignación
                            </a>
                        @endif
                        
                        @if($assignment->status !== 'completado')
                            <form method="POST" action="{{ route('analyst.assignments.markCompleted', $assignment) }}" 
                                  onsubmit="return confirm('¿Marcar esta asignación como completada?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success btn-block mb-2">
                                    <i class="fas fa-check"></i> Marcar Completada
                                </button>
                            </form>
                        @endif
                    @endif

                    @if(auth()->id() === $assignment->player_id)
                        @if($assignment->status === 'asignado')
                            <form method="POST" action="{{ route('analyst.assignments.playerAccept', $assignment) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-block mb-2">
                                    <i class="fas fa-check"></i> Aceptar Asignación
                                </button>
                            </form>
                        @elseif($assignment->status === 'en_progreso')
                            <button type="button" class="btn btn-success btn-block mb-2" data-toggle="modal" data-target="#submitAnalysisModal">
                                <i class="fas fa-upload"></i> Enviar Análisis
                            </button>
                        @endif
                    @endif

                    <a href="{{ route('videos.show', $assignment->video) }}" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-play"></i> Ver Video
                    </a>

                    <a href="{{ route('analyst.assignments.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left"></i> Volver a Lista
                    </a>
                </div>
            </div>

            <!-- Progress Card -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-chart-line"></i>
                        Progreso
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $progress = 0;
                        if ($assignment->status === 'asignado') $progress = 25;
                        elseif ($assignment->status === 'en_progreso') $progress = 75;
                        elseif ($assignment->status === 'completado') $progress = 100;
                    @endphp
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar bg-{{ $progress === 100 ? 'success' : 'primary' }}" 
                             role="progressbar" style="width: {{ $progress }}%">
                            {{ $progress }}%
                        </div>
                    </div>
                    
                    <div class="timeline">
                        <div class="timeline-item {{ $assignment->assigned_at ? 'completed' : '' }}">
                            <i class="fas fa-plus bg-primary"></i>
                            <div class="timeline-content">
                                <h6>Asignado</h6>
                                @if($assignment->assigned_at)
                                    <small>{{ $assignment->assigned_at->format('d/m/Y H:i') }}</small>
                                @endif
                            </div>
                        </div>
                        
                        <div class="timeline-item {{ $assignment->accepted_at ? 'completed' : '' }}">
                            <i class="fas fa-check bg-info"></i>
                            <div class="timeline-content">
                                <h6>Aceptado</h6>
                                @if($assignment->accepted_at)
                                    <small>{{ $assignment->accepted_at->format('d/m/Y H:i') }}</small>
                                @endif
                            </div>
                        </div>
                        
                        <div class="timeline-item {{ $assignment->completed_at ? 'completed' : '' }}">
                            <i class="fas fa-flag bg-success"></i>
                            <div class="timeline-content">
                                <h6>Completado</h6>
                                @if($assignment->completed_at)
                                    <small>{{ $assignment->completed_at->format('d/m/Y H:i') }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Analysis Modal -->
    @if(auth()->id() === $assignment->player_id && $assignment->status === 'en_progreso')
        <div class="modal fade" id="submitAnalysisModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('analyst.assignments.playerSubmit', $assignment) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Enviar Análisis Completado</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="player_notes">Notas y Observaciones *</label>
                                <textarea class="form-control" id="player_notes" name="player_notes" 
                                          rows="6" required minlength="50"
                                          placeholder="Describe tu análisis del video, observaciones clave, aspectos que identificaste para mejorar..."></textarea>
                                <small class="form-text text-muted">Mínimo 50 caracteres</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="self_evaluation">Autoevaluación (1-10) *</label>
                                <input type="range" class="form-control-range" id="self_evaluation" 
                                       name="self_evaluation" min="1" max="10" value="5">
                                <div class="d-flex justify-content-between">
                                    <small>1 - Muy bajo</small>
                                    <span id="evaluationValue" class="badge badge-primary">5</span>
                                    <small>10 - Excelente</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Áreas Identificadas para Mejora *</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_identified[]" 
                                                   value="tecnico" id="area_tecnico">
                                            <label class="form-check-label" for="area_tecnico">Técnico</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_identified[]" 
                                                   value="tactico" id="area_tactico">
                                            <label class="form-check-label" for="area_tactico">Táctico</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_identified[]" 
                                                   value="fisico" id="area_fisico">
                                            <label class="form-check-label" for="area_fisico">Físico</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_identified[]" 
                                                   value="mental" id="area_mental">
                                            <label class="form-check-label" for="area_mental">Mental</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_identified[]" 
                                                   value="liderazgo" id="area_liderazgo">
                                            <label class="form-check-label" for="area_liderazgo">Liderazgo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload"></i> Enviar Análisis
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Update evaluation value display
    $('#self_evaluation').on('input', function() {
        $('#evaluationValue').text($(this).val());
    });
    
    // Form validation for analysis submission
    $('form[action*="playerSubmit"]').on('submit', function(e) {
        const checkedAreas = $('input[name="areas_identified[]"]:checked').length;
        if (checkedAreas === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos un área para mejora');
            return false;
        }
        
        const notesLength = $('#player_notes').val().length;
        if (notesLength < 50) {
            e.preventDefault();
            alert('Las notas deben tener al menos 50 caracteres');
            return false;
        }
    });
});
</script>
@endsection

@section('css')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item i {
    position: absolute;
    left: -35px;
    top: 2px;
    width: 25px;
    height: 25px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.timeline-item.completed i {
    background-color: var(--bs-success) !important;
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: -23px;
    top: 30px;
    height: 20px;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item.completed:not(:last-child):before {
    background-color: var(--bs-success);
}

.bg-success-light {
    background-color: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.2);
}
</style>
@endsection