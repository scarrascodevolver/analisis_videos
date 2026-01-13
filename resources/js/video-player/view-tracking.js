/**
 * Video Player - View Tracking Module
 * Tracks video views after 20 seconds and auto-completes at 90%
 */

import { getConfig, getVideo } from './utils.js';

let currentViewId = null;
let trackingActive = false;
let durationUpdateInterval = null;
let viewTracked = false;

/**
 * Initialize view tracking
 */
export function initViewTracking() {
    const video = getVideo();
    if (!video) return;

    video.addEventListener('timeupdate', function () {
        // 1. Count view after 20 seconds of playback
        if (!viewTracked && video.currentTime >= 20) {
            trackView();
            viewTracked = true;
        }

        // 2. Auto-complete at 90% of video
        if (currentViewId && video.duration > 0) {
            const percentWatched = (video.currentTime / video.duration) * 100;
            if (percentWatched >= 90) {
                markVideoCompleted();
            }
        }
    });
}

/**
 * Track a view
 */
function trackView() {
    const config = getConfig();

    fetch(config.routes.trackView, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': config.csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && !data.cooldown) {
                currentViewId = data.view_id;
                trackingActive = true;

                // Update view count in UI
                updateViewCount(data.total_views, data.unique_viewers);

                // Start duration tracking
                startDurationTracking();

                console.log('View tracked successfully');
            } else if (data.cooldown) {
                console.log('View within cooldown period');
            }
        })
        .catch(error => console.error('Error tracking view:', error));
}

/**
 * Start tracking watch duration
 */
function startDurationTracking() {
    const video = getVideo();

    if (durationUpdateInterval) {
        clearInterval(durationUpdateInterval);
    }

    durationUpdateInterval = setInterval(() => {
        if (currentViewId && !video.paused) {
            updateWatchDuration();
        }
    }, 10000); // 10 seconds
}

/**
 * Update watch duration on server
 */
function updateWatchDuration() {
    if (!currentViewId) return;

    const config = getConfig();
    const video = getVideo();

    fetch(config.routes.updateDuration, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': config.csrfToken
        },
        body: JSON.stringify({
            view_id: currentViewId,
            duration: Math.floor(video.currentTime)
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Duration updated');
            }
        })
        .catch(error => console.error('Error updating duration:', error));
}

/**
 * Mark video as completed
 */
function markVideoCompleted() {
    if (!currentViewId) return;

    const config = getConfig();

    fetch(config.routes.markCompleted, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': config.csrfToken
        },
        body: JSON.stringify({
            view_id: currentViewId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Video marked as completed');
                currentViewId = null;
            }
        })
        .catch(error => console.error('Error marking completed:', error));
}

/**
 * Update view count in UI
 * @param {number} totalViews
 * @param {number} uniqueViewers
 */
function updateViewCount(totalViews, uniqueViewers) {
    const viewCountElement = document.getElementById('viewCount');
    const uniqueViewersElement = document.getElementById('uniqueViewers');

    if (viewCountElement) {
        viewCountElement.textContent = totalViews;
    }
    if (uniqueViewersElement) {
        uniqueViewersElement.textContent = uniqueViewers;
    }
}
