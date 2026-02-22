<template>
    <Teleport to="body">
        <transition name="fade">
            <div v-if="show" class="modal-overlay" @click.self="handleClose">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <!-- Header -->
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-tags mr-2"></i>
                                Gestionar Categorías
                            </h5>
                            <button type="button" class="close" @click="handleClose">
                                <span>&times;</span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <div v-if="localCategories.length === 0" class="empty-state">
                                <i class="fas fa-tag fa-3x mb-3 text-muted"></i>
                                <p class="text-muted">No hay categorías creadas</p>
                            </div>

                            <div v-else>
                                <p class="drag-hint">
                                    <i class="fas fa-grip-vertical mr-1"></i>
                                    Arrastrá las filas para cambiar el orden de los botones
                                </p>
                                <div class="categories-list">
                                    <div
                                        v-for="(category, index) in localCategories"
                                        :key="category.id"
                                        class="category-row"
                                        :class="{
                                            'drag-over': dragOverIndex === index,
                                            'dragging': draggingIndex === index,
                                        }"
                                        @dragover.prevent="onDragOver(index)"
                                        @drop="onDrop(index)"
                                        @dragend="onDragEnd"
                                    >
                                        <!-- Drag handle — único elemento draggable del row -->
                                        <span
                                            class="drag-handle"
                                            title="Arrastrar para reordenar"
                                            draggable="true"
                                            @dragstart="onDragStart(index)"
                                        >
                                            <i class="fas fa-grip-vertical"></i>
                                        </span>

                                        <!-- Color swatch -->
                                        <span
                                            class="cat-swatch"
                                            :style="{ backgroundColor: category.color }"
                                        ></span>

                                        <!-- Name -->
                                        <span class="cat-name">{{ category.name }}</span>

                                        <!-- Hotkey -->
                                        <span class="cat-hotkey">
                                            <span v-if="category.hotkey" class="hotkey-badge">
                                                {{ category.hotkey.toUpperCase() }}
                                            </span>
                                            <span v-else class="text-muted">—</span>
                                        </span>

                                        <!-- Scope -->
                                        <span class="cat-scope">
                                            <span class="scope-badge" :class="getScopeBadgeClass(category.scope)">
                                                <i :class="getScopeIcon(category.scope)" class="mr-1"></i>
                                                {{ getScopeLabel(category.scope) }}
                                            </span>
                                        </span>

                                        <!-- Lead/Lag -->
                                        <span class="cat-timing text-muted">
                                            <span v-if="category.lead_seconds || category.lag_seconds">
                                                -{{ category.lead_seconds }}s / +{{ category.lag_seconds }}s
                                            </span>
                                            <span v-else>—</span>
                                        </span>

                                        <!-- Actions -->
                                        <div class="cat-actions">
                                            <button
                                                class="btn-action btn-edit"
                                                @click="handleEdit(category)"
                                                title="Editar"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                class="btn-action btn-delete"
                                                @click="handleDelete(category)"
                                                title="Eliminar"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <p v-if="isSavingOrder" class="saving-order-hint">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Guardando orden...
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="handleClose">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation -->
                <transition name="fade">
                    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="cancelDelete">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                                        Confirmar eliminación
                                    </h5>
                                </div>
                                <div class="modal-body">
                                    <p>¿Eliminar la categoría <strong>{{ categoryToDelete?.name }}</strong>?</p>
                                    <p class="text-warning mb-0 small">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Esta acción no se puede deshacer.
                                    </p>
                                </div>
                                <div class="modal-footer">
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        @click="cancelDelete"
                                        :disabled="isDeleting"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-danger"
                                        @click="confirmDelete"
                                        :disabled="isDeleting"
                                    >
                                        <i class="fas fa-spinner fa-spin mr-1" v-if="isDeleting"></i>
                                        {{ isDeleting ? 'Eliminando...' : 'Eliminar' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useVideoApi } from '@/composables/useVideoApi';
import type { ClipCategory } from '@/types/video-player';

interface Props {
    show: boolean;
    categories: ClipCategory[];
}

interface Emits {
    (e: 'close'): void;
    (e: 'edit-category', category: ClipCategory): void;
    (e: 'delete-category', categoryId: number): void;
    (e: 'reordered', categories: ClipCategory[]): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const videoStore = useVideoStore();

// Local copy for drag & drop reordering
const localCategories = ref<ClipCategory[]>([]);
watch(() => props.categories, (cats) => {
    localCategories.value = [...cats].sort((a, b) => a.sort_order - b.sort_order);
}, { immediate: true });

// Delete state
const showDeleteConfirm = ref(false);
const categoryToDelete = ref<ClipCategory | null>(null);
const isDeleting = ref(false);

// Drag & drop state
const draggingIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);
const isSavingOrder = ref(false);

function handleClose() {
    emit('close');
}

function handleEdit(category: ClipCategory) {
    emit('edit-category', category);
}

function handleDelete(category: ClipCategory) {
    categoryToDelete.value = category;
    showDeleteConfirm.value = true;
}

function cancelDelete() {
    showDeleteConfirm.value = false;
    categoryToDelete.value = null;
}

async function confirmDelete() {
    if (!categoryToDelete.value) return;
    isDeleting.value = true;
    try {
        emit('delete-category', categoryToDelete.value.id);
        showDeleteConfirm.value = false;
        categoryToDelete.value = null;
    } finally {
        isDeleting.value = false;
    }
}

// ── Drag & Drop ──────────────────────────────────────────────

function onDragStart(index: number) {
    draggingIndex.value = index;
}

function onDragOver(index: number) {
    if (draggingIndex.value !== null && draggingIndex.value !== index) {
        dragOverIndex.value = index;
    }
}

function onDrop(index: number) {
    if (draggingIndex.value === null || draggingIndex.value === index) return;

    const items = [...localCategories.value];
    const [moved] = items.splice(draggingIndex.value, 1);
    items.splice(index, 0, moved);
    localCategories.value = items;

    draggingIndex.value = null;
    dragOverIndex.value = null;

    saveNewOrder(items);
}

function onDragEnd() {
    draggingIndex.value = null;
    dragOverIndex.value = null;
}

async function saveNewOrder(items: ClipCategory[]) {
    if (!videoStore.video) return;
    isSavingOrder.value = true;
    const api = useVideoApi(videoStore.video.id);

    try {
        await Promise.all(
            items.map((cat, idx) => api.updateCategory(cat.id, { sort_order: idx + 1 }))
        );
        emit('reordered', items.map((cat, idx) => ({ ...cat, sort_order: idx + 1 })));
    } catch (e) {
        console.error('Error saving category order:', e);
    } finally {
        isSavingOrder.value = false;
    }
}

// ── Labels ───────────────────────────────────────────────────

function getScopeLabel(scope: string): string {
    return scope === 'organization' ? 'Equipo' : scope === 'user' ? 'Personal' : 'XML';
}

function getScopeBadgeClass(scope: string): string {
    return scope === 'organization' ? 'scope-team' : scope === 'user' ? 'scope-personal' : 'scope-xml';
}

function getScopeIcon(scope: string): string {
    return scope === 'organization' ? 'fas fa-users' : scope === 'user' ? 'fas fa-user' : 'fas fa-file-code';
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
    z-index: 9999;
    padding: 1rem;
}

.modal-dialog {
    width: 100%;
    max-width: 680px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-sm { max-width: 400px; }

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
    max-height: 65vh;
    overflow-y: auto;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid #333;
}

/* Hint */
.drag-hint {
    font-size: 0.75rem;
    color: #666;
    margin-bottom: 0.5rem;
}

/* Rows */
.categories-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.category-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.45rem 0.6rem;
    background: #252525;
    border-radius: 4px;
    border: 1px solid #333;
    cursor: grab;
    user-select: none;
    transition: background 0.15s, border-color 0.15s;
    font-size: 0.83rem;
}

.category-row:hover { background: #2a2a2a; }
.category-row.drag-over { border-color: #00B7B5; background: #1e2e2e; }
.category-row.dragging { opacity: 0.4; }

.drag-handle {
    color: #444;
    font-size: 0.8rem;
    flex-shrink: 0;
    cursor: grab;
}
.drag-handle:hover { color: #888; }

.cat-swatch {
    width: 18px;
    height: 18px;
    border-radius: 3px;
    flex-shrink: 0;
    border: 1px solid rgba(255,255,255,0.1);
}

.cat-name {
    flex: 1;
    color: #fff;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}

.cat-hotkey {
    flex-shrink: 0;
    width: 36px;
    text-align: center;
}

.hotkey-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 18px;
    padding: 0 0.3rem;
    background: rgba(0, 183, 181, 0.15);
    border: 1px solid #00B7B5;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #00B7B5;
}

.cat-scope { flex-shrink: 0; }

.scope-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.45rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

.scope-team     { background: rgba(40, 167, 69, 0.2); color: #5cb85c; }
.scope-personal { background: rgba(0, 183, 181, 0.15); color: #00B7B5; }
.scope-xml      { background: rgba(255, 193, 7, 0.15); color: #ffc107; }

.cat-timing {
    flex-shrink: 0;
    font-size: 0.72rem;
    min-width: 80px;
    text-align: right;
}

.cat-actions {
    display: flex;
    gap: 0.3rem;
    flex-shrink: 0;
}

.btn-action {
    background: transparent;
    border: none;
    padding: 0.25rem 0.4rem;
    border-radius: 3px;
    cursor: pointer;
    font-size: 0.78rem;
    transition: all 0.15s;
}

.btn-edit { color: #17a2b8; }
.btn-edit:hover { background: rgba(23, 162, 184, 0.15); color: #5bc0de; }

.btn-delete { color: #dc3545; }
.btn-delete:hover { background: rgba(220, 53, 69, 0.15); color: #ff6b6b; }

.saving-order-hint {
    font-size: 0.75rem;
    color: #777;
    margin-top: 0.5rem;
    margin-bottom: 0;
}

/* Buttons */
.btn {
    padding: 0.4rem 0.9rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn-secondary { background: #444; color: #ccc; }
.btn-secondary:hover:not(:disabled) { background: #555; color: #fff; }
.btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-danger { background: #dc3545; color: #fff; }
.btn-danger:hover:not(:disabled) { background: #c82333; }
.btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }

.empty-state { text-align: center; padding: 3rem 1rem; }
.text-muted { color: #777 !important; }
.text-warning { color: #ffc107 !important; }
.small { font-size: 0.8rem; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
