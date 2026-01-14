@extends('layouts.app')

@section('page_title', 'Categorías de Clips')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item active">Categorías de Clips</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card card-rugby">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-tags mr-2"></i>Categorías de Clips (Botonera)
                    </h3>
                    <a href="{{ route('admin.clip-categories.create') }}" class="btn btn-rugby btn-sm">
                        <i class="fas fa-plus"></i> Nueva Categoría
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i>
                    Estas categorías aparecen como botones en el reproductor de video para marcar clips rápidamente.
                    La <strong>tecla rápida</strong> permite marcar sin usar el mouse.
                </div>

                @if($categories->isEmpty())
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        No hay categorías de clips. Crea la primera para habilitar la botonera en el reproductor.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="categoriesTable">
                            <thead class="rugby-green">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="5%">Color</th>
                                    <th width="20%">Nombre</th>
                                    <th width="8%" class="text-center">Tecla</th>
                                    <th width="10%" class="text-center">Lead</th>
                                    <th width="10%" class="text-center">Lag</th>
                                    <th width="10%" class="text-center">Clips</th>
                                    <th width="10%" class="text-center">Estado</th>
                                    <th width="15%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr data-id="{{ $category->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="text-center">
                                            <div style="width: 30px; height: 30px; background-color: {{ $category->color }}; border-radius: 5px; margin: 0 auto;"></div>
                                        </td>
                                        <td><strong>{{ $category->name }}</strong></td>
                                        <td class="text-center">
                                            @if($category->hotkey)
                                                <kbd style="font-size: 1.1em;">{{ strtoupper($category->hotkey) }}</kbd>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $category->lead_seconds }}s</td>
                                        <td class="text-center">{{ $category->lag_seconds }}s</td>
                                        <td class="text-center">
                                            <span class="badge badge-primary">{{ $category->clips_count ?? $category->clips()->count() }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($category->is_active)
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
                                                <a href="{{ route('admin.clip-categories.edit', $category) }}"
                                                   class="btn btn-warning btn-sm"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.clip-categories.destroy', $category) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-danger btn-sm"
                                                            title="Eliminar"
                                                            {{ ($category->clips_count ?? $category->clips()->count()) > 0 ? 'disabled' : '' }}>
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
                        <h5>Vista previa de botonera:</h5>
                        <div class="d-flex flex-wrap gap-2 mt-2" style="gap: 8px;">
                            @foreach($categories->where('is_active', true) as $cat)
                                <button type="button"
                                        class="btn btn-sm"
                                        style="background-color: {{ $cat->color }}; color: white; min-width: 80px;">
                                    {{ $cat->name }}
                                    @if($cat->hotkey)
                                        <br><small>[{{ strtoupper($cat->hotkey) }}]</small>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
