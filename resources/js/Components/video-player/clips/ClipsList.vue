<script setup lang="ts">
import { ref, computed } from 'vue';
import { useClipsStore } from '@/stores/clipsStore';
import { useVideoStore } from '@/stores/videoStore';
import ClipItem from './ClipItem.vue';
import type { ClipCategory } from '@/types/video-player';

const clipsStore = useClipsStore();
const videoStore = useVideoStore();
const searchQuery = ref('');
const expandedCategories = ref<Set<number>>(new Set());

// ── Drag & Drop state — categorías ──────────────────────────
const draggingCatIndex = ref<number | null>(null);
const dragOverCatIndex = ref<number | null>(null);

// ── Computed ────────────────────────────────────────────────

const filteredClipsByCategory = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    const grouped = clipsStore.clipsByCategory;

    if (!query) return grouped;

    const filtered: Record<number, typeof clipsStore.clips> = {};

    Object.entries(grouped).forEach(([catId, clips]) => {
        const matchingClips = clips.filter((clip) => {
            const titleMatch = clip.title?.toLowerCase().includes(query);
            const notesMatch = clip.notes?.toLowerCase().includes(query);
            const categoryMatch = clip.category?.name.toLowerCase().includes(query);
            return titleMatch || notesMatch || categoryMatch;
        });

        if (matchingClips.length > 0) {
            filtered[Number(catId)] = matchingClips;
        }
    });

    return filtered;
});

// Fuente única de verdad compartida con el timeline
const categoriesWithClips = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) return clipsStore.categoriesWithClips;
    return clipsStore.categoriesWithClips.filter(
        (cat) => !!filteredClipsByCategory.value[cat.id]?.length
    );
});

// ── Actions ─────────────────────────────────────────────────

function toggleCategory(categoryId: number) {
    if (expandedCategories.value.has(categoryId)) {
        expandedCategories.value.delete(categoryId);
    } else {
        expandedCategories.value.add(categoryId);
    }
}

function isCategoryExpanded(categoryId: number) {
    return expandedCategories.value.has(categoryId);
}

function getCategoryClipsCount(category: ClipCategory) {
    return filteredClipsByCategory.value[category.id]?.length || 0;
}

// ── Drag & Drop categorías ───────────────────────────────────

function onCatDragStart(event: DragEvent, index: number) {
    draggingCatIndex.value = index;
    if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
}

function onCatDragOver(index: number) {
    if (draggingCatIndex.value !== null && draggingCatIndex.value !== index) {
        dragOverCatIndex.value = index;
    }
}

function onCatDrop(toIndex: number) {
    if (draggingCatIndex.value === null || draggingCatIndex.value === toIndex) {
        onCatDragEnd();
        return;
    }

    const items = [...categoriesWithClips.value];
    const [moved] = items.splice(draggingCatIndex.value, 1);
    items.splice(toIndex, 0, moved);

    onCatDragEnd();

    if (!videoStore.video) return;
    clipsStore.reorderCategories(videoStore.video.id, items)
        .catch(() => console.error('Error guardando orden de categorías'));
}

function onCatDragEnd() {
    draggingCatIndex.value = null;
    dragOverCatIndex.value = null;
}
</script>

<template>
    <div class="clips-list">
        <div class="clips-list-header">
            <div class="search-box w-100">
                <i class="fas fa-search"></i>
                <input
                    v-model="searchQuery"
                    type="text"
                    class="form-control"
                    placeholder="Buscar clips..."
                />
            </div>
        </div>

        <div v-if="clipsStore.isLoading" class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i>
            <p class="text-muted mt-2">Cargando clips...</p>
        </div>

        <div v-else-if="categoriesWithClips.length === 0" class="empty-state">
            <i class="fas fa-film"></i>
            <p>{{ searchQuery ? 'No se encontraron clips' : 'No hay clips todavía' }}</p>
            <small class="text-muted">
                {{ searchQuery ? 'Intenta con otro término de búsqueda' : 'Crea clips usando los botones de categoría' }}
            </small>
        </div>

        <div v-else class="clips-accordion">
            <div
                v-for="(category, catIndex) in categoriesWithClips"
                :key="category.id"
                class="category-group"
                :class="{
                    'cat-drag-over': dragOverCatIndex === catIndex,
                    'cat-dragging': draggingCatIndex === catIndex,
                }"
                @dragover.prevent="onCatDragOver(catIndex)"
                @drop="onCatDrop(catIndex)"
                @dragend="onCatDragEnd"
            >
                <!-- Header completo draggable — click = toggle, drag = reordenar -->
                <div
                    class="category-header"
                    :style="{ '--category-color': category.color }"
                    draggable="true"
                    @dragstart="onCatDragStart($event, catIndex)"
                    @click="toggleCategory(category.id)"
                >
                    <div class="category-info">
                        <i v-if="category.icon" :class="category.icon" class="category-icon"></i>
                        <span class="category-name">{{ category.name }}</span>
                        <span class="clips-count-badge">{{ getCategoryClipsCount(category) }}</span>
                    </div>

                    <div class="category-actions">
                        <i
                            :class="['fas', isCategoryExpanded(category.id) ? 'fa-chevron-up' : 'fa-chevron-down']"
                            class="toggle-icon"
                        ></i>
                    </div>
                </div>

                <div v-if="isCategoryExpanded(category.id)" class="category-clips">
                    <ClipItem
                        v-for="clip in filteredClipsByCategory[category.id]"
                        :key="clip.id"
                        :clip="clip"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.clips-list {
    display: flex;
    flex-direction: column;
    height: 100%;
    font-size: 10.5px;
}

.clips-list-header {
    padding: 0.35rem 0.5rem 0.25rem;
    border-bottom: 1px solid #333;
}

.clips-list-header i {
    color: var(--color-accent);
}

.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #777;
    font-size: 0.875rem;
}

.search-box .form-control {
    padding-left: 2rem;
    background-color: #252525;
    border: 1px solid #333;
    color: #fff;
    font-size: 0.72rem;
    height: 30px;
    padding-top: 0.3rem;
    padding-bottom: 0.3rem;
}

.search-box .form-control:focus {
    background-color: #2a2a2a;
    border-color: var(--color-accent);
    box-shadow: none;
    color: #fff;
}

.search-box .form-control::placeholder {
    color: #777;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    text-align: center;
    color: #777;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-state p {
    margin-bottom: 0.5rem;
    color: #ccc;
}

.clips-accordion {
    flex: 1;
    overflow-y: auto;
    padding: 0.25rem;
}

.category-group {
    margin-bottom: 0.35rem;
    background-color: #1a1a1a;
    border-radius: 3px;
    overflow: hidden;
}

.cat-drag-over {
    outline: 1px solid var(--color-accent, #00B7B5);
    border-radius: 3px;
}

.cat-dragging {
    opacity: 0.4;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.38rem 0.45rem;
    background-color: #252525;
    border-left: 3px solid var(--category-color, var(--color-accent));
    cursor: grab;
    user-select: none;
    transition: background-color 0.2s;
}

.category-header:hover {
    background-color: #2a2a2a;
}

.category-header:active {
    cursor: grabbing;
}

.category-info {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex: 1;
    min-width: 0;
}

.category-actions {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    flex-shrink: 0;
}

.category-icon {
    color: var(--category-color, var(--color-accent));
    font-size: 10.5px;
}

.category-name {
    color: #fff;
    font-weight: 600;
    font-size: 0.7rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.clips-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 16px;
    height: 16px;
    padding: 0 0.3rem;
    background-color: var(--category-color, var(--color-accent));
    border-radius: 11px;
    font-size: 0.65rem;
    font-weight: 700;
    color: #0f0f0f;
    flex-shrink: 0;
}

.toggle-icon {
    color: #777;
    font-size: 0.875rem;
    transition: transform 0.2s;
}

.category-clips {
    padding: 0.25rem 0.35rem 0.15rem;
}

/* Scrollbar styling */
.clips-accordion::-webkit-scrollbar {
    width: 6px;
}

.clips-accordion::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.clips-accordion::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 3px;
}

.clips-accordion::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
