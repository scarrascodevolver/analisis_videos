@extends('layouts.app')

@section('main_content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-shield-alt text-danger mr-2"></i>Super Admin
                </h1>
                <p class="text-muted mb-0">Panel de administración global — RugbyKP</p>
            </div>
            <div>
                <a href="{{ route('super-admin.organizations.create') }}" class="btn btn-success mr-2">
                    <i class="fas fa-plus mr-1"></i> Nueva Organización
                </a>
                <a href="{{ route('super-admin.users') }}" class="btn btn-outline-primary">
                    <i class="fas fa-users mr-1"></i> Usuarios
                </a>
            </div>
        </div>
    </div>

    {{-- Alerta si hay orgs sin Bunny --}}
    @if($orgsWithoutBunny > 0)
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <i class="fas fa-exclamation-triangle fa-lg mr-3"></i>
        <div>
            <strong>{{ $orgsWithoutBunny }} organización(es) sin Bunny Stream configurado.</strong>
            Los videos de esas orgs no podrán subirse correctamente.
            <a href="{{ route('super-admin.organizations') }}" class="ml-2">Ver organizaciones →</a>
        </div>
    </div>
    @endif

    {{-- 4 Cards principales --}}
    <div class="row mb-4">
        {{-- Clubes --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Clubes</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $totalClubs }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Asociaciones --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Asociaciones</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $totalAsociaciones }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sitemap fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Usuarios --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Usuarios totales</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $totalUsers }}</div>
                            <small class="text-muted">
                                {{ $usersByRole['jugador'] ?? 0 }} jugadores ·
                                {{ ($usersByRole['analista'] ?? 0) + ($usersByRole['entrenador'] ?? 0) }} staff
                            </small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Videos + Storage --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Videos</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $totalVideos }}</div>
                            <small class="text-muted">
                                @php
                                    $gb = $totalStorageBytes / 1073741824;
                                    echo $gb >= 1 ? number_format($gb, 1) . ' GB' : number_format($totalStorageBytes / 1048576, 0) . ' MB';
                                @endphp
                                almacenados
                            </small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-video fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla orgs + Panel lateral --}}
    <div class="row">
        {{-- Tabla principal de organizaciones --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-building mr-2"></i>Todas las Organizaciones
                    </h6>
                    <a href="{{ route('super-admin.organizations') }}" class="btn btn-sm btn-outline-primary">
                        Gestionar →
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Organización</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Usuarios</th>
                                    <th class="text-center">Videos</th>
                                    <th class="text-center">Bunny</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orgStats as $org)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($org->logo_path)
                                                <img src="{{ asset('storage/' . $org->logo_path) }}"
                                                     class="img-circle mr-2"
                                                     style="width:28px;height:28px;object-fit:cover;">
                                            @else
                                                <i class="fas fa-building mr-2 text-muted"></i>
                                            @endif
                                            <div>
                                                <strong>{{ $org->name }}</strong>
                                                <br><small class="text-muted">{{ $org->created_at->format('d/m/Y') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($org->type === 'club')
                                            <span class="badge badge-primary">Club</span>
                                        @else
                                            <span class="badge badge-warning text-dark">Asociación</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $org->users_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $org->videos_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($org->bunny_library_id)
                                            <i class="fas fa-check-circle text-success" title="Library configurada"></i>
                                        @else
                                            <i class="fas fa-exclamation-circle text-danger" title="Sin Bunny library"></i>
                                        @endif
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
                                    <td colspan="6" class="text-center text-muted py-4">No hay organizaciones registradas</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel lateral --}}
        <div class="col-lg-4 mb-4">

            {{-- Últimas orgs creadas --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-plus-circle mr-2"></i>Últimas Organizaciones
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($recentOrgs as $org)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $org->name }}</strong>
                                <br><small class="text-muted">{{ $org->created_at->diffForHumans() }}</small>
                            </div>
                            @if($org->type === 'club')
                                <span class="badge badge-primary">Club</span>
                            @else
                                <span class="badge badge-warning text-dark">Asoc.</span>
                            @endif
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted">Sin organizaciones</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Desglose usuarios por rol --}}
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-tag mr-2"></i>Usuarios por Rol
                    </h6>
                </div>
                <div class="card-body">
                    @foreach(['jugador' => ['Jugadores','success'], 'analista' => ['Analistas','info'], 'entrenador' => ['Entrenadores','primary'], 'staff' => ['Staff','secondary']] as $role => $meta)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ $meta[0] }}</span>
                        <span class="badge badge-{{ $meta[1] }} badge-pill">{{ $usersByRole[$role] ?? 0 }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
