<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="modal-backdrop" @click.self="handleClose">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <!-- Header -->
                        <div class="modal-header">
                            <h3 class="modal-title">
                                <i class="fas fa-video-plus"></i>
                                Asociar ángulo de cámara
                            </h3>
                            <button
                                type="button"
                                class="btn-close"
                                @click="handleClose"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <!-- Search input -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-search"></i>
                                    Buscar video
                                </label>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-control"
                                    placeholder="Buscar por título..."
                                />
                            </div>

                            <!-- Available videos list -->
                            <div class="videos-list">
                                <div
                                    v-if="filteredVideos.length === 0"
                                    class="empty-state"
                                >
                                    <i class="fas fa-folder-open"></i>
                                    <p>No hay videos disponibles para asociar</p>
                                </div>

                                <div
                                    v-for="video in filteredVideos"
                                    :key="video.id"
                                    class="video-item"
                                    :class="{ 'is-selected': selectedVideoId === video.id }"
                                    @click="selectedVideoId = video.id"
                                >
                                    <div class="video-info">
                                        <h4 class="video-title">{{ video.title }}</h4>
                                        <p class="video-meta">
                                            <span>{{ formatDate(video.match_date) }}</span>
                                            <span>•</span>
                                            <span>{{ formatDuration(video.duration) }}</span>
                                        </p>
                                    </div>
                                    <div class="video-check">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuration -->
                            <div v-if="selectedVideoId" class="config-section">
                                <h4 class="config-title">Configuración</h4>

                                <div class="form-group">
                                    <label class="form-label">
                                        Nombre del ángulo
                                    </label>
                                    <input
                                        v-model="cameraAngleName"
                                        type="text"
                                        class="form-control"
                                        placeholder="Ej: Cámara lateral, Vista aérea..."
                                    />
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Offset de sincronización (segundos)
                                    </label>
                                    <div class="offset-control">
                                        <input
                                            v-model.number="syncOffset"
                                            type="number"
                                            step="0.1"
                                            class="form-control"
                                        />
                                        <span class="offset-hint">
                                            Positivo = video comienza después
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Error message -->
                            <div v-if="errorMessage" class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{ errorMessage }}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="handleClose"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                :disabled="!selectedVideoId || isSubmitting"
                                @click="handleSubmit"
                            >
                                <i
                                    class="fas"
                                    :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-check'"
                                ></i>
                                {{ isSubmitting ? 'Asociando...' : 'Asociar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { Video } from '@/types/video-player';

const props = defineProps<{
    show: boolean;
    videoId: number;
    excludedIds?: number[];
}>();

const emit = defineEmits<{
    close: [];
    associated: [videoId: number, cameraAngleName: string, syncOffset: number];
}>();

// State
const availableVideos = ref<Video[]>([]);
const searchQuery = ref('');
const selectedVideoId = ref<number | null>(null);
const cameraAngleName = ref('');
const syncOffset = ref(0);
const isSubmitting = ref(false);
const errorMessage = ref('');

// Computed
const filteredVideos = computed(() => {
    if (!searchQuery.value) return availableVideos.value;
    const query = searchQuery.value.toLowerCase();
    return availableVideos.value.filter((video: any) =>
        video.title.toLowerCase().includes(query)
    );
});

function filterVideos(videos: any[]) {
    const excluded = new Set([props.videoId, ...(props.excludedIds ?? [])]);
    return videos.filter((v: any) => !excluded.has(v.id));
}

// Methods
async function loadAvailableVideos() {
    try {
        const res = await fetch(`/videos/search-for-angles?query=`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        availableVideos.value = filterVideos(data.videos ?? []);
    } catch (error) {
        console.error('Failed to load available videos:', error);
        errorMessage.value = 'Error al cargar los videos disponibles';
    }
}

async function handleSubmit() {
    if (!selectedVideoId.value) return;

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(`/videos/${props.videoId}/multi-camera/associate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                slave_video_id: selectedVideoId.value,
                camera_angle: cameraAngleName.value || 'Ángulo adicional',
                sync_offset: syncOffset.value,
            }),
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Error al asociar el ángulo');
        }

        emit('associated', selectedVideoId.value, cameraAngleName.value, syncOffset.value);
        resetForm();
        emit('close');
    } catch (error: any) {
        console.error('Failed to associate camera angle:', error);
        errorMessage.value = error.message || 'Error al asociar el ángulo de cámara';
    } finally {
        isSubmitting.value = false;
    }
}

function handleClose() {
    if (!isSubmitting.value) {
        resetForm();
        emit('close');
    }
}

function resetForm() {
    searchQuery.value = '';
    selectedVideoId.value = null;
    cameraAngleName.value = '';
    syncOffset.value = 0;
    errorMessage.value = '';
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatDuration(seconds: number | null): string {
    if (!seconds) return '00:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// Watch
watch(() => props.show, (newValue) => {
    if (newValue) {
        loadAvailableVideos();
    }
});

watch(searchQuery, async (q) => {
    try {
        const res = await fetch(`/videos/search-for-angles?query=${encodeURIComponent(q)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        availableVideos.value = filterVideos(data.videos ?? []);
    } catch (e) {
        // keep existing list on error
    }
});
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.modal-dialog {
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-content {
    background: #1a1a1a;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #252525;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-title i {
    color: #FFC300;
}

.btn-close {
    background: transparent;
    border: none;
    color: #999;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.2s;
}

.btn-close:hover {
    color: #ffffff;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.form-label i {
    color: #FFC300;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    background: #252525;
    border: 1px solid #333;
    border-radius: 6px;
    color: #ffffff;
    font-size: 0.9rem;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #FFC300;
}

.videos-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #252525;
    border-radius: 8px;
    background: #0f0f0f;
}

.empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: #666;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.empty-state p {
    margin: 0;
    font-size: 0.9rem;
}

.video-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid #252525;
    cursor: pointer;
    transition: background 0.2s;
}

.video-item:last-child {
    border-bottom: none;
}

.video-item:hover {
    background: #1a1a1a;
}

.video-item.is-selected {
    background: rgba(255, 195, 0, 0.1);
    border-left: 3px solid #FFC300;
}

.video-info {
    flex: 1;
}

.video-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #ffffff;
    margin: 0 0 0.25rem 0;
}

.video-meta {
    font-size: 0.8rem;
    color: #999;
    margin: 0;
    display: flex;
    gap: 0.5rem;
}

.video-check {
    color: #FFC300;
    font-size: 1.25rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.video-item.is-selected .video-check {
    opacity: 1;
}

.config-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #252525;
}

.config-title {
    font-size: 1rem;
    font-weight: 600;
    color: #ffffff;
    margin: 0 0 1rem 0;
}

.offset-control {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.offset-hint {
    font-size: 0.75rem;
    color: #999;
    font-style: italic;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-danger {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #dc3545;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.5rem;
    border-top: 1px solid #252525;
}

.btn {
    padding: 0.625rem 1.5rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-secondary {
    background: #252525;
    color: #ffffff;
}

.btn-secondary:hover {
    background: #333;
}

.btn-primary {
    background: linear-gradient(135deg, #FFC300, #005461);
    color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 195, 0, 0.3);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

@media (max-width: 768px) {
    .modal-dialog {
        max-width: 100%;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 1rem;
    }

    .videos-list {
        max-height: 200px;
    }
}
</style>
