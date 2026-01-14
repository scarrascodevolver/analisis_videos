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

// Constantes
const BALL_OFFSET_X = 30;
const BALL_OFFSET_Y = 0;
const ANIMATION_DURATION = 3000;

// Variable para rastrear quién tiene el balón durante la reproducción
let playbackBallHolder = null;

// Preview de línea mientras dibuja
let previewLine = null;
let isMouseDown = false;

console.log('📦 app.js cargado - Estado global inicializado');
