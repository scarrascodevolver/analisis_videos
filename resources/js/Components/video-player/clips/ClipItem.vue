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
const currentUserId = inject<number>('currentUserId', 0);

const isDeleting = ref(false);
const showMenu = ref(false);
const btnRef = ref<HTMLElement | null>(null);
const dropdownStyle = ref({ top: '0px', left: '0px' });

// ¿Este clip pertenece al usuario actual?
const isOwner = computed(() => props.clip.created_by === currentUserId);

// ¿Es un clip de XML (categoría scope='video')? — siempre visible a todos
const isXmlClip = computed(() => props.clip.category?.scope === 'video');

const formattedStartTime = computed(() => formatTime(props.clip.start_time));
const formattedEndTime = computed(() => formatTime(props.clip.end_time));

const shareUrl = computed(() => `${window.location.origin}/clips/${props.clip.id}/share`);

function handleSeek() {
    videoStore.playClip(props.clip.start_time, props.clip.end_time);
}

function toggleMenu(event: MouseEvent) {
    event.stopPropagation();
    if (!showMenu.value && btnRef.value) {
        const rect = btnRef.value.getBoundingClientRect();
        dropdownStyle.value = {
            top: `${rect.bottom + 4}px`,
            left: `${rect.right - 150}px`,
        };
    }
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
    try {
        await navigator.clipboard.writeText(shareUrl.value);
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
    if (showMenu.value && btnRef.value && !btnRef.value.contains(event.target as Node)) {
        showMenu.value = false;
    }
}

onMounted(() => document.addEventListener('click', onClickOutside, true));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside, true));
</script>

<template>
    <div class="clip-item" @click="handleSeek">
        <i class="fas fa-play-circle clip-icon"></i>
        <div class="clip-time">
            {{ formattedStartTime }} - {{ formattedEndTime }}
        </div>

        <!-- Indicador de visibilidad -->
        <!-- Clip propio: muestra 🔒 privado o 👥 compartido -->
        <span
            v-if="isOwner && !isXmlClip"
            class="clip-visibility-badge"
            :class="clip.is_shared ? 'badge-shared' : 'badge-private'"
            :title="clip.is_shared ? 'Compartido con el equipo' : 'Solo vos lo ves'"
        >
            <i :class="clip.is_shared ? 'fas fa-users' : 'fas fa-lock'"></i>
        </span>
        <!-- Clip de otro analista compartido: muestra quién lo creó -->
        <span
            v-else-if="!isOwner && !isXmlClip"
            class="clip-visibility-badge badge-shared"
            :title="clip.creator?.name ? 'Creado por: ' + clip.creator.name : 'Compartido'"
        >
            <i class="fas fa-users"></i>
        </span>

        <!-- ⋯ button -->
        <button
            ref="btnRef"
            class="btn-clip-menu"
            :class="{ active: showMenu, deleting: isDeleting }"
            :disabled="isDeleting"
            title="Opciones"
            @click="toggleMenu"
        >
            <i :class="isDeleting ? 'fas fa-spinner fa-spin' : 'fas fa-ellipsis-h'"></i>
        </button>
    </div>

    <!-- Dropdown teleported to body to escape overflow:hidden parents -->
    <Teleport to="body">
        <div
            v-show="showMenu"
            class="clip-dropdown-teleport"
            :style="dropdownStyle"
            @click.stop
        >
            <button class="clip-dropdown-item" @click="playClip">
                <i class="fas fa-play"></i> Reproducir
            </button>
            <button class="clip-dropdown-item" @click="copyLink">
                <i class="fas fa-link"></i> Copiar link
            </button>

            <!-- Eliminar — solo el dueño del clip -->
            <button
                v-if="isOwner"
                class="clip-dropdown-item clip-dropdown-item--danger"
                @click="handleDelete"
            >
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    </Teleport>
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

/* Indicador de visibilidad del clip */
.clip-visibility-badge {
    font-size: 8px;
    flex-shrink: 0;
    opacity: 0.6;
    transition: opacity 0.15s;
}

.clip-item:hover .clip-visibility-badge {
    opacity: 1;
}

.badge-private i  { color: #888; }
.badge-shared i   { color: #00B7B5; }

/* ⋯ button */
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
    margin-left: auto;
    flex-shrink: 0;
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
</style>

<!-- Global styles for teleported dropdown (not scoped) -->
<style>
.clip-dropdown-teleport {
    position: fixed;
    background-color: #2c2c2c;
    border: 1px solid #444;
    border-radius: 4px;
    min-width: 150px;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
    overflow: hidden;
}

.clip-dropdown-teleport .clip-dropdown-item {
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

.clip-dropdown-teleport .clip-dropdown-item:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.clip-dropdown-teleport .clip-dropdown-item i {
    width: 12px;
    text-align: center;
    color: #888;
    flex-shrink: 0;
}

.clip-dropdown-teleport .clip-dropdown-item:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.07);
    color: #fff;
}

.clip-dropdown-teleport .clip-dropdown-item:hover:not(:disabled) i {
    color: #00B7B5;
}

.clip-dropdown-teleport .clip-dropdown-item--danger {
    color: #e06c75;
}

.clip-dropdown-teleport .clip-dropdown-item--danger i {
    color: #e06c75;
}

.clip-dropdown-teleport .clip-dropdown-item--danger:hover:not(:disabled) {
    background: rgba(220, 53, 69, 0.1);
    color: #ff6b6b;
}

.clip-dropdown-teleport .clip-dropdown-item--danger:hover:not(:disabled) i {
    color: #ff6b6b;
}
</style>
