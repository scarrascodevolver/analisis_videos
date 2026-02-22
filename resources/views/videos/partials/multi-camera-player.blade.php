{{-- Multi-Camera Player Component - Side by Side Layout (OPTIMIZED) --}}
<div id="multiCameraPlayer" style="display: none;">
    {{-- Master Video (Left 70%) --}}
    <div class="video-container" style="position: relative; background: #000; border-radius: 8px; overflow: hidden;">
        <video id="multiMasterVideo" controls style="width: 100%; height: auto; display: block;"
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

<style>
    .slave-header {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 10;
        padding: 6px 10px;
        background: transparent;
    }

    .slave-angle-name {
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.85);
    }

    .slave-angle-icon,
    .slave-badge-icon,
    .slave-btn-icon {
        color: #ffffff;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.85);
    }

    .slave-angle-icon {
        font-size: 9px;
    }

    .slave-badge {
        display: none;
        font-size: 8px;
        padding: 2px 5px;
        background: transparent;
    }

    .slave-action-btn {
        padding: 2px 6px;
        font-size: 10px;
        background: transparent;
        border: none;
    }

    /* ═══════════════════════════════════════════════════════════
       RESIZABLE PANEL DIVIDER  (Hudl / Sportscode pattern)
       Drag left → master grows · Drag right → slaves grow
       Double-click → reset to 66/34 default
       ═══════════════════════════════════════════════════════════ */
    .mc-divider {
        flex: 0 0 7px;
        width: 7px;
        background: #1a1a1a;
        cursor: ew-resize;
        position: relative;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: center;
        border-left: 1px solid #111;
        border-right: 1px solid #111;
        transition: background 0.15s ease;
    }

    .mc-divider:hover,
    .mc-divider.mc-dragging {
        background: #00B7B5;
    }

    .mc-divider-handle {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        pointer-events: none;
        line-height: 1;
    }

    .mc-divider-handle .mc-arrow {
        color: rgba(255, 255, 255, 0.45);
        font-size: 9px;
        transition: color 0.15s;
        display: block;
    }

    .mc-divider:hover .mc-arrow,
    .mc-divider.mc-dragging .mc-arrow {
        color: #fff;
    }

    /* Prevent text / video selection while dragging */
    body.mc-no-select {
        user-select: none !important;
        -webkit-user-select: none !important;
        -moz-user-select: none !important;
    }
</style>

{{-- Template for Slave Video --}}
<template id="slaveVideoTemplate">
    <div class="slave-video-card" data-video-id="" style="background: #000; overflow: hidden; border-bottom: 1px solid #111; flex: 0 0 auto; height: 18vh; max-height: 18vh;">
        {{-- Video Player --}}
        <div style="position: relative; background: #000; height: 100%;">
            <video class="slave-video" style="width: 100%; height: 100%; display: block; object-fit: cover;"
                   preload="auto"
                   crossorigin="anonymous">
                {{-- Source will be set by JavaScript --}}
            </video>

            {{-- Floating Header (over video) --}}
            <div class="d-flex justify-content-between align-items-center slave-header">
                <div class="d-flex align-items-center flex-grow-1" style="min-width: 0;">
                    <h6 class="mb-0 slave-angle-name">
                        <i class="fas fa-video slave-angle-icon"></i> <span></span>
                    </h6>
                    {{-- Sync Badge Inline --}}
                    <span class="slave-sync-badge slave-badge badge badge-success ml-2">
                        <i class="fas fa-check slave-badge-icon"></i> <span class="slave-offset-text"></span>
                    </span>
                    <span class="slave-unsync-badge slave-badge badge badge-warning ml-2">
                        <i class="fas fa-exclamation-triangle slave-badge-icon"></i>
                    </span>
                </div>
                {{-- Compact Icon Buttons --}}
                <div class="ml-2" style="white-space: nowrap;">
                    <button class="btn btn-sm btn-info slave-sync-btn slave-action-btn" title="Sincronizar">
                        <i class="fas fa-sync-alt slave-btn-icon"></i>
                    </button>
                    <button class="btn btn-sm btn-danger slave-remove-btn ml-1 slave-action-btn" title="Eliminar">
                        <i class="fas fa-trash slave-btn-icon"></i>
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

        let videoId = {{ $video->id }}; // Changed to 'let' to allow swap reassignment
        let masterVideo = document.getElementById('rugbyVideo');
        let multiMasterVideo = document.getElementById('multiMasterVideo');
        let slaveVideos = [];
        let isMultiCameraActive = false;
        let activeGroupId = null; // Track active group for multi-group support
        let panelResizeController = null; // AbortController for panel resize events

        // ═══════════════════════════════════════════════════════════
        // OPTIMIZATION 1 & 2: Single Master Listener with AbortController
        // ═══════════════════════════════════════════════════════════
        let masterSyncController = null;
        let lastSyncTime = 0;
        const SYNC_THROTTLE_MS = 250; // 4 times per second
        const SYNC_DRIFT_THRESHOLD = 1.0; // 1 second tolerance (before: 0.5s)
        let seekDebounceTimer = null;

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
                // ✅ Validar que master esté listo
                if (isNaN(masterVideo.duration) || !isFinite(masterVideo.currentTime)) {
                    return;
                }

                slaveVideos.forEach(slave => {
                    // ✅ Validar que slave tenga metadata básica
                    if (isNaN(slave.element.duration)) {
                        return;
                    }

                    const expectedTime = masterVideo.currentTime + slave.offset;

                    // ✅ Validar que expectedTime sea finito y válido
                    if (!isFinite(expectedTime) || expectedTime < 0 || expectedTime > slave.element.duration) {
                        // Si el expectedTime es inválido, skip este slave completamente
                        return;
                    }

                    // ✅ SYNC: Solo ajustar currentTime si NO está seeking y tiene data
                    const canSync = !slave.element.seeking && slave.element.readyState >= 3;
                    if (canSync) {
                        slave.element.currentTime = expectedTime;
                    }

                    // ✅ PLAY: SIEMPRE reproducir (aunque esté seeking o buffering)
                    slave.element.play().catch(err => {
                        // ✅ Ignorar AbortError (esperado cuando usuario pausa)
                        if (err?.name === 'AbortError') return;
                        console.warn('Play failed:', err);
                    });
                });
            }, { signal });

            // Single pause event - syncs ALL slaves
            masterVideo.addEventListener('pause', () => {
                slaveVideos.forEach(slave => {
                    slave.element.pause();
                });
            }, { signal });

            // Single seeked event - syncs ALL slaves (with debounce)
            masterVideo.addEventListener('seeked', () => {
                // ✅ Debounce: esperar 100ms para evitar seeks múltiples
                if (seekDebounceTimer) {
                    clearTimeout(seekDebounceTimer);
                }

                seekDebounceTimer = setTimeout(() => {
                    // ✅ Validar que master esté listo
                    if (isNaN(masterVideo.duration) || !isFinite(masterVideo.currentTime)) {
                        return;
                    }

                    slaveVideos.forEach(slave => {
                        // ✅ Validar que slave esté listo (metadata + not seeking)
                        if (isNaN(slave.element.duration) || slave.element.seeking) {
                            return;
                        }

                        const expectedTime = masterVideo.currentTime + slave.offset;

                        // ✅ Validar que expectedTime sea finito y válido
                        if (!isFinite(expectedTime) || expectedTime < 0 || expectedTime > slave.element.duration) {
                            return;
                        }

                        // ✅ Sync currentTime
                        slave.element.currentTime = expectedTime;

                        // ✅ Sync paused/playing state to prevent desync on timeline clicks
                        if (masterVideo.paused) {
                            slave.element.pause();
                        } else {
                            slave.element.play().catch(err => {
                                if (err?.name === 'AbortError') return; // Expected during rapid seeks
                                console.warn('[Slave] Play failed after seek:', err);
                            });
                        }
                    });
                }, 100); // 100ms debounce
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

                // ✅ Validar que master esté listo
                if (isNaN(masterVideo.duration) || !isFinite(masterVideo.currentTime)) {
                    return;
                }

                // Check drift for ALL slaves in a single pass
                slaveVideos.forEach(slave => {
                    if (slave.element.paused) {
                        return;
                    }

                    // ✅ Validar que slave esté listo (metadata + not seeking + has data)
                    if (isNaN(slave.element.duration) ||
                        !isFinite(slave.element.currentTime) ||
                        slave.element.seeking ||
                        slave.element.readyState < 3) {
                        return;
                    }

                    const expectedTime = masterVideo.currentTime + slave.offset;

                    // ✅ Validar que expectedTime sea finito
                    if (!isFinite(expectedTime) || expectedTime < 0 || expectedTime > slave.element.duration) {
                        return;
                    }

                    const drift = Math.abs(slave.element.currentTime - expectedTime);

                    // ✅ Only correct if drift > THRESHOLD (1.0s, más tolerante)
                    if (drift > SYNC_DRIFT_THRESHOLD) {
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

        // ═══════════════════════════════════════════════════════════
        // LOADING OVERLAY FUNCTIONS (Prevents play before slaves ready)
        // ═══════════════════════════════════════════════════════════

        function showLoadingOverlay(totalSlaves) {
            const overlay = document.getElementById('slaveLoadingOverlay');
            const masterVideo = document.getElementById('rugbyVideo');
            const title = document.getElementById('loadingOverlayTitle');
            const text = document.getElementById('loadingOverlayText');
            const count = document.getElementById('loadingOverlayCount');

            if (overlay && masterVideo) {
                // Update text
                title.textContent = `🎬 Preparando ${totalSlaves} ángulo${totalSlaves > 1 ? 's' : ''} de cámara`;
                text.textContent = 'Cargando metadatos...';
                count.textContent = `0 de ${totalSlaves} listos`;

                // Show overlay
                overlay.style.display = 'block';

                // Disable master controls
                masterVideo.removeAttribute('controls');
                masterVideo.style.pointerEvents = 'none';

                console.log(`🔒 Master bloqueado - esperando ${totalSlaves} slaves`);
            }
        }

        function updateLoadingProgress(loaded, total) {
            const progressBar = document.getElementById('loadingProgressBar');
            const text = document.getElementById('loadingOverlayText');
            const count = document.getElementById('loadingOverlayCount');

            if (progressBar) {
                const percentage = (loaded / total) * 100;
                progressBar.style.width = percentage + '%';

                if (loaded < total) {
                    text.textContent = `Cargando ángulo ${loaded + 1} de ${total}...`;
                } else {
                    text.textContent = '✅ Todos los ángulos listos';
                }

                count.textContent = `${loaded} de ${total} listos`;

                console.log(`📊 Progress: ${loaded}/${total} (${percentage.toFixed(0)}%)`);
            }
        }

        function hideLoadingOverlay() {
            const overlay = document.getElementById('slaveLoadingOverlay');
            const masterVideo = document.getElementById('rugbyVideo');

            if (overlay && masterVideo) {
                // Fade out overlay
                overlay.style.transition = 'opacity 0.5s ease';
                overlay.style.opacity = '0';

                setTimeout(() => {
                    overlay.style.display = 'none';
                    overlay.style.opacity = '1';

                    // Enable master controls
                    masterVideo.setAttribute('controls', '');
                    masterVideo.style.pointerEvents = 'auto';

                    console.log('🔓 Master desbloqueado - todos los slaves listos');

                    // Toast removed per UX preference
                }, 500);
            }
        }

        // Public function to activate multi-camera view (UPDATED: Multi-Group Support)
        window.activateMultiCamera = function(angles, groupId = null) {
            if (!angles || angles.length === 0) {
                console.log('No angles to display');
                return;
            }

            activeGroupId = groupId; // Store active group

            // Only activate layout if not already active (prevents multiple wraps)
            if (!isMultiCameraActive) {
                console.log('🎬 Activating multi-camera layout for the first time');
                isMultiCameraActive = true;
                activateSideBySideLayout();
            } else {
                console.log('♻️ Multi-camera already active, only updating slaves');
            }

            // Render slave videos (incremental - adds new, removes old, keeps existing)
            renderSlaveVideos(angles);

            console.log('Multi-camera activated with', angles.length, 'angle(s)', groupId ? `in group ${groupId}` : '');
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

            // Only build layout once
            if (!mainContainer.parent().hasClass('mc-master-panel')) {
                // Read user's saved split (defaults to 66%)
                let savedMasterPct = 66;
                try { savedMasterPct = parseFloat(localStorage.getItem('rugbyhub_mc_master_width')) || 66; } catch(e) {}
                savedMasterPct = Math.max(35, Math.min(88, savedMasterPct));
                const savedSlavePct = (100 - savedMasterPct).toFixed(1);

                // Step 1: Wrap both elements in the flex layout container
                mainContainer.add(slaveContainer).wrapAll(
                    '<div class="multi-camera-layout" style="display:flex;height:60vh;overflow:hidden;"></div>'
                );

                // Step 2: Wrap master in its resizable panel
                mainContainer.wrap(
                    `<div class="mc-master-panel" style="flex:0 0 ${savedMasterPct.toFixed(1)}%;width:${savedMasterPct.toFixed(1)}%;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#000;"></div>`
                );

                // Step 3: Insert drag divider between master panel and slave container
                //         (slaveContainer is still a direct child of .multi-camera-layout at this point)
                const divider = $(
                    '<div class="mc-divider" title="Arrastrar para redimensionar · Doble clic para restablecer">' +
                    '<div class="mc-divider-handle">' +
                    '<span class="mc-arrow">&#9664;</span>' +
                    '<span class="mc-arrow">&#9654;</span>' +
                    '</div></div>'
                );
                slaveContainer.before(divider);

                // Step 4: Wrap slave in its resizable panel
                slaveContainer.wrap(
                    `<div class="mc-slave-panel" style="flex:0 0 ${savedSlavePct}%;width:${savedSlavePct}%;overflow-y:auto;max-height:60vh;display:flex;flex-direction:column;"></div>`
                );

                // Step 5: Wire drag-to-resize events
                setupPanelResize();

                // Remove border-radius from video container when in multi-camera
                mainContainer.css('border-radius', '0');
            }

            slaveContainer.show();
        }

        function deactivateSideBySideLayout() {
            $('#slaveVideosContainer').hide().empty();

            // Cleanup resize drag events & body class
            if (panelResizeController) {
                panelResizeController.abort();
                panelResizeController = null;
            }
            document.body.classList.remove('mc-no-select');

            // Unwrap if needed
            const layout = $('.multi-camera-layout');
            if (layout.length) {
                const videoContainer = $('.video-container').first();

                // Remove divider before unwrapping (otherwise it lands in the grandparent)
                $('.mc-divider').remove();

                // Unwrap: mc-master-panel (inner), then multi-camera-layout (outer)
                videoContainer.unwrap().unwrap();

                // Unwrap: mc-slave-panel
                $('#slaveVideosContainer').unwrap();

                // Restore border-radius when exiting multi-camera
                videoContainer.css('border-radius', '8px');
            }
        }

        // ═══════════════════════════════════════════════════════════
        // PANEL RESIZE: Drag divider to resize master ↔ slaves
        // Pattern: Hudl / Sportscode / Dartfish
        // ═══════════════════════════════════════════════════════════
        function setupPanelResize() {
            // Abort any previous listeners
            if (panelResizeController) panelResizeController.abort();
            panelResizeController = new AbortController();
            const signal = panelResizeController.signal;

            const dividerEl = document.querySelector('.mc-divider');
            if (!dividerEl) return;

            const MIN_MASTER_PCT = 35;  // Slaves always visible
            const MAX_MASTER_PCT = 88;  // At least a strip of slaves remains
            const DIVIDER_W_PX   = 7;   // Must match CSS width

            let isDragging   = false;
            let startX       = 0;
            let startMasterPx = 0;

            // ── Start drag ──────────────────────────────────────────
            function onStart(e) {
                isDragging = true;
                startX = e.touches ? e.touches[0].clientX : e.clientX;

                const masterPanel = document.querySelector('.mc-master-panel');
                startMasterPx = masterPanel ? masterPanel.getBoundingClientRect().width : 0;

                dividerEl.classList.add('mc-dragging');
                document.body.classList.add('mc-no-select');
                e.preventDefault();
            }

            // ── Move drag ───────────────────────────────────────────
            function onMove(e) {
                if (!isDragging) return;

                const clientX  = e.touches ? e.touches[0].clientX : e.clientX;
                const deltaX   = clientX - startX;

                const layout   = document.querySelector('.multi-camera-layout');
                if (!layout) return;

                const layoutW  = layout.getBoundingClientRect().width - DIVIDER_W_PX;
                if (layoutW <= 0) return;

                let newMasterPct = ((startMasterPx + deltaX) / layoutW) * 100;
                newMasterPct = Math.max(MIN_MASTER_PCT, Math.min(MAX_MASTER_PCT, newMasterPct));
                const newSlavePct = (100 - newMasterPct).toFixed(2);
                newMasterPct = newMasterPct.toFixed(2);

                const masterPanel = document.querySelector('.mc-master-panel');
                const slavePanel  = document.querySelector('.mc-slave-panel');
                if (!masterPanel || !slavePanel) return;

                masterPanel.style.flex  = `0 0 ${newMasterPct}%`;
                masterPanel.style.width = `${newMasterPct}%`;
                slavePanel.style.flex   = `0 0 ${newSlavePct}%`;
                slavePanel.style.width  = `${newSlavePct}%`;
            }

            // ── End drag ────────────────────────────────────────────
            function onEnd() {
                if (!isDragging) return;
                isDragging = false;

                dividerEl.classList.remove('mc-dragging');
                document.body.classList.remove('mc-no-select');

                // Persist to localStorage so it survives page reloads
                const masterPanel = document.querySelector('.mc-master-panel');
                if (masterPanel) {
                    const pct = parseFloat(masterPanel.style.width);
                    if (!isNaN(pct)) {
                        try { localStorage.setItem('rugbyhub_mc_master_width', pct.toFixed(1)); } catch(err) {}
                    }
                }
            }

            // ── Double-click → reset to 66/34 default ──────────────
            dividerEl.addEventListener('dblclick', () => {
                const DEFAULT_PCT = 66;
                const masterPanel = document.querySelector('.mc-master-panel');
                const slavePanel  = document.querySelector('.mc-slave-panel');
                if (!masterPanel || !slavePanel) return;

                masterPanel.style.flex  = `0 0 ${DEFAULT_PCT}%`;
                masterPanel.style.width = `${DEFAULT_PCT}%`;
                slavePanel.style.flex   = `0 0 ${100 - DEFAULT_PCT}%`;
                slavePanel.style.width  = `${100 - DEFAULT_PCT}%`;

                try { localStorage.setItem('rugbyhub_mc_master_width', DEFAULT_PCT); } catch(err) {}
                if (typeof showToast === 'function') showToast('Tamaño restablecido', 'info');
            }, { signal });

            // ── Mouse events ────────────────────────────────────────
            dividerEl.addEventListener('mousedown', onStart, { signal });
            document.addEventListener('mousemove', onMove, { signal });
            document.addEventListener('mouseup', onEnd, { signal });

            // ── Touch events (tablets) ──────────────────────────────
            dividerEl.addEventListener('touchstart', onStart, { passive: false, signal });
            document.addEventListener('touchmove', onMove, { passive: false, signal });
            document.addEventListener('touchend', onEnd, { signal });
        }

        function renderSlaveVideos(angles) {
            const container = $('#slaveVideosContainer');

            // Get IDs from response
            const responseIds = angles.map(a => a.id);

            // Remove slaves that are no longer in the response
            container.find('.slave-video-card').each(function() {
                const slaveId = parseInt($(this).attr('data-video-id'));
                if (!responseIds.includes(slaveId)) {
                    console.log(`Removing slave ${slaveId} (no longer in group)`);
                    $(this).remove();

                    // Also remove from slaveVideos array
                    slaveVideos = slaveVideos.filter(s => s.id !== slaveId);
                }
            });

            // Get existing slave video IDs after cleanup
            const existingSlaveIds = [];
            container.find('.slave-video-card').each(function() {
                existingSlaveIds.push(parseInt($(this).attr('data-video-id')));
            });

            // Count existing slaves
            let loadedCount = existingSlaveIds.length;
            const totalAngles = angles.length;

            // Show loading overlay if we're loading new slaves (not just updating existing ones)
            const newSlavesCount = angles.filter(a => !existingSlaveIds.includes(a.id)).length;
            if (newSlavesCount > 0 && loadedCount === 0) {
                // Only show overlay on initial load (not when adding more slaves)
                showLoadingOverlay(totalAngles);
            }

            // Only add new angles that don't exist yet
            angles.forEach(angle => {
                // Skip if this slave already exists
                if (existingSlaveIds.includes(angle.id)) {
                    console.log(`Slave ${angle.id} already exists, skipping...`);
                    return;
                }

                console.log(`Adding new slave: ${angle.camera_angle} (ID: ${angle.id})`);

                const template = document.getElementById('slaveVideoTemplate').content.cloneNode(true);
                const card = $(template).find('.slave-video-card');

                card.attr('data-video-id', angle.id);
                card.find('.slave-angle-name span').text(angle.camera_angle);

                // ═══════════════════════════════════════════════════════════
                // OPTIMIZATION 4: Preload metadata + buffer for instant seeks
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
                            const slaveData = {
                                element: video,
                                id: angle.id,
                                angle: angle.camera_angle,
                                offset: angle.sync_offset || 0,
                                synced: angle.is_synced
                            };
                            slaveVideos.push(slaveData);

                            // Show sync badge
                            if (angle.is_synced) {
                                card.find('.slave-sync-badge').show();
                                card.find('.slave-offset-text').text(`${angle.sync_offset > 0 ? '+' : ''}${angle.sync_offset}s`);
                            } else {
                                card.find('.slave-unsync-badge').show();
                            }

                            // ✅ PRELOAD BUFFER: Force download of initial buffer for instant seeks
                            video.addEventListener('loadedmetadata', function onMetadata() {
                                const masterVideo = document.getElementById('rugbyVideo');
                                if (masterVideo && !isNaN(masterVideo.duration)) {
                                    const masterTime = masterVideo.currentTime || 0;
                                    const expectedTime = masterTime + slaveData.offset;

                                    // Set currentTime to preload buffer at expected position
                                    if (isFinite(expectedTime) && expectedTime >= 0 && expectedTime <= video.duration) {
                                        video.currentTime = expectedTime;
                                    }
                                }

                                // Wait for buffer to be ready
                                video.addEventListener('canplay', function onCanPlay() {
                                    // Increment counter when buffer is ready
                                    loadedCount++;

                                    // Update loading progress overlay
                                    updateLoadingProgress(loadedCount, totalAngles);

                                    // Setup master sync when all angles have buffer loaded
                                    if (loadedCount === totalAngles) {
                                        // Hide loading overlay and enable master controls
                                        hideLoadingOverlay();

                                        // Setup sync listeners
                                        setupMasterSync();
                                    }
                                }, { once: true });
                            }, { once: true });
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

                // Sync button (UPDATED: Pass group ID)
                card.find('.slave-sync-btn').on('click', function() {
                    if (typeof window.openSyncModal === 'function') {
                        window.openSyncModal(angle.id, activeGroupId);
                    } else {
                        alert('Función de sincronización no disponible');
                    }
                });

                // Remove button (UPDATED: Pass group ID context)
                card.find('.slave-remove-btn').on('click', function() {
                    removeAngle(angle.id, card, activeGroupId);
                });

                container.append(card);
            });
        }

        function removeAngle(angleId, cardElement, groupId = null) {
            if (!confirm('¿Eliminar este ángulo del grupo?')) {
                return;
            }

            $.ajax({
                url: `/videos/${angleId}/multi-camera/remove`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                    group_id: groupId // Pass group ID
                },
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

        // Load angles on page load if video is part of a group (UPDATED: Multi-Group Support)
        @if($video->isPartOfGroup())
            @php
                $masterGroup = $video->videoGroups->where('pivot.is_master', true)->first();
                $firstGroup = $video->videoGroups->first();
                $initialGroupId = $masterGroup ? $masterGroup->id : ($firstGroup ? $firstGroup->id : null);
            @endphp

            @if($initialGroupId)
                activeGroupId = {{ $initialGroupId }};
            @endif

            const initialParams = activeGroupId ? { group_id: activeGroupId } : {};

            $.ajax({
                url: `/videos/${videoId}/multi-camera/angles`,
                method: 'GET',
                data: initialParams,
                success: function(response) {
                    if (response.success && response.angles.length > 0) {
                        // Update activeGroupId from response
                        if (response.current_group_id) {
                            activeGroupId = response.current_group_id;
                        }

                        window.activateMultiCamera(response.angles, activeGroupId);
                    }
                },
                error: function(xhr) {
                    // Check if master was deleted (404 + should_reload flag)
                    if (xhr.status === 404 && xhr.responseJSON?.should_reload) {
                        console.log('Master video deleted, reloading page to clear multi-camera UI...');

                        if (typeof showToast === 'function') {
                            showToast('El video principal fue eliminado. Recargando...', 'warning');
                        }

                        // Reload page after short delay
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        // Other errors - log but don't block (video page should still work)
                        console.error('Failed to load multi-camera angles:', xhr.status);
                    }
                }
            });
        @endif

        // ═══════════════════════════════════════════════════════════
        // SWAP MASTER/SLAVE: Click en slave para intercambiar con master
        // ═══════════════════════════════════════════════════════════

        function swapMasterWithSlave(slaveVideoId) {
            if (!isMultiCameraActive) {
                console.warn('Multi-camera not active, swap cancelled');
                return;
            }

            const currentMasterVideo = masterVideo; // #rugbyVideo o #multiMasterVideo
            const currentMasterId = videoId; // Variable global del video actual

            // Buscar el slave clickeado
            const slaveCard = $(`.slave-video-card[data-video-id="${slaveVideoId}"]`);
            if (!slaveCard.length) {
                console.error('Slave video not found:', slaveVideoId);
                return;
            }

            const slaveVideoElement = slaveCard.find('.slave-video')[0];
            if (!slaveVideoElement) {
                console.error('Slave video element not found');
                return;
            }

            // Guardar estado actual del master
            const masterState = {
                currentTime: currentMasterVideo.currentTime,
                paused: currentMasterVideo.paused,
                volume: currentMasterVideo.volume,
                playbackRate: currentMasterVideo.playbackRate
            };

            // Guardar estado actual del slave
            const slaveState = {
                currentTime: slaveVideoElement.currentTime,
                paused: slaveVideoElement.paused,
                volume: slaveVideoElement.volume,
                playbackRate: slaveVideoElement.playbackRate
            };

            // Obtener URLs (master usa <source>, slaves usan src directo)
            const masterSource = currentMasterVideo.querySelector('source');
            const masterUrl = masterSource ? masterSource.src : currentMasterVideo.src;
            const slaveUrl = slaveVideoElement.src; // Slaves usan src directo

            if (!masterUrl || !slaveUrl) {
                console.error('Video URLs not found', {masterUrl, slaveUrl});
                return;
            }

            // Intercambiar sources
            if (masterSource) {
                // Master tiene <source>, intercambiamos via <source>
                masterSource.src = slaveUrl;
            } else {
                // Master usa src directo
                currentMasterVideo.src = slaveUrl;
            }

            // Slave siempre usa src directo
            slaveVideoElement.src = masterUrl;

            // Recargar videos
            currentMasterVideo.load();
            slaveVideoElement.load();

            // Sincronizar timestamps y estado cuando estén listos
            const onMasterLoaded = function() {
                // Aplicar estado del slave al nuevo master
                currentMasterVideo.currentTime = slaveState.currentTime;
                currentMasterVideo.volume = slaveState.volume;
                currentMasterVideo.playbackRate = slaveState.playbackRate;

                if (!slaveState.paused) {
                    currentMasterVideo.play().catch(e => console.warn('Master play failed:', e));
                }

                currentMasterVideo.removeEventListener('loadedmetadata', onMasterLoaded);
            };

            const onSlaveLoaded = function() {
                // Aplicar estado del master al nuevo slave
                slaveVideoElement.currentTime = masterState.currentTime;
                slaveVideoElement.volume = masterState.volume;
                slaveVideoElement.playbackRate = masterState.playbackRate;

                if (!masterState.paused) {
                    slaveVideoElement.play().catch(e => console.warn('Slave play failed:', e));
                }

                slaveVideoElement.removeEventListener('loadedmetadata', onSlaveLoaded);
            };

            currentMasterVideo.addEventListener('loadedmetadata', onMasterLoaded);
            slaveVideoElement.addEventListener('loadedmetadata', onSlaveLoaded);

            // Actualizar data-video-id del slave para reflejar el cambio
            slaveCard.attr('data-video-id', currentMasterId);

            // Actualizar variable global videoId al nuevo master
            videoId = slaveVideoId;

            // Mostrar notificación de feedback
            showSwapNotification('Ángulo intercambiado');

            console.log(`Swapped master (${currentMasterId}) ↔ slave (${slaveVideoId})`);
        }

        function showSwapNotification(message) {
            const notification = $(`
                <div class="swap-notification">
                    <i class="fas fa-exchange-alt"></i>
                    <span>${message}</span>
                </div>
            `);

            $('body').append(notification);

            setTimeout(() => {
                notification.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 2500);
        }

        // Event listener: Click en slave card para swap
        $(document).on('click', '.slave-video-card', function(e) {
            // Evitar swap si se clickeó en botones de acción
            if ($(e.target).closest('.slave-sync-btn, .slave-remove-btn').length) {
                return;
            }

            const slaveVideoId = parseInt($(this).attr('data-video-id'));
            if (!slaveVideoId || isNaN(slaveVideoId)) {
                console.warn('Invalid slave video ID');
                return;
            }

            swapMasterWithSlave(slaveVideoId);
        });
    }

    // Initialize when DOM and jQuery are ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
