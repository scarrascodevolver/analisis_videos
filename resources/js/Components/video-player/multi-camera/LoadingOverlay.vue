<template>
    <Transition name="fade">
        <div v-if="isLoading" class="loading-overlay">
            <div class="loading-content">
                <div class="camera-icon">
                    <i class="fas fa-video"></i>
                </div>

                <h3 class="loading-title">Preparando cámaras</h3>

                <div class="progress-container">
                    <div class="progress-bar">
                        <div
                            class="progress-fill"
                            :style="{ width: `${progress}%` }"
                        />
                    </div>
                    <p class="progress-text">
                        {{ loadedCount }} de {{ totalCount }} listos
                    </p>
                </div>

                <p class="loading-subtitle">
                    Sincronizando ángulos de cámara...
                </p>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    isLoading: boolean;
    loadedCount: number;
    totalCount: number;
}>();

const progress = computed(() => {
    if (props.totalCount === 0) return 100;
    return (props.loadedCount / props.totalCount) * 100;
});
</script>

<style scoped>
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 15, 15, 0.95);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
    max-width: 400px;
    padding: 2rem;
}

.camera-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00B7B5, #005461);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s ease-in-out infinite;
}

.camera-icon i {
    font-size: 2rem;
    color: #ffffff;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.9;
    }
}

.loading-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    text-align: center;
}

.progress-container {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #252525;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #00B7B5, #005461);
    border-radius: 4px;
    transition: width 0.3s ease;
    box-shadow: 0 0 10px rgba(0, 183, 181, 0.5);
}

.progress-text {
    font-size: 0.875rem;
    font-weight: 600;
    color: #00B7B5;
    text-align: center;
    margin: 0;
}

.loading-subtitle {
    font-size: 0.9rem;
    color: #999;
    text-align: center;
    margin: 0;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media (max-width: 768px) {
    .loading-content {
        padding: 1.5rem;
        gap: 1.25rem;
    }

    .camera-icon {
        width: 64px;
        height: 64px;
    }

    .camera-icon i {
        font-size: 1.75rem;
    }

    .loading-title {
        font-size: 1.25rem;
    }

    .loading-subtitle {
        font-size: 0.8rem;
    }
}
</style>
