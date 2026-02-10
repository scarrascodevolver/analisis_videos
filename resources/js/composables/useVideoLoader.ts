import { ref, type Ref } from 'vue';

interface VideoLoaderOptions {
    masterVideoRef: Ref<HTMLVideoElement | null>;
    getSlaveElements: () => HTMLVideoElement[];
    timeout?: number; // Default 15000ms (15s)
}

export function useVideoLoader(options: VideoLoaderOptions) {
    const { masterVideoRef, getSlaveElements, timeout = 15000 } = options;

    const isLoading = ref(false);
    const loadingProgress = ref(0);
    const loadedVideos = ref(0);
    const totalVideos = ref(0);
    const failedVideos = ref<string[]>([]);
    const hasInitialPlayHappened = ref(false);

    /**
     * Wait for a single video to be ready (readyState >= 3)
     */
    function waitForVideoReady(video: HTMLVideoElement, label: string): Promise<boolean> {
        return new Promise((resolve) => {
            // Already ready?
            if (video.readyState >= 3) {
                console.log(`✅ ${label} already ready`);
                resolve(true);
                return;
            }

            console.log(`⏳ Waiting for ${label}...`);

            let resolved = false;
            const timeoutId = setTimeout(() => {
                if (!resolved) {
                    console.warn(`⚠️ ${label} timeout after ${timeout}ms`);
                    resolved = true;
                    cleanup();
                    resolve(false);
                }
            }, timeout);

            const handleReady = () => {
                if (!resolved && video.readyState >= 3) {
                    console.log(`✅ ${label} ready`);
                    resolved = true;
                    cleanup();
                    resolve(true);
                }
            };

            const handleError = () => {
                if (!resolved) {
                    console.error(`❌ ${label} error loading`);
                    resolved = true;
                    cleanup();
                    resolve(false);
                }
            };

            const cleanup = () => {
                clearTimeout(timeoutId);
                video.removeEventListener('canplay', handleReady);
                video.removeEventListener('canplaythrough', handleReady);
                video.removeEventListener('error', handleError);
                video.removeEventListener('stalled', handleError);
            };

            video.addEventListener('canplay', handleReady);
            video.addEventListener('canplaythrough', handleReady);
            video.addEventListener('error', handleError);
            video.addEventListener('stalled', handleError);

            // Force load if not already loading
            if (video.readyState === 0 || video.networkState === 0) {
                video.load();
            }
        });
    }

    /**
     * Wait for all videos (master + slaves) to be ready
     */
    async function waitForAllVideos(): Promise<void> {
        const master = masterVideoRef.value;
        if (!master) {
            console.warn('⚠️ No master video found');
            return;
        }

        isLoading.value = true;
        loadingProgress.value = 0;
        loadedVideos.value = 0;
        failedVideos.value = [];

        const slaves = getSlaveElements();
        totalVideos.value = 1 + slaves.length;

        console.log(`🎬 Loading ${totalVideos.value} videos (1 master + ${slaves.length} slaves)...`);

        // Create array of all videos to load
        const videoPromises: Promise<boolean>[] = [
            waitForVideoReady(master, 'Master')
        ];

        slaves.forEach((slave, index) => {
            const slaveTitle = slave.getAttribute('data-video-title') || `Slave ${index + 1}`;
            videoPromises.push(waitForVideoReady(slave, slaveTitle));
        });

        // Wait for all videos with progress tracking
        const results = await Promise.allSettled(
            videoPromises.map((promise, index) =>
                promise.then((success) => {
                    loadedVideos.value++;
                    loadingProgress.value = Math.round((loadedVideos.value / totalVideos.value) * 100);

                    if (!success) {
                        const label = index === 0 ? 'Master' : `Slave ${index}`;
                        failedVideos.value.push(label);
                    }

                    return success;
                })
            )
        );

        // Check results
        const allSuccess = results.every(
            (result) => result.status === 'fulfilled' && result.value === true
        );

        if (allSuccess) {
            console.log('✅ All videos loaded successfully');
        } else {
            console.warn(`⚠️ Some videos failed to load: ${failedVideos.value.join(', ')}`);
        }

        // Small delay to ensure everything is stable
        await new Promise(resolve => setTimeout(resolve, 300));

        isLoading.value = false;
        hasInitialPlayHappened.value = true;
    }

    /**
     * Intercept play event to ensure all videos are ready first
     */
    function interceptPlay(playFn: () => void | Promise<void>): () => Promise<void> {
        return async () => {
            // If first play, wait for all videos
            if (!hasInitialPlayHappened.value) {
                await waitForAllVideos();
            }

            // Now execute the actual play
            await playFn();
        };
    }

    /**
     * Reset state (useful for switching videos)
     */
    function reset() {
        isLoading.value = false;
        loadingProgress.value = 0;
        loadedVideos.value = 0;
        totalVideos.value = 0;
        failedVideos.value = [];
        hasInitialPlayHappened.value = false;
    }

    return {
        // State
        isLoading,
        loadingProgress,
        loadedVideos,
        totalVideos,
        failedVideos,
        hasInitialPlayHappened,

        // Methods
        waitForAllVideos,
        interceptPlay,
        reset,
    };
}
