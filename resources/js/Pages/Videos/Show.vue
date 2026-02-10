<script setup lang="ts">
import { computed, onMounted, ref, provide, watch, shallowRef } from 'vue';
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
import RecordingIndicator from '@/Components/video-player/ui/RecordingIndicator.vue';
import SidebarPanel from '@/Components/video-player/sidebar/SidebarPanel.vue';
import CategoryModal from '@/Components/video-player/modals/CategoryModal.vue';
import ManageCategoriesModal from '@/Components/video-player/modals/ManageCategoriesModal.vue';
import EditClipModal from '@/Components/video-player/modals/EditClipModal.vue';
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

// Modal state
const showCategoryModal = ref(false);
const editingCategory = ref<ClipCategory | undefined>(undefined);
const showManageCategoriesModal = ref(false);
const showEditClipModal = ref(false);
const editingClip = ref<VideoClip | undefined>(undefined);
const showDeleteModal = ref(false);
const showStatsModal = ref(false);
const showAssociateAngleModal = ref(false);
const showSyncModal = ref(false);

// Multi-camera - already filtered in controller
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

// View tracking
const viewTracking = useViewTracking(props.video.id);

// Keyboard shortcuts
const shortcuts = useKeyboardShortcuts();

onMounted(async () => {
    // Set current video (avoid resetting videoRef after children mounted)
    videoStore.setVideo(props.video);

    commentsStore.setComments(props.comments);
    viewTracking.start();

    if (isAnalystOrCoach.value) {
        try {
            const api = useVideoApi(props.video.id);
            const [,, loadedAnnotations] = await Promise.all([
                clipsStore.loadClips(props.video.id),
                clipsStore.loadCategories(props.video.id),
                api.getAnnotations(),
            ]);
            annotationsStore.loadAnnotations(loadedAnnotations);

            shortcuts.registerHotkey('Space', () => videoStore.togglePlay());
            shortcuts.registerHotkey('ArrowLeft', () => videoStore.seekRelative(-5));
            shortcuts.registerHotkey('ArrowRight', () => videoStore.seekRelative(5));
            shortcuts.registerHotkey('Escape', () => {
                if (clipsStore.isRecording) {
                    clipsStore.cancelRecording();
                    toast.info('Grabación cancelada');
                }
            });

            for (const cat of clipsStore.activeCategories) {
                if (cat.hotkey) {
                    shortcuts.registerHotkey(cat.hotkey, async () => {
                        const result = await clipsStore.toggleRecording(
                            props.video.id, cat.id, videoStore.currentTime,
                        );
                        if (result) toast.success(`Clip creado: ${cat.name}`);
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

// Clip modal handlers
function onEditClip(clipId: number) {
    const clip = clipsStore.clips.find(c => c.id === clipId);
    if (clip) {
        editingClip.value = clip;
        showEditClipModal.value = true;
    }
}

function onClipSaved() {
    showEditClipModal.value = false;
    editingClip.value = undefined;
}

// Multi-camera handlers
function onAddAngle() {
    showAssociateAngleModal.value = true;
}

function onSwapMaster(slaveId: number) {
    if (!multiCamera) return;
    const result = multiCamera.swapMaster(slaveId);
    if (result) {
        toast.info('Cámara intercambiada');
    }
}

function onSyncSlave(slaveId: number) {
    if (!multiCamera) return;
    multiCamera.syncAllSlaves();
    toast.info('Sincronizando cámaras...');
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

// Safe wrapper for getSyncStatus
function safeGetSyncStatus(slaveId: number): 'synced' | 'syncing' | 'out-of-sync' {
    if (!multiCamera || !multiCamera.getSyncStatus) return 'out-of-sync';
    try {
        return multiCamera.getSyncStatus(slaveId);
    } catch (error) {
        console.warn('Error getting sync status:', error);
        return 'out-of-sync';
    }
}
</script>

<template>
    <Head :title="video.title" />

    <AdminLteLayout>
        <VideoPlayer
            :video="video"
            :comments="comments"
            :all-users="allUsers"
            :user="user"
            @show-stats="showStatsModal = true"
            @delete-video="showDeleteModal = true"
            @add-angle="onAddAngle"
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

            <!-- Clip Timeline (visual lanes) -->
            <template v-if="isAnalystOrCoach" #clip-timeline>
                <ClipTimeline :video-id="video.id" />
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
                            @edit-clip="onEditClip"
                        />
                    </template>
                </SidebarPanel>
            </template>

            <!-- Multi-camera layout -->
            <template v-if="video.is_part_of_group && isAnalystOrCoach" #multi-camera>
                <MultiCameraLayout
                    v-if="slaveVideos.length > 0"
                    :slaves="slaveVideos"
                    :get-sync-status="safeGetSyncStatus"
                    @swap-master="onSwapMaster"
                    @sync-slave="onSyncSlave"
                    @remove-slave="onRemoveSlave"
                />
                <div v-else class="text-center p-3 text-muted">
                    <i class="fas fa-video-slash"></i> No hay ángulos adicionales
                </div>
            </template>

            <!-- Player comments (jugadores) -->
            <template #player-comments>
                <div class="card mt-3" style="background: #1a1a1a; border: 1px solid #333;">
                    <div class="card-header p-2" style="background: #005461; border: none;">
                        <h6 class="card-title m-0 text-white" style="font-size: 13px;">
                            <i class="fas fa-comments"></i>
                            Comentarios ({{ commentsStore.commentCount }})
                        </h6>
                    </div>
                    <CommentList
                        :video-id="video.id"
                        :current-user="user"
                        :all-users="allUsers"
                    />
                </div>
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
                <EditClipModal
                    v-if="editingClip"
                    :show="showEditClipModal"
                    :clip="editingClip"
                    :video-id="video.id"
                    @close="showEditClipModal = false; editingClip = undefined"
                    @saved="onClipSaved"
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
                    @close="showAssociateAngleModal = false"
                    @associated="showAssociateAngleModal = false"
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
