<template>
    <div class="clip-timeline-panel">
        <!-- Collapsible Header -->
        <div
            class="timeline-header"
            @click="toggleCollapsed"
            role="button"
            tabindex="0"
            @keydown.enter="toggleCollapsed"
        >
            <div class="header-content">
                <i class="fas fa-cut text-warning mr-2"></i>
                <span class="header-title">Clips</span>
            </div>
            <i class="fas" :class="isCollapsed ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
        </div>

        <!-- Timeline Content -->
        <transition name="slide-down">
            <div v-if="!isCollapsed" class="timeline-content">
                <!-- Timeline Container -->
                <div class="timeline-container" ref="timelineContainerRef">
                    <!-- Category Lanes -->
                    <div
                        v-for="category in activeCategories"
                        :key="category.id"
                        class="timeline-lane"
                    >
                        <!-- Lane Label -->
                        <div class="lane-label" :style="{ borderLeftColor: category.color }">
                            <span class="category-name" :title="category.name">
                                {{ category.name }}
                            </span>
                        </div>

                        <!-- Lane Track – height is dynamic based on stagger rows -->
                        <div
                            class="lane-track"
                            :style="{ height: getLaneHeight(category.id) + 'px' }"
                            :ref="(el) => setTrackRef(el as HTMLElement | null, category.id)"
                            @click="handleLaneClick($event, category.id)"
                        >
                            <!-- Clip Blocks -->
                            <div
                                v-for="clip in getClipsForCategory(category.id)"
                                :key="clip.id"
                                class="clip-block"
                                :class="{ 'is-dragging': dragState?.clip.id === clip.id }"
                                :style="clipBlockStyles.get(clip.id)"
                                :title="getClipTooltip(clip)"
                                @mousedown.stop="onClipMousedown(clip, 'move', $event, category.id)"
                                @click.stop="handleClipClick(clip)"
                            >
                                <!-- Left resize handle -->
                                <div
                                    class="resize-handle resize-handle-left"
                                    @mousedown.stop="onClipMousedown(clip, 'resize-left', $event, category.id)"
                                ></div>
                                <!-- Right resize handle -->
                                <div
                                    class="resize-handle resize-handle-right"
                                    @mousedown.stop="onClipMousedown(clip, 'resize-right', $event, category.id)"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Playhead -->
                    <div class="playhead" :style="{ left: playheadPosition }"></div>
                </div>

                <!-- Drag time tooltip -->
                <div v-if="dragState" class="drag-tooltip">
                    <i class="fas fa-clock mr-1"></i>
                    {{ formatTime(dragState.previewStart + currentOffset) }}
                    –
                    {{ formatTime(dragState.previewEnd + currentOffset) }}
                    <span class="drag-duration">
                        ({{ (dragState.previewEnd - dragState.previewStart).toFixed(1) }}s)
                    </span>
                </div>

                <!-- Time Scale -->
                <div class="time-scale">
                    <div class="time-scale-label"></div>
                    <div class="time-scale-track">
                        <span class="time-marker">0:00</span>
                        <span class="time-marker">{{ formatTime(duration * 0.25) }}</span>
                        <span class="time-marker">{{ formatTime(duration * 0.5) }}</span>
                        <span class="time-marker">{{ formatTime(duration * 0.75) }}</span>
                        <span class="time-marker">{{ formattedDuration }}</span>
                    </div>
                </div>

                <!-- Help Message -->
                <div class="help-message">
                    <i class="fas fa-lightbulb text-warning"></i>
                    <strong>Cómo usar:</strong><br>
                    • <strong>Click en un clip</strong> para reproducirlo desde ese momento<br>
                    • <strong>Arrastrá los bordes ◄ ►</strong> para ajustar inicio/fin<br>
                    • <strong>Arrastrá el centro</strong> para mover el clip en el tiempo<br>
                    • <strong>Click en la barra</strong> para saltar a ese momento del video
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useVideoStore, formatTime } from '@/stores/videoStore';
import { useClipsStore } from '@/stores/clipsStore';
import type { VideoClip } from '@/types/video-player';
// No external deps needed — rAF throttle inline for drag

const props = defineProps<{
    videoId: number;
}>();

const videoStore = useVideoStore();
const clipsStore = useClipsStore();

const isCollapsed = ref(false);
const timelineContainerRef = ref<HTMLElement | null>(null);

// ─── Basic computed ───────────────────────────────────────────────────────────
const activeCategories = computed(() => clipsStore.activeCategories);
const currentTime      = computed(() => videoStore.currentTime);
const duration         = computed(() => videoStore.duration || 1);
const formattedDuration = computed(() => videoStore.formattedDuration);
const currentOffset    = computed(() => Number(videoStore.video?.timeline_offset || 0));

const playheadPosition = computed(() => {
    if (!duration.value) return '0%';
    const percent = (currentTime.value / duration.value) * 100;
    return `calc(110px + (100% - 110px) * ${percent / 100})`;
});

function toggleCollapsed(event?: MouseEvent | KeyboardEvent) {
    isCollapsed.value = !isCollapsed.value;
    // Blur para que el header no retenga foco y no intercepte hotkeys posteriores
    (event?.currentTarget as HTMLElement)?.blur();
}

function getClipsForCategory(categoryId: number): VideoClip[] {
    return clipsStore.clipsByCategory[categoryId] || [];
}

// ─── Stagger rows ─────────────────────────────────────────────────────────────
// When clips overlap in time, assign them to different vertical sub-rows
// so all clips are visible and clickable.

const ROW_HEIGHT = 30; // px: visible clip height (26px) + 4px gap
// Minimum gap in seconds between clips to be in the same row
const GAP_THRESHOLD = 0.3;

// Compute row index for each clip in a category (clips already sorted by start_time)
function computeClipRows(categoryId: number): Map<number, number> {
    const clips = getClipsForCategory(categoryId);
    const rowEndTimes: number[] = []; // last end_time in each row
    const result = new Map<number, number>();

    for (const clip of clips) {
        const start = parseFloat(clip.start_time as any) || 0;
        const end   = parseFloat(clip.end_time   as any) || 0;

        // Find first row where previous clip ends before this one starts (with gap)
        let rowIdx = rowEndTimes.findIndex(rowEnd => rowEnd + GAP_THRESHOLD <= start);
        if (rowIdx === -1) {
            rowIdx = rowEndTimes.length; // new row needed
            rowEndTimes.push(end);
        } else {
            rowEndTimes[rowIdx] = Math.max(rowEndTimes[rowIdx], end);
        }
        result.set(clip.id, rowIdx);
    }

    return result;
}

// Cached stagger maps, recomputed when clips change
const staggerCache = computed(() => {
    const cache = new Map<number, Map<number, number>>();
    for (const category of activeCategories.value) {
        cache.set(category.id, computeClipRows(category.id));
    }
    return cache;
});

function getLaneHeight(categoryId: number): number {
    const rowMap = staggerCache.value.get(categoryId);
    if (!rowMap || rowMap.size === 0) return 32;
    const maxRow = Math.max(...rowMap.values());
    return Math.max(32, (maxRow + 1) * ROW_HEIGHT + 4);
}

// ─── Track element refs (needed for pixel→time conversion during drag) ────────
const trackRefs = new Map<number, HTMLElement>();

function setTrackRef(el: HTMLElement | null, categoryId: number) {
    if (el) trackRefs.set(categoryId, el);
    else     trackRefs.delete(categoryId);
}

// ─── Drag state ───────────────────────────────────────────────────────────────
type DragType = 'move' | 'resize-left' | 'resize-right';

interface DragState {
    clip:          VideoClip;
    type:          DragType;
    startX:        number;
    originalStart: number;
    originalEnd:   number;
    trackWidth:    number;
    previewStart:  number;
    previewEnd:    number;
    didMove:       boolean;
}

const dragState   = ref<DragState | null>(null);
const wasDragging = ref(false); // blocks click handler after a real drag

// rAF throttle state for mousemove
let pendingDragEvent: MouseEvent | null = null;
let dragRafId: number | null = null;

function onClipMousedown(
    clip:       VideoClip,
    type:       DragType,
    event:      MouseEvent,
    categoryId: number,
) {
    // Avoid triggering 'move' drag when user clicked a resize handle
    if (
        type === 'move' &&
        (event.target as HTMLElement).classList.contains('resize-handle')
    ) return;

    const track = trackRefs.get(categoryId);
    if (!track) return;

    const rawStart   = parseFloat(clip.start_time as any) || 0;
    const rawEnd     = parseFloat(clip.end_time   as any) || 0;
    const trackWidth = track.getBoundingClientRect().width;

    dragState.value = {
        clip,
        type,
        startX:        event.clientX,
        originalStart: rawStart,
        originalEnd:   rawEnd,
        trackWidth,
        previewStart:  rawStart,
        previewEnd:    rawEnd,
        didMove:       false,
    };

    document.addEventListener('mousemove', onMousemove);
    document.addEventListener('mouseup',   onMouseup);
    document.body.style.cursor     = type === 'move' ? 'grabbing' : 'ew-resize';
    document.body.style.userSelect = 'none';
}

function processDrag(event: MouseEvent) {
    if (!dragState.value || !duration.value) return;

    const { type, startX, originalStart, originalEnd, trackWidth } = dragState.value;
    const deltaX       = event.clientX - startX;
    const secPerPixel  = duration.value / trackWidth;
    const deltaSeconds = deltaX * secPerPixel;
    const MIN_DUR      = 0.5;

    if (Math.abs(deltaX) > 3) dragState.value.didMove = true;

    let newStart = originalStart;
    let newEnd   = originalEnd;

    if (type === 'move') {
        const clipDur = originalEnd - originalStart;
        newStart = Math.max(0, Math.min(duration.value - clipDur, originalStart + deltaSeconds));
        newEnd   = newStart + clipDur;
    } else if (type === 'resize-left') {
        newStart = Math.max(0, Math.min(originalEnd - MIN_DUR, originalStart + deltaSeconds));
    } else {
        newEnd = Math.max(originalStart + MIN_DUR, Math.min(duration.value, originalEnd + deltaSeconds));
    }

    dragState.value.previewStart = newStart;
    dragState.value.previewEnd   = newEnd;
}

// rAF-throttled mousemove: accumulate event, process once per browser frame
function onMousemove(event: MouseEvent) {
    if (!dragState.value) return;
    pendingDragEvent = event;
    if (dragRafId === null) {
        dragRafId = requestAnimationFrame(() => {
            dragRafId = null;
            if (pendingDragEvent) {
                processDrag(pendingDragEvent);
                pendingDragEvent = null;
            }
        });
    }
}

async function onMouseup() {
    document.removeEventListener('mousemove', onMousemove);
    document.removeEventListener('mouseup',   onMouseup);
    document.body.style.cursor     = '';
    document.body.style.userSelect = '';

    // Flush any pending RAF frame so final position is applied before saving
    if (dragRafId !== null) {
        cancelAnimationFrame(dragRafId);
        dragRafId = null;
    }
    if (pendingDragEvent && dragState.value) {
        processDrag(pendingDragEvent);
        pendingDragEvent = null;
    }

    if (!dragState.value) return;

    const { clip, previewStart, previewEnd, didMove } = dragState.value;
    dragState.value = null;

    if (!didMove) return; // was just a click – handleClipClick will fire

    // Prevent the subsequent click event from seeking after a drag
    wasDragging.value = true;
    setTimeout(() => { wasDragging.value = false; }, 50);

    // Persist new times to API + local store
    try {
        await clipsStore.updateClip(props.videoId, clip.id, {
            start_time: parseFloat(previewStart.toFixed(2)) as any,
            end_time:   parseFloat(previewEnd.toFixed(2))   as any,
        });
    } catch (e) {
        console.error('Failed to save clip times after drag:', e);
    }
}

// ─── Clip styles — memoized computed (recalculates only when clips/duration/drag changes) ─────
// Replaces getClipBlockStyle() function called per-clip per-frame (O(N*60fps) → O(N) on change)
const clipBlockStyles = computed(() => {
    const styleMap = new Map<number, Record<string, string | number>>();

    for (const category of activeCategories.value) {
        const clips = clipsStore.clipsByCategory[category.id] ?? [];
        for (const clip of clips) {
            let rawStart = parseFloat(clip.start_time as any) || 0;
            let rawEnd   = parseFloat(clip.end_time   as any) || 0;

            if (dragState.value?.clip.id === clip.id) {
                rawStart = dragState.value.previewStart;
                rawEnd   = dragState.value.previewEnd;
            }

            const adjustedStart = Math.max(0, rawStart + currentOffset.value);
            const adjustedEnd   = rawEnd + currentOffset.value;
            const dur = duration.value;

            if (!dur || dur < 1 || adjustedStart >= dur || adjustedEnd <= 0) {
                styleMap.set(clip.id, { display: 'none' });
                continue;
            }

            const startPercent = (adjustedStart / dur) * 100;
            const widthPercent = ((adjustedEnd - adjustedStart) / dur) * 100;
            const color        = clip.category?.color || '#FFC300';
            const rowMap = staggerCache.value.get(category.id);
            const rowIdx = rowMap?.get(clip.id) ?? 0;
            const topPx    = rowIdx * ROW_HEIGHT + 2;
            const heightPx = ROW_HEIGHT - 4;
            const isDragged = dragState.value?.clip.id === clip.id;
            const zIdx      = isDragged ? 200 : (5 + (clip.id % 50));

            styleMap.set(clip.id, {
                left:            `${Math.max(0, Math.min(startPercent, 100))}%`,
                width:           `${Math.max(widthPercent, 0.3)}%`,
                top:             `${topPx}px`,
                height:          `${heightPx}px`,
                bottom:          'auto',
                backgroundColor: color,
                zIndex:          zIdx,
            });
        }
    }

    return styleMap;
});

// Legacy function kept for tooltip use only — NOT called per-frame
function getClipBlockStyle(clip: VideoClip, categoryId: number) {
    // Use drag-preview times for the clip being dragged
    let rawStart = parseFloat(clip.start_time as any) || 0;
    let rawEnd   = parseFloat(clip.end_time   as any) || 0;

    if (dragState.value?.clip.id === clip.id) {
        rawStart = dragState.value.previewStart;
        rawEnd   = dragState.value.previewEnd;
    }

    const adjustedStart = Math.max(0, rawStart + currentOffset.value);
    const adjustedEnd   = rawEnd + currentOffset.value;

    if (!duration.value || duration.value < 1) {
        return { display: 'none' };
    }
    if (adjustedStart >= duration.value || adjustedEnd <= 0) {
        return { display: 'none' };
    }

    const startPercent = (adjustedStart / duration.value) * 100;
    const widthPercent = ((adjustedEnd - adjustedStart) / duration.value) * 100;
    const color        = clip.category?.color || '#FFC300';

    // Stagger: which sub-row does this clip belong to?
    const rowMap = staggerCache.value.get(categoryId);
    const rowIdx = rowMap?.get(clip.id) ?? 0;
    const topPx    = rowIdx * ROW_HEIGHT + 2;
    const heightPx = ROW_HEIGHT - 4; // 2px gap top + 2px gap bottom

    const isDragged = dragState.value?.clip.id === clip.id;
    const zIndex    = isDragged ? 200 : (5 + (clip.id % 50));

    return {
        left:            `${Math.max(0, Math.min(startPercent, 100))}%`,
        width:           `${Math.max(widthPercent, 0.3)}%`,
        top:             `${topPx}px`,
        height:          `${heightPx}px`,
        bottom:          'auto',
        backgroundColor: color,
        zIndex,
    };
}

// ─── Tooltip ──────────────────────────────────────────────────────────────────
function getClipTooltip(clip: VideoClip): string {
    const rawStart    = parseFloat(clip.start_time as any) || 0;
    const rawEnd      = parseFloat(clip.end_time   as any) || 0;
    const adjStart    = rawStart + currentOffset.value;
    const adjEnd      = rawEnd   + currentOffset.value;
    const clipDur     = adjEnd - adjStart;
    const durText     = isFinite(clipDur) ? clipDur.toFixed(1) : '0.0';
    const title       = clip.title ? `${clip.title}\n` : '';
    return `${title}${formatTime(adjStart)} – ${formatTime(adjEnd)} (${durText}s)`;
}

// ─── Click to seek ────────────────────────────────────────────────────────────
function handleClipClick(clip: VideoClip) {
    if (wasDragging.value) return; // ignore click that followed a drag

    const rawStart = parseFloat(clip.start_time as any) || 0;
    const seekTime = rawStart + currentOffset.value;

    if (isFinite(seekTime) && seekTime >= 0) {
        videoStore.seek(seekTime);
        if (!videoStore.isPlaying) videoStore.play();
    }
}

function handleLaneClick(event: MouseEvent, _categoryId: number) {
    if (wasDragging.value) return;

    const target      = event.currentTarget as HTMLElement;
    const rect        = target.getBoundingClientRect();
    const clickX      = event.clientX - rect.left;
    const seekTime    = (clickX / rect.width) * duration.value;

    if (isFinite(seekTime)) videoStore.seek(seekTime);
    (document.activeElement as HTMLElement)?.blur();
}
</script>

<style scoped>
.clip-timeline-panel {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 1rem;
}

/* ── Header ───────────────────────────────────────────────────────────────── */
.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.22rem 0.4rem !important;
    background: #252525;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
    font-size: 0.68rem !important;
    line-height: 1.1;
}
.timeline-header:hover { background: #2a2a2a; }
.timeline-header:focus { outline: none; }

.header-content { display: flex; align-items: center; }

.header-title {
    color: #fff;
    font-weight: 600;
    font-size: 0.66rem !important;
}

.timeline-header i.fa-chevron-down,
.timeline-header i.fa-chevron-up {
    color: #ccc;
    font-size: 0.72rem !important;
}

/* ── Layout ───────────────────────────────────────────────────────────────── */
.timeline-content { padding: 0.45rem; }

.timeline-container {
    position: relative;
    background: #0f0f0f;
    border: 1px solid #333;
    border-radius: 4px;
    padding: 0.25rem 0;
    min-height: 60px;
}

.timeline-lane {
    display: flex;
    align-items: stretch;
    margin-bottom: 0.5rem;
}

/* ── Lane label ───────────────────────────────────────────────────────────── */
.lane-label {
    width: 110px;
    padding: 0.4rem 0.5rem;
    display: flex;
    align-items: flex-start;
    padding-top: 6px;
    border-left: 3px solid;
    background: #1a1a1a;
    flex-shrink: 0;
}

.category-name {
    color: #ccc;
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Lane track ───────────────────────────────────────────────────────────── */
.lane-track {
    position: relative;
    flex: 1;
    /* height is set inline from getLaneHeight() */
    background: #252525;
    cursor: pointer;
    border-radius: 2px;
    overflow: visible;
}
.lane-track:hover { background: #2a2a2a; }

/* ── Clip block ───────────────────────────────────────────────────────────── */
.clip-block {
    position: absolute;
    /* top / height / left / width come from getClipBlockStyle() inline style */
    border-radius: 0;
    cursor: grab;
    display: flex;
    align-items: center;
    overflow: visible;
    /* Only transition visual properties, NOT position/size (would lag during drag) */
    transition: filter 0.12s ease, opacity 0.12s ease, box-shadow 0.12s ease;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
    min-width: 8px !important;
    opacity: 0.85;
}

.clip-block:hover {
    filter: brightness(1.25);
    opacity: 1;
    z-index: 100 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.8) !important;
}

.clip-block.is-dragging {
    transition: none;       /* instant follow during drag */
    opacity: 1;
    cursor: grabbing;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.9) !important;
    filter: brightness(1.3);
    outline: 2px solid rgba(255, 255, 255, 0.4);
    outline-offset: 1px;
}

/* ── Resize handles ───────────────────────────────────────────────────────── */
.resize-handle {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 8px;
    cursor: ew-resize;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.12s ease;
}

/* Vertical grip line inside handle */
.resize-handle::after {
    content: '';
    width: 2px;
    height: 55%;
    background: rgba(255, 255, 255, 0.75);
    border-radius: 1px;
    pointer-events: none;
}

.resize-handle-left  { left:  -1px; border-radius: 2px 0 0 2px; }
.resize-handle-right { right: -1px; border-radius: 0 2px 2px 0; }

.clip-block:hover .resize-handle,
.clip-block.is-dragging .resize-handle {
    opacity: 1;
}

/* ── Drag tooltip ─────────────────────────────────────────────────────────── */
.drag-tooltip {
    display: inline-block;
    margin-top: 6px;
    padding: 3px 8px;
    background: #252525;
    border: 1px solid #444;
    border-radius: 4px;
    font-size: 11px;
    color: #FFC300;
    user-select: none;
}
.drag-duration { color: #888; margin-left: 4px; }

/* ── Playhead ─────────────────────────────────────────────────────────────── */
.playhead {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #ff0000;
    pointer-events: none;
    z-index: 10;
    transition: left 0.1s linear;
}
.playhead::before {
    content: '';
    position: absolute;
    top: -4px;
    left: -4px;
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 8px solid #ff0000;
}

/* ── Time scale ───────────────────────────────────────────────────────────── */
.time-scale {
    display: flex;
    margin-top: 0.5rem;
}
.time-scale-label  { width: 110px; flex-shrink: 0; }
.time-scale-track  {
    flex: 1;
    display: flex;
    justify-content: space-between;
    padding: 0 0.25rem;
}
.time-marker { color: #999; font-size: 11px; }

/* ── Help message ─────────────────────────────────────────────────────────── */
.help-message {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 6px;
    padding: 8px 12px;
    margin-top: 10px;
    font-size: 11px;
    color: #ccc;
    line-height: 1.6;
}
.help-message i      { margin-right: 4px; }
.help-message strong { color: #fff; }

/* ── Slide transition ─────────────────────────────────────────────────────── */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    max-height: 800px;
    overflow: hidden;
}
.slide-down-enter-from,
.slide-down-leave-to {
    max-height: 0;
    opacity: 0;
}
</style>
