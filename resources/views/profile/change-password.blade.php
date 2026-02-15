@extends('layouts.app')

@section('page_title', 'Cambiar Contraseña')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('profile.show') }}">Perfil</a></li>
<li class="breadcrumb-item active">Cambiar Contraseña</li>
@endsection

@section('main_content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header rugby-green">
                <h3 class="card-title">
                    <i class="fas fa-key"></i> Cambiar Contraseña
                </h3>
            </div>
            <form action="{{ route('profile.password.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota:</strong> Tu nueva contraseña debe tener al menos 8 caracteres.
                    </div>

                    <div class="form-group">
                        <label for="current_password">
                            Contraseña Actual <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password"
                                   name="current_password"
                                   required
                                   placeholder="Ingresa tu contraseña actual">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="form-group">
                        <label for="new_password">
                            Nueva Contraseña <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control @error('new_password') is-invalid @enderror"
                                   id="new_password"
                                   name="new_password"
                                   required
                                   minlength="8"
                                   placeholder="Ingresa tu nueva contraseña (mín. 8 caracteres)">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('new_password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <small class="form-text text-muted">
                            Debe contener al menos 8 caracteres
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="new_password_confirmation">
                            Confirmar Nueva Contraseña <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control"
                                   id="new_password_confirmation"
                                   name="new_password_confirmation"
                                   required
                                   minlength="8"
                                   placeholder="Confirma tu nueva contraseña">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password_confirmation">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Ingresa la misma contraseña nuevamente
                        </small>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <a href="{{ route('profile.show') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Actualizar Contraseña
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Tips Card -->
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-shield-alt"></i> Consejos de Seguridad
                </h3>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Usa una contraseña única que no utilices en otros sitios</li>
                    <li>Combina letras mayúsculas, minúsculas, números y símbolos</li>
                    <li>Evita usar información personal fácil de adivinar</li>
                    <li>Cambia tu contraseña regularmente</li>
                    <li>No compartas tu contraseña con nadie</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const targetId = $(this).data('target');
        const input = $('#' + targetId);
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Password strength indicator (optional)
    $('#new_password').on('keyup', function() {
        const password = $(this).val();
        let strength = 0;

        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        // You can add a visual indicator here if desired
        console.log('Password strength:', strength);
    });

    // Validate password confirmation matches
    $('form').on('submit', function(e) {
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#new_password_confirmation').val();

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Las contraseñas no coinciden. Por favor verifica e intenta nuevamente.');
            return false;
        }
    });
});
</script>
@endsection
