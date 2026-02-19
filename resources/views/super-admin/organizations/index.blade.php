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
                                    <button type="button"
                                            class="btn btn-outline-danger btn-delete-org"
                                            title="Eliminar organización"
                                            data-org-id="{{ $org->id }}"
                                            data-org-name="{{ $org->name }}"
                                            data-users="{{ $org->users_count }}"
                                            data-videos="{{ $org->videos_count }}"
                                            data-action="{{ route('super-admin.organizations.destroy', $org) }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
<!-- Modal confirmación de eliminación -->
<div class="modal fade" id="deleteOrgModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Eliminar Organización
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Estás a punto de eliminar permanentemente:</p>
                <ul>
                    <li><strong id="modal-org-name"></strong></li>
                    <li><span id="modal-users-count"></span> usuario(s) desasociados</li>
                    <li><span id="modal-videos-count"></span> video(s) eliminados de Bunny</li>
                </ul>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Esta acción <strong>no se puede deshacer</strong>. Los videos se eliminarán de Bunny Stream permanentemente.
                </div>
                <div class="form-group mb-0">
                    <label>Escribí el nombre exacto de la organización para confirmar:</label>
                    <input type="text" id="confirm-name-input" class="form-control" placeholder="Nombre de la organización">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="delete-org-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="confirm_name" id="confirm-name-hidden">
                    <button type="submit" class="btn btn-danger" id="btn-confirm-delete" disabled>
                        <i class="fas fa-trash mr-1"></i> Eliminar definitivamente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.btn-delete-org').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var name   = this.dataset.orgName;
        var users  = this.dataset.users;
        var videos = this.dataset.videos;
        var action = this.dataset.action;

        document.getElementById('modal-org-name').textContent    = name;
        document.getElementById('modal-users-count').textContent = users;
        document.getElementById('modal-videos-count').textContent= videos;
        document.getElementById('delete-org-form').action        = action;
        document.getElementById('confirm-name-input').value      = '';
        document.getElementById('btn-confirm-delete').disabled   = true;

        $('#deleteOrgModal').modal('show');
    });
});

document.getElementById('confirm-name-input').addEventListener('input', function() {
    var orgName  = document.getElementById('modal-org-name').textContent;
    var btnConfirm = document.getElementById('btn-confirm-delete');
    var hiddenInput = document.getElementById('confirm-name-hidden');

    hiddenInput.value = this.value;
    btnConfirm.disabled = (this.value !== orgName);
});
</script>
@endpush

@endsection
