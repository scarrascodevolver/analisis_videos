<script setup lang="ts">
import { useCommentsStore } from '@/stores/commentsStore';
import CommentItem from './CommentItem.vue';
import type { User } from '@/types/video-player';

const props = defineProps<{
    videoId: number;
    currentUser: User;
    allUsers: Pick<User, 'id' | 'name' | 'role'>[];
}>();

const commentsStore = useCommentsStore();
</script>

<template>
    <div class="comments-scroll-container">
        <div v-if="commentsStore.comments.length === 0" class="text-center py-4">
            <i class="fas fa-comments fa-2x mb-2" style="color: #555;"></i>
            <p style="color: #888; font-size: 13px;">No hay comentarios aún</p>
        </div>

        <div v-else class="comments-list p-2">
            <CommentItem
                v-for="comment in commentsStore.comments"
                :key="comment.id"
                :comment="comment"
                :video-id="videoId"
                :current-user="currentUser"
                :all-users="allUsers"
                :depth="0"
            />
        </div>
    </div>
</template>

<style scoped>
.comments-scroll-container {
    max-height: 400px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #333 #1a1a1a;
}

.comments-scroll-container::-webkit-scrollbar {
    width: 6px;
}

.comments-scroll-container::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.comments-scroll-container::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 3px;
}

.comments-list {
    background: #111;
}
</style>
