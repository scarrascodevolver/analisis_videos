@extends('layouts.auth')

@section('title', 'Registro - Los Troncos')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>Los Troncos</h3>
            <p>Registro en el Sistema</p>
        </div>
        
        <div class="auth-body">
            <form method="POST" action="{{ route('register') }}" id="registerForm">
                @csrf
                
                <!-- Step 1: Basic Information -->
                <div id="step1" class="registration-step">
                    <h5 class="text-center mb-4">
                        <i class="fas fa-user-plus text-success"></i>
                        Información Básica
                    </h5>
                    
                    <!-- Name -->
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   name="name" placeholder="Nombre completo" 
                                   value="{{ old('name') }}" required>
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                            </div>
                        </div>
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <div class="input-group">
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   name="email" placeholder="Correo electrónico" 
                                   value="{{ old('email') }}" required>
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                            </div>
                        </div>
                        @error('email')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <div class="input-group">
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                   name="phone" placeholder="Teléfono (ej: +56912345678)" 
                                   value="{{ old('phone') }}">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-phone"></i>
                                </span>
                            </div>
                        </div>
                        @error('phone')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <div class="input-group">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   name="password" placeholder="Contraseña" required>
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                            </div>
                        </div>
                        @error('password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <div class="input-group">
                            <input type="password" class="form-control" 
                                   name="password_confirmation" placeholder="Confirmar contraseña" required>
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="form-group">
                        <label class="text-muted small">Rol en el equipo</label>
                        <select class="form-control @error('role') is-invalid @enderror" name="role" required>
                            <option value="">Seleccionar rol...</option>
                            <option value="jugador" {{ old('role') == 'jugador' ? 'selected' : '' }}>Jugador</option>
                            <option value="entrenador" {{ old('role') == 'entrenador' ? 'selected' : '' }}>Entrenador</option>
                            <option value="analista" {{ old('role') == 'analista' ? 'selected' : '' }}>Analista</option>
                            <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff Técnico</option>
                        </select>
                        @error('role')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="row mt-4">
                        <div class="col-6">
                            <a href="{{ route('login') }}" class="btn btn-outline-rugby btn-block">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-rugby btn-block" id="nextStep1">
                                Continuar <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Rugby Profile -->
                <div id="step2" class="registration-step" style="display: none;">
                    <h5 class="text-center mb-4">
                        <i class="fas fa-futbol text-success"></i>
                        Perfil Rugby
                    </h5>

                    <!-- Player Fields -->
                    <div id="playerFields" style="display: none;">
                        <div class="form-group">
                            <label class="text-muted small">Posición principal</label>
                            <select class="form-control" name="position">
                                <option value="">Seleccionar posición...</option>
                                <optgroup label="Primera Línea">
                                    <option value="pilar_izquierdo">Pilar Izquierdo (1)</option>
                                    <option value="hooker">Hooker (2)</option>
                                    <option value="pilar_derecho">Pilar Derecho (3)</option>
                                </optgroup>
                                <optgroup label="Segunda Línea">
                                    <option value="segunda_linea_4">Segunda Línea (4)</option>
                                    <option value="segunda_linea_5">Segunda Línea (5)</option>
                                </optgroup>
                                <optgroup label="Tercera Línea">
                                    <option value="ala_izquierdo">Ala Izquierdo (6)</option>
                                    <option value="ala_derecho">Ala Derecho (7)</option>
                                    <option value="octavo">Octavo (8)</option>
                                </optgroup>
                                <optgroup label="Backs">
                                    <option value="medio_scrum">Medio Scrum (9)</option>
                                    <option value="apertura">Apertura (10)</option>
                                    <option value="ala_izquierdo_back">Ala Izquierdo (11)</option>
                                    <option value="centro_interno">Centro Interno (12)</option>
                                    <option value="centro_externo">Centro Externo (13)</option>
                                    <option value="ala_derecho_back">Ala Derecho (14)</option>
                                    <option value="fullback">Fullback (15)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="text-muted small">Número</label>
                                    <input type="number" class="form-control" name="player_number" 
                                           min="1" max="99" placeholder="10">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="text-muted small">Experiencia</label>
                                    <select class="form-control" name="experience_level">
                                        <option value="">Nivel...</option>
                                        <option value="principiante">Principiante</option>
                                        <option value="intermedio">Intermedio</option>
                                        <option value="avanzado">Avanzado</option>
                                        <option value="experto">Experto</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="text-muted small">Peso (kg)</label>
                                    <input type="number" class="form-control" name="weight" 
                                           min="40" max="200" placeholder="75">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="text-muted small">Altura (cm)</label>
                                    <input type="number" class="form-control" name="height" 
                                           min="150" max="220" placeholder="180">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="text-muted small">Fecha de nacimiento</label>
                            <input type="date" class="form-control" name="date_of_birth" 
                                   max="{{ date('Y-m-d', strtotime('-15 years')) }}">
                        </div>
                    </div>

                    <!-- Coach Fields -->
                    <div id="coachFields" style="display: none;">
                        <div class="form-group">
                            <label class="text-muted small">Años de experiencia</label>
                            <input type="number" class="form-control" name="coaching_experience" 
                                   min="0" max="50" placeholder="5">
                        </div>

                        <div class="form-group">
                            <label class="text-muted small">Certificaciones</label>
                            <textarea class="form-control" name="certifications" rows="3" 
                                      placeholder="Ej: Nivel 1 World Rugby, Curso IRB, etc."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="text-muted small">Especializaciones</label>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="specializations[]" 
                                               value="forwards" id="spec_forwards">
                                        <label class="form-check-label" for="spec_forwards">Forwards</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="specializations[]" 
                                               value="backs" id="spec_backs">
                                        <label class="form-check-label" for="spec_backs">Backs</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="specializations[]" 
                                               value="scrum" id="spec_scrum">
                                        <label class="form-check-label" for="spec_scrum">Scrum</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="specializations[]" 
                                               value="lineout" id="spec_lineout">
                                        <label class="form-check-label" for="spec_lineout">Lineout</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="specializations[]" 
                                               value="fitness" id="spec_fitness">
                                        <label class="form-check-label" for="spec_fitness">Prep. Física</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="specializations[]" 
                                               value="mental" id="spec_mental">
                                        <label class="form-check-label" for="spec_mental">Prep. Mental</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Goals (Common) -->
                    <div class="form-group">
                        <label class="text-muted small">Objetivos principales</label>
                        <textarea class="form-control" name="goals" rows="3" 
                                  placeholder="Describe tu experiencia en el rugby..."></textarea>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="row mt-4">
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-rugby btn-block" id="backStep1">
                                <i class="fas fa-arrow-left"></i> Atrás
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="submit" class="btn btn-rugby btn-block">
                                <i class="fas fa-user-check"></i> Registrarse
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Progress Indicator -->
                <div class="step-indicator mt-4">
                    <div class="progress mb-2">
                        <div class="progress-bar" id="progressBar" style="width: 50%"></div>
                    </div>
                    <div class="text-center">
                        <small class="text-muted">
                            Paso <span id="currentStep">1</span> de 2
                        </small>
                    </div>
                </div>
            </form>

            <!-- Links -->
            <div class="text-center mt-4">
                <p class="mb-0">
                    ¿Ya tienes cuenta? 
                    <a href="{{ route('login') }}"><strong>Iniciar sesión</strong></a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Show/hide fields based on role
    $('select[name="role"]').on('change', function() {
        const role = $(this).val();
        $('#playerFields, #coachFields').hide();
        
        if (role === 'jugador') {
            $('#playerFields').show();
        } else if (role === 'entrenador') {
            $('#coachFields').show();
        }
    });

    // Step navigation
    $('#nextStep1').on('click', function() {
        if (validateStep1()) {
            $('#step1').hide();
            $('#step2').show();
            $('#progressBar').css('width', '100%');
            $('#currentStep').text('2');
            
            // Show appropriate fields
            $('select[name="role"]').trigger('change');
        }
    });

    $('#backStep1').on('click', function() {
        $('#step2').hide();
        $('#step1').show();
        $('#progressBar').css('width', '50%');
        $('#currentStep').text('1');
    });

    // Validation
    function validateStep1() {
        let isValid = true;
        const requiredFields = ['name', 'email', 'password', 'password_confirmation', 'role'];
        
        requiredFields.forEach(function(field) {
            const input = $(`[name="${field}"]`);
            if (!input.val() || input.val().trim() === '') {
                input.addClass('is-invalid');
                isValid = false;
            } else {
                input.removeClass('is-invalid');
            }
        });

        // Check passwords match
        if ($('[name="password"]').val() !== $('[name="password_confirmation"]').val()) {
            $('[name="password_confirmation"]').addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            alert('Por favor completa todos los campos requeridos');
        }

        return isValid;
    }

    // Auto-suggest player number
    $('select[name="position"]').on('change', function() {
        const position = $(this).val();
        const numbers = {
            'pilar_izquierdo': 1, 'hooker': 2, 'pilar_derecho': 3,
            'segunda_linea_4': 4, 'segunda_linea_5': 5,
            'ala_izquierdo': 6, 'ala_derecho': 7, 'octavo': 8,
            'medio_scrum': 9, 'apertura': 10, 'ala_izquierdo_back': 11,
            'centro_interno': 12, 'centro_externo': 13, 'ala_derecho_back': 14, 'fullback': 15
        };

        if (numbers[position] && !$('[name="player_number"]').val()) {
            $('[name="player_number"]').val(numbers[position]);
        }
    });

    // Format phone number
    $('[name="phone"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 0 && !value.startsWith('56')) {
            if (value.startsWith('9')) {
                value = '56' + value;
            }
        }
        $(this).val(value ? '+' + value : '');
    });
});
</script>
@endsection