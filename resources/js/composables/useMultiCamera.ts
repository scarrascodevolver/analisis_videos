import { ref, watch, onUnmounted, type Ref, nextTick } from 'vue';
import type { SlaveVideo } from '@/types/video-player';
import type { useVideoLoader } from '@/composables/useVideoLoader';

interface UseMultiCameraOptions {
    masterVideoRef: Ref<HTMLVideoElement | null>;
    slaveVideos: Ref<SlaveVideo[]>;
    slaveVideoRefs?: Ref<(HTMLVideoElement | null)[]>;
    videoLoader?: ReturnType<typeof useVideoLoader>;
}

export function useMultiCamera(options: UseMultiCameraOptions) {
    const { masterVideoRef, slaveVideos, videoLoader } = options;

    const SYNC_TOLERANCE = 1.0; // seconds (reduced for high speeds)
    const SYNC_THROTTLE = 250; // ms (reduced for high speeds)

    // State
    const slaveVideoElements = ref<Map<number, HTMLVideoElement>>(new Map());
    const lastSyncTimes = ref<Map<number, number>>(new Map());
    const abortController = ref<AbortController | null>(null);
    const isBuffering = ref(false);
    const isSeeking = ref(false);

    // Helper to safely get the slaves Map
    function getSlavesMap(): Map<number, HTMLVideoElement> | null {
        if (!slaveVideoElements.value) return null;
        if (!(slaveVideoElements.value instanceof Map)) return null;
        return slaveVideoElements.value;
    }

    // Helper to safely get slaveVideos array
    function getSafeSlaveVideos(): SlaveVideo[] {
        if (!slaveVideos.value) return [];
        if (!Array.isArray(slaveVideos.value)) return [];
        return slaveVideos.value;
    }

    // Setup slave event listeners for buffering recovery
    function setupSlaveListeners(slaveId: number, slave: HTMLVideoElement) {
        // When slave starts buffering, mark it
        slave.addEventListener('waiting', () => {
            // console.log(`🔄 Slave ${slaveId} buffering...`);
        });

        // When slave finishes buffering, re-sync if needed
        slave.addEventListener('canplay', () => {
            const master = masterVideoRef.value;
            if (!master || master.paused) return;

            // Re-sync if desynchronized during buffering
            const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);
            if (!slaveData) return;

            const offset = Number(slaveData.sync_offset || 0);

            // Check if slave should be active at current master time
            if (offset > 0 && master.currentTime < offset) {
                slave.currentTime = 0;
                slave.pause();
                return;
            }

            const targetTime = master.currentTime - offset;
            if (!isFinite(targetTime) || targetTime < 0) return;

            const timeDiff = Math.abs(slave.currentTime - targetTime);

            if (timeDiff > SYNC_TOLERANCE) {
                // console.log(`🔄 Re-syncing slave ${slaveId} after buffering (diff: ${timeDiff.toFixed(2)}s)`);
                slave.currentTime = targetTime;
                if (!master.paused && slave.paused) {
                    slave.play().catch(() => {});
                }
            }
        });

        // Handle stalled (network issues)
        slave.addEventListener('stalled', () => {
            // console.warn(`⚠️ Slave ${slaveId} stalled (network issue)`);
        });
    }

    // Register a slave video element
    function registerSlaveElement(slaveId: number, element: HTMLVideoElement) {
        slaveVideoElements.value.set(slaveId, element);
        lastSyncTimes.value.set(slaveId, 0);
        setupSlaveListeners(slaveId, element);
    }

    // Sync a specific slave to master
    function syncSlaveToMaster(slaveId: number) {
        const master = masterVideoRef.value;
        const slave = slaveVideoElements.value.get(slaveId);
        const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);

        if (!master || !slave || !slaveData) return;
        if (isNaN(master.duration) || isNaN(slave.duration)) return;

        const offset = Number(slaveData.sync_offset || 0);

        // CORRECTED SYNC LOGIC:
        // If offset > 0: slave started AFTER master (should start when master reaches offset time)
        // If offset < 0: slave started BEFORE master (already playing)
        // Formula: slave.currentTime = master.currentTime - offset

        if (offset > 0 && master.currentTime < offset) {
            // Master hasn't reached the slave's start point yet
            slave.currentTime = 0;
            if (!slave.paused) {
                slave.pause();
            }
            return;
        }

        const targetTime = master.currentTime - offset;
        if (!isFinite(targetTime) || targetTime < 0 || targetTime > slave.duration) {
            // Slave is out of valid range
            if (!slave.paused) {
                slave.pause();
            }
            return;
        }

        const timeDiff = Math.abs(slave.currentTime - targetTime);
        if (timeDiff > SYNC_TOLERANCE) {
            slave.currentTime = targetTime;
            lastSyncTimes.value.set(slaveId, Date.now());
        }

        // Resume slave if master is playing and slave should be playing
        if (!master.paused && slave.paused) {
            slave.play().catch(() => {});
        }
    }

    // Sync all slaves
    function syncAllSlaves() {
        const slaves = getSlavesMap();
        if (!slaves) return;
        slaves.forEach((_, slaveId) => {
            syncSlaveToMaster(slaveId);
        });
    }

    // Sync all slaves and wait for them to be ready to play
    async function syncAllSlavesAndWait(): Promise<void> {
        const master = masterVideoRef.value;
        if (!master || isNaN(master.duration)) return;

        const slaves = getSlavesMap();
        if (!slaves) return;

        const syncPromises: Promise<void>[] = [];

        slaves.forEach((slave, slaveId) => {
            const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);
            if (!slaveData || isNaN(slave.duration)) return;

            const offset = Number(slaveData.sync_offset || 0);

            // Check if slave should be active at current master time
            if (offset > 0 && master.currentTime < offset) {
                // Master hasn't reached slave start point
                slave.currentTime = 0;
                slave.pause();
                return;
            }

            const targetTime = master.currentTime - offset;
            if (!isFinite(targetTime) || targetTime < 0 || targetTime > slave.duration) {
                slave.pause();
                return;
            }

            // Create promise that resolves when slave is ready
            const promise = new Promise<void>((resolve) => {
                // Set the target time
                slave.currentTime = targetTime;
                lastSyncTimes.value.set(slaveId, Date.now());

                // If already ready, resolve immediately
                if (slave.readyState >= 3 && !slave.seeking) {
                    resolve();
                    return;
                }

                // Otherwise, wait for canplay or timeout
                let resolved = false;
                const timeout = setTimeout(() => {
                    if (!resolved) {
                        resolved = true;
                        resolve();
                    }
                }, 2000); // 2 second timeout

                const handleReady = () => {
                    if (!resolved && slave.readyState >= 3) {
                        resolved = true;
                        clearTimeout(timeout);
                        slave.removeEventListener('canplay', handleReady);
                        slave.removeEventListener('canplaythrough', handleReady);
                        resolve();
                    }
                };

                slave.addEventListener('canplay', handleReady);
                slave.addEventListener('canplaythrough', handleReady);
            });

            syncPromises.push(promise);
        });

        await Promise.all(syncPromises);
    }

    // Setup master video event listeners
    function setupMasterListeners() {
        const master = masterVideoRef.value;
        if (!master) return;

        abortController.value?.abort();
        abortController.value = new AbortController();
        const signal = abortController.value.signal;

        // Play event - play all slaves (wait for slaves to be ready first)
        let isFirstPlay = true;
        master.addEventListener('play', async () => {
            if (isNaN(master.duration) || !isFinite(master.currentTime)) return;

            const slaves = getSlavesMap();
            if (!slaves) return;

            // CRITICAL: On first play, wait for all slaves to be ready
            if (isFirstPlay && slaves.size > 0) {
                console.log('🎬 First play - waiting for all slaves to be ready...');
                isFirstPlay = false;

                // Pause master immediately
                master.pause();

                // Wait for all slaves to be ready
                await syncAllSlavesAndWait();

                // Small delay for stability
                await new Promise(resolve => setTimeout(resolve, 200));

                // Resume master after all slaves ready
                console.log('✅ All slaves ready - resuming master playback');
                await master.play().catch(err => {
                    if (err?.name === 'AbortError') return;
                    console.warn('Master play failed after loading:', err);
                });

                // Don't continue - the resumed play will trigger this event again
                return;
            }

            // Normal play: sync all slaves (only play those that should be active)
            const slavesForPlay = getSlavesMap();
            if (slavesForPlay) {
                slavesForPlay.forEach((slave, slaveId) => {
                    if (isNaN(slave.duration)) return;

                    const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);
                    if (!slaveData) return;

                    const offset = Number(slaveData.sync_offset || 0);

                    // Check if slave should be active at current master time
                    if (offset > 0 && master.currentTime < offset) {
                        // Master hasn't reached slave start point yet
                        slave.currentTime = 0;
                        slave.pause();
                        return;
                    }

                    const expectedTime = master.currentTime - offset;
                    if (!isFinite(expectedTime) || expectedTime < 0 || expectedTime > slave.duration) {
                        slave.pause();
                        return;
                    }

                    // Sync time if ready
                    const canSync = !slave.seeking && slave.readyState >= 3;
                    if (canSync) {
                        slave.currentTime = expectedTime;
                    }

                    // Play only if slave should be active
                    slave.play().catch(err => {
                        if (err?.name === 'AbortError') return;
                        console.warn('Slave play failed:', err);
                    });
                });
            }
        }, { signal });

        // Pause event - pause all slaves
        master.addEventListener('pause', () => {
            const slaves = getSlavesMap();
            if (!slaves) return;
            slaves.forEach(slave => {
                slave.pause();
            });
        }, { signal });

        // Waiting event - master is buffering, pause all slaves
        master.addEventListener('waiting', () => {
            const slaves = getSlavesMap();
            if (!slaves) return;

            console.log('⏸️ Master buffering - pausing all slaves to maintain sync');
            isBuffering.value = true;

            slaves.forEach(slave => {
                if (!slave.paused) {
                    slave.pause();
                }
            });
        }, { signal });

        // Playing event - master resumed after buffering, re-sync and play slaves
        master.addEventListener('playing', async () => {
            if (!isBuffering.value) return; // Only handle if we were buffering

            const slaves = getSlavesMap();
            if (!slaves) return;

            console.log('▶️ Master resumed after buffering - re-syncing slaves...');

            // Re-sync all slaves to master time
            await syncAllSlavesAndWait();

            // Resume playback only on slaves that should be active
            slaves.forEach((slave, slaveId) => {
                const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);
                if (!slaveData) return;

                const offset = Number(slaveData.sync_offset || 0);

                // Only play if slave should be active at current master time
                if (offset > 0 && master.currentTime < offset) {
                    slave.pause();
                    return;
                }

                slave.play().catch(err => {
                    if (err?.name === 'AbortError') return;
                    console.warn('Slave play failed after buffering:', err);
                });
            });

            isBuffering.value = false;
            console.log('✅ All slaves resumed and synced after buffering');
        }, { signal });

        // Stalled event - network issues on master, pause slaves
        master.addEventListener('stalled', () => {
            const slaves = getSlavesMap();
            if (!slaves) return;

            console.warn('⚠️ Master stalled (network issue) - pausing slaves');
            slaves.forEach(slave => {
                if (!slave.paused) {
                    slave.pause();
                }
            });
        }, { signal });

        // Seeking event - pause all slaves immediately to prevent race conditions
        master.addEventListener('seeking', () => {
            isSeeking.value = true;
            const slaves = getSlavesMap();
            if (!slaves) return;

            console.log('🔍 Master seeking - pausing all slaves immediately');
            slaves.forEach(slave => {
                if (!slave.paused) {
                    slave.pause();
                }
            });
        }, { signal });

        // Seeked event - sync all slaves immediately with buffering wait
        let seekDebounce: ReturnType<typeof setTimeout> | null = null;
        master.addEventListener('seeked', async () => {
            // Small debounce (25ms) to handle rapid seeks (like dragging scrubber)
            if (seekDebounce) clearTimeout(seekDebounce);
            seekDebounce = setTimeout(async () => {
                console.log('✅ Master seeked - syncing all slaves...');

                // Wait for all slaves to buffer and be ready
                await syncAllSlavesAndWait();

                // Then sync play/pause state
                const slaves = getSlavesMap();
                if (!slaves) {
                    isSeeking.value = false;
                    return;
                }

                if (master.paused) {
                    // Master is paused - ensure all slaves are paused (already paused from 'seeking')
                    slaves.forEach(slave => slave.pause());
                } else {
                    // Master is playing - only play slaves that should be active at current master time
                    slaves.forEach((slave, slaveId) => {
                        const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);
                        if (!slaveData) return;

                        const offset = Number(slaveData.sync_offset || 0);

                        if (offset > 0 && master.currentTime < offset) {
                            // Slave should not be active yet - keep paused
                            slave.pause();
                        } else {
                            // Slave should be active - play it
                            slave.play().catch(() => {});
                        }
                    });
                }

                // Clear seeking flag
                isSeeking.value = false;
                console.log('✅ Seek complete - all slaves synced');
            }, 25); // Reduced from 100ms to 25ms for faster response
        }, { signal });

        // Timeupdate event - periodic sync check (throttled, adaptive for high speeds)
        master.addEventListener('timeupdate', () => {
            // Skip if master is paused or currently seeking (prevents race conditions)
            if (master.paused || isSeeking.value) return;

            const slaves = getSlavesMap();
            if (!slaves) return;

            const now = Date.now();
            const currentRate = master.playbackRate;

            // Adaptive sync: more frequent and tighter tolerance for high speeds
            const adaptiveTolerance = currentRate > 2 ? 0.5 : SYNC_TOLERANCE;
            const adaptiveThrottle = currentRate > 2 ? 100 : SYNC_THROTTLE; // 100ms for high speeds

            slaves.forEach((slave, slaveId) => {
                const lastSync = lastSyncTimes.value.get(slaveId) || 0;
                if (now - lastSync < adaptiveThrottle) return;

                const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);
                if (!slaveData) return;

                const offset = Number(slaveData.sync_offset || 0);

                // Check if slave should be active at current master time
                if (offset > 0 && master.currentTime < offset) {
                    // Master hasn't reached slave start point yet
                    if (!slave.paused) {
                        slave.pause();
                    }
                    slave.currentTime = 0;
                    return;
                }

                // Check if slave should start playing (just crossed the offset threshold)
                if (offset > 0 && slave.paused && master.currentTime >= offset) {
                    // Slave should start playing now
                    slave.currentTime = 0;
                    slave.play().catch(() => {});
                    lastSyncTimes.value.set(slaveId, now);
                    return;
                }

                if (slave.paused || slave.seeking || slave.readyState < 3) return;

                const targetTime = master.currentTime - offset;
                if (!isFinite(targetTime) || targetTime < 0 || targetTime > slave.duration) {
                    slave.pause();
                    return;
                }

                const timeDiff = Math.abs(slave.currentTime - targetTime);
                if (timeDiff > adaptiveTolerance) {
                    slave.currentTime = targetTime;
                    lastSyncTimes.value.set(slaveId, now);
                }
            });
        }, { signal });

        // Ratechange event - sync playback rate with improved synchronization
        master.addEventListener('ratechange', async () => {
            const slaves = getSlavesMap();
            if (!slaves || slaves.size === 0) return;

            const newRate = master.playbackRate;
            const wasPlaying = !master.paused;

            console.log(`🎬 Playback rate changed to ${newRate}x - syncing ${slaves.size} slaves...`);

            // If changing to high speed (>2x), use improved sync
            if (newRate > 2) {
                // Pause master temporarily
                if (wasPlaying) {
                    master.pause();
                }

                // Apply new rate to all slaves and wait for them to be ready
                const syncPromises: Promise<void>[] = [];

                slaves.forEach((slave, slaveId) => {
                    slave.playbackRate = newRate;

                    // Wait for slave to adjust (especially important for high speeds)
                    const promise = new Promise<void>((resolve) => {
                        if (slave.readyState >= 3) {
                            resolve();
                            return;
                        }

                        const timeout = setTimeout(() => resolve(), 500);
                        const onReady = () => {
                            clearTimeout(timeout);
                            slave.removeEventListener('canplay', onReady);
                            resolve();
                        };
                        slave.addEventListener('canplay', onReady, { once: true });
                    });

                    syncPromises.push(promise);
                });

                // Wait for all slaves to be ready
                await Promise.all(syncPromises);

                // Sync time precisely before resuming
                await syncAllSlavesAndWait();

                // Resume playback if it was playing
                if (wasPlaying) {
                    await master.play().catch(() => {});
                    slaves.forEach(slave => {
                        slave.play().catch(() => {});
                    });
                }

                console.log(`✅ Playback rate ${newRate}x applied and synced`);
            } else {
                // For normal speeds (<=2x), just apply immediately
                slaves.forEach(slave => {
                    slave.playbackRate = newRate;
                });
            }
        }, { signal });
    }

    // Get sync status for a slave
    function getSyncStatus(slaveId: number): 'synced' | 'syncing' | 'out-of-sync' {
        const master = masterVideoRef.value;
        const slave = slaveVideoElements.value.get(slaveId);
        const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);

        if (!master || !slave || !slaveData) return 'out-of-sync';
        if (isNaN(master.duration) || isNaN(slave.duration)) return 'out-of-sync';

        const now = Date.now();
        const lastSync = lastSyncTimes.value.get(slaveId) || 0;
        if (now - lastSync < 300) return 'syncing';

        const targetTime = master.currentTime + (slaveData.sync_offset || 0);
        const timeDiff = Math.abs(slave.currentTime - targetTime);

        return timeDiff <= SYNC_TOLERANCE ? 'synced' : 'out-of-sync';
    }

    // Adjust sync offset for a slave
    function adjustSyncOffset(slaveId: number, newOffset: number) {
        const safeSlaves = getSafeSlaveVideos();
        const slaveIndex = safeSlaves.findIndex(s => s.id === slaveId);
        if (slaveIndex !== -1 && Array.isArray(slaveVideos.value)) {
            slaveVideos.value[slaveIndex].sync_offset = newOffset;
            // Don't auto-sync/play when adjusting offset from timeline drag
            // Sync will happen naturally during playback via timeupdate event
        }
    }

    // Swap master with slave (client-side only)
    function swapMaster(slaveId: number): boolean {
        const master = masterVideoRef.value;
        const slave = slaveVideoElements.value.get(slaveId);
        if (!master || !slave) return false;

        // Save states
        const masterTime = master.currentTime;
        const masterPaused = master.paused;
        const masterVolume = master.volume;
        const masterRate = master.playbackRate;

        const slaveTime = slave.currentTime;
        const slavePaused = slave.paused;

        // Swap sources
        const masterSource = master.querySelector('source');
        const masterUrl = masterSource ? masterSource.src : master.src;
        const slaveUrl = slave.src;

        if (masterSource) {
            masterSource.src = slaveUrl;
        } else {
            master.src = slaveUrl;
        }
        slave.src = masterUrl;

        // Reload both
        master.load();
        slave.load();

        // Apply crossed states after load
        const restoreStates = () => {
            master.currentTime = slaveTime;
            slave.currentTime = masterTime;
            master.volume = masterVolume;
            master.playbackRate = masterRate;

            if (!slavePaused) {
                master.play();
            }
            if (!masterPaused) {
                slave.play();
            }
        };

        master.addEventListener('loadedmetadata', restoreStates, { once: true });

        return true;
    }

    // Cleanup
    function cleanup() {
        abortController.value?.abort();
        slaveVideoElements.value.clear();
        lastSyncTimes.value.clear();
    }

    // Watch for master video changes
    watch(() => masterVideoRef.value, (newMaster) => {
        if (newMaster) {
            setupMasterListeners();
        }
    }, { immediate: true });

    onUnmounted(() => {
        cleanup();
    });

    // Get current time of a specific slave
    function getSlaveCurrentTime(slaveId: number): number {
        const slave = slaveVideoElements.value.get(slaveId);
        return slave?.currentTime || 0;
    }

    return {
        registerSlaveElement,
        getSyncStatus,
        swapMaster,
        adjustSyncOffset,
        syncAllSlaves,
        syncAllSlavesAndWait,
        getSlaveCurrentTime,
    };
}
