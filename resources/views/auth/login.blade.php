@extends('layouts.auth')

@section('title', 'Iniciar Sesión - Los Troncos')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-icon">
                <i class="fas fa-shield-alt"></i>
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