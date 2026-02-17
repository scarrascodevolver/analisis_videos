/**
 * RugbyHub - Simple Batch Upload
 * Uses PROVEN multipart logic from commit 1630d674
 * @version 3.0 - Simplified
 * @date 2026-02-02
 */

'use strict';

// Global state
let batchVideos = [];
let currentUploadIndex = 0;
let isUploading = false;

// Constants
const CHUNK_SIZE = 100 * 1024 * 1024; // 100MB (aumentado de 50MB para menos chunks)
const MAX_FILE_SIZE = 8 * 1024 * 1024 * 1024; // 8GB
const MULTIPART_THRESHOLD = 100 * 1024 * 1024; // 100MB

// ============================================================================
// VIDEO MANAGEMENT
// ============================================================================

function handleFileSelect(files) {
    const filesArray = Array.from(files);
    
    if (filesArray.length === 0) {
        return;
    }
    
    // Clear previous
    batchVideos = [];
    $('#videosContainer').empty();
    
    // Add each video
    filesArray.forEach((file, index) => {
        if (!isValidVideoFile(file)) {
            return;
        }
        
        const video = {
            id: 'video_' + Date.now() + '_' + index,
            file: file,
            title: generateTitle(file.name),
            status: 'pending',
            progress: 0
        };
        
        batchVideos.push(video);
        renderVideoCard(video);
    });
    
    updateUI();
}

function isValidVideoFile(file) {
    const validExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
    const ext = file.name.split('.').pop().toLowerCase();
    
    if (!validExtensions.includes(ext)) {
        showError('Formato no válido: ' + file.name);
        return false;
    }
    
    if (file.size > MAX_FILE_SIZE) {
        showError('Archivo muy grande (máx 8GB): ' + file.name);
        return false;
    }
    
    return true;
}

function generateTitle(filename) {
    return filename
        .replace(/\.[^/.]+$/, '') // Remove extension
        .replace(/_/g, ' ')
        .replace(/-/g, ' ')
        .trim();
}

function renderVideoCard(video) {
    const sizeMB = (video.file.size / (1024 * 1024)).toFixed(1);
    const isFirst = batchVideos.length === 1; // First video is master by default

    const html = `
        <div class="col-lg-4 col-md-6 col-12 mb-3" id="card-wrapper-${video.id}">
            <div class="card video-card h-100" id="card-${video.id}">
                <div class="card-body p-3">
                    <!-- Filename (truncated) -->
                    <h6 class="mb-2 text-truncate" title="${video.file.name}">
                        <i class="fas fa-video"></i> ${video.file.name}
                    </h6>
                    <small class="text-muted d-block mb-2">${sizeMB} MB</small>

                    <!-- Master & Group Controls -->
                    <div class="form-row mb-2">
                        <div class="col-6">
                            <div class="custom-control custom-radio">
                                <input type="radio"
                                       class="custom-control-input master-radio"
                                       id="master-${video.id}"
                                       name="master_video"
                                       value="${video.id}"
                                       ${isFirst ? 'checked' : ''}
                                       onchange="handleMasterChange('${video.id}')">
                                <label class="custom-control-label" for="master-${video.id}">
                                    <i class="fas fa-star text-warning"></i> Master
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input group-checkbox"
                                       id="group-${video.id}"
                                       onchange="handleGroupChange('${video.id}')">
                                <label class="custom-control-label" for="group-${video.id}">
                                    <i class="fas fa-layer-group text-info"></i> En grupo
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Title (always visible) -->
                    <div class="form-group mb-2">
                        <input type="text"
                               class="form-control form-control-sm"
                               id="title-${video.id}"
                               value="${video.title}"
                               placeholder="Título del video">
                    </div>

                    <!-- More Options Button -->
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-block mb-2"
                            onclick="toggleMoreOptions('${video.id}')">
                        <i class="fas fa-plus" id="toggle-icon-${video.id}"></i>
                        <span id="toggle-text-${video.id}">Más opciones</span>
                    </button>

                    <!-- More Options (collapsible) -->
                    <div id="more-options-${video.id}" style="display:none;">
                        <!-- Fecha -->
                        <div class="form-group mb-2">
                            <label class="small mb-1">Fecha del partido</label>
                            <input type="date"
                                   class="form-control form-control-sm"
                                   id="date-${video.id}"
                                   placeholder="Usar común">
                        </div>

                        <!-- Categoría -->
                        <div class="form-group mb-2">
                            <label class="small mb-1">Categoría</label>
                            <select class="form-control form-control-sm" id="category-${video.id}">
                                <option value="">Usar común</option>
                                ${renderCategoryOptions()}
                            </select>
                        </div>

                        <!-- División -->
                        <div class="form-group mb-2">
                            <label class="small mb-1">División</label>
                            <select class="form-control form-control-sm" id="division-${video.id}">
                                <option value="">Usar común</option>
                                <option value="primera">Primera</option>
                                <option value="intermedia">Intermedia</option>
                                <option value="unica">Única</option>
                            </select>
                        </div>

                        <!-- Rival -->
                        <div class="form-group mb-2">
                            <label class="small mb-1">Equipo Rival</label>
                            <select class="form-control form-control-sm rival-select" id="rival-${video.id}">
                                <option value="">Usar común</option>
                            </select>
                        </div>

                        <!-- Situación -->
                        <div class="form-group mb-2">
                            <label class="small mb-1">Situación</label>
                            <select class="form-control form-control-sm" id="situation-${video.id}">
                                <option value="">Usar común</option>
                                ${renderSituationOptions()}
                            </select>
                        </div>

                        <!-- Visibilidad -->
                        <div class="form-group mb-2">
                            <label class="small mb-1">Visibilidad</label>
                            <select class="form-control form-control-sm" id="visibility-${video.id}">
                                <option value="">Usar común</option>
                                <option value="public">Pública</option>
                                <option value="forwards">Solo Forwards</option>
                                <option value="backs">Solo Backs</option>
                                <option value="specific">Específicos</option>
                            </select>
                        </div>

                        <!-- Descripción -->
                        <div class="form-group mb-2">
                            <label class="small mb-1">Descripción</label>
                            <textarea class="form-control form-control-sm"
                                      id="description-${video.id}"
                                      rows="2"
                                      placeholder="Descripción individual..."></textarea>
                        </div>

                        <!-- XML (only if master) -->
                        <div class="form-group mb-2" id="xml-section-${video.id}" style="display:${isFirst ? 'block' : 'none'};">
                            <label class="small mb-1">
                                <i class="fas fa-file-code"></i> XML LongoMatch
                            </label>
                            <div class="custom-file">
                                <input type="file"
                                       class="custom-file-input"
                                       id="xml-${video.id}"
                                       accept=".xml"
                                       onchange="handleXmlUpload('${video.id}', this.files[0])">
                                <label class="custom-file-label small" for="xml-${video.id}">
                                    Seleccionar XML...
                                </label>
                            </div>
                            <small class="text-muted d-block" id="xml-status-${video.id}"></small>
                        </div>
                    </div>

                    <!-- Progress (hidden initially) -->
                    <div id="progress-${video.id}" style="display:none;" class="mt-2">
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                 id="progress-bar-${video.id}"
                                 role="progressbar"
                                 style="width: 0%">
                                <span id="progress-text-${video.id}" class="small">0%</span>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1" id="status-${video.id}">Preparando...</small>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#videosContainer').append(html);
}

function renderSituationOptions() {
    // This will be populated from backend config
    const situations = window.BatchUploadConfig?.rugbySituations || {};
    let html = '';

    for (const [category, items] of Object.entries(situations)) {
        html += `<optgroup label="${category}">`;
        items.forEach(item => {
            html += `<option value="${item.id}">${item.name}</option>`;
        });
        html += `</optgroup>`;
    }

    return html;
}

function renderCategoryOptions() {
    // Get categories from common select
    let html = '';
    $('#category_id option').each(function() {
        const value = $(this).val();
        const text = $(this).text();
        if (value) {
            html += `<option value="${value}">${text}</option>`;
        }
    });
    return html;
}

function toggleMoreOptions(videoId) {
    const $moreOptions = $(`#more-options-${videoId}`);
    const $icon = $(`#toggle-icon-${videoId}`);
    const $text = $(`#toggle-text-${videoId}`);

    if ($moreOptions.is(':visible')) {
        $moreOptions.slideUp(200);
        $icon.removeClass('fa-minus').addClass('fa-plus');
        $text.text('Más opciones');
    } else {
        $moreOptions.slideDown(200);
        $icon.removeClass('fa-plus').addClass('fa-minus');
        $text.text('Menos opciones');

        // Initialize Select2 for individual rival if not already initialized
        const $rivalSelect = $(`#rival-${videoId}`);
        if (!$rivalSelect.hasClass('select2-hidden-accessible')) {
            $rivalSelect.select2({
                placeholder: 'Buscar rival...',
                allowClear: true,
                ajax: {
                    url: $('#config-routes').data('rival-autocomplete'),
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                    },
                    cache: true
                },
                tags: true,
                createTag: function(params) {
                    const term = $.trim(params.term);
                    if (term === '') return null;
                    return {
                        id: 'new:' + term,
                        text: term + ' (crear nuevo)',
                        newTag: true
                    };
                }
            });
        }
    }
}

function handleMasterChange(videoId) {
    // Show/hide XML section based on master
    $('.video-card').each(function() {
        const id = $(this).attr('id').replace('card-', '');
        const isMaster = $(`#master-${id}`).is(':checked');
        $(`#xml-section-${id}`).toggle(isMaster);
    });
}

function handleGroupChange(videoId) {
    // Could add visual indication that videos are grouped
    updateGroupIndicator();
}

function updateGroupIndicator() {
    const groupedVideos = $('.group-checkbox:checked').length;
    if (groupedVideos > 0) {
        // Show indicator that X videos will be grouped
        console.log(`${groupedVideos} videos will be grouped`);
    }
}

function handleXmlUpload(videoId, file) {
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const content = e.target.result;

        // Validate XML
        $.ajax({
            url: $('#config-routes').data('validate-xml'),
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                xml_content: content
            },
            success: function(response) {
                if (response.success) {
                    $(`#xml-status-${videoId}`).html(
                        `<i class="fas fa-check-circle text-success"></i> ` +
                        `XML válido (${response.preview.clips_count} clips)`
                    );

                    // Store XML content
                    const video = batchVideos.find(v => v.id === videoId);
                    if (video) {
                        video.xmlContent = content;
                        video.xmlFile = file;
                    }
                } else {
                    $(`#xml-status-${videoId}`).html(
                        `<i class="fas fa-exclamation-circle text-danger"></i> ${response.message}`
                    );
                }
            },
            error: function() {
                $(`#xml-status-${videoId}`).html(
                    `<i class="fas fa-exclamation-circle text-danger"></i> Error validando XML`
                );
            }
        });
    };
    reader.readAsText(file);
}

function updateUI() {
    const count = batchVideos.length;
    $('#videoCount').text(count);
    $('#uploadCount').text(count);
    
    if (count > 0) {
        $('#videosSection').show();
        $('#uploadBtn').prop('disabled', false);
    } else {
        $('#videosSection').hide();
        $('#uploadBtn').prop('disabled', true);
    }
}

// ============================================================================
// UPLOAD - Sequential (one at a time)
// ============================================================================

function startBatchUpload() {
    if (batchVideos.length === 0) {
        showError('No hay videos seleccionados');
        return;
    }
    
    // Validate common config
    const commonData = getCommonData();
    if (!commonData) {
        return; // Validation failed
    }
    
    // Disable upload button
    isUploading = true;
    $('#uploadBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subiendo...');
    
    // Start uploading first video
    currentUploadIndex = 0;
    uploadNextVideo(commonData);
}

function uploadNextVideo(commonData) {
    if (currentUploadIndex >= batchVideos.length) {
        // All done!
        onAllUploadsComplete();
        return;
    }
    
    const video = batchVideos[currentUploadIndex];
    video.status = 'uploading';
    
    // Show progress
    $(`#progress-${video.id}`).show();
    
    // Get video-specific data
    const videoData = getVideoData(video, commonData);
    
    // Upload (using PROVEN logic)
    uploadSingleVideo(video, videoData)
        .then(() => {
            video.status = 'completed';
            video.progress = 100;
            updateVideoProgress(video.id, 100, 'completed', 'Completado ✓');
            
            // Next video
            currentUploadIndex++;
            uploadNextVideo(commonData);
        })
        .catch((error) => {
            video.status = 'error';
            updateVideoProgress(video.id, 0, 'error', 'Error: ' + error.message);
            showError('Error subiendo ' + video.file.name + ': ' + error.message);
            
            // Continue with next (don't stop all uploads)
            currentUploadIndex++;
            uploadNextVideo(commonData);
        });
}

function getCommonData() {
    const matchDate = $('#match_date').val();
    const categoryId = $('#category_id').val();
    const situationId = $('#rugby_situation_id').val();
    
    if (!matchDate) {
        showError('Fecha del partido es requerida');
        return null;
    }
    
    if (!categoryId) {
        showError('Categoría es requerida');
        return null;
    }
    
    if (!situationId) {
        showError('Situación de rugby es requerida');
        return null;
    }
    
    return {
        match_date: matchDate,
        rival_team_id: $('#rival_id').val() || null,
        category_id: categoryId,
        division: $('#division').val() || null,
        rugby_situation_id: situationId,
        visibility_type: $('input[name="visibility_type"]:checked').val(),
        assigned_players: $('#assigned_players').val() || [],
        description: $('#description').val() || ''
    };
}

function getVideoData(video, commonData) {
    const title = $(`#title-${video.id}`).val();
    const isMaster = $(`#master-${video.id}`).is(':checked');
    const inGroup = $(`#group-${video.id}`).is(':checked');

    // Individual fields (override common if set)
    const individualDate = $(`#date-${video.id}`).val();
    const individualCategory = $(`#category-${video.id}`).val();
    const individualDivision = $(`#division-${video.id}`).val();
    const individualRival = $(`#rival-${video.id}`).val();
    const individualSituation = $(`#situation-${video.id}`).val();
    const individualVisibility = $(`#visibility-${video.id}`).val();
    const individualDescription = $(`#description-${video.id}`).val();

    const data = {
        title: title,
        filename: video.file.name,
        ...commonData
    };

    // Override with individual values if set
    if (individualDate) {
        data.match_date = individualDate;
    }

    if (individualCategory) {
        data.category_id = individualCategory;
    }

    if (individualDivision) {
        data.division = individualDivision;
    }

    if (individualRival) {
        data.rival_team_id = individualRival;
    }

    if (individualSituation) {
        data.rugby_situation_id = individualSituation;
    }

    if (individualVisibility) {
        data.visibility_type = individualVisibility;
    }

    if (individualDescription) {
        data.description = individualDescription;
    }

    // Multi-camera group logic
    if (inGroup) {
        data.is_master = isMaster;
        data.group_key = getGroupKey(); // Generate unique key for this batch

        if (!isMaster) {
            // Slaves need camera angle (use title as angle for now)
            data.camera_angle = title;
        }
    }

    // XML content (only for master)
    if (isMaster && video.xmlContent) {
        data.xml_content = video.xmlContent;
    }

    return data;
}

function getGroupKey() {
    // Generate unique key for this batch upload group
    if (!window.batchGroupKey) {
        const commonData = getCommonData();
        const timestamp = Date.now();
        window.batchGroupKey = `batch_${commonData.match_date}_${commonData.category_id}_${timestamp}`;
    }
    return window.batchGroupKey;
}

function updateVideoProgress(videoId, percent, status, message) {
    $(`#progress-bar-${videoId}`).css('width', percent + '%');
    $(`#progress-text-${videoId}`).text(Math.round(percent) + '%');
    $(`#status-${videoId}`).html(message);
    
    const $bar = $(`#progress-bar-${videoId}`);
    $bar.removeClass('bg-success bg-danger bg-warning bg-info');
    
    if (status === 'completed') {
        $bar.addClass('bg-success');
    } else if (status === 'error') {
        $bar.addClass('bg-danger');
    } else {
        $bar.addClass('bg-info');
    }
}

function onAllUploadsComplete() {
    isUploading = false;
    
    const completed = batchVideos.filter(v => v.status === 'completed').length;
    const failed = batchVideos.filter(v => v.status === 'error').length;
    
    if (failed === 0) {
        showSuccess(`¡${completed} video(s) subido(s) exitosamente!`);
        setTimeout(() => {
            window.location.href = $('#config-routes').data('videos-index');
        }, 2000);
    } else {
        showError(`Completado: ${completed} exitosos, ${failed} fallidos`);
        $('#uploadBtn').prop('disabled', false).html('<i class="fas fa-cloud-upload-alt"></i> Reintentar Fallidos');
    }
}

// ============================================================================
// UPLOAD SINGLE VIDEO - PROVEN LOGIC FROM COMMIT 1630d674
// ============================================================================

function uploadSingleVideo(video, formData) {
    const file = video.file;
    const useMultipart = file.size >= MULTIPART_THRESHOLD;
    
    if (useMultipart) {
        console.log('File size:', (file.size / (1024 * 1024)).toFixed(2), 'MB - Using multipart upload');
        return uploadVideoMultipart(video, file, formData);
    } else {
        return uploadVideoSimple(video, file, formData);
    }
}

// Simple upload for files < 100MB
function uploadVideoSimple(video, file, formData) {
    return new Promise((resolve, reject) => {
        // This is simplified - you can implement if needed
        // For now, we focus on multipart since that's what was failing
        reject(new Error('Simple upload not implemented - use multipart'));
    });
}

// Multipart upload - PROVEN LOGIC
function uploadVideoMultipart(video, file, formData) {
    return new Promise((resolve, reject) => {
        const chunkSize = CHUNK_SIZE;
        const totalParts = Math.ceil(file.size / chunkSize);
        
        updateVideoProgress(video.id, 0, 'uploading', `Iniciando multipart (${totalParts} partes)...`);
        
        // Step 1: Initiate
        $.ajax({
            url: $('#config-routes').data('multipart-initiate'),
            method: 'POST',
            timeout: 30000,
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                filename: file.name,
                content_type: file.type || 'video/mp4',
                file_size: file.size,
                parts_count: totalParts
            },
            success: function(response) {
                if (response.success) {
                    const uploadId = response.upload_id;
                    updateVideoProgress(video.id, 5, 'uploading', 'Subiendo partes...');
                    
                    // Step 2: Upload parts (PROVEN QUEUE-BASED LOGIC)
                    uploadPartsWithQueue(video, file, uploadId, chunkSize, totalParts, formData, resolve, reject);
                } else {
                    reject(new Error(response.message || 'Error iniciando multipart'));
                }
            },
            error: function(xhr) {
                reject(new Error('Error iniciando multipart: ' + xhr.status));
            }
        });
    });
}

// PROVEN QUEUE-BASED UPLOAD (from commit 1630d674)
function uploadPartsWithQueue(video, file, uploadId, chunkSize, totalParts, formData, resolve, reject) {
    const pendingParts = [];
    const inProgressParts = new Set();
    const completedParts = [];
    const retryCount = {};
    let hasError = false;
    let uploadedBytes = 0;

    const maxConcurrent = 10; // ✅ Aumentado de 2 a 10 para uploads más rápidos
    const maxRetries = 3;
    const maxRetriesNetwork = 15;
    
    // Initialize queue
    for (let i = 1; i <= totalParts; i++) {
        pendingParts.push(i);
    }
    
    function uploadNextPart() {
        if (hasError) {
            return;
        }
        
        if (inProgressParts.size >= maxConcurrent || pendingParts.length === 0) {
            return;
        }
        
        const partNumber = pendingParts.shift();
        inProgressParts.add(partNumber);
        
        const start = (partNumber - 1) * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);
        
        console.log('Starting upload of part', partNumber, '(pending:', pendingParts.length, 'in-progress:', inProgressParts.size, ')');
        
        // Get presigned URL
        $.ajax({
            url: $('#config-routes').data('multipart-part-urls'),
            method: 'POST',
            timeout: 30000,
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                upload_id: uploadId,
                part_numbers: [partNumber]  // ✅ Array
            },
            success: function(response) {
                if (response.success) {
                    uploadPart(chunk, partNumber, response.urls[partNumber]);  // ✅ Correct access
                } else {
                    retryOrFail(partNumber, chunk, 'Error obteniendo URL de parte ' + partNumber, false);
                }
            },
            error: function(xhr, status) {
                const isNetworkError = !xhr.status || xhr.status === 0 || status === 'timeout' || status === 'error';
                const errorMsg = status === 'timeout' ? 'Timeout obteniendo URL' : 'Error preparando parte';
                retryOrFail(partNumber, chunk, errorMsg, isNetworkError);
            }
        });
    }
    
    function uploadPart(chunk, partNumber, presignedUrl) {
        const xhr = new XMLHttpRequest();
        xhr.timeout = 600000; // 10 minutes (suficiente para chunks de 100MB)
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const totalUploaded = uploadedBytes + e.loaded;
                const percent = 5 + (totalUploaded / file.size) * 85; // 5-90%
                updateVideoProgress(video.id, percent, 'uploading', `Parte ${completedParts.length}/${totalParts}`);
            }
        });
        
        xhr.addEventListener('load', function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                const etag = xhr.getResponseHeader('ETag');
                
                if (!etag) {
                    console.error('Part', partNumber, 'uploaded but ETag missing');
                    retryOrFail(partNumber, chunk, 'ETag no disponible', false);
                    return;
                }
                
                console.log('Part', partNumber, 'uploaded successfully');
                
                inProgressParts.delete(partNumber);
                
                // Prevent duplicates
                const existingPart = completedParts.find(p => p.PartNumber === partNumber);
                if (!existingPart) {
                    completedParts.push({
                        PartNumber: partNumber,
                        ETag: etag.replace(/"/g, '')
                    });
                }
                
                uploadedBytes += chunk.size;
                checkCompletion();
            } else {
                retryOrFail(partNumber, chunk, 'Error subiendo parte: ' + xhr.status, false);
            }
        });
        
        xhr.addEventListener('error', function() {
            retryOrFail(partNumber, chunk, 'Error de conexión', true);
        });
        
        xhr.addEventListener('timeout', function() {
            retryOrFail(partNumber, chunk, 'Timeout', true);
        });
        
        xhr.open('PUT', presignedUrl);
        xhr.send(chunk);
    }
    
    function retryOrFail(partNumber, chunk, errorMsg, isNetworkError) {
        inProgressParts.delete(partNumber);
        
        if (!retryCount[partNumber]) {
            retryCount[partNumber] = 0;
        }
        retryCount[partNumber]++;
        
        const maxRetriesForPart = isNetworkError ? maxRetriesNetwork : maxRetries;
        
        if (retryCount[partNumber] <= maxRetriesForPart) {
            console.log('Retrying part', partNumber, '(attempt', retryCount[partNumber], ')', isNetworkError ? '[NETWORK]' : '');
            
            const delay = isNetworkError 
                ? Math.min(5000 * retryCount[partNumber], 60000)  // Linear: 5s, 10s, 15s...
                : Math.min(1000 * Math.pow(2, retryCount[partNumber] - 1), 16000);  // Exponential
            
            setTimeout(function() {
                pendingParts.unshift(partNumber);
                uploadNextPart();
            }, delay);
        } else {
            hasError = true;
            console.error('Max retries exceeded for part', partNumber);
            reject(new Error(errorMsg + ' (máximo de reintentos alcanzado)'));
        }
    }
    
    function checkCompletion() {
        if (completedParts.length === totalParts) {
            // Validate all parts present
            const partNumbers = completedParts.map(p => p.PartNumber).sort((a, b) => a - b);
            const missingParts = [];
            
            for (let i = 1; i <= totalParts; i++) {
                if (!partNumbers.includes(i)) {
                    missingParts.push(i);
                }
            }
            
            if (missingParts.length > 0) {
                reject(new Error('Faltan las partes: ' + missingParts.join(', ')));
                return;
            }
            
            console.log('All parts uploaded successfully. Completing...');
            updateVideoProgress(video.id, 90, 'uploading', 'Finalizando...');
            
            // Step 3: Complete multipart
            completeMultipart(uploadId, completedParts, formData);
        } else {
            uploadNextPart();
        }
    }
    
    function completeMultipart(uploadId, parts, formData) {
        $.ajax({
            url: $('#config-routes').data('multipart-complete'),
            method: 'POST',
            timeout: 300000, // 5 minutes
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                upload_id: uploadId,
                parts: parts,
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    resolve(response);
                } else {
                    reject(new Error(response.message || 'Error completando multipart'));
                }
            },
            error: function(xhr) {
                reject(new Error('Error completando multipart: ' + xhr.status));
            }
        });
    }
    
    // Start initial batch
    for (let i = 0; i < maxConcurrent && i < totalParts; i++) {
        uploadNextPart();
    }
}

// ============================================================================
// UTILITIES
// ============================================================================

function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: message,
        timer: 3000
    });
}

function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message
    });
}

// ============================================================================
// INIT
// ============================================================================

$(document).ready(function() {
    // File input change
    $('#video_files').on('change', function(e) {
        handleFileSelect(e.target.files);
    });
    
    // Upload button
    $('#uploadBtn').on('click', function(e) {
        e.preventDefault();
        startBatchUpload();
    });
    
    // Initialize Select2
    $('#rival_id').select2({
        placeholder: 'Seleccionar rival...',
        allowClear: true
    });
    
    $('#assigned_players').select2({
        placeholder: 'Seleccionar jugadores...',
        allowClear: true
    });
});
