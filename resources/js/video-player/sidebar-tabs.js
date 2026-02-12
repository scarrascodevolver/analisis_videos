/**
 * Sidebar Tabs - Gestión de pestañas Comentarios/Clips
 *
 * Maneja el switching entre tabs y la carga de clips en el sidebar
 */

import { getConfig } from './utils.js';

// Global state for sidebar clips (exposed for visual timeline editor)
window.sidebarClipsData = window.sidebarClipsData || [];
window.sidebarCategoriesData = window.sidebarCategoriesData || [];

/**
 * Initialize sidebar tabs functionality
 */
export function initSidebarTabs() {
    const tabButtons = document.querySelectorAll('.sidebar-tab');
    const tabComments = document.getElementById('tabComments');
    const tabClips = document.getElementById('tabClips');

    if (!tabButtons.length || !tabComments || !tabClips) {
        console.log('Sidebar tabs not found (user might not have permissions)');
        return;
    }

    // Tab switching
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;

            // Update button styles
            tabButtons.forEach(b => {
                b.style.background = '#252525';
                b.style.color = '#888';
                b.classList.remove('active');
            });
            this.style.background = '#005461';
            this.style.color = '#fff';
            this.classList.add('active');

            // Show/hide content
            if (tab === 'comments') {
                tabComments.style.display = 'block';
                tabClips.style.display = 'none';
            } else {
                tabComments.style.display = 'none';
                tabClips.style.display = 'block';
                loadSidebarClips();
            }
        });
    });

    console.log('Sidebar tabs initialized');
}

/**
 * Load clips for sidebar display
 */
async function loadSidebarClips() {
    const config = getConfig();
    const sidebarClipsList = document.getElementById('sidebarClipsList');
    const sidebarClipCount = document.getElementById('sidebarClipCount');
    const sidebarTotalClips = document.getElementById('sidebarTotalClips');
    const sidebarHighlights = document.getElementById('sidebarHighlights');

    if (!sidebarClipsList) return;

    try {
        // Load categories (needed for visual timeline)
        if (window.sidebarCategoriesData.length === 0) {
            const videoId = config.videoId;
            const catResponse = await fetch(`${config.routes.clipCategories}?video_id=${videoId}`);
            if (catResponse.ok) {
                const catData = await catResponse.json();
                // API now returns { categories: [...], grouped: {...} }
                window.sidebarCategoriesData = catData.categories || catData;
            }
        }

        // Load clips
        const response = await fetch(config.routes.clips);
        if (!response.ok) {
            throw new Error('Failed to load clips');
        }

        const clips = await response.json();
        window.sidebarClipsData = clips;

        // Update counters
        if (sidebarClipCount) sidebarClipCount.textContent = clips.length;
        if (sidebarTotalClips) sidebarTotalClips.textContent = clips.length;

        // Render clips
        renderSidebarClips(clips);

        // Update highlights count
        const highlights = clips.filter(c => c.is_highlight);
        if (sidebarHighlights) {
            sidebarHighlights.textContent = highlights.length;
        }

    } catch (error) {
        console.error('Error loading sidebar clips:', error);
        if (sidebarClipsList) {
            sidebarClipsList.innerHTML = '<p class="text-center text-muted py-3">Error al cargar clips</p>';
        }
    }
}

/**
 * Render clips in sidebar list
 */
function renderSidebarClips(clips) {
    const sidebarClipsList = document.getElementById('sidebarClipsList');
    if (!sidebarClipsList) return;

    if (clips.length === 0) {
        sidebarClipsList.innerHTML = '<p class="text-center text-muted py-3">No hay clips aún</p>';
        return;
    }

    // Sort by start_time
    const sorted = [...clips].sort((a, b) => a.start_time - b.start_time);

    // Get categories for color mapping
    const categories = window.sidebarCategoriesData;
    const categoryMap = {};
    categories.forEach(cat => {
        categoryMap[cat.id] = cat;
    });

    sidebarClipsList.innerHTML = sorted.map(clip => {
        const category = categoryMap[clip.clip_category_id];
        const color = category ? category.color : '#005461';
        const categoryName = category ? category.name : 'Sin categoría';
        const duration = clip.end_time - clip.start_time;
        const highlightBadge = clip.is_highlight
            ? '<span class="badge badge-warning ml-1" title="Destacado"><i class="fas fa-star"></i></span>'
            : '';

        return `
            <div class="sidebar-clip-item"
                 data-clip-id="${clip.id}"
                 data-start="${clip.start_time}"
                 style="border-left: 3px solid ${color}; cursor: pointer; padding: 8px; margin-bottom: 8px; background: #1a1a1a; border-radius: 4px; transition: background 0.2s;">
                <div class="d-flex justify-content-between align-items-start">
                    <div style="flex: 1;">
                        <div style="font-size: 12px; color: #00B7B5; font-weight: bold;">
                            ${formatTime(clip.start_time)} - ${formatTime(clip.end_time)}
                            ${highlightBadge}
                        </div>
                        <div style="font-size: 11px; color: #888; margin-top: 2px;">
                            <i class="fas fa-tag" style="color: ${color};"></i> ${categoryName}
                            <span class="ml-2"><i class="fas fa-clock"></i> ${duration}s</span>
                        </div>
                        ${clip.title ? `<div style="font-size: 11px; color: #ccc; margin-top: 4px;">${escapeHtml(clip.title)}</div>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Add click listeners to jump to clip time
    sidebarClipsList.querySelectorAll('.sidebar-clip-item').forEach(item => {
        item.addEventListener('click', function() {
            const startTime = parseFloat(this.dataset.start);
            const video = document.getElementById('rugbyVideo');
            if (video && !isNaN(startTime)) {
                video.currentTime = startTime;
                if (video.paused) {
                    video.play();
                }
            }
        });

        // Hover effect
        item.addEventListener('mouseenter', function() {
            this.style.background = '#252525';
        });
        item.addEventListener('mouseleave', function() {
            this.style.background = '#1a1a1a';
        });
    });
}

/**
 * Format seconds to MM:SS
 */
function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Reload sidebar clips (called from external code after clip changes)
 */
export function reloadSidebarClips() {
    loadSidebarClips();
}
