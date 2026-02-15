<template>
    <Teleport to="body">
        <transition name="fade">
            <div v-if="show" class="modal-overlay" @click.self="handleClose">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <!-- Header -->
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle text-danger mr-2"></i>
                                Confirmar Eliminación
                            </h5>
                            <button type="button" class="close" @click="handleClose">
                                <span>&times;</span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <div class="warning-icon">
                                <i class="fas fa-exclamation-circle fa-4x text-warning"></i>
                            </div>

                            <p class="confirmation-text">
                                ¿Estás seguro de que deseas eliminar este video?
                            </p>

                            <div class="video-info">
                                <strong>{{ video?.title }}</strong>
                            </div>

                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Esta acción no se puede deshacer.</strong>
                                <br />
                                Se eliminarán todos los comentarios, clips y anotaciones asociados.
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="handleClose"
                                :disabled="isDeleting"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger"
                                @click="handleConfirm"
                                :disabled="isDeleting"
                            >
                                <i class="fas fa-spinner fa-spin mr-1" v-if="isDeleting"></i>
                                {{ isDeleting ? 'Eliminando...' : 'Eliminar Video' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useVideoApi } from '@/composables/useVideoApi';
import type { Video } from '@/types/video-player';

interface Props {
    show: boolean;
    video: Video | null;
}

interface Emits {
    (e: 'close'): void;
    (e: 'confirmed'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const isDeleting = ref(false);

// Methods
function handleClose() {
    if (!isDeleting.value) {
        emit('close');
    }
}

async function handleConfirm() {
    if (!props.video || isDeleting.value) return;

    isDeleting.value = true;

    try {
        const api = useVideoApi(props.video.id);
        await api.deleteVideo();

        emit('confirmed');

        // Redirect to videos list
        router.visit('/videos', {
            onSuccess: () => {
                // Show success message
                console.log('Video deleted successfully');
            },
        });
    } catch (error: any) {
        console.error('Error deleting video:', error);
        alert(error.message || 'Error al eliminar el video');
        isDeleting.value = false;
    }
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.modal-dialog {
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-content {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 6px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #333;
}

.modal-title {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.close {
    background: none;
    border: none;
    color: #ccc;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close:hover {
    color: #fff;
}

.modal-body {
    padding: 1.5rem 1.25rem;
    text-align: center;
}

.warning-icon {
    margin-bottom: 1.5rem;
}

.confirmation-text {
    font-size: 1.1rem;
    color: #ccc;
    margin-bottom: 1rem;
}

.video-info {
    background: #252525;
    border: 1px solid #444;
    border-radius: 4px;
    padding: 1rem;
    color: #fff;
    word-break: break-word;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 4px;
    font-size: 0.875rem;
    text-align: left;
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.15);
    border: 1px solid rgba(255, 193, 7, 0.3);
    color: #ffc107;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #333;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-danger {
    background-color: #dc3545;
    color: #fff;
}

.btn-danger:hover:not(:disabled) {
    background-color: #c82333;
}

.btn-danger:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #6c757d;
    color: #fff;
}

.btn-secondary:hover:not(:disabled) {
    background-color: #5a6268;
}

.btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.text-danger {
    color: #dc3545 !important;
}

.text-warning {
    color: #ffc107 !important;
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
