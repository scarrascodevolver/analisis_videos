@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-dollar-sign text-success mr-2"></i>
                Panel de Pagos
            </h1>
            <p class="text-muted">Dashboard de ingresos y suscripciones</p>
        </div>
    </div>

    <!-- Navegación del panel -->
    <div class="row mb-4">
        <div class="col-12">
            <nav class="nav nav-pills">
                <a class="nav-link active" href="{{ route('owner.payments.index') }}">
                    <i class="fas fa-chart-line mr-1"></i> Dashboard
                </a>
                <a class="nav-link" href="{{ route('owner.splits.index') }}">
                    <i class="fas fa-share-alt mr-1"></i> Splits
                </a>
                @if($currentPartner->can_edit_settings)
                <a class="nav-link" href="{{ route('owner.partners.index') }}">
                    <i class="fas fa-users mr-1"></i> Socios
                </a>
                <a class="nav-link" href="{{ route('owner.plans.index') }}">
                    <i class="fas fa-tags mr-1"></i> Planes
                </a>
                @endif
            </nav>
        </div>
    </div>

    <!-- Filtro de fechas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-2">
                    <form method="GET" class="form-inline">
                        <label class="mr-2">Período:</label>
                        <input type="date" name="start_date" class="form-control form-control-sm mr-2"
                               value="{{ $startDate->format('Y-m-d') }}">
                        <span class="mr-2">a</span>
                        <input type="date" name="end_date" class="form-control form-control-sm mr-2"
                               value="{{ $endDate->format('Y-m-d') }}">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Ingresos Totales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($stats['total_revenue'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Ingresos del Período</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($stats['period_revenue'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total de Pagos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['total_payments'] }}
                                <small class="text-muted">({{ $stats['period_payments'] }} en período)</small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Splits Pendientes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($stats['pending_splits'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Gráfico de ingresos mensuales -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area mr-2"></i>Ingresos Mensuales (Últimos 6 meses)
                    </h6>
                </div>
                <div class="card-body">
                    @if($monthlyRevenue->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mes</th>
                                    <th class="text-right">Total</th>
                                    <th>Moneda</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyRevenue as $month)
                                <tr>
                                    <td>{{ $month->month }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format($month->total, 0, ',', '.') }}
                                    </td>
                                    <td>{{ $month->currency }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted my-4">No hay datos de ingresos todavía.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pagos recientes -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-receipt mr-2"></i>Pagos Recientes
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if($recentPayments->count() > 0)
                    <ul class="list-group list-group-flush">
                        @foreach($recentPayments->take(10) as $payment)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $payment->organization->name ?? 'N/A' }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {{ $payment->subscription->plan->name ?? 'N/A' }} -
                                        {{ $payment->paid_at?->format('d/m/Y') }}
                                    </small>
                                </div>
                                <span class="badge badge-success">
                                    {{ $payment->formatAmount() }}
                                </span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-center text-muted my-4">No hay pagos registrados.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
