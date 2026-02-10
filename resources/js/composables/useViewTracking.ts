import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useVideoApi } from '@/composables/useVideoApi';
import { useTimeManager } from './useTimeManager';

export function useViewTracking(videoId: number) {
    const videoStore = useVideoStore();
    const api = useVideoApi(videoId);
    const timeManager = useTimeManager();

    const viewTracked = ref(false);
    const completionTracked = ref(false);
    const currentViewId = ref<number | null>(null);
    let durationInterval: ReturnType<typeof setInterval> | null = null;

    const VIEW_THRESHOLD_SECONDS = 20;
    const COMPLETION_THRESHOLD_PERCENT = 0.9;
    const DURATION_UPDATE_INTERVAL_MS = 10000;

    function start() {
        timeManager.registerCallback('viewTracking', onTimeUpdate);
    }

    function onTimeUpdate(currentTime: number, duration: number) {
        // Track view at 20 seconds
        if (!viewTracked.value && currentTime >= VIEW_THRESHOLD_SECONDS) {
            trackView();
        }

        // Track completion at 90%
        if (!completionTracked.value && duration > 0) {
            if (currentTime / duration >= COMPLETION_THRESHOLD_PERCENT) {
                markCompleted();
            }
        }
    }

    async function trackView() {
        if (viewTracked.value) return;
        viewTracked.value = true;
        try {
            const result = await api.trackView();
            if (result.success && !result.cooldown && result.view_id) {
                currentViewId.value = result.view_id;
                startDurationTracking();
            } else if (result.cooldown) {
                console.log('View within cooldown period');
            }
        } catch {
            viewTracked.value = false;
        }
    }

    function startDurationTracking() {
        if (durationInterval) return;
        durationInterval = setInterval(() => {
            if (!currentViewId.value) return;
            if (!videoStore.isPlaying) return;

            // Validate currentTime before sending
            const currentTime = videoStore.currentTime;

            // Extra strict validation
            if (typeof currentTime !== 'number') return;
            if (!isFinite(currentTime)) return;
            if (currentTime < 0) return;
            if (Number.isNaN(currentTime)) return;

            // Send as integer (Laravel expects integer, not float)
            api.updateDuration(currentViewId.value, currentTime).catch(() => {});
        }, DURATION_UPDATE_INTERVAL_MS);
    }

    async function markCompleted() {
        if (completionTracked.value) return;
        if (!currentViewId.value) return;
        completionTracked.value = true;
        try {
            await api.markCompleted(currentViewId.value);
        } catch {
            completionTracked.value = false;
        }
    }

    function cleanup() {
        if (durationInterval) {
            clearInterval(durationInterval);
            durationInterval = null;
        }
        timeManager.unregisterCallback('viewTracking');
    }

    onBeforeUnmount(cleanup);

    return {
        start,
        viewTracked,
        completionTracked,
    };
}
