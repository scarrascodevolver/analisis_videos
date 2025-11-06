@extends('layouts.app')

@section('page_title', 'Evaluación de Jugador')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ url('/evaluacion') }}">Evaluaciones</a></li>
    <li class="breadcrumb-item active">Evaluar a {{ $player->name }}</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <!-- Header fijo con jugador seleccionado -->
        <div class="alert alert-success mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-user-check"></i>
                    <strong>Evaluando a:</strong>
                    <span>{{ $player->name }}</span>
                    <span class="badge badge-info ml-2">{{ $player->profile->position ?? 'Sin posición' }}</span>
                    @if($player->profile->player_number)
                        <span class="badge badge-secondary ml-1">#{{ $player->profile->player_number }}</span>
                    @endif
                </div>
                <a href="{{ url('/evaluacion') }}" class="btn btn-sm btn-outline-dark">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Progreso</span>
                <span class="text-rugby font-weight-bold" id="progressText">Paso 1 de 5</span>
            </div>
            <div class="progress" style="height: 10px; background-color: #e0e0e0;">
                <div class="progress-bar" role="progressbar" style="width: 20%; background-color: #7cb342;" id="progressBarFill"></div>
            </div>
        </div>

        <div class="card card-rugby">
            <div class="card-body p-4">

                <!-- STEP 1: Acondicionamiento Físico -->
                <div class="wizard-step" id="step1">
                    <h4 class="text-rugby mb-4">
                        <i class="fas fa-running"></i> Acondicionamiento Físico
                    </h4>
                    <p class="text-muted mb-4">Evalúa la condición física del <strong>0 al 10</strong></p>

                    <div class="evaluation-field" data-field="resistencia">
                        <label class="font-weight-bold mb-3">
                            <i class="fas fa-heart"></i> Resistencia
                        </label>
                        <div class="d-flex align-items-center mb-2">
                            <span class="mr-3 text-muted">0</span>
                            <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                                   min="0" max="10" value="0" data-field="resistencia">
                            <span class="ml-3 text-muted">10</span>
                        </div>
                        <div class="text-center">
                            <span class="badge badge-lg badge-dark text-white evaluation-badge">0</span>
                        </div>
                        <hr class="my-4">
                    </div>

                    <div class="evaluation-field" data-field="velocidad">
                        <label class="font-weight-bold mb-3">
                            <i class="fas fa-bolt"></i> Velocidad
                        </label>
                        <div class="d-flex align-items-center mb-2">
                            <span class="mr-3 text-muted">0</span>
                            <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                                   min="0" max="10" value="0" data-field="velocidad">
                            <span class="ml-3 text-muted">10</span>
                        </div>
                        <div class="text-center">
                            <span class="badge badge-lg badge-dark text-white evaluation-badge">0</span>
                        </div>
                        <hr class="my-4">
                    </div>

                    <div class="evaluation-field" data-field="musculatura">
                        <label class="font-weight-bold mb-3">
                            <i class="fas fa-dumbbell"></i> Musculatura
                        </label>
                        <div class="d-flex align-items-center mb-2">
                            <span class="mr-3 text-muted">0</span>
                            <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                                   min="0" max="10" value="0" data-field="musculatura">
                            <span class="ml-3 text-muted">10</span>
                        </div>
                        <div class="text-center">
                            <span class="badge badge-lg badge-dark text-white evaluation-badge">0</span>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Destrezas Básicas -->
                <div class="wizard-step" id="step2" style="display: none;">
                    <h4 class="text-rugby mb-4">
                        <i class="fas fa-football-ball"></i> Destrezas Básicas
                    </h4>
                    <p class="text-muted mb-4">Evalúa las habilidades técnicas del <strong>0 al 10</strong></p>

                    @php
                        $basicSkills = [
                            ['field' => 'recepcion_pelota', 'label' => 'Recepción de pelota', 'icon' => 'fa-hand-paper'],
                            ['field' => 'pase_dos_lados', 'label' => 'Pase para los dos lados', 'icon' => 'fa-exchange-alt'],
                            ['field' => 'juego_aereo', 'label' => 'Juego aéreo', 'icon' => 'fa-plane'],
                            ['field' => 'tackle', 'label' => 'Tackle', 'icon' => 'fa-shield-alt'],
                            ['field' => 'ruck', 'label' => 'Ruck', 'icon' => 'fa-users'],
                            ['field' => 'duelos', 'label' => 'Duelos', 'icon' => 'fa-fist-raised'],
                            ['field' => 'carreras', 'label' => 'Carreras', 'icon' => 'fa-running'],
                            ['field' => 'conocimiento_plan', 'label' => 'Conocimiento plan de juego', 'icon' => 'fa-book'],
                            ['field' => 'entendimiento_juego', 'label' => 'Entendimiento del juego', 'icon' => 'fa-brain'],
                            ['field' => 'reglamento', 'label' => 'Reglamento', 'icon' => 'fa-balance-scale']
                        ];
                    @endphp

                    @foreach($basicSkills as $skill)
                    <div class="evaluation-field" data-field="{{ $skill['field'] }}">
                        <label class="font-weight-bold mb-2">
                            <i class="fas {{ $skill['icon'] }}"></i> {{ $skill['label'] }}
                        </label>
                        <div class="d-flex align-items-center">
                            <span class="mr-2 text-muted small">0</span>
                            <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                                   min="0" max="10" value="0" data-field="{{ $skill['field'] }}">
                            <span class="ml-2 text-muted small">10</span>
                            <span class="badge badge-dark text-white ml-2 evaluation-badge" style="min-width: 35px;">0</span>
                        </div>
                        @if(!$loop->last)<hr class="my-3">@endif
                    </div>
                    @endforeach
                </div>

                <!-- STEP 3: Destrezas Mentales -->
                <div class="wizard-step" id="step3" style="display: none;">
                    <h4 class="text-rugby mb-4">
                        <i class="fas fa-brain"></i> Destrezas Mentales
                    </h4>
                    <p class="text-muted mb-4">Evalúa las capacidades mentales del <strong>0 al 10</strong></p>

                    @php
                        $mentalSkills = [
                            ['field' => 'autocontrol', 'label' => 'Autocontrol', 'icon' => 'fa-user-check'],
                            ['field' => 'concentracion', 'label' => 'Concentración', 'icon' => 'fa-eye'],
                            ['field' => 'toma_decisiones', 'label' => 'Toma de decisiones', 'icon' => 'fa-lightbulb'],
                            ['field' => 'liderazgo', 'label' => 'Liderazgo', 'icon' => 'fa-crown']
                        ];
                    @endphp

                    @foreach($mentalSkills as $skill)
                    <div class="evaluation-field" data-field="{{ $skill['field'] }}">
                        <label class="font-weight-bold mb-3">
                            <i class="fas {{ $skill['icon'] }}"></i> {{ $skill['label'] }}
                        </label>
                        <div class="d-flex align-items-center mb-2">
                            <span class="mr-3 text-muted">0</span>
                            <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                                   min="0" max="10" value="0" data-field="{{ $skill['field'] }}">
                            <span class="ml-3 text-muted">10</span>
                        </div>
                        <div class="text-center">
                            <span class="badge badge-lg badge-dark text-white evaluation-badge">0</span>
                        </div>
                        @if(!$loop->last)<hr class="my-4">@endif
                    </div>
                    @endforeach
                </div>

                <!-- STEP 4: Otros Aspectos -->
                <div class="wizard-step" id="step4" style="display: none;">
                    <h4 class="text-rugby mb-4">
                        <i class="fas fa-clipboard-list"></i> Otros Aspectos
                    </h4>
                    <p class="text-muted mb-4">Evalúa otros aspectos importantes del <strong>0 al 10</strong></p>

                    @php
                        $otherSkills = [
                            ['field' => 'disciplina', 'label' => 'Disciplina', 'icon' => 'fa-tasks'],
                            ['field' => 'compromiso', 'label' => 'Compromiso', 'icon' => 'fa-handshake'],
                            ['field' => 'puntualidad', 'label' => 'Puntualidad', 'icon' => 'fa-clock'],
                            ['field' => 'actitud_positiva', 'label' => 'Actitud positiva', 'icon' => 'fa-smile'],
                            ['field' => 'actitud_negativa', 'label' => 'Actitud negativa', 'icon' => 'fa-frown', 'warning' => true],
                            ['field' => 'comunicacion', 'label' => 'Comunicación', 'icon' => 'fa-comments']
                        ];
                    @endphp

                    @foreach($otherSkills as $skill)
                    <div class="evaluation-field" data-field="{{ $skill['field'] }}">
                        <label class="font-weight-bold mb-3">
                            <i class="fas {{ $skill['icon'] }}"></i> {{ $skill['label'] }}
                            @if(isset($skill['warning']))
                                <span class="badge badge-danger ml-2">
                                    <i class="fas fa-exclamation-triangle"></i> Resta puntos
                                </span>
                            @endif
                        </label>
                        <div class="d-flex align-items-center mb-2">
                            <span class="mr-3 text-muted">0</span>
                            <input type="range" class="custom-range flex-grow-1 evaluation-slider {{ isset($skill['warning']) ? 'warning-slider' : '' }}"
                                   min="0" max="10" value="0" data-field="{{ $skill['field'] }}">
                            <span class="ml-3 text-muted">10</span>
                        </div>
                        <div class="text-center">
                            <span class="badge badge-lg badge-dark text-white evaluation-badge">0</span>
                        </div>
                        @if(isset($skill['warning']))
                            <small class="text-danger d-block text-center mt-2">
                                <i class="fas fa-info-circle"></i> Mayor valor = más puntos restados
                            </small>
                        @endif
                        @if(!$loop->last)<hr class="my-4">@endif
                    </div>
                    @endforeach
                </div>

                <!-- STEP 5: Específico (Forwards/Backs) -->
                <div class="wizard-step" id="step5" style="display: none;">
                    @if($isForward)
                        <h4 class="text-rugby mb-4">
                            <i class="fas fa-shield-alt"></i> Habilidades de Forward
                        </h4>
                        <p class="text-muted mb-4">Evalúa las habilidades específicas del <strong>0 al 10</strong></p>

                        @php
                            $forwardSkills = [
                                ['field' => 'scrum_tecnica', 'label' => 'Scrum - Técnica', 'icon' => 'fa-users'],
                                ['field' => 'scrum_empuje', 'label' => 'Scrum - Empuje', 'icon' => 'fa-fist-raised'],
                                ['field' => 'line_levantar', 'label' => 'Lineout - Levantar', 'icon' => 'fa-hands-helping'],
                                ['field' => 'line_saltar', 'label' => 'Lineout - Saltar', 'icon' => 'fa-arrow-up'],
                                ['field' => 'line_lanzamiento', 'label' => 'Lineout - Lanzamiento', 'icon' => 'fa-bullseye']
                            ];
                        @endphp

                        @foreach($forwardSkills as $skill)
                        <div class="evaluation-field" data-field="{{ $skill['field'] }}">
                            <label class="font-weight-bold mb-3">
                                <i class="fas {{ $skill['icon'] }}"></i> {{ $skill['label'] }}
                            </label>
                            <div class="d-flex align-items-center mb-2">
                                <span class="mr-3 text-muted">0</span>
                                <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                                       min="0" max="10" value="0" data-field="{{ $skill['field'] }}">
                                <span class="ml-3 text-muted">10</span>
                            </div>
                            <div class="text-center">
                                <span class="badge badge-lg badge-dark text-white evaluation-badge">0</span>
                            </div>
                            @if(!$loop->last)<hr class="my-4">@endif
                        </div>
                        @endforeach
                    @else
                        <h4 class="text-rugby mb-4">
                            <i class="fas fa-running"></i> Habilidades de Back
                        </h4>
                        <p class="text-muted mb-4">Evalúa las habilidades específicas del <strong>0 al 10</strong></p>

                        @php
                            $backSkills = [
                                ['field' => 'kick_salidas', 'label' => 'Kick - Salidas', 'icon' => 'fa-flag'],
                                ['field' => 'kick_aire', 'label' => 'Kick - Aire', 'icon' => 'fa-cloud'],
                                ['field' => 'kick_rastron', 'label' => 'Kick - Rastrón', 'icon' => 'fa-angle-double-right'],
                                ['field' => 'kick_palos', 'label' => 'Kick - Palos', 'icon' => 'fa-crosshairs'],
                                ['field' => 'kick_drop', 'label' => 'Kick - Drop', 'icon' => 'fa-football-ball']
                            ];
                        @endphp

                        @foreach($backSkills as $skill)
                        <div class="evaluation-field" data-field="{{ $skill['field'] }}">
                            <label class="font-weight-bold mb-3">
                                <i class="fas {{ $skill['icon'] }}"></i> {{ $skill['label'] }}
                            </label>
                            <div class="d-flex align-items-center mb-2">
                                <span class="mr-3 text-muted">0</span>
                                <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                                       min="0" max="10" value="0" data-field="{{ $skill['field'] }}">
                                <span class="ml-3 text-muted">10</span>
                            </div>
                            <div class="text-center">
                                <span class="badge badge-lg badge-dark text-white evaluation-badge">0</span>
                            </div>
                            @if(!$loop->last)<hr class="my-4">@endif
                        </div>
                        @endforeach
                    @endif
                </div>

            </div>

            <!-- Footer con botones de navegación -->
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between">
                    <button class="btn btn-outline-secondary" id="btnPrev">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </button>
                    <button class="btn btn-rugby" id="btnNext">
                        Siguiente <i class="fas fa-arrow-right"></i>
                    </button>
                    <button class="btn btn-success btn-lg" id="btnSubmit" style="display: none;">
                        <i class="fas fa-check-circle"></i> Enviar Evaluación
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    let currentStep = 1;
    let evaluationData = {
        'evaluated_player_id': {{ $player->id }}
    };

    // Configuración de pasos
    const totalSteps = 5;

    // Inicializar en el paso 1
    showStep(1);

    // Navegación entre pasos
    function showStep(step) {
        currentStep = step;

        // Ocultar todos los pasos
        $('.wizard-step').hide();

        // Mostrar paso actual
        $(`#step${step}`).fadeIn(300);

        // Actualizar barra de progreso
        const progress = (step / totalSteps) * 100;
        $('#progressBarFill').css('width', progress + '%');
        $('#progressText').text(`Paso ${step} de ${totalSteps}`);

        // Botones
        $('#btnPrev').toggle(step > 1);
        $('#btnNext').toggle(step < totalSteps);
        $('#btnSubmit').toggle(step === totalSteps);
    }

    function goToStep(step) {
        showStep(step);
        window.scrollTo({top: 0, behavior: 'smooth'});
    }

    $('#btnNext').on('click', () => goToStep(currentStep + 1));
    $('#btnPrev').on('click', () => goToStep(currentStep - 1));

    $('#btnSubmit').on('click', function() {
        if (confirm('¿Enviar evaluación? No podrás modificarla después.')) {
            // Deshabilitar botón para evitar doble submit
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

            // Enviar datos vía AJAX
            $.ajax({
                url: '/evaluacion/store',
                method: 'POST',
                data: evaluationData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar mensaje de éxito y redirigir a lista
                        alert('✅ Evaluación guardada exitosamente.\n\nPuedes continuar evaluando a tus otros compañeros.');
                        window.location.href = '/evaluacion';
                    } else {
                        alert('Error: ' + response.message);
                        $btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Enviar Evaluación');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Error al guardar la evaluación.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Errores de validación
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        errorMsg = errors.join('\n');
                    }

                    alert(errorMsg);
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Enviar Evaluación');
                }
            });
        }
    });

    // Sliders
    $(document).on('input', '.evaluation-slider', function() {
        const field = $(this).data('field');
        const value = parseInt($(this).val());
        const $badge = $(this).closest('.evaluation-field').find('.evaluation-badge');

        $badge.text(value);
        evaluationData[field] = value;

        // Colores Los Troncos (negro → verde)
        $badge.removeClass('badge-danger badge-dark badge-secondary bg-rugby text-white');

        const isWarning = $(this).hasClass('warning-slider');

        if (isWarning) {
            // Actitud negativa: invertir colores (más rojo = peor)
            if (value >= 7) $badge.addClass('badge-danger');
            else if (value >= 4) $badge.addClass('badge-secondary text-white');
            else $badge.addClass('bg-rugby text-white');
        } else {
            // Normal: negro bajo → verde alto
            if (value <= 3) {
                $badge.addClass('badge-dark text-white'); // Negro
            } else if (value <= 6) {
                $badge.addClass('badge-secondary text-white'); // Gris oscuro
            } else {
                $badge.addClass('bg-rugby text-white'); // Verde
            }
        }
    });
});
</script>
@endsection

@section('css')
<style>
/* Colores del club rugby */
.bg-rugby {
    background-color: #1e4d2b !important;
}

.text-rugby {
    color: #1e4d2b !important;
}

.btn-rugby {
    background-color: #1e4d2b;
    border-color: #1e4d2b;
    color: white;
}

.btn-rugby:hover {
    background-color: #163d22;
    border-color: #163d22;
}

/* Slider colores Los Troncos (verde claro → verde oscuro) */
.custom-range::-webkit-slider-thumb {
    background-color: #000000; /* Negro (círculo) */
    border: 2px solid #1e4d2b; /* Borde verde */
}

.custom-range::-moz-range-thumb {
    background-color: #000000;
    border: 2px solid #1e4d2b;
}

.custom-range::-ms-thumb {
    background-color: #000000;
    border: 2px solid #1e4d2b;
}

.custom-range::-webkit-slider-runnable-track {
    background: linear-gradient(to right, #7cb342 0%, #1e4d2b 100%); /* Verde claro → Verde oscuro */
    height: 8px;
    border-radius: 4px;
}

.custom-range::-moz-range-track {
    background: linear-gradient(to right, #7cb342 0%, #1e4d2b 100%);
    height: 8px;
    border-radius: 4px;
}

/* Slider advertencia (rojo) */
.warning-slider::-webkit-slider-thumb {
    background-color: #dc3545;
}

.warning-slider::-moz-range-thumb {
    background-color: #dc3545;
}

.warning-slider::-ms-thumb {
    background-color: #dc3545;
}

/* Badges */
.badge-lg {
    font-size: 1.2rem;
    padding: 0.5rem 1rem;
    min-width: 50px;
}

/* Animaciones */
.wizard-step {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mobile */
@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem !important;
    }

    .custom-range {
        height: 30px;
    }

    .badge {
        font-size: 0.9rem;
    }
}
</style>
@endsection
