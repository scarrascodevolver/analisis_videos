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

    // Add click handlers
    container.querySelectorAll('.clip-category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const categoryId = parseInt(btn.dataset.categoryId);
            const category = categories.find(c => c.id === categoryId);
            if (category) {
                createClip(category);
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

        renderClipsList();
        updateClipCount();
    } catch (error) {
        console.error('Error loading clips:', error);
    }
}

/**
 * Render clips list
 */
function renderClipsList(clipsToShow = null) {
    const container = document.getElementById('clipsList');
    if (!container) return;

    const displayClips = clipsToShow || clips;

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
             data-start="${clip.start_time}">
            <div class="d-flex align-items-center">
                <span class="badge mr-2" style="background-color: ${clip.category?.color || '#6c757d'}; color: white;">
                    ${clip.category?.name || 'Sin categoría'}
                </span>
                <span class="text-primary clip-time" style="cursor: pointer;" title="Ir a este momento">
                    ${formatTime(Math.floor(clip.start_time))}
                </span>
                ${clip.title ? `<span class="ml-2 text-muted small">${clip.title}</span>` : ''}
            </div>
            <div>
                ${clip.is_highlight ? '<i class="fas fa-star text-warning mr-2" title="Destacado"></i>' : ''}
                <button class="btn btn-sm btn-outline-danger delete-clip-btn" data-clip-id="${clip.id}" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');

    // Add click handlers for seeking
    container.querySelectorAll('.clip-time').forEach(el => {
        el.addEventListener('click', () => {
            const startTime = parseFloat(el.closest('.clip-item').dataset.start);
            const video = getVideo();
            if (video) {
                video.currentTime = startTime;
                video.play();
            }
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
}

/**
 * Update clip count badge
 */
function updateClipCount() {
    const badge = document.getElementById('clipCount');
    if (badge) {
        badge.textContent = clips.length;
    }
}

/**
 * Setup keyboard shortcuts for clip creation
 */
function setupHotkeys() {
    document.addEventListener('keydown', (e) => {
        // Only active when panel is open and not typing in input
        if (!isClipMode) return;
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

        const key = e.key.toLowerCase();
        const category = categories.find(c => c.hotkey && c.hotkey.toLowerCase() === key);

        if (category) {
            e.preventDefault();
            createClip(category);
        }

        // ESC to stop clip playback mode
        if (e.key === 'Escape' && playingClipsOnly) {
            stopClipsMode();
        }
    });
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
            renderClipsList();
            updateClipCount();
            showNotification(`Clip "${category.name}" creado (${formatTime(Math.floor(startTime))} - ${formatTime(Math.floor(endTime))})`, 'success');

            // Flash the button
            flashButton(category.id);
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
            renderClipsList();
            updateClipCount();
            showNotification('Clip eliminado', 'info');
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
        background-color: #e3f2fd !important;
        border-left: 3px solid #2196f3;
    }
`;
document.head.appendChild(style);

export { loadClips, loadCategories };
