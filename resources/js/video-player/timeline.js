/**
 * Video Player - Timeline Module
 * Handles the comment timeline with markers and progress bar
 *
 * Performance optimizations:
 * - Marker clustering to prevent overlap on dense timelines
 * - DocumentFragment for efficient batch DOM insertion
 * - Event delegation for all marker clicks (no individual listeners)
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

    // Note: timeline progress update moved to time-manager.js for better performance

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

    // Add comment markers (optimized with DocumentFragment for performance)
    const fragment = document.createDocumentFragment();
    const markerMinDistance = 1; // Minimum 1% distance to prevent overlap

    // Group nearby markers to prevent clutter (cluster markers within 1% of timeline)
    const clusteredMarkers = clusterMarkers(commentsData, videoDuration, markerMinDistance);

    clusteredMarkers.forEach(cluster => {
        const position = (cluster.timestamp / videoDuration) * 100;
        const isCluster = cluster.count > 1;

        const marker = document.createElement('div');
        marker.className = 'comment-marker';
        marker.setAttribute('data-timestamp', cluster.timestamp);
        marker.setAttribute('data-comment', cluster.comment);
        marker.setAttribute('data-cluster-count', cluster.count);
        marker.style.cssText = `
            position: absolute;
            top: -5px;
            left: ${position}%;
            width: ${isCluster ? '12px' : '8px'};
            height: 50px;
            background: var(--color-accent, #D4A017);
            border: 2px solid #fff;
            border-radius: 4px;
            cursor: pointer;
            transform: translateX(-50%);
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            ${isCluster ? 'opacity: 0.9;' : ''}
        `;

        // Tooltip on hover
        if (isCluster) {
            marker.title = `${formatTime(cluster.timestamp)}: ${cluster.count} comentarios`;
        } else {
            marker.title = `${formatTime(cluster.timestamp)}: ${cluster.comment.substring(0, 50)}...`;
        }

        // Badge for clustered markers
        if (isCluster && cluster.count > 2) {
            const badge = document.createElement('span');
            badge.style.cssText = `
                position: absolute;
                top: -8px;
                right: -8px;
                background: #dc3545;
                color: white;
                border-radius: 50%;
                width: 16px;
                height: 16px;
                font-size: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                pointer-events: none;
            `;
            badge.textContent = cluster.count > 9 ? '9+' : cluster.count;
            marker.appendChild(badge);
        }

        // Note: Click handler moved to event delegation below
        fragment.appendChild(marker);
    });

    // Batch append all markers at once (more efficient than individual appends)
    progressContainer.appendChild(fragment);

    // Event delegation: Single click handler for all markers and timeline
    progressContainer.addEventListener('click', function (e) {
        // Check if clicked on a marker
        const marker = e.target.closest('.comment-marker');

        if (marker) {
            // Clicked on a marker - seek to comment timestamp
            e.stopPropagation();
            const timestamp = parseFloat(marker.getAttribute('data-timestamp'));
            if (!isNaN(timestamp)) {
                video.currentTime = timestamp;
                if (video.paused) {
                    video.play();
                }
            }
        } else {
            // Clicked on timeline - seek to percentage
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const percentage = clickX / rect.width;
            const newTime = percentage * videoDuration;

            video.currentTime = newTime;
        }
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

/**
 * Cluster nearby markers to prevent overlap (Performance optimization)
 *
 * Groups comments that are within minDistance% of each other on the timeline.
 * For example, with 1% minDistance on a 100-second video, comments within
 * 1 second of each other will be clustered into a single marker.
 *
 * @param {Array} markers - Array of marker objects with timestamp_seconds
 * @param {number} videoDuration - Total video duration in seconds
 * @param {number} minDistance - Minimum distance in percentage (0-100)
 * @returns {Array} Array of clustered markers
 */
function clusterMarkers(markers, videoDuration, minDistance = 1) {
    if (markers.length === 0) return [];

    // Sort by timestamp
    const sorted = [...markers].sort((a, b) => a.timestamp_seconds - b.timestamp_seconds);

    const clusters = [];
    let currentCluster = {
        timestamp: sorted[0].timestamp_seconds,
        comment: sorted[0].comment,
        count: 1,
        comments: [sorted[0]]
    };

    for (let i = 1; i < sorted.length; i++) {
        const marker = sorted[i];
        const prevTimestamp = currentCluster.timestamp;
        const currentTimestamp = marker.timestamp_seconds;

        // Calculate distance as percentage of timeline
        const distance = ((currentTimestamp - prevTimestamp) / videoDuration) * 100;

        if (distance < minDistance) {
            // Add to current cluster
            currentCluster.count++;
            currentCluster.comments.push(marker);
            // Use first comment's text for display
            if (currentCluster.count === 2) {
                currentCluster.comment = `${currentCluster.comments[0].comment.substring(0, 30)}...`;
            }
        } else {
            // Start new cluster
            clusters.push(currentCluster);
            currentCluster = {
                timestamp: marker.timestamp_seconds,
                comment: marker.comment,
                count: 1,
                comments: [marker]
            };
        }
    }

    // Push last cluster
    clusters.push(currentCluster);

    console.log(`📍 Timeline: Clustered ${markers.length} markers into ${clusters.length} groups`);

    return clusters;
}
