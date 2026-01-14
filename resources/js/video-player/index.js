/**
 * Video Player - Main Entry Point
 *
 * This module initializes all video player functionality.
 * Requires window.VideoPlayer.config to be defined before loading.
 */

// Import all modules
import { formatTime, getVideo, getConfig, getCommentsData } from './utils.js';
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

    // Initialize timestamp input functionality
    initTimestampInput();

    // Initialize all modules
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
 * Initialize timestamp input and display
 */
function initTimestampInput() {
    const video = getVideo();
    const timestampInput = document.getElementById('timestamp_seconds');
    const timestampDisplay = document.getElementById('timestampDisplay');

    if (!video || !timestampInput || !timestampDisplay) return;

    // Update timestamp input to current video time
    video.addEventListener('timeupdate', function () {
        if (document.activeElement !== timestampInput) {
            timestampInput.value = Math.floor(video.currentTime);
            timestampDisplay.textContent = formatTime(Math.floor(video.currentTime));
        }
    });

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
