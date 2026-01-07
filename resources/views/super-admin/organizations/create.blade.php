@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.organizations') }}">Organizaciones</a></li>
                    <li class="breadcrumb-item active">Nueva Organización</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="fas fa-plus-circle text-success mr-2"></i>
                Nueva Organización
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-building mr-2"></i>Datos de la Organización
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('super-admin.organizations.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="name">Nombre de la Organización <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Ej: Club Deportivo Los Troncos"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="slug">Slug (URL amigable)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">/</span>
                                </div>
                                <input type="text"
                                       class="form-control @error('slug') is-invalid @enderror"
                                       id="slug"
                                       name="slug"
                                       value="{{ old('slug') }}"
                                       placeholder="club-los-troncos">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                Se genera automáticamente si se deja vacío. Solo letras, números y guiones.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="logo">Logo de la Organización</label>
                            <div class="custom-file">
                                <input type="file"
                                       class="custom-file-input @error('logo') is-invalid @enderror"
                                       id="logo"
                                       name="logo"
                                       accept="image/*">
                                <label class="custom-file-label" for="logo">Seleccionar archivo...</label>
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                Formatos: JPEG, PNG, JPG, GIF, SVG. Máximo 10MB.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">
                                    Organización Activa
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Las organizaciones inactivas no permiten el acceso a sus usuarios.
                            </small>
                        </div>

                        <hr>

                        <!-- Sección crear admin -->
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="create_admin"
                                       name="create_admin"
                                       value="1"
                                       {{ old('create_admin') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="create_admin">
                                    <strong>Crear administrador de la organización</strong>
                                </label>
                            </div>
                        </div>

                        <div id="admin_fields" style="{{ old('create_admin') ? '' : 'display: none;' }}">
                            <div class="card bg-light mb-3">
                                <div class="card-header">
                                    <i class="fas fa-user-shield mr-2"></i>Datos del Administrador
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="admin_name">Nombre completo <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control @error('admin_name') is-invalid @enderror"
                                               id="admin_name"
                                               name="admin_name"
                                               value="{{ old('admin_name') }}"
                                               placeholder="Nombre del administrador">
                                        @error('admin_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_email">Email <span class="text-danger">*</span></label>
                                        <input type="email"
                                               class="form-control @error('admin_email') is-invalid @enderror"
                                               id="admin_email"
                                               name="admin_email"
                                               value="{{ old('admin_email') }}"
                                               placeholder="admin@ejemplo.com">
                                        @error('admin_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_password">Contraseña</label>
                                        <input type="text"
                                               class="form-control @error('admin_password') is-invalid @enderror"
                                               id="admin_password"
                                               name="admin_password"
                                               placeholder="Dejar vacío para generar automáticamente">
                                        @error('admin_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            Si se deja vacío, se generará una contraseña aleatoria y se mostrará una sola vez.
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_role">Rol funcional <span class="text-danger">*</span></label>
                                        <select class="form-control @error('admin_role') is-invalid @enderror"
                                                id="admin_role"
                                                name="admin_role">
                                            <option value="analista" {{ old('admin_role') == 'analista' ? 'selected' : '' }}>Analista</option>
                                            <option value="entrenador" {{ old('admin_role') == 'entrenador' ? 'selected' : '' }}>Entrenador</option>
                                            <option value="staff" {{ old('admin_role') == 'staff' ? 'selected' : '' }}>Staff</option>
                                        </select>
                                        @error('admin_role')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('super-admin.organizations') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save mr-1"></i> Crear Organización
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3 bg-info text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-info-circle mr-2"></i>Información
                    </h6>
                </div>
                <div class="card-body">
                    <p><strong>¿Qué es una organización?</strong></p>
                    <p class="text-muted small">
                        Una organización representa un club, equipo o entidad que utilizará el sistema de análisis de video.
                    </p>
                    <hr>
                    <p><strong>Después de crear:</strong></p>
                    <ul class="text-muted small pl-3">
                        <li>Asignar un administrador</li>
                        <li>El admin podrá gestionar usuarios</li>
                        <li>Cada org tiene sus videos separados</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-generate slug from name
    document.getElementById('name').addEventListener('input', function() {
        const slugField = document.getElementById('slug');
        if (!slugField.value || slugField.dataset.autoGenerated === 'true') {
            slugField.value = this.value
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            slugField.dataset.autoGenerated = 'true';
        }
    });

    document.getElementById('slug').addEventListener('input', function() {
        this.dataset.autoGenerated = 'false';
    });

    // File input label
    document.querySelector('.custom-file-input').addEventListener('change', function() {
        const fileName = this.files[0]?.name || 'Seleccionar archivo...';
        this.nextElementSibling.textContent = fileName;
    });

    // Toggle admin fields
    document.getElementById('create_admin').addEventListener('change', function() {
        const adminFields = document.getElementById('admin_fields');
        adminFields.style.display = this.checked ? 'block' : 'none';
    });
</script>
@endpush
@endsection
