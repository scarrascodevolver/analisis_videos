@extends('layouts.auth')

@section('title', 'Nueva Contraseña - Los Troncos')

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
                               id="password-confirm" name="password_confirmation" placeholder="Confirmar nueva contraseña"
                               required autocomplete="new-password">
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
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
