<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="modal-backdrop" @click.self="handleClose">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <!-- Header -->
                        <div class="modal-header">
                            <h3 class="modal-title">
                                <i class="fas fa-sync-alt"></i>
                                Ajustar sincronización
                            </h3>
                            <button
                                type="button"
                                class="btn-close"
                                @click="handleClose"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <p class="description">
                                Ajusta el offset de sincronización para cada ángulo de cámara.
                                Un valor positivo significa que el video comienza después del principal.
                            </p>

                            <div
                                v-for="(slave, index) in localSlaveVideos"
                                :key="slave.id"
                                class="slave-sync-control"
                            >
                                <div class="slave-header">
                                    <h4 class="slave-title">{{ slave.title }}</h4>
                                    <span class="current-offset">
                                        {{ formatOffset(slave.sync_offset) }}
                                    </span>
                                </div>

                                <div class="slider-container">
                                    <div class="slider-wrapper">
                                        <span class="slider-label">-30s</span>
                                        <input
                                            v-model.number="slave.sync_offset"
                                            type="range"
                                            min="-30"
                                            max="30"
                                            step="0.1"
                                            class="sync-slider"
                                            @input="handleSliderChange(index)"
                                        />
                                        <span class="slider-label">+30s</span>
                                    </div>

                                    <div class="slider-value">
                                        <input
                                            v-model.number="slave.sync_offset"
                                            type="number"
                                            step="0.1"
                                            class="offset-input"
                                            @input="handleSliderChange(index)"
                                        />
                                        <span class="offset-unit">segundos</span>
                                    </div>
                                </div>

                                <div class="preview-controls">
                                    <button
                                        type="button"
                                        class="btn btn-preview"
                                        @click="previewSync(slave.id)"
                                    >
                                        <i class="fas fa-play"></i>
                                        Vista previa
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-reset"
                                        @click="resetOffset(index)"
                                    >
                                        <i class="fas fa-undo"></i>
                                        Resetear
                                    </button>
                                </div>
                            </div>

                            <!-- Error message -->
                            <div v-if="errorMessage" class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{ errorMessage }}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="handleClose"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                :disabled="isSubmitting"
                                @click="handleSave"
                            >
                                <i
                                    class="fas"
                                    :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-check'"
                                ></i>
                                {{ isSubmitting ? 'Guardando...' : 'Guardar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import type { SlaveVideo } from '@/types/video-player';

const props = defineProps<{
    show: boolean;
    slaveVideos: SlaveVideo[];
}>();

const emit = defineEmits<{
    close: [];
    saved: [offsets: Record<number, number>];
}>();

// State
const localSlaveVideos = ref<SlaveVideo[]>([]);
const originalOffsets = ref<Record<number, number>>({});
const isSubmitting = ref(false);
const errorMessage = ref('');

// Methods
function handleSliderChange(index: number) {
    // Clamp the value to range
    const slave = localSlaveVideos.value[index];
    slave.sync_offset = Math.max(-30, Math.min(30, slave.sync_offset));
}

function resetOffset(index: number) {
    const slave = localSlaveVideos.value[index];
    slave.sync_offset = originalOffsets.value[slave.id] || 0;
}

function previewSync(slaveId: number) {
    // TODO: Implement preview functionality
    // This would emit an event that the parent can handle
    // to briefly play both videos and show the sync
    console.log('Preview sync for slave:', slaveId);
}

async function handleSave() {
    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        // Build offsets object
        const offsets: Record<number, number> = {};
        localSlaveVideos.value.forEach(slave => {
            offsets[slave.id] = slave.sync_offset;
        });

        // TODO: Implement API call to save offsets
        // This would be a new endpoint in useVideoApi
        // await api.updateSyncOffsets(props.videoId, offsets);

        emit('saved', offsets);
        emit('close');
    } catch (error: any) {
        console.error('Failed to save sync offsets:', error);
        errorMessage.value = error.message || 'Error al guardar la sincronización';
    } finally {
        isSubmitting.value = false;
    }
}

function handleClose() {
    if (!isSubmitting.value) {
        // Restore original offsets
        localSlaveVideos.value.forEach((slave, index) => {
            slave.sync_offset = originalOffsets.value[slave.id] || 0;
        });
        errorMessage.value = '';
        emit('close');
    }
}

function formatOffset(seconds: any): string {
    const num = typeof seconds === 'number' && isFinite(seconds) ? seconds : 0;
    const abs = Math.abs(num);
    const sign = num >= 0 ? '+' : '-';
    return `${sign}${abs.toFixed(1)}s`;
}

// Watch
watch(() => props.show, (newValue) => {
    if (newValue) {
        console.log('🔍 SyncModal - slaveVideos type:', typeof props.slaveVideos, 'IsArray:', Array.isArray(props.slaveVideos), 'Value:', props.slaveVideos);

        // Clone slave videos to avoid mutating props
        localSlaveVideos.value = JSON.parse(JSON.stringify(props.slaveVideos));

        // Store original offsets
        originalOffsets.value = {};

        // SAFE forEach with validation
        if (Array.isArray(props.slaveVideos)) {
            props.slaveVideos.forEach(slave => {
                originalOffsets.value[slave.id] = slave.sync_offset;
            });
        } else {
            console.error('❌ SyncModal - slaveVideos is NOT an array!', props.slaveVideos);
        }
    }
});
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.modal-dialog {
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-content {
    background: #1a1a1a;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #252525;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-title i {
    color: #00B7B5;
}

.btn-close {
    background: transparent;
    border: none;
    color: #999;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.2s;
}

.btn-close:hover {
    color: #ffffff;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.description {
    font-size: 0.9rem;
    color: #999;
    margin: 0 0 1.5rem 0;
    line-height: 1.5;
}

.slave-sync-control {
    background: #0f0f0f;
    border: 1px solid #252525;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.slave-sync-control:last-of-type {
    margin-bottom: 0;
}

.slave-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
}

.slave-title {
    font-size: 1rem;
    font-weight: 600;
    color: #ffffff;
    margin: 0;
}

.current-offset {
    font-size: 0.875rem;
    font-weight: 600;
    color: #00B7B5;
    background: rgba(0, 183, 181, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
}

.slider-container {
    margin-bottom: 1rem;
}

.slider-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.slider-label {
    font-size: 0.75rem;
    color: #666;
    font-weight: 600;
    min-width: 40px;
    text-align: center;
}

.sync-slider {
    flex: 1;
    -webkit-appearance: none;
    appearance: none;
    height: 6px;
    background: #252525;
    border-radius: 3px;
    outline: none;
}

.sync-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    background: linear-gradient(135deg, #00B7B5, #005461);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.sync-slider::-moz-range-thumb {
    width: 18px;
    height: 18px;
    background: linear-gradient(135deg, #00B7B5, #005461);
    border-radius: 50%;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.slider-value {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.offset-input {
    width: 80px;
    padding: 0.5rem;
    background: #252525;
    border: 1px solid #333;
    border-radius: 6px;
    color: #ffffff;
    font-size: 0.9rem;
    text-align: center;
}

.offset-input:focus {
    outline: none;
    border-color: #00B7B5;
}

.offset-unit {
    font-size: 0.8rem;
    color: #999;
}

.preview-controls {
    display: flex;
    gap: 0.75rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-preview {
    flex: 1;
    background: rgba(0, 183, 181, 0.1);
    color: #00B7B5;
    border: 1px solid rgba(0, 183, 181, 0.3);
}

.btn-preview:hover {
    background: rgba(0, 183, 181, 0.2);
}

.btn-reset {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.btn-reset:hover {
    background: rgba(255, 193, 7, 0.2);
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-danger {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #dc3545;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #252525;
}

.btn-secondary {
    background: #252525;
    color: #ffffff;
}

.btn-secondary:hover {
    background: #333;
}

.btn-primary {
    background: linear-gradient(135deg, #00B7B5, #005461);
    color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 183, 181, 0.3);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

@media (max-width: 768px) {
    .modal-dialog {
        max-width: 100%;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 1rem;
    }

    .slave-sync-control {
        padding: 1rem;
    }

    .slave-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .preview-controls {
        flex-direction: column;
    }
}
</style>
