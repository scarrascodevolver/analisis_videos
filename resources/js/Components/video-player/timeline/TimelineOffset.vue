<template>
    <div class="timeline-offset-controls">
        <div class="offset-input-group">
            <label class="offset-label">
                <i class="fas fa-sync-alt"></i> Offset:
            </label>
            <input
                v-model.number="tempOffset"
                type="range"
                class="offset-slider"
                min="-300"
                max="300"
                step="0.5"
            />
            <span class="offset-display">{{ (typeof tempOffset === 'number' && isFinite(tempOffset) ? tempOffset : 0).toFixed(1) }}s</span>
        </div>
        <div class="offset-buttons">
            <button class="btn-apply" :disabled="isApplying" @click="applyOffset">
                <i class="fas fa-check"></i> Aplicar
            </button>
            <button class="btn-reset" :disabled="isApplying" @click="resetOffset" title="Resetear a 0s">
                <i class="fas fa-undo"></i>
            </button>
        </div>
        <div class="clip-count">
            <i class="fas fa-film"></i> <span>{{ clipCount }}</span> clips
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, inject } from 'vue';
import { useVideoStore } from '@/stores/videoStore';

const props = defineProps<{
    videoId: number;
    initialOffset?: number;
    clipCount: number;
}>();

const emit = defineEmits<{
    offsetChanged: [offset: number];
}>();

const api = inject<any>('videoApi');
const toast = inject<any>('toast');
const videoStore = useVideoStore();

const tempOffset = ref(props.initialOffset ?? 0);
const isApplying = ref(false);

watch(() => props.initialOffset, (newVal) => {
    if (newVal !== undefined) {
        tempOffset.value = newVal;
    }
});

async function applyOffset() {
    if (!api) {
        console.warn('⚠️ videoApi not available');
        toast?.error('Error: API no disponible');
        return;
    }

    isApplying.value = true;
    try {
        await api.setTimelineOffset(tempOffset.value);

        // Update videoStore to reflect new offset immediately
        videoStore.updateTimelineOffset(tempOffset.value);

        // Emit event to parent for additional processing (e.g., reload clips)
        emit('offsetChanged', tempOffset.value);

        toast?.success('Offset aplicado exitosamente');
    } catch (error) {
        console.error('Error applying offset:', error);
        toast?.error('Error al aplicar el offset');
    } finally {
        isApplying.value = false;
    }
}

function resetOffset() {
    tempOffset.value = 0;
    applyOffset();
}
</script>

<style scoped>
.timeline-offset-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 6px;
    padding: 8px 12px;
    margin-bottom: 10px;
}

.offset-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 300px;
}

.offset-label {
    color: #aaa;
    font-size: 12px;
    margin: 0;
    white-space: nowrap;
}

.offset-label i {
    color: #ffc107;
}

.offset-slider {
    flex: 1;
    max-width: 200px;
    cursor: pointer;
}

.offset-display {
    background: #ffc107;
    color: #000;
    font-weight: bold;
    font-size: 11px;
    min-width: 45px;
    text-align: center;
    padding: 2px 6px;
    border-radius: 3px;
}

.offset-buttons {
    display: flex;
    gap: 6px;
}

.btn-apply,
.btn-reset {
    font-size: 11px;
    padding: 4px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 600;
}

.btn-apply {
    background: #FFC300;
    color: #fff;
}

.btn-apply:hover:not(:disabled) {
    background: #009a98;
}

.btn-apply:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-reset {
    background: #6c757d;
    color: #fff;
    padding: 4px 10px;
}

.btn-reset:hover:not(:disabled) {
    background: #5a6268;
}

.btn-reset:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.clip-count {
    color: #888;
    font-size: 11px;
    margin-left: auto;
    white-space: nowrap;
}
</style>
