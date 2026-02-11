<template>
    <div class="timeline-row" :class="rowClass">
        <!-- Label -->
        <div class="timeline-label">
            <i :class="iconClass"></i>
            <span class="label-text">{{ label }}</span>
            <span v-if="offset !== 0" class="offset-badge" :class="offsetBadgeClass">
                {{ offsetBadgeText }}
            </span>
        </div>

        <!-- Timeline Track -->
        <div
            ref="trackRef"
            class="timeline-track"
            :class="{ 'draggable': draggable, 'is-dragging': isDragging }"
            @mousedown="startDrag"
            @click="handleClick"
        >
            <!-- Background grid -->
            <div class="timeline-grid">
                <div
                    v-for="i in 20"
                    :key="i"
                    class="grid-line"
                    :style="{ left: (i * 5) + '%' }"
                ></div>
            </div>

            <!-- Timeline bar (se mueve con el offset) -->
            <div
                class="timeline-bar"
                :style="{ transform: `translateX(${offsetPixels}px)` }"
            >
                <!-- Playhead -->
                <div
                    class="playhead"
                    :style="{ left: playheadPercent + '%' }"
                >
                    <div class="playhead-line"></div>
                    <div class="playhead-handle"></div>
                </div>
            </div>

            <!-- Drag indicator -->
            <div v-if="isDragging" class="drag-indicator">
                <i class="fas fa-arrows-alt-h"></i>
                <span>{{ offsetBadgeText }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { formatTime } from '@/stores/videoStore';

const props = defineProps<{
    type: 'master' | 'slave' | 'clips';
    label: string;
    currentTime: number;
    duration: number;
    offset: number;
    draggable: boolean;
}>();

const emit = defineEmits<{
    'offset-changed': [newOffset: number];
    'seek': [time: number];
}>();

const trackRef = ref<HTMLElement | null>(null);
const isDragging = ref(false);
const tempOffset = ref(props.offset);
const dragStartX = ref(0);
const initialOffset = ref(0);
const justDragged = ref(false);

const rowClass = computed(() => ({
    'is-master': props.type === 'master',
    'is-slave': props.type === 'slave',
    'is-clips': props.type === 'clips',
}));

const iconClass = computed(() => {
    switch (props.type) {
        case 'master': return 'fas fa-film text-primary';
        case 'slave': return 'fas fa-video text-info';
        case 'clips': return 'fas fa-cut text-warning';
        default: return 'fas fa-circle';
    }
});

const offsetBadgeClass = computed(() => ({
    'positive': props.offset > 0,
    'negative': props.offset < 0,
}));

// Calculate absolute timestamp for better UX (like Hudl/Angles)
const offsetBadgeText = computed(() => {
    const offsetValue = isDragging.value ? tempOffset.value : props.offset;
    const sign = offsetValue > 0 ? '+' : '';
    const offsetText = `${sign}${offsetValue.toFixed(1)}s`;

    // For slaves, show absolute timestamp where they start
    if (props.type === 'slave' && offsetValue !== 0) {
        const absoluteTime = Math.abs(offsetValue);
        const timestamp = formatTime(absoluteTime);
        return `${offsetText} (${offsetValue > 0 ? 'empieza' : 'empezó'} en ${timestamp})`;
    }

    return offsetText;
});

const offsetPixels = computed(() => {
    if (!trackRef.value) return 0;
    const trackWidth = trackRef.value.offsetWidth;
    const pixelsPerSecond = trackWidth / props.duration;
    return (isDragging.value ? tempOffset.value : props.offset) * pixelsPerSecond;
});

const playheadPercent = computed(() => {
    if (!props.duration) return 0;

    // Master and clips: playhead shows current playback progress
    // Slaves: playhead shows the OFFSET (configuration - where they start)
    if (props.type === 'slave') {
        // Slave playhead shows where it starts in master timeline (the offset)
        const offsetTime = isDragging.value ? tempOffset.value : props.offset;
        return Math.min(100, Math.max(0, (offsetTime / props.duration) * 100));
    } else {
        // Master and Clips: show current playback time
        return Math.min(100, Math.max(0, (props.currentTime / props.duration) * 100));
    }
});

function startDrag(event: MouseEvent) {
    if (!props.draggable || event.button !== 0) return;

    event.preventDefault();
    event.stopPropagation();

    isDragging.value = true;
    dragStartX.value = event.clientX;
    initialOffset.value = props.offset;
    tempOffset.value = props.offset;

    document.addEventListener('mousemove', handleDrag);
    document.addEventListener('mouseup', endDrag);

    // Añadir clase al body para cambiar cursor
    document.body.style.cursor = 'grabbing';
    document.body.style.userSelect = 'none';
}

function handleDrag(event: MouseEvent) {
    if (!isDragging.value || !trackRef.value) return;

    const deltaX = event.clientX - dragStartX.value;
    const trackWidth = trackRef.value.offsetWidth;
    const pixelsPerSecond = trackWidth / props.duration;
    const deltaSeconds = deltaX / pixelsPerSecond;

    tempOffset.value = initialOffset.value + deltaSeconds;

    // Limitar offset a rango razonable (-60s a +600s = -1min a +10min)
    tempOffset.value = Math.max(-60, Math.min(600, tempOffset.value));
}

function endDrag() {
    if (!isDragging.value) return;

    isDragging.value = false;

    // Restaurar cursor
    document.body.style.cursor = '';
    document.body.style.userSelect = '';

    document.removeEventListener('mousemove', handleDrag);
    document.removeEventListener('mouseup', endDrag);

    // Detectar si realmente se arrastró
    const offsetChanged = Math.abs(tempOffset.value - props.offset) > 0.1;

    if (offsetChanged) {
        emit('offset-changed', tempOffset.value);
        justDragged.value = true;
        // Reset flag después de un breve delay
        setTimeout(() => {
            justDragged.value = false;
        }, 100);
    }
}

function handleClick(event: MouseEvent) {
    // No hacer seek si acabamos de arrastrar
    if (justDragged.value) return;

    // Permitir seek en todos los timelines
    if (trackRef.value) {
        const rect = trackRef.value.getBoundingClientRect();
        const clickX = event.clientX - rect.left;
        const percent = clickX / rect.width;
        const seekTime = percent * props.duration;

        // Emitir evento de seek
        emit('seek', seekTime);
    }
}
</script>

<style scoped>
.timeline-row {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    background: #1a1a1a;
    border-radius: 4px;
    padding: 6px;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.timeline-row.is-master {
    border-left-color: #005461;
    background: #252525;
}

.timeline-row.is-slave {
    border-left-color: #00B7B5;
}

.timeline-row.is-clips {
    border-left-color: #ffc107;
}

.timeline-row:hover {
    background: #252525;
}

.timeline-label {
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 200px;
    max-width: 200px;
    padding-right: 8px;
    font-size: 11px;
}

.label-text {
    color: #ccc;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.offset-badge {
    font-size: 8.5px;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: bold;
    white-space: nowrap;
    max-width: 160px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.offset-badge.positive {
    background: #28a745;
    color: white;
}

.offset-badge.negative {
    background: #dc3545;
    color: white;
}

.timeline-track {
    position: relative;
    flex: 1;
    height: 32px;
    background: #0f0f0f;
    border-radius: 3px;
    overflow: hidden;
    border: 1px solid #333;
    cursor: pointer;
}

.timeline-track.draggable {
    cursor: grab;
}

.timeline-track.is-dragging {
    cursor: grabbing;
    border-color: #00B7B5;
    box-shadow: 0 0 8px rgba(0, 183, 181, 0.4);
}

.timeline-grid {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.grid-line {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 1px;
    background: rgba(255, 255, 255, 0.05);
}

.grid-line:nth-child(4n) {
    background: rgba(255, 255, 255, 0.1);
}

.timeline-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    transition: transform 0.1s ease;
    pointer-events: none;
}

.timeline-track.is-dragging .timeline-bar {
    transition: none;
}

.playhead {
    position: absolute;
    top: 0;
    bottom: 0;
    transform: translateX(-50%);
    pointer-events: none;
    z-index: 10;
}

.playhead-line {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 50%;
    width: 2px;
    background: #ff0000;
    box-shadow: 0 0 4px rgba(255, 0, 0, 0.6);
}

.playhead-handle {
    position: absolute;
    top: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 8px solid #ff0000;
}

.drag-indicator {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 183, 181, 0.95);
    color: white;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    pointer-events: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
    z-index: 20;
}

.drag-indicator i {
    margin-right: 4px;
}

.text-primary {
    color: #005461 !important;
}

.text-info {
    color: #00B7B5 !important;
}

.text-warning {
    color: #ffc107 !important;
}
</style>
