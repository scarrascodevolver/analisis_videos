@extends('layouts.app')

@section('page_title', 'Equipos Rivales')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Equipos Rivales</li>
@endsection

@section('main_content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: var(--color-primary, #005461); color: white;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">
                                <i class="fas fa-users"></i> Equipos Rivales
                            </h3>
                            <small class="d-block mt-1">Gestiona los equipos contra los que juega tu organización</small>
                        </div>
                        <a href="{{ route('rival-teams.create') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> Nuevo Rival
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($rivals->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay equipos rivales registrados</h5>
                            <p class="text-muted">Agrega equipos rivales para poder asociarlos a tus videos</p>
                            <a href="{{ route('rival-teams.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Primer Rival
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead style="background-color: #f8f9fa;">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Código</th>
                                        <th>Ciudad</th>
                                        <th class="text-center">Videos</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rivals as $rival)
                                    <tr>
                                        <td>
                                            <strong>{{ $rival->name }}</strong>
                                            @if($rival->notes)
                                                <i class="fas fa-info-circle text-muted ml-1"
                                                   data-toggle="tooltip"
                                                   title="{{ $rival->notes }}"></i>
                                            @endif
                                        </td>
                                        <td>
                                            @if($rival->code)
                                                <span class="badge badge-secondary">{{ $rival->code }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $rival->city ?? '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $rival->videos()->count() }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('rival-teams.edit', $rival) }}"
                                                   class="btn btn-sm btn-info"
                                                   data-toggle="tooltip"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('rival-teams.destroy', $rival) }}"
                                                      method="POST"
                                                      style="display:inline;"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este equipo rival?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-danger"
                                                            data-toggle="tooltip"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $rivals->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endpush
@endsection
