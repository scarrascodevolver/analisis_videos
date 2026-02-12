import { ref, onBeforeUnmount, watch } from 'vue';
import { useVideoStore } from '@/stores/videoStore';

type TimeUpdateCallback = (currentTime: number, duration: number) => void;

export function useTimeManager() {
    const videoStore = useVideoStore();
    const callbacks = ref<Map<string, TimeUpdateCallback>>(new Map());
    let lastUpdateSecond = -1;

    // Throttled time update - runs callbacks max 1/sec
    function onThrottledTimeUpdate() {
        const currentSecond = Math.floor(videoStore.currentTime);
        if (currentSecond === lastUpdateSecond) return;
        lastUpdateSecond = currentSecond;

        for (const cb of callbacks.value.values()) {
            cb(videoStore.currentTime, videoStore.duration);
        }
    }

    // Watch currentTime changes (from videoStore)
    const stopWatch = watch(
        () => videoStore.currentTime,
        () => onThrottledTimeUpdate(),
    );

    function registerCallback(name: string, callback: TimeUpdateCallback) {
        callbacks.value.set(name, callback);
    }

    function unregisterCallback(name: string) {
        callbacks.value.delete(name);
    }

    onBeforeUnmount(() => {
        stopWatch();
        callbacks.value.clear();
    });

    return {
        registerCallback,
        unregisterCallback,
    };
}
