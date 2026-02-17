<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, inject, computed, watch } from 'vue';
import Hls from 'hls.js';
import { useVideoStore } from '@/stores/videoStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';
import SpeedControl from './ui/SpeedControl.vue';
import VideoLoadingOverlay from './ui/VideoLoadingOverlay.vue';
import type { useVideoLoader } from '@/composables/useVideoLoader';

const props = defineProps<{
    streamUrl: string;
    title: string;
    canAnnotate: boolean;
    bunnyHlsUrl?: string | null;
    bunnyStatus?: string | null;
    bunnyMp4Url?: string | null;
}>();

const activeHlsUrl = computed(() =>
    props.bunnyHlsUrl && props.bunnyStatus === 'ready' ? props.bunnyHlsUrl : null
);
const isHls = computed(() => !!activeHlsUrl.value);

// URL MP4: Bunny original (disponible inmediatamente) o stream legacy
const activeMp4Url = computed(() =>
    !isHls.value ? (props.bunnyMp4Url ?? props.streamUrl) : props.streamUrl
);

const emit = defineEmits<{
    toggleAnnotationMode: [];
    addComment: [];
}>();

const videoStore = useVideoStore();
const annotationsStore = useAnnotationsStore();
const videoEl = ref<HTMLVideoElement | null>(null);
let hlsInstance: Hls | null = null;
let hlsRetryTimeout: ReturnType<typeof setTimeout> | null = null;

// Disable video controls when in annotation mode
const showControls = computed(() => !annotationsStore.annotationMode);

// Inject videoLoader if in multi-camera mode
const videoLoader = inject<ReturnType<typeof useVideoLoader> | null>('videoLoader', null);

/**
 * Inicializa hls.js con la URL dada.
 * Si falla (CDN aún no propagó), vuelve al MP4 original y reintenta en 30s.
 */
function initHls(hlsUrl: string, restoreTime?: number, shouldPlay?: boolean) {
    if (!videoEl.value) return;

    if (Hls.isSupported()) {
        if (hlsInstance) hlsInstance.destroy();
        hlsInstance = new Hls({ enableWorker: true });
        hlsInstance.loadSource(hlsUrl);
        hlsInstance.attachMedia(videoEl.value);

        if (restoreTime !== undefined) {
            hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
                if (videoEl.value) {
                    videoEl.value.currentTime = restoreTime;
                    if (shouldPlay) videoEl.value.play();
                }
            });
        }

        // Fallback: si HLS falla (CDN no propagado aún), volver a MP4 y reintentar
        hlsInstance.on(Hls.Events.ERROR, (_event, data) => {
            if (data.fatal) {
                console.warn('[VideoElement] HLS error, fallback to MP4, retry in 30s:', data.type);
                hlsInstance?.destroy();
                hlsInstance = null;

                // Volver al MP4 original mientras espera
                if (videoEl.value && props.bunnyMp4Url) {
                    const savedTime = videoEl.value.currentTime;
                    videoEl.value.src = props.bunnyMp4Url;
                    videoEl.value.addEventListener('loadedmetadata', () => {
                        if (videoEl.value) {
                            videoEl.value.currentTime = savedTime;
                            if (shouldPlay) videoEl.value.play();
                        }
                    }, { once: true });
                }

                // Reintentar HLS en 30 segundos
                if (hlsRetryTimeout) clearTimeout(hlsRetryTimeout);
                hlsRetryTimeout = setTimeout(() => {
                    initHls(hlsUrl, videoEl.value?.currentTime, !videoEl.value?.paused);
                }, 30000);
            }
        });

    } else if (videoEl.value.canPlayType('application/vnd.apple.mpegurl')) {
        // Safari: HLS nativo
        videoEl.value.src = hlsUrl;
        if (restoreTime !== undefined) {
            videoEl.value.addEventListener('loadedmetadata', () => {
                if (videoEl.value) {
                    videoEl.value.currentTime = restoreTime;
                    if (shouldPlay) videoEl.value.play();
                }
            }, { once: true });
        }
    }
}

onMounted(() => {
    if (!videoEl.value) return;

    // Inicializar HLS si ya está disponible (video existente con encoding completo)
    if (isHls.value && activeHlsUrl.value) {
        initHls(activeHlsUrl.value);
    }

    videoStore.setVideoRef(videoEl.value);

    // Auto-collapse sidebar on play (AdminLTE behavior)
    videoEl.value.addEventListener('play', () => {
        document.body.classList.add('sidebar-collapse');
    });
});

// Transición silenciosa MP4 → HLS cuando el encoding termina mientras el usuario ve el original
watch(activeHlsUrl, (newHlsUrl) => {
    if (!newHlsUrl || !videoEl.value) return;

    const currentTime = videoEl.value.currentTime;
    const wasPlaying = !videoEl.value.paused;

    initHls(newHlsUrl, currentTime, wasPlaying);
});

onBeforeUnmount(() => {
    if (hlsInstance) {
        hlsInstance.destroy();
        hlsInstance = null;
    }
    if (hlsRetryTimeout) {
        clearTimeout(hlsRetryTimeout);
        hlsRetryTimeout = null;
    }
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
                <!-- Safari native HLS (cuando HLS está listo); Chrome/Firefox usa hls.js vía watch -->
                <source v-if="isHls" :src="activeHlsUrl!" type="application/vnd.apple.mpegurl">
                <!-- MP4 de Bunny original (mientras encodea) o stream legacy -->
                <source v-else :src="activeMp4Url" type="video/mp4">
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
