@extends('layouts.app')

@section('page_title', 'Gestión de Divisiones')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item active">Divisiones</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card card-rugby">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Divisiones de Competencia</h3>
                <a href="{{ route('admin.divisions.create') }}" class="btn btn-rugby btn-sm">
                    <i class="fas fa-plus"></i> Nueva División
                </a>
            </div>
            <div class="card-body">
                @if($divisions->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay divisiones creadas. Crea la primera división ahora.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="rugby-green">
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="25%">Nombre</th>
                                    <th width="45%">Descripción</th>
                                    <th width="10%" class="text-center">Videos</th>
                                    <th width="5%" class="text-center">Estado</th>
                                    <th width="10%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($divisions as $division)
                                    <tr>
                                        <td>{{ $division->id }}</td>
                                        <td><strong>{{ $division->name }}</strong></td>
                                        <td>{{ $division->description ?: '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-primary">{{ $division->videos_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($division->active)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-times"></i>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.divisions.edit', $division) }}"
                                               class="btn btn-warning btn-sm"
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.divisions.destroy', $division) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar esta división?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-danger btn-sm"
                                                        title="Eliminar"
                                                        {{ $division->videos_count > 0 ? 'disabled' : '' }}>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
