@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.organizations') }}">Organizaciones</a></li>
                    <li class="breadcrumb-item active">Editar: {{ $organization->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="fas fa-edit text-primary mr-2"></i>
                Editar Organización
            </h1>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-building mr-2"></i>Datos de la Organización
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('super-admin.organizations.update', $organization) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Tipo de Organización <span class="text-danger">*</span></label>
                            <div class="d-flex" style="gap:12px">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="type_club" name="type" value="club"
                                           class="custom-control-input"
                                           {{ old('type', $organization->type) === 'club' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="type_club">
                                        <strong>Club</strong>
                                        <small class="d-block text-muted">Un solo club con categorías (Adultos, M18…)</small>
                                    </label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="type_asoc" name="type" value="asociacion"
                                           class="custom-control-input"
                                           {{ old('type', $organization->type) === 'asociacion' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="type_asoc">
                                        <strong>Asociación</strong>
                                        <small class="d-block text-muted">Analiza varios clubes en distintos torneos</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name">Nombre de la Organización <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $organization->name) }}"
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
                                       value="{{ old('slug', $organization->slug) }}">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                Cuidado: cambiar el slug puede afectar URLs de videos existentes.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="logo">Logo de la Organización</label>
                            @if($organization->logo_path)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $organization->logo_path) }}"
                                         alt="Logo actual"
                                         class="img-thumbnail"
                                         style="max-height: 100px;">
                                    <span class="text-muted ml-2">Logo actual</span>
                                </div>
                            @endif
                            <div class="custom-file">
                                <input type="file"
                                       class="custom-file-input @error('logo') is-invalid @enderror"
                                       id="logo"
                                       name="logo"
                                       accept="image/*">
                                <label class="custom-file-label" for="logo">Cambiar logo...</label>
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', $organization->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">
                                    Organización Activa
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('super-admin.organizations') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Stats Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-secondary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-pie mr-2"></i>Estadísticas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h3 class="text-primary">{{ $organization->users_count }}</h3>
                            <small class="text-muted">Usuarios</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-info">{{ $organization->videos_count }}</h3>
                            <small class="text-muted">Videos</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-tools mr-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('super-admin.organizations.settings', $organization) }}"
                       class="btn btn-outline-primary btn-block mb-2">
                        <i class="fas fa-cog mr-2"></i> Organization Settings
                    </a>
                    <a href="{{ route('super-admin.organizations.assign-admin', $organization) }}"
                       class="btn btn-outline-success btn-block">
                        <i class="fas fa-user-shield mr-2"></i> Manage Users
                    </a>
                </div>
            </div>

            <!-- Admins Card -->
            <div class="card shadow">
                <div class="card-header py-3 bg-success text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-user-shield mr-2"></i>Administradores
                    </h6>
                    <a href="{{ route('super-admin.organizations.assign-admin', $organization) }}"
                       class="btn btn-sm btn-light">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                <div class="card-body">
                    @if($admins->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($admins as $admin)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $admin->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $admin->email }}</small>
                                </div>
                                <span class="badge badge-success">Admin</span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted text-center mb-0">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <br>
                            Sin administradores asignados
                        </p>
                        <div class="text-center mt-2">
                            <a href="{{ route('super-admin.organizations.assign-admin', $organization) }}"
                               class="btn btn-sm btn-success">
                                Asignar Admin
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelector('.custom-file-input').addEventListener('change', function() {
        const fileName = this.files[0]?.name || 'Cambiar logo...';
        this.nextElementSibling.textContent = fileName;
    });
</script>
@endpush
@endsection
