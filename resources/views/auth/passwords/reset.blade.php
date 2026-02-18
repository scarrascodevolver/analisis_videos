@extends('layouts.auth')

@section('title', 'Nueva Contraseña - Rugby Key Performance')

@section('css')
<style>
.password-toggle-btn {
    border-left: 0 !important;
    border-color: #ced4da;
    background-color: #f8f9fa;
    color: #6c757d;
    transition: all 0.2s ease;
}

.password-toggle-btn:hover {
    background-color: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

.password-toggle-btn:focus {
    box-shadow: none;
    border-color: var(--color-primary);
}

.password-toggle-btn.active {
    background-color: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}
</style>
@endsection

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-icon">
                <img src="{{ asset('logo.png') }}" alt="Rugby Key Performance Logo" style="width: 130px; height: 130px; object-fit: contain;">
            </div>
            <h3>Rugby Key Performance</h3>
            <p>Sistema de Análisis de Video para Rugby</p>
        </div>

        <div class="auth-body">
            <h5 class="text-center mb-4">Nueva Contraseña</h5>

            <p class="text-muted text-center mb-4">
                Ingresa tu nueva contraseña. Debe tener al menos 8 caracteres.
            </p>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <!-- Email -->
                <div class="form-group">
                    <div class="input-group">
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" placeholder="Correo electrónico"
                               value="{{ $email ?? old('email') }}" required autocomplete="email" readonly>
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

                <!-- New Password -->
                <div class="form-group">
                    <div class="input-group">
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" placeholder="Nueva contraseña"
                               required autocomplete="new-password">
                        <div class="input-group-append">
                            <button type="button" class="btn password-toggle-btn" id="togglePassword" title="Mostrar contraseña">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
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
                               id="password-confirm" name="password_confirmation" placeholder="Confirmar nueva contraseña"
                               required autocomplete="new-password">
                        <div class="input-group-append">
                            <button type="button" class="btn password-toggle-btn" id="togglePasswordConfirm" title="Mostrar confirmación">
                                <i class="fas fa-eye" id="toggleIconConfirm"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-rugby btn-block btn-lg">
                    <i class="fas fa-key"></i> Actualizar Contraseña
                </button>
            </form>

            <!-- Links -->
            <div class="text-center mt-4">
                <p class="mb-0">
                    ¿Recordaste tu contraseña?
                    <a href="{{ route('login') }}"><strong>Iniciar Sesión</strong></a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para contraseña principal
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            if (type === 'text') {
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
                togglePassword.classList.add('active');
                togglePassword.title = 'Ocultar contraseña';
            } else {
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                togglePassword.classList.remove('active');
                togglePassword.title = 'Mostrar contraseña';
            }
        });
    }

    // Toggle para confirmación de contraseña
    const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
    const passwordConfirmInput = document.getElementById('password-confirm');
    const toggleIconConfirm = document.getElementById('toggleIconConfirm');

    if (togglePasswordConfirm) {
        togglePasswordConfirm.addEventListener('click', function() {
            const type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirmInput.setAttribute('type', type);

            if (type === 'text') {
                toggleIconConfirm.classList.remove('fa-eye');
                toggleIconConfirm.classList.add('fa-eye-slash');
                togglePasswordConfirm.classList.add('active');
                togglePasswordConfirm.title = 'Ocultar confirmación';
            } else {
                toggleIconConfirm.classList.remove('fa-eye-slash');
                toggleIconConfirm.classList.add('fa-eye');
                togglePasswordConfirm.classList.remove('active');
                togglePasswordConfirm.title = 'Mostrar confirmación';
            }
        });
    }
});
</script>
@endsection
