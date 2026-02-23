<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import * as tus from 'tus-js-client';
import type { Video, SlaveVideo } from '@/types/video-player';

const props = defineProps<{
    show: boolean;
    video: Video;
    csrfToken?: string;
}>();

// Read CSRF from meta tag (most reliable) with prop fallback
function getCsrf(): string {
    return (
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        props.csrfToken ||
        ''
    );
}

const emit = defineEmits<{
    close: [];
    angleUploaded: [slave: SlaveVideo];
}>();

// ─── Tabs ──────────────────────────────────────────────────────
type UploadTab = 'file' | 'youtube';
const activeTab = ref<UploadTab>('file');

// ─── Preset angles ────────────────────────────────────────────
const PRESET_ANGLES = [
    'Tribuna lateral',
    'Tribuna central',
    'Drone / Aérea',
    'In-goal',
    'Diagonal',
];

// ─── Shared state ─────────────────────────────────────────────
const cameraAngle = ref('');
const customAngle = ref('');
const isUploading = ref(false);
const errorMessage = ref('');

// ─── File tab state ───────────────────────────────────────────
const selectedFile = ref<File | null>(null);
const isDragging = ref(false);
const uploadProgress = ref(0);
const fileInputRef = ref<HTMLInputElement | null>(null);

// ─── YouTube tab state ────────────────────────────────────────
const youtubeUrl = ref('');

// ─── Computed ─────────────────────────────────────────────────
const effectiveAngle = computed(() => {
    if (cameraAngle.value === '__custom__') return customAngle.value.trim();
    return cameraAngle.value;
});

const canSubmitFile = computed(() => {
    return effectiveAngle.value.length > 0 && selectedFile.value !== null && !isUploading.value;
});

const canSubmitYoutube = computed(() => {
    return effectiveAngle.value.length > 0 && youtubeUrl.value.trim().length > 0 && !isUploading.value;
});

const canSubmit = computed(() => {
    return activeTab.value === 'file' ? canSubmitFile.value : canSubmitYoutube.value;
});

const formattedFileSize = computed(() => {
    if (!selectedFile.value) return '';
    const bytes = selectedFile.value.size;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1024 * 1024 * 1024) return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
    return `${(bytes / 1024 / 1024 / 1024).toFixed(2)} GB`;
});

// ─── File handling ────────────────────────────────────────────
const ACCEPTED_TYPES = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm', 'video/x-matroska'];

function isValidVideoFile(file: File): boolean {
    return ACCEPTED_TYPES.includes(file.type) || /\.(mp4|mov|avi|webm|mkv)$/i.test(file.name);
}

function handleFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        setFile(input.files[0]);
    }
}

function handleDrop(event: DragEvent) {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) setFile(file);
}

function setFile(file: File) {
    if (!isValidVideoFile(file)) {
        errorMessage.value = 'Formato no soportado. Usá mp4, mov, avi, webm o mkv.';
        return;
    }
    errorMessage.value = '';
    selectedFile.value = file;
}

function openFilePicker() {
    fileInputRef.value?.click();
}

// ─── Submit: YouTube ──────────────────────────────────────────
async function handleSubmitYoutube() {
    if (!canSubmitYoutube.value) return;

    isUploading.value = true;
    errorMessage.value = '';

    const angle = effectiveAngle.value;
    const videoAny = props.video as any;

    try {
        const payload = {
            youtube_url: youtubeUrl.value.trim(),
            camera_angle: angle,
            master_video_id: props.video.id,
        };

        const res = await fetch('/api/upload/bunny/init', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json().catch(() => ({})) as any;

        if (!res.ok || !data.success) {
            throw new Error(data.message || `Error al agregar el ángulo de YouTube (HTTP ${res.status})`);
        }

        // Build a SlaveVideo from the server response
        const newSlave: SlaveVideo = {
            id: data.slave_video.id,
            title: data.slave_video.title,
            stream_url: data.slave_video.stream_url ?? '',
            camera_angle: data.slave_video.camera_angle,
            sync_offset: data.slave_video.sync_offset ?? 0,
            is_synced: data.slave_video.is_synced ?? false,
            bunny_hls_url: data.slave_video.bunny_hls_url ?? null,
            bunny_status: data.slave_video.bunny_status ?? null,
            bunny_mp4_url: data.slave_video.bunny_mp4_url ?? null,
            is_youtube_video: true,
            youtube_video_id: data.slave_video.youtube_video_id,
        };

        emit('angleUploaded', newSlave);
        resetForm();
        emit('close');

    } catch (err: any) {
        console.error('UploadAngleModal YouTube error:', err);
        errorMessage.value = err.message || 'Ocurrió un error inesperado. Intentá de nuevo.';
    } finally {
        isUploading.value = false;
    }
}

// ─── Submit: File upload ───────────────────────────────────────
async function handleSubmitFile() {
    if (!canSubmitFile.value || !selectedFile.value) return;

    isUploading.value = true;
    errorMessage.value = '';
    uploadProgress.value = 0;

    const angle = effectiveAngle.value;
    // Auto-generate title from master video title + angle name
    const slaveTitle = `${props.video.title} - ${angle}`;
    // Cast to any to access server-side fields not in the TS type
    const videoAny = props.video as any;

    try {
        // Step 1: Init — create video record in DB and get Bunny TUS credentials
        const initPayload = {
            title: slaveTitle,
            filename: selectedFile.value.name,
            file_size: selectedFile.value.size,
            mime_type: selectedFile.value.type || 'video/mp4',
            match_date: props.video.match_date,
            category_id: props.video.category_id ?? null,
            tournament_id: videoAny.tournament_id ?? null,
            visibility_type: 'public',
            description: '',
            is_master: false,
            master_video_id: props.video.id,
            camera_angle: angle,
        };

        const initRes = await fetch('/api/upload/bunny/init', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify(initPayload),
        });

        if (!initRes.ok) {
            const err = await initRes.json().catch(() => ({}));
            throw new Error((err as any).message || `Error al iniciar la subida (HTTP ${initRes.status})`);
        }

        const initData = await initRes.json() as {
            success: boolean;
            video_id: number;
            bunny_guid: string;
            upload_url: string;
            signature: string;
            expire: number;
            library_id: number;
        };

        if (!initData.success) {
            throw new Error('No se pudo iniciar la subida en el servidor.');
        }

        const { video_id, bunny_guid, upload_url, signature, expire, library_id } = initData;

        // Step 2: TUS upload directly to Bunny Stream
        await new Promise<void>((resolve, reject) => {
            const upload = new tus.Upload(selectedFile.value!, {
                endpoint: upload_url,
                retryDelays: [0, 3000, 5000, 10000, 20000],
                chunkSize: 50 * 1024 * 1024, // 50 MB chunks
                headers: {
                    AuthorizationSignature: signature,
                    AuthorizationExpire: String(expire),
                    VideoId: bunny_guid,
                    LibraryId: String(library_id),
                },
                metadata: {
                    filename: selectedFile.value!.name,
                    filetype: selectedFile.value!.type || 'video/mp4',
                },
                onProgress(bytesUploaded: number, bytesTotal: number) {
                    uploadProgress.value = Math.round((bytesUploaded / bytesTotal) * 100);
                },
                onSuccess() {
                    resolve();
                },
                onError(err: Error) {
                    reject(new Error('Error en la transferencia TUS: ' + err.message));
                },
            });
            upload.start();
        });

        uploadProgress.value = 100;

        // Step 3: Notify server that upload is complete
        const completeRes = await fetch('/api/upload/bunny/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify({ video_id, bunny_guid }),
        });

        const completeData = await completeRes.json().catch(() => ({})) as any;

        // Step 4: Build the new slave video object and emit it
        const newSlave: SlaveVideo = {
            id: video_id,
            title: slaveTitle,
            stream_url: `/videos/${video_id}/stream`,
            camera_angle: angle,
            sync_offset: 0,
            is_synced: false,
            bunny_hls_url: null,
            bunny_status: 'queued',
            bunny_mp4_url: completeData.bunny_mp4_url ?? null,
        };

        emit('angleUploaded', newSlave);
        resetForm();
        emit('close');

    } catch (err: any) {
        console.error('UploadAngleModal error:', err);
        errorMessage.value = err.message || 'Ocurrió un error inesperado. Intentá de nuevo.';
    } finally {
        isUploading.value = false;
    }
}

// ─── Unified submit dispatcher ─────────────────────────────────
function handleSubmit() {
    if (activeTab.value === 'youtube') {
        handleSubmitYoutube();
    } else {
        handleSubmitFile();
    }
}

// ─── Helpers ──────────────────────────────────────────────────
function handleClose() {
    if (!isUploading.value) {
        resetForm();
        emit('close');
    }
}

function resetForm() {
    activeTab.value = 'file';
    cameraAngle.value = '';
    customAngle.value = '';
    selectedFile.value = null;
    uploadProgress.value = 0;
    errorMessage.value = '';
    isDragging.value = false;
    youtubeUrl.value = '';
    if (fileInputRef.value) fileInputRef.value.value = '';
}

// Reset form when modal is opened
watch(() => props.show, (newVal) => {
    if (newVal) resetForm();
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="modal-backdrop" @click.self="handleClose">
                <div class="modal-dialog">
                    <div class="modal-content">

                        <!-- Header -->
                        <div class="modal-header">
                            <h3 class="modal-title">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Subir ángulo de cámara
                            </h3>
                            <button
                                type="button"
                                class="btn-close"
                                :disabled="isUploading"
                                @click="handleClose"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">

                            <!-- Master video info -->
                            <div class="master-info">
                                <i class="fas fa-film"></i>
                                <span>Ángulo adicional para: <strong>{{ video.title }}</strong></span>
                            </div>

                            <!-- Source type tabs -->
                            <div class="source-tabs">
                                <button
                                    type="button"
                                    class="source-tab"
                                    :class="{ active: activeTab === 'file' }"
                                    :disabled="isUploading"
                                    @click="activeTab = 'file'"
                                >
                                    <i class="fas fa-file-video"></i>
                                    Subir archivo
                                </button>
                                <button
                                    type="button"
                                    class="source-tab"
                                    :class="{ active: activeTab === 'youtube' }"
                                    :disabled="isUploading"
                                    @click="activeTab = 'youtube'"
                                >
                                    <i class="fab fa-youtube"></i>
                                    URL de YouTube
                                </button>
                            </div>

                            <!-- Angle name (shared for both tabs) -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-video"></i>
                                    Nombre del ángulo
                                </label>
                                <div class="preset-buttons">
                                    <button
                                        v-for="preset in PRESET_ANGLES"
                                        :key="preset"
                                        type="button"
                                        class="preset-btn"
                                        :class="{ active: cameraAngle === preset }"
                                        :disabled="isUploading"
                                        @click="cameraAngle = preset"
                                    >
                                        {{ preset }}
                                    </button>
                                    <button
                                        type="button"
                                        class="preset-btn"
                                        :class="{ active: cameraAngle === '__custom__' }"
                                        :disabled="isUploading"
                                        @click="cameraAngle = '__custom__'"
                                    >
                                        Otro...
                                    </button>
                                </div>
                                <input
                                    v-if="cameraAngle === '__custom__'"
                                    v-model="customAngle"
                                    type="text"
                                    class="form-control mt-2"
                                    placeholder="Ej: Cámara de in-goal sur..."
                                    :disabled="isUploading"
                                    maxlength="100"
                                />
                            </div>

                            <!-- TAB: File upload -->
                            <template v-if="activeTab === 'file'">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-file-video"></i>
                                        Archivo de video
                                    </label>

                                    <!-- Hidden file input -->
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept="video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska,.mp4,.mov,.avi,.webm,.mkv"
                                        style="display:none"
                                        @change="handleFileSelect"
                                    />

                                    <!-- Dropzone (shown when no file selected) -->
                                    <div
                                        v-if="!selectedFile"
                                        class="dropzone"
                                        :class="{ dragging: isDragging }"
                                        @click="openFilePicker"
                                        @dragover.prevent="isDragging = true"
                                        @dragleave.prevent="isDragging = false"
                                        @drop.prevent="handleDrop"
                                    >
                                        <i class="fas fa-cloud-upload-alt dropzone-icon"></i>
                                        <p class="dropzone-text">Arrastrá el video aquí o hacé clic para seleccionar</p>
                                        <p class="dropzone-hint">mp4, mov, avi, webm, mkv — hasta 8 GB</p>
                                    </div>

                                    <!-- Selected file info -->
                                    <div v-if="selectedFile" class="file-info">
                                        <div class="file-details">
                                            <i class="fas fa-file-video file-icon"></i>
                                            <div class="file-text">
                                                <span class="file-name">{{ selectedFile.name }}</span>
                                                <span class="file-size">{{ formattedFileSize }}</span>
                                            </div>
                                        </div>
                                        <button
                                            v-if="!isUploading"
                                            type="button"
                                            class="btn-remove-file"
                                            title="Quitar archivo"
                                            @click="selectedFile = null"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Upload progress -->
                                <div v-if="isUploading" class="progress-section">
                                    <div class="progress-header">
                                        <span class="progress-label">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            Subiendo ángulo...
                                        </span>
                                        <span class="progress-percent">{{ uploadProgress }}%</span>
                                    </div>
                                    <div class="progress-bar-track">
                                        <div
                                            class="progress-bar-fill"
                                            :style="{ width: uploadProgress + '%' }"
                                        ></div>
                                    </div>
                                    <p class="progress-note">
                                        No cierres esta ventana hasta que la subida termine.
                                    </p>
                                </div>
                            </template>

                            <!-- TAB: YouTube URL -->
                            <template v-if="activeTab === 'youtube'">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fab fa-youtube yt-icon"></i>
                                        URL de YouTube
                                    </label>
                                    <input
                                        v-model="youtubeUrl"
                                        type="url"
                                        class="form-control"
                                        placeholder="https://www.youtube.com/watch?v=..."
                                        :disabled="isUploading"
                                        maxlength="500"
                                    />
                                    <p class="field-hint">
                                        Formatos aceptados: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID
                                    </p>
                                </div>

                                <div v-if="isUploading" class="yt-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Agregando ángulo de YouTube...
                                </div>
                            </template>

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
                                :disabled="isUploading"
                                @click="handleClose"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                :disabled="!canSubmit"
                                @click="handleSubmit"
                            >
                                <i
                                    class="fas"
                                    :class="isUploading ? 'fa-spinner fa-spin' : (activeTab === 'youtube' ? 'fa-plus-circle' : 'fa-cloud-upload-alt')"
                                ></i>
                                <template v-if="isUploading">
                                    {{ activeTab === 'file' ? `Subiendo ${uploadProgress}%...` : 'Agregando...' }}
                                </template>
                                <template v-else>
                                    {{ activeTab === 'youtube' ? 'Agregar ángulo YouTube' : 'Subir ángulo' }}
                                </template>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* ─── Backdrop + Dialog ─────────────────────────────────── */
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
    max-width: 560px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-content {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
}

/* ─── Header ────────────────────────────────────────────── */
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #252525;
    flex-shrink: 0;
}

.modal-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-title i {
    color: #00B7B5;
}

.btn-close {
    background: transparent;
    border: none;
    color: #999;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 0.4rem 0.6rem;
    border-radius: 4px;
    transition: color 0.2s, background 0.2s;
    line-height: 1;
}

.btn-close:hover:not(:disabled) {
    color: #fff;
    background: #333;
}

.btn-close:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ─── Body ──────────────────────────────────────────────── */
.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

/* Master info banner */
.master-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: rgba(0, 183, 181, 0.08);
    border: 1px solid rgba(0, 183, 181, 0.2);
    border-radius: 6px;
    color: #aaa;
    font-size: 0.85rem;
    margin-bottom: 1.25rem;
}

.master-info i {
    color: #00B7B5;
    flex-shrink: 0;
}

.master-info strong {
    color: #fff;
}

/* ─── Source tabs ────────────────────────────────────────── */
.source-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 1.25rem;
    border: 1px solid #333;
    border-radius: 8px;
    overflow: hidden;
}

.source-tab {
    flex: 1;
    padding: 0.6rem 1rem;
    background: #0f0f0f;
    border: none;
    color: #888;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    transition: all 0.2s;
}

.source-tab:first-child {
    border-right: 1px solid #333;
}

.source-tab:hover:not(:disabled):not(.active) {
    background: #1a1a1a;
    color: #ccc;
}

.source-tab.active {
    background: rgba(0, 183, 181, 0.12);
    color: #00B7B5;
    font-weight: 600;
}

.source-tab:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Form groups */
.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: #ccc;
    margin-bottom: 0.6rem;
}

.form-label i {
    color: #00B7B5;
}

.yt-icon {
    color: #ff0000 !important;
}

.form-control {
    width: 100%;
    padding: 0.65rem 0.875rem;
    background: #252525;
    border: 1px solid #333;
    border-radius: 6px;
    color: #fff;
    font-size: 0.9rem;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #00B7B5;
}

.form-control:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.mt-2 {
    margin-top: 0.5rem;
}

.field-hint {
    margin: 0.4rem 0 0 0;
    font-size: 0.75rem;
    color: #555;
}

/* Preset buttons */
.preset-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.preset-btn {
    padding: 0.35rem 0.75rem;
    background: #252525;
    border: 1px solid #333;
    border-radius: 20px;
    color: #ccc;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.preset-btn:hover:not(:disabled) {
    border-color: #00B7B5;
    color: #00B7B5;
}

.preset-btn.active {
    background: rgba(0, 183, 181, 0.15);
    border-color: #00B7B5;
    color: #00B7B5;
    font-weight: 600;
}

.preset-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Dropzone */
.dropzone {
    border: 2px dashed #333;
    border-radius: 8px;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: #0f0f0f;
}

.dropzone:hover,
.dropzone.dragging {
    border-color: #00B7B5;
    background: rgba(0, 183, 181, 0.04);
}

.dropzone-icon {
    font-size: 2.5rem;
    color: #444;
    display: block;
    margin-bottom: 0.75rem;
}

.dropzone:hover .dropzone-icon,
.dropzone.dragging .dropzone-icon {
    color: #00B7B5;
}

.dropzone-text {
    color: #aaa;
    font-size: 0.9rem;
    margin: 0 0 0.3rem 0;
}

.dropzone-hint {
    color: #555;
    font-size: 0.75rem;
    margin: 0;
}

/* Selected file info */
.file-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: #252525;
    border: 1px solid #333;
    border-radius: 6px;
}

.file-details {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.file-icon {
    font-size: 1.5rem;
    color: #00B7B5;
    flex-shrink: 0;
}

.file-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.file-name {
    color: #fff;
    font-size: 0.875rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 320px;
}

.file-size {
    color: #888;
    font-size: 0.75rem;
}

.btn-remove-file {
    background: transparent;
    border: none;
    color: #666;
    font-size: 1rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: color 0.2s, background 0.2s;
    flex-shrink: 0;
}

.btn-remove-file:hover {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
}

/* Progress */
.progress-section {
    margin-top: 0.5rem;
    padding: 1rem;
    background: #0f0f0f;
    border: 1px solid #252525;
    border-radius: 8px;
}

.progress-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.6rem;
}

.progress-label {
    font-size: 0.85rem;
    color: #ccc;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.progress-label i {
    color: #00B7B5;
}

.progress-percent {
    font-size: 0.85rem;
    font-weight: 700;
    color: #00B7B5;
}

.progress-bar-track {
    height: 6px;
    background: #252525;
    border-radius: 3px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #005461, #00B7B5);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-note {
    font-size: 0.75rem;
    color: #666;
    margin: 0.5rem 0 0 0;
    font-style: italic;
}

/* YouTube loading indicator */
.yt-loading {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1rem;
    background: rgba(255, 0, 0, 0.07);
    border: 1px solid rgba(255, 0, 0, 0.2);
    border-radius: 6px;
    color: #f08080;
    font-size: 0.875rem;
}

.yt-loading i {
    color: #ff4444;
}

/* Alert */
.alert {
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-top: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.875rem;
}

.alert-danger {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #f08080;
}

/* ─── Footer ────────────────────────────────────────────── */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid #252525;
    flex-shrink: 0;
}

.btn {
    padding: 0.6rem 1.25rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-secondary {
    background: #252525;
    color: #ccc;
    border: 1px solid #333;
}

.btn-secondary:hover:not(:disabled) {
    background: #333;
    color: #fff;
}

.btn-secondary:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.btn-primary {
    background: linear-gradient(135deg, #005461, #00B7B5);
    color: #fff;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 183, 181, 0.3);
}

.btn-primary:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* ─── Transition ────────────────────────────────────────── */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

/* ─── Responsive ────────────────────────────────────────── */
@media (max-width: 640px) {
    .modal-dialog {
        max-width: 100%;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 1rem;
    }

    .file-name {
        max-width: 200px;
    }

    .preset-buttons {
        gap: 0.3rem;
    }
}
</style>
