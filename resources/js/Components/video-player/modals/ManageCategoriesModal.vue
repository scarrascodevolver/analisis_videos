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
                                Gestionar Categorías de Clips
                            </h5>
                            <button type="button" class="close" @click="handleClose">
                                <span>&times;</span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <div v-if="categories.length === 0" class="empty-state">
                                <i class="fas fa-tag fa-3x mb-3 text-muted"></i>
                                <p class="text-muted">No hay categorías creadas</p>
                            </div>

                            <div v-else class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Color</th>
                                            <th>Atajo</th>
                                            <th>Ámbito</th>
                                            <th width="120">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="category in categories" :key="category.id">
                                            <td>
                                                <i
                                                    v-if="category.icon"
                                                    :class="category.icon"
                                                    class="mr-2"
                                                ></i>
                                                {{ category.name }}
                                            </td>
                                            <td>
                                                <div class="color-display">
                                                    <div
                                                        class="color-swatch"
                                                        :style="{ backgroundColor: category.color }"
                                                    ></div>
                                                    <span class="color-code">{{ category.color }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span v-if="category.hotkey" class="badge badge-primary">
                                                    {{ category.hotkey.toUpperCase() }}
                                                </span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td>
                                                <span class="badge" :class="getScopeBadgeClass(category.scope)">
                                                    {{ getScopeLabel(category.scope) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button
                                                        class="btn btn-sm btn-info"
                                                        @click="handleEdit(category)"
                                                        title="Editar"
                                                    >
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button
                                                        class="btn btn-sm btn-danger"
                                                        @click="handleDelete(category)"
                                                        title="Eliminar"
                                                    >
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="handleClose"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <transition name="fade">
                    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="cancelDelete">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                                        Confirmar Eliminación
                                    </h5>
                                </div>
                                <div class="modal-body">
                                    <p>¿Estás seguro de que deseas eliminar la categoría <strong>{{ categoryToDelete?.name }}</strong>?</p>
                                    <p class="text-warning mb-0">
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
import { ref } from 'vue';
import type { ClipCategory } from '@/types/video-player';

interface Props {
    show: boolean;
    categories: ClipCategory[];
}

interface Emits {
    (e: 'close'): void;
    (e: 'edit-category', category: ClipCategory): void;
    (e: 'delete-category', categoryId: number): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const showDeleteConfirm = ref(false);
const categoryToDelete = ref<ClipCategory | null>(null);
const isDeleting = ref(false);

// Methods
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
    } catch (error) {
        console.error('Error deleting category:', error);
    } finally {
        isDeleting.value = false;
    }
}

function getScopeLabel(scope: string): string {
    const labels: Record<string, string> = {
        organization: 'Organización',
        user: 'Usuario',
        video: 'Video',
    };
    return labels[scope] || scope;
}

function getScopeBadgeClass(scope: string): string {
    const classes: Record<string, string> = {
        organization: 'badge-success',
        user: 'badge-info',
        video: 'badge-warning',
    };
    return classes[scope] || 'badge-secondary';
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
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-lg {
    max-width: 800px;
}

.modal-sm {
    max-width: 400px;
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
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #333;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.table-responsive {
    overflow-x: auto;
}

.table {
    margin-bottom: 0;
    color: #ccc;
}

.table-dark {
    background-color: #0f0f0f;
}

.table-dark thead th {
    background-color: #252525;
    border-bottom: 2px solid #444;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.75rem;
}

.table-dark tbody td {
    border-top: 1px solid #333;
    padding: 0.75rem;
}

.table-hover tbody tr:hover {
    background-color: #252525;
}

.color-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.color-swatch {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 1px solid #444;
}

.color-code {
    font-family: monospace;
    font-size: 0.85rem;
}

.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    border-radius: 3px;
}

.badge-primary {
    background-color: #00B7B5;
    color: #fff;
}

.badge-success {
    background-color: #28a745;
    color: #fff;
}

.badge-info {
    background-color: #17a2b8;
    color: #fff;
}

.badge-warning {
    background-color: #ffc107;
    color: #000;
}

.badge-secondary {
    background-color: #6c757d;
    color: #fff;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.375rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.btn-info {
    background-color: #17a2b8;
    color: #fff;
}

.btn-info:hover {
    background-color: #138496;
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

.text-muted {
    color: #999 !important;
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
