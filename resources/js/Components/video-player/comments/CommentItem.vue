<script setup lang="ts">
import { ref, computed } from 'vue';
import { useVideoStore, formatTime } from '@/stores/videoStore';
import { useCommentsStore } from '@/stores/commentsStore';
import { useToast } from '@/composables/useToast';
import ReplyForm from './ReplyForm.vue';
import type { VideoComment, User } from '@/types/video-player';

const props = defineProps<{
    comment: VideoComment;
    videoId: number;
    currentUser: User;
    allUsers: Pick<User, 'id' | 'name' | 'role'>[];
    depth?: number;
}>();

const videoStore = useVideoStore();
const commentsStore = useCommentsStore();
const toast = useToast();

const showReplyForm = ref(false);
const maxDepth = 4;

const canDelete = computed(() => {
    return props.currentUser.id === props.comment.user_id
        || ['analista', 'entrenador'].includes(props.currentUser.role);
});

const roleBadgeClass = computed(() => {
    switch (props.comment.user?.role) {
        case 'analista': return 'badge-primary';
        case 'entrenador': return 'badge-success';
        default: return 'badge-info';
    }
});

const categoryBadgeClass = computed(() => {
    switch (props.comment.category) {
        case 'tecnico': return 'badge-info';
        case 'tactico': return 'badge-warning';
        case 'fisico': return 'badge-success';
        case 'mental': return 'badge-purple';
        default: return 'badge-secondary';
    }
});

const priorityBadgeClass = computed(() => {
    switch (props.comment.priority) {
        case 'critica': return 'badge-danger';
        case 'alta': return 'badge-warning';
        case 'media': return 'badge-info';
        default: return 'badge-secondary';
    }
});

function seekToTimestamp() {
    videoStore.seek(props.comment.timestamp_seconds);
    videoStore.play();
}

async function handleDelete() {
    const ok = await commentsStore.deleteComment(props.videoId, props.comment.id);
    if (ok) {
        toast.success('Comentario eliminado');
    } else {
        toast.error('Error al eliminar el comentario');
    }
}

function toggleReply() {
    showReplyForm.value = !showReplyForm.value;
}

function onReplySubmitted() {
    showReplyForm.value = false;
}

function timeAgo(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) return 'Hace unos segundos';
    if (seconds < 3600) return `Hace ${Math.floor(seconds / 60)} min`;
    if (seconds < 86400) return `Hace ${Math.floor(seconds / 3600)}h`;
    return `Hace ${Math.floor(seconds / 86400)}d`;
}

// Render comment text with highlighted @mentions
function renderComment(text: string): string {
    return text.replace(/@(\w+(?:\s\w+)?)/g, '<span class="mention-highlight">@$1</span>');
}
</script>

<template>
    <div
        class="comment-item"
        :class="{ 'is-reply': depth && depth > 0 }"
        :style="depth && depth > 0 ? { marginLeft: '16px', borderLeft: '2px solid #005461', paddingLeft: '12px' } : {}"
    >
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <!-- Timestamp & badges (only for root comments) -->
                <div v-if="!depth || depth === 0" class="d-flex align-items-center mb-2 flex-wrap" style="gap: 4px;">
                    <button
                        class="btn btn-sm btn-primary timestamp-btn"
                        @click="seekToTimestamp"
                    >
                        {{ formatTime(comment.timestamp_seconds) }}
                    </button>
                    <span v-if="comment.category" class="badge" :class="categoryBadgeClass">
                        {{ comment.category.charAt(0).toUpperCase() + comment.category.slice(1) }}
                    </span>
                    <span v-if="comment.priority" class="badge" :class="priorityBadgeClass">
                        {{ comment.priority.charAt(0).toUpperCase() + comment.priority.slice(1) }}
                    </span>
                </div>

                <!-- Comment text -->
                <p class="mb-1 comment-text" v-html="renderComment(comment.comment)"></p>

                <!-- User info -->
                <small class="text-muted">
                    <i class="fas fa-user"></i>
                    {{ comment.user?.name ?? 'Usuario' }}
                    <span class="badge badge-sm" :class="roleBadgeClass">
                        {{ (comment.user?.role ?? '').charAt(0).toUpperCase() + (comment.user?.role ?? '').slice(1) }}
                    </span>
                    - {{ timeAgo(comment.created_at) }}
                </small>

                <button
                    v-if="(!depth || depth < maxDepth)"
                    class="btn btn-sm btn-link text-rugby p-0 ml-2 reply-btn"
                    @click="toggleReply"
                >
                    <i class="fas fa-reply"></i> Responder
                </button>
            </div>

            <button
                v-if="canDelete"
                class="btn btn-sm btn-outline-danger delete-btn"
                @click="handleDelete"
                title="Eliminar"
            >
                <i class="fas fa-trash"></i>
            </button>
        </div>

        <!-- Reply form -->
        <ReplyForm
            v-if="showReplyForm"
            :video-id="videoId"
            :parent-id="comment.id"
            :all-users="allUsers"
            @submitted="onReplySubmitted"
            @cancel="showReplyForm = false"
        />

        <!-- Nested replies -->
        <div v-if="comment.replies?.length" class="replies mt-2">
            <CommentItem
                v-for="reply in comment.replies"
                :key="reply.id"
                :comment="reply"
                :video-id="videoId"
                :current-user="currentUser"
                :all-users="allUsers"
                :depth="(depth || 0) + 1"
            />
        </div>
    </div>
</template>

<style scoped>
.comment-item {
    padding: 10px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.comment-item:last-child {
    border-bottom: none;
}

.timestamp-btn {
    background: #005461;
    border: none;
    font-family: monospace;
    font-size: 11px;
    padding: 2px 8px;
}
.timestamp-btn:hover {
    background: #FFC300;
}

.comment-text {
    color: #ddd;
    font-size: 13px;
    word-break: break-word;
}

:deep(.mention-highlight) {
    color: #FFC300;
    font-weight: 600;
}

.text-muted {
    color: #888 !important;
    font-size: 11px;
}

.reply-btn {
    color: #FFC300 !important;
    font-size: 11px;
}

.delete-btn {
    font-size: 10px;
    padding: 2px 6px;
    opacity: 0.5;
}
.delete-btn:hover {
    opacity: 1;
}

.badge-purple {
    background: #9b59b6;
    color: white;
}
</style>
