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
                `docFocused=${document.hasFocus()}`,
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

    // Canary: listener completamente independiente para detectar si window recibe eventos
    // Si este aparece pero [hotkey] ↓ NO aparece → el listener principal fue removido
    // Si este NO aparece → window no recibe eventos (foco perdido, iframe, etc.)
    function canaryListener(e: KeyboardEvent) {
        const raw = e.key === ' ' ? 'space' : e.key;
        const k = raw.toLowerCase();
        if (k.length === 1 || ['space', 'escape'].includes(k)) {
            console.log(`[hotkey] 🐦 canary window capture: key=${JSON.stringify(k)} docFocused=${document.hasFocus()} activeEl=${document.activeElement?.tagName}`);
        }
    }

    // Focus diagnostics: log when the parent window loses/gains focus
    // If blur fires before hotkeys stop → YouTube iframe or other element is stealing focus
    function onWindowBlur() {
        console.log('[hotkey] ⚠️ WINDOW BLUR — foco perdido, activeEl:', document.activeElement?.tagName, document.activeElement?.id || '');
    }
    function onWindowFocus() {
        console.log('[hotkey] ✅ WINDOW FOCUS — foco recuperado');
    }

    onMounted(() => {
        console.log('[hotkey] ✅ MOUNTED — registrando listeners (capture:true)');
        window.addEventListener('keydown', handleKeyDown, { capture: true });
        window.addEventListener('keydown', canaryListener, { capture: true });
        window.addEventListener('blur', onWindowBlur);
        window.addEventListener('focus', onWindowFocus);
    });

    onUnmounted(() => {
        console.log('[hotkey] ❌ UNMOUNTED — removiendo listeners, shortcuts:', Array.from(shortcuts.keys()));
        window.removeEventListener('keydown', handleKeyDown, { capture: true });
        window.removeEventListener('keydown', canaryListener, { capture: true });
        window.removeEventListener('blur', onWindowBlur);
        window.removeEventListener('focus', onWindowFocus);
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
