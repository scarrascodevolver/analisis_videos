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
    const slaveYtPlayers = ref<Map<number, any>>(new Map()); // slaveId -> YT.Player
    const lastSyncTimes = ref<Map<number, number>>(new Map());
    const abortController = ref<AbortController | null>(null);
    const isBuffering = ref(false);
    const isSeeking = ref(false);
    // Tracks whether the next "play" event should wait for all slaves to be ready.
    // Reset to true after every master swap so the new set of slaves gets the
    // same "wait for ready" treatment the very first play always got.
    const isFirstPlay = ref(true);

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

    // ── YouTube player registration ───────────────────────────────────────────

    function registerSlaveYtPlayer(slaveId: number, ytPlayer: any) {
        slaveYtPlayers.value.set(slaveId, ytPlayer);
        lastSyncTimes.value.set(slaveId, 0);
    }

    function unregisterSlaveYtPlayer(slaveId: number) {
        slaveYtPlayers.value.delete(slaveId);
        lastSyncTimes.value.delete(slaveId);
    }

    /**
     * Sync a single YT slave to the master's current time.
     * isPlaying indicates whether master is playing (not paused).
     */
    function syncYtSlaveToMaster(slaveId: number, masterCurrentTime: number, isPlaying: boolean) {
        const ytPlayer = slaveYtPlayers.value.get(slaveId);
        if (!ytPlayer) return;

        const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);
        if (!slaveData) return;

        const offset = Number(slaveData.sync_offset || 0);

        // If master hasn't reached this slave's start point yet, keep it at 0/paused
        if (offset > 0 && masterCurrentTime < offset) {
            try {
                ytPlayer.seekTo(0, true);
                ytPlayer.pauseVideo();
            } catch (_) { /* player may not be ready */ }
            return;
        }

        const targetTime = Math.max(0, masterCurrentTime - offset);

        try {
            ytPlayer.seekTo(targetTime, true);
            if (isPlaying) {
                ytPlayer.playVideo();
            } else {
                ytPlayer.pauseVideo();
            }
            lastSyncTimes.value.set(slaveId, Date.now());
        } catch (_) { /* YT player can fail if not ready yet */ }
    }

    /** Sync all registered YT slave players. */
    function syncAllYtSlaves(masterCurrentTime: number, isPlaying: boolean) {
        slaveYtPlayers.value.forEach((_, slaveId) => {
            syncYtSlaveToMaster(slaveId, masterCurrentTime, isPlaying);
        });
    }

    // ── HTML video slave helpers ──────────────────────────────────────────────

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

    // Unregister a slave video element (called on SlaveVideo unmount)
    function unregisterSlaveElement(slaveId: number) {
        slaveVideoElements.value.delete(slaveId);
        lastSyncTimes.value.delete(slaveId);
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
        master.addEventListener('play', async () => {
            if (isNaN(master.duration) || !isFinite(master.currentTime)) return;

            const slaves = getSlavesMap();
            if (!slaves) return;

            // CRITICAL: On first play after mount or after a master swap,
            // wait for all slaves to be ready before starting playback.
            if (isFirstPlay.value && slaves.size > 0) {
                console.log('First play - waiting for all slaves to be ready...');
                isFirstPlay.value = false;

                // Pause master immediately
                master.pause();

                // Wait for all HTML slaves to be ready
                await syncAllSlavesAndWait();

                // Seek YT slaves to the correct position (best-effort; they will start playing after master resumes)
                syncAllYtSlaves(master.currentTime, false);

                // Small delay for stability
                await new Promise(resolve => setTimeout(resolve, 200));

                // Resume master after all slaves ready
                console.log('All slaves ready - resuming master playback');
                await master.play().catch(err => {
                    if (err?.name === 'AbortError') return;
                    console.warn('Master play failed after loading:', err);
                });

                // Don't continue - the resumed play will trigger this event again
                return;
            }

            // Normal play: sync all HTML slaves (only play those that should be active)
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

            // Play YT slaves
            syncAllYtSlaves(master.currentTime, true);
        }, { signal });

        // Pause event - pause all slaves
        master.addEventListener('pause', () => {
            const slaves = getSlavesMap();
            if (!slaves) return;
            slaves.forEach(slave => {
                slave.pause();
            });

            // Pause YT slaves
            slaveYtPlayers.value.forEach((ytPlayer) => {
                try { ytPlayer.pauseVideo(); } catch (_) {}
            });
        }, { signal });

        // Waiting event - master is buffering, pause all slaves
        master.addEventListener('waiting', () => {
            const slaves = getSlavesMap();
            if (!slaves) return;

            console.log('Master buffering - pausing all slaves to maintain sync');
            isBuffering.value = true;

            slaves.forEach(slave => {
                if (!slave.paused) {
                    slave.pause();
                }
            });

            // Pause YT slaves while master buffers
            slaveYtPlayers.value.forEach((ytPlayer) => {
                try { ytPlayer.pauseVideo(); } catch (_) {}
            });
        }, { signal });

        // Playing event - master resumed after buffering, re-sync and play slaves
        master.addEventListener('playing', async () => {
            if (!isBuffering.value) return; // Only handle if we were buffering

            const slaves = getSlavesMap();
            if (!slaves) return;

            console.log('Master resumed after buffering - re-syncing slaves...');

            // Re-sync all HTML slaves to master time
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

            // Re-sync YT slaves after buffering
            syncAllYtSlaves(master.currentTime, true);

            isBuffering.value = false;
            console.log('All slaves resumed and synced after buffering');
        }, { signal });

        // Stalled event - network issues on master, pause slaves
        master.addEventListener('stalled', () => {
            const slaves = getSlavesMap();
            if (!slaves) return;

            console.warn('Master stalled (network issue) - pausing slaves');
            slaves.forEach(slave => {
                if (!slave.paused) {
                    slave.pause();
                }
            });

            // Pause YT slaves on stall
            slaveYtPlayers.value.forEach((ytPlayer) => {
                try { ytPlayer.pauseVideo(); } catch (_) {}
            });
        }, { signal });

        // Seeking event - pause all slaves immediately to prevent race conditions
        master.addEventListener('seeking', () => {
            isSeeking.value = true;
            const slaves = getSlavesMap();
            if (!slaves) return;

            console.log('Master seeking - pausing all slaves immediately');
            slaves.forEach(slave => {
                if (!slave.paused) {
                    slave.pause();
                }
            });

            // Pause YT slaves during seek
            slaveYtPlayers.value.forEach((ytPlayer) => {
                try { ytPlayer.pauseVideo(); } catch (_) {}
            });
        }, { signal });

        // Seeked event - sync all slaves immediately with buffering wait
        let seekDebounce: ReturnType<typeof setTimeout> | null = null;
        master.addEventListener('seeked', async () => {
            // Small debounce (25ms) to handle rapid seeks (like dragging scrubber)
            if (seekDebounce) clearTimeout(seekDebounce);
            seekDebounce = setTimeout(async () => {
                console.log('Master seeked - syncing all slaves...');

                // Wait for all HTML slaves to buffer and be ready
                await syncAllSlavesAndWait();

                // Seek YT slaves
                syncAllYtSlaves(master.currentTime, !master.paused);

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
                console.log('Seek complete - all slaves synced');
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

            // Sync HTML slaves
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

            // Sync YT slaves (throttled at adaptive rate)
            slaveYtPlayers.value.forEach((_, slaveId) => {
                const lastSync = lastSyncTimes.value.get(slaveId) || 0;
                if (now - lastSync < adaptiveThrottle) return;

                const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);
                if (!slaveData) return;

                const offset = Number(slaveData.sync_offset || 0);
                const targetTime = Math.max(0, master.currentTime - offset);

                syncYtSlaveToMaster(slaveId, master.currentTime, !master.paused);
            });
        }, { signal });

        // Ratechange event - sync playback rate with improved synchronization
        master.addEventListener('ratechange', async () => {
            const slaves = getSlavesMap();
            if (!slaves || slaves.size === 0) return;

            const newRate = master.playbackRate;
            const wasPlaying = !master.paused;

            console.log(`Playback rate changed to ${newRate}x - syncing ${slaves.size} slaves...`);

            // If changing to high speed (>2x), use improved sync
            if (newRate > 2) {
                // Pause master temporarily
                if (wasPlaying) {
                    master.pause();
                }

                // Apply new rate to all HTML slaves and wait for them to be ready
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

                // Wait for all HTML slaves to be ready
                await Promise.all(syncPromises);

                // Sync time precisely before resuming
                await syncAllSlavesAndWait();

                // Resume playback if it was playing
                if (wasPlaying) {
                    await master.play().catch(() => {});
                    slaves.forEach(slave => {
                        slave.play().catch(() => {});
                    });
                    // YT slaves don't support arbitrary playback rates; just re-sync position
                    syncAllYtSlaves(master.currentTime, true);
                }

                console.log(`Playback rate ${newRate}x applied and synced`);
            } else {
                // For normal speeds (<=2x), just apply immediately
                slaves.forEach(slave => {
                    slave.playbackRate = newRate;
                });
                // YT slaves: re-sync position only (no playback rate control via API)
                if (wasPlaying) {
                    syncAllYtSlaves(master.currentTime, true);
                }
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

    // Swap master with slave — now handled entirely by data mutation in Show.vue.
    // Show.vue's onSwapMaster() updates the reactive slaveVideos array and master
    // URL refs (videoStreamUrl, videoHlsUrl, videoMp4Url) so Vue re-renders with
    // the correct URLs without any DOM manipulation or video.load() calls.
    // This stub is kept for API compatibility but performs no DOM operations.
    function swapMaster(_slaveId: number): boolean {
        return true;
    }

    // Called by Show.vue after every master swap so the next play event
    // waits for the new set of slaves to be ready (same as initial load).
    function resetForNewMaster() {
        isFirstPlay.value = true;
    }

    /**
     * Called by Show.vue when master is a YouTube video.
     * Bridges videoStore state changes → YT slave sync.
     * (masterVideoRef is null for YT masters so setupMasterListeners never runs.)
     *
     * Smart sync: only seekTo on state change or significant drift (>2s).
     * Avoids interrupting buffer every 250ms which causes infinite loading.
     */
    let _lastYtMasterPlaying: boolean | null = null;

    function onYtMasterUpdate(currentTime: number, isPlaying: boolean) {
        const playStateChanged = isPlaying !== _lastYtMasterPlaying;
        _lastYtMasterPlaying = isPlaying;

        slaveYtPlayers.value.forEach((ytPlayer, slaveId) => {
            const slaveData = getSafeSlaveVideos().find(s => s.id === slaveId);
            if (!slaveData) return;

            const offset = Number(slaveData.sync_offset || 0);
            const targetTime = Math.max(0, currentTime - offset);

            try {
                if (playStateChanged) {
                    // Estado cambió: seek + play/pause inmediato
                    ytPlayer.seekTo(targetTime, true);
                    if (isPlaying) ytPlayer.playVideo();
                    else ytPlayer.pauseVideo();
                } else if (isPlaying) {
                    // Ya está reproduciendo: solo corregir si hay deriva significativa
                    const slaveTime = ytPlayer.getCurrentTime?.() ?? 0;
                    if (Math.abs(slaveTime - targetTime) > 2.0) {
                        ytPlayer.seekTo(targetTime, true);
                    }
                }
            } catch (_) { /* player not ready yet */ }
        });
    }

    // Cleanup
    function cleanup() {
        abortController.value?.abort();
        slaveVideoElements.value.clear();
        slaveYtPlayers.value.clear();
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
        unregisterSlaveElement,
        registerSlaveYtPlayer,
        unregisterSlaveYtPlayer,
        resetForNewMaster,
        onYtMasterUpdate,
        getSyncStatus,
        swapMaster,
        adjustSyncOffset,
        syncAllSlaves,
        syncAllSlavesAndWait,
        getSlaveCurrentTime,
    };
}
