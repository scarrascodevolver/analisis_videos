/**
 * app.js - Estado global y configuración del canvas
 * Debe cargarse PRIMERO antes de todos los demás módulos
 */

// ============================================
// CONFIGURACIÓN DEL CANVAS
// ============================================
function getCanvasDimensions() {
    const wrapper = document.querySelector('.canvas-wrapper');
    const availableWidth = wrapper.clientWidth - 32;
    const aspectRatio = 1.85;
    const height = Math.min(availableWidth / aspectRatio, window.innerHeight * 0.75);
    return {
        width: Math.floor(height * aspectRatio),
        height: Math.floor(height)
    };
}

const dims = getCanvasDimensions();
const canvas = new fabric.Canvas('playCanvas', {
    backgroundColor: '#2d5a2d',
    selection: true,
    width: dims.width,
    height: dims.height
});

// ============================================
// SISTEMA DE ESCALA RESPONSIVO
// ============================================
// Canvas de referencia (diseñado para 1920x1080)
const REFERENCE_WIDTH = 1200;
const REFERENCE_HEIGHT = 650;

// Factor de escala basado en el ancho del canvas actual
function getScaleFactor() {
    return canvas.width / REFERENCE_WIDTH;
}

// Tamaños base (para canvas de referencia)
const BASE_PLAYER_RADIUS = 20;
const BASE_PLAYER_FONT = 18;
const BASE_BALL_RADIUS_X = 18;
const BASE_BALL_RADIUS_Y = 12;

// Obtener tamaños escalados
function getPlayerRadius() {
    return Math.max(12, Math.round(BASE_PLAYER_RADIUS * getScaleFactor()));
}

function getPlayerFontSize() {
    return Math.max(10, Math.round(BASE_PLAYER_FONT * getScaleFactor()));
}

function getBallSize() {
    const scale = getScaleFactor();
    return {
        rx: Math.max(10, Math.round(BASE_BALL_RADIUS_X * scale)),
        ry: Math.max(7, Math.round(BASE_BALL_RADIUS_Y * scale))
    };
}

// Escalar coordenadas de formaciones
function scaleX(x) {
    return Math.round(x * getScaleFactor());
}

function scaleY(y) {
    return Math.round(y * (canvas.height / REFERENCE_HEIGHT));
}

// ============================================
// ESTADO GLOBAL
// ============================================

// Jugadores
let playerCounter = 1;
let players = [];
let selectedPlayer = null;

// Balón
let rugbyBall = null;

// Sistema de trayectorias
let movements = [];
let isDrawingMode = false;
let currentPath = [];
let selectedForDrawing = null;

// Sistema de animación
let originalPositions = {};
let isPlaying = false;
let currentAnimationIndex = 0;

// Sistema de posesión del balón
let ballPossession = null;
let originalBallHolder = null;
let isAssigningPossession = false;

// Sistema de pases
let isCreatingPass = false;

// Contadores
let movementIdCounter = 0;

// Constantes base (se escalan dinámicamente)
const BASE_BALL_OFFSET_X = 30;
const BASE_BALL_OFFSET_Y = 0;

// Obtener offset escalado del balón
function getBallOffset() {
    return {
        x: Math.round(BASE_BALL_OFFSET_X * getScaleFactor()),
        y: Math.round(BASE_BALL_OFFSET_Y * getScaleFactor())
    };
}

// Constantes legacy (para compatibilidad)
const BALL_OFFSET_X = BASE_BALL_OFFSET_X;
const BALL_OFFSET_Y = BASE_BALL_OFFSET_Y;
const ANIMATION_DURATION = 3000;
const PLAYER_SPEED = 200; // pixels por segundo (velocidad constante para todos)

// Variable para rastrear quién tiene el balón durante la reproducción
let playbackBallHolder = null;

// Preview de línea mientras dibuja
let previewLine = null;
let isMouseDown = false;

console.log('📦 app.js cargado - Estado global inicializado');
