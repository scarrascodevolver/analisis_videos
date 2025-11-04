@extends('layouts.app')

@section('page_title', 'Gestión de Usuarios')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Mantenedor</a></li>
    <li class="breadcrumb-item active">Usuarios</li>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card card-rugby">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Usuarios del Sistema</h3>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-rugby btn-sm">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Buscador y Filtros -->
                <form action="{{ route('admin.users.index') }}" method="GET" class="mb-3" id="filter-form">
                    <div class="row">
                        <!-- Buscador -->
                        <div class="col-md-4 mb-2">
                            <div class="input-group">
                                <input type="text"
                                       id="user-search"
                                       name="search"
                                       class="form-control"
                                       placeholder="Buscar por nombre o email..."
                                       value="{{ request('search') }}"
                                       autocomplete="off">
                                <div class="input-group-append">
                                    <span class="input-group-text bg-white" id="search-icon">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Búsqueda en tiempo real
                            </small>
                        </div>

                        <!-- Filtro por Rol -->
                        <div class="col-md-3 mb-2">
                            <select id="role-filter" name="role" class="form-control">
                                <option value="">Todos los roles</option>
                                <option value="jugador" {{ request('role') == 'jugador' ? 'selected' : '' }}>Jugador</option>
                                <option value="entrenador" {{ request('role') == 'entrenador' ? 'selected' : '' }}>Entrenador</option>
                                <option value="analista" {{ request('role') == 'analista' ? 'selected' : '' }}>Analista</option>
                                <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="director_club" {{ request('role') == 'director_club' ? 'selected' : '' }}>Director Club</option>
                                <option value="director_tecnico" {{ request('role') == 'director_tecnico' ? 'selected' : '' }}>Director Técnico</option>
                            </select>
                        </div>

                        <!-- Filtro por Categoría -->
                        <div class="col-md-3 mb-2">
                            <select id="category-filter" name="category_id" class="form-control">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Botón limpiar filtros -->
                        <div class="col-md-2 mb-2">
                            <button type="button" id="clear-filters" class="btn btn-secondary btn-block" style="display: none;">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Loading State -->
                <div id="loading-state" class="text-center py-4" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-2x text-rugby"></i>
                    <p class="mt-2 text-muted">Buscando usuarios...</p>
                </div>

                <div id="table-container" class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="rugby-green">
                            <tr>
                                <th width="25%">Nombre</th>
                                <th width="25%">Email</th>
                                <th width="15%">Rol</th>
                                <th width="15%">Categoría</th>
                                <th width="10%" class="text-center">Registro</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>
                                        @if($user->profile && $user->profile->avatar)
                                            <img src="{{ asset('storage/' . $user->profile->avatar) }}"
                                                 alt="Avatar"
                                                 class="img-circle mr-2"
                                                 style="width: 25px; height: 25px; object-fit: cover;">
                                        @endif
                                        <strong>{{ $user->name }}</strong>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @php
                                            $roleColors = [
                                                'jugador' => 'primary',
                                                'entrenador' => 'success',
                                                'analista' => 'warning',
                                                'staff' => 'info',
                                                'director_club' => 'danger',
                                                'director_tecnico' => 'secondary'
                                            ];
                                            $color = $roleColors[$user->role] ?? 'dark';
                                        @endphp
                                        <span class="badge badge-{{ $color }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->profile && $user->profile->category)
                                            <span class="badge badge-info">
                                                {{ $user->profile->category->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ $user->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.users.show', $user) }}"
                                               class="btn btn-info btn-sm"
                                               title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="btn btn-warning btn-sm"
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($user->id !== auth()->id())
                                                <form action="{{ route('admin.users.destroy', $user) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-danger btn-sm"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div class="mt-3" id="pagination-container">
                        {{ $users->links('custom.pagination') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let searchTimeout;
    const $searchInput = $('#user-search');
    const $roleFilter = $('#role-filter');
    const $categoryFilter = $('#category-filter');
    const $loadingState = $('#loading-state');
    const $tableContainer = $('#table-container');
    const $clearButton = $('#clear-filters');
    const $searchIcon = $('#search-icon i');

    // Función para realizar la búsqueda
    function performSearch() {
        const search = $searchInput.val().trim();
        const role = $roleFilter.val();
        const categoryId = $categoryFilter.val();

        // Mostrar u ocultar botón limpiar
        if (search || role || categoryId) {
            $clearButton.show();
        } else {
            $clearButton.hide();
        }

        // Mostrar loading
        $loadingState.show();
        $tableContainer.css('opacity', '0.5');
        $searchIcon.removeClass('fa-search').addClass('fa-spinner fa-spin');

        // Hacer petición AJAX
        $.ajax({
            url: '{{ route('admin.users.index') }}',
            method: 'GET',
            data: {
                search: search,
                role: role,
                category_id: categoryId,
                ajax: 1
            },
            success: function(response) {
                $tableContainer.html(response);
                $loadingState.hide();
                $tableContainer.css('opacity', '1');
                $searchIcon.removeClass('fa-spinner fa-spin').addClass('fa-search');
            },
            error: function() {
                $loadingState.hide();
                $tableContainer.css('opacity', '1');
                $searchIcon.removeClass('fa-spinner fa-spin').addClass('fa-search');
                alert('Error al buscar usuarios. Por favor, intenta nuevamente.');
            }
        });
    }

    // Búsqueda con debounce en el input
    $searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 400);
    });

    // Búsqueda inmediata al cambiar filtros
    $roleFilter.on('change', performSearch);
    $categoryFilter.on('change', performSearch);

    // Limpiar filtros
    $clearButton.on('click', function() {
        $searchInput.val('');
        $roleFilter.val('');
        $categoryFilter.val('');
        performSearch();
    });

    // Delegación de eventos para paginación AJAX
    $(document).on('click', '#pagination-container .pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');

        $loadingState.show();
        $tableContainer.css('opacity', '0.5');

        $.ajax({
            url: url,
            method: 'GET',
            data: {
                search: $searchInput.val(),
                role: $roleFilter.val(),
                category_id: $categoryFilter.val(),
                ajax: 1
            },
            success: function(response) {
                $tableContainer.html(response);
                $loadingState.hide();
                $tableContainer.css('opacity', '1');

                // Scroll suave hacia arriba
                $('html, body').animate({
                    scrollTop: $tableContainer.offset().top - 100
                }, 300);
            },
            error: function() {
                $loadingState.hide();
                $tableContainer.css('opacity', '1');
            }
        });
    });

    // Mostrar botón limpiar si hay filtros activos al cargar
    if ($searchInput.val() || $roleFilter.val() || $categoryFilter.val()) {
        $clearButton.show();
    }
});
</script>

<style>
.text-rugby {
    color: #1e4d2b !important;
}
</style>
@endpush

@endsection
