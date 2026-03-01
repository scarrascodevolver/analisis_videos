<script setup lang="ts">
import { onMounted, onUnmounted, computed, ref, provide, watch, onBeforeUnmount, useSlots } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';
import { useVideoApi } from '@/composables/useVideoApi';
import { useToast } from '@/composables/useToast';
import type { Video, VideoComment, User } from '@/types/video-player';
import VideoElement from './VideoElement.vue';
import VideoHeader from './VideoHeader.vue';
import VideoInfo from './VideoInfo.vue';
import ToastContainer from './ui/ToastContainer.vue';
import PanelOrderWrapper from './ui/PanelOrderWrapper.vue';

const props = defineProps<{
    video: Video;
    comments: VideoComment[];
    allUsers: Pick<User, 'id' | 'name' | 'role'>[];
    user: User;
    hasSlaves?: boolean;
}>();

const emit = defineEmits<{
    showStats: [];
    deleteVideo: [];
    uploadAngle: [];
    toggleTimelines: [];
    showLineup: [];
}>();

const videoStore = useVideoStore();
const annotationsStore = useAnnotationsStore();
const api = useVideoApi(props.video.id);
const toast = useToast();

provide('videoApi', api);
provide('toast', toast);
provide('allUsers', props.allUsers);
provide('currentUserId', props.user.id);

const isAnalystOrCoach = computed(() =>
    ['analista', 'entrenador'].includes(props.user.role)
);

const hasMultiCamera = computed(() =>
    !!props.video.is_part_of_group && isAnalystOrCoach.value && !!props.hasSlaves
);

const showComments = ref(true);
const isTheaterMode = ref(false);

// Colapsar el sidebar IZQUIERDO de AdminLTE al primer play.
// Una vez colapsado, no vuelve automáticamente (el usuario lo expande manualmente).
let sidebarCollapsedByVideo = false;
watch(() => videoStore.isPlaying, (playing) => {
    if (playing && !sidebarCollapsedByVideo) {
        sidebarCollapsedByVideo = true;
        document.body.classList.add('sidebar-collapse');
    }
});

// ── Panel resize state ────────────────────────────────────────
const layoutRef = ref<HTMLElement | null>(null);
const masterWidthPct = ref(66);
const isDraggingPanel = ref(false);
const DIVIDER_W_PX = 12;
const MIN_MASTER_PCT = 35;
const MAX_MASTER_PCT = 88;

let _startX = 0;
let _startMasterPx = 0;

const masterPanelStyle = computed(() => ({
    flex: `0 0 ${masterWidthPct.value.toFixed(1)}%`,
    maxWidth: `${masterWidthPct.value.toFixed(1)}%`,
}));
const slavesPanelStyle = computed(() => ({
    flex: `0 0 ${(100 - masterWidthPct.value).toFixed(1)}%`,
    maxWidth: `${(100 - masterWidthPct.value).toFixed(1)}%`,
}));

function applyResize(clientX: number) {
    const layout = layoutRef.value;
    if (!layout) return;
    const layoutW = layout.getBoundingClientRect().width - DIVIDER_W_PX;
    if (layoutW <= 0) return;
    const newPct = Math.max(MIN_MASTER_PCT, Math.min(MAX_MASTER_PCT,
        ((_startMasterPx + (clientX - _startX)) / layoutW) * 100
    ));
    masterWidthPct.value = newPct;
}

function startResize(clientX: number) {
    isDraggingPanel.value = true;
    _startX = clientX;
    const masterEl = layoutRef.value?.querySelector('.master-col') as HTMLElement | null;
    _startMasterPx = masterEl?.getBoundingClientRect().width ?? 0;
    document.body.classList.add('mc-no-select');
}

function finishResize() {
    if (!isDraggingPanel.value) return;
    isDraggingPanel.value = false;
    document.body.classList.remove('mc-no-select');
    try { localStorage.setItem('rugbyhub_mc_master_width', masterWidthPct.value.toFixed(1)); } catch (_) {}
}

function onDividerMousedown(e: MouseEvent) {
    startResize(e.clientX);
    e.preventDefault();
}
function onDividerTouchstart(e: TouchEvent) {
    startResize(e.touches[0].clientX);
}
function onDocMousemove(e: MouseEvent) {
    if (isDraggingPanel.value) applyResize(e.clientX);
}
function onDocTouchmove(e: TouchEvent) {
    if (isDraggingPanel.value) { e.preventDefault(); applyResize(e.touches[0].clientX); }
}
function onDividerDblclick() {
    masterWidthPct.value = 66;
    try { localStorage.setItem('rugbyhub_mc_master_width', '66'); } catch (_) {}
    toast.info('Tamaño restablecido');
}

onMounted(() => {
    videoStore.setVideo(props.video);
    try {
        const saved = parseFloat(localStorage.getItem('rugbyhub_mc_master_width') ?? '');
        if (!isNaN(saved)) masterWidthPct.value = Math.max(MIN_MASTER_PCT, Math.min(MAX_MASTER_PCT, saved));
    } catch (_) {}
    document.addEventListener('mousemove', onDocMousemove);
    document.addEventListener('mouseup', finishResize);
    document.addEventListener('touchmove', onDocTouchmove, { passive: false });
    document.addEventListener('touchend', finishResize);
});

onUnmounted(() => {
    document.removeEventListener('mousemove', onDocMousemove);
    document.removeEventListener('mouseup', finishResize);
    document.removeEventListener('touchmove', onDocTouchmove);
    document.removeEventListener('touchend', finishResize);
    document.body.classList.remove('mc-no-select');
});

function toggleComments() {
    showComments.value = !showComments.value;
}

function handleAddComment() {
    const sidebar = document.getElementById('sidebarSection');
    if (sidebar) {
        sidebar.scrollIntoView({ behavior: 'smooth' });
    }
}

function handleToggleAnnotationMode() {
    if (annotationsStore.annotationMode) {
        annotationsStore.exitAnnotationMode();
    } else {
        annotationsStore.enterAnnotationMode();
    }
}

function toggleTheaterMode() {
    isTheaterMode.value = !isTheaterMode.value;
}

// ── Panel ordering ─────────────────────────────────────────────
type PanelName = 'timelines-sync' | 'clip-panel' | 'clip-timeline' | 'comment-timeline';

const ALL_PANELS: PanelName[] = [
    'timelines-sync', 'clip-panel', 'clip-timeline', 'comment-timeline'
];
const PANEL_ORDER_KEY = `rugbyhub_panel_order_${props.user.id}`;

const panelOrder = ref<PanelName[]>((() => {
    try {
        const saved = localStorage.getItem(PANEL_ORDER_KEY);
        if (saved) {
            const parsed = JSON.parse(saved) as PanelName[];
            if (ALL_PANELS.every(p => parsed.includes(p))) return parsed;
        }
    } catch (_) {}
    return [...ALL_PANELS];
})());

const slots = useSlots();

const visiblePanels = computed(() =>
    panelOrder.value.filter(name => !!slots[name])
);

function movePanelUp(panelName: PanelName) {
    const order = [...panelOrder.value];
    const idx = order.indexOf(panelName);
    if (idx <= 0) return;
    [order[idx - 1], order[idx]] = [order[idx], order[idx - 1]];
    panelOrder.value = order;
    try { localStorage.setItem(PANEL_ORDER_KEY, JSON.stringify(panelOrder.value)); } catch (_) {}
}

function movePanelDown(panelName: PanelName) {
    const order = [...panelOrder.value];
    const idx = order.indexOf(panelName);
    if (idx >= order.length - 1) return;
    [order[idx + 1], order[idx]] = [order[idx], order[idx + 1]];
    panelOrder.value = order;
    try { localStorage.setItem(PANEL_ORDER_KEY, JSON.stringify(panelOrder.value)); } catch (_) {}
}
</script>

<template>
    <div class="row">
        <div :class="!isTheaterMode ? 'col-lg-10' : 'col-12'" id="videoSection">
            <div class="card">
                <VideoHeader
                    :video="video"
                    :user="user"
                    :is-theater-mode="isTheaterMode"
                    @show-stats="$emit('showStats')"
                    @upload-angle="$emit('uploadAngle')"
                    @toggle-comments="toggleComments"
                    @toggle-timelines="$emit('toggleTimelines')"
                    @delete-video="$emit('deleteVideo')"
                    @toggle-theater="toggleTheaterMode"
                    @show-lineup="$emit('showLineup')"
                />

                <div class="card-body p-0">
                    <div
                        ref="layoutRef"
                        :class="[hasMultiCamera ? 'multi-cam-wrapper' : '', { 'theater-mode': isTheaterMode }]"
                    >
                        <div
                            :class="[hasMultiCamera ? 'master-col' : 'single-col', { 'no-transition': isDraggingPanel }]"
                            :style="hasMultiCamera ? masterPanelStyle : {}"
                        >
                            <VideoElement
                                :stream-url="video.stream_url"
                                :download-url="(video as any).download_url ?? null"
                                :title="video.title"
                                :can-annotate="isAnalystOrCoach"
                                :bunny-hls-url="video.bunny_hls_url"
                                :bunny-status="video.bunny_status"
                                :bunny-mp4-url="video.bunny_mp4_url"
                                :is-youtube-video="(video as any).is_youtube_video ?? false"
                                :youtube-video-id="(video as any).youtube_video_id ?? null"
                                @add-comment="handleAddComment"
                                @toggle-annotation-mode="handleToggleAnnotationMode"
                            >
                                <template #annotation-canvas>
                                    <slot name="annotation-canvas" />
                                </template>
                                <template #annotation-toolbar>
                                    <slot name="annotation-toolbar" />
                                </template>
                            </VideoElement>
                        </div>

                        <!-- Drag divider (Hudl / Sportscode pattern) -->
                        <div
                            v-if="hasMultiCamera"
                            :class="['mc-divider', { 'mc-dragging': isDraggingPanel }]"
                            title="Arrastrar para redimensionar · Doble clic para restablecer"
                            @mousedown.prevent="onDividerMousedown"
                            @touchstart.prevent="onDividerTouchstart"
                            @dblclick="onDividerDblclick"
                        >
                            <i class="fas fa-grip-vertical mc-divider-handle"></i>
                        </div>

                        <div
                            v-if="hasMultiCamera"
                            :class="['slaves-col', { 'no-transition': isDraggingPanel }]"
                            :style="hasMultiCamera ? slavesPanelStyle : {}"
                        >
                            <slot name="multi-camera" />
                        </div>
                    </div>

                    <template v-for="(panelName, index) in visiblePanels" :key="panelName">
                        <PanelOrderWrapper
                            :can-move-up="index > 0"
                            :can-move-down="index < visiblePanels.length - 1"
                            @move-up="movePanelUp(panelName)"
                            @move-down="movePanelDown(panelName)"
                        >
                            <slot :name="panelName" />
                        </PanelOrderWrapper>
                    </template>
                </div>
            </div>

            <VideoInfo :video="video" />
        </div>

        <div v-if="!isTheaterMode" class="col-lg-2 sidebar-col" id="sidebarSection">
            <slot name="sidebar" />
        </div>
    </div>

    <slot name="modals" />

    <ToastContainer />
</template>

<style scoped>
/* Sidebar sticky — queda fijo mientras el contenido principal scrollea */
.sidebar-col {
    position: sticky;
    top: 0.75rem;
    height: calc(100vh - 80px);
    overflow: hidden;
    align-self: flex-start;
}

/* Video único (sin ángulos) — altura fija, video centrado horizontalmente */
.single-col :deep(.video-container) {
    aspect-ratio: unset !important;
    height: 65vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #000;
}

.single-col :deep(.video-wrapper) {
    width: auto !important;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* YouTube: el div contenedor no tiene ratio intrínseco, necesita ancho explícito */
.single-col :deep(.video-container.is-youtube .video-wrapper) {
    width: 100% !important;
}

.single-col :deep(video) {
    width: auto !important;
    height: 100% !important;
    max-width: 100%;
}

/* Theater mode: más altura */
.theater-mode .single-col :deep(.video-container) {
    height: 82vh;
}

/* Multi-camera side-by-side layout (matches Blade's activateSideBySideLayout) */
.multi-cam-wrapper {
    display: flex;
    height: 60vh;
    align-items: center;
    overflow: hidden;
    transition: height 0.3s ease;
}

/* Theater mode: more height and better ratio for slaves */
.multi-cam-wrapper.theater-mode {
    height: 80vh;
}

.master-col {
    flex: 0 0 66.666%;
    max-width: 66.666%;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    transition: flex 0.3s ease, max-width 0.3s ease;
}

/* Theater mode: reduce master to give more space to slaves */
.multi-cam-wrapper.theater-mode .master-col {
    flex: 0 0 55%;
    max-width: 55%;
}

.master-col :deep(.video-container) {
    /* Width drives the size: when column grows, video grows */
    width: 100%;
    height: auto;
    max-height: 60vh;
    border-radius: 0;
}

.master-col :deep(.video-wrapper) {
    width: 100%;
    height: auto;
    display: block;
}

.master-col :deep(video) {
    width: 100% !important;
    height: auto !important;
    max-height: 60vh !important;
    display: block;
}

/* Theater mode: allow taller video */
.multi-cam-wrapper.theater-mode .master-col :deep(.video-container) {
    max-height: 80vh;
}

.multi-cam-wrapper.theater-mode .master-col :deep(video) {
    max-height: 80vh !important;
}

.slaves-col {
    flex: 0 0 33.333%;
    max-width: 33.333%;
    overflow-y: auto;
    max-height: 60vh;
    display: flex;
    flex-direction: column;
    transition: flex 0.3s ease, max-width 0.3s ease, max-height 0.3s ease;
}

/* Theater mode: increase slaves size */
.multi-cam-wrapper.theater-mode .slaves-col {
    flex: 0 0 45%;
    max-width: 45%;
    max-height: 80vh;
}

/* Drag divider — Hudl / Sportscode pattern */
.mc-divider {
    flex: 0 0 12px;
    width: 12px;
    background: #2a2a2a;          /* Always visible strip */
    border-left: 1px solid #444;  /* Visible separator line on master side */
    border-right: 1px solid #222;
    cursor: ew-resize;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s ease, border-color 0.15s ease;
    z-index: 20;
    position: relative;
    user-select: none;
}

.mc-divider:hover,
.mc-divider.mc-dragging {
    background: #005461;
    border-left-color: var(--color-accent);
    border-right-color: var(--color-accent);
}

.mc-divider-handle {
    pointer-events: none;
    color: rgba(255, 255, 255, 0.5);
    font-size: 14px;
    transition: color 0.15s;
    line-height: 1;
}

.mc-divider:hover .mc-divider-handle,
.mc-divider.mc-dragging .mc-divider-handle {
    color: #fff;
}

/* Disable transitions during drag for smooth real-time response */
.master-col.no-transition,
.slaves-col.no-transition {
    transition: none !important;
}

/* Durante el drag: los iframes capturan mousemove/mouseup y bloquean el resize.
   pointer-events:none hace que el cursor "atraviese" el iframe hacia el document. */
:global(body.mc-no-select) iframe {
    pointer-events: none !important;
}

:global(body.mc-no-select) {
    user-select: none !important;
    -webkit-user-select: none !important;
}

@media (max-width: 991px) {
    .master-col {
        flex: 0 0 58.333%;
        max-width: 58.333%;
    }
    .slaves-col {
        flex: 0 0 41.666%;
        max-width: 41.666%;
    }
}

@media (max-width: 768px) {
    .multi-cam-wrapper {
        flex-direction: column;
        height: auto;
    }
    .master-col,
    .slaves-col {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .slaves-col {
        max-height: 40vh;
    }
}
</style>
