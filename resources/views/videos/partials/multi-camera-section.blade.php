{{-- Multi-Camera Section - Hidden (functionality moved to header button) --}}
<div style="display: none;"></div>

{{-- Modal: Associate Angle --}}
<div class="modal fade" id="associateAngleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-rugby">
                <h5 class="modal-title text-white">
                    <i class="fas fa-plus-circle"></i> Asociar Nuevo Ángulo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Search Videos --}}
                <div class="form-group">
                    <label>Buscar Video</label>
                    <div class="input-group">
                        <input type="text" id="searchVideoInput" class="form-control" placeholder="Buscar por título, equipo o rival...">
                        <div class="input-group-append">
                            <button class="btn btn-rugby" type="button" id="searchVideoBtn">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Busca videos del mismo partido que quieras asociar como ángulos adicionales.
                    </small>
                </div>

                {{-- Search Results --}}
                <div id="searchResults">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <p>Cargando videos recientes...</p>
                    </div>
                </div>

                {{-- Camera Angle Input --}}
                <div id="angleInputSection" style="display: none;">
                    <hr>
                    <h6>Selecciona el Nombre del Ángulo:</h6>
                    <div class="form-group">
                        <label>Nombre del Ángulo</label>
                        <select id="cameraAngleSelect" class="form-control">
                            <option value="">-- Seleccionar --</option>
                            <option value="Lateral Derecha">Lateral Derecha</option>
                            <option value="Lateral Izquierda">Lateral Izquierda</option>
                            <option value="Try Zone">Try Zone</option>
                            <option value="Drone / Aérea">Drone / Aérea</option>
                            <option value="In-Goal">In-Goal</option>
                            <option value="custom">Personalizado...</option>
                        </select>
                    </div>
                    <div class="form-group" id="customAngleInput" style="display: none;">
                        <label>Nombre Personalizado</label>
                        <input type="text" id="customAngleName" class="form-control" placeholder="Ej: Cámara Línea 22">
                    </div>
                    <input type="hidden" id="selectedVideoId">
                    <button type="button" class="btn btn-rugby btn-block" id="confirmAssociateBtn">
                        <i class="fas fa-check"></i> Asociar Ángulo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Template for Angle Card --}}
<template id="angleCardTemplate">
    <div class="angle-card card mb-2" data-video-id="">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-1">
                        <i class="fas fa-video text-rugby"></i>
                        <span class="angle-name"></span>
                    </h6>
                    <small class="text-muted angle-title"></small>
                </div>
                <div class="col-md-3 text-center">
                    <span class="sync-status">
                        {{-- Will be populated by JS --}}
                    </span>
                </div>
                <div class="col-md-3 text-right">
                    <button class="btn btn-sm btn-info sync-btn" title="Sincronizar">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-rugby view-btn" title="Ver en Multi-Cámara">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger remove-btn" title="Eliminar ángulo">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- Template for Search Result --}}
<template id="searchResultTemplate">
    <div class="search-result-item card mb-2" data-video-id="">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-1 result-title"></h6>
                    <small class="text-muted result-info"></small>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-sm btn-rugby select-video-btn">
                        <i class="fas fa-check"></i> Seleccionar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Multi-Camera Management JavaScript (UPDATED: Multi-Group Support)
(function() {
    function init() {
        const $ = window.jQuery;
        if (!$) {
            console.error('jQuery not loaded yet for multi-camera');
            return;
        }

        const videoId = {{ $video->id }};
        const isPartOfGroup = {{ $video->isPartOfGroup() ? 'true' : 'false' }};

        // ═══════════════════════════════════════════════════════════
        // MULTI-GROUP SUPPORT: Determine active group
        // ═══════════════════════════════════════════════════════════
        let activeGroupId = null;

        @php
            $videoGroups = $video->videoGroups;
            $masterGroup = $videoGroups->where('pivot.is_master', true)->first();
            $firstGroup = $videoGroups->first();
        @endphp

        @if($masterGroup)
            // Video is master in at least one group - use that group
            activeGroupId = {{ $masterGroup->id }};
            console.log('Video is MASTER in group {{ $masterGroup->id }}');
        @elseif($firstGroup)
            // Video is slave in all groups - use first group
            activeGroupId = {{ $firstGroup->id }};
            console.log('Video is SLAVE, using first group {{ $firstGroup->id }}');
        @else
            // Fallback to old system (backward compatibility)
            activeGroupId = null;
            console.log('Using old single-group system');
        @endif

        @if($videoGroups->count() > 1)
            console.warn('⚠️ Video is in {{ $videoGroups->count() }} groups - showing group ' + activeGroupId);
        @endif

    // Load angles on page load
    if (isPartOfGroup) {
        loadAngles();
    }

    // Load recent videos when modal opens
    $('#associateAngleModal').on('shown.bs.modal', function() {
        // Load recent videos automatically
        searchVideos('');
    });

    // Search videos
    $('#searchVideoBtn, #searchVideoInput').on('click keypress', function(e) {
        if (e.type === 'click' || e.which === 13) {
            searchVideos();
        }
    });

    // Camera angle select change
    $('#cameraAngleSelect').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customAngleInput').show();
        } else {
            $('#customAngleInput').hide();
        }
    });

    // Confirm associate
    $('#confirmAssociateBtn').on('click', function() {
        associateAngle();
    });

    function loadAngles() {
        const params = activeGroupId ? { group_id: activeGroupId } : {};

        $.ajax({
            url: `/videos/${videoId}/multi-camera/angles`,
            method: 'GET',
            data: params,
            success: function(response) {
                if (response.success) {
                    // Update activeGroupId if returned (new system)
                    if (response.current_group_id) {
                        activeGroupId = response.current_group_id;
                        console.log('Active group:', activeGroupId);
                    }

                    // Show multi-group indicator if applicable
                    if (response.groups && response.groups.length > 1) {
                        console.log('Video is in', response.groups.length, 'groups:', response.groups);
                    }

                    renderAngles(response.angles);
                }
            },
            error: function() {
                showError('Error al cargar ángulos');
            }
        });
    }

    function renderAngles(angles) {
        const container = $('#anglesContainer');
        container.empty();

        if (angles.length === 0) {
            container.html(`
                <div class="text-center text-muted py-3">
                    <i class="fas fa-info-circle"></i> No hay ángulos asociados todavía
                </div>
            `);
            return;
        }

        angles.forEach(angle => {
            const template = document.getElementById('angleCardTemplate').content.cloneNode(true);
            const card = $(template).find('.angle-card');

            card.attr('data-video-id', angle.id);
            card.find('.angle-name').text(angle.camera_angle);
            card.find('.angle-title').text(angle.title);

            // Sync status
            if (angle.is_synced) {
                card.find('.sync-status').html(`
                    <span class="badge badge-success">
                        <i class="fas fa-check-circle"></i> Sincronizado
                        <br><small>${angle.sync_offset > 0 ? '+' : ''}${angle.sync_offset}s</small>
                    </span>
                `);
            } else {
                card.find('.sync-status').html(`
                    <span class="badge badge-warning">
                        <i class="fas fa-exclamation-triangle"></i> No Sincronizado
                    </span>
                `);
            }

            // Event handlers
            card.find('.sync-btn').on('click', function() {
                openSyncModal(angle.id);
            });

            card.find('.view-btn').on('click', function() {
                viewMultiCamera(angle.id);
            });

            card.find('.remove-btn').on('click', function() {
                removeAngle(angle.id);
            });

            container.append(card);
        });
    }

    function searchVideos(customQuery = null) {
        const query = customQuery !== null ? customQuery : $('#searchVideoInput').val();

        // UPDATED: No longer exclude videos in groups (multi-group support)
        $.ajax({
            url: '/videos/search-for-angles',
            method: 'GET',
            data: {
                query: query
                // NOTE: exclude_group_id removed to allow videos in multiple groups
            },
            success: function(response) {
                if (response.success) {
                    if (response.videos.length > 0) {
                        renderSearchResults(response.videos);
                    } else {
                        $('#searchResults').html(`
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-search"></i> No se encontraron videos
                            </div>
                        `);
                    }
                }
            },
            error: function() {
                showError('Error al buscar videos');
            }
        });
    }

    function renderSearchResults(videos) {
        const container = $('#searchResults');
        container.empty();

        if (videos.length === 0) {
            container.html(`
                <div class="text-center text-muted py-3">
                    <i class="fas fa-search"></i> No se encontraron videos
                </div>
            `);
            return;
        }

        videos.forEach(video => {
            const template = document.getElementById('searchResultTemplate').content.cloneNode(true);
            const item = $(template).find('.search-result-item');

            item.attr('data-video-id', video.id);
            item.find('.result-title').text(video.title);
            item.find('.result-info').text(`Fecha: ${video.match_date || 'N/A'} • ${formatFileSize(video.file_size)}`);

            item.find('.select-video-btn').on('click', function() {
                selectVideo(video.id, video.title);
            });

            container.append(item);
        });
    }

    function selectVideo(id, title) {
        $('#selectedVideoId').val(id);
        $('#angleInputSection').slideDown();

        // Highlight selected
        $('.search-result-item').removeClass('border-rugby');
        $(`.search-result-item[data-video-id="${id}"]`).addClass('border-rugby');
    }

    function associateAngle() {
        const videoId = $('#selectedVideoId').val();
        let cameraAngle = $('#cameraAngleSelect').val();

        if (!videoId || !cameraAngle) {
            showError('Por favor selecciona un video y un nombre de ángulo');
            return;
        }

        if (cameraAngle === 'custom') {
            cameraAngle = $('#customAngleName').val();
            if (!cameraAngle) {
                showError('Por favor ingresa un nombre personalizado');
                return;
            }
        }

        // Check maximum angles limit (3 slaves max)
        const currentSlaveCount = $('.slave-video-card').length;
        if (currentSlaveCount >= 3) {
            showError('⚠️ Máximo 3 ángulos permitidos por razones de rendimiento.<br><small>Con más de 3 videos sincronizados, el navegador puede congelarse.</small>');
            return;
        }

        const btn = $('#confirmAssociateBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Asociando...');

        $.ajax({
            url: `/videos/{{ $video->id }}/multi-camera/associate`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                slave_video_id: videoId,
                camera_angle: cameraAngle,
                group_id: activeGroupId // Pass active group ID
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Ángulo asociado correctamente');
                    $('#associateAngleModal').modal('hide');

                    // Update activeGroupId if returned
                    if (response.group_id) {
                        activeGroupId = response.group_id;
                        console.log('Video associated to group:', activeGroupId);
                    }

                    // Angles are included in response, no need for second request
                    if (response.angles && response.angles.length > 0) {
                        console.log('✅ Angles returned in associate response:', response.angles.length);

                        // Activate multi-camera with the angles
                        if (typeof window.activateMultiCamera === 'function') {
                            window.activateMultiCamera(response.angles, activeGroupId);
                        } else {
                            console.error('⚠️ activateMultiCamera function not found');
                        }
                    } else {
                        console.warn('⚠️ No angles returned in response, trying fallback GET request...');

                        // Fallback: fetch angles if not included in response (shouldn't happen)
                        const params = activeGroupId ? { group_id: activeGroupId } : {};
                        $.ajax({
                            url: `/videos/{{ $video->id }}/multi-camera/angles`,
                            method: 'GET',
                            data: params,
                            success: function(anglesResponse) {
                                if (anglesResponse.success && anglesResponse.angles.length > 0) {
                                    window.activateMultiCamera(anglesResponse.angles, activeGroupId);
                                }
                            },
                            error: function() {
                                console.error('❌ Failed to fetch angles after association');
                            }
                        });
                    }
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.message || 'Error al asociar ángulo');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Asociar Ángulo');
            }
        });
    }

    function removeAngle(angleId) {
        if (!confirm('¿Estás seguro de eliminar este ángulo del grupo?')) {
            return;
        }

        $.ajax({
            url: `/videos/${angleId}/multi-camera/remove`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}',
                group_id: activeGroupId // Pass active group ID
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Ángulo eliminado');
                    loadAngles();
                }
            },
            error: function() {
                showError('Error al eliminar ángulo');
            }
        });
    }

    function openSyncModal(angleId) {
        // Call global openSyncModal function defined in sync-modal.blade.php
        if (typeof window.openSyncModal === 'function') {
            window.openSyncModal(angleId);
        } else {
            console.error('Sync modal not loaded');
            alert('Error: Modal de sincronización no disponible');
        }
    }

    function viewMultiCamera(angleId) {
        // Call global viewMultiCamera function defined in multi-camera-player.blade.php
        if (typeof window.viewMultiCamera === 'function') {
            window.viewMultiCamera(angleId);
        } else {
            console.error('Multi-camera player not loaded');
            alert('Error: Vista multi-cámara no disponible');
        }
    }

    function formatFileSize(bytes) {
        if (!bytes) return 'N/A';
        const gb = bytes / (1024 * 1024 * 1024);
        if (gb >= 1) return gb.toFixed(2) + ' GB';
        const mb = bytes / (1024 * 1024);
        return mb.toFixed(2) + ' MB';
    }

    function showSuccess(message) {
        if (typeof showToast === 'function') {
            showToast(message, 'success');
        }
    }

        function showError(message) {
            if (typeof showToast === 'function') {
                showToast(message, 'error');
            }
        }
    }

    // Initialize when DOM and jQuery are ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

