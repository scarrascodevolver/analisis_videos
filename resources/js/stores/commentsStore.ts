import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { VideoComment, CommentCategory, CommentPriority } from '@/types/video-player';
import { useVideoApi } from '@/composables/useVideoApi';

export const useCommentsStore = defineStore('comments', () => {
    const comments = ref<VideoComment[]>([]);
    const isSubmitting = ref(false);
    const replyingTo = ref<number | null>(null);

    // Indexed by timestamp for O(1) lookup by timeline/notifications
    const commentsByTimestamp = computed(() => {
        const map = new Map<number, VideoComment[]>();
        for (const c of flatComments.value) {
            const ts = Math.floor(c.timestamp_seconds);
            if (!map.has(ts)) map.set(ts, []);
            map.get(ts)!.push(c);
        }
        return map;
    });

    // Flat list of all comments (including nested replies) for timeline markers
    const flatComments = computed(() => {
        const result: VideoComment[] = [];
        function flatten(items: VideoComment[]) {
            for (const c of items) {
                result.push(c);
                if (c.replies?.length) flatten(c.replies);
            }
        }
        flatten(comments.value);
        return result;
    });

    const commentCount = computed(() => flatComments.value.length);

    function setComments(initial: VideoComment[]) {
        comments.value = initial;
    }

    async function addComment(
        videoId: number,
        data: {
            comment: string;
            timestamp_seconds: number;
            category?: CommentCategory;
            priority?: CommentPriority;
        },
    ): Promise<VideoComment | null> {
        isSubmitting.value = true;
        try {
            const api = useVideoApi(videoId);
            const response = await api.createComment(data);
            if (response.success && response.comment) {
                comments.value.push(response.comment);
                return response.comment;
            }
            return null;
        } finally {
            isSubmitting.value = false;
        }
    }

    async function deleteComment(videoId: number, commentId: number): Promise<boolean> {
        try {
            const api = useVideoApi(videoId);
            const response = await api.deleteComment(commentId);
            if (response.success) {
                removeCommentById(commentId);
                return true;
            }
            return false;
        } catch {
            return false;
        }
    }

    async function addReply(
        videoId: number,
        parentId: number,
        text: string,
    ): Promise<VideoComment | null> {
        isSubmitting.value = true;
        try {
            const api = useVideoApi(videoId);
            const response = await api.replyToComment(parentId, { reply_comment: text });
            if (response.success && response.reply) {
                // Add reply to parent comment's replies array
                addReplyToParent(comments.value, parentId, response.reply);
                return response.reply;
            }
            return null;
        } finally {
            isSubmitting.value = false;
        }
    }

    function removeCommentById(id: number) {
        // Try top-level first
        const topIndex = comments.value.findIndex(c => c.id === id);
        if (topIndex !== -1) {
            comments.value.splice(topIndex, 1);
            return;
        }
        // Try nested
        removeNestedComment(comments.value, id);
    }

    function removeNestedComment(items: VideoComment[], id: number): boolean {
        for (const item of items) {
            if (item.replies) {
                const idx = item.replies.findIndex(r => r.id === id);
                if (idx !== -1) {
                    item.replies.splice(idx, 1);
                    return true;
                }
                if (removeNestedComment(item.replies, id)) return true;
            }
        }
        return false;
    }

    function addReplyToParent(items: VideoComment[], parentId: number, reply: VideoComment): boolean {
        for (const item of items) {
            if (item.id === parentId) {
                if (!item.replies) item.replies = [];
                item.replies.push(reply);
                return true;
            }
            if (item.replies && addReplyToParent(item.replies, parentId, reply)) {
                return true;
            }
        }
        return false;
    }

    function setReplyingTo(commentId: number | null) {
        replyingTo.value = commentId;
    }

    return {
        comments,
        isSubmitting,
        replyingTo,
        commentsByTimestamp,
        flatComments,
        commentCount,
        setComments,
        addComment,
        deleteComment,
        addReply,
        setReplyingTo,
    };
});
