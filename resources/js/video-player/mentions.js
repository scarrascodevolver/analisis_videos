/**
 * Video Player - Mentions Module
 * Handles @mentions autocomplete using Tribute.js
 */

import { getConfig } from './utils.js';

/**
 * Initialize mentions autocomplete
 */
export function initMentions() {
    // Check if Tribute is available
    if (typeof Tribute === 'undefined') {
        console.warn('Tribute.js not loaded, mentions disabled');
        return;
    }

    const config = getConfig();
    const allUsers = config.allUsers || [];

    if (allUsers.length === 0) {
        console.warn('No users available for mentions');
        return;
    }

    // Configure Tribute.js
    const tribute = new Tribute({
        values: allUsers.map(user => ({
            key: user.name,
            value: user.name,
            role: user.role
        })),
        selectTemplate: function (item) {
            // Add space after mention to avoid continuous suggestions
            return '@' + item.original.value + ' ';
        },
        menuItemTemplate: function (item) {
            const badgeClass = item.original.role === 'jugador' ? 'badge-info' :
                (item.original.role === 'entrenador' ? 'badge-success' :
                    (item.original.role === 'analista' ? 'badge-primary' : 'badge-secondary'));

            return `
                <div class="d-flex justify-content-between align-items-center">
                    <span>${item.original.value}</span>
                    <span class="badge ${badgeClass} ml-2">${item.original.role}</span>
                </div>
            `;
        },
        noMatchTemplate: function () {
            return '<span style="visibility: hidden;"></span>';
        },
        lookup: 'key',
        fillAttr: 'value',
        allowSpaces: true,
        menuShowMinLength: 0
    });

    // Attach tribute to comment textareas
    const textareas = document.querySelectorAll('textarea[name="comment"], textarea[name="reply_comment"]');
    if (textareas.length > 0) {
        tribute.attach(textareas);
    }

    // Also attach to dynamically added textareas
    $(document).on('focus', 'textarea[name="reply_comment"]', function () {
        if (!this.tributeAttached) {
            tribute.attach(this);
            this.tributeAttached = true;
        }
    });
}
