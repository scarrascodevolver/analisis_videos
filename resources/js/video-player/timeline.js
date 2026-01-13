/**
 * Video Player - Timeline Module
 * Handles the comment timeline with markers and progress bar
 */

import { formatTime, getVideo, getCommentsData } from './utils.js';

/**
 * Initialize timeline functionality
 */
export function initTimeline() {
    const video = getVideo();
    if (!video) return;

    // Initialize timeline when video metadata loads
    video.addEventListener('loadedmetadata', function () {
        if (video.duration && !isNaN(video.duration)) {
            createTimelineMarkers();
        }

        // Check for timestamp parameter in URL (from session expiry return)
        const urlParams = new URLSearchParams(window.location.search);
        const startTime = urlParams.get('t');
        if (startTime && !isNaN(startTime)) {
            const timeInSeconds = parseInt(startTime);
            if (timeInSeconds > 0 && timeInSeconds < video.duration) {
                video.currentTime = timeInSeconds;

                // Show notification that video was restored
                if (typeof toastr !== 'undefined') {
                    toastr.success(`Video restaurado desde ${formatTime(timeInSeconds)}`, 'Sesion Recuperada');
                }

                // Clean URL parameter
                const newUrl = window.location.href.split('?')[0];
                window.history.replaceState({}, document.title, newUrl);
            }
        }
    });

    // Update timeline progress
    video.addEventListener('timeupdate', function () {
        updateProgressIndicator();
    });

    // Force timeline creation if video is already loaded
    if (video.readyState >= 2) {
        createTimelineMarkers();
    }

    // Also try after a delay
    setTimeout(function () {
        if (video.duration && !isNaN(video.duration) && !document.getElementById('progressBar')) {
            createTimelineMarkers();
        }
    }, 1000);
}

/**
 * Create timeline markers for comments
 */
export function createTimelineMarkers() {
    const video = getVideo();
    const timeline = document.getElementById('timelineMarkers');
    const commentsData = getCommentsData();

    if (!timeline || !video) return;

    const videoDuration = video.duration;

    if (!videoDuration || videoDuration === 0) {
        return;
    }

    // Clear existing content
    timeline.innerHTML = '';

    // Create progress bar container
    const progressContainer = document.createElement('div');
    progressContainer.style.cssText = `
        position: relative;
        width: 100%;
        height: 100%;
        background: #dee2e6;
        border-radius: 5px;
        cursor: pointer;
    `;

    // Progress bar
    const progressBar = document.createElement('div');
    progressBar.id = 'progressBar';
    progressBar.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 0%;
        background: var(--color-primary, #005461);
        border-radius: 5px;
        transition: width 0.1s ease;
    `;

    // Progress indicator
    const progressIndicator = document.createElement('div');
    progressIndicator.id = 'progressIndicator';
    progressIndicator.style.cssText = `
        position: absolute;
        top: -5px;
        left: 0%;
        width: 4px;
        height: 50px;
        background: var(--color-primary, #005461);
        border-radius: 2px;
        transition: left 0.1s ease;
        transform: translateX(-50%);
    `;

    progressContainer.appendChild(progressBar);
    progressContainer.appendChild(progressIndicator);

    // Add comment markers
    commentsData.forEach(comment => {
        const position = (comment.timestamp_seconds / videoDuration) * 100;

        const marker = document.createElement('div');
        marker.className = 'comment-marker';
        marker.setAttribute('data-timestamp', comment.timestamp_seconds);
        marker.setAttribute('data-comment', comment.comment);
        marker.style.cssText = `
            position: absolute;
            top: -5px;
            left: ${position}%;
            width: 8px;
            height: 50px;
            background: var(--color-accent, #4B9DA9);
            border: 2px solid #fff;
            border-radius: 4px;
            cursor: pointer;
            transform: translateX(-50%);
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        `;

        // Tooltip on hover
        marker.title = `${formatTime(comment.timestamp_seconds)}: ${comment.comment.substring(0, 50)}...`;

        // Click to seek
        marker.addEventListener('click', function (e) {
            e.stopPropagation();
            video.currentTime = comment.timestamp_seconds;
            if (video.paused) {
                video.play();
            }
        });

        progressContainer.appendChild(marker);
    });

    // Timeline click to seek
    progressContainer.addEventListener('click', function (e) {
        const rect = this.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const percentage = clickX / rect.width;
        const newTime = percentage * videoDuration;

        video.currentTime = newTime;
    });

    timeline.appendChild(progressContainer);
}

/**
 * Update progress indicator and bar
 */
export function updateProgressIndicator() {
    const video = getVideo();
    const progressIndicator = document.getElementById('progressIndicator');
    const progressBar = document.getElementById('progressBar');

    if (video && video.duration) {
        const percentage = (video.currentTime / video.duration) * 100;

        if (progressIndicator) {
            progressIndicator.style.left = percentage + '%';
        }

        if (progressBar) {
            progressBar.style.width = percentage + '%';
        }
    }
}
