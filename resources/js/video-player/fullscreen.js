/**
 * Video Player - Fullscreen Module
 * Handles pseudo-fullscreen for mobile devices
 */

import { formatTime, getVideo } from './utils.js';

let isPseudoFullscreen = false;
let isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

/**
 * Initialize fullscreen functionality
 */
export function initFullscreen() {
    const video = getVideo();
    if (!video) return;

    const mobileFullscreenBtn = document.getElementById('mobileFullscreenBtn');

    // Show mobile fullscreen button only on mobile devices
    if (isMobile && mobileFullscreenBtn) {
        mobileFullscreenBtn.style.display = 'inline-block';

        // Disable native fullscreen on mobile
        video.addEventListener('webkitbeginfullscreen', function (e) {
            e.preventDefault();
            enterPseudoFullscreen();
        });

        // Hide native fullscreen button
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
    }

    // Mobile fullscreen button click
    if (mobileFullscreenBtn) {
        mobileFullscreenBtn.addEventListener('click', function () {
            if (isPseudoFullscreen) {
                exitPseudoFullscreen();
            } else {
                enterPseudoFullscreen();
            }
        });
    }
}

/**
 * Enter pseudo-fullscreen mode
 */
export function enterPseudoFullscreen() {
    const videoSection = document.getElementById('videoSection');
    if (!videoSection) return;

    const videoContainer = videoSection.querySelector('.video-container');
    if (!videoContainer) return;

    isPseudoFullscreen = true;

    // Create pseudo-fullscreen overlay
    const overlay = document.createElement('div');
    overlay.id = 'pseudoFullscreenOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: black;
        z-index: 9999;
        display: flex;
        flex-direction: column;
    `;

    // Clone video container
    const clonedContainer = videoContainer.cloneNode(true);
    clonedContainer.style.cssText = `
        flex: 1;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    // Update video size
    const clonedVideo = clonedContainer.querySelector('#rugbyVideo');
    if (clonedVideo) {
        clonedVideo.style.cssText = `
            width: 100%;
            height: 100%;
            max-height: calc(100vh - 200px);
            object-fit: contain;
        `;
    }

    // Add exit button
    const exitBtn = document.createElement('button');
    exitBtn.innerHTML = '<i class="fas fa-times"></i>';
    exitBtn.style.cssText = `
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(0,0,0,0.7);
        border: none;
        color: white;
        font-size: 24px;
        padding: 10px 15px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 10000;
    `;
    exitBtn.onclick = exitPseudoFullscreen;

    // Add comments area at bottom
    const commentsArea = document.createElement('div');
    commentsArea.id = 'pseudoFullscreenComments';
    commentsArea.style.cssText = `
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 150px;
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 20px;
        overflow-y: auto;
        border-top: 2px solid var(--color-accent, #D4A017);
    `;
    commentsArea.innerHTML = '<h6><i class="fas fa-comments"></i> Comentarios en tiempo real</h6>';

    overlay.appendChild(exitBtn);
    overlay.appendChild(clonedContainer);
    overlay.appendChild(commentsArea);
    document.body.appendChild(overlay);

    // Hide original video
    videoContainer.style.display = 'none';

    // Update button icon
    const mobileBtn = document.getElementById('mobileFullscreenBtn');
    if (mobileBtn) {
        mobileBtn.innerHTML = '<i class="fas fa-compress"></i>';
    }
}

/**
 * Exit pseudo-fullscreen mode
 */
export function exitPseudoFullscreen() {
    const overlay = document.getElementById('pseudoFullscreenOverlay');
    if (overlay) {
        overlay.remove();
    }

    // Show original video
    const videoSection = document.getElementById('videoSection');
    if (videoSection) {
        const videoContainer = videoSection.querySelector('.video-container');
        if (videoContainer) {
            videoContainer.style.display = 'block';
        }
    }

    isPseudoFullscreen = false;

    // Update button icon
    const mobileBtn = document.getElementById('mobileFullscreenBtn');
    if (mobileBtn) {
        mobileBtn.innerHTML = '<i class="fas fa-expand"></i>';
    }
}

/**
 * Show comment in pseudo-fullscreen mode
 * @param {Object} comment
 */
export function showCommentInPseudoFullscreen(comment) {
    const commentsArea = document.getElementById('pseudoFullscreenComments');
    if (!commentsArea) return;

    const notification = document.createElement('div');
    notification.className = 'pseudo-fullscreen-comment';
    notification.style.cssText = `
        background: rgba(212, 160, 23, 0.2);
        border: 1px solid var(--color-accent, #D4A017);
        border-radius: 8px;
        padding: 10px;
        margin: 10px 0;
        animation: slideInFromBottom 0.5s ease;
    `;

    notification.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <div class="mb-1">
                    <span class="badge badge-success">${comment.category}</span>
                    <span class="badge badge-warning ml-1">${comment.priority}</span>
                </div>
                <p class="mb-1" style="font-size: 14px;">${comment.comment}</p>
                <small style="opacity: 0.8;">
                    <i class="fas fa-user"></i> ${comment.user.name} - ${formatTime(comment.timestamp_seconds)}
                </small>
            </div>
            <button onclick="this.parentNode.parentNode.remove()" class="btn btn-sm btn-link text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    commentsArea.appendChild(notification);

    // Auto-remove after 8 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 8000);
}

/**
 * Check if in pseudo-fullscreen mode
 * @returns {boolean}
 */
export function isInPseudoFullscreen() {
    return isPseudoFullscreen;
}

/**
 * Check if on mobile device
 * @returns {boolean}
 */
export function isMobileDevice() {
    return isMobile;
}
