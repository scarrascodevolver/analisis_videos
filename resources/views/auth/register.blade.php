@extends('layouts.auth')

@section('title', 'Registro de Jugador')

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                @if ($organization)
                    {{-- Código válido: mostrar logo y nombre del club --}}
                    <div class="logo-icon">
                        @if ($organization->logo_path)
                            <img src="{{ asset('storage/' . $organization->logo_path) }}" alt="{{ $organization->name }}"
                                style="width: 234px; height: 150px; object-fit: contain;">
                        @else
                            <img src="{{ asset('logo.png') }}" alt="{{ $organization->name }}"
                                style="width: 234px; height: 150px; object-fit: contain;">
                        @endif
                    </div>
                    <h3>{{ $organization->name }}</h3>
                    <p>Registro de Jugador</p>
                @else
                    {{-- Sin código: mostrar logo genérico --}}
                    <div class="logo-icon">
                        <img src="{{ asset('logo.png') }}" alt="Rugby Key Performance Logo"
                            style="width: 234px; height: 150px; object-fit: contain;">
                    </div>

                @endif
            </div>

            <div class="auth-body">
                <form method="POST" action="{{ route('register') }}" id="registerForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Campo de código de invitación --}}
                    @if ($organization)
                        {{-- Código válido en URL: campo oculto --}}
                        <input type="hidden" name="invitation_code" id="invitation_code" value="{{ $invitationCode }}">
                        <div class="alert alert-success mb-3 text-center">
                            <i class="fas fa-check-circle"></i>
                            Te registrarás en <strong>{{ $organization->name }}</strong>
                        </div>
                    @else
                        {{-- Sin código: mostrar campo para ingresar --}}
                        <div id="codeSection" class="mb-4">
                            <div class="form-group">
                                <label class="text-muted small font-weight-bold">
                                    <i class="fas fa-ticket-alt"></i> Código del Club *
                                </label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control text-uppercase @error('invitation_code') is-invalid @enderror"
                                        name="invitation_code" id="invitation_code" placeholder="Ej: LOSTRONCOS25"
                                        value="{{ old('invitation_code', $invitationCode) }}" maxlength="20" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-rugby py-0" id="validateCodeBtn"
                                            style="height: 100%;">
                                            <i class="fas fa-search"></i> Validar
                                        </button>
                                    </div>
                                </div>
                                @error('invitation_code')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                                <small class="form-text text-muted">
                                    Ingresa el código que te proporcionó tu club
                                </small>
                            </div>
                            <div id="codeResult" class="mb-3" style="display: none;"></div>
                        </div>
                    @endif

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
                                    name="name" placeholder="Nombre completo" value="{{ old('name') }}" required>
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
                                    name="email" placeholder="Correo electrónico" value="{{ old('email') }}" required>
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
                            <label class="text-muted small font-weight-bold">
                                <i class="fas fa-phone"></i> Teléfono (Opcional)
                            </label>
                            <div class="input-group phone-input-group">
                                <select class="country-code-select @error('country_code') is-invalid @enderror"
                                    name="country_code" id="country_code">
                                        <option value="+56" {{ old('country_code', '+56') == '+56' ? 'selected' : '' }}>🇨🇱 +56</option>
                                        <option value="+54" {{ old('country_code') == '+54' ? 'selected' : '' }}>🇦🇷 +54</option>
                                        <option value="+34" {{ old('country_code') == '+34' ? 'selected' : '' }}>🇪🇸 +34</option>
                                        <option value="+598" {{ old('country_code') == '+598' ? 'selected' : '' }}>🇺🇾 +598</option>
                                        <option value="+55" {{ old('country_code') == '+55' ? 'selected' : '' }}>🇧🇷 +55</option>
                                        <option value="+52" {{ old('country_code') == '+52' ? 'selected' : '' }}>🇲🇽 +52</option>
                                        <option value="+51" {{ old('country_code') == '+51' ? 'selected' : '' }}>🇵🇪 +51</option>
                                        <option value="+57" {{ old('country_code') == '+57' ? 'selected' : '' }}>🇨🇴 +57</option>
                                        <option value="+1" {{ old('country_code') == '+1' ? 'selected' : '' }}>🇺🇸 +1</option>
                                        <option value="+44" {{ old('country_code') == '+44' ? 'selected' : '' }}>🇬🇧 +44</option>
                                        <option value="+33" {{ old('country_code') == '+33' ? 'selected' : '' }}>🇫🇷 +33</option>
                                        <option value="+39" {{ old('country_code') == '+39' ? 'selected' : '' }}>🇮🇹 +39</option>
                                        <option value="+61" {{ old('country_code') == '+61' ? 'selected' : '' }}>🇦🇺 +61</option>
                                        <option value="+64" {{ old('country_code') == '+64' ? 'selected' : '' }}>🇳🇿 +64</option>
                                        <option value="+27" {{ old('country_code') == '+27' ? 'selected' : '' }}>🇿🇦 +27</option>
                                </select>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                    name="phone" id="phone_number" placeholder="912345678" value="{{ old('phone') }}">
                            </div>
                            @error('phone')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <small class="form-text text-muted">
                                Solo el número sin el código de país
                            </small>
                        </div>

                        <!-- Avatar Upload -->
                        <div class="form-group">
                            <label class="text-muted small font-weight-bold">
                                <i class="fas fa-camera"></i> Foto de Perfil (Opcional)
                            </label>
                            <div class="text-center mb-2">
                                <img id="avatar-preview"
                                    src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiNlOWVjZWYiLz48cGF0aCBkPSJtNTAgNDhjOS4yMDUgMCAxNi42NjctNy40NjIgMTYuNjY3LTE2LjY2N3MtNy40NjItMTYuNjY3LTE2LjY2Ny0xNi42NjctMTYuNjY3IDcuNDYyLTE2LjY2NyAxNi42NjcgNy40NjIgMTYuNjY3IDE2LjY2NyAxNi42Njd6bTAgOC4zMzNjLTExLjEzMyAwLTMzLjMzMyA1LjU4NC0zMy4zMzMgMTYuNjY3djguMzMzaDY2LjY2N3YtOC4zMzNjMC0xMS4wODMtMjIuMi0xNi42NjctMzMuMzM0LTE2LjY2N3oiIGZpbGw9IiM5ZWEzYTgiLz48L3N2Zz4="
                                    alt="Preview" class="avatar-preview-register">
                            </div>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('avatar') is-invalid @enderror"
                                    id="avatar" name="avatar" accept="image/jpeg,image/png,image/jpg,image/gif">
                                <label class="custom-file-label" for="avatar">Seleccionar imagen...</label>
                            </div>
                            @error('avatar')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <small class="form-text text-muted">
                                Formatos: JPG, PNG, GIF. Tamaño máximo: 2MB
                            </small>
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    name="password" placeholder="Contraseña" required id="password">
                                <div class="input-group-append">
                                    <span class="input-group-text password-toggle"
                                        onclick="togglePassword('password', this)">
                                        <i class="fas fa-eye"></i>
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
                                <input type="password" class="form-control" name="password_confirmation"
                                    placeholder="Confirmar contraseña" required id="password_confirmation">
                                <div class="input-group-append">
                                    <span class="input-group-text password-toggle"
                                        onclick="togglePassword('password_confirmation', this)">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden role field -->
                        <input type="hidden" name="role" value="jugador">

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
                            <i class="fas fa-football-ball text-success"></i>
                            Perfil Rugby
                        </h5>

                        <!-- Player Fields -->
                        <div id="playerFields" style="display: none;">
                            <div class="form-group">
                                <label class="text-muted small font-weight-bold">Posición principal</label>
                                <select class="form-control rugby-select" name="position">
                                    <option value="">Posición principal...</option>
                                    <optgroup label="Forwards">
                                        <option value="Pilar Izquierdo">Pilar Izquierdo (1)</option>
                                        <option value="Hooker">Hooker (2)</option>
                                        <option value="Pilar Derecho">Pilar Derecho (3)</option>
                                        <option value="Segunda Línea">Segunda Línea (4-5)</option>
                                        <option value="Ala">Ala (6-7)</option>
                                        <option value="Octavo">Octavo (8)</option>
                                    </optgroup>
                                    <optgroup label="Backs">
                                        <option value="Medio Scrum">Medio Scrum (9)</option>
                                        <option value="Apertura">Apertura (10)</option>
                                        <option value="Wing">Wing (11-14)</option>
                                        <option value="Centro">Centro (12-13)</option>
                                        <option value="Fullback">Fullback (15)</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="text-muted small font-weight-bold">Posición secundaria</label>
                                <select class="form-control rugby-select" name="secondary_position">
                                    <option value="">Posición secundaria...</option>
                                    <optgroup label="Forwards">
                                        <option value="Pilar Izquierdo">Pilar Izquierdo (1)</option>
                                        <option value="Hooker">Hooker (2)</option>
                                        <option value="Pilar Derecho">Pilar Derecho (3)</option>
                                        <option value="Segunda Línea">Segunda Línea (4-5)</option>
                                        <option value="Ala">Ala (6-7)</option>
                                        <option value="Octavo">Octavo (8)</option>
                                    </optgroup>
                                    <optgroup label="Backs">
                                        <option value="Medio Scrum">Medio Scrum (9)</option>
                                        <option value="Apertura">Apertura (10)</option>
                                        <option value="Wing">Wing (11-14)</option>
                                        <option value="Centro">Centro (12-13)</option>
                                        <option value="Fullback">Fullback (15)</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="text-muted small font-weight-bold">Peso (kg)</label>
                                        <input type="number" class="form-control" name="weight" min="40"
                                            max="200" placeholder="75">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="text-muted small font-weight-bold">Altura (cm)</label>
                                        <input type="number" class="form-control" name="height" min="150"
                                            max="220" placeholder="180">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="text-muted small font-weight-bold">Fecha de nacimiento</label>
                                <input type="date" class="form-control" name="date_of_birth">
                            </div>

                            <div class="form-group">
                                <label class="text-muted small font-weight-bold">Categoría del Jugador *</label>
                                <select class="form-control rugby-select" name="user_category_id" required>
                                    <option value="">Seleccionar categoría...</option>
                                    @if (isset($categories))
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="form-text text-muted">
                                    Selecciona la categoría a la que perteneces (determina qué videos puedes ver)
                                </small>
                            </div>
                        </div>

                        <!-- Coach Fields -->
                        <div id="coachFields" style="display: none;">
                            <div class="form-group">
                                <label class="text-muted small">Años de experiencia</label>
                                <input type="number" class="form-control" name="coaching_experience" min="0"
                                    max="50" placeholder="5">
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
        // Toggle password visibility
        function togglePassword(fieldId, element) {
            const passwordField = document.getElementById(fieldId);
            const icon = element.querySelector('i');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        $(document).ready(function() {
            // Variable para trackear si el código es válido
            let codeValidated = {{ $organization ? 'true' : 'false' }};

            // Validación de código de invitación vía AJAX
            $('#validateCodeBtn').on('click', function() {
                validateInvitationCode();
            });

            // También validar al presionar Enter en el campo
            $('#invitation_code').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    validateInvitationCode();
                }
            });

            // Convertir a mayúsculas mientras escribe
            $('#invitation_code').on('input', function() {
                $(this).val($(this).val().toUpperCase());
                // Resetear validación si cambia el código
                codeValidated = false;
                $('#codeResult').hide();
            });

            function validateInvitationCode() {
                const code = $('#invitation_code').val().trim();
                const btn = $('#validateCodeBtn');
                const resultDiv = $('#codeResult');

                if (!code) {
                    resultDiv.html(
                        '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Ingresa un código</div>'
                    ).show();
                    return;
                }

                // Mostrar loading
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: '/api/validate-invitation-code',
                    method: 'POST',
                    data: {
                        code: code,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.valid) {
                            codeValidated = true;
                            resultDiv.html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>${response.organization.name}</strong>
                            <br><small>Código válido - puedes continuar</small>
                        </div>
                    `).show();

                            // Actualizar categorías
                            updateCategories(response.categories);

                            // Marcar campo como válido
                            $('#invitation_code').removeClass('is-invalid').addClass('is-valid');
                        } else {
                            codeValidated = false;
                            resultDiv.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i>
                            ${response.message}
                        </div>
                    `).show();
                            $('#invitation_code').addClass('is-invalid').removeClass('is-valid');
                        }
                    },
                    error: function() {
                        codeValidated = false;
                        resultDiv.html(
                            '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Error al validar el código</div>'
                        ).show();
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-search"></i> Validar');
                    }
                });
            }

            function updateCategories(categories) {
                const select = $('select[name="user_category_id"]');
                select.empty().append('<option value="">Seleccionar categoría...</option>');

                categories.forEach(function(cat) {
                    select.append(`<option value="${cat.id}">${cat.name}</option>`);
                });
            }

            // Always show player fields since only players can register
            $('#playerFields').show();

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

                // Verificar código de invitación primero
                @if (!$organization)
                    if (!codeValidated) {
                        alert('Por favor valida el código del club antes de continuar');
                        $('#invitation_code').focus();
                        return false;
                    }
                @endif

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

            // Position selection enhancement
            $('select[name="position"]').on('change', function() {
                const selectedPosition = $(this).val();
                const secondarySelect = $('select[name="secondary_position"]');

                // Disable the same position in secondary select
                secondarySelect.find('option').prop('disabled', false);
                if (selectedPosition) {
                    secondarySelect.find(`option[value="${selectedPosition}"]`).prop('disabled', true);
                }
            });

            // Format phone number - solo números, sin código de país
            $('#phone_number').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                $(this).val(value);
            });

            // Avatar preview functionality
            $('#avatar').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (2MB max)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('El archivo es demasiado grande. Máximo 2MB permitido.');
                        $(this).val('');
                        return;
                    }

                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    if (!validTypes.includes(file.type)) {
                        alert('Formato no válido. Solo JPG, PNG o GIF.');
                        $(this).val('');
                        return;
                    }

                    // Update file label
                    $('.custom-file-label').text(file.name);

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#avatar-preview').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Reset to default if no file
                    $('.custom-file-label').text('Seleccionar imagen...');
                    $('#avatar-preview').attr('src',
                        'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiNlOWVjZWYiLz48cGF0aCBkPSJtNTAgNDhjOS4yMDUgMCAxNi42NjctNy40NjIgMTYuNjY3LTE2LjY2N3MtNy40NjItMTYuNjY3LTE2LjY2Ny0xNi42NjctMTYuNjY3IDcuNDYyLTE2LjY2NyAxNi42NjcgNy40NjIgMTYuNjY3IDE2LjY2NyAxNi42Njd6bTAgOC4zMzNjLTExLjEzMyAwLTMzLjMzMyA1LjU4NC0zMy4zMzMgMTYuNjY3djguMzMzaDY2LjY2N3YtOC4zMzNjMC0xMS4wODMtMjIuMi0xNi42NjctMzMuMzM0LTE2LjY2N3oiIGZpbGw9IiM5ZWEzYTgiLz48L3N2Zz4='
                    );
                }
            });
        });
    </script>

    <style>
        /* Improved CSS for rugby selects */
        .rugby-select {
            font-size: 14px !important;
            min-height: 50px !important;
            height: auto !important;
            line-height: 1.6 !important;
            padding: 12px 15px !important;
            background-color: #fff;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.3s ease;
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: ellipsis;
            appearance: menulist;
            -webkit-appearance: menulist;
            -moz-appearance: menulist;
        }

        .rugby-select:focus {
            border-color: var(--color-primary, #005461);
            box-shadow: 0 0 0 0.2rem rgba(0, 84, 97, 0.25);
            outline: none;
        }

        .rugby-select option {
            padding: 12px 15px !important;
            font-size: 14px !important;
            line-height: 1.6 !important;
            white-space: normal !important;
            word-wrap: break-word;
            min-height: 40px;
            display: block;
            background-color: #fff;
            color: #333;
        }

        .rugby-select option:hover {
            background-color: #f8f9fa;
        }

        .rugby-select optgroup {
            font-weight: bold !important;
            color: var(--color-primary, #005461) !important;
            font-size: 13px !important;
            padding: 8px 15px !important;
            background-color: #f1f8f1;
            margin: 4px 0;
        }

        .rugby-select optgroup option {
            font-weight: normal !important;
            color: #333 !important;
            padding-left: 25px !important;
            background-color: #fff;
            border-left: 3px solid var(--color-primary, #005461);
        }

        /* Better label visibility */
        .font-weight-bold {
            color: #495057 !important;
            font-size: 13px !important;
        }

        /* Form enhancements */
        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--color-primary, #005461);
            box-shadow: 0 0 0 0.2rem rgba(0, 84, 97, 0.25);
        }

        /* Additional fixes for select visibility */
        select.rugby-select {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        /* For mobile devices */
        @media (max-width: 768px) {
            .rugby-select {
                font-size: 16px !important;
                /* Prevents zoom on iOS */
                min-height: 44px !important;
            }
        }

        /* For different browsers */
        select.rugby-select option {
            overflow: visible !important;
            text-overflow: clip !important;
            white-space: nowrap !important;
        }

        /* Firefox specific */
        @-moz-document url-prefix() {
            .rugby-select option {
                text-indent: 0.01px;
                text-overflow: '';
            }
        }

        /* Rugby theme colors for selects */
        .rugby-select {
            border-color: #ced4da;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .rugby-select:focus {
            border-color: var(--color-primary, #005461) !important;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 84, 97, 0.25) !important;
        }

        .rugby-select:hover {
            border-color: var(--color-primary, #005461);
        }

        /* Para navegadores que soportan personalización de options */
        .rugby-select option {
            padding: 8px 12px;
            color: #333;
            background-color: white;
        }

        .rugby-select option:checked {
            background-color: var(--color-primary, #005461);
            color: white;
        }

        /* Webkit browsers (Chrome, Safari) - personalización limitada */
        .rugby-select::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .rugby-select::-webkit-scrollbar-thumb {
            background: var(--color-primary, #005461);
            border-radius: 6px;
        }

        .rugby-select::-webkit-scrollbar-thumb:hover {
            background: var(--color-primary-hover, #003d4a);
        }

        /* Alternativa: Crear un select personalizado con div + JS (más complejo) */

        /* Password toggle styles */
        .password-toggle {
            cursor: pointer;
            user-select: none;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            background-color: var(--color-primary, #005461) !important;
            color: white !important;
        }

        .password-toggle i {
            transition: all 0.3s ease;
        }

        .password-toggle:hover i {
            transform: scale(1.1);
        }

        /* Avatar preview styles */
        .avatar-preview-register {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--color-primary, #005461);
            margin: 0 auto;
            display: block;
            transition: all 0.3s ease;
        }

        .avatar-preview-register:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 84, 97, 0.3);
        }

        .custom-file-label {
            border-radius: 8px;
            border: 2px solid #dee2e6;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .custom-file-label:hover {
            border-color: var(--color-primary, #005461);
            background-color: #f8f9fa;
        }

        .custom-file-input:focus~.custom-file-label {
            border-color: var(--color-primary, #005461);
            box-shadow: 0 0 0 0.2rem rgba(0, 84, 97, 0.25);
        }

        .custom-file-label::after {
            background-color: var(--color-primary, #005461);
            color: white;
            border-radius: 0 6px 6px 0;
            content: "Buscar";
        }

        /* Contenedor del teléfono - misma altura para ambos */
        .phone-input-group {
            display: flex;
            align-items: stretch;
        }

        .phone-input-group .country-code-select,
        .phone-input-group #phone_number {
            height: 48px !important;
            box-sizing: border-box !important;
        }

        /* Country code selector */
        .country-code-select {
            width: 100px !important;
            min-width: 100px !important;
            max-width: 100px !important;
            font-size: 16px !important;
            padding: 0 10px !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-right: none !important;
            border-radius: 8px 0 0 8px !important;
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            cursor: pointer;
        }

        .country-code-select:focus {
            border-color: rgba(255, 255, 255, 0.6) !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.25) !important;
            outline: none;
        }

        /* Opciones del select con colores visibles */
        .country-code-select option {
            background-color: #fff !important;
            color: #333 !important;
            padding: 8px;
        }

        /* Input del teléfono */
        #phone_number {
            border-radius: 0 8px 8px 0 !important;
            border-left: none !important;
            flex: 1 !important;
        }
    </style>
@endsection
