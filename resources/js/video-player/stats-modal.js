/**
 * Video Player - Stats Modal Module
 * Handles video statistics/views modal
 */

import { getConfig } from './utils.js';

/**
 * Initialize stats modal functionality
 */
export function initStatsModal() {
    const config = getConfig();

    // Only initialize if user can view stats
    if (!config.user || !config.user.canViewStats) {
        return;
    }

    $('#statsModal').on('show.bs.modal', function () {
        loadVideoStats();
    });
}

/**
 * Load video statistics from server
 */
function loadVideoStats() {
    const config = getConfig();

    fetch(config.routes.stats, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': config.csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update totals
                $('#modalTotalViews').text(data.total_views);
                $('#modalUniqueViewers').text(data.unique_viewers);

                // Update table
                const tbody = $('#statsTableBody');
                tbody.empty();

                if (data.stats.length === 0) {
                    tbody.append(`
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                <i class="fas fa-info-circle"></i> No hay visualizaciones registradas aun
                            </td>
                        </tr>
                    `);
                } else {
                    data.stats.forEach(stat => {
                        const lastViewed = stat.last_viewed_timestamp
                            ? formatRelativeTimeFromTimestamp(stat.last_viewed_timestamp)
                            : formatRelativeTime(stat.last_viewed);
                        tbody.append(`
                            <tr>
                                <td><i class="fas fa-user"></i> ${stat.user.name}</td>
                                <td class="text-center"><span class="badge badge-success">${stat.view_count}x</span></td>
                                <td><i class="fas fa-clock"></i> ${lastViewed}</td>
                            </tr>
                        `);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
            $('#statsTableBody').html(`
                <tr>
                    <td colspan="3" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error al cargar visualizaciones
                    </td>
                </tr>
            `);
        });
}

/**
 * Format relative time from Unix timestamp
 * @param {number} timestamp - Unix timestamp in seconds
 * @returns {string}
 */
function formatRelativeTimeFromTimestamp(timestamp) {
    const nowTimestamp = Math.floor(Date.now() / 1000);
    const diffSecs = nowTimestamp - timestamp;
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffSecs < 60) return 'Hace unos segundos';
    if (diffMins < 60) return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;

    if (diffHours < 24) {
        const remainingMins = diffMins % 60;
        if (diffHours > 0 && remainingMins > 0) {
            return `Hace ${diffHours}h ${remainingMins}min`;
        }
        if (diffHours > 0 && remainingMins === 0) {
            return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
        }
        return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
    }

    if (diffDays < 7) return `Hace ${diffDays} dia${diffDays > 1 ? 's' : ''}`;
    if (diffDays < 30) {
        const weeks = Math.floor(diffDays / 7);
        return `Hace ${weeks} semana${weeks > 1 ? 's' : ''}`;
    }
    const months = Math.floor(diffDays / 30);
    return `Hace ${months} mes${months > 1 ? 'es' : ''}`;
}

/**
 * Format relative time from date string (legacy)
 * @param {string} dateString
 * @returns {string}
 */
function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffSecs < 60) return 'Hace unos segundos';
    if (diffMins < 60) return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;

    if (diffHours < 24) {
        const remainingMins = diffMins % 60;
        if (diffHours > 0 && remainingMins > 0) {
            return `Hace ${diffHours}h ${remainingMins}min`;
        }
        if (diffHours > 0 && remainingMins === 0) {
            return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
        }
        return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
    }

    if (diffDays < 7) return `Hace ${diffDays} dia${diffDays > 1 ? 's' : ''}`;
    if (diffDays < 30) {
        const weeks = Math.floor(diffDays / 7);
        return `Hace ${weeks} semana${weeks > 1 ? 's' : ''}`;
    }
    const months = Math.floor(diffDays / 30);
    return `Hace ${months} mes${months > 1 ? 'es' : ''}`;
}
