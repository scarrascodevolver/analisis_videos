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
                                    <label>Nombre <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="formData.name"
                                        required
                                        maxlength="50"
                                        placeholder="Ej: Scrum, Tackle, Try..."
                                        autofocus
                                    />
                                </div>

                                <!-- Color + Hotkey en una fila -->
                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label>Color <span class="text-danger">*</span></label>
                                        <div class="color-input-group">
                                            <input
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
                                                placeholder="#00B7B5"
                                            />
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Atajo de teclado</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="formData.hotkey"
                                            maxlength="1"
                                            placeholder="Ej: A, 1..."
                                        />
                                        <small class="form-text text-muted">Tecla rápida para grabar</small>
                                    </div>
                                </div>

                                <!-- Scope -->
                                <div class="form-group">
                                    <label>Visibilidad del botón</label>
                                    <select class="form-control" v-model="formData.scope">
                                        <option value="organization">Todos en la organización ven este botón</option>
                                        <option value="user">Solo yo veo este botón</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Controla quién ve el botón para grabar — no afecta la visibilidad de los clips
                                    </small>
                                </div>

                                <!-- Vista previa del botón -->
                                <div class="form-group">
                                    <label>Vista previa</label>
                                    <div class="btn-preview" :style="{ '--cat-color': formData.color }">
                                        <span class="preview-name">{{ formData.name || 'Nombre categoría' }}</span>
                                        <div class="preview-footer">
                                            <span v-if="formData.hotkey" class="preview-hotkey">
                                                {{ formData.hotkey.toUpperCase() }}
                                            </span>
                                            <span class="preview-scope" :class="formData.scope === 'user' ? 'preview-personal' : 'preview-team'">
                                                <i :class="formData.scope === 'user' ? 'fas fa-user' : 'fas fa-users'"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lead + Lag en una fila -->
                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label>Seg. previos (lead)</label>
                                        <div class="input-with-unit">
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model.number="formData.lead_seconds"
                                                min="0"
                                                max="30"
                                                step="0.5"
                                            />
                                            <span class="unit">seg</span>
                                        </div>
                                        <small class="form-text text-muted">Antes del inicio</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Seg. posteriores (lag)</label>
                                        <div class="input-with-unit">
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model.number="formData.lag_seconds"
                                                min="0"
                                                max="30"
                                                step="0.5"
                                            />
                                            <span class="unit">seg</span>
                                        </div>
                                        <small class="form-text text-muted">Después del fin</small>
                                    </div>
                                </div>
                            </form>

                            <!-- Error Message -->
                            <div v-if="errorMessage" class="alert alert-danger mt-2 mb-0">
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
    lead_seconds: 0,
    lag_seconds: 0,
});

const isSaving = ref(false);
const errorMessage = ref('');

const isEditMode = computed(() => !!props.category);

const isFormValid = computed(() => {
    return formData.value.name.trim() !== '' && /^#[0-9A-Fa-f]{6}$/.test(formData.value.color);
});

watch(
    () => props.category,
    (newCategory) => {
        if (newCategory) {
            formData.value = {
                name: newCategory.name,
                color: newCategory.color,
                hotkey: newCategory.hotkey || '',
                scope: newCategory.scope,
                lead_seconds: newCategory.lead_seconds,
                lag_seconds: newCategory.lag_seconds,
            };
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

function resetForm() {
    formData.value = {
        name: '',
        color: '#00B7B5',
        hotkey: '',
        scope: 'organization',
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
            await api.updateCategory(props.category.id, formData.value);
            emit('saved', { ...props.category, ...formData.value });
        } else {
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
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 1rem;
}

.modal-dialog {
    width: 100%;
    max-width: 440px;
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
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #333;
}

.modal-title {
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.close {
    background: none;
    border: none;
    color: #ccc;
    font-size: 1.4rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close:hover { color: #fff; }

.modal-body {
    padding: 0.9rem 1rem;
}

.form-group {
    margin-bottom: 0.75rem;
}

.form-group label {
    color: #ccc;
    font-size: 0.8rem;
    margin-bottom: 0.3rem;
    display: block;
}

/* Dos columnas */
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.form-control {
    background: #252525;
    border: 1px solid #444;
    color: #fff;
    border-radius: 4px;
    padding: 0.4rem 0.6rem;
    font-size: 0.85rem;
    width: 100%;
}

.form-control:focus {
    background: #2a2a2a;
    border-color: #00B7B5;
    outline: none;
    box-shadow: 0 0 0 0.15rem rgba(0, 183, 181, 0.25);
}

select.form-control {
    cursor: pointer;
}

.color-input-group {
    display: flex;
    gap: 0.4rem;
    align-items: center;
}

.form-control-color {
    width: 44px;
    height: 34px;
    border: 1px solid #444;
    border-radius: 4px;
    cursor: pointer;
    background: #252525;
    flex-shrink: 0;
    padding: 2px;
}

.input-with-unit {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.unit {
    color: #777;
    font-size: 0.75rem;
    white-space: nowrap;
}

.form-text {
    font-size: 0.73rem;
    margin-top: 0.2rem;
}

.text-muted { color: #888 !important; }
.text-danger { color: #dc3545 !important; }

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid #333;
}

.btn {
    padding: 0.4rem 0.9rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-primary { background-color: #00B7B5; color: #fff; }
.btn-primary:hover:not(:disabled) { background-color: #009f9d; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary { background-color: #444; color: #ccc; }
.btn-secondary:hover:not(:disabled) { background-color: #555; color: #fff; }
.btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }

.alert {
    padding: 0.6rem 0.8rem;
    border-radius: 4px;
    font-size: 0.85rem;
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.15);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #f8d7da;
}

/* Button preview */
.btn-preview {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.4rem;
    padding: 0.5rem 0.6rem;
    min-height: 54px;
    background: #252525;
    border: 2px solid var(--cat-color, #444);
    border-radius: 6px;
    max-width: 130px;
    transition: border-color 0.2s;
}

.preview-name {
    font-size: 0.78rem;
    font-weight: 600;
    color: #fff;
    word-break: break-word;
    line-height: 1.2;
}

.preview-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.preview-hotkey {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 17px;
    padding: 0 0.3rem;
    background: rgba(0, 183, 181, 0.15);
    border: 1px solid var(--cat-color, #00B7B5);
    border-radius: 3px;
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--cat-color, #00B7B5);
}

.preview-scope {
    font-size: 0.65rem;
    margin-left: auto;
}
.preview-team     { color: #5cb85c; }
.preview-personal { color: #00B7B5; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
