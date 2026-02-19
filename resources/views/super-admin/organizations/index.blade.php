@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-building text-primary mr-2"></i>
                    Organizaciones
                </h1>
                <p class="text-muted mb-0">Gestión de todas las organizaciones del sistema</p>
            </div>
            <a href="{{ route('super-admin.organizations.create') }}" class="btn btn-success">
                <i class="fas fa-plus mr-1"></i> Nueva Organización
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 60px;">Logo</th>
                            <th>Nombre</th>
                            <th class="text-center">Tipo</th>
                            <th>Slug</th>
                            <th class="text-center">Usuarios</th>
                            <th class="text-center">Videos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width: 200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($organizations as $org)
                        <tr>
                            <td class="text-center">
                                @if($org->logo_path)
                                    <img src="{{ asset('storage/' . $org->logo_path) }}"
                                         alt="{{ $org->name }}"
                                         class="img-thumbnail"
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <i class="fas fa-building fa-2x text-muted"></i>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $org->name }}</strong>
                                <br>
                                <small class="text-muted">Creada: {{ $org->created_at->format('d/m/Y') }}</small>
                            </td>
                            <td class="text-center">
                                @if($org->type === 'club')
                                    <span class="badge badge-primary">Club</span>
                                @else
                                    <span class="badge badge-warning text-dark">Asociación</span>
                                @endif
                            </td>
                            <td><code>{{ $org->slug }}</code></td>
                            <td class="text-center">
                                <span class="badge badge-primary badge-pill">{{ $org->users_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info badge-pill">{{ $org->videos_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($org->is_active)
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i> Activa
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fas fa-pause"></i> Inactiva
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('super-admin.organizations.edit', $org) }}"
                                       class="btn btn-outline-primary"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('super-admin.organizations.assign-admin', $org) }}"
                                       class="btn btn-outline-success"
                                       title="Gestionar Usuarios">
                                        <i class="fas fa-users-cog"></i>
                                    </a>
                                    @if($org->users_count == 0 && $org->videos_count == 0)
                                    <form action="{{ route('super-admin.organizations.destroy', $org) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar esta organización?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay organizaciones registradas</p>
                                <a href="{{ route('super-admin.organizations.create') }}" class="btn btn-success">
                                    <i class="fas fa-plus mr-1"></i> Crear primera organización
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $organizations->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
