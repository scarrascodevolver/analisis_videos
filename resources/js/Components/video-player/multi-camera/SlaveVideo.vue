<template>
    <div
        class="slave-video-card"
        @click="handleClick"
    >
        <div class="video-wrapper">
            <!-- YouTube slave player:
                 YT.Player REEMPLAZA el div target con un <iframe>.
                 El wrapper externo (.yt-slave-outer) persiste con la posición correcta.
                 El iframe hereda tamaño vía :deep(iframe). -->
            <div
                v-if="slave.is_youtube_video && slave.youtube_video_id"
                class="slave-video yt-slave-outer"
            >
                <div :id="ytContainerId" style="width:100%;height:100%;" />
                <!-- Overlay transparente: captura clicks antes que el iframe YouTube.
                     Sin esto el iframe los intercepta y abre YouTube en ventana nueva. -->
                <div class="yt-click-overlay" @click.stop="handleClick" />
            </div>

            <!-- Regular HTML5 video slave -->
            <video
                v-else
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
import { ref, onMounted, onBeforeUnmount, inject, computed, watch } from 'vue';
import Hls from 'hls.js';
import type { SlaveVideo as SlaveVideoType } from '@/types/video-player';
import { useVideoStore } from '@/stores/videoStore';

const props = defineProps<{
    slave: SlaveVideoType;
}>();

const emit = defineEmits<{
    click: [];
    remove: [slaveId: number];
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
let hlsInstance: Hls | null = null;
let ytPlayerInstance: any = null;

// Stable DOM id for the YouTube iframe container
const ytContainerId = `yt-slave-${props.slave.id}`;

const activeHlsUrl = computed(() =>
    props.slave.bunny_hls_url && props.slave.bunny_status === 'ready' ? props.slave.bunny_hls_url : null
);
const isHls = computed(() => !!activeHlsUrl.value);

// Inject the multiCamera composable from parent
const multiCamera = inject<any>('multiCamera', null);
const videoStore = useVideoStore();

// ── Defensive watchers (handle component reuse when key stays the same) ────────
// These fire when props change on a reused component. With composite :key they
// almost never fire, but they ensure correctness in all edge cases.

watch(activeHlsUrl, (newHlsUrl, oldHlsUrl) => {
    if (!videoRef.value || !newHlsUrl || newHlsUrl === oldHlsUrl) return;
    if (hlsInstance) { hlsInstance.destroy(); hlsInstance = null; }
    if (Hls.isSupported()) {
        hlsInstance = new Hls({ enableWorker: true });
        hlsInstance.loadSource(newHlsUrl);
        hlsInstance.attachMedia(videoRef.value);
    } else if (videoRef.value.canPlayType('application/vnd.apple.mpegurl')) {
        videoRef.value.src = newHlsUrl;
        videoRef.value.load();
    }
});

const activeSrc = computed(() =>
    !props.slave.is_youtube_video && !isHls.value
        ? (props.slave.bunny_mp4_url ?? props.slave.stream_url)
        : null
);
watch(activeSrc, (newSrc, oldSrc) => {
    if (!videoRef.value || !newSrc || newSrc === oldSrc) return;
    if (hlsInstance) { hlsInstance.destroy(); hlsInstance = null; }
    videoRef.value.src = newSrc;
    videoRef.value.load();
});

// ── YouTube IFrame API helpers ────────────────────────────────────────────────

declare global {
    interface Window {
        YT: any;
        onYouTubeIframeAPIReady?: () => void;
    }
}

function ensureYtApiLoaded(): Promise<void> {
    return new Promise((resolve) => {
        if (window.YT && window.YT.Player) {
            resolve();
            return;
        }

        // If the script tag is already in the DOM, just wait for the callback
        const existingCallback = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = () => {
            if (existingCallback) existingCallback();
            resolve();
        };

        if (!document.getElementById('yt-iframe-api-script')) {
            const tag = document.createElement('script');
            tag.id = 'yt-iframe-api-script';
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        }
    });
}

async function initYtPlayer() {
    await ensureYtApiLoaded();

    const container = document.getElementById(ytContainerId);
    if (!container) return;

    ytPlayerInstance = new window.YT.Player(ytContainerId, {
        videoId: props.slave.youtube_video_id!,
        playerVars: {
            autoplay: 0,
            controls: 0,          // Hide YouTube controls — master controls playback
            mute: 1,              // Always muted (slaves follow master audio)
            modestbranding: 1,
            rel: 0,
            disablekb: 1,
            fs: 0,
            iv_load_policy: 3,
            playsinline: 1,
        },
        events: {
            onReady: () => {
                // Register the YT player with the multi-camera controller
                if (multiCamera) {
                    multiCamera.registerSlaveYtPlayer(props.slave.id, ytPlayerInstance);
                    // If the HTML5 master was already playing when this slave
                    // finished initializing, start it immediately instead of
                    // waiting for the next timeupdate tick.
                    multiCamera.syncSlaveIfMasterPlaying(props.slave.id);
                    // Also position slave at the correct timestamp even if master is
                    // paused (e.g., after a master swap). Works for both HTML5 and
                    // YouTube masters via videoStore.currentTime.
                    const offset = Number(props.slave.sync_offset || 0);
                    const targetTime = Math.max(0, videoStore.currentTime - offset);
                    try { ytPlayerInstance.seekTo(targetTime, true); } catch (_) {}
                }
            },
        },
    });
}

// ─────────────────────────────────────────────────────────────────────────────

onMounted(() => {
    console.log(`[SLAVE MOUNT] id=${props.slave.id} isYt=${props.slave.is_youtube_video} ytId=${props.slave.youtube_video_id ?? 'none'} isHls=${isHls.value} stream=...${String(props.slave.stream_url).slice(-40)}`);

    if (props.slave.is_youtube_video && props.slave.youtube_video_id) {
        // YouTube slave: initialise YT.Player
        initYtPlayer();
        return;
    }

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
    if (props.slave.is_youtube_video) {
        if (multiCamera) {
            multiCamera.unregisterSlaveYtPlayer(props.slave.id);
        }
        if (ytPlayerInstance) {
            try { ytPlayerInstance.destroy(); } catch (_) {}
            ytPlayerInstance = null;
        }
        return;
    }

    if (hlsInstance) {
        hlsInstance.destroy();
        hlsInstance = null;
    }
    if (multiCamera) {
        multiCamera.unregisterSlaveElement(props.slave.id);
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
    border-color: #FFC300;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 195, 0, 0.2);
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

/* Overlay transparente sobre el iframe YouTube para capturar clicks de swap */
.yt-click-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 5;
    cursor: pointer;
    background: transparent;
}

/* YouTube: wrapper persiste con la posición, el iframe hereda tamaño */
.yt-slave-outer {
    object-fit: unset;
}

.yt-slave-outer :deep(iframe) {
    position: absolute;
    top: 0;
    left: 0;
    width: 100% !important;
    height: 100% !important;
    border: none;
    display: block;
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
    color: #FFC300;
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
