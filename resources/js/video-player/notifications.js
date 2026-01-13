/**
 * Video Player - Comment Notifications Module
 * Shows comment notifications when video reaches comment timestamp
 */

import { formatTime, getVideo, getCommentsData, setsAreEqual } from './utils.js';

let lastCheckedTime = -1;
let activeCommentIds = new Set();

/**
 * Initialize notification system
 */
export function initNotifications() {
    const video = getVideo();
    if (!video) return;

    video.addEventListener('timeupdate', function () {
        checkAndShowCommentNotifications();
    });

    video.addEventListener('seeked', function () {
        checkAndShowCommentNotifications();
    });

    // Expose close function globally
    window.closeNotification = closeNotification;
}

/**
 * Check and show comment notifications at current time
 */
export function checkAndShowCommentNotifications() {
    const video = getVideo();
    const commentsData = getCommentsData();

    if (!video) return;

    const currentTime = Math.floor(video.currentTime);

    // Only check once per second
    if (currentTime === lastCheckedTime) return;
    lastCheckedTime = currentTime;

    // Find comments at current time (exact match or +/-1 second)
    const currentComments = commentsData.filter(comment =>
        Math.abs(comment.timestamp_seconds - currentTime) <= 1
    );

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

    // Smart positioning to keep notification within bounds
    let leftPosition = relativePosition;
    let transformX = '-50%';

    if (isMobileView) {
        const halfWidth = notificationWidth / 2;
        const margin = 10;

        if (relativePosition < halfWidth + margin) {
            leftPosition = margin;
            transformX = '0%';
        } else if (relativePosition > notificationAreaWidth - halfWidth - margin) {
            leftPosition = notificationAreaWidth - margin;
            transformX = '-100%';
        }
    }

    notification.style.cssText = `
        position: absolute;
        top: 10px;
        left: ${leftPosition}px;
        transform: translateX(${transformX});
        max-width: ${notificationWidth}px;
        min-width: ${minWidth}px;
        background: rgba(255, 255, 255, 0.95);
        border: 2px solid var(--color-accent, #4B9DA9);
        border-radius: 12px;
        padding: ${padding};
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: fadeIn 0.5s ease;
        pointer-events: auto;
        backdrop-filter: blur(3px);
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
                <p class="mb-2 text-dark" style="font-size: 13px; line-height: 1.3; font-weight: 500;">
                    ${comment.comment.length > 80 ? comment.comment.substring(0, 80) + '...' : comment.comment}
                </p>
                <small class="text-muted" style="font-size: 11px;">
                    <i class="fas fa-user"></i> ${comment.user.name}
                    <span class="ml-2"><i class="fas fa-clock"></i> ${formatTime(comment.timestamp_seconds)}</span>
                </small>
            </div>
            <button class="btn btn-sm btn-link text-muted p-1 ml-2"
                    style="font-size: 12px; opacity: 0.8;"
                    onclick="closeNotification(${comment.id})"
                    title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    notificationArea.appendChild(notification);

    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
}

/**
 * Hide all notifications
 */
export function hideAllNotifications() {
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
