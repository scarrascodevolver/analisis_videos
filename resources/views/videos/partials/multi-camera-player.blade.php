{{-- Multi-Camera Player Component - Side by Side Layout --}}
<div id="multiCameraPlayer" style="display: none;">
    {{-- Master Video (Left 70%) --}}
    <div class="video-container" style="position: relative; background: #000; border-radius: 8px; overflow: hidden;">
        <video id="multiMasterVideo" controls style="width: 100%; height: auto; max-height: 550px; display: block;"
               preload="metadata"
               crossorigin="anonymous"
               x-webkit-airplay="allow">
            <source src="{{ route('videos.stream', $video) }}" type="video/mp4">
            Tu navegador no soporta la reproducción de video.
        </video>
    </div>
</div>

{{-- Slave Videos Container (Right 30%) --}}
<div id="slaveVideosContainer" style="display: none;">
    {{-- Will be populated by JavaScript --}}
</div>

{{-- Template for Slave Video --}}
<template id="slaveVideoTemplate">
    <div class="slave-video-card mb-3" data-video-id="" style="background: #1a1a1a; border-radius: 8px; overflow: hidden;">
        {{-- Video Title Header --}}
        <div style="padding: 8px 12px; background: #2a2a2a; border-bottom: 1px solid #444;">
            <h6 class="mb-0 slave-angle-name" style="color: #00B7B5; font-size: 14px; font-weight: 600;">
                <i class="fas fa-video"></i> <span></span>
            </h6>
            <small class="text-muted slave-video-title" style="font-size: 11px;"></small>
        </div>

        {{-- Video Player --}}
        <div style="position: relative; background: #000;">
            <video class="slave-video" controls style="width: 100%; height: auto; max-height: 250px; display: block;"
                   preload="metadata"
                   crossorigin="anonymous">
                {{-- Source will be set by JavaScript --}}
            </video>

            {{-- Sync Status Badge --}}
            <div style="position: absolute; top: 8px; right: 8px;">
                <span class="slave-sync-badge badge badge-success" style="display: none; font-size: 10px;">
                    <i class="fas fa-check"></i> <span class="slave-offset-text"></span>
                </span>
                <span class="slave-unsync-badge badge badge-warning" style="display: none; font-size: 10px;">
                    <i class="fas fa-exclamation-triangle"></i> No Sync
                </span>
            </div>
        </div>

        {{-- Control Buttons --}}
        <div style="padding: 10px 12px; background: #2a2a2a; border-top: 1px solid #444;">
            <div class="d-flex justify-content-between align-items-center">
                <button class="btn btn-sm btn-info slave-sync-btn" title="Sincronizar con Master">
                    <i class="fas fa-sync-alt"></i> Sincronizar
                </button>
                <button class="btn btn-sm btn-danger slave-remove-btn" title="Eliminar ángulo">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</template>

<script>
// Multi-Camera Player JavaScript - Side by Side
(function() {
    function init() {
        const $ = window.jQuery;
        if (!$) {
            console.error('jQuery not loaded yet for multi-camera player');
            return;
        }

        const videoId = {{ $video->id }};
        let masterVideo = document.getElementById('rugbyVideo');
        let multiMasterVideo = document.getElementById('multiMasterVideo');
        let slaveVideos = [];
        let isMultiCameraActive = false;

        // Public function to activate multi-camera view
        window.activateMultiCamera = function(angles) {
            if (!angles || angles.length === 0) {
                console.log('No angles to display');
                return;
            }

            isMultiCameraActive = true;

            // Show multi-camera layout
            activateSideBySideLayout();

            // Render slave videos
            renderSlaveVideos(angles);

            console.log('Multi-camera activated with', angles.length, 'angle(s)');
        };

        window.deactivateMultiCamera = function() {
            isMultiCameraActive = false;
            deactivateSideBySideLayout();
            slaveVideos = [];
        };

        function activateSideBySideLayout() {
            const mainContainer = $('.video-container').first();
            const slaveContainer = $('#slaveVideosContainer');

            // Create flex container
            if (!mainContainer.parent().hasClass('multi-camera-layout')) {
                mainContainer.add(slaveContainer).wrapAll('<div class="multi-camera-layout row"></div>');
                mainContainer.wrap('<div class="col-md-8"></div>');
                slaveContainer.wrap('<div class="col-md-4"></div>');
            }

            slaveContainer.show();
        }

        function deactivateSideBySideLayout() {
            $('#slaveVideosContainer').hide().empty();

            // Unwrap if needed
            const layout = $('.multi-camera-layout');
            if (layout.length) {
                $('.video-container').first().unwrap().unwrap();
                $('#slaveVideosContainer').unwrap();
            }
        }

        function renderSlaveVideos(angles) {
            const container = $('#slaveVideosContainer');
            container.empty();

            angles.forEach(angle => {
                const template = document.getElementById('slaveVideoTemplate').content.cloneNode(true);
                const card = $(template).find('.slave-video-card');

                card.attr('data-video-id', angle.id);
                card.find('.slave-angle-name span').text(angle.camera_angle);
                card.find('.slave-video-title').text(angle.title);

                // Get stream URL
                $.ajax({
                    url: `/videos/${angle.id}/multi-camera/stream-url`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const video = card.find('.slave-video')[0];
                            video.src = response.stream_url;

                            // Store video element and metadata
                            slaveVideos.push({
                                element: video,
                                id: angle.id,
                                angle: angle.camera_angle,
                                offset: angle.sync_offset || 0,
                                synced: angle.is_synced
                            });

                            // Show sync badge
                            if (angle.is_synced) {
                                card.find('.slave-sync-badge').show();
                                card.find('.slave-offset-text').text(`${angle.sync_offset > 0 ? '+' : ''}${angle.sync_offset}s`);
                            } else {
                                card.find('.slave-unsync-badge').show();
                            }

                            // Sync playback with master
                            syncWithMaster(video, angle.sync_offset || 0);
                        }
                    }
                });

                // Sync button
                card.find('.slave-sync-btn').on('click', function() {
                    if (typeof window.openSyncModal === 'function') {
                        window.openSyncModal(angle.id);
                    } else {
                        alert('Función de sincronización no disponible');
                    }
                });

                // Remove button
                card.find('.slave-remove-btn').on('click', function() {
                    removeAngle(angle.id, card);
                });

                container.append(card);
            });
        }

        function syncWithMaster(slaveVideo, offset) {
            // Sync play/pause
            masterVideo.addEventListener('play', () => {
                slaveVideo.currentTime = masterVideo.currentTime + offset;
                slaveVideo.play();
            });

            masterVideo.addEventListener('pause', () => {
                slaveVideo.pause();
            });

            // Sync seeking
            masterVideo.addEventListener('seeked', () => {
                slaveVideo.currentTime = masterVideo.currentTime + offset;
            });

            // Sync time periodically to prevent drift
            masterVideo.addEventListener('timeupdate', () => {
                if (!masterVideo.paused && !slaveVideo.paused) {
                    const expectedTime = masterVideo.currentTime + offset;
                    const drift = Math.abs(slaveVideo.currentTime - expectedTime);

                    if (drift > 0.5) {
                        console.log(`Re-syncing slave video, drift: ${drift.toFixed(2)}s`);
                        slaveVideo.currentTime = expectedTime;
                    }
                }
            });
        }

        function removeAngle(angleId, cardElement) {
            if (!confirm('¿Eliminar este ángulo del grupo?')) {
                return;
            }

            $.ajax({
                url: `/videos/${angleId}/multi-camera/remove`,
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        if (typeof showToast === 'function') {
                            showToast('Ángulo eliminado', 'success');
                        }

                        // Remove from DOM
                        cardElement.fadeOut(300, function() {
                            $(this).remove();

                            // If no more angles, deactivate multi-camera
                            if ($('#slaveVideosContainer .slave-video-card').length === 0) {
                                deactivateMultiCamera();
                            }
                        });

                        // Remove from slaveVideos array
                        slaveVideos = slaveVideos.filter(v => v.id !== angleId);
                    }
                },
                error: function() {
                    if (typeof showToast === 'function') {
                        showToast('Error al eliminar ángulo', 'error');
                    }
                }
            });
        }

        // Load angles on page load if video is part of a group
        @if($video->isPartOfGroup())
            $.ajax({
                url: `/videos/${videoId}/multi-camera/angles`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.angles.length > 0) {
                        window.activateMultiCamera(response.angles);
                    }
                }
            });
        @endif
    }

    // Initialize when DOM and jQuery are ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
