<script setup lang="ts">
import { computed } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useClipsStore } from '@/stores/clipsStore';
import { formatTime } from '@/stores/videoStore';
import type { VideoClip } from '@/types/video-player';

const props = defineProps<{
    clip: VideoClip;
}>();

const emit = defineEmits<{
    edit: [clipId: number];
}>();

const videoStore = useVideoStore();
const clipsStore = useClipsStore();

const duration = computed(() => {
    const seconds = props.clip.end_time - props.clip.start_time;
    return formatTime(seconds);
});

const formattedStartTime = computed(() => formatTime(props.clip.start_time));
const formattedEndTime = computed(() => formatTime(props.clip.end_time));

const displayTitle = computed(() => {
    return props.clip.title || `Clip #${props.clip.id}`;
});

function handleSeek() {
    console.log('Seeking to clip from sidebar:', props.clip.start_time);
    videoStore.seek(props.clip.start_time);
    // Always play after seeking to clip
    videoStore.play();
}

async function handleDelete() {
    if (!videoStore.video) return;

    const confirmed = confirm('¿Estás seguro de que quieres eliminar este clip?');
    if (!confirmed) return;

    try {
        await clipsStore.removeClip(videoStore.video.id, props.clip.id);
    } catch (error) {
        console.error('Error deleting clip:', error);
        alert('Error al eliminar el clip');
    }
}

function handleEdit() {
    emit('edit', props.clip.id);
}
</script>

<template>
    <div class="clip-item" @click="handleSeek">
        <div class="clip-main">
            <div class="clip-info">
                <div class="clip-title">{{ displayTitle }}</div>
                <div class="clip-time">
                    <i class="fas fa-clock mr-1"></i>
                    {{ formattedStartTime }} - {{ formattedEndTime }}
                    <span class="clip-duration">({{ duration }})</span>
                </div>
                <div v-if="clip.notes" class="clip-notes">
                    {{ clip.notes }}
                </div>
            </div>

            <div class="clip-actions" @click.stop>
                <button
                    class="btn-icon"
                    title="Editar clip"
                    @click="handleEdit"
                >
                    <i class="fas fa-edit"></i>
                </button>
                <button
                    class="btn-icon btn-danger"
                    title="Eliminar clip"
                    @click="handleDelete"
                >
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>

        <div v-if="clip.tags && clip.tags.length > 0" class="clip-tags">
            <span v-for="(tag, index) in clip.tags" :key="index" class="clip-tag">
                {{ tag }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.clip-item {
    padding: 7px;
    margin-bottom: 0.4rem;
    background-color: #252525;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.clip-item:hover {
    background-color: #2a2a2a;
    transform: translateX(4px);
}

.clip-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
}

.clip-info {
    flex: 1;
    min-width: 0;
}

.clip-title {
    color: #fff;
    font-weight: 600;
    font-size: 11px;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.clip-time {
    color: #00B7B5;
    font-size: 10px;
    margin-bottom: 0.2rem;
}

.clip-time i {
    font-size: 0.75rem;
}

.clip-duration {
    color: #999;
    margin-left: 0.25rem;
}

.clip-notes {
    color: #999;
    font-size: 10px;
    margin-top: 0.35rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.clip-actions {
    display: flex;
    gap: 0.2rem;
    flex-shrink: 0;
}

.btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn-icon:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.btn-icon.btn-danger:hover {
    background-color: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.clip-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.35rem;
}

.clip-tag {
    padding: 0.25rem 0.5rem;
    background-color: rgba(0, 183, 181, 0.15);
    border: 1px solid rgba(0, 183, 181, 0.3);
    border-radius: 3px;
    color: #00B7B5;
    font-size: 0.7rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .clip-item {
        padding: 0.5rem;
    }

    .clip-main {
        flex-direction: column;
    }

    .clip-actions {
        align-self: flex-end;
        margin-top: 0.5rem;
    }
}
</style>
