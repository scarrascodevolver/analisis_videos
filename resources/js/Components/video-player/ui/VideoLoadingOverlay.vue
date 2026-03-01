<template>
    <Transition name="fade">
        <div v-if="isLoading" class="video-loading-overlay">
            <div class="loading-content">
                <!-- Spinner -->
                <div class="spinner-border text-rugby" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>

                <!-- Progress text -->
                <div class="loading-text">
                    <h5 class="mb-2">Preparando videos</h5>
                    <p class="mb-3">
                        Cargando {{ loadedVideos }} de {{ totalVideos }} videos...
                    </p>
                </div>

                <!-- Progress bar -->
                <div class="progress-container">
                    <div class="progress">
                        <div
                            class="progress-bar progress-bar-striped progress-bar-animated bg-rugby"
                            role="progressbar"
                            :style="{ width: loadingProgress + '%' }"
                            :aria-valuenow="loadingProgress"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            {{ loadingProgress }}%
                        </div>
                    </div>
                </div>

                <!-- Failed videos warning -->
                <div v-if="failedVideos.length > 0" class="warning-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    Algunos videos tardaron en cargar: {{ failedVideos.join(', ') }}
                </div>

                <!-- Help text -->
                <div class="help-text">
                    <i class="fas fa-info-circle"></i>
                    Esto asegura que todos los ángulos se reproduzcan sincronizados
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
defineProps<{
    isLoading: boolean;
    loadingProgress: number;
    loadedVideos: number;
    totalVideos: number;
    failedVideos: string[];
}>();
</script>

<style scoped>
.video-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.95);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.loading-content {
    text-align: center;
    max-width: 400px;
    padding: 2rem;
}

.spinner-border {
    width: 4rem;
    height: 4rem;
    border-width: 0.4rem;
    margin-bottom: 1.5rem;
}

.text-rugby {
    color: var(--color-accent) !important;
}

.loading-text h5 {
    color: #ffffff;
    font-weight: 600;
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.loading-text p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.95rem;
    margin-bottom: 0;
}

.progress-container {
    margin-top: 1rem;
    width: 100%;
}

.progress {
    height: 8px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.3s ease;
    font-size: 0.7rem;
    line-height: 8px;
}

.bg-rugby {
    background-color: var(--color-accent) !important;
}

.warning-message {
    margin-top: 1rem;
    padding: 0.75rem;
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 6px;
    color: #ffc107;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.warning-message i {
    font-size: 1rem;
}

.help-text {
    margin-top: 1.5rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.help-text i {
    opacity: 0.6;
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
