@extends('layouts.app')

@section('page_title', 'Gestión de Categorías')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item active">Categorías</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card card-rugby">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Categorías de Usuario</h3>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-rugby btn-sm">
                        <i class="fas fa-plus"></i> Nueva Categoría
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($categories->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay categorías creadas. Crea la primera categoría ahora.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="rugby-green">
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="25%">Nombre</th>
                                    <th width="35%">Descripción</th>
                                    <th width="10%" class="text-center">Usuarios</th>
                                    <th width="10%" class="text-center">Videos</th>
                                    <th width="15%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr>
                                        <td>{{ $category->id }}</td>
                                        <td><strong>{{ $category->name }}</strong></td>
                                        <td>{{ $category->description ?: '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $category->user_profiles_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-primary">{{ $category->videos_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.categories.edit', $category) }}"
                                                   class="btn btn-warning btn-sm"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.categories.destroy', $category) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-danger btn-sm"
                                                            title="Eliminar"
                                                            {{ $category->user_profiles_count > 0 || $category->videos_count > 0 ? 'disabled' : '' }}>
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
