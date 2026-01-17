@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-user-plus text-primary mr-2"></i>
                {{ $isEdit ? 'Editar Socio' : 'Nuevo Socio' }}
            </h1>
            <p class="text-muted">{{ $isEdit ? 'Modifica los datos del socio' : 'Agrega un nuevo socio al sistema' }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle mr-2"></i>Datos del Socio
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ $isEdit ? route('owner.partners.update', $partner) : route('owner.partners.store') }}">
                        @csrf
                        @if($isEdit) @method('PUT') @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nombre Completo <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $partner->name ?? '') }}" required>
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $partner->email ?? '') }}" required>
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Debe coincidir con el email de login del usuario.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role">Rol <span class="text-danger">*</span></label>
                                    <select name="role" id="role"
                                            class="form-control @error('role') is-invalid @enderror" required>
                                        <option value="partner" {{ old('role', $partner->role ?? '') === 'partner' ? 'selected' : '' }}>
                                            Socio
                                        </option>
                                        <option value="owner" {{ old('role', $partner->role ?? '') === 'owner' ? 'selected' : '' }}>
                                            Propietario
                                        </option>
                                    </select>
                                    @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="split_percentage">Porcentaje de Split <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="split_percentage" id="split_percentage"
                                               class="form-control @error('split_percentage') is-invalid @enderror"
                                               value="{{ old('split_percentage', $partner->split_percentage ?? 0) }}"
                                               min="0" max="100" step="0.01" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @error('split_percentage')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-muted mb-3">Datos de Pago (Opcionales)</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="paypal_email">
                                        <i class="fab fa-paypal text-primary"></i> Email de PayPal
                                    </label>
                                    <input type="email" name="paypal_email" id="paypal_email"
                                           class="form-control @error('paypal_email') is-invalid @enderror"
                                           value="{{ old('paypal_email', $partner->paypal_email ?? '') }}">
                                    @error('paypal_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mercadopago_email">
                                        <i class="fas fa-handshake text-info"></i> Email de Mercado Pago
                                    </label>
                                    <input type="email" name="mercadopago_email" id="mercadopago_email"
                                           class="form-control @error('mercadopago_email') is-invalid @enderror"
                                           value="{{ old('mercadopago_email', $partner->mercadopago_email ?? '') }}">
                                    @error('mercadopago_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-muted mb-3">Permisos</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="custom-control custom-switch mb-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" class="custom-control-input" id="is_active"
                                           name="is_active" value="1"
                                           {{ old('is_active', $partner->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active">
                                        <strong>Socio Activo</strong>
                                        <br><small class="text-muted">Solo socios activos reciben splits</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-switch mb-3">
                                    <input type="hidden" name="can_edit_settings" value="0">
                                    <input type="checkbox" class="custom-control-input" id="can_edit_settings"
                                           name="can_edit_settings" value="1"
                                           {{ old('can_edit_settings', $partner->can_edit_settings ?? false) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="can_edit_settings">
                                        <strong>Puede Editar Configuración</strong>
                                        <br><small class="text-muted">Puede gestionar socios, planes y splits</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('owner.partners.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Guardar Cambios' : 'Crear Socio' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="m-0"><i class="fas fa-info-circle mr-2"></i>Información</h6>
                </div>
                <div class="card-body">
                    <p class="small">
                        <strong>Porcentaje de Split:</strong> Determina qué porcentaje de cada pago
                        corresponde a este socio. La suma de todos los socios activos no puede
                        exceder el 100%.
                    </p>
                    <p class="small">
                        <strong>Permisos de Edición:</strong> Solo los socios con este permiso
                        pueden crear/editar otros socios, planes y marcar splits como transferidos.
                    </p>
                    <p class="small mb-0">
                        <strong>Email:</strong> El email debe coincidir con el email de inicio
                        de sesión del usuario para que pueda acceder al panel.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
