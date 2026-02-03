{{-- Single Upload Form --}}
<div class="card card-rugby" id="singleUploadCard">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-upload"></i>
            Subir Nuevo Video de Análisis
        </h3>
    </div>
    <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data" id="videoUploadForm">
        @csrf
        <div class="card-body">
            {{-- Video File Upload --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="video_file">
                            <i class="fas fa-video"></i> Archivo de Video *
                        </label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input @error('video_file') is-invalid @enderror"
                                   id="video_file" name="video_file" accept=".mp4,.mov,.avi,.webm,.mkv" required>
                            <label class="custom-file-label" for="video_file">Seleccionar archivo de video...</label>
                        </div>
                        <small class="form-text text-muted">
                            Formatos soportados: MP4, MOV, AVI, WEBM, MKV. Tamaño máximo: 8GB
                            <br><strong>Nota:</strong> Videos grandes serán comprimidos automáticamente para optimizar la reproducción.
                        </small>
                        @error('video_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- LongoMatch XML File (Optional) --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="xml_file">
                            <i class="fas fa-file-code"></i> Archivo XML
                            <small class="text-muted">(Opcional)</small>
                        </label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input"
                                   id="xml_file" name="xml_file" accept=".xml">
                            <label class="custom-file-label" for="xml_file">Seleccionar archivo XML...</label>
                        </div>
                        <small class="form-text text-muted">
                            Si tienes un archivo XML con la línea de tiempo, súbelo aquí para importar los clips automáticamente.
                        </small>
                    </div>

                    {{-- XML Preview (hidden by default) --}}
                    <div id="xmlPreview" class="alert alert-info" style="display: none;">
                        <h6><i class="fas fa-check-circle"></i> XML válido detectado</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Clips encontrados:</strong> <span id="xmlClipsCount">0</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Categorías:</strong> <span id="xmlCategoriesCount">0</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong>Categorías detectadas:</strong>
                            <div id="xmlCategoriesList" class="mt-1"></div>
                        </div>
                    </div>

                    {{-- XML Error (hidden by default) --}}
                    <div id="xmlError" class="alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i> <span id="xmlErrorText"></span>
                    </div>
                </div>
            </div>

            {{-- Video Information --}}
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="title">
                            <i class="fas fa-heading"></i> Título del Video *
                        </label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title" value="{{ old('title') }}"
                               placeholder="Escribe un título para el video" required>
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
                               id="match_date" name="match_date" value="{{ old('match_date', date('Y-m-d')) }}" required>
                        @error('match_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Team Selection --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-users"></i> Equipo Analizado
                        </label>
                        <input type="text"
                               class="form-control"
                               value="{{ $organizationName }}"
                               style="background-color: #3d4248; color: #e9ecef; cursor: not-allowed;"
                               disabled
                               readonly>
                        <small class="form-text text-muted">
                            El equipo analizado es tu organización
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="rival_team_id">
                            <i class="fas fa-shield-alt"></i> Equipo Rival
                            <small class="text-muted">(Opcional)</small>
                        </label>
                        <select class="form-control select2 @error('rival_team_id') is-invalid @enderror"
                                id="rival_team_id"
                                name="rival_team_id"
                                style="width: 100%;">
                            <option value="">Sin rival (entrenamiento)</option>
                        </select>
                        <small class="form-text text-muted">
                            Puedes crear un nuevo rival escribiendo su nombre
                        </small>
                        @error('rival_team_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Category Selection --}}
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
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- División (solo para categoría Adultas) --}}
                    <div class="form-group" id="division-group" style="display: none;">
                        <label for="division">
                            <i class="fas fa-layer-group"></i> División
                        </label>
                        <select class="form-control @error('division') is-invalid @enderror"
                                id="division" name="division">
                            <option value="">Seleccionar división...</option>
                            <option value="primera" {{ old('division') == 'primera' ? 'selected' : '' }}>Primera División</option>
                            <option value="intermedia" {{ old('division') == 'intermedia' ? 'selected' : '' }}>División Intermedia</option>
                        </select>
                        <small class="form-text text-muted">
                            Especifica la división para videos de categoría Adultas
                        </small>
                        @error('division')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="rugby_situation_id">
                            <i class="fas fa-football-ball"></i> Situación de Rugby
                        </label>
                        <select class="form-control @error('rugby_situation_id') is-invalid @enderror"
                                id="rugby_situation_id" name="rugby_situation_id">
                            <option value="">Sin situación específica</option>
                            @foreach($rugbySituations as $categoryName => $situations)
                                <optgroup label="{{ $categoryName }}">
                                    @foreach($situations as $situation)
                                        <option value="{{ $situation->id }}"
                                                data-color="{{ $situation->color }}"
                                                {{ old('rugby_situation_id') == $situation->id ? 'selected' : '' }}>
                                            {{ $situation->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('rugby_situation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Opcional: Especifica la situación de rugby para mejor análisis
                        </small>
                    </div>
                </div>
            </div>

            {{-- Rugby Situation Preview --}}
            <div class="row" id="rugbySituationPreview" style="display: none;">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Situación seleccionada:</strong>
                        <span id="situationPreviewText"></span>
                        <span id="situationPreviewBadge" class="badge ml-2"></span>
                    </div>
                </div>
            </div>

            {{-- Video Visibility Section --}}
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-eye"></i> ¿Quién puede ver este video?
                </label>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="visibility_type" id="visibility_public" value="public" checked>
                            <label class="form-check-label" for="visibility_public">
                                <strong>Todo el Equipo</strong>
                                <br><small class="text-muted">Visible para todos los jugadores, entrenadores y staff</small>
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="visibility_type" id="visibility_forwards" value="forwards">
                            <label class="form-check-label" for="visibility_forwards">
                                <strong>Solo Delanteros</strong>
                                <br><small class="text-muted">Visible solo para posiciones 1-8 (Forwards)</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="visibility_type" id="visibility_backs" value="backs">
                            <label class="form-check-label" for="visibility_backs">
                                <strong>Solo Backs</strong>
                                <br><small class="text-muted">Visible solo para posiciones 9-15 (Tres Cuartos)</small>
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="visibility_type" id="visibility_specific" value="specific">
                            <label class="form-check-label" for="visibility_specific">
                                <strong>Jugadores Específicos</strong>
                                <br><small class="text-muted">Solo para los jugadores que selecciones abajo</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Player Assignment Section --}}
            <div class="form-group" id="player-assignment-section" style="display: none;">
                <label for="assigned_players">
                    <i class="fas fa-user-plus"></i> Seleccionar Jugadores Específicos
                </label>
                <select class="form-control select2" id="assigned_players" name="assigned_players[]" multiple="multiple"
                        data-placeholder="Buscar y seleccionar jugadores..." style="width: 100%;">
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">
                            {{ $player->name }}
                            @if($player->profile && $player->profile->position)
                                - {{ $player->profile->position }}
                            @endif
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    Puedes buscar por nombre o posición. Selecciona múltiples jugadores manteniendo Ctrl.
                </small>

                <div class="form-group mt-3">
                    <label for="assignment_notes">
                        <i class="fas fa-sticky-note"></i> Notas de Asignación
                    </label>
                    <textarea class="form-control" id="assignment_notes" name="assignment_notes" rows="2"
                              placeholder="Notas específicas para los jugadores asignados...">{{ old('assignment_notes') }}</textarea>
                </div>
            </div>

            {{-- Description --}}
            <div class="form-group">
                <label for="description">
                    <i class="fas fa-align-left"></i> Descripción del Video
                </label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3"
                          placeholder="Describe el contenido del video, aspectos importantes a analizar, etc.">{{ old('description') }}</textarea>
                <small class="form-text text-muted">
                    Incluye información relevante sobre el partido, jugadas específicas, objetivos del análisis, etc.
                </small>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Upload Progress --}}
            <div class="form-group" id="uploadProgress" style="display: none;">
                <label>Progreso de Subida</label>
                <div class="progress mb-2">
                    <div class="progress-bar" role="progressbar" style="width: 0%" id="progressBar">
                        <span id="progressText">0%</span>
                    </div>
                </div>
                <div id="uploadStatus" class="text-muted small">
                    Preparando subida...
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <a href="{{ route('videos.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                </div>
                <div class="col-md-6 text-right">
                    <button type="submit" class="btn btn-rugby btn-lg" id="uploadBtn">
                        <i class="fas fa-upload"></i> Subir Video
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Single Upload JavaScript (incluido inline para mantener compatibilidad) --}}
@push('scripts')
<script>
$(document).ready(function() {
    // Variable to store XML content
    var xmlContent = null;

    // Initialize Select2 for rival team
    $('#rival_team_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccionar o crear equipo rival...',
        allowClear: true,
        tags: true,
        width: '100%',
        ajax: {
            url: '{{ route("api.rival-teams.autocomplete") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        createTag: function (params) {
            var term = $.trim(params.term);
            if (term === '') {
                return null;
            }
            return {
                id: 'new:' + term,
                text: term + ' (crear nuevo)',
                newTag: true
            };
        }
    });

    // Initialize Select2 for players
    $('#assigned_players').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Custom file input for video
    $('#video_file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);

        // Show file info
        if (this.files && this.files[0]) {
            var file = this.files[0];
            var size = (file.size / (1024 * 1024)).toFixed(2);
            var info = fileName + ' (' + size + ' MB)';
            $(this).siblings('.custom-file-label').html(info);
        }
    });

    // XML file handling
    $('#xml_file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);

        $('#xmlPreview').hide();
        $('#xmlError').hide();
        xmlContent = null;

        if (this.files && this.files[0]) {
            var file = this.files[0];

            if (!file.name.toLowerCase().endsWith('.xml')) {
                $('#xmlErrorText').text('El archivo debe ser XML');
                $('#xmlError').show();
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                xmlContent = e.target.result;
                validateXml(xmlContent);
            };
            reader.onerror = function() {
                $('#xmlErrorText').text('Error leyendo el archivo');
                $('#xmlError').show();
            };
            reader.readAsText(file);
        }
    });

    function validateXml(content) {
        $.ajax({
            url: '{{ route("api.xml.validate") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                xml_content: content
            },
            success: function(response) {
                if (response.valid) {
                    $('#xmlClipsCount').text(response.preview.clips_count);
                    $('#xmlCategoriesCount').text(response.preview.categories_used.length);

                    var categoriesHtml = response.preview.categories_used.map(function(cat) {
                        return '<span class="badge badge-secondary mr-1 mb-1">' + cat + '</span>';
                    }).join('');
                    $('#xmlCategoriesList').html(categoriesHtml);

                    $('#xmlPreview').show();
                    $('#xmlError').hide();
                } else {
                    xmlContent = null;
                    $('#xmlErrorText').text(response.error || 'XML inválido');
                    $('#xmlError').show();
                    $('#xmlPreview').hide();
                }
            },
            error: function(xhr) {
                xmlContent = null;
                $('#xmlErrorText').text('Error validando XML');
                $('#xmlError').show();
            }
        });
    }

    // Category change (show division for Adultas)
    $('#category_id').on('change', function() {
        var selectedText = $(this).find('option:selected').text().toLowerCase();
        if (selectedText.includes('adulta')) {
            $('#division-group').slideDown();
        } else {
            $('#division-group').slideUp();
            $('#division').val('');
        }
    });

    // Rugby situation change
    $('#rugby_situation_id').on('change', function() {
        var selected = $(this).find('option:selected');
        if ($(this).val()) {
            $('#situationPreviewText').text(selected.text());
            $('#situationPreviewBadge').text(selected.parent('optgroup').attr('label'));
            $('#situationPreviewBadge').css('background-color', selected.data('color') || '#6c757d');
            $('#rugbySituationPreview').slideDown();
        } else {
            $('#rugbySituationPreview').slideUp();
        }
    });

    // Visibility type change
    $('input[name="visibility_type"]').on('change', function() {
        if ($(this).val() === 'specific') {
            $('#player-assignment-section').slideDown();
        } else {
            $('#player-assignment-section').slideUp();
        }
    });

    // Form validation and upload with real progress
    $('#videoUploadForm').on('submit', function(e) {
        e.preventDefault(); // Always prevent default to handle with AJAX

        var fileInput = $('#video_file')[0];
        if (!fileInput.files || !fileInput.files[0]) {
            alert('Por favor selecciona un archivo de video');
            return false;
        }

        var file = fileInput.files[0];
        var maxSize = 8 * 1024 * 1024 * 1024; // 8GB

        if (file.size > maxSize) {
            alert('El archivo es demasiado grande. El tamaño máximo es 8GB.');
            return false;
        }

        // Prepare form data
        var formData = new FormData(this);

        // Show progress
        $('#uploadProgress').show();
        $('#uploadBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subiendo...');
        $('#progressBar').removeClass('bg-success bg-danger bg-primary bg-warning').css('background-color', 'var(--color-primary, #005461)');

        // AJAX upload with progress
        uploadWithProgress(formData);
    });

    function uploadWithProgress(formData) {
        var fileInput = $('#video_file')[0];
        var file = fileInput.files[0];

        // Use multipart upload for files > 100MB
        var multipartThreshold = 100 * 1024 * 1024; // 100MB

        if (file.size > multipartThreshold) {
            console.log('File size:', (file.size / 1024 / 1024).toFixed(2), 'MB - Using multipart upload');
            uploadMultipart(file, formData);
        } else {
            console.log('File size:', (file.size / 1024 / 1024).toFixed(2), 'MB - Using single upload');
            uploadSingle(file, formData);
        }
    }

    // Single upload function (simplified - full implementation needed)
    function uploadSingle(file, formData) {
        $('#uploadStatus').html('<i class="fas fa-cloud"></i> Subiendo archivo...');

        $.ajax({
            url: $('#videoUploadForm').attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('#progressBar').css('width', percentComplete + '%');
                        $('#progressText').text(Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                $('#progressBar').addClass('bg-success').css('width', '100%');
                $('#progressText').text('100%');
                $('#uploadStatus').html('<i class="fas fa-check-circle"></i> Subida completada exitosamente');

                setTimeout(function() {
                    window.location.href = '{{ route("videos.index") }}';
                }, 1500);
            },
            error: function(xhr) {
                $('#progressBar').addClass('bg-danger');
                $('#uploadStatus').html('<i class="fas fa-exclamation-circle"></i> Error: ' + (xhr.responseJSON?.message || 'Error desconocido'));
                $('#uploadBtn').prop('disabled', false).html('<i class="fas fa-upload"></i> Reintentar');
            }
        });
    }

    // Multipart upload function (placeholder - to be implemented)
    function uploadMultipart(file, formData) {
        $('#uploadStatus').html('<i class="fas fa-info-circle"></i> Upload multipart no implementado en este modo. Usa modo batch para archivos grandes.');
        $('#uploadBtn').prop('disabled', false).html('<i class="fas fa-upload"></i> Subir Video');
    }
});
</script>
@endpush
