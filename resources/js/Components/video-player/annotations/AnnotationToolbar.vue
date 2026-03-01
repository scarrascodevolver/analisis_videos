<script setup lang="ts">
import { ref, computed } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';
import { useAnnotationCanvas } from '@/composables/useAnnotationCanvas';
import { useVideoApi } from '@/composables/useVideoApi';
import type { AnnotationTool } from '@/stores/annotationsStore';

const videoStore = useVideoStore();
const annotationsStore = useAnnotationsStore();
const canvasComposable = useAnnotationCanvas();

const isSaving = ref(false);
const showColorPicker = ref(false);

// Draggable toolbar state
const isDragging = ref(false);
const toolbarPosition = ref({ x: 10, y: 10 });
const dragStart = ref({ x: 0, y: 0 });

const tools: { name: AnnotationTool; icon: string; label: string }[] = [
    { name: 'select', icon: 'fa-mouse-pointer', label: 'Seleccionar' },
    { name: 'arrow', icon: 'fa-arrow-right', label: 'Flecha' },
    { name: 'line', icon: 'fa-minus', label: 'Línea' },
    { name: 'circle', icon: 'fa-circle', label: 'Círculo' },
    { name: 'rectangle', icon: 'fa-square', label: 'Rectángulo' },
    { name: 'free_draw', icon: 'fa-pencil-alt', label: 'Dibujo Libre' },
    { name: 'text', icon: 'fa-font', label: 'Texto' },
    { name: 'area', icon: 'fa-draw-polygon', label: 'Área' },
];

const symbols = [
    { name: 'tackle', icon: 'fa-bolt', color: '#dc3545', label: 'Tackle' },
    { name: 'ball', icon: 'fa-football-ball', color: '#8B4513', label: 'Balón' },
    { name: 'x', icon: 'fa-times', color: '#dc3545', label: 'Error' },
    { name: 'check', icon: 'fa-check', color: '#28a745', label: 'OK' },
];

const annotationDuration = ref<string>('4');
const durationOptions = [
    { value: '2', label: '2s' },
    { value: '4', label: '4s' },
    { value: '8', label: '8s' },
    { value: 'permanent', label: 'Fija' },
];

const predefinedColors = [
    '#ff0000', // Red
    '#00ff00', // Green
    '#0000ff', // Blue
    '#ffff00', // Yellow
    '#ff00ff', // Magenta
    '#00ffff', // Cyan
    '#ffffff', // White
    '#ff8800', // Orange
];

function selectTool(tool: AnnotationTool) {
    annotationsStore.setTool(tool);

    // Complete area if switching from area tool
    if (annotationsStore.currentTool !== 'area' && tool !== 'area') {
        canvasComposable.completeArea();
    }
}

function selectColor(color: string) {
    annotationsStore.setColor(color);
    showColorPicker.value = false;
}

function addSpotlight() {
    // Trigger spotlight via store - AnnotationCanvas will handle it
    annotationsStore.triggerSpotlight();
}

function addSymbol(symbolType: string) {
    // Trigger symbol via store - AnnotationCanvas will handle it
    annotationsStore.triggerSymbol(symbolType);
}

async function saveAnnotation() {
    if (!videoStore.video || isSaving.value) return;

    // Defensive: ensure video ID is present and numeric
    if (!videoStore.video?.id || isNaN(Number(videoStore.video.id))) {
        alert('No se pudo identificar el video actual. Recarga la página.');
        return;
    }

    const canvasData = canvasComposable.getCanvasJSON();
    if (!canvasData || canvasData === '{"objects":[],"background":"transparent"}') {
        alert('No hay dibujos para guardar');
        return;
    }

    isSaving.value = true;

    try {
        // Create videoApi with current video ID
        const videoApi = useVideoApi(videoStore.video.id);

        // Parse duration
        const isPermanent = annotationDuration.value === 'permanent';
        const durationSeconds = isPermanent ? undefined : parseInt(annotationDuration.value);

    const saveData = {
        timestamp: Math.floor(videoStore.currentTime),
        annotation_data: canvasData,
        // Ensure we only send types allowed by the DB enum
        annotation_type: (() => {
            const allowed = ['arrow', 'circle', 'line', 'text', 'rectangle', 'free_draw', 'canvas'];
            const tool = annotationsStore.currentTool;
            if (allowed.includes(tool)) return tool;
            // Fallback to the last valid drawing tool, otherwise 'canvas'
            if (allowed.includes(annotationsStore.lastDrawingTool)) return annotationsStore.lastDrawingTool;
            return 'canvas';
        })(),
        duration_seconds: durationSeconds || 4,
        is_permanent: isPermanent,
    };

        console.log('💾 Saving annotation with data:', saveData);
        console.log('📹 Video ID:', videoStore.video.id);

        const result = await videoApi.saveAnnotation(saveData);

        if (result.success) {
            annotationsStore.addAnnotation(result.annotation);
            annotationsStore.markDrawingSaved();
        }
    } catch (error) {
        console.error('Error saving annotation:', error);
        alert('Error al guardar la anotación');
    } finally {
        isSaving.value = false;
    }
}

function clearDrawing() {
    if (annotationsStore.hasTemporaryDrawing) {
        if (!confirm('¿Desea borrar todos los dibujos actuales?')) return;
    }
    canvasComposable.clearCanvas();
    annotationsStore.clearUndoRedo();
}

function undoAction() {
    const previousState = annotationsStore.undo();
    canvasComposable.undo(previousState);
}

function redoAction() {
    const nextState = annotationsStore.redo();
    if (nextState) {
        canvasComposable.redo(nextState);
    }
}

function closeAnnotationMode() {
    if (annotationsStore.hasTemporaryDrawing) {
        if (!confirm('Tiene dibujos sin guardar. ¿Desea cerrar de todas formas?')) return;
    }
    annotationsStore.exitAnnotationMode();
}

// Draggable functionality
function startDrag(event: MouseEvent) {
    if (event.button !== 0) return; // Only left click

    isDragging.value = true;
    dragStart.value = {
        x: event.clientX - toolbarPosition.value.x,
        y: event.clientY - toolbarPosition.value.y,
    };

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);

    // Prevent text selection while dragging
    document.body.style.userSelect = 'none';
}

function onDrag(event: MouseEvent) {
    if (!isDragging.value) return;

    toolbarPosition.value = {
        x: event.clientX - dragStart.value.x,
        y: event.clientY - dragStart.value.y,
    };
}

function stopDrag() {
    isDragging.value = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    document.body.style.userSelect = '';
}

const toolbarStyle = computed(() => ({
    left: `${toolbarPosition.value.x}px`,
    top: `${toolbarPosition.value.y}px`,
}));
</script>

<template>
    <div
        v-if="annotationsStore.annotationMode"
        class="annotation-toolbar"
        :class="{ 'is-dragging': isDragging }"
        :style="toolbarStyle"
    >
        <!-- Header (draggable) -->
        <div
            class="toolbar-header"
            @mousedown="startDrag"
        >
            <h6 class="toolbar-title">
                <i class="fas fa-grip-vertical drag-handle"></i>
                Anotaciones
            </h6>
            <span v-if="annotationsStore.annotationCount > 0" class="annotation-count">
                {{ annotationsStore.annotationCount }}
            </span>
        </div>

        <!-- Tools -->
        <div class="toolbar-section">
            <div class="toolbar-label">Herramientas</div>
            <div class="tool-grid">
                <button
                    v-for="tool in tools"
                    :key="tool.name"
                    :class="['tool-btn', { active: annotationsStore.currentTool === tool.name }]"
                    :title="tool.label"
                    @click="selectTool(tool.name)"
                >
                    <i :class="['fas', tool.icon]"></i>
                </button>
            </div>
        </div>

        <!-- Color Picker -->
        <div class="toolbar-section">
            <div class="toolbar-label">Color</div>
            <button
                class="color-preview"
                :style="{ backgroundColor: annotationsStore.currentColor }"
                @click="showColorPicker = !showColorPicker"
            ></button>

            <div v-if="showColorPicker" class="color-picker">
                <button
                    v-for="color in predefinedColors"
                    :key="color"
                    class="color-option"
                    :style="{ backgroundColor: color }"
                    :class="{ active: annotationsStore.currentColor === color }"
                    @click="selectColor(color)"
                ></button>
            </div>
        </div>

        <!-- Duration Selector -->
        <div class="toolbar-section">
            <div class="toolbar-label">Duración</div>
            <select v-model="annotationDuration" class="duration-select">
                <option v-for="opt in durationOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                </option>
            </select>
        </div>

        <!-- Spotlight -->
        <div class="toolbar-section">
            <button class="action-btn spotlight-btn" @click="addSpotlight">
                <i class="fas fa-bullseye"></i>
                Foco
            </button>
        </div>

        <!-- Quick Symbols -->
        <div class="toolbar-section">
            <div class="toolbar-label">Símbolos</div>
            <div class="symbol-grid">
                <button
                    v-for="symbol in symbols"
                    :key="symbol.name"
                    class="symbol-btn"
                    :title="symbol.label"
                    @click="addSymbol(symbol.name)"
                >
                    <i :class="['fas', symbol.icon]" :style="{ color: symbol.color }"></i>
                </button>
            </div>
        </div>

        <!-- Actions -->
        <div class="toolbar-section">
            <button
                class="action-btn save-btn"
                :disabled="!annotationsStore.hasTemporaryDrawing || isSaving"
                @click="saveAnnotation"
            >
                <i class="fas fa-save"></i>
                Guardar
            </button>

            <button
                class="action-btn"
                @click="clearDrawing"
            >
                <i class="fas fa-eraser"></i>
                Limpiar
            </button>

            <div class="action-group">
                <button
                    class="action-btn half"
                    :disabled="!annotationsStore.canUndo"
                    @click="undoAction"
                    title="Deshacer"
                >
                    <i class="fas fa-undo"></i>
                </button>
                <button
                    class="action-btn half"
                    :disabled="!annotationsStore.canRedo"
                    @click="redoAction"
                    title="Rehacer"
                >
                    <i class="fas fa-redo"></i>
                </button>
            </div>

            <button
                class="action-btn close-btn"
                @click="closeAnnotationMode"
            >
                <i class="fas fa-times"></i>
                Cerrar
            </button>
        </div>
    </div>
</template>

<style scoped>
.annotation-toolbar {
    position: absolute;
    width: 125px;
    max-height: calc(100vh - 150px);
    background: rgba(0, 0, 0, 0.95);
    border: 1px solid #FFC300;
    border-radius: 4px;
    padding: 5px;
    z-index: 20;
    box-shadow: 0 4px 12px rgba(255, 195, 0, 0.3);
    overflow-y: auto;
    overflow-x: hidden;
    transition: box-shadow 0.2s;
}

.annotation-toolbar.is-dragging {
    box-shadow: 0 6px 20px rgba(255, 195, 0, 0.5);
    cursor: grabbing;
}

.annotation-toolbar::-webkit-scrollbar {
    width: 4px;
}

.annotation-toolbar::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.annotation-toolbar::-webkit-scrollbar-thumb {
    background: #FFC300;
    border-radius: 2px;
}

.toolbar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 5px;
    padding-bottom: 4px;
    border-bottom: 1px solid #333;
    cursor: grab;
    user-select: none;
}

.toolbar-header:active {
    cursor: grabbing;
}

.toolbar-title {
    margin: 0;
    font-size: 10px;
    color: #FFC300;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 3px;
}

.drag-handle {
    opacity: 0.5;
    font-size: 9px;
}

.toolbar-header:hover .drag-handle {
    opacity: 1;
}

.annotation-count {
    background: #FFC300;
    color: #000;
    font-size: 8px;
    font-weight: 600;
    padding: 1px 3px;
    border-radius: 8px;
    min-width: 14px;
    text-align: center;
}

.toolbar-section {
    margin-bottom: 3px;
}

.toolbar-label {
    font-size: 8px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 3px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.tool-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 3px;
}

.tool-btn {
    background: #1a1a1a;
    border: 1px solid #333;
    color: #fff;
    padding: 4px;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.2s;
}

.tool-btn:hover {
    background: #2a2a2a;
    border-color: #FFC300;
}

.tool-btn.active {
    background: #FFC300;
    color: #000;
    border-color: #FFC300;
}

.tool-btn i {
    font-size: 10px;
}

.color-preview {
    width: 100%;
    height: 24px;
    border: 2px solid #333;
    border-radius: 3px;
    cursor: pointer;
    transition: border-color 0.2s;
}

.color-preview:hover {
    border-color: #FFC300;
}

.color-picker {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 3px;
    margin-top: 4px;
}

.color-option {
    width: 100%;
    height: 20px;
    border: 2px solid transparent;
    border-radius: 3px;
    cursor: pointer;
    transition: border-color 0.2s;
}

.color-option:hover,
.color-option.active {
    border-color: #FFC300;
}

.action-btn {
    width: 100%;
    background: #1a1a1a;
    border: 1px solid #333;
    color: #fff;
    padding: 4px;
    border-radius: 3px;
    cursor: pointer;
    margin-bottom: 2px;
    font-size: 9px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
}

.action-btn:hover:not(:disabled) {
    background: #2a2a2a;
    border-color: #FFC300;
}

.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.action-btn.save-btn {
    background: #FFC300;
    color: #000;
    font-weight: 600;
}

.action-btn.save-btn:hover:not(:disabled) {
    background: #00d4d2;
}

.action-btn.close-btn {
    background: #dc3545;
    border-color: #dc3545;
    margin-top: 2px;
}

.action-btn.close-btn:hover {
    background: #c82333;
    border-color: #c82333;
}

.action-group {
    display: flex;
    gap: 3px;
    margin-bottom: 2px;
}

.action-btn.half {
    width: calc(50% - 1.5px);
    margin-bottom: 0;
}

.duration-select {
    width: 100%;
    background: #1a1a1a;
    border: 1px solid #333;
    color: #fff;
    padding: 3px 5px;
    border-radius: 3px;
    font-size: 9px;
    cursor: pointer;
}

.duration-select:hover {
    border-color: #FFC300;
}

.spotlight-btn {
    background: #FFC300;
    color: #000;
    font-weight: 600;
}

.spotlight-btn:hover {
    background: #00d4d2;
}

.symbol-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2px;
}

.symbol-btn {
    background: #1a1a1a;
    border: 1px solid #333;
    padding: 3px;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.symbol-btn:hover {
    background: #2a2a2a;
    border-color: #FFC300;
    transform: scale(1.05);
}

.symbol-btn i {
    font-size: 10px;
}
</style>
