<script setup lang="ts">
import { onMounted, computed, ref, provide } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';
import { useVideoApi } from '@/composables/useVideoApi';
import { useToast } from '@/composables/useToast';
import type { Video, VideoComment, User } from '@/types/video-player';
import VideoElement from './VideoElement.vue';
import VideoHeader from './VideoHeader.vue';
import VideoInfo from './VideoInfo.vue';
import ToastContainer from './ui/ToastContainer.vue';

const props = defineProps<{
    video: Video;
    comments: VideoComment[];
    allUsers: Pick<User, 'id' | 'name' | 'role'>[];
    user: User;
}>();

const emit = defineEmits<{
    showStats: [];
    deleteVideo: [];
    addAngle: [];
    toggleTimelines: [];
}>();

const videoStore = useVideoStore();
const annotationsStore = useAnnotationsStore();
const api = useVideoApi(props.video.id);
const toast = useToast();

provide('videoApi', api);
provide('toast', toast);
provide('allUsers', props.allUsers);

const isAnalystOrCoach = computed(() =>
    ['analista', 'entrenador'].includes(props.user.role)
);

const hasMultiCamera = computed(() =>
    !!props.video.is_part_of_group && isAnalystOrCoach.value
);

const showComments = ref(true);
const isTheaterMode = ref(false);

onMounted(() => {
    videoStore.setVideo(props.video);
});

function toggleComments() {
    showComments.value = !showComments.value;
}

function handleAddComment() {
    const sidebar = document.getElementById('sidebarSection');
    if (sidebar) {
        sidebar.scrollIntoView({ behavior: 'smooth' });
    }
}

function handleToggleAnnotationMode() {
    if (annotationsStore.annotationMode) {
        annotationsStore.exitAnnotationMode();
    } else {
        annotationsStore.enterAnnotationMode();
    }
}

function toggleTheaterMode() {
    isTheaterMode.value = !isTheaterMode.value;
}
</script>

<template>
    <div class="row">
        <div :class="isAnalystOrCoach && !isTheaterMode ? 'col-lg-10' : 'col-12'" id="videoSection">
            <div class="card">
                <VideoHeader
                    :video="video"
                    :user="user"
                    :is-theater-mode="isTheaterMode"
                    @show-stats="$emit('showStats')"
                    @add-angle="$emit('addAngle')"
                    @toggle-comments="toggleComments"
                    @toggle-timelines="$emit('toggleTimelines')"
                    @delete-video="$emit('deleteVideo')"
                    @toggle-theater="toggleTheaterMode"
                />

                <div class="card-body p-0">
                    <div :class="[hasMultiCamera ? 'multi-cam-wrapper' : '', { 'theater-mode': isTheaterMode }]">
                        <div :class="hasMultiCamera ? 'master-col' : ''">
                            <VideoElement
                                :stream-url="video.stream_url"
                                :title="video.title"
                                :can-annotate="isAnalystOrCoach"
                                @add-comment="handleAddComment"
                                @toggle-annotation-mode="handleToggleAnnotationMode"
                            >
                                <template #annotation-canvas>
                                    <slot name="annotation-canvas" />
                                </template>
                                <template #annotation-toolbar>
                                    <slot name="annotation-toolbar" />
                                </template>
                            </VideoElement>
                        </div>
                        <div v-if="hasMultiCamera" class="slaves-col">
                            <slot name="multi-camera" />
                        </div>
                    </div>

                    <slot name="timelines-sync" />
                    <slot name="clip-panel" />
                    <slot name="clip-timeline" />
                    <slot name="comment-timeline" />
                </div>
            </div>

            <VideoInfo :video="video" />
        </div>

        <div v-if="isAnalystOrCoach && !isTheaterMode" class="col-lg-2" id="sidebarSection">
            <slot name="sidebar" />
        </div>

        <div v-if="user.role === 'jugador' && showComments" class="col-12">
            <slot name="player-comments" />
        </div>
    </div>

    <slot name="modals" />

    <ToastContainer />
</template>

<style scoped>
/* Multi-camera side-by-side layout (matches Blade's activateSideBySideLayout) */
.multi-cam-wrapper {
    display: flex;
    height: 60vh;
    align-items: center;
    overflow: hidden;
    transition: height 0.3s ease;
}

/* Theater mode: more height and better ratio for slaves */
.multi-cam-wrapper.theater-mode {
    height: 80vh;
}

.master-col {
    flex: 0 0 66.666%;
    max-width: 66.666%;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    transition: flex 0.3s ease, max-width 0.3s ease;
}

/* Theater mode: reduce master to give more space to slaves */
.multi-cam-wrapper.theater-mode .master-col {
    flex: 0 0 55%;
    max-width: 55%;
}

.master-col :deep(.video-container) {
    height: 100%;
    border-radius: 0;
}

.master-col :deep(.video-wrapper) {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.master-col :deep(video) {
    width: 100% !important;
    height: auto !important;
    max-height: 57vh !important;
}

/* Theater mode: allow taller video */
.multi-cam-wrapper.theater-mode .master-col :deep(video) {
    max-height: 77vh !important;
}

.slaves-col {
    flex: 0 0 33.333%;
    max-width: 33.333%;
    overflow-y: auto;
    max-height: 60vh;
    display: flex;
    flex-direction: column;
    transition: flex 0.3s ease, max-width 0.3s ease, max-height 0.3s ease;
}

/* Theater mode: increase slaves size */
.multi-cam-wrapper.theater-mode .slaves-col {
    flex: 0 0 45%;
    max-width: 45%;
    max-height: 80vh;
}

@media (max-width: 991px) {
    .master-col {
        flex: 0 0 58.333%;
        max-width: 58.333%;
    }
    .slaves-col {
        flex: 0 0 41.666%;
        max-width: 41.666%;
    }
}

@media (max-width: 768px) {
    .multi-cam-wrapper {
        flex-direction: column;
        height: auto;
    }
    .master-col,
    .slaves-col {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .slaves-col {
        max-height: 40vh;
    }
}
</style>
