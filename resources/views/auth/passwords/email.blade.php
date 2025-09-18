@extends('layouts.auth')

@section('title', 'Recuperar Contraseña - Los Troncos')

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
            <h5 class="text-center mb-4">Recuperar Contraseña</h5>

            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    {{ session('status') }}
                </div>
            @endif

            <p class="text-muted text-center mb-4">
                Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
            </p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Email -->
                <div class="form-group">
                    <div class="input-group">
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" placeholder="Correo electrónico"
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

                <!-- Submit Button -->
                <button type="submit" class="btn btn-rugby btn-block btn-lg">
                    <i class="fas fa-paper-plane"></i> Enviar Enlace de Recuperación
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
