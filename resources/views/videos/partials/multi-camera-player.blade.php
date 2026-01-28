{{-- Multi-Camera Player Component - Side by Side Layout (OPTIMIZED) --}}
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
    {{-- Loading Spinner --}}
    <div id="slaveLoadingSpinner" style="display: none; text-align: center; padding: 40px; color: #00B7B5;">
        <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
        <p>Cargando ángulos de cámara...</p>
    </div>
    {{-- Will be populated by JavaScript --}}
</div>

{{-- Template for Slave Video --}}
<template id="slaveVideoTemplate">
    <div class="slave-video-card mb-2" data-video-id="" style="background: #000; border-radius: 8px; overflow: hidden; border: 1px solid #222;">
        {{-- Video Player --}}
        <div style="position: relative; background: #000;">
            <video class="slave-video" controls style="width: 100%; height: auto; display: block;"
                   preload="none"
                   crossorigin="anonymous">
                {{-- Source will be set by JavaScript --}}
            </video>

            {{-- Floating Header (over video) --}}
            <div class="d-flex justify-content-between align-items-center" style="position: absolute; top: 0; left: 0; right: 0; z-index: 10; padding: 6px 10px; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                <div class="d-flex align-items-center flex-grow-1" style="min-width: 0;">
                    <h6 class="mb-0 slave-angle-name" style="color: #00B7B5; font-size: 11px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-shadow: 0 1px 3px rgba(0,0,0,0.8);">
                        <i class="fas fa-video" style="font-size: 9px;"></i> <span></span>
                    </h6>
                    {{-- Sync Badge Inline --}}
                    <span class="slave-sync-badge badge badge-success ml-2" style="display: none; font-size: 8px; padding: 2px 5px; background: rgba(40, 167, 69, 0.9);">
                        <i class="fas fa-check"></i> <span class="slave-offset-text"></span>
                    </span>
                    <span class="slave-unsync-badge badge badge-warning ml-2" style="display: none; font-size: 8px; padding: 2px 5px; background: rgba(255, 193, 7, 0.9);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </span>
                </div>
                {{-- Compact Icon Buttons --}}
                <div class="ml-2" style="white-space: nowrap;">
                    <button class="btn btn-sm btn-info slave-sync-btn" style="padding: 2px 6px; font-size: 10px; background: rgba(23, 162, 184, 0.9); border: none;" title="Sincronizar">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-danger slave-remove-btn ml-1" style="padding: 2px 6px; font-size: 10px; background: rgba(220, 53, 69, 0.9); border: none;" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Multi-Camera Player JavaScript - Side by Side (OPTIMIZED)
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

        // ═══════════════════════════════════════════════════════════
        // OPTIMIZATION 1 & 2: Single Master Listener with AbortController
        // ═══════════════════════════════════════════════════════════
        let masterSyncController = null;
        let lastSyncTime = 0;
        const SYNC_THROTTLE_MS = 250; // 4 times per second

        function setupMasterSync() {
            // Cleanup previous listeners if exist
            if (masterSyncController) {
                masterSyncController.abort();
            }

            // Create new AbortController for cleanup
            masterSyncController = new AbortController();
            const signal = masterSyncController.signal;

            // Single play event - syncs ALL slaves
            masterVideo.addEventListener('play', () => {
                slaveVideos.forEach(slave => {
                    const expectedTime = masterVideo.currentTime + slave.offset;
                    slave.element.currentTime = expectedTime;
                    slave.element.play().catch(err => console.warn('Play failed:', err));
                });
            }, { signal });

            // Single pause event - syncs ALL slaves
            masterVideo.addEventListener('pause', () => {
                slaveVideos.forEach(slave => {
                    slave.element.pause();
                });
            }, { signal });

            // Single seeked event - syncs ALL slaves
            masterVideo.addEventListener('seeked', () => {
                slaveVideos.forEach(slave => {
                    const expectedTime = masterVideo.currentTime + slave.offset;
                    slave.element.currentTime = expectedTime;
                });
            }, { signal });

            // ═══════════════════════════════════════════════════════════
            // OPTIMIZATION 3: Throttled timeupdate (250ms = 4 times/sec)
            // ═══════════════════════════════════════════════════════════
            masterVideo.addEventListener('timeupdate', () => {
                const now = Date.now();

                // Throttle: only check every 250ms
                if (now - lastSyncTime < SYNC_THROTTLE_MS) {
                    return;
                }
                lastSyncTime = now;

                // Only sync if master is playing
                if (masterVideo.paused) {
                    return;
                }

                // Check drift for ALL slaves in a single pass
                slaveVideos.forEach(slave => {
                    if (slave.element.paused) {
                        return;
                    }

                    const expectedTime = masterVideo.currentTime + slave.offset;
                    const drift = Math.abs(slave.element.currentTime - expectedTime);

                    // Only correct if drift > 0.5 seconds
                    if (drift > 0.5) {
                        console.log(`Re-syncing ${slave.angle}, drift: ${drift.toFixed(2)}s`);
                        slave.element.currentTime = expectedTime;
                    }
                });
            }, { signal });

            console.log('✅ Master sync listeners initialized with throttling');
        }

        function cleanupMasterSync() {
            if (masterSyncController) {
                masterSyncController.abort();
                masterSyncController = null;
                console.log('🧹 Master sync listeners cleaned up');
            }
        }

        // Public function to activate multi-camera view
        window.activateMultiCamera = function(angles) {
            if (!angles || angles.length === 0) {
                console.log('No angles to display');
                return;
            }

            isMultiCameraActive = true;

            // Show multi-camera layout
            activateSideBySideLayout();

            // Show loading spinner
            $('#slaveLoadingSpinner').show();

            // Render slave videos
            renderSlaveVideos(angles);

            console.log('Multi-camera activated with', angles.length, 'angle(s)');
        };

        window.deactivateMultiCamera = function() {
            isMultiCameraActive = false;

            // Cleanup all listeners
            cleanupMasterSync();

            deactivateSideBySideLayout();
            slaveVideos = [];
        };

        function activateSideBySideLayout() {
            const mainContainer = $('.video-container').first();
            const slaveContainer = $('#slaveVideosContainer');

            // Create flex container - master 75%, slaves 25%
            if (!mainContainer.parent().hasClass('multi-camera-layout')) {
                mainContainer.add(slaveContainer).wrapAll('<div class="multi-camera-layout row" style="align-items: stretch; min-height: 85vh;"></div>');
                mainContainer.wrap('<div class="col-lg-9 col-md-8" style="display: flex; align-items: center; justify-content: center; padding: 20px 0;"></div>');
                slaveContainer.wrap('<div class="col-lg-3 col-md-4" style="overflow-y: auto; max-height: 90vh;"></div>');
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
            const loadingSpinner = $('#slaveLoadingSpinner');

            // Clear previous content (keep spinner)
            container.find('.slave-video-card').remove();

            let loadedCount = 0;
            const totalAngles = angles.length;

            angles.forEach(angle => {
                const template = document.getElementById('slaveVideoTemplate').content.cloneNode(true);
                const card = $(template).find('.slave-video-card');

                card.attr('data-video-id', angle.id);
                card.find('.slave-angle-name span').text(angle.camera_angle);

                // ═══════════════════════════════════════════════════════════
                // OPTIMIZATION 4: Lazy loading - metadata loads on demand
                // ═══════════════════════════════════════════════════════════
                $.ajax({
                    url: `/videos/${angle.id}/multi-camera/stream-url`,
                    method: 'GET',
                    timeout: 10000, // 10 second timeout
                    success: function(response) {
                        if (response.success) {
                            const video = card.find('.slave-video')[0];
                            video.src = response.stream_url;

                            // Load metadata explicitly when ready
                            video.load();

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

                            // Hide spinner when all angles loaded
                            loadedCount++;
                            if (loadedCount === totalAngles) {
                                loadingSpinner.fadeOut(300);

                                // Setup master sync ONCE after all slaves loaded
                                setupMasterSync();
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(`Failed to load angle ${angle.id}:`, error);

                        // Still count as "loaded" to hide spinner
                        loadedCount++;
                        if (loadedCount === totalAngles) {
                            loadingSpinner.fadeOut(300);

                            // Setup sync even if some failed
                            if (slaveVideos.length > 0) {
                                setupMasterSync();
                            }
                        }

                        if (typeof showToast === 'function') {
                            showToast(`Error cargando ${angle.camera_angle}`, 'error');
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

                        // Re-setup sync with remaining slaves
                        if (slaveVideos.length > 0) {
                            setupMasterSync();
                        }
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
