@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-tag text-warning mr-2"></i>
                {{ $isEdit ? 'Editar Plan' : 'Nuevo Plan' }}
            </h1>
            <p class="text-muted">{{ $isEdit ? 'Modifica los datos del plan' : 'Crea un nuevo plan de suscripción' }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle mr-2"></i>Datos del Plan
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ $isEdit ? route('owner.plans.update', $plan) : route('owner.plans.store') }}">
                        @csrf
                        @if($isEdit) @method('PUT') @endif

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name">Nombre del Plan <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $plan->name ?? '') }}" required>
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="slug">Slug (URL)</label>
                                    <input type="text" name="slug" id="slug"
                                           class="form-control @error('slug') is-invalid @enderror"
                                           value="{{ old('slug', $plan->slug ?? '') }}"
                                           placeholder="auto-generado">
                                    @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="description">Descripción</label>
                                    <textarea name="description" id="description" rows="2"
                                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $plan->description ?? '') }}</textarea>
                                    @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="duration_months">Duración <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="duration_months" id="duration_months"
                                               class="form-control @error('duration_months') is-invalid @enderror"
                                               value="{{ old('duration_months', $plan->duration_months ?? 1) }}"
                                               min="1" max="24" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">meses</span>
                                        </div>
                                    </div>
                                    @error('duration_months')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-muted mb-3"><i class="fas fa-dollar-sign mr-2"></i>Precios por Moneda</h6>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price_clp">
                                        <span class="flag-icon flag-icon-cl mr-1"></span> CLP (Chile)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="price_clp" id="price_clp"
                                               class="form-control @error('price_clp') is-invalid @enderror"
                                               value="{{ old('price_clp', $plan->price_clp ?? 0) }}"
                                               min="0" step="1" required>
                                    </div>
                                    @error('price_clp')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price_usd">
                                        <span class="flag-icon flag-icon-us mr-1"></span> USD
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="price_usd" id="price_usd"
                                               class="form-control @error('price_usd') is-invalid @enderror"
                                               value="{{ old('price_usd', $plan->price_usd ?? 0) }}"
                                               min="0" step="0.01" required>
                                    </div>
                                    @error('price_usd')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price_eur">
                                        <span class="flag-icon flag-icon-eu mr-1"></span> EUR
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">€</span>
                                        </div>
                                        <input type="number" name="price_eur" id="price_eur"
                                               class="form-control @error('price_eur') is-invalid @enderror"
                                               value="{{ old('price_eur', $plan->price_eur ?? 0) }}"
                                               min="0" step="0.01" required>
                                    </div>
                                    @error('price_eur')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price_pen">
                                        <span class="flag-icon flag-icon-pe mr-1"></span> PEN (Perú)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">S/</span>
                                        </div>
                                        <input type="number" name="price_pen" id="price_pen"
                                               class="form-control @error('price_pen') is-invalid @enderror"
                                               value="{{ old('price_pen', $plan->price_pen ?? 0) }}"
                                               min="0" step="0.01" required>
                                    </div>
                                    @error('price_pen')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price_brl">
                                        <span class="flag-icon flag-icon-br mr-1"></span> BRL (Brasil)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">R$</span>
                                        </div>
                                        <input type="number" name="price_brl" id="price_brl"
                                               class="form-control @error('price_brl') is-invalid @enderror"
                                               value="{{ old('price_brl', $plan->price_brl ?? 0) }}"
                                               min="0" step="0.01" required>
                                    </div>
                                    @error('price_brl')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-muted mb-3"><i class="fas fa-list-check mr-2"></i>Características del Plan</h6>

                        <div id="features-container">
                            @php
                                $features = old('features', $plan->features ?? []);
                                if (empty($features)) $features = [''];
                            @endphp
                            @foreach($features as $index => $feature)
                            <div class="input-group mb-2 feature-row">
                                <input type="text" name="features[]"
                                       class="form-control" placeholder="Ej: Videos ilimitados"
                                       value="{{ $feature }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-danger remove-feature"
                                            {{ count($features) <= 1 ? 'disabled' : '' }}>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-feature">
                            <i class="fas fa-plus mr-1"></i> Agregar Característica
                        </button>

                        <hr>

                        <div class="custom-control custom-switch mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="custom-control-input" id="is_active"
                                   name="is_active" value="1"
                                   {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">
                                <strong>Plan Activo</strong>
                                <br><small class="text-muted">Solo planes activos están disponibles para suscripción</small>
                            </label>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('owner.plans.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Guardar Cambios' : 'Crear Plan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="m-0"><i class="fas fa-lightbulb mr-2"></i>Consejos</h6>
                </div>
                <div class="card-body">
                    <p class="small">
                        <strong>Precios Multi-Moneda:</strong> Define precios en diferentes monedas
                        para facilitar el pago a clientes de distintos países.
                    </p>
                    <p class="small">
                        <strong>Características:</strong> Lista las funcionalidades incluidas
                        en el plan. Estas se mostrarán en la página de precios.
                    </p>
                    <p class="small mb-0">
                        <strong>Duración:</strong> Los planes pueden ser mensuales (1),
                        semestrales (6) o anuales (12). También puedes crear duraciones personalizadas.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mt-3 border-info">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="m-0"><i class="fas fa-calculator mr-2"></i>Conversiones Sugeridas</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">Tasas aproximadas (actualizar según mercado):</p>
                    <ul class="small list-unstyled mb-0">
                        <li>1 USD ≈ 950 CLP</li>
                        <li>1 USD ≈ 0.92 EUR</li>
                        <li>1 USD ≈ 3.75 PEN</li>
                        <li>1 USD ≈ 5.00 BRL</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('features-container');
    const addBtn = document.getElementById('add-feature');

    addBtn.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'input-group mb-2 feature-row';
        row.innerHTML = `
            <input type="text" name="features[]" class="form-control" placeholder="Ej: Videos ilimitados">
            <div class="input-group-append">
                <button type="button" class="btn btn-danger remove-feature">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
        updateRemoveButtons();
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-feature')) {
            e.target.closest('.feature-row').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        const rows = container.querySelectorAll('.feature-row');
        rows.forEach(row => {
            const btn = row.querySelector('.remove-feature');
            btn.disabled = rows.length <= 1;
        });
    }
});
</script>
@endpush
@endsection
