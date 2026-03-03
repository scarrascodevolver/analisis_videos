@extends('layouts.auth')

@section('title', 'Registrar Organización - Rugby Key Performance')

@section('css')
<style>
    .org-type-card {
        cursor: pointer;
        border: 2px solid rgba(255,255,255,0.15);
        border-radius: 12px;
        padding: 20px;
        transition: all 0.25s ease;
        background: rgba(255,255,255,0.05);
    }
    .org-type-card:hover {
        border-color: rgba(0,183,181,0.6);
        background: rgba(0,183,181,0.08);
    }
    .org-type-card.selected {
        border-color: #00B7B5;
        background: rgba(0,183,181,0.15);
    }
    .org-type-card .type-icon {
        font-size: 2rem;
        margin-bottom: 10px;
        display: block;
    }
    .org-type-card.selected .type-icon i {
        color: #fff !important;
    }
    .org-type-card h6 { margin-bottom: 4px; font-weight: 600; }
    .org-type-card p  { font-size: 0.78rem; opacity: 0.7; margin: 0; }

    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 20px;
    }
    .step-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        transition: background 0.3s;
    }
    .step-dot.active { background: #00B7B5; }
</style>
@endsection

@section('content')
<div class="auth-container">
    <div class="auth-card" style="max-width: 500px;">
        <div class="auth-header">
            <div class="logo-icon">
                <img src="{{ asset('logo.png') }}" alt="Rugby KP Logo" style="width: 192px; height: auto; object-fit: contain;">
            </div>
            <p class="text-muted small mt-1 mb-0">Registrá tu club o asociación</p>
        </div>

        <div class="auth-body">

            @if ($errors->any())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.organization.store') }}" enctype="multipart/form-data" id="orgRegisterForm">
                @csrf

                {{-- STEP 1: Tipo de organización --}}
                <div id="step1">
                    <h6 class="text-center mb-3" style="color:#00B7B5;">¿Qué tipo de organización sos?</h6>

                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="org-type-card text-center {{ old('org_type') === 'club' ? 'selected' : '' }}"
                                 onclick="selectType('club')">
                                <span class="type-icon">
                                    <i class="fas fa-shield-alt" style="color:#00B7B5;font-size:2rem;"></i>
                                </span>
                                <h6>Club</h6>
                                <p>Equipo de rugby que quiere analizar sus partidos</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="org-type-card text-center {{ old('org_type') === 'asociacion' ? 'selected' : '' }}"
                                 onclick="selectType('asociacion')">
                                <span class="type-icon">
                                    <i class="fas fa-trophy" style="color:#00B7B5;font-size:2rem;"></i>
                                </span>
                                <h6>Asociación</h6>
                                <p>Organiza torneos y comparte análisis con clubes</p>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="org_type" id="org_type" value="{{ old('org_type', '') }}">
                    @error('org_type')
                        <small class="text-danger d-block text-center mb-2">{{ $message }}</small>
                    @enderror

                    <button type="button" class="btn btn-rugby btn-block" id="nextStepBtn" onclick="nextStep()" disabled>
                        Continuar <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>

                {{-- STEP 2: Datos de la organización --}}
                <div id="step2" style="display:none;">
                    <h6 class="text-center mb-3" style="color:#00B7B5;">Datos de la organización</h6>

                    <div class="form-group">
                        <input type="text"
                               class="form-control @error('org_name') is-invalid @enderror"
                               name="org_name"
                               placeholder="Nombre de la organización *"
                               value="{{ old('org_name') }}"
                               required>
                        @error('org_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="text-muted small">Logo (opcional)</label>
                        <input type="file" class="form-control-file" name="logo" accept="image/*">
                    </div>

                    <h6 class="text-center mb-3 mt-4" style="color:#00B7B5;">Datos del administrador</h6>

                    <div class="form-group">
                        <input type="text"
                               class="form-control @error('admin_name') is-invalid @enderror"
                               name="admin_name"
                               placeholder="Tu nombre *"
                               value="{{ old('admin_name') }}"
                               required>
                        @error('admin_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <input type="email"
                               class="form-control @error('admin_email') is-invalid @enderror"
                               name="admin_email"
                               placeholder="Correo electrónico *"
                               value="{{ old('admin_email') }}"
                               required>
                        @error('admin_email')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <input type="password"
                               class="form-control @error('admin_password') is-invalid @enderror"
                               name="admin_password"
                               placeholder="Contraseña (mín. 8 caracteres) *"
                               required>
                        @error('admin_password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <input type="password"
                               class="form-control"
                               name="admin_password_confirmation"
                               placeholder="Confirmar contraseña *"
                               required>
                    </div>

                    <button type="submit" class="btn btn-rugby btn-block btn-lg mt-2">
                        <i class="fas fa-check-circle mr-1"></i> Crear Organización
                    </button>

                    <div class="text-center mt-3">
                        <a href="#" onclick="goBack()" class="text-muted small">
                            <i class="fas fa-arrow-left mr-1"></i> Volver
                        </a>
                    </div>
                </div>
            </form>

            <div class="text-center mt-4">
                <p class="mb-1 small">
                    <a href="{{ route('login') }}">¿Ya tenés cuenta? Iniciá sesión</a>
                </p>
                <p class="mb-0 small">
                    ¿Sos jugador? <a href="{{ route('register') }}">Registrate con código de invitación</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    function selectType(type) {
        document.getElementById('org_type').value = type;
        document.querySelectorAll('.org-type-card').forEach(c => c.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
        document.getElementById('nextStepBtn').disabled = false;
    }

    function nextStep() {
        if (!document.getElementById('org_type').value) return;
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
    }

    function goBack() {
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1').style.display = 'block';
    }

    // Si hay errores de validación (old input), ir directo al step2
    document.addEventListener('DOMContentLoaded', function() {
        @if (old('org_type'))
            nextStep();
        @endif
    });
</script>
@endsection
