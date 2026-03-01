<script setup lang="ts">
import { computed, ref, watch, onUnmounted } from 'vue';
import { useVideoStore, formatTime } from '@/stores/videoStore';
import { useCommentsStore } from '@/stores/commentsStore';
import type { VideoComment } from '@/types/video-player';

const props = defineProps<{
    commentCount: number;
}>();

const videoStore = useVideoStore();
const commentsStore = useCommentsStore();

const isCollapsed = ref(false);

// rAF-based DOM updates for playhead/progress — decouples from Vue reactive cycle (60fps → rAF)
const progressFillRef = ref<HTMLElement | null>(null);
const playheadRef     = ref<HTMLElement | null>(null);
let playheadRafId: number | null = null;

watch(() => videoStore.currentTime, () => {
    if (playheadRafId !== null) cancelAnimationFrame(playheadRafId);
    playheadRafId = requestAnimationFrame(() => {
        playheadRafId = null;
        const p = videoStore.progress + '%';
        if (progressFillRef.value) progressFillRef.value.style.width = p;
        if (playheadRef.value)     playheadRef.value.style.left = p;
    });
});

onUnmounted(() => {
    if (playheadRafId !== null) cancelAnimationFrame(playheadRafId);
});

// Clustered markers for rendering
interface MarkerCluster {
    position: number; // percentage 0-100
    timestamp: number; // seconds
    comments: VideoComment[];
    color: string;
}

const markers = computed<MarkerCluster[]>(() => {
    if (!videoStore.duration) return [];

    const duration = videoStore.duration;
    const minDistancePercent = 1; // minimum 1% apart

    // Get all root comments sorted by timestamp
    const sorted = [...commentsStore.comments].sort(
        (a, b) => a.timestamp_seconds - b.timestamp_seconds,
    );

    // Cluster nearby markers
    const clusters: MarkerCluster[] = [];
    for (const comment of sorted) {
        const pos = (comment.timestamp_seconds / duration) * 100;

        // Check if this can merge into existing cluster
        if (clusters.length > 0) {
            const last = clusters[clusters.length - 1];
            if (Math.abs(pos - last.position) < minDistancePercent) {
                last.comments.push(comment);
                // Average position
                last.position = (last.position * (last.comments.length - 1) + pos) / last.comments.length;
                continue;
            }
        }

        clusters.push({
            position: pos,
            timestamp: comment.timestamp_seconds,
            comments: [comment],
            color: markerColor(comment),
        });
    }

    return clusters;
});

function markerColor(comment: VideoComment): string {
    switch (comment.priority) {
        case 'critica': return '#dc3545';
        case 'alta': return '#ffc107';
        case 'media': return COLOR_ACCENT;
        default: return '#6c757d';
    }
}

function seekToPosition(event: MouseEvent) {
    const bar = event.currentTarget as HTMLElement;
    const rect = bar.getBoundingClientRect();
    const percent = (event.clientX - rect.left) / rect.width;
    videoStore.seek(percent * videoStore.duration);
    // Devolver foco al body para que los hotkeys funcionen de inmediato
    (document.activeElement as HTMLElement)?.blur();
}

function seekToMarker(marker: MarkerCluster) {
    videoStore.seek(marker.timestamp);
    videoStore.play();
}
</script>

<template>
    <div id="timelineWrapper">
        <button
            class="btn btn-block text-left py-1 px-2 toggle-btn"
            @click="isCollapsed = !isCollapsed"
        >
            <i class="fas fa-comments mr-2" style="color: var(--color-accent);"></i>
            <strong class="toggle-title">Timeline de Comentarios</strong>
            <span class="badge ml-2 toggle-badge" style="background: var(--color-accent);">{{ commentCount }}</span>
            <i class="fas float-right mt-1 toggle-chevron" :class="isCollapsed ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
        </button>

        <div v-show="!isCollapsed" class="timeline-content p-1 position-relative" style="overflow: visible;">
            <!-- Notification slot -->
            <slot name="notifications" />

            <!-- Timeline bar -->
            <div
                class="timeline-bar position-relative"
                @click="seekToPosition"
            >
                <!-- Progress fill — updated via rAF, not Vue reactive binding -->
                <div class="progress-fill" ref="progressFillRef"></div>

                <!-- Playhead indicator — updated via rAF -->
                <div class="playhead" ref="playheadRef"></div>

                <!-- Comment markers -->
                <div
                    v-for="(marker, i) in markers"
                    :key="i"
                    class="timeline-marker"
                    :style="{
                        left: marker.position + '%',
                        backgroundColor: marker.color,
                    }"
                    :title="`${formatTime(marker.timestamp)} (${marker.comments.length} comentario${marker.comments.length > 1 ? 's' : ''})`"
                    @click.stop="seekToMarker(marker)"
                >
                    <span v-if="marker.comments.length > 2" class="marker-count">
                        {{ marker.comments.length > 9 ? '9+' : marker.comments.length }}
                    </span>
                </div>
            </div>

            <!-- Time labels -->
            <div class="d-flex justify-content-between small mt-1" style="color: #888;">
                <span>00:00</span>
                <span>{{ videoStore.formattedDuration }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.toggle-btn {
    background: #1a1a1a;
    border: none;
    border-radius: 0;
    color: #fff;
    border-top: 1px solid #333;
    font-size: 0.68rem !important;
    line-height: 1.1;
}

.toggle-btn:hover {
    background: #252525;
    color: #fff;
}

.toggle-title {
    font-size: 0.64rem !important;
    font-weight: 600;
}

.toggle-badge {
    font-size: 0.55rem !important;
    padding: 0.1rem 0.3rem;
}

.toggle-chevron {
    font-size: 0.7rem !important;
}

.timeline-content {
    background: #1a1a1a;
}

.timeline-bar {
    height: 26px;
    background: #333;
    border-radius: 5px;
    margin: 6px 0;
    cursor: pointer;
    position: relative;
    overflow: visible;
}

.progress-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: rgba(255, 195, 0, 0.3);
    border-radius: 5px 0 0 5px;
    transition: width 0.1s linear;
    pointer-events: none;
}

.playhead {
    position: absolute;
    top: -2px;
    width: 2px;
    height: calc(100% + 4px);
    background: var(--color-accent);
    z-index: 5;
    pointer-events: none;
    transition: left 0.1s linear;
}

.timeline-marker {
    position: absolute;
    top: 0;
    transform: translateX(-50%);
    width: 3px;
    height: 100%;
    border-radius: 1px;
    cursor: pointer;
    z-index: 3;
    transition: width 0.2s, opacity 0.2s;
    opacity: 0.9;
}

.timeline-marker:hover {
    width: 5px;
    opacity: 1;
    z-index: 4;
}

.marker-count {
    position: absolute;
    top: -16px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 8px;
    background: rgba(0, 84, 97, 0.95);
    color: white;
    border-radius: 6px;
    padding: 0 3px;
    line-height: 12px;
    white-space: nowrap;
    font-weight: 600;
}
</style>
