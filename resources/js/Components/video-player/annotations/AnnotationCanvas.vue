<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';
import { useAnnotationCanvas } from '@/composables/useAnnotationCanvas';
import { debounce } from '@/utils/timing';
import AreaToolTip from './AreaToolTip.vue';

const videoStore = useVideoStore();
const annotationsStore = useAnnotationsStore();
const canvasRef = ref<HTMLCanvasElement | null>(null);
const showAreaTip = ref(false);
let areaTipTimeout: ReturnType<typeof setTimeout> | null = null;

const {
    fabricCanvas,
    initCanvas,
    resizeCanvas,
    setTool,
    setColor,
    clearCanvas,
    getCanvasJSON,
    loadFromJSON,
    completeArea,
    addSpotlight,
    addSymbol,
    dispose,
} = useAnnotationCanvas();

let resizeObserver: ResizeObserver | null = null;
let currentAnnotationId: number | null = null;
let lastCheckedSecond: number = -1;
let canvasInitialized = ref(false);
const debouncedResize = debounce((videoRef: HTMLVideoElement) => resizeCanvas(videoRef), 100);

function enableCanvasInteractionIfReady() {
    if (!fabricCanvas.value) return;
    if (!annotationsStore.annotationMode) return;

    if (fabricCanvas.value.upperCanvasEl) {
        fabricCanvas.value.upperCanvasEl.style.pointerEvents = 'auto';
    }
    setTool(annotationsStore.currentTool);
    setColor(annotationsStore.currentColor);
    console.log('✅ Canvas became ready while in annotation mode; interaction enabled');
}

// Initialize canvas when video is ready
watch(() => videoStore.videoRef, async (videoRef) => {
    if (!videoRef || !canvasRef.value || canvasInitialized.value) return;

    console.log('🎬 Video ready, initializing canvas...', {
        hasCanvas: !!canvasRef.value,
        hasVideo: !!videoRef
    });

    try {
        await initCanvas(canvasRef.value, videoRef);
        canvasInitialized.value = true;
        enableCanvasInteractionIfReady();

        // Watch for video resize — debounced 100ms to avoid Fabric.js renderAll() en cada pixel
        resizeObserver = new ResizeObserver(() => {
            if (videoStore.videoRef) {
                debouncedResize(videoStore.videoRef);
            }
        });
        resizeObserver.observe(videoRef);
    } catch (error) {
        console.error('❌ Failed to initialize canvas:', error);
    }
}, { immediate: true });

// If fabricCanvas becomes available after annotation mode was turned on, enable interaction
watch(fabricCanvas, () => {
    enableCanvasInteractionIfReady();
});

function handleKeyDown(event: KeyboardEvent) {
    if (!annotationsStore.annotationMode) return;

    if (event.key === 'Enter' && annotationsStore.currentTool === 'area') {
        event.preventDefault();
        completeArea();
        showAreaTip.value = false;
    }

    if (event.key === 'Escape' && annotationsStore.currentTool === 'area') {
        event.preventDefault();
        clearCanvas();
        showAreaTip.value = false;
    }
}

onMounted(() => {
    console.log('🎨 AnnotationCanvas mounted', {
        hasCanvasRef: !!canvasRef.value,
        hasVideoRef: !!videoStore.videoRef
    });

    // Add keyboard listeners
    document.addEventListener('keydown', handleKeyDown);
});


onUnmounted(() => {
    debouncedResize.cancel();
    if (resizeObserver && videoStore.videoRef) {
        resizeObserver.unobserve(videoStore.videoRef);
    }
    document.removeEventListener('keydown', handleKeyDown);
    if (areaTipTimeout) clearTimeout(areaTipTimeout);
    dispose();
});

// Watch annotation mode changes
watch(() => annotationsStore.annotationMode, (isActive) => {
    console.log('🎨 Annotation mode changed:', isActive, 'fabricCanvas:', !!fabricCanvas.value);
    if (!fabricCanvas.value) {
        console.error('❌ fabricCanvas not initialized!');
        return;
    }

    if (isActive) {
        // IMPORTANT: Only enable pointer events on the UPPER canvas
        // The lower canvas must stay with pointer-events: none to not intercept clicks
        if (fabricCanvas.value.upperCanvasEl) {
            fabricCanvas.value.upperCanvasEl.style.pointerEvents = 'auto';
            console.log('✅ Upper canvas interactive', {
                upperPointerEvents: fabricCanvas.value.upperCanvasEl.style.pointerEvents,
                lowerPointerEvents: canvasRef.value?.style.pointerEvents || 'none',
            });
        }

        setTool(annotationsStore.currentTool);
        setColor(annotationsStore.currentColor);
        console.log('🔧 Tool set to:', annotationsStore.currentTool, 'Color:', annotationsStore.currentColor);
    } else {
        // Disable pointer events on upper canvas
        if (fabricCanvas.value.upperCanvasEl) {
            fabricCanvas.value.upperCanvasEl.style.pointerEvents = 'none';
            console.log('🚫 Upper canvas disabled');
        }
        clearCanvas();
        currentAnnotationId = null;
        lastCheckedSecond = -1; // Force re-evaluation when mode changes
    }
});

// Watch tool changes
watch(() => annotationsStore.currentTool, (tool) => {
    console.log('🔧 Tool changed to:', tool);
    setTool(tool);

    // Show tip only for area tool
    if (tool === 'area') {
        showAreaTip.value = true;
        // Auto-hide after 4 seconds
        if (areaTipTimeout) clearTimeout(areaTipTimeout);
        areaTipTimeout = setTimeout(() => {
            showAreaTip.value = false;
        }, 4000);
    } else {
        showAreaTip.value = false;
        if (areaTipTimeout) {
            clearTimeout(areaTipTimeout);
            areaTipTimeout = null;
        }
    }
});

// Watch color changes
watch(() => annotationsStore.currentColor, (color) => {
    console.log('🎨 Color changed to:', color);
    setColor(color);
});

// Watch video time to display saved annotations
// Optimization: annotations are indexed per integer second — skip if second hasn't changed (60x speedup)
watch(() => videoStore.currentTime, (time) => {
    if (annotationsStore.annotationMode) return; // Don't show saved annotations in edit mode

    const currentSecond = Math.floor(time);
    if (currentSecond === lastCheckedSecond) return;
    lastCheckedSecond = currentSecond;

    const annotations = annotationsStore.getAnnotationsAtTime(time);

    if (annotations.length > 0) {
        // Display the most recent annotation at this timestamp
        const annotation = annotations[annotations.length - 1];
        if (annotation.id !== currentAnnotationId) {
            currentAnnotationId = annotation.id;
            loadFromJSON(annotation.annotation_data);
        }
    } else {
        // Clear canvas if no annotations at this time
        if (currentAnnotationId !== null) {
            currentAnnotationId = null;
            clearCanvas();
        }
    }
});

// Watch for spotlight trigger
watch(() => annotationsStore.spotlightTrigger, () => {
    if (annotationsStore.spotlightTrigger > 0) {
        addSpotlight(annotationsStore.currentColor);
        annotationsStore.setTool('select');
    }
});

// Watch for symbol trigger
watch(() => annotationsStore.symbolTrigger, (trigger) => {
    if (trigger) {
        addSymbol(trigger.type);
        annotationsStore.setTool('select');
    }
});
</script>

<template>
    <canvas
        ref="canvasRef"
        class="annotation-canvas"
        :class="{ 'is-active': annotationsStore.annotationMode }"
    ></canvas>
    <AreaToolTip :visible="showAreaTip && annotationsStore.annotationMode" />
</template>

<style scoped>
.annotation-canvas {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10;
    pointer-events: none;
}

.annotation-canvas.is-active {
    cursor: crosshair;
    pointer-events: auto;
}
</style>
