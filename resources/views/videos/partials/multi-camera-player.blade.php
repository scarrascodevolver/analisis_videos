{{-- Multi-Camera Player Component --}}
<div id="multiCameraPlayer" style="display: none;">
    <div class="card card-outline card-rugby">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-video"></i> Vista Multi-Cámara
            </h5>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-rugby-outline" id="exitMultiCameraBtn">
                    <i class="fas fa-times"></i> Salir de Vista Multi-Cámara
                </button>
            </div>
        </div>
        <div class="card-body p-2" style="background: #000;">
            {{-- Warning for unsynced videos --}}
            <div id="unsyncedWarning" class="alert alert-warning mb-2" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i>
                Algunos ángulos no están sincronizados. Pueden mostrar momentos diferentes del partido.
                <button class="btn btn-sm btn-warning ml-2" id="syncAllBtn">
                    <i class="fas fa-sync-alt"></i> Sincronizar Ahora
                </button>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>

            {{-- Master Video (Large) --}}
            <div id="masterVideoContainer" class="mb-2">
                <div class="position-relative">
                    <video id="multiMasterVideo" controls style="width: 100%; max-height: 500px; background: #000;">
                        <source src="{{ route('videos.stream', $video->id) }}" type="video/mp4">
                    </video>
                    <div class="position-absolute" style="top: 10px; left: 10px;">
                        <span class="badge badge-primary badge-lg">
                            <i class="fas fa-video"></i> {{ $video->camera_angle ?? 'Master' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Slave Videos (Thumbnails) --}}
            <div id="slaveVideosContainer" class="row">
                {{-- Will be populated by JavaScript --}}
            </div>

            {{-- Timeline (Shared) --}}
            <div class="card mt-2" style="background: #1a1a1a; border-color: #444;">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <button class="btn btn-sm btn-rugby" id="multiPlayPauseBtn">
                                <i class="fas fa-play"></i> Play
                            </button>
                            <button class="btn btn-sm btn-secondary" id="multiBackward10Btn">
                                <i class="fas fa-backward"></i> 10s
                            </button>
                            <button class="btn btn-sm btn-secondary" id="multiForward10Btn">
                                <i class="fas fa-forward"></i> 10s
                            </button>
                        </div>
                        <div class="text-white">
                            <i class="fas fa-clock"></i>
                            <span id="multiCurrentTime">00:00</span> / <span id="multiDuration">00:00</span>
                        </div>
                    </div>
                    <input type="range" class="custom-range" id="multiSeekBar" min="0" max="100" value="0" step="0.1">
                    <small class="text-muted d-block text-center mt-1">
                        <i class="fas fa-info-circle"></i>
                        Todos los videos se controlan sincronizadamente desde esta timeline
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Template for Slave Video Thumbnail --}}
<template id="slaveVideoTemplate">
    <div class="col-md-4 mb-2 slave-video-item" data-video-id="">
        <div class="card h-100" style="background: #2a2a2a; border-color: #444;">
            <div class="card-body p-2">
                <div class="position-relative">
                    <video class="slave-video" controls style="width: 100%; max-height: 200px; background: #000;">
                        {{-- Source will be set by JavaScript --}}
                    </video>
                    <div class="position-absolute" style="top: 5px; left: 5px;">
                        <span class="badge badge-info badge-sm slave-angle-name"></span>
                    </div>
                    <div class="position-absolute" style="top: 5px; right: 5px;">
                        <span class="badge badge-success badge-sm slave-sync-badge" style="display: none;">
                            <i class="fas fa-check"></i> <span class="slave-offset-text"></span>
                        </span>
                        <span class="badge badge-warning badge-sm slave-unsync-badge" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i> No Sync
                        </span>
                    </div>
                </div>
                <div class="mt-1 text-center">
                    <button class="btn btn-xs btn-rugby slave-swap-btn" title="Intercambiar con Master">
                        <i class="fas fa-exchange-alt"></i> Hacer Master
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Multi-Camera Player JavaScript
(function() {
    function init() {
        const $ = window.jQuery;
        if (!$) {
            console.error('jQuery not loaded yet for multi-camera player');
            return;
        }

        let multiCameraActive = false;
    let masterVideo = null;
    let slaveVideos = [];
    let isPlaying = false;
    let isSeeking = false;

    // Public function to activate multi-camera view
    window.viewMultiCamera = function(angleId) {
        if (!angleId) {
            // Load all angles for the current video
            loadMultiCameraView();
        } else {
            // Load specific angle
            loadMultiCameraView(angleId);
        }
    };

    function loadMultiCameraView(specificAngleId = null) {
        $.ajax({
            url: `/videos/{{ $video->id }}/multi-camera/angles`,
            method: 'GET',
            success: function(response) {
                if (response.success && response.angles.length > 0) {
                    initMultiCameraPlayer(response.angles);
                } else {
                    toastr.warning('No hay ángulos disponibles para vista multi-cámara');
                }
            },
            error: function() {
                toastr.error('Error al cargar ángulos');
            }
        });
    }

    function initMultiCameraPlayer(angles) {
        // Hide single video player
        $('#videoSection .card').first().fadeOut();

        // Show multi-camera player
        $('#multiCameraPlayer').fadeIn();

        multiCameraActive = true;
        masterVideo = document.getElementById('multiMasterVideo');
        slaveVideos = [];

        // Render slave videos
        renderSlaveVideos(angles);

        // Setup controls
        setupMultiCameraControls();

        // Check for unsynced videos
        checkUnsyncedVideos(angles);
    }

    function renderSlaveVideos(angles) {
        const container = $('#slaveVideosContainer');
        container.empty();

        angles.forEach(angle => {
            const template = document.getElementById('slaveVideoTemplate').content.cloneNode(true);
            const item = $(template).find('.slave-video-item');

            item.attr('data-video-id', angle.id);
            item.find('.slave-angle-name').text(angle.camera_angle);

            // Get stream URL
            $.ajax({
                url: `/videos/${angle.id}/multi-camera/stream-url`,
                method: 'GET',
                async: false,
                success: function(response) {
                    if (response.success) {
                        const video = item.find('.slave-video')[0];
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
                            item.find('.slave-sync-badge').show();
                            item.find('.slave-offset-text').text(`${angle.sync_offset > 0 ? '+' : ''}${angle.sync_offset}s`);
                        } else {
                            item.find('.slave-unsync-badge').show();
                        }
                    }
                }
            });

            // Swap button
            item.find('.slave-swap-btn').on('click', function() {
                swapWithMaster(angle.id);
            });

            container.append(item);
        });
    }

    function setupMultiCameraControls() {
        // Play/Pause button
        $('#multiPlayPauseBtn').off('click').on('click', function() {
            if (isPlaying) {
                pauseAll();
            } else {
                playAll();
            }
        });

        // Seek backward/forward
        $('#multiBackward10Btn').off('click').on('click', () => {
            const newTime = Math.max(0, masterVideo.currentTime - 10);
            seekAll(newTime);
        });

        $('#multiForward10Btn').off('click').on('click', () => {
            const newTime = Math.min(masterVideo.duration, masterVideo.currentTime + 10);
            seekAll(newTime);
        });

        // Seek bar
        $('#multiSeekBar').off('input change').on('input', function() {
            const time = ($(this).val() / 100) * masterVideo.duration;
            seekAll(time);
        }).on('mousedown touchstart', function() {
            isSeeking = true;
            pauseAll();
        }).on('mouseup touchend', function() {
            isSeeking = false;
        });

        // Master video events
        masterVideo.addEventListener('loadedmetadata', function() {
            $('#multiDuration').text(formatTime(this.duration));
            $('#multiSeekBar').attr('max', this.duration);
        });

        masterVideo.addEventListener('timeupdate', function() {
            if (!isSeeking) {
                const current = this.currentTime;
                const duration = this.duration;
                $('#multiCurrentTime').text(formatTime(current));
                $('#multiSeekBar').val((current / duration) * 100);

                // Verify slave sync (every second)
                verifySyncDrift();
            }
        });

        // Exit multi-camera
        $('#exitMultiCameraBtn').off('click').on('click', function() {
            exitMultiCamera();
        });

        // Sync all button
        $('#syncAllBtn').off('click').on('click', function() {
            const firstUnsynced = slaveVideos.find(v => !v.synced);
            if (firstUnsynced) {
                window.openSyncModal(firstUnsynced.id);
            }
        });
    }

    function playAll() {
        masterVideo.play();

        slaveVideos.forEach(slave => {
            if (slave.synced) {
                slave.element.currentTime = masterVideo.currentTime + slave.offset;
            } else {
                slave.element.currentTime = masterVideo.currentTime;
            }
            slave.element.play();
        });

        isPlaying = true;
        $('#multiPlayPauseBtn').html('<i class="fas fa-pause"></i> Pause');
    }

    function pauseAll() {
        masterVideo.pause();
        slaveVideos.forEach(slave => slave.element.pause());

        isPlaying = false;
        $('#multiPlayPauseBtn').html('<i class="fas fa-play"></i> Play');
    }

    function seekAll(time) {
        masterVideo.currentTime = time;

        slaveVideos.forEach(slave => {
            if (slave.synced) {
                slave.element.currentTime = time + slave.offset;
            } else {
                slave.element.currentTime = time;
            }
        });
    }

    function verifySyncDrift() {
        const masterTime = masterVideo.currentTime;

        slaveVideos.forEach(slave => {
            if (slave.synced && !slave.element.paused) {
                const expectedTime = masterTime + slave.offset;
                const actualTime = slave.element.currentTime;
                const drift = Math.abs(actualTime - expectedTime);

                // If drift > 0.5s, re-sync
                if (drift > 0.5) {
                    console.log(`Re-syncing ${slave.angle}, drift: ${drift.toFixed(2)}s`);
                    slave.element.currentTime = expectedTime;
                }
            }
        });
    }

    function checkUnsyncedVideos(angles) {
        const hasUnsynced = angles.some(a => !a.is_synced);
        if (hasUnsynced) {
            $('#unsyncedWarning').show();
        } else {
            $('#unsyncedWarning').hide();
        }
    }

    function swapWithMaster(slaveId) {
        // TODO: Implement swap functionality
        // This would require changing the video sources
        toastr.info('Funcionalidad de intercambio próximamente...');
    }

    function exitMultiCamera() {
        // Pause all videos
        pauseAll();

        // Hide multi-camera player
        $('#multiCameraPlayer').fadeOut();

        // Show single video player
        $('#videoSection .card').first().fadeIn();

        multiCameraActive = false;
        slaveVideos = [];
    }

        function formatTime(seconds) {
            if (isNaN(seconds)) return '00:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // Update viewMultiCamera function in multi-camera-section.blade.php
        // to call this window.viewMultiCamera
    }

    // Initialize when DOM and jQuery are ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
