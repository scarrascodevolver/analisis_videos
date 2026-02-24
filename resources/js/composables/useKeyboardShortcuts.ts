import { onMounted, onUnmounted } from 'vue';
import type { ClipCategory } from '@/types/video-player';

interface ShortcutHandler {
    key: string;
    callback: () => void;
    description?: string;
}

export function useKeyboardShortcuts() {
    const shortcuts: Map<string, ShortcutHandler> = new Map();

    function isInputFocused(): boolean {
        const activeElement = document.activeElement;
        if (!activeElement) return false;

        const tagName = activeElement.tagName.toLowerCase();
        return (
            tagName === 'input' ||
            tagName === 'textarea' ||
            tagName === 'select' ||
            activeElement.hasAttribute('contenteditable')
        );
    }

    function handleKeyDown(event: KeyboardEvent) {
        // Normalize: event.key for spacebar is ' ' (space char), not 'Space'
        const raw = event.key === ' ' ? 'space' : event.key;
        const key = raw.toLowerCase();

        // Log all relevant keypresses for diagnostics
        const isRelevant = key.length === 1 || ['space', 'escape', 'arrowleft', 'arrowright'].includes(key);
        if (isRelevant) {
            const activeEl = document.activeElement;
            console.debug(
                `[hotkey] ↓ key=${JSON.stringify(key)}`,
                `| handler=${shortcuts.has(key) ? '✓' : '✗'}`,
                `| inputFocused=${isInputFocused()}`,
                `| activeEl=${activeEl?.tagName}#${(activeEl as HTMLElement)?.id || (activeEl as HTMLElement)?.className?.toString().slice(0, 30) || '?'}`,
                `| registered=[${Array.from(shortcuts.keys()).join(', ')}]`,
            );
        }

        // Ignore if user is typing in an input field
        if (isInputFocused()) return;

        const handler = shortcuts.get(key);

        if (handler) {
            event.preventDefault();
            handler.callback();
        }
    }

    function registerHotkey(key: string, callback: () => void, description?: string) {
        shortcuts.set(key.toLowerCase(), { key, callback, description });
    }

    function unregisterHotkey(key: string) {
        shortcuts.delete(key.toLowerCase());
    }

    function registerCategoryHotkeys(
        categories: ClipCategory[],
        onHotkeyPress: (categoryId: number) => void
    ) {
        categories.forEach((category) => {
            if (category.hotkey && category.is_active) {
                registerHotkey(
                    category.hotkey,
                    () => onHotkeyPress(category.id),
                    `Clip: ${category.name}`
                );
            }
        });
    }

    function unregisterAll() {
        shortcuts.clear();
    }

    function getRegisteredShortcuts(): ShortcutHandler[] {
        return Array.from(shortcuts.values());
    }

    onMounted(() => {
        window.addEventListener('keydown', handleKeyDown);
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeyDown);
        unregisterAll();
    });

    return {
        registerHotkey,
        unregisterHotkey,
        registerCategoryHotkeys,
        unregisterAll,
        getRegisteredShortcuts,
    };
}
