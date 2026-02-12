<script setup lang="ts">
import { computed } from 'vue';
import { useClipsStore } from '@/stores/clipsStore';
import { useVideoStore } from '@/stores/videoStore';
import { formatTime } from '@/stores/videoStore';

const clipsStore = useClipsStore();
const videoStore = useVideoStore();

const recordingDuration = computed(() => {
    if (!clipsStore.isRecording) return 0;
    return videoStore.currentTime - clipsStore.recordingStartTime;
});

const formattedDuration = computed(() => formatTime(recordingDuration.value));

function handleCancel() {
    clipsStore.cancelRecording();
}
</script>

<template>
    <Transition name="fade">
        <div v-if="clipsStore.isRecording" class="recording-indicator">
            <div class="recording-content">
                <div class="recording-badge">
                    <i class="fas fa-circle pulse"></i>
                    <span class="rec-text">REC</span>
                </div>

                <div class="recording-info">
                    <div class="recording-category">
                        {{ clipsStore.recordingCategory?.name }}
                    </div>
                    <div class="recording-duration">
                        {{ formattedDuration }}
                    </div>
                </div>

                <button class="btn-cancel" title="Cancelar (ESC)" @click="handleCancel">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.recording-indicator {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    pointer-events: auto;
}

.recording-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.95) 0%, rgba(180, 30, 40, 0.95) 100%);
    border-radius: 50px;
    box-shadow: 0 4px 20px rgba(220, 53, 69, 0.4),
                0 0 0 4px rgba(220, 53, 69, 0.2);
    animation: slide-in 0.3s ease-out;
}

.recording-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.recording-badge i {
    font-size: 0.75rem;
    color: #fff;
}

.rec-text {
    font-weight: 700;
    font-size: 0.9rem;
    color: #fff;
    letter-spacing: 0.5px;
}

.recording-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.recording-category {
    font-weight: 600;
    font-size: 0.875rem;
    color: #fff;
    line-height: 1.2;
}

.recording-duration {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

.btn-cancel {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: rgba(0, 0, 0, 0.2);
    border: none;
    border-radius: 50%;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-cancel:hover {
    background-color: rgba(0, 0, 0, 0.4);
    transform: scale(1.1);
}

.pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(0.9);
    }
}

@keyframes slide-in {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}

.fade-enter-from {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
}

@media (max-width: 768px) {
    .recording-indicator {
        top: 10px;
    }

    .recording-content {
        padding: 0.5rem 1rem;
        gap: 0.75rem;
    }

    .recording-category {
        font-size: 0.8rem;
    }

    .recording-duration {
        font-size: 0.7rem;
    }

    .btn-cancel {
        width: 24px;
        height: 24px;
    }
}
</style>
