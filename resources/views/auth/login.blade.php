@extends('layouts.auth')

@section('title', 'Iniciar Sesión - Los Troncos')

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
    background-color: #1e4d2b;
    color: white;
    border-color: #1e4d2b;
}

.password-toggle-btn:focus {
    box-shadow: none;
    border-color: #1e4d2b;
}

.password-toggle-btn.active {
    background-color: #1e4d2b;
    color: white;
    border-color: #1e4d2b;
}
</style>
@endsection

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-icon">
                <img src="{{ asset('logo_lt.png') }}" alt="Los Troncos Logo" style="width: 80px; height: 80px; object-fit: contain;">
            </div>
            <h3>Los Troncos</h3>
            <p>Sistema de Análisis Rugby</p>
        </div>
        
        <div class="auth-body">
            <h5 class="text-center mb-4">Iniciar Sesión</h5>
            
            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <!-- Email -->
                <div class="form-group">
                    <div class="input-group">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               name="email" placeholder="Correo electrónico" 
                               value="{{ old('email') }}" required autofocus>
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

                <!-- Password -->
                <div class="form-group">
                    <div class="input-group">
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" placeholder="Contraseña" required>
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

                <!-- Remember Me -->
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" 
                               name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">
                            Recordarme
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-rugby btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>

            <!-- Links -->
            <div class="text-center mt-4">
                @if (Route::has('password.request'))
                    <p class="mb-2">
                        <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                    </p>
                @endif
                <p class="mb-0">
                    ¿No tienes cuenta? 
                    <a href="{{ route('register') }}"><strong>Regístrate aquí</strong></a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    togglePassword.addEventListener('click', function() {
        // Toggle the type attribute
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Toggle the icon and button state
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
});
</script>
@endsection