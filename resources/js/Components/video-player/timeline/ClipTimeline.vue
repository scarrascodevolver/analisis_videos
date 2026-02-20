<template>
    <div class="clip-timeline-panel">
        <!-- Collapsible Header -->
        <div
            class="timeline-header"
            @click="toggleCollapsed"
            role="button"
            tabindex="0"
            @keydown.enter="toggleCollapsed"
            @keydown.space.prevent="toggleCollapsed"
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

                        <!-- Lane Track -->
                        <div
                            class="lane-track"
                            @click="handleLaneClick($event, category.id)"
                        >
                            <!-- Clip Blocks -->
                            <div
                                v-for="clip in getClipsForCategory(category.id)"
                                :key="clip.id"
                                class="clip-block"
                                :style="getClipBlockStyle(clip)"
                                :title="getClipTooltip(clip)"
                                @click.stop="handleClipClick(clip)"
                            ></div>
                        </div>
                    </div>

                    <!-- Playhead -->
                    <div class="playhead" :style="{ left: playheadPosition }"></div>
                </div>

                <!-- Time Scale -->
                <div class="time-scale">
                    <div class="time-scale-label">
                        <!-- Empty space for lane labels -->
                    </div>
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
                    • <strong>Click en un clip</strong> (cuadrado de color) para reproducirlo<br>
                    • <strong>Click en la barra</strong> para saltar a ese momento del video<br>
                    • Los clips importados de XML son de solo lectura
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

const props = defineProps<{
    videoId: number;
}>();

const videoStore = useVideoStore();
const clipsStore = useClipsStore();

const isCollapsed = ref(false);
const timelineContainerRef = ref<HTMLElement | null>(null);

// Computed
const activeCategories = computed(() => clipsStore.activeCategories);
const currentTime = computed(() => videoStore.currentTime);
const duration = computed(() => videoStore.duration || 1);
const formattedDuration = computed(() => videoStore.formattedDuration);

// Timeline offset from video (for syncing imported XML clips)
// IMPORTANT: Convert to Number because Laravel returns decimals as strings
const currentOffset = computed(() => Number(videoStore.video?.timeline_offset || 0));

const playheadPosition = computed(() => {
    if (!duration.value) return '0%';
    const percent = (currentTime.value / duration.value) * 100;
    // Position playhead accounting for lane-label width (110px)
    // Playhead should be positioned only over the track area, not the labels
    return `calc(110px + (100% - 110px) * ${percent / 100})`;
});

// Methods
function toggleCollapsed() {
    isCollapsed.value = !isCollapsed.value;
}

function getClipsForCategory(categoryId: number): VideoClip[] {
    return clipsStore.clipsByCategory[categoryId] || [];
}

function getClipBlockStyle(clip: VideoClip) {
    // Parse raw clip times from database
    const rawStartTime = parseFloat(clip.start_time as any) || 0;
    const rawEndTime = parseFloat(clip.end_time as any) || 0;

    // Apply timeline offset for synchronization (important for XML imported clips)
    const adjustedStart = Math.max(0, rawStartTime + currentOffset.value);
    const adjustedEnd = rawEndTime + currentOffset.value;

    // Skip clips if duration not loaded yet
    if (!duration.value || duration.value < 1) {
        return {
            left: '0%',
            width: '0%',
            display: 'none',
        };
    }

    // Skip clips outside video duration (after offset adjustment)
    if (adjustedStart >= duration.value || adjustedEnd <= 0) {
        return {
            left: '0%',
            width: '0%',
            display: 'none',
        };
    }

    // Calculate position percentages with adjusted times
    const startPercent = (adjustedStart / duration.value) * 100;
    const widthPercent = ((adjustedEnd - adjustedStart) / duration.value) * 100;
    const color = clip.category?.color || '#00B7B5';

    // Z-index based on clip ID: newer clips (higher IDs) on top of older ones
    const zIndex = 5 + (clip.id % 50); // Base 5, can go up to 55

    return {
        left: `${Math.max(0, Math.min(startPercent, 100))}%`,
        width: `${Math.max(widthPercent, 0.5)}%`,
        backgroundColor: color,
        zIndex: zIndex,
    };
}

function getClipTooltip(clip: VideoClip): string {
    // Parse raw clip times
    const rawStartTime = parseFloat(clip.start_time as any) || 0;
    const rawEndTime = parseFloat(clip.end_time as any) || 0;

    // Apply timeline offset for synchronization
    const adjustedStart = rawStartTime + currentOffset.value;
    const adjustedEnd = rawEndTime + currentOffset.value;
    const clipDuration = adjustedEnd - adjustedStart;
    const durationText = isFinite(clipDuration) ? clipDuration.toFixed(1) : '0.0';

    const startTimeStr = formatTime(adjustedStart);
    const endTimeStr = formatTime(adjustedEnd);
    const title = clip.title ? `${clip.title}\n` : '';
    return `${title}${startTimeStr} - ${endTimeStr} (${durationText}s)`;
}

function handleClipClick(clip: VideoClip) {
    // Parse raw clip time and apply offset for synchronization
    const rawStartTime = parseFloat(clip.start_time as any) || 0;
    const seekTime = rawStartTime + currentOffset.value;

    if (isFinite(seekTime) && seekTime >= 0) {
        console.log('🎬 Seeking to clip with offset:', {
            rawTime: rawStartTime,
            offset: currentOffset.value,
            seekTime: seekTime,
        });
        videoStore.seek(seekTime);
        // Play immediately after seeking
        if (!videoStore.isPlaying) {
            videoStore.play();
        }
    } else {
        console.error('Invalid seek time:', { clip, rawStartTime, offset: currentOffset.value, seekTime });
    }
}

function handleLaneClick(event: MouseEvent, categoryId: number) {
    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const clickPercent = clickX / rect.width;
    // Click on timeline already accounts for visual offset, so no adjustment needed
    const seekTime = clickPercent * duration.value;

    if (isFinite(seekTime)) {
        videoStore.seek(seekTime);
    } else {
        console.error('Invalid seek time from lane click:', { clickPercent, duration: duration.value, seekTime });
    }
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

.timeline-header:hover {
    background: #2a2a2a;
}

.header-content {
    display: flex;
    align-items: center;
}

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

.timeline-content {
    padding: 0.45rem;
}

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

.lane-label {
    width: 110px;
    padding: 0.4rem 0.5rem;
    display: flex;
    align-items: center;
    border-left: 3px solid;
    background: #1a1a1a;
    flex-shrink: 0;
}

.color-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 0.5rem;
    flex-shrink: 0;
}

.category-name {
    color: #ccc;
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lane-track {
    position: relative;
    flex: 1;
    height: 32px;
    background: #252525;
    cursor: pointer;
    border-radius: 2px;
}

.lane-track:hover {
    background: #2a2a2a;
}

.clip-block {
    position: absolute;
    top: 2px;
    bottom: 2px;
    border-radius: 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: all 0.15s ease;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
    min-width: 8px !important;
    margin-right: 2px;
    opacity: 0.85;
}

.clip-block:hover {
    transform: scaleY(1.3);
    z-index: 100 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.8) !important;
    filter: brightness(1.2);
    opacity: 1;
}

.clip-block:active {
    transform: translateY(0);
}

.clip-label {
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    padding: 0 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

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

.time-scale {
    display: flex;
    margin-top: 0.5rem;
}

.time-scale-label {
    width: 110px;
    flex-shrink: 0;
}

.time-scale-track {
    flex: 1;
    display: flex;
    justify-content: space-between;
    padding: 0 0.25rem;
}

.time-marker {
    color: #999;
    font-size: 11px;
}

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

.help-message i {
    margin-right: 4px;
}

.help-message strong {
    color: #fff;
}

/* Slide down transition */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    max-height: 0;
    opacity: 0;
}
</style>
