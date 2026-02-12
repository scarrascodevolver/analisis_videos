<template>
    <Teleport to="body">
        <transition name="fade">
            <div v-if="show" class="modal-overlay" @click.self="handleClose">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <!-- Header -->
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-tag mr-2"></i>
                                {{ isEditMode ? 'Editar Categoría' : 'Nueva Categoría' }}
                            </h5>
                            <button type="button" class="close" @click="handleClose">
                                <span>&times;</span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <form @submit.prevent="handleSubmit">
                                <!-- Name -->
                                <div class="form-group">
                                    <label for="category-name">
                                        Nombre <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="category-name"
                                        type="text"
                                        class="form-control"
                                        v-model="formData.name"
                                        required
                                        maxlength="50"
                                    />
                                </div>

                                <!-- Color -->
                                <div class="form-group">
                                    <label for="category-color">
                                        Color <span class="text-danger">*</span>
                                    </label>
                                    <div class="color-input-group">
                                        <input
                                            id="category-color"
                                            type="color"
                                            class="form-control-color"
                                            v-model="formData.color"
                                            required
                                        />
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formData.color"
                                            pattern="^#[0-9A-Fa-f]{6}$"
                                        />
                                    </div>
                                </div>

                                <!-- Hotkey -->
                                <div class="form-group">
                                    <label for="category-hotkey">Atajo de Teclado</label>
                                    <input
                                        id="category-hotkey"
                                        type="text"
                                        class="form-control"
                                        v-model="formData.hotkey"
                                        maxlength="1"
                                        placeholder="Ej: A, B, 1, 2..."
                                    />
                                    <small class="form-text text-muted">
                                        Presiona una tecla para crear clips rápidos
                                    </small>
                                </div>

                                <!-- Scope -->
                                <div class="form-group">
                                    <label for="category-scope">
                                        Ámbito <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        id="category-scope"
                                        class="form-control"
                                        v-model="formData.scope"
                                        required
                                    >
                                        <option value="organization">Organización</option>
                                        <option value="user">Usuario</option>
                                        <option value="video">Solo este video</option>
                                    </select>
                                </div>

                                <!-- Icon -->
                                <div class="form-group">
                                    <label for="category-icon">Icono (Font Awesome)</label>
                                    <input
                                        id="category-icon"
                                        type="text"
                                        class="form-control"
                                        v-model="formData.icon"
                                        placeholder="Ej: fas fa-star"
                                    />
                                </div>

                                <!-- Lead Seconds -->
                                <div class="form-group">
                                    <label for="category-lead">
                                        Segundos Previos (Lead)
                                    </label>
                                    <input
                                        id="category-lead"
                                        type="number"
                                        class="form-control"
                                        v-model.number="formData.lead_seconds"
                                        min="0"
                                        max="30"
                                        step="0.5"
                                    />
                                    <small class="form-text text-muted">
                                        Tiempo antes del inicio del clip
                                    </small>
                                </div>

                                <!-- Lag Seconds -->
                                <div class="form-group">
                                    <label for="category-lag">
                                        Segundos Posteriores (Lag)
                                    </label>
                                    <input
                                        id="category-lag"
                                        type="number"
                                        class="form-control"
                                        v-model.number="formData.lag_seconds"
                                        min="0"
                                        max="30"
                                        step="0.5"
                                    />
                                    <small class="form-text text-muted">
                                        Tiempo después del fin del clip
                                    </small>
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
import { useVideoStore } from '@/stores/videoStore';
import { useVideoApi } from '@/composables/useVideoApi';
import type { ClipCategory } from '@/types/video-player';

interface Props {
    show: boolean;
    category?: ClipCategory | null;
}

interface Emits {
    (e: 'close'): void;
    (e: 'saved', category: ClipCategory): void;
}

const props = withDefaults(defineProps<Props>(), {
    category: null,
});

const emit = defineEmits<Emits>();

const videoStore = useVideoStore();

const formData = ref({
    name: '',
    color: '#00B7B5',
    hotkey: '',
    scope: 'organization' as 'organization' | 'user' | 'video',
    icon: '',
    lead_seconds: 0,
    lag_seconds: 0,
});

const isSaving = ref(false);
const errorMessage = ref('');

// Computed
const isEditMode = computed(() => !!props.category);

const isFormValid = computed(() => {
    return formData.value.name.trim() !== '' && /^#[0-9A-Fa-f]{6}$/.test(formData.value.color);
});

// Watch for category changes
watch(
    () => props.category,
    (newCategory) => {
        if (newCategory) {
            formData.value = {
                name: newCategory.name,
                color: newCategory.color,
                hotkey: newCategory.hotkey || '',
                scope: newCategory.scope,
                icon: newCategory.icon || '',
                lead_seconds: newCategory.lead_seconds,
                lag_seconds: newCategory.lag_seconds,
            };
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

// Methods
function resetForm() {
    formData.value = {
        name: '',
        color: '#00B7B5',
        hotkey: '',
        scope: 'organization',
        icon: '',
        lead_seconds: 0,
        lag_seconds: 0,
    };
    errorMessage.value = '';
}

function handleClose() {
    if (!isSaving.value) {
        resetForm();
        emit('close');
    }
}

async function handleSubmit() {
    if (!isFormValid.value || isSaving.value) return;

    const video = videoStore.video;
    if (!video) return;

    isSaving.value = true;
    errorMessage.value = '';

    try {
        const api = useVideoApi(video.id);

        if (isEditMode.value && props.category) {
            // Update existing category
            await api.updateCategory(props.category.id, formData.value);
            emit('saved', { ...props.category, ...formData.value });
        } else {
            // Create new category
            const response = await api.createCategory(formData.value);
            if (response.success && response.category) {
                emit('saved', response.category);
            }
        }

        handleClose();
    } catch (error: any) {
        errorMessage.value = error.message || 'Error al guardar la categoría';
        console.error('Error saving category:', error);
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
}

.form-control:focus {
    background: #2a2a2a;
    border-color: #00B7B5;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0, 183, 181, 0.25);
}

.color-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.form-control-color {
    width: 60px;
    height: 38px;
    border: 1px solid #444;
    border-radius: 4px;
    cursor: pointer;
    background: #252525;
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
