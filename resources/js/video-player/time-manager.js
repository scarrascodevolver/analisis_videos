/**
 * Video Player - Centralized Time Manager
 *
 * Manages all time-based operations with a single timeupdate listener
 * and throttling to improve performance.
 *
 * Performance: Reduces 16 operations/sec to 4 operations/sec
 */

import { formatTime, getVideo, getCommentsData } from './utils.js';

// Registered callbacks for timeupdate
const timeupdateCallbacks = [];
let lastUpdateTime = -1;
let timeupdateListener = null;

/**
 * Initialize centralized time manager
 */
export function initTimeManager() {
    const video = getVideo();
    if (!video) return;

    // Single timeupdate listener with throttling
    timeupdateListener = function() {
        const currentTime = Math.floor(video.currentTime);

        // Throttle: Only process once per second
        if (currentTime === lastUpdateTime) return;
        lastUpdateTime = currentTime;

        // Execute all registered callbacks
        timeupdateCallbacks.forEach(({ callback, name }) => {
            try {
                callback(currentTime, video);
            } catch (error) {
                console.error(`Error in timeupdate callback '${name}':`, error);
            }
        });
    };

    video.addEventListener('timeupdate', timeupdateListener);
    console.log('Time Manager initialized with', timeupdateCallbacks.length, 'callbacks');
}

/**
 * Register a callback to be called on timeupdate
 * @param {Function} callback - Function to call (receives currentTime, video)
 * @param {string} name - Optional name for debugging
 */
export function registerTimeupdateCallback(callback, name = 'unnamed') {
    timeupdateCallbacks.push({ callback, name });
    console.log(`Registered timeupdate callback: ${name}`);
}

/**
 * Cleanup: Remove all callbacks and listener
 */
export function cleanupTimeManager() {
    const video = getVideo();
    if (video && timeupdateListener) {
        video.removeEventListener('timeupdate', timeupdateListener);
    }

    timeupdateCallbacks.length = 0;
    timeupdateListener = null;
    lastUpdateTime = -1;

    console.log('Time Manager cleaned up');
}

/**
 * Update timestamp input (called once per second instead of 4x/sec)
 */
export function updateTimestampInput(currentTime, video) {
    const timestampInput = document.getElementById('timestamp_seconds');
    const timestampDisplay = document.getElementById('timestampDisplay');

    if (!timestampInput || !timestampDisplay) return;

    // Only update if user is not editing
    if (document.activeElement !== timestampInput) {
        timestampInput.value = currentTime;
        timestampDisplay.textContent = formatTime(currentTime);
    }
}

/**
 * Track view after 20 seconds (moved from view-tracking.js)
 */
let viewTracked = false;
export function checkViewTracking(currentTime, video) {
    if (!viewTracked && currentTime >= 20) {
        // Dispatch custom event for view-tracking module
        window.dispatchEvent(new CustomEvent('videoview:track'));
        viewTracked = true;
    }
}

/**
 * Auto-complete at 90% (moved from view-tracking.js)
 */
export function checkAutoComplete(currentTime, video) {
    if (video.duration > 0) {
        const percentWatched = (currentTime / video.duration) * 100;
        if (percentWatched >= 90) {
            // Dispatch custom event for view-tracking module
            window.dispatchEvent(new CustomEvent('videoview:complete'));
        }
    }
}

/**
 * Reset tracking state (for cleanup)
 */
export function resetTracking() {
    viewTracked = false;
}
