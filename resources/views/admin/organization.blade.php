@extends('layouts.app')

@section('page_title', 'Configuración de Organización')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item active">Organización</li>
@endsection


@section('main_content')
<div class="row">
    <div class="col-lg-8">
        <!-- Código de Invitación -->
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-ticket-alt mr-2"></i>
                    Código de Invitación para Jugadores
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Comparte este código con los jugadores de tu club para que puedan registrarse.
                    El código les permitirá unirse automáticamente a <strong>{{ $organization->name }}</strong>.
                </p>

                <!-- Código actual -->
                <div class="form-group">
                    <label>Código actual:</label>
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control text-center readonly-highlight"
                               id="invitationCode"
                               value="{{ $organization->invitation_code }}"
                               readonly
                               style="font-size: 1.5rem; letter-spacing: 3px; font-family: monospace;">
                        <div class="input-group-append">
                            <button class="btn btn-rugby" type="button" onclick="copyToClipboard('invitationCode', 'Código copiado!')">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Link completo -->
                <div class="form-group">
                    <label>Link de registro directo:</label>
                    <div class="input-group">
                        <input type="text" class="form-control readonly-link"
                               id="registerUrl"
                               value="{{ $registerUrl }}"
                               readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('registerUrl', 'Link copiado!')">
                                <i class="fas fa-link"></i> Copiar Link
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Los jugadores que usen este link tendrán el código pre-cargado.
                    </small>
                </div>

                <hr>

                <!-- Regenerar código -->
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Regenerar código automático</h6>
                        <small class="text-muted">Se generará un nuevo código aleatorio de 8 caracteres.</small>
                    </div>
                    <form action="{{ route('admin.organization.regenerate-code') }}" method="POST"
                          onsubmit="return confirm('¿Estás seguro? El código anterior dejará de funcionar.')">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-sync-alt"></i> Regenerar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Personalizar código -->
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-2"></i>
                    Personalizar Código
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Puedes crear un código personalizado más fácil de recordar (ej: TRONCOS2025, CLUBRUGBY).
                </p>

                <form action="{{ route('admin.organization.update-code') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="custom_code">Nuevo código:</label>
                        <input type="text"
                               class="form-control @error('invitation_code') is-invalid @enderror"
                               id="custom_code"
                               name="invitation_code"
                               value="{{ old('invitation_code', $organization->invitation_code) }}"
                               placeholder="Ej: TRONCOS2025"
                               maxlength="20"
                               style="text-transform: uppercase;">
                        @error('invitation_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Solo letras y números. Mínimo 4, máximo 20 caracteres.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Guardar Código Personalizado
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Info de la organización -->
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building mr-2"></i>
                    {{ $organization->name }}
                </h3>
            </div>
            <div class="card-body text-center">
                @if($organization->logo_path)
                    <img src="{{ asset('storage/' . $organization->logo_path) }}"
                         alt="{{ $organization->name }}"
                         class="img-fluid mb-3"
                         style="max-height: 150px;">
                @else
                    <i class="fas fa-building fa-5x text-muted mb-3"></i>
                @endif

                <div class="info-box bg-light mb-0">
                    <span class="info-box-icon bg-success"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Usuarios registrados</span>
                        <span class="info-box-number">{{ $organization->users()->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instrucciones -->
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title text-white">
                    <i class="fas fa-info-circle mr-2"></i>
                    Instrucciones
                </h3>
            </div>
            <div class="card-body">
                <ol class="pl-3 mb-0">
                    <li class="mb-2">Comparte el <strong>código</strong> o el <strong>link</strong> con tus jugadores.</li>
                    <li class="mb-2">Ellos irán a la página de registro e ingresarán el código.</li>
                    <li class="mb-2">Al registrarse, quedarán automáticamente en tu organización.</li>
                    <li>Podrás verlos en la sección de <a href="{{ route('admin.users.index') }}">Usuarios</a>.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Estilos para inputs readonly - Mayor especificidad para sobrescribir theme */
    #invitationCode.readonly-highlight {
        background-color: #1a1a1a !important;
        color: #00ff88 !important;
        border: 2px solid #D4A017 !important;
        font-weight: 600 !important;
    }

    #registerUrl.readonly-link {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border: 2px solid #4A6274 !important;
    }

    /* Mejorar contraste de labels */
    .card-body label {
        color: #ffffff !important;
        font-weight: 500;
    }

    /* Mejorar texto muted */
    .card-body .text-muted {
        color: #b8b8b8 !important;
    }

    .card-body small.text-muted {
        color: #a0a0a0 !important;
    }
</style>
@endpush

@push('scripts')
<script>
function copyToClipboard(elementId, message) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(element.value);

    // Mostrar toast o alert
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    } else {
        // Fallback: mostrar mensaje temporal
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-rugby', 'btn-outline-secondary');
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            if (elementId === 'invitationCode') {
                btn.classList.add('btn-rugby');
            } else {
                btn.classList.add('btn-outline-secondary');
            }
        }, 2000);
    }
}

// Convertir a mayúsculas mientras escribe
document.getElementById('custom_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>
@endpush
