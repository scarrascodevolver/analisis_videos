<script setup lang="ts">
import { ref } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { PLAYBACK_SPEEDS, type PlaybackSpeed } from '@/types/video-player';

const videoStore = useVideoStore();
const isOpen = ref(false);

function selectSpeed(speed: PlaybackSpeed) {
    videoStore.setPlaybackRate(speed);
    // If the browser capped the rate, reflect the actual value
    const applied = videoStore.videoRef?.playbackRate ?? speed;
    if (applied !== speed) {
        videoStore.setPlaybackRate(applied as PlaybackSpeed);
    }
    isOpen.value = false;
}

function toggle() {
    isOpen.value = !isOpen.value;
}

// Close on outside click
function onClickOutside() {
    isOpen.value = false;
}
</script>

<template>
    <div class="speed-control" v-click-outside="onClickOutside">
        <button
            class="video-utility-btn"
            title="Velocidad de reproducción"
            @click="toggle"
        >
            <i class="fas fa-tachometer-alt"></i>
            <span class="current-speed">{{ (videoStore.playbackRate || 1).toFixed(2).replace(/\.?0+$/, '') }}x</span>
        </button>
        <Transition name="fade">
            <div v-if="isOpen" class="speed-menu">
                <div class="speed-menu-title">Velocidad</div>
                <button
                    v-for="speed in PLAYBACK_SPEEDS"
                    :key="speed"
                    class="speed-option"
                    :class="{ active: videoStore.playbackRate === speed }"
                    @click="selectSpeed(speed)"
                >
                    {{ speed }}x
                </button>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.speed-control {
    position: relative;
}

.video-utility-btn {
    background: rgba(0, 0, 0, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s;
}

.video-utility-btn:hover {
    background: rgba(0, 84, 97, 0.8);
}

.current-speed {
    font-weight: bold;
    font-size: 12px;
}

.speed-menu {
    position: absolute;
    bottom: 100%;
    right: 0;
    background: rgba(0, 0, 0, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 6px 0;
    min-width: 110px;
    max-height: 240px;
    overflow-y: auto;
    margin-bottom: 6px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
}

.speed-menu-title {
    color: #888;
    font-size: 10px;
    text-transform: uppercase;
    padding: 4px 10px 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 2px;
}

.speed-option {
    display: block;
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    color: #ccc;
    padding: 5px 10px;
    cursor: pointer;
    font-size: 12px;
}

.speed-option:hover {
    background: rgba(255, 195, 0, 0.2);
    color: #fff;
}

.speed-option.active {
    color: var(--color-accent);
    font-weight: bold;
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.15s;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
