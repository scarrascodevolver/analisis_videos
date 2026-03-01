/**
 * Video Player - Comment Notifications Module
 * Shows comment notifications when video reaches comment timestamp
 */

import { formatTime, getVideo, getCommentsData, setsAreEqual } from './utils.js';

let lastCheckedTime = -1;
let activeCommentIds = new Set();
let notificationsEnabled = true; // Flag para habilitar/deshabilitar notificaciones
let notificationTimeouts = new Map(); // Track auto-hide timeouts to prevent accumulation
let commentsBySecond = new Map(); // Performance: Index comments by second for O(1) lookup

/**
 * Initialize notification system
 * Note: timeupdate handler moved to time-manager.js for performance
 */
export function initNotifications() {
    const video = getVideo();
    if (!video) return;

    // Build comment index for performance
    buildCommentIndex();

    // Keep seeked listener for immediate notifications after seeking
    video.addEventListener('seeked', function () {
        checkAndShowCommentNotifications();
    });

    // Expose close function globally
    window.closeNotification = closeNotification;
}

/**
 * Build timestamp index for fast comment lookup (Performance optimization)
 */
function buildCommentIndex() {
    commentsBySecond.clear();
    const commentsData = getCommentsData();

    commentsData.forEach(comment => {
        const timestamp = Math.floor(comment.timestamp_seconds);

        // Index comment for its timestamp +/- 1 second (tolerance)
        for (let t = timestamp - 1; t <= timestamp + 1; t++) {
            if (!commentsBySecond.has(t)) {
                commentsBySecond.set(t, []);
            }
            commentsBySecond.get(t).push(comment);
        }
    });
}

/**
 * Rebuild comment index (call when comments are added/removed)
 */
export function rebuildCommentIndex() {
    buildCommentIndex();
}

/**
 * Check and show comment notifications at current time
 */
export function checkAndShowCommentNotifications() {
    const video = getVideo();

    if (!video || !notificationsEnabled) return;

    const currentTime = Math.floor(video.currentTime);

    // Only check once per second
    if (currentTime === lastCheckedTime) return;
    lastCheckedTime = currentTime;

    // Performance optimization: Use indexed lookup instead of filter() - O(1) vs O(n)
    const currentComments = commentsBySecond.get(currentTime) || [];

    // Get IDs of comments that should be visible now
    const currentCommentIds = new Set(currentComments.map(c => c.id));

    // Only update notifications if the set of comments has changed
    if (!setsAreEqual(activeCommentIds, currentCommentIds)) {
        // Remove notifications that should no longer be visible
        activeCommentIds.forEach(commentId => {
            if (!currentCommentIds.has(commentId)) {
                const notification = document.getElementById(`notification-${commentId}`);
                if (notification && notification.parentNode) {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }
        });

        // Show new notifications
        currentComments.forEach(comment => {
            if (!activeCommentIds.has(comment.id)) {
                showCommentNotification(comment);
            }
        });

        // Update active comment IDs
        activeCommentIds = currentCommentIds;
    }
}

/**
 * Show a comment notification
 * @param {Object} comment
 */
function showCommentNotification(comment) {
    const video = getVideo();
    const notificationArea = document.getElementById('commentNotifications');

    if (!notificationArea || !video || !video.duration) return;

    // Calculate position relative to the notifications area
    const notificationAreaWidth = notificationArea.offsetWidth;
    const relativePosition = (comment.timestamp_seconds / video.duration) * notificationAreaWidth;

    // Create notification element
    const notification = document.createElement('div');
    notification.id = `notification-${comment.id}`;
    notification.className = 'comment-notification';

    // Category colors
    const categoryColors = {
        'tecnico': 'info',
        'tactico': 'warning',
        'fisico': 'success',
        'mental': 'purple',
        'general': 'secondary'
    };

    const priorityColors = {
        'critica': 'danger',
        'alta': 'warning',
        'media': 'info',
        'baja': 'secondary'
    };

    // Responsive width and positioning for mobile
    const isMobileView = window.innerWidth <= 768;
    const notificationWidth = isMobileView ? 280 : 320;
    const minWidth = isMobileView ? 200 : 250;
    const padding = isMobileView ? '8px 12px' : '12px 15px';

    // Smart positioning to keep notification within bounds (aplica a desktop y mobile)
    let leftPosition = relativePosition;
    let transformX = '-50%';

    const halfWidth = notificationWidth / 2;
    const margin = isMobileView ? 10 : 15;

    // Si está muy a la izquierda (ej: segundo 0), alinear al borde izquierdo
    if (relativePosition < halfWidth + margin) {
        leftPosition = margin;
        transformX = '0%';
    }
    // Si está muy a la derecha (ej: final del video), alinear al borde derecho
    else if (relativePosition > notificationAreaWidth - halfWidth - margin) {
        leftPosition = notificationAreaWidth - margin;
        transformX = '-100%';
    }

    notification.style.cssText = `
        position: absolute;
        bottom: 0;
        left: ${leftPosition}px;
        transform: translateX(${transformX});
        max-width: ${notificationWidth}px;
        min-width: ${minWidth}px;
        background: rgba(30, 30, 30, 0.95);
        border: 2px solid var(--color-accent, #FFC300);
        border-radius: 12px;
        padding: ${padding};
        box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        z-index: 1000;
        animation: slideUp 0.3s ease;
        pointer-events: auto;
        backdrop-filter: blur(5px);
    `;

    notification.innerHTML = `
        <div class="d-flex align-items-start">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge badge-${categoryColors[comment.category] || 'secondary'} mr-2" style="font-size: 10px;">
                        ${comment.category.charAt(0).toUpperCase() + comment.category.slice(1)}
                    </span>
                    <span class="badge badge-${priorityColors[comment.priority] || 'secondary'}" style="font-size: 10px;">
                        ${comment.priority.charAt(0).toUpperCase() + comment.priority.slice(1)}
                    </span>
                </div>
                <p class="mb-2" style="font-size: 13px; line-height: 1.3; font-weight: 500; color: #fff;">
                    ${comment.comment.length > 80 ? comment.comment.substring(0, 80) + '...' : comment.comment}
                </p>
                <small style="font-size: 11px; color: #aaa;">
                    <i class="fas fa-user"></i> ${comment.user.name}
                    <span class="ml-2"><i class="fas fa-clock"></i> ${formatTime(comment.timestamp_seconds)}</span>
                </small>
            </div>
            <button class="btn btn-sm btn-link p-1 ml-2"
                    style="font-size: 12px; color: #888;"
                    onclick="closeNotification(${comment.id})"
                    title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    notificationArea.appendChild(notification);

    // Auto-hide after 5 seconds - store timeout reference to prevent accumulation
    const autoHideTimeout = setTimeout(() => {
        if (notification.parentNode) {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                // Clean up timeout reference
                notificationTimeouts.delete(comment.id);
            }, 300);
        }
    }, 5000);

    // Store timeout for cleanup
    notificationTimeouts.set(comment.id, autoHideTimeout);
}

/**
 * Hide all notifications
 */
export function hideAllNotifications() {
    // Clear all auto-hide timeouts to prevent accumulation
    notificationTimeouts.forEach(timeout => clearTimeout(timeout));
    notificationTimeouts.clear();

    const notificationArea = document.getElementById('commentNotifications');
    if (!notificationArea) return;

    while (notificationArea.firstChild) {
        notificationArea.removeChild(notificationArea.firstChild);
    }
    activeCommentIds.clear();
}

/**
 * Close a specific notification
 * @param {number} commentId
 */
export function closeNotification(commentId) {
    // Clear auto-hide timeout to prevent accumulation
    if (notificationTimeouts.has(commentId)) {
        clearTimeout(notificationTimeouts.get(commentId));
        notificationTimeouts.delete(commentId);
    }

    const notification = document.getElementById(`notification-${commentId}`);
    if (notification && notification.parentNode) {
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
        activeCommentIds.delete(commentId);
    }
}

/**
 * Get active comment IDs (for external use)
 * @returns {Set}
 */
export function getActiveCommentIds() {
    return activeCommentIds;
}

/**
 * Toggle notifications on/off
 * @returns {boolean} New state
 */
export function toggleNotifications() {
    notificationsEnabled = !notificationsEnabled;

    if (!notificationsEnabled) {
        hideAllNotifications();
    }

    return notificationsEnabled;
}

/**
 * Set notifications enabled state
 * @param {boolean} enabled
 */
export function setNotificationsEnabled(enabled) {
    notificationsEnabled = enabled;

    if (!notificationsEnabled) {
        hideAllNotifications();
    }
}

/**
 * Check if notifications are enabled
 * @returns {boolean}
 */
export function areNotificationsEnabled() {
    return notificationsEnabled;
}
