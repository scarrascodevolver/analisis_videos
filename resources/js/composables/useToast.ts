import { ref } from 'vue';
import type { Toast } from '@/types/video-player';

const toasts = ref<Toast[]>([]);
let nextId = 0;

export function useToast() {
    function addToast(message: string, type: Toast['type'] = 'info', duration = 3000) {
        const id = nextId++;
        toasts.value.push({ id, message, type, duration });

        if (duration > 0) {
            setTimeout(() => removeToast(id), duration);
        }
    }

    function removeToast(id: number) {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    }

    function success(message: string, duration?: number) {
        addToast(message, 'success', duration);
    }

    function error(message: string, duration?: number) {
        addToast(message, 'error', duration ?? 5000);
    }

    function warning(message: string, duration?: number) {
        addToast(message, 'warning', duration);
    }

    function info(message: string, duration?: number) {
        addToast(message, 'info', duration);
    }

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        warning,
        info,
    };
}
