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
import LineupModal from '@/Components/video-player/lineup/LineupModal.vue';
import StatsModal from '@/Components/video-player/modals/StatsModal.vue';
import AnnotationCanvas from '@/Components/video-player/annotations/AnnotationCanvas.vue';
import AnnotationToolbar from '@/Components/video-player/annotations/AnnotationToolbar.vue';
import AnnotationList from '@/Components/video-player/annotations/AnnotationList.vue';
import MultiCameraLayout from '@/Components/video-player/multi-camera/MultiCameraLayout.vue';
import UploadAngleModal from '@/Components/video-player/multi-camera/UploadAngleModal.vue';
import SyncModal from '@/Components/video-player/multi-camera/SyncModal.vue';
import MobileFullscreen from '@/Components/video-player/ui/MobileFullscreen.vue';
import { useMultiCamera } from '@/composables/useMultiCamera';
import { useVideoLoader } from '@/composables/useVideoLoader';
import { useCommentsStore } from '@/stores/commentsStore';
import { useClipsStore } from '@/stores/clipsStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';
import { useLineupStore } from '@/stores/lineupStore';
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
const lineupStore = useLineupStore();
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
const showLineupModal = ref(false);
const showCategoryModal = ref(false);
const editingCategory = ref<ClipCategory | undefined>(undefined);
const showManageCategoriesModal = ref(false);
const showDeleteModal = ref(false);
const showStatsModal = ref(false);
const showUploadAngleModal = ref(false);
const showSyncModal = ref(false);

// ─── YouTube ──────────────────────────────────────────────────────────────────
const isYoutubeVideo  = ref((props.video as any).is_youtube_video ?? false);
const youtubeVideoId  = ref((props.video as any).youtube_video_id ?? null);

// ─── is_part_of_group reactivo ────────────────────────────────────────────────
// El prop original es estático (Inertia). Necesitamos actualizarlo cuando se agrega
// el primer ángulo (antes no había grupo → is_part_of_group = false).
const isPartOfGroup = ref(props.video.is_part_of_group ?? false);

// ─── Master video URL state (reactive so swap can update them) ────────────────
// These start from the Inertia-provided props and are updated on master/slave swap.
const videoStreamUrl  = ref(props.video.stream_url);
// Tracks which video ID is currently in the master slot (changes on every swap)
const currentMasterId = ref(props.video.id);

// ─── Polling de encoding Bunny ────────────────────────────────
const videoStatus     = ref(props.video.bunny_status ?? null);
const videoHlsUrl     = ref(props.video.bunny_hls_url ?? null);
const videoMp4Url     = ref(props.video.bunny_mp4_url ?? null);
// Muestra pantalla de encoding solo si no hay NI HLS NI MP4 original disponible
// Para YouTube: nunca está procesando (ya está listo en YouTube)
const isProcessing    = computed(() => !isYoutubeVideo.value && !videoHlsUrl.value && !videoMp4Url.value);
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
            // Apply HLS URL immediately in all cases.
            // VideoElement.vue watch(activeHlsUrl) handles the seamless transition:
            // it preserves currentTime and play state via initHls(url, currentTime, wasPlaying).
            videoHlsUrl.value = data.playback_url;
        }
    } catch (e) {
        console.warn('Polling error:', e);
    }
}

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

// Siempre crear multiCamera — maneja tanto HTML5 como YouTube como master.
// Si se crea con is_part_of_group=false, no tiene slaves registrados y es no-op.
const multiCamera = useMultiCamera({
    masterVideoRef,
    slaveVideos,
    videoLoader: videoLoader ?? undefined,
});

// Provide multiCamera and videoLoader to child components
provide('multiCamera', multiCamera);
provide('videoLoader', videoLoader);

// Cuando el master es YouTube, useMultiCamera no tiene HTMLVideoElement para escuchar.
// Bridgeamos los cambios del videoStore hacia los slaves YouTube.
watch(
    [() => videoStore.isPlaying, () => videoStore.currentTime],
    ([playing, time]) => {
        if (!isYoutubeVideo.value) return; // Solo para master YouTube
        multiCamera.onYtMasterUpdate(time as number, playing as boolean);
    }
);

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

    // Prevent Chrome from activating OS-level media session when a video plays/seeks.
    // Without this, Chrome dispatches window.blur when any <video> starts playing,
    // which breaks all keyboard shortcuts until the user clicks somewhere on the page.
    if ('mediaSession' in navigator) {
        navigator.mediaSession.setActionHandler('play', () => videoStore.play());
        navigator.mediaSession.setActionHandler('pause', () => videoStore.pause());
        navigator.mediaSession.setActionHandler('seekforward', () => videoStore.seekRelative(10));
        navigator.mediaSession.setActionHandler('seekbackward', () => videoStore.seekRelative(-10));
        navigator.mediaSession.setActionHandler('previoustrack', () => videoStore.seekRelative(-10));
        navigator.mediaSession.setActionHandler('nexttrack', () => videoStore.seekRelative(10));
    }

    if (isAnalystOrCoach.value) {
        // Load lineups in background — no await, non-blocking
        lineupStore.loadLineups(props.video.id);

        shortcuts.registerHotkey('Escape', () => {
            if (clipsStore.isRecording) {
                clipsStore.cancelRecording();
                toast.info('Grabación cancelada');
            }
        });

        try {
            const api = useVideoApi(props.video.id);
            const [,, loadedAnnotations] = await Promise.all([
                clipsStore.loadClips(props.video.id),
                clipsStore.loadCategories(props.video.id),
                api.getAnnotations(),
            ]);
            annotationsStore.loadAnnotations(loadedAnnotations);
        } catch (e) {
            console.error('Error loading clips/categories:', e);
        }
    }
});

// Reactive hotkey registration — runs whenever categories load or change
// (handles initial load, edits, deletes, reorders)
if (isAnalystOrCoach.value) {
    watch(
        () => clipsStore.activeCategories,
        (categories) => {
            console.log('[hotkey] 🔄 watch(activeCategories) fired →', categories.length, 'cats, hotkeys:', categories.map(c => c.hotkey).filter(Boolean));
            // Remove old category hotkeys, keep base hotkeys (space, arrows, escape)
            for (const cat of categories) {
                if (cat.hotkey) shortcuts.unregisterHotkey(cat.hotkey);
            }
            // Register fresh for current categories
            for (const cat of categories) {
                if (cat.hotkey) {
                    shortcuts.registerHotkey(cat.hotkey, async () => {
                        console.log(`[hotkey] 🎬 callback cat="${cat.name}" key="${cat.hotkey}" isRecording=${clipsStore.isRecording} recCatId=${clipsStore.recordingCategoryId} isPlaying=${videoStore.isPlaying} t=${videoStore.currentTime.toFixed(2)}`);
                        try {
                            const wasRecording =
                                clipsStore.isRecording &&
                                clipsStore.recordingCategoryId === cat.id;
                            if (!wasRecording && !videoStore.isPlaying) {
                                videoStore.play();
                            }
                            const result = await clipsStore.toggleRecording(
                                props.video.id, cat.id, videoStore.currentTime,
                            );
                            if (result) toast.success(`Clip creado: ${cat.name}`);
                        } catch (error: any) {
                            console.error('[hotkey] ❌ toggleRecording error:', error);
                            toast.error(error.message || 'Error al crear el clip');
                        }
                    });
                }
            }
        },
        { immediate: false },
    );
}

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

function onCategoriesReordered(reordered: ClipCategory[]) {
    clipsStore.categories = reordered;
}

function onCategoryModalClose() {
    showCategoryModal.value = false;
    editingCategory.value = undefined;
}

function onCategorySaved() {
    showCategoryModal.value = false;
    editingCategory.value = undefined;
    clipsStore.loadCategories(props.video.id);
    clipsStore.loadClips(props.video.id);
}

// Multi-camera handlers
function onUploadAngle() {
    showUploadAngleModal.value = true;
}

function onAngleUploaded(slave: SlaveVideo) {
    slaveVideos.value = [...slaveVideos.value, slave];
    isPartOfGroup.value = true; // el video ahora pertenece a un grupo
}

function onSwapMaster(slaveId: number) {
    // Find the slave to promote to master
    const slaveIndex = slaveVideos.value.findIndex(s => s.id === slaveId);
    if (slaveIndex === -1) return;

    const incomingSlave = slaveVideos.value[slaveIndex];

    // Snapshot current master state (URLs + YouTube info)
    const oldMasterStreamUrl = videoStreamUrl.value;
    const oldMasterHlsUrl    = videoHlsUrl.value;
    const oldMasterMp4Url    = videoMp4Url.value;
    const oldMasterStatus    = videoStatus.value;
    const oldIsYoutube       = isYoutubeVideo.value;
    const oldYoutubeId       = youtubeVideoId.value;

    // Promote slave's URLs to master refs — Vue watchers in VideoElement.vue
    // will detect the activeHlsUrl or activeMp4Url change and reload seamlessly,
    // preserving currentTime and play state.
    videoStreamUrl.value = incomingSlave.stream_url;
    videoHlsUrl.value    = (incomingSlave.bunny_hls_url && incomingSlave.bunny_status === 'ready')
        ? incomingSlave.bunny_hls_url
        : null;
    // Use Bunny MP4 if available, fallback to stream_url so isProcessing stays false
    videoMp4Url.value    = incomingSlave.bunny_mp4_url ?? incomingSlave.stream_url;
    videoStatus.value    = incomingSlave.bunny_status ?? null;

    // Promote slave's YouTube state — VideoElement.vue watches isYoutube and
    // initializes/destroys the YT.Player dynamically when this changes.
    isYoutubeVideo.value = incomingSlave.is_youtube_video ?? false;
    youtubeVideoId.value = incomingSlave.youtube_video_id ?? null;

    // Demote the CURRENT master (tracked by currentMasterId, NOT always props.video.id).
    // sync_offset for demoted master = -(incomingSlave.sync_offset):
    //   Before swap: slave.currentTime = master.currentTime - slaveOffset
    //   After swap:  newSlave.currentTime = newMaster.currentTime - newSlaveOffset
    //   Since both were at the same real-world moment: newSlaveOffset = -slaveOffset
    const demotedMaster: SlaveVideo = {
        id:               currentMasterId.value,
        title:            props.video.title,
        stream_url:       oldMasterStreamUrl,
        camera_angle:     incomingSlave.camera_angle,
        sync_offset:      -(incomingSlave.sync_offset ?? 0),
        is_synced:        true,
        bunny_hls_url:    oldMasterHlsUrl,
        bunny_status:     oldMasterStatus,
        bunny_mp4_url:    oldMasterMp4Url,
        is_youtube_video: oldIsYoutube,
        youtube_video_id: oldYoutubeId,
    };

    // Update currentMasterId to the promoted slave's real ID
    currentMasterId.value = incomingSlave.id;

    // Build a new array replacing the slave slot with the demoted master
    const newSlaves = slaveVideos.value.map((s, i) =>
        i === slaveIndex ? demotedMaster : s
    );

    console.log(`[SWAP] promoted=${incomingSlave.id}(yt=${incomingSlave.is_youtube_video},ytId=${incomingSlave.youtube_video_id}) → demoted=${demotedMaster.id}(yt=${demotedMaster.is_youtube_video},ytId=${demotedMaster.youtube_video_id})`);
    console.log(`[SWAP] promoted.stream=...${String(incomingSlave.stream_url).slice(-40)} | promoted.hls=${!!incomingSlave.bunny_hls_url}`);
    console.log(`[SWAP] demoted.stream=...${String(demotedMaster.stream_url).slice(-40)} | demoted.hls=${!!demotedMaster.bunny_hls_url}`);
    console.log(`[SWAP] newSlaves=${newSlaves.map(s => `${s.id}(yt=${s.is_youtube_video})`).join(', ')}`);

    slaveVideos.value = newSlaves;

    // Reset "first play" flag so the next play waits for the new slave
    // to be ready before starting — same behavior as on initial load.
    multiCamera?.resetForNewMaster();

    toast.info('Cámara intercambiada');
}

async function onRemoveSlave(slaveId: number) {
    if (!confirm('¿Estás seguro de que deseas eliminar este ángulo?')) return;

    try {
        const api = useVideoApi(props.video.id);
        await api.removeSlaveVideo(slaveId);

        // Remove from local state (must assign new array — shallowRef doesn't detect splice)
        slaveVideos.value = slaveVideos.value.filter(s => s.id !== slaveId);

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
                    <i class="fas fa-film fa-3x" style="color:#FFC300;opacity:.6"></i>
                </div>
                <h4 class="font-weight-bold mb-1">{{ video.title }}</h4>
                <p class="text-muted mb-3">
                    El video se subió correctamente y está siendo procesado por Bunny Stream.
                </p>
                <div class="d-flex align-items-center justify-content-center mb-3" style="gap:10px">
                    <div class="spinner-border spinner-border-sm" style="color:#FFC300" role="status"></div>
                    <span class="text-muted small">
                        Verificando estado cada 10 segundos...
                        <span v-if="videoStatus" class="badge badge-secondary ml-1">{{ videoStatus }}</span>
                    </span>
                </div>
                <div class="progress" style="height:6px;background:#2a2a2a;border-radius:3px">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                        style="background:linear-gradient(90deg,#005461,#FFC300);width:100%"></div>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Videos de 40 min tardan aprox. 5-10 min en estar listos. Esta página se actualizará automáticamente.
                </p>
            </div>
        </div>

        <!-- Player normal cuando el video está listo (tiene HLS, MP4 original o es YouTube) -->
        <VideoPlayer
            v-if="!isProcessing"
            :video="{ ...video, stream_url: videoStreamUrl, bunny_hls_url: videoHlsUrl, bunny_status: videoStatus, bunny_mp4_url: videoMp4Url, is_youtube_video: isYoutubeVideo, youtube_video_id: youtubeVideoId, is_part_of_group: isPartOfGroup }"
            :comments="comments"
            :all-users="allUsers"
            :user="user"
            :has-slaves="slaveVideos.length > 0"
            @show-stats="showStatsModal = true"
            @delete-video="showDeleteModal = true"
            @upload-angle="onUploadAngle"
            @toggle-timelines="toggleTimelinesSync"
            @show-lineup="showLineupModal = true"
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
                    @edit-categories="onManageCategories"
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
                <LineupModal
                    :show="showLineupModal"
                    :video="video"
                    :all-users="allUsers"
                    @close="showLineupModal = false"
                />
                <CategoryModal
                    :show="showCategoryModal"
                    :category="editingCategory"
                    @close="onCategoryModalClose"
                    @saved="onCategorySaved"
                />
                <ManageCategoriesModal
                    :show="showManageCategoriesModal"
                    :categories="clipsStore.categories"
                    @close="showManageCategoriesModal = false"
                    @edit-category="onEditCategory"
                    @delete-category="onDeleteCategory"
                    @reordered="onCategoriesReordered"
                />
                <DeleteVideoModal
                    :show="showDeleteModal"
                    :video="video"
                    @close="showDeleteModal = false"
                    @confirmed="showDeleteModal = false"
                />
                <StatsModal
                    :show="showStatsModal"
                    :video-id="video.id"
                    @close="showStatsModal = false"
                />
                <UploadAngleModal
                    :show="showUploadAngleModal"
                    :video="video"
                    :csrf-token="($page.props as any).csrf_token ?? ''"
                    @close="showUploadAngleModal = false"
                    @angle-uploaded="onAngleUploaded"
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
