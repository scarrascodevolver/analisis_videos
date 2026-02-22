<script setup lang="ts">
import { ref, computed, inject } from 'vue';
import { useClipsStore } from '@/stores/clipsStore';
import ClipItem from './ClipItem.vue';
import type { ClipCategory } from '@/types/video-player';

const clipsStore = useClipsStore();
const searchQuery = ref('');
const expandedCategories = ref<Set<number>>(new Set());
const sharingCategory = ref<number | null>(null);

const currentUserId = inject<number>('currentUserId', 0);
const toast = inject<any>('toast');

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

const categoriesWithClips = computed(() => {
    const safeCategories = Array.isArray(clipsStore.categories) ? clipsStore.categories : [];
    return safeCategories
        .filter((cat) => filteredClipsByCategory.value[cat.id]?.length > 0)
        .sort((a, b) => a.sort_order - b.sort_order);
});

// Estado de sharing por categoría: 'shared' | 'private' | 'none' (sin clips propios)
const categoryShareState = computed(() => {
    const state: Record<number, 'shared' | 'private' | 'none'> = {};

    Object.entries(clipsStore.clipsByCategory).forEach(([catIdStr, clips]) => {
        const catId = Number(catIdStr);
        const ownClips = clips.filter(
            (c) => c.created_by === currentUserId && c.category?.scope !== 'video'
        );

        if (ownClips.length === 0) {
            state[catId] = 'none';
        } else if (ownClips.every((c) => c.is_shared)) {
            state[catId] = 'shared';
        } else {
            state[catId] = 'private';
        }
    });

    return state;
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

async function handleToggleCategoryShare(event: MouseEvent, categoryId: number, videoId: number) {
    event.stopPropagation(); // No colapsar el acordeón
    if (sharingCategory.value === categoryId) return;

    sharingCategory.value = categoryId;
    try {
        const result = await clipsStore.toggleCategoryShare(videoId, categoryId, currentUserId);
        toast?.success(result.message);
    } catch {
        toast?.error('Error al cambiar visibilidad de la categoría');
    } finally {
        sharingCategory.value = null;
    }
}

// Expand all categories by default
const safeCategories = Array.isArray(clipsStore.categories) ? clipsStore.categories : [];
safeCategories.forEach((cat) => {
    if (clipsStore.clipsByCategory[cat.id]?.length > 0) {
        expandedCategories.value.add(cat.id);
    }
});
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
                v-for="category in categoriesWithClips"
                :key="category.id"
                class="category-group"
            >
                <div
                    class="category-header"
                    :style="{ '--category-color': category.color }"
                    @click="toggleCategory(category.id)"
                >
                    <div class="category-info">
                        <i v-if="category.icon" :class="category.icon" class="category-icon"></i>
                        <span class="category-name">{{ category.name }}</span>
                        <span class="clips-count-badge">{{ getCategoryClipsCount(category) }}</span>
                    </div>

                    <div class="category-actions">
                        <!-- Botón compartir/privatizar categoría (solo si tengo clips propios en ella) -->
                        <button
                            v-if="categoryShareState[category.id] !== 'none'"
                            class="btn-share-category"
                            :class="{
                                'is-shared': categoryShareState[category.id] === 'shared',
                                'is-loading': sharingCategory === category.id
                            }"
                            :title="categoryShareState[category.id] === 'shared'
                                ? 'Categoría compartida — clic para privatizar'
                                : 'Categoría privada — clic para compartir con el equipo'"
                            @click="handleToggleCategoryShare($event, category.id, clipsStore.clips.find(c => c.clip_category_id === category.id)?.video_id ?? 0)"
                        >
                            <i
                                :class="sharingCategory === category.id
                                    ? 'fas fa-spinner fa-spin'
                                    : categoryShareState[category.id] === 'shared'
                                        ? 'fas fa-users'
                                        : 'fas fa-lock'"
                            ></i>
                        </button>

                        <i
                            :class="['fas', isCategoryExpanded(category.id) ? 'fa-chevron-up' : 'fa-chevron-down']"
                            class="toggle-icon"
                        ></i>
                    </div>
                </div>

                <div v-show="isCategoryExpanded(category.id)" class="category-clips">
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
    color: #00B7B5;
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
    border-color: #00B7B5;
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

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.38rem 0.45rem;
    background-color: #252525;
    border-left: 3px solid var(--category-color, #00B7B5);
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
}

.category-header:hover {
    background-color: #2a2a2a;
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
    color: var(--category-color, #00B7B5);
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
    background-color: var(--category-color, #00B7B5);
    border-radius: 11px;
    font-size: 0.65rem;
    font-weight: 700;
    color: #0f0f0f;
    flex-shrink: 0;
}

/* Botón compartir categoría */
.btn-share-category {
    background: transparent;
    border: none;
    padding: 0.15rem 0.3rem;
    border-radius: 3px;
    cursor: pointer;
    font-size: 9px;
    line-height: 1;
    transition: all 0.15s;
    color: #555;
}

.btn-share-category:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #aaa;
}

/* Estado: compartida → teal */
.btn-share-category.is-shared {
    color: #00B7B5;
}

.btn-share-category.is-shared:hover {
    color: #fff;
    background: rgba(0, 183, 181, 0.15);
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
