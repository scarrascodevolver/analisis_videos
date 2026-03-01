@extends('layouts.app')

@section('main_content')
<style>
    /* --- Type cards --- */
    .type-card {
        cursor: pointer;
        border: 2px solid #2d2d4e;
        border-radius: 10px;
        padding: 20px 16px;
        background: #0f0f1a;
        transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        text-align: center;
        flex: 1;
    }
    .type-card:hover {
        border-color: #005461;
        background: #111827;
    }
    .type-card.selected {
        border-color: #00B7B5;
        background: #0d2226;
        box-shadow: 0 0 0 3px rgba(0,183,181,0.15);
    }
    .type-card .type-icon {
        font-size: 2.2rem;
        margin-bottom: 10px;
        color: #888;
        transition: color 0.2s ease;
    }
    .type-card.selected .type-icon {
        color: #00B7B5;
    }
    .type-card .type-title {
        font-weight: 700;
        font-size: 1rem;
        color: #fff;
        margin-bottom: 4px;
    }
    .type-card .type-desc {
        font-size: 0.78rem;
        color: #888;
        line-height: 1.4;
    }

    /* --- Logo preview --- */
    #logo-preview-wrap {
        display: none;
        margin-top: 14px;
        align-items: center;
        gap: 14px;
    }
    #logo-preview-wrap.visible {
        display: flex;
    }
    #logo-preview-img {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #00B7B5;
        background: #0f0f1a;
    }

    /* --- Admin card animated reveal --- */
    #admin_fields {
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transition: max-height 0.35s ease, opacity 0.3s ease;
    }
    #admin_fields.open {
        max-height: 600px;
        opacity: 1;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0" style="color:#fff;">
                <i class="fas fa-plus-circle mr-2" style="color:#00B7B5;"></i>
                Nueva Organización
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow" style="background:#1a1a2e; border:1px solid #2d2d4e;">
                <div class="card-header py-3" style="background:#005461; border-bottom:1px solid #2d2d4e;">
                    <h6 class="m-0 font-weight-bold" style="color:#fff;">
                        <i class="fas fa-building mr-2"></i>Datos de la Organización
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('super-admin.organizations.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- Tipo de organización como cards --}}
                        <div class="form-group">
                            <label style="color:#ccc;">Tipo de Organización <span class="text-danger">*</span></label>
                            <div class="d-flex" style="gap:14px;">
                                {{-- Card Club --}}
                                <div class="type-card {{ old('type', 'club') === 'club' ? 'selected' : '' }}"
                                     id="card-club"
                                     onclick="selectType('club')">
                                    <div class="type-icon"><i class="fas fa-shield-alt"></i></div>
                                    <div class="type-title">Club</div>
                                    <div class="type-desc">Un solo club con categorías<br>(Adultos, M18, Femenino…)</div>
                                </div>
                                {{-- Card Asociación --}}
                                <div class="type-card {{ old('type') === 'asociacion' ? 'selected' : '' }}"
                                     id="card-asoc"
                                     onclick="selectType('asociacion')">
                                    <div class="type-icon"><i class="fas fa-sitemap"></i></div>
                                    <div class="type-title">Asociación</div>
                                    <div class="type-desc">Analiza varios clubes en<br>distintos torneos</div>
                                </div>
                            </div>
                            {{-- Input oculto que lleva el valor real --}}
                            <input type="hidden" id="type_input" name="type" value="{{ old('type', 'club') }}">
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="name" style="color:#ccc;">Nombre de la Organización <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Ej: Club Deportivo Rugby"
                                   style="background:#0f0f1a; border-color:#2d2d4e; color:#fff;"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slug: campo oculto, se auto-genera desde el nombre --}}
                        <input type="hidden" id="slug" name="slug" value="{{ old('slug') }}">

                        {{-- Logo con preview circular --}}
                        <div class="form-group">
                            <label for="logo" style="color:#ccc;">Logo de la Organización</label>
                            <div class="custom-file">
                                <input type="file"
                                       class="custom-file-input @error('logo') is-invalid @enderror"
                                       id="logo"
                                       name="logo"
                                       accept="image/*">
                                <label class="custom-file-label" for="logo"
                                       style="background:#0f0f1a; border-color:#2d2d4e; color:#888;">
                                    Seleccionar archivo...
                                </label>
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small style="color:#888;">Formatos: JPEG, PNG, JPG, GIF, SVG. Máximo 10MB.</small>

                            {{-- Preview circular --}}
                            <div id="logo-preview-wrap">
                                <img id="logo-preview-img" src="" alt="Preview logo">
                                <div>
                                    <div style="color:#ccc; font-size:0.85rem; font-weight:600;">Vista previa del logo</div>
                                    <div id="logo-filename" style="color:#888; font-size:0.78rem;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active" style="color:#ccc;">
                                    Organización Activa
                                </label>
                            </div>
                            <small style="color:#888;">Las organizaciones inactivas no permiten el acceso a sus usuarios.</small>
                        </div>

                        <hr style="border-color:#2d2d4e;">

                        {{-- Switch crear admin --}}
                        <div class="form-group mb-2">
                            <div class="custom-control custom-switch">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="create_admin"
                                       name="create_admin"
                                       value="1"
                                       {{ old('create_admin') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="create_admin" style="color:#ccc;">
                                    <strong style="color:#fff;">Crear administrador de la organización</strong>
                                </label>
                            </div>
                        </div>

                        {{-- Card admin con animacion CSS --}}
                        <div id="admin_fields" class="{{ old('create_admin') ? 'open' : '' }}">
                            <div class="card mb-3" style="background:#0f0f1a; border:1px solid #2d2d4e;">
                                <div class="card-header" style="background:#003d4a; border-bottom:1px solid #2d2d4e; color:#fff;">
                                    <i class="fas fa-user-shield mr-2" style="color:#00B7B5;"></i>Datos del Administrador
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="admin_name" style="color:#ccc;">Nombre completo <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control @error('admin_name') is-invalid @enderror"
                                               id="admin_name"
                                               name="admin_name"
                                               value="{{ old('admin_name') }}"
                                               placeholder="Nombre del administrador"
                                               style="background:#1a1a2e; border-color:#2d2d4e; color:#fff;">
                                        @error('admin_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_email" style="color:#ccc;">Email <span class="text-danger">*</span></label>
                                        <input type="email"
                                               class="form-control @error('admin_email') is-invalid @enderror"
                                               id="admin_email"
                                               name="admin_email"
                                               value="{{ old('admin_email') }}"
                                               placeholder="admin@ejemplo.com"
                                               style="background:#1a1a2e; border-color:#2d2d4e; color:#fff;">
                                        @error('admin_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_password" style="color:#ccc;">Contraseña</label>
                                        <input type="text"
                                               class="form-control @error('admin_password') is-invalid @enderror"
                                               id="admin_password"
                                               name="admin_password"
                                               placeholder="Dejar vacío para generar automáticamente"
                                               style="background:#1a1a2e; border-color:#2d2d4e; color:#fff;">
                                        @error('admin_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small style="color:#888;">Si se deja vacío, se generará una contraseña aleatoria y se mostrará una sola vez.</small>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label for="admin_role" style="color:#ccc;">Rol funcional <span class="text-danger">*</span></label>
                                        <select class="form-control @error('admin_role') is-invalid @enderror"
                                                id="admin_role"
                                                name="admin_role"
                                                style="background:#1a1a2e; border-color:#2d2d4e; color:#fff;">
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

                        <hr style="border-color:#2d2d4e;">

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
            <div class="card shadow" style="background:#1a1a2e; border:1px solid #2d2d4e;">
                <div class="card-header py-3" style="background:#00B7B5; border-bottom:1px solid #2d2d4e;">
                    <h6 class="m-0 font-weight-bold" style="color:#fff;">
                        <i class="fas fa-info-circle mr-2"></i>Información
                    </h6>
                </div>
                <div class="card-body">
                    <p style="color:#fff;"><strong>¿Qué es una organización?</strong></p>
                    <p style="color:#888;" class="small">
                        Una organización representa un club, equipo o entidad que utilizará el sistema de análisis de video.
                    </p>
                    <hr style="border-color:#2d2d4e;">
                    <p style="color:#fff;"><strong>Después de crear:</strong></p>
                    <ul class="small pl-3" style="color:#888;">
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
    // -------------------------------------------------------
    // Type cards
    // -------------------------------------------------------
    function selectType(value) {
        document.getElementById('type_input').value = value;

        document.getElementById('card-club').classList.toggle('selected', value === 'club');
        document.getElementById('card-asoc').classList.toggle('selected', value === 'asociacion');
    }

    // -------------------------------------------------------
    // Auto-generate slug from name
    // -------------------------------------------------------
    document.getElementById('name').addEventListener('input', function () {
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

    document.getElementById('slug').addEventListener('input', function () {
        this.dataset.autoGenerated = 'false';
    });

    // -------------------------------------------------------
    // Logo: label + preview circular
    // -------------------------------------------------------
    document.getElementById('logo').addEventListener('change', function () {
        const file = this.files[0];

        // Update custom-file label
        this.nextElementSibling.textContent = file ? file.name : 'Seleccionar archivo...';

        const wrap = document.getElementById('logo-preview-wrap');
        const img  = document.getElementById('logo-preview-img');
        const fnEl = document.getElementById('logo-filename');

        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                fnEl.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                wrap.classList.add('visible');
            };
            reader.readAsDataURL(file);
        } else {
            wrap.classList.remove('visible');
            img.src = '';
        }
    });

    // -------------------------------------------------------
    // Toggle admin fields con animación CSS
    // -------------------------------------------------------
    document.getElementById('create_admin').addEventListener('change', function () {
        const panel = document.getElementById('admin_fields');
        if (this.checked) {
            panel.classList.add('open');
        } else {
            panel.classList.remove('open');
        }
    });
</script>
@endpush
@endsection
