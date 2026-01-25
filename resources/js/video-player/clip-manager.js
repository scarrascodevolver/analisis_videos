/**
 * Clip Manager Module
 *
 * Handles video clip creation and management (LongoMatch-style).
 * Allows analysts/coaches to mark clips with keyboard shortcuts.
 */

import { getVideo, getConfig, formatTime } from './utils.js';

let categories = [];
let clips = [];
let isPanelOpen = false;
let isClipMode = false;
let playingClipsOnly = false;
let filteredClips = [];
let currentClipIndex = 0;
let clipEndHandler = null;
let singleClipHandler = null; // Handler para reproducir un solo clip
let pendingClip = null; // Clip en proceso de grabación (modo toggle)

/**
 * Initialize clip manager
 */
export function initClipManager() {
    const config = getConfig();

    // Only init for users who can create clips
    if (!config.user.canCreateClips) {
        return;
    }

    // Check if clip panel elements exist
    const toggleBtn = document.getElementById('toggleClipPanel');
    if (!toggleBtn) {
        return;
    }

    console.log('Initializing Clip Manager...');

    // Setup toggle panel
    toggleBtn.addEventListener('click', togglePanel);

    // Initialize panel state based on current visibility
    const panel = document.getElementById('clipPanel');
    if (panel && panel.style.display !== 'none') {
        isPanelOpen = true;
        isClipMode = true;
        console.log('Clip Mode: ACTIVADO (panel visible por defecto)');
    }

    // Load categories and clips
    loadCategories();
    loadClips();

    // Setup keyboard shortcuts
    setupHotkeys();

    // Setup filter
    const filterSelect = document.getElementById('clipFilterCategory');
    if (filterSelect) {
        filterSelect.addEventListener('change', filterClips);
    }

    // Setup play all clips button
    const playAllBtn = document.getElementById('playAllClipsBtn');
    if (playAllBtn) {
        playAllBtn.addEventListener('click', playAllClips);
    }

    console.log('Clip Manager initialized');
}

/**
 * Toggle clip panel visibility
 */
function togglePanel() {
    const panel = document.getElementById('clipPanel');
    const arrow = document.getElementById('clipPanelArrow');

    if (panel) {
        isPanelOpen = !isPanelOpen;
        panel.style.display = isPanelOpen ? 'block' : 'none';

        if (arrow) {
            arrow.classList.toggle('fa-chevron-down', !isPanelOpen);
            arrow.classList.toggle('fa-chevron-up', isPanelOpen);
        }

        if (isPanelOpen) {
            isClipMode = true;
            showNotification('Modo Análisis activado. Usa las teclas rápidas para marcar clips.', 'info');
        } else {
            isClipMode = false;
        }
    }
}

/**
 * Load clip categories from API
 */
async function loadCategories() {
    const config = getConfig();

    try {
        const response = await fetch(config.routes.clipCategories);
        categories = await response.json();

        renderCategoryButtons();
        populateFilterDropdown();
    } catch (error) {
        console.error('Error loading clip categories:', error);
        showCategoriesError();
    }
}

/**
 * Render category buttons in the panel
 */
function renderCategoryButtons() {
    const container = document.getElementById('clipButtonsContainer');
    if (!container) return;

    if (categories.length === 0) {
        container.innerHTML = `
            <div class="alert alert-warning w-100 mb-0">
                <i class="fas fa-exclamation-triangle"></i>
                No hay categorías configuradas.
                <a href="/admin/clip-categories" class="alert-link">Configurar categorías</a>
            </div>
        `;
        return;
    }

    container.innerHTML = categories.map(cat => `
        <button type="button"
                class="btn clip-category-btn"
                data-category-id="${cat.id}"
                data-lead="${cat.lead_seconds}"
                data-lag="${cat.lag_seconds}"
                style="background-color: ${cat.color}; color: white; min-width: 90px;"
                title="Tecla: ${cat.hotkey ? cat.hotkey.toUpperCase() : 'Sin asignar'}">
            ${cat.name}
            ${cat.hotkey ? `<br><small>[${cat.hotkey.toUpperCase()}]</small>` : ''}
        </button>
    `).join('');

    // Add click handlers (toggle mode - same as hotkeys)
    container.querySelectorAll('.clip-category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const categoryId = parseInt(btn.dataset.categoryId);
            const category = categories.find(c => c.id === categoryId);
            if (category) {
                handleClipToggle(category);
            }
        });
    });
}

/**
 * Populate filter dropdown with categories
 */
function populateFilterDropdown() {
    const select = document.getElementById('clipFilterCategory');
    if (!select) return;

    select.innerHTML = '<option value="">Todas las categorías</option>' +
        categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
}

/**
 * Show error message in categories container
 */
function showCategoriesError() {
    const container = document.getElementById('clipButtonsContainer');
    if (container) {
        container.innerHTML = `
            <div class="alert alert-danger w-100 mb-0">
                <i class="fas fa-exclamation-circle"></i>
                Error al cargar categorías. Recarga la página.
            </div>
        `;
    }
}

/**
 * Load clips for current video
 */
async function loadClips() {
    const config = getConfig();

    try {
        const response = await fetch(config.routes.clips);
        clips = await response.json();

        // Solo actualizar contadores - el renderizado lo hace show.blade.php
        updateClipCount();
    } catch (error) {
        console.error('Error loading clips:', error);
    }
}

/**
 * Render clips list (in sidebar)
 */
function renderClipsList(clipsToShow = null) {
    // Usar sidebarClipsList que es el que existe en la vista
    const container = document.getElementById('sidebarClipsList') || document.getElementById('clipsList');
    if (!container) return;

    // Ordenar por ID descendente (más reciente primero)
    const displayClips = [...(clipsToShow || clips)].sort((a, b) => b.id - a.id);

    if (displayClips.length === 0) {
        container.innerHTML = `
            <div class="text-muted text-center py-3">
                <i class="fas fa-video-slash"></i> Sin clips aún
            </div>
        `;
        return;
    }

    container.innerHTML = displayClips.map(clip => `
        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 clip-item"
             data-clip-id="${clip.id}"
             data-start="${clip.start_time}"
             data-end="${clip.end_time}"
             style="cursor: pointer; background: #252525; border: none; border-bottom: 1px solid #333; color: #ccc;"
             title="Clic para reproducir clip (${formatTime(Math.floor(clip.start_time))} - ${formatTime(Math.floor(clip.end_time))})">
            <div class="d-flex align-items-center clip-play-area">
                <i class="fas fa-play-circle mr-2" style="color: #00B7B5;"></i>
                <span class="badge mr-2" style="background-color: ${clip.category?.color || '#6c757d'}; color: white;">
                    ${clip.category?.name || 'Sin categoría'}
                </span>
                <span class="clip-time" style="color: #00B7B5;">
                    ${formatTime(Math.floor(clip.start_time))} - ${formatTime(Math.floor(clip.end_time))}
                </span>
                ${clip.title ? `<span class="ml-2 small" style="color: #888;">${clip.title}</span>` : ''}
            </div>
            <div class="clip-actions">
                ${clip.is_highlight ? '<i class="fas fa-star text-warning mr-2" title="Destacado"></i>' : ''}
                <button class="btn btn-sm btn-outline-info export-gif-btn" data-clip-id="${clip.id}" data-start="${clip.start_time}" data-end="${clip.end_time}" data-title="${clip.title || 'clip'}" title="Exportar GIF">
                    <i class="fas fa-file-image"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-clip-btn" data-clip-id="${clip.id}" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');

    // Add click handlers for playing clip (LongoMatch style)
    container.querySelectorAll('.clip-item').forEach(el => {
        el.addEventListener('click', (e) => {
            // Ignore if clicking delete button
            if (e.target.closest('.delete-clip-btn')) return;

            const startTime = parseFloat(el.dataset.start);
            const endTime = parseFloat(el.dataset.end);
            playSingleClip(startTime, endTime, el);
        });
    });

    // Add click handlers for delete
    container.querySelectorAll('.delete-clip-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            if (confirm('¿Eliminar este clip?')) {
                await deleteClip(parseInt(btn.dataset.clipId));
            }
        });
    });

    // Add click handlers for GIF export
    container.querySelectorAll('.export-gif-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const startTime = parseFloat(btn.dataset.start);
            const endTime = parseFloat(btn.dataset.end);
            const title = btn.dataset.title;
            await exportClipAsGif(startTime, endTime, title, btn);
        });
    });
}

/**
 * Play a single clip in infinite loop until another clip is selected or ESC is pressed
 */
function playSingleClip(startTime, endTime, clipElement) {
    const video = getVideo();
    if (!video) return;

    // Stop any previous single clip playback
    stopSingleClipMode();

    // Highlight this clip
    document.querySelectorAll('.clip-item').forEach(el => el.classList.remove('playing'));
    if (clipElement) {
        clipElement.classList.add('playing');
    }

    // Jump to start and play
    video.currentTime = startTime;
    const playPromise = video.play();

    // Handle play promise to avoid errors
    if (playPromise !== undefined) {
        playPromise.catch(error => {
            console.warn('Play was prevented:', error);
        });
    }

    // Setup handler to loop when reaching end
    singleClipHandler = () => {
        if (video.currentTime >= endTime) {
            // Loop back to start instead of pausing
            video.currentTime = startTime;

            // Wait a bit for seek to complete, then play
            setTimeout(() => {
                const loopPlayPromise = video.play();
                if (loopPlayPromise !== undefined) {
                    loopPlayPromise.catch(error => {
                        console.warn('Loop play was prevented:', error);
                    });
                }
            }, 50);
        }
    };
    video.addEventListener('timeupdate', singleClipHandler);

    showNotification(`Reproduciendo clip en loop... (ESC para cancelar)`, 'info');
}

/**
 * Stop single clip playback mode
 */
function stopSingleClipMode() {
    const video = getVideo();
    if (video && singleClipHandler) {
        video.removeEventListener('timeupdate', singleClipHandler);
    }
    singleClipHandler = null;

    // Remove playing highlight
    document.querySelectorAll('.clip-item').forEach(el => {
        el.classList.remove('playing');
    });
}

/**
 * Update clip count badges (panel and sidebar)
 */
function updateClipCount() {
    // Badge del panel principal
    const badge = document.getElementById('clipCount');
    if (badge) {
        badge.textContent = clips.length;
    }

    // Badge del sidebar
    const sidebarBadge = document.getElementById('sidebarClipCount');
    if (sidebarBadge) {
        sidebarBadge.textContent = clips.length;
    }

    // Stats del sidebar
    const totalClips = document.getElementById('sidebarTotalClips');
    if (totalClips) {
        totalClips.textContent = clips.length;
    }

    const highlights = document.getElementById('sidebarHighlights');
    if (highlights) {
        highlights.textContent = clips.filter(c => c.is_highlight).length;
    }
}

/**
 * Setup keyboard shortcuts for clip creation (Toggle mode - LongoMatch style)
 */
let lastHotkeyTime = 0;
const HOTKEY_DEBOUNCE = 300; // ms entre pulsaciones

function setupHotkeys() {
    // Usar capture: true para interceptar teclas ANTES de que lleguen al video player
    document.addEventListener('keydown', (e) => {
        // Skip if typing in input fields
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

        // Skip if categories not loaded yet
        if (categories.length === 0) {
            console.log('Hotkey ignored: categories not loaded');
            return;
        }

        const key = e.key.toLowerCase();
        const category = categories.find(c => c.hotkey && c.hotkey.toLowerCase() === key);

        if (category) {
            e.preventDefault();
            e.stopPropagation();

            // Debounce para evitar doble disparo
            const now = Date.now();
            if (now - lastHotkeyTime < HOTKEY_DEBOUNCE) {
                console.log(`Hotkey "${key}" ignored (debounce)`);
                return;
            }
            lastHotkeyTime = now;

            console.log(`Hotkey "${key}" -> ${category.name}`);
            handleClipToggle(category);
        }

        // ESC to stop clip playback mode OR cancel pending clip
        if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
            if (pendingClip) {
                cancelPendingClip();
            }
            if (playingClipsOnly) {
                stopClipsMode();
            }
            if (singleClipHandler) {
                const video = getVideo();
                if (video) video.pause();
                stopSingleClipMode();
                showNotification('Reproducción cancelada', 'info');
            }
        }
    }, { capture: true }); // Capturar en fase de capture, antes del video
}

/**
 * Handle clip toggle - Start or End recording (LongoMatch style)
 */
function handleClipToggle(category) {
    const video = getVideo();
    if (!video) return;

    const currentTime = video.currentTime;

    // Check if there's a pending clip for THIS category
    if (pendingClip && pendingClip.categoryId === category.id) {
        // SECOND PRESS - End recording
        finishClip(category, currentTime);
    } else if (pendingClip && pendingClip.categoryId !== category.id) {
        // Different category pressed - finish current and start new
        const oldCategory = categories.find(c => c.id === pendingClip.categoryId);
        if (oldCategory) {
            finishClip(oldCategory, currentTime);
        }
        // Start new clip with the new category
        startClip(category, currentTime);
    } else {
        // FIRST PRESS - Start recording
        startClip(category, currentTime);
    }
}

/**
 * Start recording a clip (first press)
 */
function startClip(category, currentTime) {
    const video = getVideo();

    // Verificar si el video está al final (no se puede grabar)
    if (video.currentTime >= video.duration - 0.5) {
        showNotification('El video terminó. Rebobina para crear clips.', 'warning');
        return;
    }

    // Auto-play si el video está pausado (evita clips de 0 segundos)
    const wasPaused = video.paused;
    if (wasPaused) {
        video.play();
    }

    const startTime = Math.max(0, currentTime - category.lead_seconds);

    pendingClip = {
        categoryId: category.id,
        categoryName: category.name,
        startTime: startTime,
        startedAt: currentTime,
        color: category.color,
        lagSeconds: category.lag_seconds
    };

    // Visual feedback - highlight the button
    highlightRecordingButton(category.id, true);

    // Show recording indicator
    showRecordingIndicator(category);

    const hotkeyText = category.hotkey ? ` - Presiona [${category.hotkey.toUpperCase()}] para terminar` : '';
    const resumedText = wasPaused ? ' (video reanudado)' : '';

    showNotification(
        `🔴 GRABANDO "${category.name}"${resumedText} desde ${formatTime(Math.floor(startTime))}${hotkeyText}`,
        'warning'
    );
}

/**
 * Finish recording a clip (second press)
 */
async function finishClip(category, currentTime) {
    if (!pendingClip) return;

    const video = getVideo();
    const config = getConfig();
    const endTime = Math.min(video.duration, currentTime + category.lag_seconds);

    // Remove visual feedback
    highlightRecordingButton(pendingClip.categoryId, false);
    hideRecordingIndicator();

    try {
        const response = await fetch(config.routes.createClip, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                clip_category_id: category.id,
                start_time: pendingClip.startTime.toFixed(2),
                end_time: endTime.toFixed(2)
            })
        });

        const data = await response.json();

        if (data.success) {
            clips.push(data.clip);
            updateClipCount();

            const duration = endTime - pendingClip.startTime;
            showNotification(
                `✅ Clip "${category.name}" guardado (${formatTime(Math.floor(pendingClip.startTime))} - ${formatTime(Math.floor(endTime))}) - ${duration.toFixed(1)}s`,
                'success'
            );

            // Flash the button
            flashButton(category.id);

            // Refresh clips list - use sidebar refresh if available (avoids double render)
            if (typeof window.refreshSidebarClips === 'function') {
                window.refreshSidebarClips();
            } else {
                renderClipsList();
            }

            // Also refresh visual timeline if available
            if (typeof window.renderVisualTimeline === 'function') {
                // Wait a bit for sidebar data to load, then refresh timeline
                setTimeout(() => {
                    window.renderVisualTimeline();
                }, 300);
            }
        } else {
            showNotification('Error al crear clip', 'error');
        }
    } catch (error) {
        console.error('Error creating clip:', error);
        showNotification('Error al crear clip', 'error');
    }

    pendingClip = null;
}

/**
 * Cancel pending clip recording
 */
function cancelPendingClip() {
    if (!pendingClip) return;

    highlightRecordingButton(pendingClip.categoryId, false);
    hideRecordingIndicator();
    showNotification(`Grabación de "${pendingClip.categoryName}" cancelada`, 'info');
    pendingClip = null;
}

/**
 * Highlight button when recording
 */
function highlightRecordingButton(categoryId, isRecording) {
    const btn = document.querySelector(`[data-category-id="${categoryId}"]`);
    if (btn) {
        if (isRecording) {
            btn.classList.add('recording');
        } else {
            btn.classList.remove('recording');
        }
    }
}

/**
 * Show recording indicator on video
 */
function showRecordingIndicator(category) {
    // Remove existing indicator
    hideRecordingIndicator();

    const indicator = document.createElement('div');
    indicator.id = 'clipRecordingIndicator';
    indicator.innerHTML = `
        <div class="recording-badge">
            <span class="recording-dot"></span>
            <span class="recording-text">REC: ${category.name}</span>
            <span class="recording-time" id="recordingTimer">00:00</span>
        </div>
    `;

    // Find video container and append
    const videoContainer = document.querySelector('.video-container');
    if (videoContainer) {
        videoContainer.appendChild(indicator);
        startRecordingTimer();
    }
}

/**
 * Hide recording indicator
 */
function hideRecordingIndicator() {
    const indicator = document.getElementById('clipRecordingIndicator');
    if (indicator) {
        indicator.remove();
    }
    stopRecordingTimer();
}

let recordingTimerInterval = null;
let recordingStartTime = null;

function startRecordingTimer() {
    recordingStartTime = Date.now();
    recordingTimerInterval = setInterval(() => {
        const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
        const minutes = Math.floor(elapsed / 60);
        const seconds = elapsed % 60;
        const timerEl = document.getElementById('recordingTimer');
        if (timerEl) {
            timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }, 1000);
}

function stopRecordingTimer() {
    if (recordingTimerInterval) {
        clearInterval(recordingTimerInterval);
        recordingTimerInterval = null;
    }
    recordingStartTime = null;
}

/**
 * Create a new clip
 */
async function createClip(category) {
    const video = getVideo();
    const config = getConfig();

    if (!video) return;

    const currentTime = video.currentTime;
    const startTime = Math.max(0, currentTime - category.lead_seconds);
    const endTime = Math.min(video.duration || currentTime + category.lag_seconds, currentTime + category.lag_seconds);

    try {
        const response = await fetch(config.routes.createClip, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                clip_category_id: category.id,
                start_time: startTime.toFixed(2),
                end_time: endTime.toFixed(2)
            })
        });

        const data = await response.json();

        if (data.success) {
            clips.push(data.clip);
            updateClipCount();
            showNotification(`Clip "${category.name}" creado (${formatTime(Math.floor(startTime))} - ${formatTime(Math.floor(endTime))})`, 'success');

            // Flash the button
            flashButton(category.id);

            // Refresh sidebar
            if (typeof window.refreshSidebarClips === 'function') {
                window.refreshSidebarClips();
            }

            // Also refresh visual timeline if available
            if (typeof window.renderVisualTimeline === 'function') {
                setTimeout(() => {
                    window.renderVisualTimeline();
                }, 300);
            }
        } else {
            showNotification('Error al crear clip', 'error');
        }
    } catch (error) {
        console.error('Error creating clip:', error);
        showNotification('Error al crear clip', 'error');
    }
}

/**
 * Delete a clip
 */
async function deleteClip(clipId) {
    const config = getConfig();
    const videoId = config.videoId;

    try {
        const response = await fetch(`/videos/${videoId}/clips/${clipId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            clips = clips.filter(c => c.id !== clipId);
            updateClipCount();
            showNotification('Clip eliminado', 'info');

            // Refresh sidebar
            if (typeof window.refreshSidebarClips === 'function') {
                window.refreshSidebarClips();
            }

            // Also refresh visual timeline if available
            if (typeof window.renderVisualTimeline === 'function') {
                setTimeout(() => {
                    window.renderVisualTimeline();
                }, 300);
            }
        }
    } catch (error) {
        console.error('Error deleting clip:', error);
    }
}

/**
 * Filter clips by category
 */
function filterClips() {
    const select = document.getElementById('clipFilterCategory');
    const categoryId = select ? parseInt(select.value) : null;

    if (!categoryId) {
        renderClipsList(clips);
    } else {
        const filtered = clips.filter(c => c.clip_category_id === categoryId);
        renderClipsList(filtered);
    }
}

/**
 * Play all clips (or filtered clips) sequentially
 */
function playAllClips() {
    const select = document.getElementById('clipFilterCategory');
    const categoryId = select ? parseInt(select.value) : null;

    filteredClips = categoryId
        ? clips.filter(c => c.clip_category_id === categoryId)
        : [...clips];

    if (filteredClips.length === 0) {
        showNotification('No hay clips para reproducir', 'warning');
        return;
    }

    // Sort by start time
    filteredClips.sort((a, b) => a.start_time - b.start_time);

    playingClipsOnly = true;
    currentClipIndex = 0;

    showNotification(`Reproduciendo ${filteredClips.length} clips. Presiona ESC para salir.`, 'info');
    playNextClip();
}

/**
 * Play next clip in sequence
 */
function playNextClip() {
    const video = getVideo();
    if (!video) return;

    if (currentClipIndex >= filteredClips.length) {
        stopClipsMode();
        showNotification('Fin de clips', 'info');
        return;
    }

    const clip = filteredClips[currentClipIndex];
    video.currentTime = clip.start_time;
    video.play();

    // Highlight current clip in list
    highlightCurrentClip(clip.id);

    // Remove previous handler
    if (clipEndHandler) {
        video.removeEventListener('timeupdate', clipEndHandler);
    }

    // Setup handler to jump to next clip when current ends
    clipEndHandler = () => {
        if (video.currentTime >= clip.end_time) {
            currentClipIndex++;
            playNextClip();
        }
    };
    video.addEventListener('timeupdate', clipEndHandler);
}

/**
 * Stop clips playback mode
 */
function stopClipsMode() {
    const video = getVideo();
    if (video && clipEndHandler) {
        video.removeEventListener('timeupdate', clipEndHandler);
    }

    playingClipsOnly = false;
    clipEndHandler = null;

    // Remove highlight
    document.querySelectorAll('.clip-item').forEach(el => {
        el.classList.remove('active');
    });
}

/**
 * Highlight current clip in list
 */
function highlightCurrentClip(clipId) {
    document.querySelectorAll('.clip-item').forEach(el => {
        el.classList.remove('active');
        if (parseInt(el.dataset.clipId) === clipId) {
            el.classList.add('active');
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
}

/**
 * Flash button when clip created
 */
function flashButton(categoryId) {
    const btn = document.querySelector(`[data-category-id="${categoryId}"]`);
    if (btn) {
        btn.classList.add('clip-created-flash');
        setTimeout(() => btn.classList.remove('clip-created-flash'), 500);
    }
}

/**
 * Show notification toast
 */
function showNotification(message, type = 'info') {
    // Use existing toast system if available, or create simple one
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };

    const toast = document.createElement('div');
    toast.className = 'clip-toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: ${colors[type] || colors.info};
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        z-index: 9999;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        animation: slideUp 0.3s ease;
    `;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideDown 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from { transform: translateX(-50%) translateY(100%); opacity: 0; }
        to { transform: translateX(-50%) translateY(0); opacity: 1; }
    }
    @keyframes slideDown {
        from { transform: translateX(-50%) translateY(0); opacity: 1; }
        to { transform: translateX(-50%) translateY(100%); opacity: 0; }
    }
    .clip-created-flash {
        animation: flashPulse 0.5s ease;
    }
    @keyframes flashPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); box-shadow: 0 0 20px rgba(255,255,255,0.8); }
    }
    .clip-item.active {
        background-color: #1a3a4a !important;
        border-left: 3px solid #00B7B5;
    }
    .clip-item.playing {
        background-color: #3d2e00 !important;
        border-left: 3px solid #ffc107;
        animation: clipPlaying 1s ease infinite;
    }
    .clip-item.playing .fa-play-circle {
        color: #ffc107 !important;
        animation: pulse 0.5s ease infinite alternate;
    }
    @keyframes clipPlaying {
        0%, 100% { background-color: #3d2e00; }
        50% { background-color: #4a3800; }
    }
    @keyframes pulse {
        from { transform: scale(1); }
        to { transform: scale(1.2); }
    }
    .clip-item:hover {
        background-color: #0a3040 !important;
    }

    /* Recording mode styles */
    .clip-category-btn.recording {
        animation: recordingPulse 1s ease infinite;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.5);
    }
    @keyframes recordingPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.05); }
    }

    /* Recording indicator on video */
    #clipRecordingIndicator {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 100;
    }
    .recording-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(220, 53, 69, 0.95);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 14px;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
    }
    .recording-dot {
        width: 12px;
        height: 12px;
        background: white;
        border-radius: 50%;
        animation: recordingDot 1s ease infinite;
    }
    @keyframes recordingDot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
    .recording-time {
        font-family: monospace;
        background: rgba(0,0,0,0.3);
        padding: 2px 8px;
        border-radius: 10px;
    }
    .clip-item:hover .fa-play-circle {
        color: #00d4d1 !important;
        transform: scale(1.1);
    }
`;
document.head.appendChild(style);

/**
 * Export a clip as GIF
 * Uses gif.js library to capture video frames and generate GIF
 */
async function exportClipAsGif(startTime, endTime, title, buttonEl) {
    const video = getVideo();
    if (!video) {
        showNotification('No se encontró el video', 'error');
        return;
    }

    // Check if GIF library is loaded
    if (typeof GIF === 'undefined') {
        showNotification('Librería GIF no disponible', 'error');
        return;
    }

    // Show loading state
    const originalContent = buttonEl.innerHTML;
    buttonEl.disabled = true;
    buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    showNotification('Generando GIF... esto puede tardar unos segundos', 'info');

    // Save current video state
    const wasPlaying = !video.paused;
    const originalTime = video.currentTime;
    video.pause();

    // Calculate dimensions (maintain aspect ratio, max 480px width)
    const maxWidth = 480;
    const scale = Math.min(1, maxWidth / video.videoWidth);
    const width = Math.floor(video.videoWidth * scale);
    const height = Math.floor(video.videoHeight * scale);

    // Create canvas for frame capture
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    // GIF settings
    const fps = 10; // frames per second
    const frameInterval = 1 / fps;
    const duration = endTime - startTime;
    const totalFrames = Math.min(Math.floor(duration * fps), 100); // Max 100 frames

    // Initialize GIF encoder
    const gif = new GIF({
        workers: 2,
        quality: 10,
        width: width,
        height: height,
        workerScript: '/js/gif.worker.js'
    });

    // Capture frames
    let framesAdded = 0;

    try {
        for (let i = 0; i < totalFrames; i++) {
            const frameTime = startTime + (i * frameInterval);

            // Seek to frame time
            await seekToTime(video, frameTime);

            // Draw frame to canvas
            ctx.drawImage(video, 0, 0, width, height);

            // Add frame to GIF
            gif.addFrame(ctx, { copy: true, delay: Math.floor(1000 / fps) });
            framesAdded++;

            // Update progress
            if (i % 10 === 0) {
                buttonEl.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${Math.floor((i / totalFrames) * 100)}%`;
            }
        }

        // Render GIF
        buttonEl.innerHTML = '<i class="fas fa-cog fa-spin"></i>';

        gif.on('finished', function(blob) {
            // Create download link
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${sanitizeFilename(title)}_${formatTime(Math.floor(startTime))}-${formatTime(Math.floor(endTime))}.gif`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            // Restore button
            buttonEl.disabled = false;
            buttonEl.innerHTML = originalContent;
            showNotification(`GIF exportado correctamente (${framesAdded} frames)`, 'success');

            // Restore video state
            video.currentTime = originalTime;
            if (wasPlaying) video.play();
        });

        gif.on('progress', function(p) {
            buttonEl.innerHTML = `<i class="fas fa-cog fa-spin"></i> ${Math.floor(p * 100)}%`;
        });

        gif.render();

    } catch (error) {
        console.error('Error exporting GIF:', error);
        buttonEl.disabled = false;
        buttonEl.innerHTML = originalContent;
        showNotification('Error al exportar GIF', 'error');

        // Restore video state
        video.currentTime = originalTime;
        if (wasPlaying) video.play();
    }
}

/**
 * Promise-based video seek
 */
function seekToTime(video, time) {
    return new Promise((resolve) => {
        const onSeeked = () => {
            video.removeEventListener('seeked', onSeeked);
            // Small delay to ensure frame is rendered
            setTimeout(resolve, 50);
        };
        video.addEventListener('seeked', onSeeked);
        video.currentTime = time;
    });
}

/**
 * Sanitize filename for download
 */
function sanitizeFilename(name) {
    return name
        .replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_]/g, '')
        .replace(/\s+/g, '_')
        .substring(0, 50) || 'clip';
}

// Expose functions globally for external access
window.loadCategories = loadCategories;
window.removeClipFromLocalArray = function(clipId) {
    clips = clips.filter(c => c.id !== clipId);
};

export { loadClips, loadCategories };
