@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-users text-primary mr-2"></i>
                Todos los Usuarios
            </h1>
            <p class="text-muted mb-0">Vista global de usuarios en todas las organizaciones</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('super-admin.users') }}" method="GET" class="row align-items-end">
                <div class="col-md-3 mb-2">
                    <label for="search">Buscar</label>
                    <input type="text"
                           class="form-control"
                           id="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Nombre o email...">
                </div>
                <div class="col-md-3 mb-2">
                    <label for="organization">Organización</label>
                    <select class="form-control" id="organization" name="organization">
                        <option value="">Todas</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ request('organization') == $org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="role">Rol</label>
                    <select class="form-control" id="role" name="role">
                        <option value="">Todos</option>
                        @foreach(\App\Models\User::ROLES as $role)
                            <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i> Filtrar
                    </button>
                    <a href="{{ route('super-admin.users') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Usuarios -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol Global</th>
                            <th>Organizaciones</th>
                            <th class="text-center">Super Admin</th>
                            <th>Registrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge badge-secondary">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td>
                                @if($user->organizations->count() > 0)
                                    @foreach($user->organizations as $org)
                                        <span class="badge badge-{{ $org->pivot->is_current ? 'primary' : 'light' }} mr-1"
                                              title="{{ $org->pivot->is_current ? 'Org. actual' : '' }}">
                                            {{ $org->name }}
                                            <small>({{ $org->pivot->role }})</small>
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted">Sin organización</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($user->is_super_admin)
                                    <span class="badge badge-danger">
                                        <i class="fas fa-shield-alt"></i> Sí
                                    </span>
                                @else
                                    <span class="text-muted">No</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $user->created_at->format('d/m/Y') }}</small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No se encontraron usuarios con los filtros aplicados</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <span class="text-muted">
                    Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de {{ $users->total() }} usuarios
                </span>
                {{ $users->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
