/**
 * Video Player - Cleanup Module
 *
 * Centralized cleanup to prevent memory leaks when navigating away
 * or closing the page.
 */

import { cleanupTimeManager, resetTracking } from './time-manager.js';
import { cleanupViewTracking } from './view-tracking.js';

// Track if cleanup has been performed
let cleanupPerformed = false;

/**
 * Initialize cleanup handlers
 */
export function initCleanup() {
    // Cleanup on page unload
    window.addEventListener('beforeunload', performCleanup);

    // Cleanup on page hide (mobile browsers)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            performCleanup();
        }
    });

    // Cleanup on page freeze (modern browsers)
    document.addEventListener('freeze', performCleanup);

    console.log('Cleanup handlers initialized');
}

/**
 * Perform complete cleanup
 */
export function performCleanup() {
    if (cleanupPerformed) return;

    console.log('Performing cleanup...');

    try {
        // 1. Cleanup time manager (removes timeupdate listener)
        cleanupTimeManager();
        resetTracking();

        // 2. Cleanup view tracking (clears intervals)
        cleanupViewTracking();

        // 3. Clear any remaining intervals globally
        clearAllIntervals();

        // 4. Clear any remaining timeouts
        clearAllTimeouts();

        // 5. Remove large data from memory
        clearMemoryArrays();

        cleanupPerformed = true;
        console.log('Cleanup completed successfully');
    } catch (error) {
        console.error('Error during cleanup:', error);
    }
}

/**
 * Clear all registered intervals
 */
function clearAllIntervals() {
    // Get highest interval ID and clear all
    const highestIntervalId = setInterval(() => {}, 1);
    for (let i = 0; i < highestIntervalId; i++) {
        clearInterval(i);
    }
    clearInterval(highestIntervalId);
}

/**
 * Clear all registered timeouts
 */
function clearAllTimeouts() {
    // Get highest timeout ID and clear all
    const highestTimeoutId = setTimeout(() => {}, 1);
    for (let i = 0; i < highestTimeoutId; i++) {
        clearTimeout(i);
    }
    clearTimeout(highestTimeoutId);
}

/**
 * Clear large data arrays from memory
 */
function clearMemoryArrays() {
    // Clear comments data if exists
    if (window.sidebarClipsData) {
        window.sidebarClipsData.length = 0;
    }

    // Clear saved annotations if exists
    if (window.savedAnnotations) {
        window.savedAnnotations.length = 0;
    }

    // Clear current displayed annotations
    if (window.currentDisplayedAnnotations) {
        window.currentDisplayedAnnotations.length = 0;
    }
}

/**
 * Manual cleanup function (can be called programmatically)
 */
export function cleanup() {
    performCleanup();
}

// Expose globally for emergency cleanup
window.performVideoPlayerCleanup = performCleanup;
