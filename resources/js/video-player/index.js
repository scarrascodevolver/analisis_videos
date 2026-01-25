/**
 * Video Player - Main Entry Point
 *
 * This module initializes all video player functionality.
 * Requires window.VideoPlayer.config to be defined before loading.
 */

// Import all modules
import { formatTime, getVideo, getConfig, getCommentsData } from './utils.js';
import { initTimeManager, registerTimeupdateCallback, updateTimestampInput, checkViewTracking, checkAutoComplete } from './time-manager.js';
import { initCleanup } from './cleanup.js';
import { initViewTracking } from './view-tracking.js';
import { initTimeline, createTimelineMarkers, updateProgressIndicator } from './timeline.js';
import { initNotifications, checkAndShowCommentNotifications } from './notifications.js';
import { initFullscreen } from './fullscreen.js';
import { initComments } from './comments.js';
import { initAnnotations, checkAndShowAnnotations } from './annotations.js';
import { initStatsModal } from './stats-modal.js';
import { initMentions } from './mentions.js';
import { initClipManager } from './clip-manager.js';

/**
 * Initialize Video Player when DOM is ready
 */
$(document).ready(function () {
    // Check if config is available
    if (!window.VideoPlayer || !window.VideoPlayer.config) {
        console.error('VideoPlayer.config not found. Make sure to define it before loading this script.');
        return;
    }

    const config = getConfig();
    const video = getVideo();

    if (!video) {
        console.error('Video element not found');
        return;
    }

    console.log('Initializing Video Player...');

    // Initialize cleanup handlers FIRST (to catch any errors)
    initCleanup();

    // Initialize centralized time manager
    initTimeManager();

    // Register timeupdate callbacks (consolidated from multiple modules)
    registerTimeupdateCallback(updateTimestampInput, 'timestamp-input');
    registerTimeupdateCallback(checkViewTracking, 'view-tracking');
    registerTimeupdateCallback(checkAutoComplete, 'auto-complete');
    registerTimeupdateCallback((currentTime, video) => {
        updateProgressIndicator();
    }, 'timeline-progress');
    registerTimeupdateCallback((currentTime, video) => {
        checkAndShowCommentNotifications();
    }, 'comment-notifications');
    registerTimeupdateCallback((currentTime, video) => {
        checkAndShowAnnotations();
    }, 'annotations-check');

    // Initialize timestamp input controls
    initTimestampInputControls();

    // Initialize all modules (without their own timeupdate listeners)
    initViewTracking();
    initTimeline();
    initNotifications();
    initFullscreen();
    initComments(config.comments || []);
    initAnnotations();
    initStatsModal();
    initMentions();
    initClipManager();

    console.log('Video Player initialized successfully');
});

/**
 * Initialize timestamp input controls (non-timeupdate functionality)
 */
function initTimestampInputControls() {
    const video = getVideo();
    const timestampInput = document.getElementById('timestamp_seconds');
    const timestampDisplay = document.getElementById('timestampDisplay');

    if (!video || !timestampInput || !timestampDisplay) return;

    // Use current time button
    const useCurrentTimeBtn = document.getElementById('useCurrentTime');
    if (useCurrentTimeBtn) {
        useCurrentTimeBtn.addEventListener('click', function () {
            const currentSeconds = Math.floor(video.currentTime || 0);
            timestampInput.value = currentSeconds;
            timestampDisplay.textContent = formatTime(currentSeconds);
        });
    }

    // Update timestamp display when input changes
    timestampInput.addEventListener('input', function () {
        const seconds = parseInt(this.value) || 0;
        timestampDisplay.textContent = formatTime(seconds);
    });
}

// Export for external use if needed
export {
    formatTime,
    getVideo,
    getConfig,
    createTimelineMarkers,
    checkAndShowAnnotations
};
