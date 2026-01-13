/**
 * Video Player - Utility Functions
 */

/**
 * Format seconds to HH:MM:SS or MM:SS
 * @param {number} seconds
 * @returns {string}
 */
export function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);

    if (hours > 0) {
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

/**
 * Helper function to compare sets
 * @param {Set} set1
 * @param {Set} set2
 * @returns {boolean}
 */
export function setsAreEqual(set1, set2) {
    if (set1.size !== set2.size) return false;
    for (let item of set1) {
        if (!set2.has(item)) return false;
    }
    return true;
}

/**
 * Get config from window.VideoPlayer.config
 * @returns {Object}
 */
export function getConfig() {
    return window.VideoPlayer?.config || {};
}

/**
 * Get video element
 * @returns {HTMLVideoElement}
 */
export function getVideo() {
    return document.getElementById('rugbyVideo');
}

/**
 * Get comments data from config
 * @returns {Array}
 */
export function getCommentsData() {
    return getConfig().comments || [];
}
