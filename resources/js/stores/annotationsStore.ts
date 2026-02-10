import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { VideoAnnotation } from '@/types/video-player';
import { useVideoStore } from '@/stores/videoStore';

export type AnnotationTool = 'select' | 'arrow' | 'line' | 'circle' | 'rectangle' | 'free_draw' | 'text' | 'area' | 'canvas';

export const useAnnotationsStore = defineStore('annotations', () => {
    const videoStore = useVideoStore();
    // State
    const annotations = ref<VideoAnnotation[]>([]);
    const annotationMode = ref(false);
    const currentTool = ref<AnnotationTool>('select');
    // Track last drawing tool that is valid for persistence (exclude 'select'/'area')
    const lastDrawingTool = ref<AnnotationTool>('line');
    const currentColor = ref('#ff0000');
    const undoStack = ref<string[]>([]);
    const redoStack = ref<string[]>([]);
    const hasTemporaryDrawing = ref(false);

    // Triggers for canvas actions (watched by AnnotationCanvas)
    const spotlightTrigger = ref(0);
    const symbolTrigger = ref<{ type: string; timestamp: number } | null>(null);

    // Computed
    const annotationsByTimestamp = computed(() => {
        const map = new Map<number, VideoAnnotation[]>();
        // Defensive: ensure annotations is always an array
        const safeAnnotations = Array.isArray(annotations.value) ? annotations.value : [];
        safeAnnotations.forEach((annotation) => {
            const key = Math.floor(annotation.timestamp);
            if (!map.has(key)) {
                map.set(key, []);
            }
            map.get(key)!.push(annotation);
        });
        return map;
    });

    const annotationCount = computed(() => {
        return Array.isArray(annotations.value) ? annotations.value.length : 0;
    });

    const canUndo = computed(() => undoStack.value.length > 0);
    const canRedo = computed(() => redoStack.value.length > 0);

    // Actions
    function loadAnnotations(newAnnotations: VideoAnnotation[]) {
        // Defensive: ensure newAnnotations is always an array
        annotations.value = Array.isArray(newAnnotations) ? newAnnotations : [];
    }

    function addAnnotation(annotation: VideoAnnotation) {
        annotations.value.push(annotation);
    }

    function removeAnnotation(annotationId: number) {
        const index = annotations.value.findIndex((a) => a.id === annotationId);
        if (index !== -1) {
            annotations.value.splice(index, 1);
        }
    }

    function getAnnotationsAtTime(timestamp: number, tolerance: number = 1): VideoAnnotation[] {
        const key = Math.floor(timestamp);
        const results: VideoAnnotation[] = [];

        // Check current second and adjacent seconds
        for (let i = -tolerance; i <= tolerance; i++) {
            const checkKey = key + i;
            if (annotationsByTimestamp.value.has(checkKey)) {
                results.push(...annotationsByTimestamp.value.get(checkKey)!);
            }
        }

        return results;
    }

    function enterAnnotationMode() {
        annotationMode.value = true;
        clearUndoRedo();

        // Pause video when entering annotation mode (like Blade version)
        if (videoStore.videoRef) {
            videoStore.videoRef.pause();
        }
    }

    function exitAnnotationMode() {
        annotationMode.value = false;
        hasTemporaryDrawing.value = false;
        clearUndoRedo();
    }

    function setTool(tool: AnnotationTool) {
        currentTool.value = tool;
        const persistableTools: AnnotationTool[] = ['arrow', 'circle', 'line', 'text', 'rectangle', 'free_draw', 'canvas' as AnnotationTool];
        if (persistableTools.includes(tool)) {
            lastDrawingTool.value = tool;
        }
    }

    function setColor(color: string) {
        currentColor.value = color;
    }

    function pushToUndoStack(canvasState: string) {
        undoStack.value.push(canvasState);
        // Limit undo stack to 20 states
        if (undoStack.value.length > 20) {
            undoStack.value.shift();
        }
        // Clear redo stack when new action is performed
        redoStack.value = [];
        hasTemporaryDrawing.value = true;
    }

    function undo(): string | null {
        if (undoStack.value.length === 0) return null;
        const current = undoStack.value.pop();
        if (current) {
            redoStack.value.push(current);
        }
        return undoStack.value[undoStack.value.length - 1] || null;
    }

    function redo(): string | null {
        if (redoStack.value.length === 0) return null;
        const state = redoStack.value.pop();
        if (state) {
            undoStack.value.push(state);
            return state;
        }
        return null;
    }

    function clearUndoRedo() {
        undoStack.value = [];
        redoStack.value = [];
    }

    function markDrawingSaved() {
        hasTemporaryDrawing.value = false;
        clearUndoRedo();
    }

    function triggerSpotlight() {
        spotlightTrigger.value++;
        hasTemporaryDrawing.value = true;
    }

    function triggerSymbol(symbolType: string) {
        symbolTrigger.value = { type: symbolType, timestamp: Date.now() };
        hasTemporaryDrawing.value = true;
    }

    return {
        // State
        annotations,
        annotationMode,
        currentTool,
        currentColor,
        lastDrawingTool,
        undoStack,
        redoStack,
        hasTemporaryDrawing,
        spotlightTrigger,
        symbolTrigger,
        // Computed
        annotationsByTimestamp,
        annotationCount,
        canUndo,
        canRedo,
        // Actions
        loadAnnotations,
        addAnnotation,
        removeAnnotation,
        getAnnotationsAtTime,
        enterAnnotationMode,
        exitAnnotationMode,
        setTool,
        setColor,
        pushToUndoStack,
        undo,
        redo,
        clearUndoRedo,
        markDrawingSaved,
        triggerSpotlight,
        triggerSymbol,
    };
});
