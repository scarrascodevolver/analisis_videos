<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useClipsStore } from '@/stores/clipsStore';
import { useVideoStore } from '@/stores/videoStore';
import ClipCategoryButton from './ClipCategoryButton.vue';

const emit = defineEmits<{
    createCategory: [];
    editCategories: [];
}>();

const clipsStore = useClipsStore();
const videoStore = useVideoStore();

const isExpanded = ref(true);

const sortedCategories = computed(() => {
    return clipsStore.activeCategories
        .filter((c) => c.scope !== 'video')
        .sort((a, b) => a.sort_order - b.sort_order);
});

function togglePanel() {
    isExpanded.value = !isExpanded.value;
}

function handleCreateCategory() {
    emit('createCategory');
}

function handleEditCategories() {
    emit('editCategories');
}
</script>

<template>
    <div class="clip-panel">
        <div class="clip-panel-header" @click="togglePanel">
            <div class="d-flex align-items-center">
                <i class="fas fa-cut mr-2"></i>
                <h5 class="mb-0">Modo Análisis - Clips</h5>
            </div>
            <i :class="['fas', isExpanded ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
        </div>

        <div v-show="isExpanded" class="clip-panel-body">
            <div v-if="clipsStore.isLoading" class="text-center py-4">
                <i class="fas fa-spinner fa-spin"></i>
                <p class="mb-0 mt-2 text-muted">Cargando categorías...</p>
            </div>

            <div v-else-if="sortedCategories.length === 0" class="text-center py-4">
                <p class="text-muted mb-3">No hay categorías de clips disponibles</p>
                <button class="btn btn-sm btn-accent" @click="handleCreateCategory">
                    <i class="fas fa-plus mr-1"></i>
                    Crear primera categoría
                </button>
            </div>

            <div v-else class="categories-grid">
                <ClipCategoryButton
                    v-for="category in sortedCategories"
                    :key="category.id"
                    :category="category"
                />
            </div>

            <div v-if="sortedCategories.length > 0" class="clip-panel-actions">
                <button
                    class="btn btn-sm btn-outline-secondary"
                    @click="handleCreateCategory"
                >
                    <i class="fas fa-plus mr-1"></i>
                    Crear
                </button>
                <button
                    class="btn btn-sm btn-outline-secondary"
                    @click="handleEditCategories"
                >
                    <i class="fas fa-edit mr-1"></i>
                    Editar
                </button>
            </div>

            <div v-if="clipsStore.isRecording" class="recording-info">
                <i class="fas fa-circle text-danger pulse mr-2"></i>
                <span>
                    Grabando: <strong>{{ clipsStore.recordingCategory?.name }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.clip-panel {
    background-color: #1a1a1a;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.clip-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.22rem 0.4rem !important;
    background-color: #252525;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
    font-size: 0.68rem !important;
    line-height: 1.1;
}

.clip-panel-header:hover {
    background-color: #2a2a2a;
}

.clip-panel-header h5 {
    color: #fff;
    font-size: 0.66rem !important;
    font-weight: 600;
    margin: 0;
}

.clip-panel-header i {
    color: var(--color-accent);
    font-size: 0.72rem !important;
}

.clip-panel-body {
    padding: 0.5rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.clip-panel-actions {
    display: flex;
    gap: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #333;
}

.clip-panel-actions .btn {
    flex: 1;
}

.recording-info {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    margin-top: 0.5rem;
    background-color: rgba(220, 53, 69, 0.1);
    border-radius: 4px;
    color: #ccc;
    font-size: 0.85rem;
}

.recording-info strong {
    color: #fff;
}

.btn-accent {
    background-color: var(--color-accent);
    color: #fff;
    border: none;
}

.btn-accent:hover {
    background-color: #009d9b;
}

.text-danger {
    color: #dc3545;
}

.pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.3;
    }
}

@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 0.5rem;
    }
}
</style>
