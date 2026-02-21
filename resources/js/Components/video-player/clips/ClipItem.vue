<script setup lang="ts">
import { computed, inject, ref, onMounted, onBeforeUnmount } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useClipsStore } from '@/stores/clipsStore';
import { formatTime } from '@/stores/videoStore';
import type { VideoClip } from '@/types/video-player';

const props = defineProps<{
    clip: VideoClip;
}>();

const videoStore = useVideoStore();
const clipsStore = useClipsStore();
const toast = inject<any>('toast');
const isDeleting = ref(false);
const showMenu = ref(false);
const menuRef = ref<HTMLElement | null>(null);

const formattedStartTime = computed(() => formatTime(props.clip.start_time));
const formattedEndTime = computed(() => formatTime(props.clip.end_time));

const bunnyEmbedUrl = computed(() => {
    const v = videoStore.video;
    if (!v?.bunny_library_id || !v?.bunny_video_id) return null;
    const start = Math.floor(props.clip.start_time);
    const end = Math.ceil(props.clip.end_time);
    return `https://iframe.mediadelivery.net/embed/${v.bunny_library_id}/${v.bunny_video_id}?start=${start}&end=${end}&autoplay=true`;
});

function handleSeek() {
    videoStore.seek(props.clip.start_time);
    videoStore.play();
}

function toggleMenu(event: MouseEvent) {
    event.stopPropagation();
    showMenu.value = !showMenu.value;
}

function playClip(event: MouseEvent) {
    event.stopPropagation();
    showMenu.value = false;
    handleSeek();
}

async function copyLink(event: MouseEvent) {
    event.stopPropagation();
    showMenu.value = false;
    if (!bunnyEmbedUrl.value) {
        toast?.error('No hay link de Bunny disponible para este video');
        return;
    }
    try {
        await navigator.clipboard.writeText(bunnyEmbedUrl.value);
        toast?.success('¡Link copiado!');
    } catch {
        toast?.error('No se pudo copiar el link');
    }
}

async function handleDelete(event: MouseEvent) {
    event.stopPropagation();
    showMenu.value = false;
    if (isDeleting.value) return;
    if (!confirm('¿Estás seguro de que deseas eliminar este clip?')) return;

    isDeleting.value = true;
    try {
        await clipsStore.removeClip(props.clip.video_id, props.clip.id);
        toast?.success('Clip eliminado');
    } catch (error) {
        console.error('Error deleting clip:', error);
        toast?.error('Error al eliminar el clip');
        isDeleting.value = false;
    }
}

function onClickOutside(event: MouseEvent) {
    if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
        showMenu.value = false;
    }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>

<template>
    <div class="clip-item" @click="handleSeek">
        <i class="fas fa-play-circle clip-icon"></i>
        <div class="clip-time">
            {{ formattedStartTime }} - {{ formattedEndTime }}
        </div>

        <!-- ⋯ menu button -->
        <div ref="menuRef" class="clip-menu-wrapper">
            <button
                class="btn-clip-menu"
                :class="{ active: showMenu, deleting: isDeleting }"
                :disabled="isDeleting"
                title="Opciones"
                @click="toggleMenu"
            >
                <i :class="isDeleting ? 'fas fa-spinner fa-spin' : 'fas fa-ellipsis-h'"></i>
            </button>

            <div v-show="showMenu" class="clip-dropdown">
                <button class="clip-dropdown-item" @click="playClip">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button
                    v-if="bunnyEmbedUrl"
                    class="clip-dropdown-item"
                    @click="copyLink"
                >
                    <i class="fas fa-link"></i> Copiar link
                </button>
                <button class="clip-dropdown-item clip-dropdown-item--danger" @click="handleDelete">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.clip-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.5rem;
    margin-bottom: 0.25rem;
    background-color: #252525;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.15s;
    position: relative;
}

.clip-item:hover {
    background-color: #2a2a2a;
    transform: translateX(3px);
}

.clip-icon {
    color: #00B7B5;
    font-size: 12px;
    flex-shrink: 0;
}

.clip-time {
    color: #ccc;
    font-size: 10px;
    font-weight: 500;
    white-space: nowrap;
    flex: 1;
}

/* ⋯ button */
.clip-menu-wrapper {
    position: relative;
    margin-left: auto;
}

.btn-clip-menu {
    background: transparent;
    border: none;
    color: #888;
    padding: 0.2rem 0.4rem;
    cursor: pointer;
    font-size: 10px;
    border-radius: 3px;
    transition: all 0.2s;
    opacity: 0;
    line-height: 1;
}

.clip-item:hover .btn-clip-menu,
.btn-clip-menu.active {
    opacity: 1;
}

.btn-clip-menu:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
}

.btn-clip-menu:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* dropdown */
.clip-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 2px);
    background-color: #2c2c2c;
    border: 1px solid #444;
    border-radius: 4px;
    min-width: 130px;
    z-index: 100;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}

.clip-dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    width: 100%;
    padding: 0.4rem 0.65rem;
    background: transparent;
    border: none;
    color: #ccc;
    font-size: 10.5px;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s;
    white-space: nowrap;
}

.clip-dropdown-item i {
    width: 12px;
    text-align: center;
    color: #888;
    flex-shrink: 0;
}

.clip-dropdown-item:hover {
    background: rgba(255, 255, 255, 0.07);
    color: #fff;
}

.clip-dropdown-item:hover i {
    color: #00B7B5;
}

.clip-dropdown-item--danger {
    color: #e06c75;
}

.clip-dropdown-item--danger i {
    color: #e06c75;
}

.clip-dropdown-item--danger:hover {
    background: rgba(220, 53, 69, 0.1);
    color: #ff6b6b;
}

.clip-dropdown-item--danger:hover i {
    color: #ff6b6b;
}
</style>
