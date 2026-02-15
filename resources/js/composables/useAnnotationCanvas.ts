import { ref, type Ref } from 'vue';
import type { AnnotationTool } from '@/stores/annotationsStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';

// Fabric.js will be dynamically imported
type FabricCanvas = any;
type FabricObject = any;

interface Point {
    x: number;
    y: number;
}

// Singleton instance
let instance: ReturnType<typeof createAnnotationCanvas> | null = null;

function createAnnotationCanvas() {
    const fabricCanvas = ref<FabricCanvas | null>(null);
    const isDrawing = ref(false);
    const currentObject = ref<FabricObject | null>(null);
    const startPoint = ref<Point | null>(null);
    const currentTool = ref<AnnotationTool>('select');
    const currentColor = ref('#ff0000');
    const areaPoints = ref<Point[]>([]);

    let fabric: any = null;

    async function initCanvas(canvasElement: HTMLCanvasElement, videoElement: HTMLVideoElement) {
        console.log('🎨 Initializing annotation canvas...', {
            canvasElement,
            videoElement,
            videoDimensions: {
                width: videoElement.clientWidth,
                height: videoElement.clientHeight
            }
        });

        // Dynamic import to reduce bundle size
        const fabricModule = await import('fabric');
        fabric = fabricModule.fabric;

        fabricCanvas.value = new fabric.Canvas(canvasElement, {
            isDrawingMode: false,
            selection: false,
            backgroundColor: 'transparent',
        });

        // Force Fabric.js wrapper to be positioned correctly and transparent to pointer events
        const fabricWrapper = canvasElement.parentElement;
        if (fabricWrapper && fabricWrapper.classList.contains('canvas-container')) {
            fabricWrapper.style.position = 'absolute';
            fabricWrapper.style.top = '0';
            fabricWrapper.style.left = '0';
            fabricWrapper.style.width = '100%';
            fabricWrapper.style.height = '100%';
            fabricWrapper.style.pointerEvents = 'none'; // Make wrapper transparent to clicks
            console.log('✅ Fabric wrapper positioned absolutely and pointer-transparent');
        }

        // Disable pointer events by default (will be enabled when annotation mode is activated)
        if (fabricCanvas.value.lowerCanvasEl) {
            fabricCanvas.value.lowerCanvasEl.style.pointerEvents = 'none';
        }
        if (fabricCanvas.value.upperCanvasEl) {
            fabricCanvas.value.upperCanvasEl.style.pointerEvents = 'none';
        }
        console.log('🚫 Canvas pointer events disabled by default');

        // Set canvas dimensions to match video
        resizeCanvas(videoElement);

        // Setup event listeners
        fabricCanvas.value.on('mouse:down', handleMouseDown);
        fabricCanvas.value.on('mouse:move', handleMouseMove);
        fabricCanvas.value.on('mouse:up', handleMouseUp);
        fabricCanvas.value.on('mouse:dblclick', handleDoubleClick);

        // Setup object change listeners to enable save button
        fabricCanvas.value.on('object:added', handleObjectChange);
        fabricCanvas.value.on('object:modified', handleObjectChange);

        console.log('✅ Canvas initialized successfully', {
            width: fabricCanvas.value.width,
            height: fabricCanvas.value.height
        });

        return fabricCanvas.value;
    }

    function resizeCanvas(videoElement: HTMLVideoElement) {
        if (!fabricCanvas.value) return;

        const rect = videoElement.getBoundingClientRect();
        console.log('📐 Resizing canvas:', {
            videoWidth: rect.width,
            videoHeight: rect.height,
            videoOffsetWidth: videoElement.offsetWidth,
            videoOffsetHeight: videoElement.offsetHeight,
            videoClientWidth: videoElement.clientWidth,
            videoClientHeight: videoElement.clientHeight,
        });
        fabricCanvas.value.setWidth(rect.width);
        fabricCanvas.value.setHeight(rect.height);
        fabricCanvas.value.renderAll();
    }

    function setTool(tool: AnnotationTool) {
        currentTool.value = tool;

        if (!fabricCanvas.value) return;

        // Reset drawing mode
        fabricCanvas.value.isDrawingMode = tool === 'free_draw';
        fabricCanvas.value.selection = tool === 'select';

        if (tool === 'free_draw') {
            fabricCanvas.value.freeDrawingBrush.color = currentColor.value;
            fabricCanvas.value.freeDrawingBrush.width = 3;
        }

        // Reset area points when switching tools
        if (tool !== 'area') {
            areaPoints.value = [];
        }
    }

    function setColor(color: string) {
        currentColor.value = color;
        if (fabricCanvas.value && fabricCanvas.value.freeDrawingBrush) {
            fabricCanvas.value.freeDrawingBrush.color = color;
        }
    }

    function handleMouseDown(event: any) {
        console.log('🖱️ Mouse down event:', {
            hasFabricCanvas: !!fabricCanvas.value,
            hasPointer: !!event.pointer,
            currentTool: currentTool.value,
            pointer: event.pointer
        });

        if (!fabricCanvas.value || !event.pointer) {
            console.error('❌ Missing fabricCanvas or pointer');
            return;
        }

        const pointer = event.pointer;
        startPoint.value = { x: pointer.x, y: pointer.y };

        if (currentTool.value === 'select' || currentTool.value === 'free_draw') {
            console.log('⏩ Skipping createShape for tool:', currentTool.value);
            return;
        }

        if (currentTool.value === 'area') {
            handleAreaClick(pointer);
            return;
        }

        isDrawing.value = true;
        console.log('✏️ Creating shape:', currentTool.value);
        createShape(pointer);
    }

    function handleMouseMove(event: any) {
        if (!isDrawing.value || !currentObject.value || !startPoint.value || !event.pointer) return;

        const pointer = event.pointer;
        updateShape(pointer);
        fabricCanvas.value?.renderAll();
    }

    function handleMouseUp() {
        if (currentTool.value === 'area' || currentTool.value === 'text') {
            return;
        }

        isDrawing.value = false;
        currentObject.value = null;
        startPoint.value = null;
    }

    function handleDoubleClick(event: any) {
        if (currentTool.value === 'area' && areaPoints.value.length >= 3) {
            console.log('🖱️ Double click detected - completing area');
            completeArea();
        }
    }

    let objectChangeTimeout: ReturnType<typeof setTimeout> | null = null;
    function handleObjectChange() {
        // Debounce to avoid too many updates
        if (objectChangeTimeout) clearTimeout(objectChangeTimeout);
        objectChangeTimeout = setTimeout(() => {
            const annotationsStore = useAnnotationsStore();
            const state = getCanvasJSON();
            if (state && annotationsStore.annotationMode) {
                annotationsStore.pushToUndoStack(state);
                console.log('💾 Object change detected - save button enabled');
            }
        }, 300);
    }

    function createShape(pointer: Point) {
        if (!fabric || !fabricCanvas.value) return;

        const options = {
            stroke: currentColor.value,
            strokeWidth: 3,
            fill: 'transparent',
            selectable: currentTool.value === 'select',
        };

        switch (currentTool.value) {
            case 'arrow':
                currentObject.value = createArrow(startPoint.value!, pointer, options);
                break;
            case 'line':
                currentObject.value = new fabric.Line(
                    [startPoint.value!.x, startPoint.value!.y, pointer.x, pointer.y],
                    options
                );
                fabricCanvas.value.add(currentObject.value);
                break;
            case 'circle':
                currentObject.value = new fabric.Circle({
                    ...options,
                    left: startPoint.value!.x,
                    top: startPoint.value!.y,
                    radius: 0,
                    originX: 'center',
                    originY: 'center',
                });
                fabricCanvas.value.add(currentObject.value);
                break;
            case 'rectangle':
                currentObject.value = new fabric.Rect({
                    ...options,
                    left: startPoint.value!.x,
                    top: startPoint.value!.y,
                    width: 0,
                    height: 0,
                });
                fabricCanvas.value.add(currentObject.value);
                break;
            case 'text':
                handleTextCreation(pointer);
                break;
        }
    }

    function updateShape(pointer: Point) {
        if (!currentObject.value || !startPoint.value) return;

        switch (currentTool.value) {
            case 'arrow':
            case 'line':
                currentObject.value.set({
                    x2: pointer.x,
                    y2: pointer.y,
                });
                // Update arrow head for arrow tool
                if (currentTool.value === 'arrow' && currentObject.value.type === 'group') {
                    updateArrowHead(currentObject.value, startPoint.value, pointer);
                }
                break;
            case 'circle':
                const radius = Math.sqrt(
                    Math.pow(pointer.x - startPoint.value.x, 2) +
                    Math.pow(pointer.y - startPoint.value.y, 2)
                );
                currentObject.value.set({ radius });
                break;
            case 'rectangle':
                const width = pointer.x - startPoint.value.x;
                const height = pointer.y - startPoint.value.y;
                currentObject.value.set({
                    width: Math.abs(width),
                    height: Math.abs(height),
                    left: width > 0 ? startPoint.value.x : pointer.x,
                    top: height > 0 ? startPoint.value.y : pointer.y,
                });
                break;
        }
    }

    function createArrow(from: Point, to: Point, options: any): FabricObject {
        if (!fabric || !fabricCanvas.value) return null;

        const line = new fabric.Line([from.x, from.y, to.x, to.y], {
            ...options,
            strokeWidth: 2,
        });

        const arrow = createArrowHead(from, to, options);

        const group = new fabric.Group([line, arrow], {
            selectable: currentTool.value === 'select',
        });

        fabricCanvas.value.add(group);
        return group;
    }

    function createArrowHead(from: Point, to: Point, options: any): FabricObject {
        if (!fabric) return null;

        const angle = Math.atan2(to.y - from.y, to.x - from.x);
        const headLength = 15;

        const points = [
            { x: to.x, y: to.y },
            {
                x: to.x - headLength * Math.cos(angle - Math.PI / 6),
                y: to.y - headLength * Math.sin(angle - Math.PI / 6)
            },
            {
                x: to.x - headLength * Math.cos(angle + Math.PI / 6),
                y: to.y - headLength * Math.sin(angle + Math.PI / 6)
            },
        ];

        return new fabric.Polygon(points, {
            fill: options.stroke,
            stroke: options.stroke,
            strokeWidth: 1,
            selectable: false,
        });
    }

    function updateArrowHead(group: FabricObject, from: Point, to: Point) {
        if (!group || group._objects.length < 2) return;

        const line = group._objects[0];
        const arrowHead = group._objects[1];

        const angle = Math.atan2(to.y - from.y, to.x - from.x);
        const headLength = 15;

        const points = [
            { x: to.x, y: to.y },
            {
                x: to.x - headLength * Math.cos(angle - Math.PI / 6),
                y: to.y - headLength * Math.sin(angle - Math.PI / 6)
            },
            {
                x: to.x - headLength * Math.cos(angle + Math.PI / 6),
                y: to.y - headLength * Math.sin(angle + Math.PI / 6)
            },
        ];

        arrowHead.set({ points });
    }

    function handleAreaClick(pointer: Point) {
        if (!fabric || !fabricCanvas.value) return;

        areaPoints.value.push({ x: pointer.x, y: pointer.y });

        // Draw point indicator (marked as temporary)
        const circle = new fabric.Circle({
            left: pointer.x,
            top: pointer.y,
            radius: 4,
            fill: currentColor.value,
            selectable: false,
            originX: 'center',
            originY: 'center',
            isAreaIndicator: true, // Mark as temporary
        } as any);
        fabricCanvas.value.add(circle);

        // Draw line from previous point (marked as temporary)
        if (areaPoints.value.length > 1) {
            const prev = areaPoints.value[areaPoints.value.length - 2];
            const line = new fabric.Line([prev.x, prev.y, pointer.x, pointer.y], {
                stroke: currentColor.value,
                strokeWidth: 2,
                selectable: false,
                isAreaIndicator: true, // Mark as temporary
            } as any);
            fabricCanvas.value.add(line);
        }

        fabricCanvas.value.renderAll();
    }

    function completeArea() {
        if (!fabric || !fabricCanvas.value || areaPoints.value.length < 3) return;

        // Remove only temporary area indicators (circles and lines)
        const objects = fabricCanvas.value.getObjects();
        const toRemove = objects.filter((obj: any) => obj.isAreaIndicator === true);
        toRemove.forEach((obj: any) => fabricCanvas.value!.remove(obj));

        // Create polygon
        const polygon = new fabric.Polygon(areaPoints.value, {
            stroke: currentColor.value,
            strokeWidth: 3,
            fill: currentColor.value + '20', // 20% opacity
            selectable: currentTool.value === 'select',
        });

        fabricCanvas.value.add(polygon);
        fabricCanvas.value.renderAll();

        areaPoints.value = [];
    }

    function handleTextCreation(pointer: Point) {
        if (!fabric || !fabricCanvas.value) return;

        const text = prompt('Ingrese el texto:');
        if (!text) return;

        const textObject = new fabric.IText(text, {
            left: pointer.x,
            top: pointer.y,
            fill: currentColor.value,
            fontSize: 24,
            fontWeight: 'bold',
            selectable: currentTool.value === 'select',
        });

        fabricCanvas.value.add(textObject);
        fabricCanvas.value.renderAll();

        isDrawing.value = false;
        currentObject.value = null;
    }

    function clearCanvas() {
        if (!fabricCanvas.value) return;
        fabricCanvas.value.clear();
        areaPoints.value = [];
    }

    function getCanvasJSON(): string {
        if (!fabricCanvas.value) return '';
        return JSON.stringify(fabricCanvas.value.toJSON());
    }

    function loadFromJSON(json: string) {
        if (!fabricCanvas.value || !json) return;

        try {
            const data = JSON.parse(json);
            fabricCanvas.value.loadFromJSON(data, () => {
                fabricCanvas.value?.renderAll();
            });
        } catch (e) {
            console.error('Error loading annotation JSON:', e);
        }
    }

    function undo(previousState: string | null) {
        if (previousState) {
            loadFromJSON(previousState);
        } else {
            clearCanvas();
        }
    }

    function redo(nextState: string) {
        loadFromJSON(nextState);
    }

    function addSpotlight(color: string) {
        if (!fabric || !fabricCanvas.value) return;

        const centerX = fabricCanvas.value.width / 2;
        const centerY = fabricCanvas.value.height / 2;

        // Inner ring (subtle)
        const innerRing = new fabric.Circle({
            left: centerX,
            top: centerY,
            radius: 75,
            fill: 'transparent',
            stroke: `${color}4D`, // 30% opacity
            strokeWidth: 8,
            originX: 'center',
            originY: 'center',
            selectable: false,
            evented: false,
        });

        // Outer ring (bright with glow)
        const spotlight = new fabric.Circle({
            left: centerX,
            top: centerY,
            radius: 80,
            fill: 'transparent',
            stroke: color,
            strokeWidth: 4,
            originX: 'center',
            originY: 'center',
            selectable: true,
            shadow: {
                color: `${color}99`, // 60% opacity
                blur: 20,
                offsetX: 0,
                offsetY: 0,
            },
        });

        // Group both circles
        const spotlightGroup = new fabric.Group([innerRing, spotlight], {
            left: centerX,
            top: centerY,
            originX: 'center',
            originY: 'center',
            selectable: true,
            evented: true,
        });

        fabricCanvas.value.add(spotlightGroup);
        fabricCanvas.value.setActiveObject(spotlightGroup);
        fabricCanvas.value.renderAll();

        console.log('✨ Spotlight added');
    }

    function addSymbol(symbolType: string) {
        if (!fabric || !fabricCanvas.value) return;

        const centerX = fabricCanvas.value.width / 2;
        const centerY = fabricCanvas.value.height / 2;
        let symbol: FabricObject;

        switch (symbolType) {
            case 'tackle': {
                // Starburst with 8 spikes
                const points: Point[] = [];
                const outerRadius = 25;
                const innerRadius = 12;
                const spikes = 8;

                for (let i = 0; i < spikes * 2; i++) {
                    const radius = i % 2 === 0 ? outerRadius : innerRadius;
                    const angle = (Math.PI / spikes) * i - Math.PI / 2;
                    points.push({
                        x: radius * Math.cos(angle),
                        y: radius * Math.sin(angle),
                    });
                }

                symbol = new fabric.Polygon(points, {
                    left: centerX,
                    top: centerY,
                    fill: '#dc3545',
                    stroke: '#fff',
                    strokeWidth: 2,
                    originX: 'center',
                    originY: 'center',
                    shadow: {
                        color: 'rgba(220, 53, 69, 0.5)',
                        blur: 10,
                        offsetX: 0,
                        offsetY: 0,
                    },
                });
                break;
            }

            case 'ball': {
                // Rugby ball (rotated ellipse)
                symbol = new fabric.Ellipse({
                    left: centerX,
                    top: centerY,
                    rx: 20,
                    ry: 12,
                    fill: '#8B4513',
                    stroke: '#fff',
                    strokeWidth: 2,
                    angle: -30,
                    originX: 'center',
                    originY: 'center',
                });
                break;
            }

            case 'x': {
                // X mark (two diagonal lines)
                const line1 = new fabric.Line([-15, -15, 15, 15], {
                    stroke: '#dc3545',
                    strokeWidth: 6,
                    strokeLineCap: 'round',
                });
                const line2 = new fabric.Line([15, -15, -15, 15], {
                    stroke: '#dc3545',
                    strokeWidth: 6,
                    strokeLineCap: 'round',
                });
                symbol = new fabric.Group([line1, line2], {
                    left: centerX,
                    top: centerY,
                    originX: 'center',
                    originY: 'center',
                    shadow: {
                        color: 'rgba(0,0,0,0.4)',
                        blur: 4,
                        offsetX: 0,
                        offsetY: 0,
                    },
                });
                break;
            }

            case 'check': {
                // Checkmark
                const checkPath = new fabric.Path('M -12 0 L -4 10 L 15 -12', {
                    fill: 'transparent',
                    stroke: '#28a745',
                    strokeWidth: 6,
                    strokeLineCap: 'round',
                    strokeLineJoin: 'round',
                });
                symbol = new fabric.Group([checkPath], {
                    left: centerX,
                    top: centerY,
                    originX: 'center',
                    originY: 'center',
                    shadow: {
                        color: 'rgba(0,0,0,0.4)',
                        blur: 4,
                        offsetX: 0,
                        offsetY: 0,
                    },
                });
                break;
            }

            default:
                console.warn('Unknown symbol type:', symbolType);
                return;
        }

        // Make symbol selectable but not scalable/rotatable
        symbol.set({
            selectable: true,
            evented: true,
            hasControls: false,
            hasBorders: true,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
        });

        fabricCanvas.value.add(symbol);
        fabricCanvas.value.setActiveObject(symbol);
        fabricCanvas.value.renderAll();

        console.log('🔣 Symbol added:', symbolType);
    }

    function dispose() {
        if (!fabricCanvas.value) return;

        fabricCanvas.value.off('mouse:down');
        fabricCanvas.value.off('mouse:move');
        fabricCanvas.value.off('mouse:up');
        fabricCanvas.value.off('mouse:dblclick');
        fabricCanvas.value.dispose();
        fabricCanvas.value = null;
    }

    return {
        fabricCanvas,
        isDrawing,
        initCanvas,
        resizeCanvas,
        setTool,
        setColor,
        clearCanvas,
        getCanvasJSON,
        loadFromJSON,
        undo,
        redo,
        completeArea,
        addSpotlight,
        addSymbol,
        dispose,
    };
}

export function useAnnotationCanvas() {
    if (!instance) {
        instance = createAnnotationCanvas();
    }
    return instance;
}
