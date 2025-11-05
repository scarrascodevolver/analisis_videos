@extends('layouts.app')

@section('page_title', 'Evaluación de Compañero')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Evaluación de Compañero</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <!-- Progress Bar -->
        <div class="mb-4" id="progressBar" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Progreso</span>
                <span class="text-rugby font-weight-bold" id="progressText">Paso 1 de 5</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-rugby" role="progressbar" style="width: 0%" id="progressBarFill"></div>
            </div>
        </div>

        <!-- Jugador seleccionado (header visible durante evaluación) -->
        <div class="alert alert-success mb-4" id="selectedPlayerHeader" style="display: none;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-user-check"></i>
                    <strong>Evaluando a:</strong>
                    <span id="selectedPlayerName"></span>
                    <span class="badge badge-info ml-2" id="selectedPlayerPosition"></span>
                    <span class="badge badge-secondary ml-1" id="selectedPlayerCategory"></span>
                </div>
                <button class="btn btn-sm btn-outline-danger" id="changePlayerBtn">
                    <i class="fas fa-sync"></i> Cambiar
                </button>
            </div>
        </div>

        <div class="card card-rugby">
            <div class="card-body p-4">

                <!-- STEP 0: Selector de Jugador -->
                <div class="wizard-step" id="step0">
                    <div class="text-center mb-4">
                        <i class="fas fa-users fa-4x text-rugby mb-3"></i>
                        <h3 class="text-rugby">Evaluación de Compañero</h3>
                        <p class="text-muted">Selecciona al jugador que deseas evaluar</p>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold mb-3">
                            <i class="fas fa-search"></i> Buscar Jugador
                        </label>
                        <input type="text"
                               class="form-control form-control-lg"
                               id="playerSearch"
                               placeholder="Escribe el nombre del jugador..."
                               autocomplete="off">
                        <small class="text-muted">Escribe al menos 2 caracteres para buscar</small>
                    </div>

                    <!-- Resultados de búsqueda -->
                    <div id="searchResults" class="list-group mt-3" style="display: none;"></div>

                    <!-- Jugadores populares/recientes -->
                    <div class="mt-4">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-star"></i> Jugadores de tu categoría
                        </h6>
                        <div id="recentPlayers" class="list-group"></div>
                    </div>
                </div>

                <!-- STEP 1: Acondicionamiento Físico -->
                <div class="wizard-step" id="step1" style="display: none;">
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
                            <span class="badge badge-lg badge-warning evaluation-badge">0</span>
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
                            <span class="badge badge-lg badge-warning evaluation-badge">0</span>
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
                            <span class="badge badge-lg badge-warning evaluation-badge">0</span>
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
                            <span class="badge badge-warning ml-2 evaluation-badge" style="min-width: 35px;">0</span>
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
                            <span class="badge badge-lg badge-warning evaluation-badge">0</span>
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
                            <span class="badge badge-lg badge-warning evaluation-badge">0</span>
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
                    <!-- Se llenará dinámicamente según posición -->
                </div>

            </div>

            <!-- Footer con botones de navegación -->
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between">
                    <button class="btn btn-outline-secondary" id="btnPrev" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </button>
                    <button class="btn btn-rugby" id="btnNext" style="display: none;">
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
    let currentStep = 0;
    let selectedPlayer = null;
    let evaluationData = {};

    // Configuración de pasos
    const totalSteps = 5;

    // Inicializar
    showStep(0);

    // Búsqueda de jugadores con AJAX
    let searchTimeout;
    $('#playerSearch').on('input', function() {
        const query = $(this).val().trim();

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            $('#searchResults').hide().empty();
            return;
        }

        searchTimeout = setTimeout(() => {
            searchPlayers(query);
        }, 300);
    });

    function searchPlayers(query) {
        $.ajax({
            url: '/api/search-players',
            method: 'GET',
            data: { q: query },
            success: function(players) {
                displaySearchResults(players);
            },
            error: function() {
                console.error('Error buscando jugadores');
            }
        });
    }

    function displaySearchResults(players) {
        const $results = $('#searchResults');
        $results.empty();

        if (players.length === 0) {
            $results.html('<div class="list-group-item text-muted">No se encontraron jugadores</div>');
            $results.show();
            return;
        }

        players.forEach(player => {
            const $item = $(`
                <a href="#" class="list-group-item list-group-item-action player-item" data-player='${JSON.stringify(player)}'>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${player.name}</strong>
                            <span class="badge badge-info ml-2">${player.position || 'Sin posición'}</span>
                            <span class="badge badge-secondary ml-1">${player.category || 'Sin categoría'}</span>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </div>
                </a>
            `);
            $results.append($item);
        });

        $results.show();
    }

    // Seleccionar jugador
    $(document).on('click', '.player-item', function(e) {
        e.preventDefault();
        selectedPlayer = JSON.parse($(this).attr('data-player'));
        selectPlayer(selectedPlayer);
    });

    function selectPlayer(player) {
        selectedPlayer = player;

        // Actualizar header
        $('#selectedPlayerName').text(player.name);
        $('#selectedPlayerPosition').text(player.position || 'Sin posición');
        $('#selectedPlayerCategory').text(player.category || 'Sin categoría');
        $('#selectedPlayerHeader').fadeIn();

        // Generar step 5 según posición
        generateStep5(player.position);

        // Ir al paso 1
        goToStep(1);
    }

    // Cambiar jugador
    $('#changePlayerBtn').on('click', function() {
        if (confirm('¿Estás seguro? Se perderán los datos ingresados.')) {
            selectedPlayer = null;
            evaluationData = {};
            resetAllSliders();
            $('#selectedPlayerHeader').hide();
            goToStep(0);
        }
    });

    // Generar paso 5 dinámicamente
    function generateStep5(position) {
        const isForward = position && position.toLowerCase().includes('forward');
        const isBack = position && position.toLowerCase().includes('back');

        let skills = [];
        let title = '';
        let icon = '';

        if (isForward) {
            title = 'Habilidades de Forward';
            icon = 'fa-shield-alt';
            skills = [
                {field: 'scrum_tecnica', label: 'Scrum - Técnica', icon: 'fa-users'},
                {field: 'scrum_empuje', label: 'Scrum - Empuje', icon: 'fa-fist-raised'},
                {field: 'line_levantar', label: 'Lineout - Levantar', icon: 'fa-hands-helping'},
                {field: 'line_saltar', label: 'Lineout - Saltar', icon: 'fa-arrow-up'},
                {field: 'line_lanzamiento', label: 'Lineout - Lanzamiento', icon: 'fa-bullseye'}
            ];
        } else if (isBack) {
            title = 'Habilidades de Back';
            icon = 'fa-running';
            skills = [
                {field: 'kick_salidas', label: 'Kick - Salidas', icon: 'fa-flag'},
                {field: 'kick_aire', label: 'Kick - Aire', icon: 'fa-cloud'},
                {field: 'kick_rastron', label: 'Kick - Rastrón', icon: 'fa-angle-double-right'},
                {field: 'kick_palos', label: 'Kick - Palos', icon: 'fa-crosshairs'},
                {field: 'kick_drop', label: 'Kick - Drop', icon: 'fa-football-ball'}
            ];
        } else {
            title = 'Habilidades Generales';
            icon = 'fa-star';
            skills = [
                {field: 'versatilidad', label: 'Versatilidad', icon: 'fa-sync'},
                {field: 'adaptabilidad', label: 'Adaptabilidad', icon: 'fa-adjust'}
            ];
        }

        let html = `
            <h4 class="text-rugby mb-4">
                <i class="fas ${icon}"></i> ${title}
            </h4>
            <p class="text-muted mb-4">Evalúa las habilidades específicas del <strong>0 al 10</strong></p>
        `;

        skills.forEach((skill, index) => {
            html += `
                <div class="evaluation-field" data-field="${skill.field}">
                    <label class="font-weight-bold mb-3">
                        <i class="fas ${skill.icon}"></i> ${skill.label}
                    </label>
                    <div class="d-flex align-items-center mb-2">
                        <span class="mr-3 text-muted">0</span>
                        <input type="range" class="custom-range flex-grow-1 evaluation-slider"
                               min="0" max="10" value="0" data-field="${skill.field}">
                        <span class="ml-3 text-muted">10</span>
                    </div>
                    <div class="text-center">
                        <span class="badge badge-lg badge-warning evaluation-badge">0</span>
                    </div>
                    ${index < skills.length - 1 ? '<hr class="my-4">' : ''}
                </div>
            `;
        });

        $('#step5').html(html);
    }

    // Navegación entre pasos
    function showStep(step) {
        currentStep = step;

        // Ocultar todos los pasos
        $('.wizard-step').hide();

        // Mostrar paso actual
        $(`#step${step}`).fadeIn(300);

        // Actualizar barra de progreso
        if (step === 0) {
            $('#progressBar').hide();
        } else {
            $('#progressBar').show();
            const progress = (step / totalSteps) * 100;
            $('#progressBarFill').css('width', progress + '%');
            $('#progressText').text(`Paso ${step} de ${totalSteps}`);
        }

        // Botones
        $('#btnPrev').toggle(step > 0);
        $('#btnNext').toggle(step > 0 && step < totalSteps);
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
            window.location.href = '/evaluacion/completada';
        }
    });

    // Sliders
    $(document).on('input', '.evaluation-slider', function() {
        const field = $(this).data('field');
        const value = parseInt($(this).val());
        const $badge = $(this).closest('.evaluation-field').find('.evaluation-badge');

        $badge.text(value);
        evaluationData[field] = value;

        // Colores rugby (amarillo/verde)
        $badge.removeClass('badge-danger badge-warning badge-success bg-rugby');

        const isWarning = $(this).hasClass('warning-slider');

        if (isWarning) {
            // Actitud negativa: invertir colores
            if (value >= 7) $badge.addClass('badge-danger');
            else if (value >= 4) $badge.addClass('badge-warning');
            else $badge.addClass('badge-success');
        } else {
            // Normal: amarillo bajo, verde alto
            if (value <= 3) $badge.addClass('badge-warning');
            else if (value <= 6) $badge.addClass('badge-warning');
            else $badge.addClass('bg-rugby text-white');
        }
    });

    function resetAllSliders() {
        $('.evaluation-slider').val(0).trigger('input');
    }

    // Cargar jugadores de la categoría del usuario
    function loadRecentPlayers() {
        $.ajax({
            url: '/api/category-players',
            method: 'GET',
            success: function(players) {
                const $container = $('#recentPlayers');
                $container.empty();

                players.forEach(player => {
                    const $item = $(`
                        <a href="#" class="list-group-item list-group-item-action player-item" data-player='${JSON.stringify(player)}'>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${player.name}</strong>
                                    <span class="badge badge-info ml-2">${player.position || 'Sin posición'}</span>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                    `);
                    $container.append($item);
                });
            }
        });
    }

    loadRecentPlayers();
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

/* Slider colores rugby (verde/amarillo) */
.custom-range::-webkit-slider-thumb {
    background-color: #ffc107; /* Amarillo */
}

.custom-range::-moz-range-thumb {
    background-color: #ffc107;
}

.custom-range::-ms-thumb {
    background-color: #ffc107;
}

.custom-range::-webkit-slider-runnable-track {
    background: linear-gradient(to right, #ffc107 0%, #1e4d2b 100%);
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

/* Lista de jugadores */
.list-group-item:hover {
    background-color: #f8f9fa;
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
