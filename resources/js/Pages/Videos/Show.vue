<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref, provide, watch, shallowRef } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLteLayout from '@/Layouts/AdminLteLayout.vue';
import VideoPlayer from '@/Components/video-player/VideoPlayer.vue';
import CommentForm from '@/Components/video-player/comments/CommentForm.vue';
import CommentList from '@/Components/video-player/comments/CommentList.vue';
import CommentTimeline from '@/Components/video-player/timeline/CommentTimeline.vue';
import CommentNotification from '@/Components/video-player/comments/CommentNotification.vue';
import ClipPanel from '@/Components/video-player/clips/ClipPanel.vue';
import ClipTimeline from '@/Components/video-player/timeline/ClipTimeline.vue';
import ClipsList from '@/Components/video-player/clips/ClipsList.vue';
import TimelinesSyncPanel from '@/Components/video-player/timelines/TimelinesSyncPanel.vue';
import RecordingIndicator from '@/Components/video-player/ui/RecordingIndicator.vue';
import SidebarPanel from '@/Components/video-player/sidebar/SidebarPanel.vue';
import CategoryModal from '@/Components/video-player/modals/CategoryModal.vue';
import ManageCategoriesModal from '@/Components/video-player/modals/ManageCategoriesModal.vue';
import DeleteVideoModal from '@/Components/video-player/modals/DeleteVideoModal.vue';
import StatsModal from '@/Components/video-player/modals/StatsModal.vue';
import AnnotationCanvas from '@/Components/video-player/annotations/AnnotationCanvas.vue';
import AnnotationToolbar from '@/Components/video-player/annotations/AnnotationToolbar.vue';
import AnnotationList from '@/Components/video-player/annotations/AnnotationList.vue';
import MultiCameraLayout from '@/Components/video-player/multi-camera/MultiCameraLayout.vue';
import AssociateAngleModal from '@/Components/video-player/multi-camera/AssociateAngleModal.vue';
import SyncModal from '@/Components/video-player/multi-camera/SyncModal.vue';
import MobileFullscreen from '@/Components/video-player/ui/MobileFullscreen.vue';
import { useMultiCamera } from '@/composables/useMultiCamera';
import { useVideoLoader } from '@/composables/useVideoLoader';
import { useCommentsStore } from '@/stores/commentsStore';
import { useClipsStore } from '@/stores/clipsStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';
import { useViewTracking } from '@/composables/useViewTracking';
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts';
import { useVideoStore } from '@/stores/videoStore';
import { useToast } from '@/composables/useToast';
import { useVideoApi } from '@/composables/useVideoApi';
import type { Video, VideoComment, VideoClip, ClipCategory, SlaveVideo, User } from '@/types/video-player';

const props = defineProps<{
    video: Video;
    comments: VideoComment[];
    allUsers: Pick<User, 'id' | 'name' | 'role'>[];
}>();

const page = usePage();
const authUser = computed(() => page.props.auth as any);
const user = computed<User>(() => authUser.value.user);

const commentsStore = useCommentsStore();
const clipsStore = useClipsStore();
const annotationsStore = useAnnotationsStore();
const videoStore = useVideoStore();
const toast = useToast();
const notificationsEnabled = ref(true);

const isAnalystOrCoach = computed(() =>
    ['analista', 'entrenador'].includes(user.value.role)
);

// Timelines sync panel visibility (expanded by default)
const showTimelinesSyncPanel = ref(true);

// Show timelines sync toggle if has slaves OR clips
const canShowTimelinesSync = computed(() => {
    if (!isAnalystOrCoach.value) return false;
    const hasSlaves = slaveVideos.value.length > 0;
    const hasClips = clipsStore.clips.length > 0;
    return hasSlaves || hasClips;
});

function toggleTimelinesSync() {
    showTimelinesSyncPanel.value = !showTimelinesSyncPanel.value;
}

// Modal state
const showCategoryModal = ref(false);
const editingCategory = ref<ClipCategory | undefined>(undefined);
const showManageCategoriesModal = ref(false);
const showDeleteModal = ref(false);
const showStatsModal = ref(false);
const showAssociateAngleModal = ref(false);
const showSyncModal = ref(false);

// ─── Polling de encoding Bunny ────────────────────────────────
const videoStatus     = ref(props.video.bunny_status ?? null);
const videoHlsUrl     = ref(props.video.bunny_hls_url ?? null);
const videoMp4Url     = ref(props.video.bunny_mp4_url ?? null);
// URL HLS lista pero pendiente de aplicar (usuario viendo MP4, no interrumpir)
const pendingHlsUrl   = ref<string | null>(null);
// Muestra pantalla de encoding solo si no hay NI HLS NI MP4 original disponible
const isProcessing    = computed(() => !videoHlsUrl.value && !videoMp4Url.value);
let pollingInterval: ReturnType<typeof setInterval> | null = null;

async function pollStatus() {
    if (!props.video.bunny_video_id) return;
    try {
        const res = await fetch(`/api/upload/bunny/${props.video.id}/status`);
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        videoStatus.value = data.status;

        if (data.ready && data.playback_url) {
            stopPolling();

            if (isProcessing.value) {
                // Usuario en pantalla de encoding → mostrar player inmediatamente
                videoHlsUrl.value = data.playback_url;
            } else if (videoStore.isPlaying) {
                // Usuario ya está viendo (MP4) → NO interrumpir, guardar URL pendiente
                pendingHlsUrl.value = data.playback_url;
                toast.info('Video HD listo. Cambiará automáticamente al pausar.', { duration: 8000 } as any);
            } else {
                // Video pausado/detenido → cambiar fuente silenciosamente
                videoHlsUrl.value = data.playback_url;
                pendingHlsUrl.value = null;
            }
        }
    } catch (e) {
        console.warn('Polling error:', e);
    }
}

// Cuando el usuario pausa y hay HLS pendiente → aplicar sin interrumpir
watch(() => videoStore.isPlaying, (playing) => {
    if (!playing && pendingHlsUrl.value) {
        videoHlsUrl.value = pendingHlsUrl.value;
        pendingHlsUrl.value = null;
    }
});

function startPolling() {
    if (pollingInterval) return;
    pollingInterval = setInterval(pollStatus, 10000); // cada 10s
    pollStatus(); // primera consulta inmediata
}

function stopPolling() {
    if (pollingInterval) { clearInterval(pollingInterval); pollingInterval = null; }
}

// Inicia polling si no hay HLS listo aún (sea que tenga MP4 o no)
onMounted(() => { if (!videoHlsUrl.value && props.video.bunny_video_id) startPolling(); });
onBeforeUnmount(() => stopPolling());

// ─── Multi-camera - already filtered in controller
// Ensure slave_videos is ALWAYS an array (defensive programming)
const rawSlaveVideos = props.video.slave_videos;
const safeSlaveVideos = Array.isArray(rawSlaveVideos) ? rawSlaveVideos : [];
// Use shallowRef to avoid deep reactivity issues with nested Proxies
const slaveVideos = shallowRef<SlaveVideo[]>(safeSlaveVideos);

// Watch to ensure slaveVideos.value NEVER becomes non-array
watch(slaveVideos, (newValue) => {
    if (!Array.isArray(newValue)) {
        console.error('⚠️ slaveVideos became non-array, fixing...', newValue);
        slaveVideos.value = [];
    }
}, { deep: true });

const masterVideoRef = computed(() => videoStore.videoRef);

// Debug logging
console.log('🎬 Video Multi-Camera Setup:', {
    videoId: props.video.id,
    isPartOfGroup: props.video.is_part_of_group,
    rawSlaveVideos,
    slaveVideosCount: slaveVideos.value.length,
    slaveVideos: slaveVideos.value,
});

// DISABLED: videoLoader approach has timing issues with slave registration
// Instead, useMultiCamera handles synchronization internally
const videoLoader = null;

const multiCamera = props.video.is_part_of_group
    ? useMultiCamera({
        masterVideoRef,
        slaveVideos,
        videoLoader: videoLoader ?? undefined,
    })
    : null;

// Provide multiCamera and videoLoader to child components
provide('multiCamera', multiCamera);
provide('videoLoader', videoLoader);

// Provide videoApi and toast to child components (for TimelineOffset and others)
const videoApi = useVideoApi(props.video.id);
provide('videoApi', videoApi);
provide('toast', toast);

// View tracking
const viewTracking = useViewTracking(props.video.id);

// Keyboard shortcuts
const shortcuts = useKeyboardShortcuts();

onMounted(async () => {
    // Set current video (avoid resetting videoRef after children mounted)
    videoStore.setVideo(props.video);

    commentsStore.setComments(props.comments);
    viewTracking.start();

    // Register basic playback shortcuts for ALL users
    shortcuts.registerHotkey('Space', () => videoStore.togglePlay());
    shortcuts.registerHotkey('ArrowLeft', () => videoStore.seekRelative(-5));
    shortcuts.registerHotkey('ArrowRight', () => videoStore.seekRelative(5));

    if (isAnalystOrCoach.value) {
        try {
            const api = useVideoApi(props.video.id);
            const [,, loadedAnnotations] = await Promise.all([
                clipsStore.loadClips(props.video.id),
                clipsStore.loadCategories(props.video.id),
                api.getAnnotations(),
            ]);
            annotationsStore.loadAnnotations(loadedAnnotations);

            shortcuts.registerHotkey('Escape', () => {
                if (clipsStore.isRecording) {
                    clipsStore.cancelRecording();
                    toast.info('Grabación cancelada');
                }
            });

            for (const cat of clipsStore.activeCategories) {
                if (cat.hotkey) {
                    shortcuts.registerHotkey(cat.hotkey, async () => {
                        try {
                            // Auto-play if paused when starting recording
                            const wasRecording = clipsStore.isRecording && clipsStore.recordingCategoryId === cat.id;
                            if (!wasRecording && videoStore.isPaused) {
                                videoStore.play();
                            }

                            const result = await clipsStore.toggleRecording(
                                props.video.id, cat.id, videoStore.currentTime,
                            );
                            if (result) toast.success(`Clip creado: ${cat.name}`);
                        } catch (error: any) {
                            toast.error(error.message || 'Error al crear el clip');
                        }
                    });
                }
            }
        } catch (e) {
            console.error('Error loading clips/categories:', e);
        }
    }
});

// Category modal handlers
function onCreateCategory() {
    editingCategory.value = undefined;
    showCategoryModal.value = true;
}

function onManageCategories() {
    showManageCategoriesModal.value = true;
}

function onEditCategory(category: ClipCategory) {
    editingCategory.value = category;
    showCategoryModal.value = true;
    showManageCategoriesModal.value = false;
}

async function onDeleteCategory(categoryId: number) {
    try {
        const api = useVideoApi(props.video.id);
        await api.deleteCategory(categoryId);
        await clipsStore.loadCategories(props.video.id);
        toast.success('Categoría eliminada');
    } catch {
        toast.error('Error al eliminar la categoría');
    }
}

function onCategorySaved() {
    showCategoryModal.value = false;
    clipsStore.loadCategories(props.video.id);
    clipsStore.loadClips(props.video.id);
}

// Multi-camera handlers
function onAddAngle() {
    showAssociateAngleModal.value = true;
}

async function onAngleAssociated() {
    showAssociateAngleModal.value = false;
    // Reload slave videos from the API
    try {
        const res = await fetch(`/videos/${props.video.id}/multi-camera/angles`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (data.success && Array.isArray(data.angles)) {
            slaveVideos.value = data.angles.map((a: any) => ({
                id: a.id,
                title: a.title,
                stream_url: `/videos/${a.id}/stream`,
                camera_angle: a.camera_angle,
                sync_offset: a.sync_offset ?? 0,
                is_synced: a.is_synced ?? false,
                duration: a.duration,
                file_size: a.file_size,
            }));
        }
    } catch (e) {
        console.error('Failed to reload slave videos', e);
    }
}

function onSwapMaster(slaveId: number) {
    if (!multiCamera) return;
    const result = multiCamera.swapMaster(slaveId);
    if (result) {
        toast.info('Cámara intercambiada');
    }
}

async function onRemoveSlave(slaveId: number) {
    if (!confirm('¿Estás seguro de que deseas eliminar este ángulo?')) return;

    try {
        const api = useVideoApi(props.video.id);
        await api.removeSlaveVideo(slaveId);

        // Remove from local state
        const index = slaveVideos.value.findIndex(s => s.id === slaveId);
        if (index !== -1) {
            slaveVideos.value.splice(index, 1);
        }

        toast.success('Ángulo eliminado correctamente');
    } catch (error) {
        console.error('Error removing slave:', error);
        toast.error('Error al eliminar el ángulo');
    }
}

function onSyncSaved(offsets: Record<number, number>) {
    if (!multiCamera) return;
    for (const [id, offset] of Object.entries(offsets)) {
        multiCamera.adjustSyncOffset(Number(id), offset);
    }
    toast.success('Sincronización guardada');
}
</script>

<template>
    <Head :title="video.title" />

    <AdminLteLayout>

        <!-- Pantalla de encoding en progreso -->
        <div v-if="isProcessing" class="card card-rugby text-center py-5 mx-auto" style="max-width:600px;margin-top:40px">
            <div class="card-body">
                <div class="mb-3">
                    <i class="fas fa-film fa-3x" style="color:#00B7B5;opacity:.6"></i>
                </div>
                <h4 class="font-weight-bold mb-1">{{ video.title }}</h4>
                <p class="text-muted mb-3">
                    El video se subió correctamente y está siendo procesado por Bunny Stream.
                </p>
                <div class="d-flex align-items-center justify-content-center mb-3" style="gap:10px">
                    <div class="spinner-border spinner-border-sm" style="color:#00B7B5" role="status"></div>
                    <span class="text-muted small">
                        Verificando estado cada 10 segundos...
                        <span v-if="videoStatus" class="badge badge-secondary ml-1">{{ videoStatus }}</span>
                    </span>
                </div>
                <div class="progress" style="height:6px;background:#2a2a2a;border-radius:3px">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                        style="background:linear-gradient(90deg,#005461,#00B7B5);width:100%"></div>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Videos de 40 min tardan aprox. 5-10 min en estar listos. Esta página se actualizará automáticamente.
                </p>
            </div>
        </div>

        <!-- Player normal cuando el video está listo (tiene HLS o MP4 original) -->
        <VideoPlayer
            v-if="!isProcessing"
            :video="{ ...video, bunny_hls_url: videoHlsUrl, bunny_status: videoStatus, bunny_mp4_url: videoMp4Url }"
            :comments="comments"
            :all-users="allUsers"
            :user="user"
            :has-slaves="slaveVideos.length > 0"
            @show-stats="showStatsModal = true"
            @delete-video="showDeleteModal = true"
            @add-angle="onAddAngle"
            @toggle-timelines="toggleTimelinesSync"
        >
            <!-- Annotation Canvas (overlay on video) -->
            <template v-if="isAnalystOrCoach" #annotation-canvas>
                <AnnotationCanvas :video-id="video.id" />
            </template>

            <!-- Annotation Toolbar -->
            <template v-if="isAnalystOrCoach" #annotation-toolbar>
                <AnnotationToolbar :video-id="video.id" />
            </template>

            <!-- Clip Panel -->
            <template v-if="isAnalystOrCoach" #clip-panel>
                <ClipPanel
                    :video-id="video.id"
                    @create-category="onCreateCategory"
                    @manage-categories="onManageCategories"
                />
            </template>

            <!-- Clip Timeline (visual timeline with clips) -->
            <template v-if="isAnalystOrCoach" #clip-timeline>
                <ClipTimeline :video-id="video.id" />
            </template>

            <!-- Timelines Sync Panel (multi-camera + clips XML sync) -->
            <template v-if="canShowTimelinesSync && showTimelinesSyncPanel" #timelines-sync>
                <TimelinesSyncPanel :slaves="slaveVideos" />
            </template>

            <!-- Comment Timeline with notifications -->
            <template #comment-timeline>
                <CommentTimeline :comment-count="commentsStore.commentCount">
                    <template #notifications>
                        <CommentNotification :enabled="notificationsEnabled" />
                    </template>
                </CommentTimeline>
            </template>

            <!-- Sidebar with tabs -->
            <template #sidebar>
                <SidebarPanel
                    :comment-count="commentsStore.commentCount"
                    :clip-count="clipsStore.clips.length"
                    :can-create-clips="isAnalystOrCoach"
                >
                    <template #comments>
                        <CommentForm
                            :video-id="video.id"
                            :all-users="allUsers"
                        />
                        <CommentList
                            :video-id="video.id"
                            :current-user="user"
                            :all-users="allUsers"
                        />
                    </template>
                    <template #clips>
                        <ClipsList
                            :video-id="video.id"
                        />
                    </template>
                </SidebarPanel>
            </template>

            <!-- Multi-camera layout -->
            <template v-if="video.is_part_of_group && isAnalystOrCoach && slaveVideos.length > 0" #multi-camera>
                <MultiCameraLayout
                    :slaves="slaveVideos"
                    @swap-master="onSwapMaster"
                    @remove-slave="onRemoveSlave"
                />
            </template>

            <!-- Modals slot -->
            <template #modals>
                <CategoryModal
                    :show="showCategoryModal"
                    :category="editingCategory"
                    :video-id="video.id"
                    @close="showCategoryModal = false"
                    @saved="onCategorySaved"
                />
                <ManageCategoriesModal
                    :show="showManageCategoriesModal"
                    :categories="clipsStore.categories"
                    @close="showManageCategoriesModal = false"
                    @edit-category="onEditCategory"
                    @delete-category="onDeleteCategory"
                />
                <DeleteVideoModal
                    :show="showDeleteModal"
                    :video="video"
                    @close="showDeleteModal = false"
                />
                <StatsModal
                    :show="showStatsModal"
                    :video-id="video.id"
                    @close="showStatsModal = false"
                />
                <AssociateAngleModal
                    :show="showAssociateAngleModal"
                    :video-id="video.id"
                    :excluded-ids="[video.id, ...slaveVideos.map(s => s.id)]"
                    @close="showAssociateAngleModal = false"
                    @associated="onAngleAssociated"
                />
                <SyncModal
                    :show="showSyncModal"
                    :slave-videos="slaveVideos"
                    @close="showSyncModal = false"
                    @saved="onSyncSaved"
                />
            </template>
        </VideoPlayer>

        <RecordingIndicator v-if="clipsStore.isRecording" />
        <MobileFullscreen />
    </AdminLteLayout>
</template>
