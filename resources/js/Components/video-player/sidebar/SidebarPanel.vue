<script setup lang="ts">
import { ref, computed } from 'vue';
import SidebarTabs from './SidebarTabs.vue';

type TabType = 'comments' | 'clips';

const props = withDefaults(
    defineProps<{
        initialTab?: TabType;
        commentCount?: number;
        clipCount?: number;
    }>(),
    {
        initialTab: 'clips',
    }
);

// Si no hay clips, siempre empezar en comentarios
const initialActiveTab = (props.clipCount ?? 0) > 0 ? props.initialTab : 'comments';
const activeTab = ref<TabType>(initialActiveTab);

function handleTabChange(tab: TabType) {
    activeTab.value = tab;
}
</script>

<template>
    <div class="sidebar-panel">
        <SidebarTabs
            :active-tab="activeTab"
            :comment-count="commentCount"
            :clip-count="clipCount"
            @tab-change="handleTabChange"
        />

        <div class="sidebar-content">
            <div v-show="activeTab === 'comments'" class="tab-pane">
                <slot name="comments"></slot>
            </div>

            <div v-show="activeTab === 'clips'" class="tab-pane">
                <slot name="clips"></slot>
            </div>
        </div>
    </div>
</template>

<style scoped>
.sidebar-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
    background-color: #1a1a1a;
    border-radius: 8px;
    overflow: hidden;
}

.sidebar-content {
    flex: 1;
    overflow: hidden;
    position: relative;
}

.tab-pane {
    height: 100%;
    overflow-y: auto;
}

/* Scrollbar styling */
.tab-pane::-webkit-scrollbar {
    width: 6px;
}

.tab-pane::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.tab-pane::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 3px;
}

.tab-pane::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
