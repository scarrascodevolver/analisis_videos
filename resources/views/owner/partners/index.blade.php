@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-users text-primary mr-2"></i>
                Gestión de Socios
            </h1>
            <p class="text-muted">Administra los socios y su participación en los ingresos</p>
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
                <a class="nav-link active" href="{{ route('owner.partners.index') }}">
                    <i class="fas fa-users mr-1"></i> Socios
                </a>
                <a class="nav-link" href="{{ route('owner.plans.index') }}">
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

    <!-- Resumen de porcentajes -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Porcentaje Total Asignado</h6>
                            <h3 class="mb-0 {{ $totalPercentage > 100 ? 'text-danger' : ($totalPercentage == 100 ? 'text-success' : 'text-warning') }}">
                                {{ $totalPercentage }}%
                            </h3>
                        </div>
                        <div class="text-right">
                            <small class="text-muted">Disponible: {{ 100 - $totalPercentage }}%</small>
                            <div class="progress mt-2" style="width: 150px; height: 10px;">
                                <div class="progress-bar {{ $totalPercentage > 100 ? 'bg-danger' : 'bg-success' }}"
                                     style="width: {{ min($totalPercentage, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('owner.partners.create') }}" class="btn btn-success">
                <i class="fas fa-plus mr-1"></i> Nuevo Socio
            </a>
        </div>
    </div>

    <!-- Lista de socios -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list mr-2"></i>Socios Registrados
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th class="text-center">Porcentaje</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Permisos</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($partners as $partner)
                                <tr class="{{ !$partner->is_active ? 'table-secondary' : '' }}">
                                    <td>
                                        <strong>{{ $partner->name }}</strong>
                                    </td>
                                    <td>{{ $partner->email }}</td>
                                    <td>
                                        <span class="badge badge-{{ $partner->role === 'owner' ? 'danger' : 'info' }}">
                                            {{ $partner->role === 'owner' ? 'Propietario' : 'Socio' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary" style="font-size: 1rem;">
                                            {{ $partner->split_percentage }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($partner->is_active)
                                        <span class="badge badge-success">Activo</span>
                                        @else
                                        <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($partner->can_edit_settings)
                                        <span class="badge badge-warning">
                                            <i class="fas fa-edit"></i> Puede editar
                                        </span>
                                        @else
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-eye"></i> Solo ver
                                        </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('owner.partners.edit', $partner) }}"
                                           class="btn btn-sm btn-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($partner->email !== 'eliascarrascoaguayo@gmail.com')
                                        <form method="POST" action="{{ route('owner.partners.destroy', $partner) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('¿Eliminar este socio?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No hay socios registrados.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
