@extends('layouts.app')

@section('page_title', 'Editar Video')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}">Videos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('videos.show', $video) }}">{{ $video->title }}</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('main_content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="card card-rugby">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i>
                        Editar Video: {{ $video->title }}
                    </h3>
                </div>
                <form action="{{ route('videos.update', $video) }}" method="POST" id="videoEditForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <!-- Video Information -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="title">
                                        <i class="fas fa-heading"></i> Título del Video *
                                    </label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $video->title) }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="match_date">
                                        <i class="fas fa-calendar"></i> Fecha del Partido *
                                    </label>
                                    <input type="date" class="form-control @error('match_date') is-invalid @enderror" 
                                           id="match_date" name="match_date" 
                                           value="{{ old('match_date', $video->match_date->format('Y-m-d')) }}" required>
                                    @error('match_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Team Selection -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-users"></i> Equipo Analizado
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           value="{{ $video->analyzed_team_name ?? $organizationName }}"
                                           style="background-color: #3d4248; color: #e9ecef; cursor: not-allowed;"
                                           disabled
                                           readonly>
                                    <small class="form-text text-muted">
                                        El equipo analizado no se puede modificar
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rival_team_name">
                                        <i class="fas fa-shield-alt"></i> Equipo Rival
                                        <small class="text-muted">(Opcional)</small>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('rival_team_name') is-invalid @enderror"
                                           id="rival_team_name"
                                           name="rival_team_name"
                                           value="{{ old('rival_team_name', $video->rival_team_name) }}"
                                           placeholder="Ej: Club Rugby Rival">
                                    <small class="form-text text-muted">
                                        Deja vacío si es un video de entrenamiento
                                    </small>
                                    @error('rival_team_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Category Selection -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_id">
                                        <i class="fas fa-tags"></i> Categoría *
                                    </label>
                                    <select class="form-control @error('category_id') is-invalid @enderror" 
                                            id="category_id" name="category_id" required>
                                        <option value="">Seleccionar categoría...</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                    {{ old('category_id', $video->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-info-circle"></i> Información del Video
                                    </label>
                                    <div class="bg-light p-3 rounded">
                                        <small class="d-block">
                                            <strong>Archivo:</strong> {{ $video->file_name }}
                                        </small>
                                        <small class="d-block">
                                            <strong>Tamaño:</strong> {{ number_format($video->file_size / 1024 / 1024, 2) }} MB
                                        </small>
                                        <small class="d-block">
                                            <strong>Tipo:</strong> {{ $video->mime_type }}
                                        </small>
                                        <small class="d-block">
                                            <strong>Subido:</strong> {{ $video->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="description">
                                <i class="fas fa-align-left"></i> Descripción del Video
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="4"
                                      placeholder="Describe el contenido del video, aspectos importantes a analizar, etc.">{{ old('description', $video->description) }}</textarea>
                            <small class="form-text text-muted">
                                Incluye información relevante sobre el partido, jugadas específicas, objetivos del análisis, etc.
                            </small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route('videos.show', $video) }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="submit" class="btn btn-rugby btn-lg">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Import LongoMatch XML (separate form outside main edit form) -->
                @if(in_array(auth()->user()->role, ['analista', 'entrenador']))
                <div class="card card-rugby mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-code"></i> Importar Clips desde LongoMatch XML
                        </h3>
                    </div>
                    <div class="card-body">
                        @php
                            $clipsCount = $video->clips()->count();
                        @endphp

                        @if($clipsCount > 0)
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i>
                            Este video tiene <strong>{{ $clipsCount }} clips</strong> actualmente.
                        </div>
                        @endif

                        <form action="{{ route('videos.import-xml', $video) }}" method="POST" enctype="multipart/form-data" id="xmlImportForm">
                            @csrf
                            <div class="form-group">
                                <label for="xml_file" class="text-light">
                                    Seleccionar archivo XML
                                </label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="xml_file" name="xml_file" accept=".xml" required>
                                    <label class="custom-file-label" for="xml_file">Elegir archivo XML...</label>
                                </div>
                                <small class="form-text text-muted mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    Sube un archivo XML exportado desde LongoMatch.
                                    @if($clipsCount > 0)
                                        Los {{ $clipsCount }} clips existentes serán reemplazados.
                                    @endif
                                </small>
                            </div>
                            <button type="submit" class="btn btn-rugby">
                                <i class="fas fa-upload"></i> Importar XML
                            </button>
                        </form>

                        @if($clipsCount > 0)
                        <hr class="my-3">
                        <form action="{{ route('videos.delete-all-clips', $video) }}" method="POST" id="deleteClipsForm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Eliminar todos los clips ({{ $clipsCount }})
                            </button>
                            <small class="form-text text-muted mt-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                Esta acción eliminará permanentemente todos los clips de este video.
                            </small>
                        </form>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Form validation
    $('#videoEditForm').on('submit', function(e) {
        let isValid = true;

        // Check required fields
        const requiredFields = ['title', 'category_id', 'match_date'];
        requiredFields.forEach(function(fieldName) {
            const field = $(`[name="${fieldName}"]`);
            if (!field.val() || field.val().trim() === '') {
                field.addClass('is-invalid');
                isValid = false;
            } else {
                field.removeClass('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Por favor completa todos los campos requeridos');
            return false;
        }
    });

    // Remove validation errors on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });

    // Update custom file input label
    $('#xml_file').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Elegir archivo XML...');
    });

    // XML Import Form submission
    $('#xmlImportForm').on('submit', function(e) {
        const fileInput = $('#xml_file')[0];
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Por favor selecciona un archivo XML');
            return false;
        }

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Importando...');
    });

    // Delete all clips confirmation
    $('#deleteClipsForm').on('submit', function(e) {
        e.preventDefault();

        if (confirm('¿Estás seguro de que deseas eliminar TODOS los clips de este video?\n\nEsta acción no se puede deshacer.')) {
            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Eliminando...');

            // Submit form
            this.submit();
        }
    });
});
</script>
@endsection