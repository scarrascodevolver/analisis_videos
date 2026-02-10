<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { useVideoStore, formatTime } from '@/stores/videoStore';
import { useCommentsStore } from '@/stores/commentsStore';
import type { VideoComment } from '@/types/video-player';

const props = defineProps<{
    commentCount: number;
}>();

const videoStore = useVideoStore();
const commentsStore = useCommentsStore();

const isCollapsed = ref(false);

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
        case 'media': return '#00B7B5';
        default: return '#6c757d';
    }
}

function seekToPosition(event: MouseEvent) {
    const bar = event.currentTarget as HTMLElement;
    const rect = bar.getBoundingClientRect();
    const percent = (event.clientX - rect.left) / rect.width;
    videoStore.seek(percent * videoStore.duration);
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
            <i class="fas fa-comments mr-2" style="color: #00B7B5;"></i>
            <strong class="toggle-title">Timeline de Comentarios</strong>
            <span class="badge ml-2 toggle-badge" style="background: #00B7B5;">{{ commentCount }}</span>
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
                <!-- Progress fill -->
                <div
                    class="progress-fill"
                    :style="{ width: videoStore.progress + '%' }"
                ></div>

                <!-- Playhead indicator -->
                <div
                    class="playhead"
                    :style="{ left: videoStore.progress + '%' }"
                ></div>

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
    background: rgba(0, 183, 181, 0.3);
    border-radius: 5px 0 0 5px;
    transition: width 0.1s linear;
    pointer-events: none;
}

.playhead {
    position: absolute;
    top: -2px;
    width: 2px;
    height: calc(100% + 4px);
    background: #00B7B5;
    z-index: 5;
    pointer-events: none;
    transition: left 0.1s linear;
}

.timeline-marker {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.5);
    cursor: pointer;
    z-index: 3;
    transition: transform 0.2s;
}

.timeline-marker:hover {
    transform: translate(-50%, -50%) scale(1.4);
    z-index: 4;
}

.marker-count {
    position: absolute;
    top: -18px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 9px;
    background: #005461;
    color: white;
    border-radius: 8px;
    padding: 0 4px;
    line-height: 14px;
    white-space: nowrap;
}
</style>
