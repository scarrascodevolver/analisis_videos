@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-share-alt text-info mr-2"></i>
                Splits de Ingresos
            </h1>
            <p class="text-muted">Distribución de ingresos entre socios</p>
        </div>
    </div>

    <!-- Navegación del panel -->
    <div class="row mb-4">
        <div class="col-12">
            <nav class="nav nav-pills">
                <a class="nav-link" href="{{ route('owner.payments.index') }}">
                    <i class="fas fa-chart-line mr-1"></i> Dashboard
                </a>
                <a class="nav-link active" href="{{ route('owner.splits.index') }}">
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

    <!-- Resumen por Socio -->
    <div class="row mb-4">
        @foreach($partnerSummary as $summary)
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-user-tie mr-2 text-primary"></i>
                        {{ $summary['partner']->name }}
                        <span class="badge badge-{{ $summary['partner']->role === 'owner' ? 'danger' : 'info' }} ml-2">
                            {{ $summary['partner']->split_percentage }}%
                        </span>
                    </h5>
                    <div class="row text-center mt-3">
                        <div class="col-4">
                            <small class="text-muted d-block">Total</small>
                            <strong class="text-success">${{ number_format($summary['total_earned'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Pendiente</small>
                            <strong class="text-warning">${{ number_format($summary['pending_amount'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Transferido</small>
                            <strong class="text-info">${{ number_format($summary['transferred_amount'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-2">
                    <form method="GET" class="form-inline">
                        <label class="mr-2">Estado:</label>
                        <select name="status" class="form-control form-control-sm mr-3">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todos</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pendientes</option>
                            <option value="transferred" {{ $status === 'transferred' ? 'selected' : '' }}>Transferidos</option>
                        </select>

                        <label class="mr-2">Socio:</label>
                        <select name="partner_id" class="form-control form-control-sm mr-3">
                            <option value="">Todos</option>
                            @foreach($partners as $partner)
                            <option value="{{ $partner->id }}" {{ $partnerId == $partner->id ? 'selected' : '' }}>
                                {{ $partner->name }}
                            </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-sm btn-primary mr-2">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>

                        <a href="{{ route('owner.splits.export', request()->query()) }}" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Exportar CSV
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Splits -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list mr-2"></i>Detalle de Splits
                    </h6>
                    @if($currentPartner->can_edit_settings && $status === 'pending')
                    <form method="POST" action="{{ route('owner.splits.bulk-transfer') }}" id="bulkTransferForm">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success" id="bulkTransferBtn" disabled>
                            <i class="fas fa-check-double"></i> Marcar seleccionados como transferidos
                        </button>
                    </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    @if($currentPartner->can_edit_settings && $status === 'pending')
                                    <th width="40">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    @endif
                                    <th>Fecha Pago</th>
                                    <th>Socio</th>
                                    <th>Organización</th>
                                    <th class="text-center">%</th>
                                    <th class="text-right">Monto</th>
                                    <th class="text-center">Estado</th>
                                    @if($currentPartner->can_edit_settings)
                                    <th class="text-center">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($splits as $split)
                                <tr>
                                    @if($currentPartner->can_edit_settings && $status === 'pending')
                                    <td>
                                        @if($split->status === 'pending')
                                        <input type="checkbox" name="split_ids[]" value="{{ $split->id }}"
                                               class="split-checkbox" form="bulkTransferForm">
                                        @endif
                                    </td>
                                    @endif
                                    <td>{{ $split->payment->paid_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <strong>{{ $split->partner->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $split->partner->email }}</small>
                                    </td>
                                    <td>{{ $split->payment->organization->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $split->percentage_applied }}%</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format($split->amount, 0, ',', '.') }} {{ $split->currency }}
                                    </td>
                                    <td class="text-center">
                                        @if($split->status === 'pending')
                                        <span class="badge badge-warning">Pendiente</span>
                                        @else
                                        <span class="badge badge-success">Transferido</span>
                                        @if($split->transferred_at)
                                        <br><small class="text-muted">{{ $split->transferred_at->format('d/m/Y') }}</small>
                                        @endif
                                        @endif
                                    </td>
                                    @if($currentPartner->can_edit_settings)
                                    <td class="text-center">
                                        @if($split->status === 'pending')
                                        <form method="POST" action="{{ route('owner.splits.transfer', $split) }}"
                                              class="d-inline" onsubmit="return confirm('¿Marcar como transferido?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Marcar transferido">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ $currentPartner->can_edit_settings ? 8 : 6 }}"
                                        class="text-center text-muted py-4">
                                        No hay splits registrados.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($splits->hasPages())
                <div class="card-footer">
                    {{ $splits->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.split-checkbox');
    const bulkBtn = document.getElementById('bulkTransferBtn');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkBtn();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkBtn);
    });

    function updateBulkBtn() {
        if (bulkBtn) {
            const checked = document.querySelectorAll('.split-checkbox:checked').length;
            bulkBtn.disabled = checked === 0;
            bulkBtn.innerHTML = checked > 0
                ? `<i class="fas fa-check-double"></i> Marcar ${checked} como transferidos`
                : `<i class="fas fa-check-double"></i> Marcar seleccionados como transferidos`;
        }
    }
});
</script>
@endpush
@endsection
