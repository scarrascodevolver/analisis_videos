<template>
    <Teleport to="body">
        <transition name="fade">
            <div v-if="show" class="modal-overlay" @click.self="handleClose">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <!-- Header -->
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-cut mr-2"></i>
                                Editar Clip
                            </h5>
                            <button type="button" class="close" @click="handleClose">
                                <span>&times;</span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <form @submit.prevent="handleSubmit">
                                <!-- Start Time -->
                                <div class="form-group">
                                    <label for="clip-start-time">
                                        Tiempo de Inicio <span class="text-danger">*</span>
                                    </label>
                                    <div class="time-input-group">
                                        <input
                                            id="clip-start-time"
                                            type="number"
                                            class="form-control"
                                            v-model.number="formData.start_time"
                                            required
                                            min="0"
                                            step="0.1"
                                        />
                                        <span class="time-display">{{ formatTime(formData.start_time) }}</span>
                                    </div>
                                    <small class="form-text text-muted">En segundos</small>
                                </div>

                                <!-- End Time -->
                                <div class="form-group">
                                    <label for="clip-end-time">
                                        Tiempo de Fin <span class="text-danger">*</span>
                                    </label>
                                    <div class="time-input-group">
                                        <input
                                            id="clip-end-time"
                                            type="number"
                                            class="form-control"
                                            v-model.number="formData.end_time"
                                            required
                                            min="0"
                                            step="0.1"
                                        />
                                        <span class="time-display">{{ formatTime(formData.end_time) }}</span>
                                    </div>
                                    <small class="form-text text-muted">En segundos</small>
                                </div>

                                <!-- Duration Display -->
                                <div class="duration-display mb-3">
                                    <i class="fas fa-clock mr-2"></i>
                                    Duración: <strong>{{ clipDuration }}</strong>
                                </div>

                                <!-- Title -->
                                <div class="form-group">
                                    <label for="clip-title">Título</label>
                                    <input
                                        id="clip-title"
                                        type="text"
                                        class="form-control"
                                        v-model="formData.title"
                                        maxlength="255"
                                        placeholder="Título del clip (opcional)"
                                    />
                                </div>

                                <!-- Notes -->
                                <div class="form-group">
                                    <label for="clip-notes">Notas</label>
                                    <textarea
                                        id="clip-notes"
                                        class="form-control"
                                        v-model="formData.notes"
                                        rows="3"
                                        placeholder="Notas adicionales (opcional)"
                                    ></textarea>
                                </div>
                            </form>

                            <!-- Error Message -->
                            <div v-if="errorMessage" class="alert alert-danger mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                {{ errorMessage }}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="handleClose"
                                :disabled="isSaving"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="handleSubmit"
                                :disabled="isSaving || !isFormValid"
                            >
                                <i class="fas fa-spinner fa-spin mr-1" v-if="isSaving"></i>
                                {{ isSaving ? 'Guardando...' : 'Guardar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useClipsStore } from '@/stores/clipsStore';
import { formatTime } from '@/stores/videoStore';
import type { VideoClip } from '@/types/video-player';

interface Props {
    show: boolean;
    clip: VideoClip | null;
    videoId: number;
}

interface Emits {
    (e: 'close'): void;
    (e: 'saved', clip: VideoClip): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const clipsStore = useClipsStore();

const formData = ref({
    start_time: 0,
    end_time: 0,
    title: '',
    notes: '',
});

const isSaving = ref(false);
const errorMessage = ref('');

// Computed
const isFormValid = computed(() => {
    return (
        formData.value.start_time >= 0 &&
        formData.value.end_time > formData.value.start_time
    );
});

const clipDuration = computed(() => {
    const duration = formData.value.end_time - formData.value.start_time;
    return formatTime(duration);
});

// Watch for clip changes
watch(
    () => props.clip,
    (newClip) => {
        if (newClip) {
            formData.value = {
                start_time: newClip.start_time,
                end_time: newClip.end_time,
                title: newClip.title || '',
                notes: newClip.notes || '',
            };
            errorMessage.value = '';
        }
    },
    { immediate: true }
);

// Methods
function handleClose() {
    if (!isSaving.value) {
        emit('close');
    }
}

async function handleSubmit() {
    if (!isFormValid.value || isSaving.value || !props.clip) return;

    isSaving.value = true;
    errorMessage.value = '';

    try {
        await clipsStore.updateClip(props.videoId, props.clip.id, {
            start_time: formData.value.start_time,
            end_time: formData.value.end_time,
            title: formData.value.title || null,
            notes: formData.value.notes || null,
        });

        // Emit saved event with updated clip
        const updatedClip: VideoClip = {
            ...props.clip,
            start_time: formData.value.start_time,
            end_time: formData.value.end_time,
            title: formData.value.title || null,
            notes: formData.value.notes || null,
        };

        emit('saved', updatedClip);
        emit('close');
    } catch (error: any) {
        errorMessage.value = error.message || 'Error al guardar el clip';
        console.error('Error updating clip:', error);
    } finally {
        isSaving.value = false;
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
    padding: 1.25rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    color: #ccc;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    background: #252525;
    border: 1px solid #444;
    color: #fff;
    border-radius: 4px;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
    width: 100%;
}

.form-control:focus {
    background: #2a2a2a;
    border-color: #00B7B5;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0, 183, 181, 0.25);
}

.time-input-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.time-input-group input {
    flex: 1;
}

.time-display {
    color: #00B7B5;
    font-family: monospace;
    font-size: 1rem;
    font-weight: 600;
    min-width: 80px;
}

.duration-display {
    background: #252525;
    border: 1px solid #444;
    border-radius: 4px;
    padding: 0.5rem 0.75rem;
    color: #ccc;
    font-size: 0.9rem;
}

.duration-display strong {
    color: #00B7B5;
}

.form-text {
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.text-muted {
    color: #999 !important;
}

.text-danger {
    color: #dc3545 !important;
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
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

.btn-primary {
    background-color: #00B7B5;
    color: #fff;
}

.btn-primary:hover:not(:disabled) {
    background-color: #009f9d;
}

.btn-primary:disabled {
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

.alert {
    padding: 0.75rem 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.15);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #f8d7da;
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
