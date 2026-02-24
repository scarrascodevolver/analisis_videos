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

        // Log all relevant keypresses (console.log = visible en filtro Default de Chrome)
        const isRelevant = key.length === 1 || ['space', 'escape', 'arrowleft', 'arrowright'].includes(key);
        if (isRelevant) {
            const activeEl = document.activeElement;
            console.log(
                `[hotkey] ↓ key=${JSON.stringify(key)}`,
                `handler=${shortcuts.has(key) ? '✓' : '✗'}`,
                `inputFocused=${isInputFocused()}`,
                `activeEl=${activeEl?.tagName}#${(activeEl as HTMLElement)?.id || (activeEl as HTMLElement)?.className?.toString().slice(0, 40) || '?'}`,
                `registered=[${Array.from(shortcuts.keys()).join(',')}]`,
            );
        }

        // Ignore if user is typing in an input field
        if (isInputFocused()) return;

        // Blur defensivo: si hay algún elemento con foco (ej. header timeline, botón)
        // que no sea un input, lo desfocalizamos. Esto limpia anillos de foco visuales
        // y evita que elementos UI intercepten la tecla como acción propia.
        const focusedEl = document.activeElement as HTMLElement | null;
        if (focusedEl && focusedEl !== document.body && focusedEl.tagName !== 'BODY') {
            focusedEl.blur?.();
        }

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
        // capture: true → recibe el evento ANTES de que cualquier elemento hijo
        // pueda llamar stopPropagation() en la fase de burbujeo.
        // Esto garantiza que los hotkeys globales siempre funcionen sin importar
        // qué elemento tiene foco o llama stopPropagation.
        window.addEventListener('keydown', handleKeyDown, { capture: true });
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeyDown, { capture: true });
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
