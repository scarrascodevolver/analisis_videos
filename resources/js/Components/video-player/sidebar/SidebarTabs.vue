<script setup lang="ts">
import { computed } from 'vue';

type TabType = 'comments' | 'clips';

const props = defineProps<{
    activeTab: TabType;
    commentCount?: number;
    clipCount?: number;
}>();

const emit = defineEmits<{
    tabChange: [tab: TabType];
}>();

function selectTab(tab: TabType) {
    if (tab !== props.activeTab) {
        emit('tabChange', tab);
    }
}

const commentsLabel = computed(() => props.commentCount ?? 0);
const clipsLabel = computed(() => props.clipCount ?? 0);
</script>

<template>
    <div class="sidebar-tabs">
        <button
            class="tab-button"
            :class="{ active: activeTab === 'comments' }"
            @click="selectTab('comments')"
        >
            <i class="fas fa-comment-dots"></i>
            <span class="tab-count">{{ commentsLabel }}</span>
        </button>

        <button
            class="tab-button"
            :class="{ active: activeTab === 'clips' }"
            @click="selectTab('clips')"
        >
            <i class="fas fa-cut"></i>
            <span class="tab-count">{{ clipsLabel }}</span>
        </button>
    </div>
</template>

<style scoped>
.sidebar-tabs {
    display: flex;
    gap: 0;
    padding: 0;
    background-color: #1a1a1a;
    border-bottom: none;
    border-radius: 0;
    overflow: hidden;
}

.tab-button {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    padding: 0.5rem 0.4rem;
    background-color: #252525;
    border: none;
    border-right: none;
    color: #888;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    border-radius: 0;
}

.tab-button:last-child {
    border-right: none;
}

.tab-button:hover {
    background-color: #2a2a2a;
    color: #ccc;
}

.tab-button.active {
    background-color: #005461;
    color: #fff;
    border-radius: 0;
}

.tab-button i {
    font-size: 1rem;
    flex-shrink: 0;
}

.tab-button .tab-count {
    font-size: 0.78rem;
    font-weight: 700;
    color: #ccc;
}

@media (max-width: 1400px) {
    .tab-button {
        font-size: 0.72rem;
        padding: 0.45rem 0.35rem;
        gap: 0.2rem;
    }

    .tab-button i {
        font-size: 0.95rem;
    }
}

@media (max-width: 1200px) {
    .tab-button span {
        max-width: 60px;
    }
}

@media (max-width: 991px) {
    .tab-button {
        flex-direction: column;
        gap: 0.2rem;
        padding: 0.45rem 0.25rem;
    }

    .tab-button .tab-count {
        font-size: 0.7rem;
        max-width: none;
    }
}

@media (max-width: 768px) {
    .tab-button {
        padding: 0.75rem 0.5rem;
    }

    .tab-button i {
        font-size: 1.1rem;
    }
}
</style>
