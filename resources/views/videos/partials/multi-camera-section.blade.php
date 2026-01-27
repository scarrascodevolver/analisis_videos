{{-- Multi-Camera / Multi-Angle Section --}}
@if(in_array(auth()->user()->role, ['analista', 'entrenador']))
<div class="card card-outline card-rugby mt-3" id="multiCameraSection">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-video"></i> Ángulos de Cámara
        </h3>
        <div class="card-tools">
            @if($video->isPartOfGroup())
                <span class="badge badge-rugby">
                    <i class="fas fa-check-circle"></i> Grupo Multi-Cámara
                </span>
            @endif
        </div>
    </div>
    <div class="card-body">
        {{-- Current Video Info --}}
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle"></i>
            <strong>Video Actual:</strong>
            {{ $video->camera_angle ?? ($video->is_master ? 'Master / Tribuna Central' : 'Video Individual') }}
            @if($video->is_master)
                <span class="badge badge-primary ml-2">MASTER</span>
            @endif
        </div>

        @if($video->isPartOfGroup())
            {{-- Show Associated Angles --}}
            <h5 class="mb-3">
                <i class="fas fa-camera"></i> Ángulos Asociados
            </h5>

            <div id="anglesContainer">
                {{-- Will be populated by JavaScript --}}
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin"></i> Cargando ángulos...
                </div>
            </div>

            {{-- Add New Angle Button --}}
            @if($video->is_master)
                <button type="button" class="btn btn-rugby-outline btn-block mt-3" data-toggle="modal" data-target="#associateAngleModal">
                    <i class="fas fa-plus-circle"></i> Asociar Nuevo Ángulo
                </button>
            @endif
        @else
            {{-- Not part of a group yet --}}
            <div class="text-center py-4">
                <i class="fas fa-video fa-3x text-muted mb-3"></i>
                <p class="text-muted">Este video no forma parte de un grupo multi-cámara.</p>
                <p class="text-muted mb-3">
                    Puedes asociar otros videos del mismo partido para ver múltiples ángulos simultáneamente.
                </p>
                <button type="button" class="btn btn-rugby" data-toggle="modal" data-target="#associateAngleModal">
                    <i class="fas fa-plus-circle"></i> Crear Grupo Multi-Cámara
                </button>
            </div>
        @endif
    </div>
</div>

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
                        <i class="fas fa-search fa-2x mb-2"></i>
                        <p>Usa el buscador para encontrar videos</p>
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
// Multi-Camera Management JavaScript
(function() {
    const videoId = {{ $video->id }};
    const isPartOfGroup = {{ $video->isPartOfGroup() ? 'true' : 'false' }};

    // Load angles on page load
    if (isPartOfGroup) {
        loadAngles();
    }

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
        $.ajax({
            url: `/videos/${videoId}/multi-camera/angles`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
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

    function searchVideos() {
        const query = $('#searchVideoInput').val();

        $.ajax({
            url: '/videos/search-for-angles',
            method: 'GET',
            data: {
                query: query,
                exclude_group_id: {{ $video->video_group_id ? "'".$video->video_group_id."'" : 'null' }}
            },
            success: function(response) {
                if (response.success) {
                    renderSearchResults(response.videos);
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

        const btn = $('#confirmAssociateBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Asociando...');

        $.ajax({
            url: `/videos/{{ $video->id }}/multi-camera/associate`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                slave_video_id: videoId,
                camera_angle: cameraAngle
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Ángulo asociado correctamente');
                    $('#associateAngleModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
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
            data: { _token: '{{ csrf_token() }}' },
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
        toastr.success(message);
    }

    function showError(message) {
        toastr.error(message);
    }
})();
</script>
@endif
