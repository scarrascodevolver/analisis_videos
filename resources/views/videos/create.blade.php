@extends('layouts.app')

@section('page_title', 'Subir Video')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('videos.index') }}">Videos del Equipo</a></li>
    <li class="breadcrumb-item active">Subir Video</li>
@endsection

@section('main_content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-rugby">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload"></i>
                        Subir Nuevo Video de Análisis
                    </h3>
                </div>
                <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data" id="videoUploadForm">
                    @csrf
                    <div class="card-body">
                        <!-- Video File Upload -->
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

                        <!-- LongoMatch XML File (Optional) -->
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

                                <!-- XML Preview (hidden by default) -->
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

                                <!-- XML Error (hidden by default) -->
                                <div id="xmlError" class="alert alert-danger" style="display: none;">
                                    <i class="fas fa-exclamation-triangle"></i> <span id="xmlErrorText"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Video Information -->
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

                        <!-- Team Selection -->
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
                                    <label for="rival_team_name">
                                        <i class="fas fa-shield-alt"></i> Equipo Rival
                                        <small class="text-muted">(Opcional)</small>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('rival_team_name') is-invalid @enderror"
                                           id="rival_team_name"
                                           name="rival_team_name"
                                           value="{{ old('rival_team_name') }}"
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
                                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- División (solo para categoría Adultas) -->
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

                        <!-- Rugby Situation Preview -->
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


                        <!-- Video Visibility Section -->
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

                        <!-- Player Assignment Section -->
                        <div class="form-group" id="player-assignment-section">
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

                        <!-- Description -->
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

                        <!-- Upload Progress -->
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
        </div>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Variable to store XML content
    var xmlContent = null;

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

    function uploadSingle(file, formData) {
        $('#uploadStatus').html('<i class="fas fa-spinner fa-spin"></i> Preparando subida directa a la nube...');

        // Step 1: Get pre-signed URL from our server
        $.ajax({
            url: '{{ route("api.upload.presigned") }}',
            method: 'POST',
            timeout: 30000, // 30 seconds timeout
            data: {
                _token: '{{ csrf_token() }}',
                filename: file.name,
                content_type: file.type || 'video/mp4',
                file_size: file.size
            },
            success: function(response) {
                if (response.success) {
                    $('#uploadStatus').html('<i class="fas fa-cloud"></i> Conectado a la nube. Iniciando subida directa...');
                    // Step 2: Upload directly to Spaces using pre-signed URL
                    uploadToSpaces(response.upload_url, response.upload_id, file, formData);
                } else {
                    showError('Error obteniendo URL de subida: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('Presigned URL error:', xhr);
                showError('Error preparando la subida: ' + (xhr.responseJSON?.message || 'Error de conexión'));
            }
        });
    }

    function uploadMultipart(file, formData) {
        var chunkSize = 100 * 1024 * 1024; // 100MB chunks (increased from 50MB for better throughput)
        var totalParts = Math.ceil(file.size / chunkSize);

        $('#uploadStatus').html('<i class="fas fa-spinner fa-spin"></i> Preparando subida multipart (' + totalParts + ' partes)...');

        // Step 1: Initiate multipart upload
        $.ajax({
            url: '{{ route("api.upload.multipart.initiate") }}',
            method: 'POST',
            timeout: 30000, // 30 seconds timeout
            data: {
                _token: '{{ csrf_token() }}',
                filename: file.name,
                content_type: file.type || 'video/mp4',
                file_size: file.size,
                parts_count: totalParts
            },
            success: function(response) {
                if (response.success) {
                    $('#uploadStatus').html('<i class="fas fa-cloud"></i> Iniciando subida en partes...');
                    uploadPartsInParallel(file, formData, response.upload_id, response.s3_upload_id, chunkSize, totalParts);
                } else {
                    showError('Error iniciando multipart upload: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('Multipart initiate error:', xhr);
                showError('Error preparando multipart upload: ' + (xhr.responseJSON?.message || 'Error de conexión'));
            }
        });
    }

    function uploadPartsInParallel(file, formData, uploadId, s3UploadId, chunkSize, totalParts) {
        var completedParts = [];
        var uploadedBytes = 0;
        var maxConcurrent = 10; // ✅ Optimal balance - more can cause saturation
        var hasError = false;
        var maxRetries = 3; // Retry failed parts up to 3 times
        var retryCount = {}; // Track retries per part

        // Queue-based approach to prevent race conditions with concurrent uploads
        var pendingParts = []; // Queue of parts waiting to be uploaded
        var inProgressParts = new Set(); // Track which parts are currently uploading

        // Initialize queue with all part numbers
        for (var i = 1; i <= totalParts; i++) {
            pendingParts.push(i);
        }

        function uploadNextPart() {
            if (hasError) {
                return;
            }

            // Check if we have capacity and pending parts
            if (inProgressParts.size >= maxConcurrent || pendingParts.length === 0) {
                return;
            }

            // Get next part from queue
            var partNumber = pendingParts.shift();
            inProgressParts.add(partNumber);

            var start = (partNumber - 1) * chunkSize;
            var end = Math.min(start + chunkSize, file.size);
            var chunk = file.slice(start, end);

            console.log('Starting upload of part', partNumber, '(pending:', pendingParts.length, 'in-progress:', inProgressParts.size, ')');

            // Get presigned URL for this part
            $.ajax({
                url: '{{ route("api.upload.multipart.part-urls") }}',
                method: 'POST',
                timeout: 30000, // 30 seconds timeout
                data: {
                    _token: '{{ csrf_token() }}',
                    upload_id: uploadId,
                    part_numbers: [partNumber]
                },
                success: function(response) {
                    if (response.success) {
                        uploadPart(chunk, partNumber, response.urls[partNumber]);
                    } else {
                        retryOrFail(partNumber, chunk, 'Error obteniendo URL de parte ' + partNumber, false);
                    }
                },
                error: function(xhr, status) {
                    // Detect network errors (offline, no connection)
                    var isNetworkError = !xhr.status || xhr.status === 0 || status === 'timeout' || status === 'error';
                    var errorMsg = status === 'timeout' ? 'Timeout obteniendo URL de parte ' + partNumber : 'Error preparando parte ' + partNumber;
                    retryOrFail(partNumber, chunk, errorMsg, isNetworkError);
                }
            });
        }

        function retryOrFail(partNumber, chunk, errorMsg, isNetworkError) {
            // Remove from in-progress set
            inProgressParts.delete(partNumber);

            if (!retryCount[partNumber]) {
                retryCount[partNumber] = 0;
            }

            retryCount[partNumber]++;

            // More retries for network errors (connection lost/offline)
            var maxRetriesForPart = isNetworkError ? 15 : maxRetries;

            if (retryCount[partNumber] <= maxRetriesForPart) {
                console.log('Retrying part', partNumber, '(attempt', retryCount[partNumber], 'of', maxRetriesForPart + ')',
                    isNetworkError ? '[NETWORK ERROR]' : '');

                // Different backoff strategies
                var delay;
                if (isNetworkError) {
                    // Linear backoff for network errors: 5s, 10s, 15s, 20s... (max 60s)
                    // Give more time for connection to come back
                    delay = Math.min(5000 * retryCount[partNumber], 60000);
                    $('#uploadStatus').html(
                        '<i class="fas fa-wifi text-warning"></i> Sin conexión. Reintentando parte ' +
                        partNumber + ' en ' + (delay/1000) + 's... (intento ' + retryCount[partNumber] + '/' + maxRetriesForPart + ')'
                    );
                } else {
                    // Exponential backoff for other errors: 1s, 2s, 4s (max 16s)
                    delay = Math.min(1000 * Math.pow(2, retryCount[partNumber] - 1), 16000);
                    $('#uploadStatus').html('<i class="fas fa-redo text-warning"></i> Reintentando parte ' + partNumber + ' (intento ' + retryCount[partNumber] + ')...');
                }

                console.log('Waiting', delay, 'ms before retry');

                setTimeout(function() {
                    // Re-add part to front of queue for retry
                    pendingParts.unshift(partNumber);
                    uploadNextPart();
                }, delay);
            } else {
                hasError = true;
                console.error('Max retries exceeded for part', partNumber);

                // CRITICAL: Abort the multipart upload on server
                abortMultipartUpload(uploadId, errorMsg);
            }
        }

        function abortMultipartUpload(uploadId, reason) {
            console.error('Aborting multipart upload:', reason);

            $.ajax({
                url: '{{ route("api.upload.multipart.abort") }}',
                method: 'POST',
                timeout: 30000,
                data: {
                    _token: '{{ csrf_token() }}',
                    upload_id: uploadId
                },
                success: function(response) {
                    console.log('Multipart upload aborted on server');
                },
                error: function(xhr) {
                    console.error('Failed to abort multipart upload:', xhr);
                }
            });

            showError(reason + ' (upload abortado después de máximo de reintentos)');
        }

        function uploadPart(chunk, partNumber, presignedUrl) {
            var xhr = new XMLHttpRequest();
            xhr.timeout = 1800000; // 30 minutes timeout for upload (slow connections)

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    // Update progress (approximate based on completed parts + current upload)
                    var totalUploaded = uploadedBytes + e.loaded;
                    var percentComplete = Math.round((totalUploaded / file.size) * 100);
                    $('#progressBar').css('width', percentComplete + '%');
                    $('#progressText').text(percentComplete + '%');

                    var loadedMB = (totalUploaded / (1024 * 1024)).toFixed(1);
                    var totalMB = (file.size / (1024 * 1024)).toFixed(1);
                    $('#uploadStatus').html('<i class="fas fa-cloud-upload-alt text-info"></i> Subiendo parte ' +
                        completedParts.length + '/' + totalParts + ' (' + loadedMB + 'MB / ' + totalMB + 'MB)');
                }
            });

            xhr.addEventListener('load', function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var etag = xhr.getResponseHeader('ETag');

                    // CRITICAL: ETag MUST be present, otherwise the part is corrupted
                    if (!etag) {
                        console.error('Part', partNumber, 'uploaded but ETag missing - treating as failure');
                        retryOrFail(partNumber, chunk, 'ETag no disponible para parte ' + partNumber + ' (posible corrupción)', false);
                        return;
                    }

                    etag = etag.replace(/"/g, ''); // Remove quotes
                    console.log('Part', partNumber, 'uploaded successfully with ETag:', etag);

                    // Remove from in-progress set
                    inProgressParts.delete(partNumber);

                    // Prevent duplicate entries (check if part already completed)
                    var existingPart = completedParts.find(p => p.PartNumber === partNumber);
                    if (!existingPart) {
                        completedParts.push({
                            PartNumber: partNumber,
                            ETag: etag
                        });
                    } else {
                        console.warn('Part', partNumber, 'already in completedParts, skipping duplicate');
                    }

                    checkCompletion();
                } else {
                    console.error('Part', partNumber, 'upload failed:', xhr.status);
                    retryOrFail(partNumber, chunk, 'Error subiendo parte ' + partNumber + '. Código: ' + xhr.status, false);
                }
            });

            function checkCompletion() {
                uploadedBytes += chunk.size;

                console.log('Part', partNumber, 'uploaded successfully. Total completed:', completedParts.length, '/', totalParts);

                // Check if all parts are completed
                if (completedParts.length === totalParts) {
                    console.log('All parts uploaded. Validating before completion...');

                    // Final validation: ensure all part numbers from 1 to totalParts are present
                    var partNumbers = completedParts.map(p => p.PartNumber).sort((a, b) => a - b);
                    var missingParts = [];

                    for (var i = 1; i <= totalParts; i++) {
                        if (!partNumbers.includes(i)) {
                            missingParts.push(i);
                        }
                    }

                    if (missingParts.length > 0) {
                        console.error('CRITICAL: Missing parts detected:', missingParts);
                        showError('Error: faltan las partes ' + missingParts.join(', ') + '. No se puede completar upload.');
                        return;
                    }

                    console.log('All parts validated successfully. Completing multipart upload...');
                    $('#progressBar').css('background-color', '#ffc107');
                    $('#uploadStatus').html('<i class="fas fa-spinner fa-spin text-warning"></i> Finalizando upload...');
                    completeMultipartUpload(uploadId, completedParts, formData);
                } else {
                    // Upload next part
                    uploadNextPart();
                }
            }

            xhr.addEventListener('error', function() {
                console.error('Part', partNumber, 'network error');
                retryOrFail(partNumber, chunk, 'Error de conexión subiendo parte ' + partNumber, true);
            });

            xhr.addEventListener('timeout', function() {
                console.error('Part', partNumber, 'timeout');
                retryOrFail(partNumber, chunk, 'Timeout subiendo parte ' + partNumber, true);
            });

            xhr.open('PUT', presignedUrl);
            xhr.send(chunk);
        }

        // Start uploading first batch of parts
        for (var i = 0; i < maxConcurrent && i < totalParts; i++) {
            uploadNextPart();
        }
    }

    function completeMultipartUpload(uploadId, parts, formData) {
        // Sort parts by PartNumber
        parts.sort((a, b) => a.PartNumber - b.PartNumber);

        // CRITICAL: Validate ALL parts have ETags (strict validation)
        var invalidParts = parts.filter(p => !p.ETag || p.ETag === null);

        if (invalidParts.length > 0) {
            console.error('CRITICAL: Parts with missing ETags detected:', invalidParts);
            showError('Error: ' + invalidParts.length + ' partes sin ETag válido. No se puede completar upload.');
            return;
        }

        console.log('All', parts.length, 'parts have valid ETags. Proceeding to complete upload.');

        var confirmData = {
            _token: '{{ csrf_token() }}',
            upload_id: uploadId,
            parts: parts, // All parts are validated to have ETags
            title: formData.get('title'),
            description: formData.get('description'),
            rival_team_name: formData.get('rival_team_name'),
            category_id: formData.get('category_id'),
            division: formData.get('division'),
            rugby_situation_id: formData.get('rugby_situation_id'),
            match_date: formData.get('match_date'),
            visibility_type: formData.get('visibility_type'),
            assignment_notes: formData.get('assignment_notes')
        };

        // Add XML content if present
        if (xmlContent) {
            confirmData['xml_content'] = xmlContent;
        }

        // Add assigned players if any
        var assignedPlayers = formData.getAll('assigned_players[]');
        if (assignedPlayers.length > 0) {
            confirmData['assigned_players'] = assignedPlayers;
        }

        // Retry wrapper with exponential backoff
        function attemptComplete(attemptNumber) {
            var maxAttempts = 3;

            console.log('Attempting to complete multipart upload (attempt', attemptNumber, 'of', maxAttempts + ')');

            $.ajax({
                url: '{{ route("api.upload.multipart.complete") }}',
                method: 'POST',
                timeout: 300000, // 5 minutes timeout for completion (increased from 60s)
                data: confirmData,
                success: function(response) {
                    if (response.success) {
                        $('#progressBar').css('background-color', 'var(--color-accent, #4B9DA9)');
                        $('#progressText').text('¡Completado!');

                        var successMsg = '<i class="fas fa-check-double text-success"></i> <strong>¡Video subido exitosamente!</strong>';
                        successMsg += '<br><small class="text-muted">El video se está optimizando en segundo plano.</small>';

                        if (response.xml_import) {
                            successMsg += '<br><small class="text-success"><i class="fas fa-file-code"></i> ';
                            successMsg += response.xml_import.clips_created + ' clips importados';
                            if (response.xml_import.categories_created > 0) {
                                successMsg += ', ' + response.xml_import.categories_created + ' categorías creadas';
                            }
                            successMsg += '</small>';
                        }

                        $('#uploadStatus').html(successMsg);

                        setTimeout(function() {
                            window.location.href = response.redirect || '/videos';
                        }, 2500);
                    } else {
                        showError('Error finalizando video: ' + response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Complete multipart error (attempt ' + attemptNumber + '):', xhr);

                    // Retry with exponential backoff if not max attempts
                    if (attemptNumber < maxAttempts) {
                        var delay = 2000 * Math.pow(2, attemptNumber - 1); // 2s, 4s, 8s
                        console.log('Retrying complete in', delay, 'ms...');
                        $('#uploadStatus').html('<i class="fas fa-redo text-warning"></i> Reintentando finalización (intento ' + (attemptNumber + 1) + ')...');

                        setTimeout(function() {
                            attemptComplete(attemptNumber + 1);
                        }, delay);
                    } else {
                        showError('Error finalizando upload después de ' + maxAttempts + ' intentos: ' + (xhr.responseJSON?.message || 'Error de conexión'));
                    }
                }
            });
        }

        // Start first attempt
        attemptComplete(1);
    }

    function uploadToSpaces(presignedUrl, uploadId, file, formData) {
        var xhr = new XMLHttpRequest();

        // Progress tracking - upload directo a Spaces
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var percentComplete = Math.round((e.loaded / e.total) * 100);
                $('#progressBar').css('width', percentComplete + '%');
                $('#progressText').text(percentComplete + '%');

                var loaded = (e.loaded / (1024 * 1024)).toFixed(1);
                var total = (e.total / (1024 * 1024)).toFixed(1);

                if (percentComplete < 100) {
                    $('#uploadStatus').html('<i class="fas fa-cloud-upload-alt text-info"></i> Subiendo directo a la nube: ' + loaded + 'MB / ' + total + 'MB');
                } else {
                    $('#progressBar').css('background-color', '#ffc107');
                    $('#uploadStatus').html('<i class="fas fa-spinner fa-spin text-warning"></i> Video subido. Creando registro...');
                }
            }
        });

        xhr.addEventListener('load', function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                // Step 3: Confirm upload and create video record
                confirmUpload(uploadId, formData);
            } else {
                console.error('Spaces upload failed:', xhr.status, xhr.responseText);
                showError('Error subiendo a la nube. Código: ' + xhr.status);
            }
        });

        xhr.addEventListener('error', function() {
            console.error('Spaces upload network error');
            showError('Error de conexión subiendo a la nube');
        });

        // Upload directly to Spaces
        xhr.open('PUT', presignedUrl);
        xhr.setRequestHeader('Content-Type', file.type || 'video/mp4');
        xhr.setRequestHeader('x-amz-acl', 'public-read');
        xhr.send(file);
    }

    function confirmUpload(uploadId, formData) {
        var statusMsg = '<i class="fas fa-spinner fa-spin text-warning"></i> Guardando información del video...';
        if (xmlContent) {
            statusMsg += '<br><small class="text-info"><i class="fas fa-file-code"></i> Importando clips desde XML...</small>';
        }
        $('#uploadStatus').html(statusMsg);

        // Build confirm data from form
        var confirmData = {
            _token: '{{ csrf_token() }}',
            upload_id: uploadId,
            title: formData.get('title'),
            description: formData.get('description'),
            rival_team_name: formData.get('rival_team_name'),
            category_id: formData.get('category_id'),
            division: formData.get('division'),
            rugby_situation_id: formData.get('rugby_situation_id'),
            match_date: formData.get('match_date'),
            visibility_type: formData.get('visibility_type'),
            assignment_notes: formData.get('assignment_notes')
        };

        // Add XML content if present
        if (xmlContent) {
            confirmData['xml_content'] = xmlContent;
        }

        // Add assigned players if any
        var assignedPlayers = formData.getAll('assigned_players[]');
        if (assignedPlayers.length > 0) {
            confirmData['assigned_players'] = assignedPlayers;
        }

        $.ajax({
            url: '{{ route("api.upload.confirm") }}',
            method: 'POST',
            timeout: 60000, // 60 seconds timeout
            data: confirmData,
            success: function(response) {
                if (response.success) {
                    $('#progressBar').css('background-color', 'var(--color-accent, #4B9DA9)');
                    $('#progressText').text('¡Completado!');

                    var successMsg = '<i class="fas fa-check-double text-success"></i> <strong>¡Video subido exitosamente!</strong>';
                    successMsg += '<br><small class="text-muted">El video se está optimizando en segundo plano.</small>';

                    // Show XML import stats if available
                    if (response.xml_import) {
                        successMsg += '<br><small class="text-success"><i class="fas fa-file-code"></i> ';
                        successMsg += response.xml_import.clips_created + ' clips importados';
                        if (response.xml_import.categories_created > 0) {
                            successMsg += ', ' + response.xml_import.categories_created + ' categorías creadas';
                        }
                        successMsg += '</small>';
                    }

                    $('#uploadStatus').html(successMsg);

                    setTimeout(function() {
                        window.location.href = response.redirect || '/videos';
                    }, 2500);
                } else {
                    showError('Error guardando video: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('Confirm upload error:', xhr);
                showError('Error guardando información: ' + (xhr.responseJSON?.message || 'Error de conexión'));
            }
        });
    }

    function showError(message) {
        $('#progressBar').css('background-color', '#dc3545');
        $('#progressText').text('Error');
        $('#uploadStatus').html('<i class="fas fa-exclamation-triangle text-danger"></i> ' + message);
        $('#uploadBtn').prop('disabled', false).html('<i class="fas fa-upload"></i> Subir Video');
    }

    // Rugby situation preview
    $('#rugby_situation_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var situationName = selectedOption.text();
        var situationColor = selectedOption.data('color');
        var categoryName = selectedOption.parent('optgroup').attr('label');
        
        if ($(this).val()) {
            $('#rugbySituationPreview').show();
            $('#situationPreviewText').text(situationName);
            $('#situationPreviewBadge').text(categoryName).css('background-color', situationColor);
        } else {
            $('#rugbySituationPreview').hide();
        }
    });

    // Initialize Select2 for players
    $('#assigned_players').select2({
        theme: 'bootstrap4',
        placeholder: 'Buscar y seleccionar jugadores...',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "No se encontraron jugadores";
            },
            searching: function() {
                return "Buscando...";
            },
            removeAllItems: function() {
                return "Quitar todos los elementos";
            }
        }
    });

    // Handle visibility type changes
    function togglePlayerAssignment() {
        const specificSelected = $('#visibility_specific').is(':checked');
        const playerSection = $('#player-assignment-section');

        if (specificSelected) {
            playerSection.show();
            $('#assigned_players').prop('required', true);
        } else {
            playerSection.hide();
            $('#assigned_players').prop('required', false);
            $('#assigned_players').val(null).trigger('change'); // Clear selection
        }
    }

    // Initialize visibility state
    togglePlayerAssignment();

    // Listen for visibility type changes
    $('input[name="visibility_type"]').on('change', function() {
        togglePlayerAssignment();
    });

    // Handle division field visibility (solo para categoría Adultas)
    function toggleDivisionField() {
        const categorySelect = $('#category_id');
        const selectedCategoryText = categorySelect.find('option:selected').text();
        const divisionGroup = $('#division-group');

        if (selectedCategoryText === 'Adultas') {
            divisionGroup.show();
            $('#division').prop('required', true);
        } else {
            divisionGroup.hide();
            $('#division').prop('required', false);
            $('#division').val(''); // Clear selection
        }
    }

    // Initialize division field state
    toggleDivisionField();

    // Listen for category changes
    $('#category_id').on('change', function() {
        toggleDivisionField();
    });

    // Auto-generate title based on selections (only if title is empty)
    var titleInput = $('#title');
    var isUserTyping = false;
    var organizationName = '{{ $organizationName }}';

    titleInput.on('input', function() {
        isUserTyping = $(this).val().length > 0;
    });

    function cleanText(text) {
        return text.replace(/\s+/g, ' ').trim();
    }

    $('#rival_team_name, #category_id, #rugby_situation_id').on('change input', function() {
        if (isUserTyping) return; // Don't auto-generate if user is typing

        var rivalTeam = $('#rival_team_name').val().trim();
        var category = cleanText($('#category_id option:selected').text());
        var rugbySituation = cleanText($('#rugby_situation_id option:selected').text());

        if (category && category !== 'Seleccionar categoría...') {
            var title = '';

            if (rugbySituation && rugbySituation !== 'Sin situación específica') {
                title = rugbySituation;
            } else {
                title = 'Análisis ' + category;
            }

            if (rivalTeam) {
                title += ' - ' + organizationName + ' vs ' + rivalTeam;
            } else {
                title += ' - ' + organizationName;
            }

            titleInput.val(title);
        }
    });
});
</script>

<style>
/* Visibility option styling */
.form-check {
    padding: 0.75rem;
    border: 2px solid var(--color-secondary, #018790);
    border-radius: 8px;
    background: var(--color-bg-card, #0f0f0f);
    transition: all 0.3s ease;
    cursor: pointer;
}

.form-check:hover {
    border-color: var(--color-accent, #00B7B5);
    background: var(--color-primary-hover, #003d4a);
}

.form-check-input:checked + .form-check-label {
    color: var(--color-accent, #00B7B5);
}

.form-check:has(.form-check-input:checked) {
    border-color: var(--color-accent, #00B7B5);
    background: var(--color-primary-hover, #003d4a);
    box-shadow: 0 2px 8px rgba(0, 183, 181, 0.25);
}

.form-check-label {
    cursor: pointer;
    width: 100%;
    margin-bottom: 0;
}

.form-check-label strong {
    color: var(--color-text, #ffffff);
    font-size: 1rem;
}

.form-check-label small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    line-height: 1.2;
    color: #aaaaaa !important;
}

#player-assignment-section {
    padding: 1rem;
    background: var(--color-primary-hover, #003d4a);
    border: 2px solid var(--color-accent, #00B7B5);
    border-radius: 8px;
    margin-top: 1rem;
}

#player-assignment-section label {
    color: var(--color-accent, #00B7B5);
    font-weight: 600;
}

/* Custom file input para tema oscuro */
.custom-file-label {
    background-color: var(--color-primary-hover, #003d4a);
    border-color: var(--color-secondary, #018790);
    color: var(--color-text, #ffffff);
}

.custom-file-label::after {
    background-color: var(--color-primary, #005461);
    color: var(--color-text, #ffffff);
}

/* Labels del formulario */
label {
    color: var(--color-text, #ffffff);
}

/* Select2 tema oscuro */
.select2-container--bootstrap4 .select2-selection {
    background-color: var(--color-primary-hover, #003d4a) !important;
    border-color: var(--color-secondary, #018790) !important;
    color: var(--color-text, #ffffff) !important;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
    background-color: var(--color-accent, #00B7B5) !important;
    border-color: var(--color-accent, #00B7B5) !important;
    color: white !important;
}

.select2-container--bootstrap4 .select2-dropdown {
    background-color: var(--color-primary-hover, #003d4a);
    border-color: var(--color-secondary, #018790);
}

.select2-container--bootstrap4 .select2-results__option {
    color: var(--color-text, #ffffff);
}

.select2-container--bootstrap4 .select2-results__option--highlighted {
    background-color: var(--color-accent, #00B7B5) !important;
}
</style>

@endsection