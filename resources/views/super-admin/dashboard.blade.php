@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-shield-alt text-danger mr-2"></i>
                Super Admin Panel
            </h1>
            <p class="text-muted">Panel de administración global del sistema</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Organizaciones</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['total_organizations'] }}
                                <small class="text-success">({{ $stats['active_organizations'] }} activas)</small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Usuarios Totales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_users'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                Videos Totales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_videos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-video fa-2x text-gray-300"></i>
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
                                Acciones Rápidas</div>
                            <a href="{{ route('super-admin.organizations.create') }}" class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Nueva Org
                            </a>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cogs fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Organizaciones por estadísticas -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar mr-2"></i>Estadísticas por Organización
                    </h6>
                    <a href="{{ route('super-admin.organizations') }}" class="btn btn-sm btn-outline-primary">
                        Ver todas
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Organización</th>
                                    <th class="text-center">Usuarios</th>
                                    <th class="text-center">Videos</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orgStats as $org)
                                <tr>
                                    <td>
                                        @if($org->logo_path)
                                            <img src="{{ asset('storage/' . $org->logo_path) }}"
                                                 alt="{{ $org->name }}"
                                                 class="img-circle mr-2"
                                                 style="width: 30px; height: 30px; object-fit: cover;">
                                        @else
                                            <i class="fas fa-building mr-2 text-muted"></i>
                                        @endif
                                        {{ $org->name }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary">{{ $org->users_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $org->videos_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($org->is_active)
                                            <span class="badge badge-success">Activa</span>
                                        @else
                                            <span class="badge badge-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No hay organizaciones</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimos usuarios -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-user-plus mr-2"></i>Últimos Usuarios
                    </h6>
                    <a href="{{ route('super-admin.users') }}" class="btn btn-sm btn-outline-success">
                        Ver todos
                    </a>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse($stats['recent_users'] as $user)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong>{{ $user->name }}</strong>
                                <br>
                                <small class="text-muted">{{ $user->email }}</small>
                            </div>
                            <span class="badge badge-{{ $user->is_super_admin ? 'danger' : 'secondary' }}">
                                {{ $user->is_super_admin ? 'Super Admin' : $user->role }}
                            </span>
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted">No hay usuarios</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
