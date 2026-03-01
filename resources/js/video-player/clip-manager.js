/**
 * Clip Manager Module
 *
 * Handles video clip creation and management (LongoMatch-style).
 * Allows analysts/coaches to mark clips with keyboard shortcuts.
 */

import { getVideo, getConfig, formatTime } from './utils.js';
import { VirtualScrollManager } from './virtual-scroll.js';

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
let virtualScrollManager = null; // Virtual scroll instance for large clip lists
const VIRTUAL_SCROLL_THRESHOLD = 50; // Use virtual scroll when clips > 50

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
 * Categories are grouped by scope: templates (org), personal (user), video (from XML)
 */
async function loadCategories() {
    const config = getConfig();

    try {
        // Pass video_id to get video-specific categories
        const url = `${config.routes.clipCategories}?video_id=${config.videoId}`;
        const response = await fetch(url);
        const data = await response.json();

        // Store both flat list and grouped data
        categories = data.categories || [];
        window.categoriesGrouped = data.grouped || { templates: [], personal: [], video: [] };

        renderCategoryButtons();
    } catch (error) {
        console.error('Error loading clip categories:', error);
        showCategoriesError();
    }
}

/**
 * Render category buttons in the panel
 * Organized by scope: Templates (org), Personal (user), Video (from XML)
 */
function renderCategoryButtons() {
    const container = document.getElementById('clipButtonsContainer');
    if (!container) return;

    const grouped = window.categoriesGrouped || { templates: [], personal: [], video: [] };
    const hasAnyCategories = categories.length > 0;

    if (!hasAnyCategories) {
        container.innerHTML = `
            <div class="alert alert-warning w-100 mb-0">
                <i class="fas fa-exclamation-triangle"></i>
                No hay categorías configuradas.
                <a href="/admin/clip-categories" class="alert-link">Configurar categorías</a>
            </div>
        `;
        return;
    }

    // Build sections HTML
    let html = '';

    // Templates section (organization-level)
    if (grouped.templates && grouped.templates.length > 0) {
        html += `
            <div class="category-section mb-2">
                <div class="category-section-label" style="font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 5px;">
                    <i class="fas fa-building"></i> Plantillas del club
                </div>
                <div class="d-flex flex-wrap" style="gap: 6px;">
                    ${grouped.templates.map(cat => renderCategoryButton(cat)).join('')}
                </div>
            </div>
        `;
    }

    // Personal section (user-level)
    if (grouped.personal && grouped.personal.length > 0) {
        html += `
            <div class="category-section mb-2">
                <div class="category-section-label" style="font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 5px;">
                    <i class="fas fa-user"></i> Mis categorías
                </div>
                <div class="d-flex flex-wrap" style="gap: 6px;">
                    ${grouped.personal.map(cat => renderCategoryButton(cat)).join('')}
                </div>
            </div>
        `;
    }

    // Video section (from XML import)
    if (grouped.video && grouped.video.length > 0) {
        html += `
            <div class="category-section mb-2">
                <div class="category-section-label" style="font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 5px;">
                    <i class="fas fa-video"></i> De este video
                </div>
                <div class="d-flex flex-wrap" style="gap: 6px;">
                    ${grouped.video.map(cat => renderCategoryButton(cat)).join('')}
                </div>
            </div>
        `;
    }

    // If no grouped data, fallback to flat list
    if (!html) {
        html = `
            <div class="d-flex flex-wrap" style="gap: 6px;">
                ${categories.map(cat => renderCategoryButton(cat)).join('')}
            </div>
        `;
    }

    container.innerHTML = html;

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
 * Render a single category button HTML
 */
function renderCategoryButton(cat) {
    return `
        <button type="button"
                class="btn clip-category-btn"
                data-category-id="${cat.id}"
                data-lead="${cat.lead_seconds}"
                data-lag="${cat.lag_seconds}"
                style="background-color: ${cat.color}; color: white; min-width: 80px; font-size: 12px; padding: 6px 10px;"
                title="Tecla: ${cat.hotkey ? cat.hotkey.toUpperCase() : 'Sin asignar'}">
            ${cat.name}
            ${cat.hotkey ? `<br><small>[${cat.hotkey.toUpperCase()}]</small>` : ''}
        </button>
    `;
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
 * Render clips list (in sidebar) - Grouped by category with accordion
 */
function renderClipsList(clipsToShow = null) {
    const container = document.getElementById('sidebarClipsList') || document.getElementById('clipsList');
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

    // Always use grouped view
    renderClipsListGrouped(container, displayClips);
}

/**
 * Render clips list grouped by category with accordion
 */
function renderClipsListGrouped(container, displayClips) {
    // Group clips by category
    const groupedClips = {};
    const categoryOrder = [];

    displayClips.forEach(clip => {
        const categoryId = clip.clip_category_id || 0;
        const categoryName = clip.category?.name || 'Sin categoría';
        const categoryColor = clip.category?.color || '#666';

        if (!groupedClips[categoryId]) {
            groupedClips[categoryId] = {
                id: categoryId,
                name: categoryName,
                color: categoryColor,
                clips: []
            };
            categoryOrder.push(categoryId);
        }

        groupedClips[categoryId].clips.push(clip);
    });

    // Sort clips within each category by start_time
    Object.values(groupedClips).forEach(group => {
        group.clips.sort((a, b) => a.start_time - b.start_time);
    });

    // Build search input
    const searchHTML = `
        <div style="padding: 10px; background: #1a1a1a; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 10;">
            <input type="text"
                   id="clipCategorySearch"
                   placeholder="Buscar categoría..."
                   style="width: 100%; padding: 8px 12px; background: #0f0f0f; border: 1px solid #333; border-radius: 4px; color: #fff; font-size: 12px;"
                   autocomplete="off">
        </div>
    `;

    // Build accordion HTML
    const accordionHTML = categoryOrder.map(categoryId => {
        const group = groupedClips[categoryId];
        const clipCount = group.clips.length;
        const totalDuration = group.clips.reduce((sum, clip) => sum + (clip.end_time - clip.start_time), 0);

        return `
            <div class="clip-category-group" data-category-id="${group.id}" data-category-name="${group.name.toLowerCase()}">
                <div class="clip-category-header" style="padding: 10px; border-bottom: 1px solid #333; cursor: pointer; background: #1a1a1a; display: flex; align-items: center; justify-content: space-between;">
                    <div class="d-flex align-items-center flex-grow-1">
                        <span style="width: 6px; height: 24px; background: ${group.color}; border-radius: 2px; margin-right: 10px;"></span>
                        <span style="font-weight: 600; font-size: 13px; color: #fff;">${group.name}</span>
                        <span style="margin-left: 8px; background: rgba(255, 195, 0, 0.2); color: #FFC300; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">
                            ${clipCount}
                        </span>
                    </div>
                    <div style="color: #888; font-size: 11px; margin-right: 5px;">
                        ${formatTime(Math.floor(totalDuration))}
                    </div>
                    <i class="fas fa-chevron-down" style="color: #666; font-size: 12px; transition: transform 0.2s;"></i>
                </div>
                <div class="clip-category-content" style="display: none; background: #0f0f0f;">
                    ${group.clips.length > VIRTUAL_SCROLL_THRESHOLD
                        ? '<div class="clip-category-clips-container" style="max-height: 400px; overflow-y: auto;"></div>'
                        : group.clips.map(clip => createClipItemHTML(clip)).join('')
                    }
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = searchHTML + accordionHTML;

    // Setup search functionality
    const searchInput = container.querySelector('#clipCategorySearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase().trim();
            const groups = container.querySelectorAll('.clip-category-group');

            groups.forEach(group => {
                const categoryName = group.dataset.categoryName;
                if (categoryName.includes(searchTerm)) {
                    group.style.display = 'block';
                } else {
                    group.style.display = 'none';
                }
            });
        });
    }

    // Setup accordion toggle
    container.querySelectorAll('.clip-category-header').forEach(header => {
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            const chevron = header.querySelector('.fa-chevron-down');
            const isOpen = content.style.display !== 'none';

            if (isOpen) {
                content.style.display = 'none';
                chevron.style.transform = 'rotate(0deg)';
            } else {
                content.style.display = 'block';
                chevron.style.transform = 'rotate(180deg)';

                // If category has >50 clips and hasn't been rendered with virtual scroll yet
                const categoryId = header.parentElement.dataset.categoryId;
                const group = groupedClips[categoryId];
                const clipsContainer = content.querySelector('.clip-category-clips-container');

                if (clipsContainer && clipsContainer.children.length === 0) {
                    renderCategoryClipsVirtual(clipsContainer, group.clips);
                }
            }
        });
    });

    // Setup event delegation
    setupClipListEventDelegation(container);
}

/**
 * Render clips for a category using virtual scroll
 */
function renderCategoryClipsVirtual(container, categoryClips) {
    const virtualScrollManager = new VirtualScrollManager(
        container,
        categoryClips,
        (clip) => createClipItemElement(clip),
        60
    );
}

/**
 * Render clips list using standard method (for small lists <50 clips)
 */
function renderClipsListStandard(container, displayClips) {
    container.innerHTML = displayClips.map(clip => createClipItemHTML(clip)).join('');

    // Performance optimization: Event delegation - single listener instead of N*3 listeners
    setupClipListEventDelegation(container);
}

/**
 * Render clips list using virtual scrolling (for large lists >50 clips)
 */
function renderClipsListVirtual(container, displayClips) {
    // Destroy previous virtual scroll instance if exists
    if (virtualScrollManager) {
        virtualScrollManager.destroy();
    }

    // Create new virtual scroll manager
    virtualScrollManager = new VirtualScrollManager(
        container,
        displayClips,
        (clip, index) => createClipItemElement(clip),
        60 // Item height in pixels (approximate)
    );

    // Setup event delegation on the container
    // Virtual scroll creates a viewport, so we delegate on that
    const viewport = container.querySelector('div');
    if (viewport) {
        setupClipListEventDelegation(viewport);
    }
}

/**
 * Create clip item HTML string (for standard render)
 */
function createClipItemHTML(clip) {
    const duration = (clip.end_time - clip.start_time).toFixed(1);
    return `
        <div class="sidebar-clip-item"
             data-clip-id="${clip.id}"
             data-start="${clip.start_time}"
             data-end="${clip.end_time}"
             style="padding: 10px; border-bottom: 1px solid #333; cursor: pointer; transition: background 0.2s; background: transparent;"
             onmouseover="this.style.background='#252525'"
             onmouseout="this.style.background='transparent'">
            <div class="d-flex align-items-center">
                <span style="width: 8px; height: 30px; background: ${clip.category?.color || '#666'}; border-radius: 3px; margin-right: 10px;"></span>
                <div class="flex-grow-1" style="min-width: 0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-weight: 600; font-size: 12px; color: #fff;">
                            ${clip.category?.name || 'Sin categoría'}
                        </span>
                        <div>
                            ${clip.is_highlight ? '<i class="fas fa-star" style="color: #ffc107; font-size: 10px; margin-right: 5px;"></i>' : ''}
                            <button class="sidebar-edit-clip-btn" data-clip-id="${clip.id}" data-start="${clip.start_time}" data-end="${clip.end_time}" data-title="${clip.title || ''}" data-notes="${clip.notes || ''}" data-category-id="${clip.clip_category_id}"
                                    style="background: none; border: none; color: #FFC300; padding: 2px 5px; cursor: pointer; font-size: 11px;"
                                    title="Editar clip">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="sidebar-delete-clip-btn" data-clip-id="${clip.id}"
                                    style="background: none; border: none; color: #666; padding: 2px 5px; cursor: pointer; font-size: 11px;"
                                    title="Eliminar clip">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div style="font-size: 11px; color: #888;">
                        ${formatTime(clip.start_time)} - ${formatTime(clip.end_time)}
                        <span style="color: #666; margin-left: 5px;">(${duration}s)</span>
                    </div>
                    ${clip.title ? `<div style="font-size: 11px; color: #aaa; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${clip.title}</div>` : ''}
                </div>
            </div>
        </div>
    `;
}

/**
 * Create clip item DOM element (for virtual scroll render)
 */
function createClipItemElement(clip) {
    const duration = (clip.end_time - clip.start_time).toFixed(1);
    const div = document.createElement('div');
    div.className = 'sidebar-clip-item';
    div.dataset.clipId = clip.id;
    div.dataset.start = clip.start_time;
    div.dataset.end = clip.end_time;
    div.style.cssText = 'padding: 10px; border-bottom: 1px solid #333; cursor: pointer; transition: background 0.2s; background: transparent;';

    // Add hover effects
    div.onmouseover = function() { this.style.background = '#252525'; };
    div.onmouseout = function() { this.style.background = 'transparent'; };

    div.innerHTML = `
        <div class="d-flex align-items-center">
            <span style="width: 8px; height: 30px; background: ${clip.category?.color || '#666'}; border-radius: 3px; margin-right: 10px;"></span>
            <div class="flex-grow-1" style="min-width: 0;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-weight: 600; font-size: 12px; color: #fff;">
                        ${clip.category?.name || 'Sin categoría'}
                    </span>
                    <div>
                        ${clip.is_highlight ? '<i class="fas fa-star" style="color: #ffc107; font-size: 10px; margin-right: 5px;"></i>' : ''}
                        <button class="sidebar-edit-clip-btn" data-clip-id="${clip.id}" data-start="${clip.start_time}" data-end="${clip.end_time}" data-title="${clip.title || ''}" data-notes="${clip.notes || ''}" data-category-id="${clip.clip_category_id}"
                                style="background: none; border: none; color: #FFC300; padding: 2px 5px; cursor: pointer; font-size: 11px;"
                                title="Editar clip">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="sidebar-delete-clip-btn" data-clip-id="${clip.id}"
                                style="background: none; border: none; color: #666; padding: 2px 5px; cursor: pointer; font-size: 11px;"
                                title="Eliminar clip">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div style="font-size: 11px; color: #888;">
                    ${formatTime(clip.start_time)} - ${formatTime(clip.end_time)}
                    <span style="color: #666; margin-left: 5px;">(${duration}s)</span>
                </div>
                ${clip.title ? `<div style="font-size: 11px; color: #aaa; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${clip.title}</div>` : ''}
            </div>
        </div>
    `;

    return div;
}

/**
 * Setup event delegation for clip list (Performance optimization)
 * Reduces N*3 event listeners to just 1 listener for the entire container
 */
function setupClipListEventDelegation(container) {
    // Remove previous delegated listener if exists
    container.removeEventListener('click', handleClipListClick);

    // Single delegated click handler for all clip interactions
    container.addEventListener('click', handleClipListClick);
}

/**
 * Handle all clip list clicks via event delegation
 */
function handleClipListClick(e) {
    // Handle delete button clicks
    const deleteBtn = e.target.closest('.sidebar-delete-clip-btn');
    if (deleteBtn) {
        e.stopPropagation();
        const clipId = parseInt(deleteBtn.dataset.clipId);
        if (confirm('¿Eliminar este clip?')) {
            deleteClip(clipId);
        }
        return;
    }

    // Handle edit button clicks
    const editBtn = e.target.closest('.sidebar-edit-clip-btn');
    if (editBtn) {
        e.stopPropagation();
        // Trigger edit modal (handled by show.blade.php)
        if (typeof window.openEditClipModal === 'function') {
            window.openEditClipModal(editBtn);
        }
        return;
    }

    // Handle clip item clicks (play clip)
    const clipItem = e.target.closest('.sidebar-clip-item');
    if (clipItem) {
        const startTime = parseFloat(clipItem.dataset.start);
        const endTime = parseFloat(clipItem.dataset.end);
        playSingleClip(startTime, endTime, clipItem);
        return;
    }
}

/**
 * Play clip from start - video continues normally after clip ends
 */
function playSingleClip(startTime, endTime, clipElement) {
    const video = getVideo();
    if (!video) return;

    // Stop any previous single clip playback handlers
    stopSingleClipMode();

    // Highlight this clip
    document.querySelectorAll('.clip-item').forEach(el => el.classList.remove('playing'));
    if (clipElement) {
        clipElement.classList.add('playing');
    }

    // Jump to start and play - video continues normally (no loop, no pause)
    video.currentTime = startTime;

    // Small delay to ensure seek completes before playing
    setTimeout(() => {
        const playPromise = video.play();

        // Handle play promise to avoid errors
        if (playPromise !== undefined) {
            playPromise.catch(error => {
                console.warn('Play was prevented:', error);
            });
        }
    }, 50);

    showNotification(`Reproduciendo desde clip...`, 'info');
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

        // SPACEBAR - Play/Pause video (including all slave videos in multi-camera mode)
        if (e.code === 'Space' || e.key === ' ') {
            e.preventDefault(); // Prevent page scroll
            const video = getVideo();
            if (!video) return;

            if (video.paused) {
                video.play().catch(err => console.warn('Play failed:', err));
            } else {
                video.pause();
            }
            return; // Exit early to avoid processing other hotkeys
        }

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
 * Play all clips sequentially
 */
function playAllClips() {
    filteredClips = [...clips];

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
        border-left: 3px solid #FFC300;
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


// Expose functions globally for external access
window.loadCategories = loadCategories;
window.renderClipsList = renderClipsList; // Exposes virtual scroll functionality
window.removeClipFromLocalArray = function(clipId) {
    clips = clips.filter(c => c.id !== clipId);
};

export { loadClips, loadCategories };
