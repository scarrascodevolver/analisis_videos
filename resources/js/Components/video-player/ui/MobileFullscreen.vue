<template>
    <button
        v-if="isMobileOrTablet"
        type="button"
        class="mobile-fullscreen-btn"
        :class="{ 'is-fullscreen': isFullscreen }"
        :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'"
        @click="toggleFullscreen"
    >
        <i
            class="fas"
            :class="isFullscreen ? 'fa-compress' : 'fa-expand'"
        ></i>
    </button>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    targetElement?: HTMLElement;
}>();

const emit = defineEmits<{
    fullscreenChange: [isFullscreen: boolean];
}>();

// State
const isFullscreen = ref(false);
const isMobileOrTablet = ref(false);

// Methods
function checkIfMobile() {
    isMobileOrTablet.value = window.innerWidth <= 768;
}

async function toggleFullscreen() {
    try {
        const element = props.targetElement || document.documentElement;

        if (!document.fullscreenElement) {
            // Enter fullscreen
            if (element.requestFullscreen) {
                await element.requestFullscreen();
            } else if ((element as any).webkitRequestFullscreen) {
                // Safari
                await (element as any).webkitRequestFullscreen();
            } else if ((element as any).mozRequestFullScreen) {
                // Firefox
                await (element as any).mozRequestFullScreen();
            } else if ((element as any).msRequestFullscreen) {
                // IE/Edge
                await (element as any).msRequestFullscreen();
            }
        } else {
            // Exit fullscreen
            if (document.exitFullscreen) {
                await document.exitFullscreen();
            } else if ((document as any).webkitExitFullscreen) {
                // Safari
                await (document as any).webkitExitFullscreen();
            } else if ((document as any).mozCancelFullScreen) {
                // Firefox
                await (document as any).mozCancelFullScreen();
            } else if ((document as any).msExitFullscreen) {
                // IE/Edge
                await (document as any).msExitFullscreen();
            }
        }
    } catch (error) {
        console.error('Failed to toggle fullscreen:', error);
    }
}

function handleFullscreenChange() {
    isFullscreen.value = !!document.fullscreenElement;
    emit('fullscreenChange', isFullscreen.value);
}

function handleResize() {
    checkIfMobile();
}

// Lifecycle
onMounted(() => {
    checkIfMobile();

    // Listen for fullscreen changes
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);

    // Listen for resize
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.removeEventListener('mozfullscreenchange', handleFullscreenChange);
    document.removeEventListener('MSFullscreenChange', handleFullscreenChange);
    window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
.mobile-fullscreen-btn {
    position: fixed;
    bottom: 1rem;
    right: 1rem;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FFC300, #005461);
    color: #ffffff;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    z-index: 9998;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mobile-fullscreen-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(255, 195, 0, 0.4);
}

.mobile-fullscreen-btn:active {
    transform: scale(0.95);
}

.mobile-fullscreen-btn.is-fullscreen {
    background: linear-gradient(135deg, #005461, #003d4a);
}

/* Hide on desktop */
@media (min-width: 769px) {
    .mobile-fullscreen-btn {
        display: none;
    }
}

/* Adjust position when in fullscreen */
.mobile-fullscreen-btn.is-fullscreen {
    position: absolute;
    z-index: 10000;
}
</style>
