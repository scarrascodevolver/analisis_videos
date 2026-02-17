<template>
    <div
        class="slave-video-card"
        @click="handleClick"
    >
        <div class="video-wrapper">
            <video
                ref="videoRef"
                class="slave-video"
                :src="isHls ? undefined : (slave.bunny_mp4_url ?? slave.stream_url)"
                :data-video-title="slave.title"
                muted
                playsinline
                preload="metadata"
                disablePictureInPicture
                @contextmenu.prevent
            />

            <!-- Action button: Remove -->
            <div class="action-buttons">
                <button class="btn-action btn-remove" title="Eliminar ángulo" @click.stop="$emit('remove', slave.id)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <!-- Title overlay -->
            <div class="title-overlay">
                <span class="title-text">{{ slave.title || 'Sin título' }}</span>
            </div>

            <!-- Click hint -->
            <div class="swap-hint">
                <i class="fas fa-exchange-alt"></i>
                <span>Cambiar a principal</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, inject, computed } from 'vue';
import Hls from 'hls.js';
import type { SlaveVideo as SlaveVideoType } from '@/types/video-player';

const props = defineProps<{
    slave: SlaveVideoType;
}>();

const emit = defineEmits<{
    click: [];
    remove: [slaveId: number];
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
let hlsInstance: Hls | null = null;

const activeHlsUrl = computed(() =>
    props.slave.bunny_hls_url && props.slave.bunny_status === 'ready' ? props.slave.bunny_hls_url : null
);
const isHls = computed(() => !!activeHlsUrl.value);

// Inject the multiCamera composable from parent
const multiCamera = inject<any>('multiCamera', null);

onMounted(() => {
    if (!videoRef.value) return;

    // Inicializar HLS (Bunny o Cloudflare legacy)
    if (isHls.value && activeHlsUrl.value) {
        if (Hls.isSupported()) {
            hlsInstance = new Hls({ enableWorker: true });
            hlsInstance.loadSource(activeHlsUrl.value);
            hlsInstance.attachMedia(videoRef.value);
        } else if (videoRef.value.canPlayType('application/vnd.apple.mpegurl')) {
            videoRef.value.src = activeHlsUrl.value;
        }
    }

    // Register this slave video element with the multi-camera controller
    if (multiCamera) {
        multiCamera.registerSlaveElement(props.slave.id, videoRef.value);
    }
});

onBeforeUnmount(() => {
    if (hlsInstance) {
        hlsInstance.destroy();
        hlsInstance = null;
    }
});

function handleClick() {
    emit('click');
}

defineExpose({
    videoRef,
});
</script>

<style scoped>
.slave-video-card {
    position: relative;
    background: #252525;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.slave-video-card:hover {
    border-color: #00B7B5;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 183, 181, 0.2);
}

.video-wrapper {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    background: #0f0f0f;
}

.slave-video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.title-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0.75rem;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    z-index: 1;
}

.title-text {
    font-size: 0.65rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.85);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

.swap-hint {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
    z-index: 3;
    color: #ffffff;
    text-align: center;
}

.slave-video-card:hover .swap-hint {
    opacity: 1;
}

.swap-hint i {
    font-size: 2rem;
    color: #00B7B5;
}

.swap-hint span {
    font-size: 0.875rem;
    font-weight: 600;
    background: rgba(0, 0, 0, 0.8);
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

.action-buttons {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    display: flex;
    gap: 0.25rem;
    z-index: 4;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.slave-video-card:hover .action-buttons {
    opacity: 1;
}

.btn-action {
    width: 24px;
    height: 24px;
    border-radius: 3px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    transition: all 0.2s;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

.btn-remove {
    color: #dc3545;
}

.btn-remove:hover {
    background: rgba(220, 53, 69, 0.9);
    color: #fff;
}

@media (max-width: 768px) {
    .title-overlay {
        padding: 0.5rem;
    }

    .title-text {
        font-size: 0.55rem;
    }
}
</style>
