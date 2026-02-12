<script setup lang="ts">
import { computed } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { formatTime } from '@/stores/videoStore';
import type { VideoClip } from '@/types/video-player';

const props = defineProps<{
    clip: VideoClip;
}>();

const videoStore = useVideoStore();

const formattedStartTime = computed(() => formatTime(props.clip.start_time));
const formattedEndTime = computed(() => formatTime(props.clip.end_time));

function handleSeek() {
    console.log('Seeking to clip from sidebar:', props.clip.start_time);
    videoStore.seek(props.clip.start_time);
    // Always play after seeking to clip
    videoStore.play();
}
</script>

<template>
    <div class="clip-item" @click="handleSeek">
        <i class="fas fa-play-circle clip-icon"></i>
        <div class="clip-time">
            {{ formattedStartTime }} - {{ formattedEndTime }}
        </div>
    </div>
</template>

<style scoped>
.clip-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.5rem;
    margin-bottom: 0.25rem;
    background-color: #252525;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.15s;
}

.clip-item:hover {
    background-color: #2a2a2a;
    transform: translateX(3px);
}

.clip-icon {
    color: #00B7B5;
    font-size: 12px;
    flex-shrink: 0;
}

.clip-time {
    color: #ccc;
    font-size: 10px;
    font-weight: 500;
    white-space: nowrap;
}
</style>
