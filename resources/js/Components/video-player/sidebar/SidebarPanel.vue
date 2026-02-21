<script setup lang="ts">
import { ref, computed } from 'vue';
import SidebarTabs from './SidebarTabs.vue';

type TabType = 'comments' | 'clips';

const props = withDefaults(
    defineProps<{
        initialTab?: TabType;
        commentCount?: number;
        clipCount?: number;
        canCreateClips?: boolean;
    }>(),
    {
        initialTab: 'clips',
    }
);

// Si no hay clips Y no puede crear clips, empezar en comentarios
// Si puede crear clips, empezar en el initialTab aunque no haya clips
const initialActiveTab = (props.canCreateClips || (props.clipCount ?? 0) > 0) ? props.initialTab : 'comments';
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
            :can-create-clips="canCreateClips"
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

/* Scrollbar styling — colores RugbyKP */
.tab-pane::-webkit-scrollbar {
    width: 4px;
}

.tab-pane::-webkit-scrollbar-track {
    background: transparent;
}

.tab-pane::-webkit-scrollbar-thumb {
    background: rgba(0, 183, 181, 0.35);
    border-radius: 4px;
}

.tab-pane::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 183, 181, 0.75);
}

/* Firefox */
.tab-pane {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 183, 181, 0.35) transparent;
}
</style>
