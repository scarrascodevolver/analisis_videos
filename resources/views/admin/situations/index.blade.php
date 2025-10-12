@extends('layouts.app')

@section('page_title', 'Gestión de Situaciones de Rugby')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item active">Situaciones Rugby</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card card-rugby">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Situaciones de Rugby</h3>
                    <a href="{{ route('admin.situations.create') }}" class="btn btn-rugby btn-sm">
                        <i class="fas fa-plus"></i> Nueva Situación
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($situations->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay situaciones creadas. Crea la primera situación ahora.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="rugby-green">
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="3%"></th>
                                    <th width="25%">Nombre</th>
                                    <th width="20%">Categoría</th>
                                    <th width="27%">Descripción</th>
                                    <th width="5%" class="text-center">Videos</th>
                                    <th width="5%" class="text-center">Estado</th>
                                    <th width="10%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($situations as $situation)
                                    <tr>
                                        <td>{{ $situation->id }}</td>
                                        <td class="text-center">
                                            <div style="width: 20px; height: 20px; background-color: {{ $situation->color }}; border-radius: 3px;"></div>
                                        </td>
                                        <td><strong>{{ $situation->name }}</strong></td>
                                        <td>{{ $situation->category }}</td>
                                        <td>{{ Str::limit($situation->description, 50) ?: '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-primary">{{ $situation->videos_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($situation->active)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Activo
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-times"></i> Inactivo
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.situations.edit', $situation) }}"
                                                   class="btn btn-warning btn-sm"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.situations.destroy', $situation) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar esta situación?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-danger btn-sm"
                                                            title="Eliminar"
                                                            {{ $situation->videos_count > 0 ? 'disabled' : '' }}>
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
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
