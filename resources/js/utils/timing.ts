/**
 * Vanilla TypeScript timing utilities (no lodash, no VueUse)
 */

/** Debounce: ejecuta fn solo después de waitMs sin llamadas nuevas */
export function debounce<T extends (...args: any[]) => void>(
    fn: T,
    waitMs: number,
): T & { cancel: () => void } {
    let timer: ReturnType<typeof setTimeout> | null = null;

    const debounced = ((...args: Parameters<T>) => {
        if (timer !== null) clearTimeout(timer);
        timer = setTimeout(() => {
            timer = null;
            fn(...args);
        }, waitMs);
    }) as T & { cancel: () => void };

    debounced.cancel = () => {
        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }
    };

    return debounced;
}

/** rAF throttle: ejecuta fn máximo una vez por frame del browser */
export function rafThrottle<T extends (...args: any[]) => void>(
    fn: T,
): T & { cancel: () => void } {
    let rafId: number | null = null;
    let lastArgs: Parameters<T> | null = null;

    const throttled = ((...args: Parameters<T>) => {
        lastArgs = args;
        if (rafId === null) {
            rafId = requestAnimationFrame(() => {
                rafId = null;
                if (lastArgs !== null) {
                    fn(...lastArgs);
                    lastArgs = null;
                }
            });
        }
    }) as T & { cancel: () => void };

    throttled.cancel = () => {
        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
        lastArgs = null;
    };

    return throttled;
}
