@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-tags text-warning mr-2"></i>
                Planes de Suscripción
            </h1>
            <p class="text-muted">Gestiona los planes disponibles para las organizaciones</p>
        </div>
    </div>

    <!-- Navegación del panel -->
    <div class="row mb-4">
        <div class="col-12">
            <nav class="nav nav-pills">
                <a class="nav-link" href="{{ route('owner.payments.index') }}">
                    <i class="fas fa-chart-line mr-1"></i> Dashboard
                </a>
                <a class="nav-link" href="{{ route('owner.splits.index') }}">
                    <i class="fas fa-share-alt mr-1"></i> Splits
                </a>
                <a class="nav-link" href="{{ route('owner.partners.index') }}">
                    <i class="fas fa-users mr-1"></i> Socios
                </a>
                <a class="nav-link active" href="{{ route('owner.plans.index') }}">
                    <i class="fas fa-tags mr-1"></i> Planes
                </a>
            </nav>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    <div class="row mb-4">
        <div class="col-12 text-right">
            <a href="{{ route('owner.plans.create') }}" class="btn btn-success">
                <i class="fas fa-plus mr-1"></i> Nuevo Plan
            </a>
        </div>
    </div>

    <!-- Lista de planes -->
    <div class="row">
        @forelse($plans as $plan)
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow h-100 {{ !$plan->is_active ? 'border-secondary' : 'border-primary' }}">
                <div class="card-header py-3 d-flex justify-content-between align-items-center
                            {{ !$plan->is_active ? 'bg-secondary' : 'bg-primary' }} text-white">
                    <h5 class="m-0 font-weight-bold">{{ $plan->name }}</h5>
                    @if(!$plan->is_active)
                    <span class="badge badge-dark">Inactivo</span>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ $plan->description ?: 'Sin descripción' }}</p>

                    <div class="mb-3">
                        <span class="badge badge-info">{{ $plan->duration_months }} {{ $plan->duration_months == 1 ? 'mes' : 'meses' }}</span>
                    </div>

                    <h6 class="font-weight-bold mb-2">Precios:</h6>
                    <div class="row text-center">
                        <div class="col-6 mb-2">
                            <small class="text-muted d-block">CLP</small>
                            <strong>${{ number_format($plan->price_clp, 0, ',', '.') }}</strong>
                        </div>
                        <div class="col-6 mb-2">
                            <small class="text-muted d-block">USD</small>
                            <strong>${{ number_format($plan->price_usd, 2) }}</strong>
                        </div>
                        <div class="col-6 mb-2">
                            <small class="text-muted d-block">EUR</small>
                            <strong>€{{ number_format($plan->price_eur, 2) }}</strong>
                        </div>
                        <div class="col-6 mb-2">
                            <small class="text-muted d-block">PEN</small>
                            <strong>S/{{ number_format($plan->price_pen, 2) }}</strong>
                        </div>
                    </div>

                    @if($plan->features && count($plan->features) > 0)
                    <hr>
                    <h6 class="font-weight-bold mb-2">Características:</h6>
                    <ul class="list-unstyled mb-0">
                        @foreach($plan->features as $feature)
                        <li><i class="fas fa-check text-success mr-2"></i>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="{{ route('owner.plans.edit', $plan) }}"
                               class="btn btn-sm btn-primary" title="Editar">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <form method="POST" action="{{ route('owner.plans.toggle', $plan) }}"
                                  class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-{{ $plan->is_active ? 'warning' : 'success' }}">
                                    <i class="fas fa-{{ $plan->is_active ? 'pause' : 'play' }}"></i>
                                    {{ $plan->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('owner.plans.destroy', $plan) }}"
                              class="d-inline" onsubmit="return confirm('¿Eliminar este plan?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay planes creados</h5>
                    <p class="text-muted">Crea tu primer plan de suscripción para comenzar.</p>
                    <a href="{{ route('owner.plans.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Crear Primer Plan
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
