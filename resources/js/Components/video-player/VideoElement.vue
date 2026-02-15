<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, inject, computed } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';
import SpeedControl from './ui/SpeedControl.vue';
import VideoLoadingOverlay from './ui/VideoLoadingOverlay.vue';
import type { useVideoLoader } from '@/composables/useVideoLoader';

const props = defineProps<{
    streamUrl: string;
    title: string;
    canAnnotate: boolean;
}>();

const emit = defineEmits<{
    toggleAnnotationMode: [];
    addComment: [];
}>();

const videoStore = useVideoStore();
const annotationsStore = useAnnotationsStore();
const videoEl = ref<HTMLVideoElement | null>(null);

// Disable video controls when in annotation mode
const showControls = computed(() => !annotationsStore.annotationMode);

// Inject videoLoader if in multi-camera mode
const videoLoader = inject<ReturnType<typeof useVideoLoader> | null>('videoLoader', null);

onMounted(() => {
    if (videoEl.value) {
        videoStore.setVideoRef(videoEl.value);

        // Auto-collapse sidebar on play (AdminLTE behavior)
        videoEl.value.addEventListener('play', () => {
            document.body.classList.add('sidebar-collapse');
        });
    }
});

onBeforeUnmount(() => {
    // Cleanup PiP if active
    if (document.pictureInPictureElement) {
        document.exitPictureInPicture().catch(() => {});
    }
});

function downloadVideo() {
    const a = document.createElement('a');
    a.href = props.streamUrl;
    a.download = props.title + '.mp4';
    a.click();
}

// Prevent video interaction in annotation mode
function handleVideoClick(event: MouseEvent) {
    if (annotationsStore.annotationMode) {
        event.preventDefault();
        event.stopPropagation();
    }
}
</script>

<template>
    <div
        class="video-container"
        :class="{ 'annotation-mode-active': annotationsStore.annotationMode }"
        style="position: relative; background: #000; border-radius: 8px;"
    >
        <!-- Video wrapper for flex layout (doesn't affect canvas positioning) -->
        <div class="video-wrapper" style="position: relative; width: 100%; height: 100%;">
            <video
                ref="videoEl"
                :controls="showControls"
                preload="metadata"
                crossorigin="anonymous"
                x-webkit-airplay="allow"
                :data-video-title="title"
                style="width: 100%; height: auto; display: block;"
                @timeupdate="videoStore.onTimeUpdate"
                @durationchange="videoStore.onDurationChange"
                @play="videoStore.onPlay"
                @pause="videoStore.onPause"
                @waiting="videoStore.onWaiting"
                @canplay="videoStore.onCanPlay"
                @volumechange="videoStore.onVolumeChange"
                @click="handleVideoClick"
            >
                <source :src="streamUrl" type="video/mp4">
                Tu navegador no soporta la reproducción de video.
            </video>

            <!-- Annotation Canvas Slot (inside video wrapper) -->
            <slot name="annotation-canvas" />
        </div>

        <!-- Loading Overlay (for multi-camera) -->
        <VideoLoadingOverlay
            v-if="videoLoader"
            :is-loading="videoLoader.isLoading"
            :loading-progress="videoLoader.loadingProgress"
            :loaded-videos="videoLoader.loadedVideos"
            :total-videos="videoLoader.totalVideos"
            :failed-videos="videoLoader.failedVideos"
        />

        <!-- Annotation Toolbar Slot -->
        <slot name="annotation-toolbar" />

        <!-- Video Utility Controls -->
        <div class="video-utility-controls">
            <button
                class="video-utility-btn"
                title="Picture-in-Picture (Mini ventana)"
                @click="videoStore.togglePiP"
            >
                <i class="fas fa-external-link-alt"></i>
            </button>

            <button
                class="video-utility-btn"
                title="Descargar video"
                @click="downloadVideo"
            >
                <i class="fas fa-download"></i>
            </button>

            <SpeedControl />
        </div>

        <!-- Overlay action buttons -->
        <div class="video-controls-overlay">
            <button
                class="btn btn-sm btn-rugby font-weight-bold mr-2"
                v-show="!videoStore.isPlaying"
                @click="$emit('addComment')"
            >
                <i class="fas fa-comment-plus"></i> Comentar aquí
            </button>
            <button
                v-if="canAnnotate"
                class="btn btn-sm btn-rugby-outline font-weight-bold"
                @click="$emit('toggleAnnotationMode')"
            >
                <i class="fas fa-paint-brush"></i> Anotar
            </button>
        </div>
    </div>
</template>

<style scoped>
.video-container {
    min-height: 300px;
    aspect-ratio: 16/9;
    overflow: hidden;
}

/* Allow toolbar to overflow when in annotation mode */
.video-container.annotation-mode-active {
    overflow: visible;
}

.video-utility-controls {
    position: absolute;
    bottom: 150px;
    right: 15px;
    z-index: 8;
    display: flex;
    flex-direction: column;
    gap: 6px;
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

.video-controls-overlay {
    position: absolute;
    bottom: 90px;
    right: 10px;
    z-index: 10;
}

.btn-rugby {
    background-color: #005461;
    border-color: #005461;
    color: white;
}

.btn-rugby:hover {
    background-color: #003d4a;
    border-color: #003d4a;
    color: white;
}

.btn-rugby-outline {
    background-color: transparent;
    border: 1px solid #005461;
    color: #00B7B5;
}

.btn-rugby-outline:hover {
    background-color: #005461;
    color: white;
}
</style>
