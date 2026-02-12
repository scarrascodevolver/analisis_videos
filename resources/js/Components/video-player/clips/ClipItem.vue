<script setup lang="ts">
import { computed, inject } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useClipsStore } from '@/stores/clipsStore';
import { formatTime } from '@/stores/videoStore';
import type { VideoClip } from '@/types/video-player';

const props = defineProps<{
    clip: VideoClip;
}>();

const videoStore = useVideoStore();
const clipsStore = useClipsStore();
const toast = inject<any>('toast');

const formattedStartTime = computed(() => formatTime(props.clip.start_time));
const formattedEndTime = computed(() => formatTime(props.clip.end_time));

function handleSeek() {
    console.log('Seeking to clip from sidebar:', props.clip.start_time);
    videoStore.seek(props.clip.start_time);
    // Always play after seeking to clip
    videoStore.play();
}

async function handleDelete(event: MouseEvent) {
    event.stopPropagation(); // Prevent seek when clicking delete

    if (!confirm('¿Estás seguro de que deseas eliminar este clip?')) {
        return;
    }

    try {
        await clipsStore.deleteClip(props.clip.video_id, props.clip.id);
        toast?.success('Clip eliminado');
    } catch (error) {
        console.error('Error deleting clip:', error);
        toast?.error('Error al eliminar el clip');
    }
}
</script>

<template>
    <div class="clip-item" @click="handleSeek">
        <i class="fas fa-play-circle clip-icon"></i>
        <div class="clip-time">
            {{ formattedStartTime }} - {{ formattedEndTime }}
        </div>
        <button
            class="btn-delete-clip"
            title="Eliminar clip"
            @click="handleDelete"
        >
            <i class="fas fa-trash"></i>
        </button>
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
    flex: 1;
}

.btn-delete-clip {
    background: transparent;
    border: none;
    color: #888;
    padding: 0.2rem 0.4rem;
    cursor: pointer;
    font-size: 10px;
    border-radius: 3px;
    transition: all 0.2s;
    opacity: 0;
    margin-left: auto;
}

.clip-item:hover .btn-delete-clip {
    opacity: 1;
}

.btn-delete-clip:hover {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    transform: scale(1.1);
}
</style>
