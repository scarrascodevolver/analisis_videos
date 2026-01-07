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
                                Formatos: JPEG, PNG, JPG, GIF, SVG. Máximo 2MB.
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
</script>
@endpush
@endsection
