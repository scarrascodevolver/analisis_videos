<script setup lang="ts">
import { computed, inject } from 'vue';
import { useClipsStore } from '@/stores/clipsStore';
import { useVideoStore } from '@/stores/videoStore';
import type { ClipCategory } from '@/types/video-player';

const props = defineProps<{
    category: ClipCategory;
}>();

const clipsStore = useClipsStore();
const videoStore = useVideoStore();
const toast = inject<any>('toast');

const isRecording = computed(() => {
    return clipsStore.isRecording && clipsStore.recordingCategoryId === props.category.id;
});

const clipsCount = computed(() => {
    return clipsStore.clipsByCategory[props.category.id]?.length || 0;
});

async function handleClick() {
    if (!videoStore.video) return;

    try {
        const wasRecording = clipsStore.isRecording && clipsStore.recordingCategoryId === props.category.id;

        // Auto-play video if paused and starting a new recording
        if (!wasRecording && videoStore.isPaused) {
            videoStore.play();
        }

        await clipsStore.toggleRecording(
            videoStore.video.id,
            props.category.id,
            videoStore.currentTime
        );
    } catch (error: any) {
        console.error('Error toggling clip recording:', error);
        toast?.error(error.message || 'Error al crear el clip');
    }
}
</script>

<template>
    <button
        class="clip-category-button"
        :class="{ 'is-recording': isRecording }"
        :style="{ '--category-color': category.color }"
        @click="handleClick"
    >
        <div class="category-content">
            <i v-if="category.icon" :class="category.icon" class="category-icon"></i>
            <span class="category-name">{{ category.name }}</span>
        </div>

        <div class="category-footer">
            <span v-if="category.hotkey" class="hotkey-badge">{{ category.hotkey.toUpperCase() }}</span>
            <span v-if="clipsCount > 0" class="clips-count">{{ clipsCount }}</span>
        </div>

        <div v-if="isRecording" class="recording-indicator">
            <i class="fas fa-circle pulse"></i>
            <span>REC</span>
        </div>

        <!-- Indicador de categoría personal (solo yo la veo) -->
        <div
            v-if="!isRecording && category.scope === 'user'"
            class="personal-indicator"
            title="Categoría personal — solo vos la ves"
        >
            <i class="fas fa-user"></i>
        </div>
    </button>
</template>

<style scoped>
.clip-category-button {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 0.5rem;
    min-height: 65px;
    background-color: #252525;
    border: 2px solid var(--category-color, #444);
    border-radius: 6px;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
    overflow: hidden;
}

.clip-category-button:hover {
    background-color: #2a2a2a;
    border-color: var(--category-color, #00B7B5);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.clip-category-button.is-recording {
    background-color: rgba(220, 53, 69, 0.15);
    border-color: #dc3545;
    animation: recording-pulse 2s infinite;
}

.category-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.category-icon {
    font-size: 1.25rem;
    color: var(--category-color, #00B7B5);
}

.category-name {
    font-size: 0.8rem;
    font-weight: 600;
    text-align: center;
    line-height: 1.2;
    word-break: break-word;
}

.category-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
    margin-top: auto;
}

.hotkey-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 20px;
    padding: 0 0.35rem;
    background-color: rgba(0, 183, 181, 0.2);
    border: 1px solid var(--category-color, #00B7B5);
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--category-color, #00B7B5);
}

.clips-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 0.35rem;
    background-color: var(--category-color, #00B7B5);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #0f0f0f;
}

.recording-indicator {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background-color: #dc3545;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #fff;
}

.recording-indicator i {
    font-size: 0.5rem;
}

/* Indicador categoría personal */
.personal-indicator {
    position: absolute;
    bottom: 0.35rem;
    right: 0.35rem;
    font-size: 0.55rem;
    color: #00B7B5;
    opacity: 0.7;
    line-height: 1;
}

.pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.3;
    }
}

@keyframes recording-pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
    }
    50% {
        box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
    }
}

@media (max-width: 768px) {
    .clip-category-button {
        min-height: 70px;
        padding: 0.5rem;
    }

    .category-icon {
        font-size: 1.25rem;
    }

    .category-name {
        font-size: 0.8rem;
    }
}
</style>
