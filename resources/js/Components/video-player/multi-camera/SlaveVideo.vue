<template>
    <div
        class="slave-video-card"
        :class="{ 'is-syncing': syncStatus === 'syncing' }"
        @click="handleClick"
    >
        <div class="video-wrapper">
            <video
                ref="videoRef"
                class="slave-video"
                :src="slave.stream_url"
                :data-video-title="slave.title"
                muted
                playsinline
                preload="metadata"
                disablePictureInPicture
                @contextmenu.prevent
            />

            <!-- Sync status indicator -->
            <div class="sync-indicator" :class="`sync-${syncStatus}`">
                <i
                    class="fas"
                    :class="{
                        'fa-check-circle': syncStatus === 'synced',
                        'fa-sync fa-spin': syncStatus === 'syncing',
                        'fa-exclamation-triangle': syncStatus === 'out-of-sync'
                    }"
                />
            </div>

            <!-- Action buttons -->
            <div class="action-buttons">
                <button class="btn-action btn-sync" title="Sincronizar" @click.stop="$emit('sync', slave.id)">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="btn-action btn-remove" title="Eliminar ángulo" @click.stop="$emit('remove', slave.id)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <!-- Title overlay -->
            <div class="title-overlay">
                <span class="title-text">{{ slave.title || 'Sin título' }}</span>
                <span class="sync-offset">
                    {{ formatOffset(slave.sync_offset ?? 0) }}
                </span>
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
import { ref, onMounted, inject } from 'vue';
import type { SlaveVideo as SlaveVideoType } from '@/types/video-player';

const props = defineProps<{
    slave: SlaveVideoType;
    syncStatus: 'synced' | 'syncing' | 'out-of-sync';
}>();

const emit = defineEmits<{
    click: [];
    sync: [slaveId: number];
    remove: [slaveId: number];
}>();

const videoRef = ref<HTMLVideoElement | null>(null);

// Inject the multiCamera composable from parent
const multiCamera = inject<any>('multiCamera', null);

onMounted(() => {
    // Register this slave video element with the multi-camera controller
    if (videoRef.value && multiCamera) {
        multiCamera.registerSlaveElement(props.slave.id, videoRef.value);
    }
});

function handleClick() {
    emit('click');
}

defineExpose({
    videoRef,
});

function formatOffset(seconds: any): string {
    // Handle any type - convert to number safely
    let numSeconds = 0;

    if (seconds === null || seconds === undefined) {
        numSeconds = 0;
    } else if (typeof seconds === 'number' && isFinite(seconds)) {
        numSeconds = seconds;
    } else if (typeof seconds === 'string') {
        const parsed = parseFloat(seconds);
        numSeconds = isFinite(parsed) ? parsed : 0;
    } else {
        // Handle any other type (including refs)
        numSeconds = 0;
    }

    const abs = Math.abs(numSeconds);
    const sign = numSeconds >= 0 ? '+' : '-';
    return `${sign}${abs.toFixed(1)}s`;
}
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

.slave-video-card.is-syncing {
    border-color: #ffc107;
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
    object-fit: cover;
}

.sync-indicator {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 2;
}

.sync-indicator.sync-synced {
    color: #00B7B5;
}

.sync-indicator.sync-syncing {
    color: #ffc107;
}

.sync-indicator.sync-out-of-sync {
    color: #dc3545;
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
    font-size: 0.9rem;
    font-weight: 600;
    color: #ffffff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

.sync-offset {
    font-size: 0.75rem;
    font-weight: 500;
    color: #00B7B5;
    background: rgba(0, 183, 181, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
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

.btn-sync {
    color: #00B7B5;
}

.btn-sync:hover {
    background: rgba(0, 183, 181, 0.9);
    color: #fff;
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
        font-size: 0.8rem;
    }

    .sync-offset {
        font-size: 0.7rem;
    }

    .sync-indicator {
        width: 28px;
        height: 28px;
        font-size: 14px;
        top: 0.5rem;
        right: 0.5rem;
    }
}
</style>
