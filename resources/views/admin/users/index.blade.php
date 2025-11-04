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
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="card-title mb-0">Usuarios del Sistema</h3>
                    <div>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-rugby btn-sm">
                            <i class="fas fa-user-plus"></i> Nuevo Usuario
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="role-filter">Filtrar por Rol:</label>
                        <select id="role-filter" class="form-control">
                            <option value="">Todos los roles</option>
                            <option value="jugador">Jugador</option>
                            <option value="entrenador">Entrenador</option>
                            <option value="analista">Analista</option>
                            <option value="staff">Staff</option>
                            <option value="director_club">Director Club</option>
                            <option value="director_tecnico">Director Técnico</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="category-filter">Filtrar por Categoría:</label>
                        <select id="category-filter" class="form-control">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button id="clear-filters" class="btn btn-secondary btn-block">
                            <i class="fas fa-times"></i> Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- Tabla DataTables -->
                <div class="table-responsive">
                    <table id="users-table" class="table table-bordered table-hover w-100">
                        <thead class="rugby-green">
                            <tr>
                                <th width="5%" class="text-center">Foto</th>
                                <th width="20%">Nombre</th>
                                <th width="20%">Email</th>
                                <th width="12%">Rol</th>
                                <th width="13%">Categoría</th>
                                <th width="10%" class="text-center">Registro</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">

<style>
.rugby-green {
    background-color: #1e4d2b !important;
    color: white !important;
}

.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
}

/* Paginación estilo rugby (sobrescribir Bootstrap) */
#users-table_paginate .pagination .page-link {
    color: #1e4d2b !important;
    border-color: #dee2e6 !important;
    background-color: white !important;
}

#users-table_paginate .pagination .page-item.active .page-link {
    background-color: #1e4d2b !important;
    border-color: #1e4d2b !important;
    color: white !important;
}

#users-table_paginate .pagination .page-link:hover {
    color: #28a745 !important;
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
}

#users-table_paginate .pagination .page-item.disabled .page-link {
    color: #6c757d !important;
    background-color: #fff !important;
    border-color: #dee2e6 !important;
}

.dt-buttons {
    margin-bottom: 1rem;
}

.dt-button {
    background-color: #1e4d2b !important;
    border-color: #1e4d2b !important;
    color: white !important;
}

.dt-button:hover {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
}

/* Alinear buscador a la izquierda */
.dataTables_wrapper .dataTables_filter {
    text-align: left !important;
    margin-bottom: 1rem;
}

/* Centrar botones de exportación */
.dataTables_wrapper .dt-buttons {
    text-align: center !important;
    margin-bottom: 1rem;
}

/* Alinear selector de registros a la derecha */
.dataTables_wrapper .dataTables_length {
    text-align: right !important;
    margin-bottom: 1rem;
}

/* Remover comportamiento de btn-group para separar botones */
.dt-buttons.btn-group {
    display: flex !important;
    gap: 1rem !important;
    justify-content: center !important;
}

/* Espaciado entre botones */
.dt-buttons .dt-button {
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding: 0.5rem 1rem !important;
}

/* Redondear esquinas individuales */
.dt-buttons .dt-button {
    border-radius: 0.375rem !important;
}
</style>
@endpush

@push('scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    const table = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.users.index') }}',
            data: function(d) {
                d.role = $('#role-filter').val();
                d.category_id = $('#category-filter').val();
            }
        },
        columns: [
            { data: 'avatar', name: 'avatar', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'role_badge', name: 'role', orderable: true },
            { data: 'category_badge', name: 'profile.category.name', orderable: false },
            { data: 'created_at', name: 'created_at', className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'asc']],
        pageLength: 15,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        dom: '<"row"<"col-sm-12 col-md-4"f><"col-sm-12 col-md-4 text-center"B><"col-sm-12 col-md-4"l>>rtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn-sm',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn-sm',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5]
                },
                customize: function(doc) {
                    doc.styles.title = {
                        color: '#1e4d2b',
                        fontSize: '16',
                        alignment: 'center'
                    };
                    doc.content[0].text = 'Usuarios del Sistema - Los Troncos';
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimir',
                className: 'btn-sm',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5]
                }
            }
        ]
    });

    // Filtros personalizados
    $('#role-filter, #category-filter').on('change', function() {
        table.ajax.reload();
    });

    // Limpiar filtros
    $('#clear-filters').on('click', function() {
        $('#role-filter').val('');
        $('#category-filter').val('');
        table.ajax.reload();
    });

    // Confirmación de eliminación
    $('#users-table').on('submit', '.delete-form', function(e) {
        e.preventDefault();
        const form = this;

        if (confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
            form.submit();
        }
    });
});
</script>
@endpush
