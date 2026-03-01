/**
 * UI Helpers - Small UI enhancements
 *
 * Contains optional UI features that don't depend on Blade
 */

import { getVideo } from './utils.js';

/**
 * Initialize all UI helpers
 */
export function initUIHelpers() {
    initTimelineToggle();
    initAutoHideSidebar();
    initSpeedControl();
    initPictureInPicture();
    initDownloadVideo();

    console.log('UI helpers initialized');
}

/**
 * Toggle Timeline - Collapse/expand timeline for analysts/coaches
 */
function initTimelineToggle() {
    const toggleBtn = document.getElementById('toggleTimeline');
    const content = document.getElementById('timelineContent');
    const arrow = document.getElementById('timelineArrow');

    if (!toggleBtn || !content) {
        return; // Element not present (user might not have permissions)
    }

    toggleBtn.addEventListener('click', function() {
        const isVisible = content.style.display !== 'none';
        content.style.display = isVisible ? 'none' : 'block';
        if (arrow) {
            arrow.classList.toggle('fa-chevron-up', !isVisible);
            arrow.classList.toggle('fa-chevron-down', isVisible);
        }
    });
}

/**
 * Auto-hide Sidebar - Collapse sidebar when video starts playing
 * Better viewing experience
 */
function initAutoHideSidebar() {
    const video = getVideo();

    if (!video) return;

    video.addEventListener('play', function() {
        document.body.classList.add('sidebar-collapse');
    });
}

/**
 * Speed Control - Video playback speed control
 */
function initSpeedControl() {
    const video = getVideo();
    const speedBtn = document.getElementById('speedControlBtn');
    const speedMenu = document.getElementById('speedMenu');
    const utilityControls = document.querySelector('.video-utility-controls');
    const currentSpeedDisplay = document.getElementById('currentSpeed');
    const speedOptions = document.querySelectorAll('.speed-option');

    if (!video || !speedBtn) return;

    // Toggle menu on button click
    speedBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        speedMenu.classList.toggle('show');
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!speedBtn.contains(e.target) && !speedMenu.contains(e.target)) {
            speedMenu.classList.remove('show');
        }
    });

    // Speed option selection
    speedOptions.forEach(option => {
        option.addEventListener('click', function() {
            const speed = parseFloat(this.dataset.speed);
            video.playbackRate = speed;

            // Update display
            if (currentSpeedDisplay) {
                currentSpeedDisplay.textContent = `${speed}x`;
            }

            // Update active state
            speedOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');

            // Close menu
            speedMenu.classList.remove('show');

            // Show brief feedback
            showSpeedFeedback(speed);
        });
    });

    // Keep utility controls visible when speed menu is open
    speedMenu.addEventListener('mouseenter', function() {
        if (utilityControls) {
            utilityControls.style.opacity = '1';
        }
    });

    speedMenu.addEventListener('mouseleave', function() {
        if (utilityControls && !speedMenu.classList.contains('show')) {
            utilityControls.style.opacity = '';
        }
    });
}

/**
 * Show brief speed feedback overlay
 */
function showSpeedFeedback(speed) {
    // Remove existing feedback if any
    const existing = document.getElementById('speedFeedback');
    if (existing) {
        existing.remove();
    }

    const feedback = document.createElement('div');
    feedback.id = 'speedFeedback';
    feedback.textContent = `Velocidad: ${speed}x`;
    feedback.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(212, 160, 23, 0.95);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        animation: fadeInOut 1.5s ease-in-out;
    `;

    document.body.appendChild(feedback);

    // Remove after animation
    setTimeout(() => {
        feedback.remove();
    }, 1500);
}

// Add animation styles dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
        20% { opacity: 1; transform: translateX(-50%) translateY(0); }
        80% { opacity: 1; transform: translateX(-50%) translateY(0); }
        100% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
    }
`;
document.head.appendChild(style);

/**
 * Picture-in-Picture - Mini floating video window
 */
function initPictureInPicture() {
    const video = getVideo();
    const pipBtn = document.getElementById('pipBtn');

    if (!video || !pipBtn) return;

    // Check if PiP is supported
    if (!document.pictureInPictureEnabled) {
        pipBtn.disabled = true;
        pipBtn.title = 'Picture-in-Picture no soportado en este navegador';
        return;
    }

    pipBtn.addEventListener('click', async function() {
        try {
            if (document.pictureInPictureElement) {
                // Already in PiP, exit
                await document.exitPictureInPicture();
            } else {
                // Enter PiP
                await video.requestPictureInPicture();
            }
        } catch (error) {
            console.error('Error with Picture-in-Picture:', error);
            alert('No se pudo activar Picture-in-Picture. Asegúrate de que el video esté reproduciendo.');
        }
    });

    // Update button icon when entering/exiting PiP
    video.addEventListener('enterpictureinpicture', function() {
        pipBtn.innerHTML = '<i class="fas fa-compress"></i>';
        pipBtn.title = 'Salir de Picture-in-Picture';
    });

    video.addEventListener('leavepictureinpicture', function() {
        pipBtn.innerHTML = '<i class="fas fa-external-link-alt"></i>';
        pipBtn.title = 'Picture-in-Picture (Mini ventana)';
    });
}

/**
 * Download Video - Download video file
 */
function initDownloadVideo() {
    const video = getVideo();
    const downloadBtn = document.getElementById('downloadBtn');

    if (!video || !downloadBtn) return;

    downloadBtn.addEventListener('click', function() {
        const videoSrc = video.querySelector('source').src;
        const videoTitle = video.dataset.videoTitle || 'video'; // Read from data-attribute

        // Create download link
        const a = document.createElement('a');
        a.href = videoSrc;
        a.download = videoTitle.replace(/[^a-zA-Z0-9\s\-_]/g, '').replace(/\s+/g, '_') + '.mp4';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    });
}
