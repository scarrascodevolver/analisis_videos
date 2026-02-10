<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useVideoStore, formatTime } from '@/stores/videoStore';
import { useCommentsStore } from '@/stores/commentsStore';
import type { VideoComment } from '@/types/video-player';

const props = defineProps<{
    enabled: boolean;
}>();

const videoStore = useVideoStore();
const commentsStore = useCommentsStore();

const activeNotifications = ref<Map<number, VideoComment>>(new Map());
const autoHideTimeouts = ref<Map<number, ReturnType<typeof setTimeout>>>(new Map());
let lastCheckedSecond = -1;

const NOTIFICATION_DURATION_MS = 5000;
const TIME_TOLERANCE = 1; // ±1 second window

// Watch current time for showing notifications
watch(
    () => videoStore.currentTime,
    (time) => {
        if (!props.enabled) return;
        const currentSecond = Math.floor(time);
        if (currentSecond === lastCheckedSecond) return;
        lastCheckedSecond = currentSecond;
        checkNotifications(currentSecond);
    },
);

// Also check on seek
watch(
    () => videoStore.currentTime,
    () => {
        if (!props.enabled) return;
        // On seek, immediately check and clear stale notifications
        cleanStaleNotifications();
    },
);

watch(
    () => props.enabled,
    (val) => {
        if (!val) hideAll();
    },
);

function checkNotifications(currentSecond: number) {
    // Find comments at this timestamp (±tolerance)
    for (let s = currentSecond - TIME_TOLERANCE; s <= currentSecond + TIME_TOLERANCE; s++) {
        const comments = commentsStore.commentsByTimestamp.get(s);
        if (comments) {
            for (const c of comments) {
                if (!activeNotifications.value.has(c.id)) {
                    showNotification(c);
                }
            }
        }
    }
}

function showNotification(comment: VideoComment) {
    activeNotifications.value.set(comment.id, comment);

    // Auto-hide after duration
    const timeout = setTimeout(() => {
        closeNotification(comment.id);
    }, NOTIFICATION_DURATION_MS);

    autoHideTimeouts.value.set(comment.id, timeout);
}

function closeNotification(id: number) {
    activeNotifications.value.delete(id);
    const timeout = autoHideTimeouts.value.get(id);
    if (timeout) {
        clearTimeout(timeout);
        autoHideTimeouts.value.delete(id);
    }
}

function cleanStaleNotifications() {
    const currentSecond = Math.floor(videoStore.currentTime);
    for (const [id, comment] of activeNotifications.value) {
        const commentSecond = Math.floor(comment.timestamp_seconds);
        if (Math.abs(commentSecond - currentSecond) > TIME_TOLERANCE + 2) {
            closeNotification(id);
        }
    }
}

function hideAll() {
    for (const [id] of activeNotifications.value) {
        closeNotification(id);
    }
}

function seekToComment(comment: VideoComment) {
    videoStore.seek(comment.timestamp_seconds);
    videoStore.play();
}

function categoryColor(category: string | null): string {
    switch (category) {
        case 'tecnico': return '#17a2b8';
        case 'tactico': return '#ffc107';
        case 'fisico': return '#28a745';
        case 'mental': return '#9b59b6';
        default: return '#6c757d';
    }
}

function priorityColor(priority: string | null): string {
    switch (priority) {
        case 'critica': return '#dc3545';
        case 'alta': return '#ffc107';
        case 'media': return '#17a2b8';
        default: return '#6c757d';
    }
}

onBeforeUnmount(() => {
    for (const timeout of autoHideTimeouts.value.values()) {
        clearTimeout(timeout);
    }
});
</script>

<template>
    <div class="comment-notifications" v-if="enabled">
        <TransitionGroup name="notification">
            <div
                v-for="[id, comment] in activeNotifications"
                :key="id"
                class="notification-bubble"
                @click="seekToComment(comment)"
            >
                <button
                    class="notification-close"
                    @click.stop="closeNotification(id)"
                >
                    <i class="fas fa-times"></i>
                </button>
                <div class="notification-header">
                    <span class="notification-time">
                        {{ formatTime(comment.timestamp_seconds) }}
                    </span>
                    <span
                        v-if="comment.category"
                        class="notification-badge"
                        :style="{ background: categoryColor(comment.category) }"
                    >
                        {{ comment.category }}
                    </span>
                    <span
                        v-if="comment.priority"
                        class="notification-badge"
                        :style="{ background: priorityColor(comment.priority) }"
                    >
                        {{ comment.priority }}
                    </span>
                </div>
                <div class="notification-body">
                    {{ comment.comment.substring(0, 100) }}{{ comment.comment.length > 100 ? '...' : '' }}
                </div>
                <div class="notification-user">
                    <i class="fas fa-user"></i> {{ comment.user?.name ?? 'Usuario' }}
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.comment-notifications {
    position: absolute;
    bottom: 100%;
    left: 10px;
    right: 10px;
    margin-bottom: 5px;
    pointer-events: none;
    z-index: 100;
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: center;
}

.notification-bubble {
    background: rgba(0, 84, 97, 0.95);
    border: 1px solid rgba(0, 183, 181, 0.4);
    border-radius: 10px;
    padding: 10px 14px;
    max-width: 320px;
    width: 100%;
    color: #fff;
    cursor: pointer;
    pointer-events: auto;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
    position: relative;
}

@media (max-width: 768px) {
    .notification-bubble {
        max-width: 280px;
    }
}

.notification-close {
    position: absolute;
    top: 4px;
    right: 8px;
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    font-size: 11px;
    padding: 2px;
    pointer-events: auto;
}

.notification-close:hover {
    color: #fff;
}

.notification-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
}

.notification-time {
    font-family: monospace;
    font-size: 11px;
    background: #00B7B5;
    padding: 1px 6px;
    border-radius: 4px;
}

.notification-badge {
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 3px;
    color: white;
    text-transform: capitalize;
}

.notification-body {
    font-size: 12px;
    line-height: 1.3;
    color: #ddd;
    word-break: break-word;
}

.notification-user {
    font-size: 10px;
    color: #888;
    margin-top: 4px;
}

.notification-enter-active {
    transition: all 0.3s ease;
}

.notification-leave-active {
    transition: all 0.3s ease;
}

.notification-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.notification-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
