import type {
    VideoComment,
    VideoClip,
    ClipCategory,
    VideoAnnotation,
    VideoStats,
} from '@/types/video-player';

function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta?.getAttribute('content') ?? '';
}

function getBaseHeaders(): Record<string, string> {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
}

async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
    const response = await fetch(url, {
        ...options,
        headers: {
            ...getBaseHeaders(),
            ...options.headers,
        },
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new ApiError(response.status, errorData.message || response.statusText, errorData);
    }

    return response.json();
}

export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        public data?: any,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

export function useVideoApi(videoId: number) {
    // ====== View Tracking ======
    // Routes: POST /api/videos/{video}/track-view
    //         PATCH /api/videos/{video}/update-duration
    //         PATCH /api/videos/{video}/mark-completed
    async function trackView() {
        return request<{ success: boolean; view_id?: number; view_count: number; cooldown?: boolean }>(
            `/api/videos/${videoId}/track-view`,
            { method: 'POST' },
        );
    }

    async function updateDuration(viewId: number, duration: number) {
        return request<{ success: boolean }>(
            `/api/videos/${videoId}/update-duration`,
            {
                method: 'PATCH',
                body: JSON.stringify({
                    view_id: viewId,
                    duration: Math.floor(duration)  // Laravel expects integer
                })
            },
        );
    }

    async function markCompleted(viewId: number) {
        return request<{ success: boolean }>(
            `/api/videos/${videoId}/mark-completed`,
            {
                method: 'PATCH',
                body: JSON.stringify({ view_id: viewId })
            },
        );
    }

    // ====== Stats ======
    // Route: GET /api/videos/{video}/stats
    async function getStats(): Promise<VideoStats> {
        return request<VideoStats>(`/api/videos/${videoId}/stats`);
    }

    // ====== Comments ======
    // Route: POST /videos/{video}/comments (NOT /api/)
    // Route: DELETE /comments/{comment} (NOT /api/videos/...)
    async function getComments(): Promise<VideoComment[]> {
        // Comments are loaded via Inertia props, but this can be used for refresh
        return request<VideoComment[]>(`/videos/${videoId}/comments`);
    }

    async function createComment(data: {
        comment: string;
        timestamp_seconds: number;
        category?: string;
        priority?: string;
    }): Promise<{ success: boolean; comment: VideoComment }> {
        return request(`/videos/${videoId}/comments`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async function deleteComment(commentId: number): Promise<{ success: boolean }> {
        return request(`/comments/${commentId}`, {
            method: 'DELETE',
        });
    }

    async function replyToComment(
        commentId: number,
        data: { reply_comment: string },
    ): Promise<{ success: boolean; reply: VideoComment }> {
        return request(`/videos/${videoId}/comments/${commentId}/reply`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    // ====== Clips ======
    // Routes: GET  /api/videos/{video}/clips/
    //         POST /api/videos/{video}/clips/quick
    //         PUT  /videos/{video}/clips/{clip}
    //         DELETE /videos/{video}/clips/{clip}
    //         POST /api/videos/{video}/clips/timeline-offset
    async function getClips(): Promise<VideoClip[]> {
        return request<VideoClip[]>(`/api/videos/${videoId}/clips`);
    }

    async function createClip(data: {
        clip_category_id: number;
        start_time: number;
        end_time: number;
        title?: string;
        notes?: string;
    }): Promise<{ success: boolean; clip: VideoClip }> {
        return request(`/api/videos/${videoId}/clips/quick`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async function updateClip(
        clipId: number,
        data: Partial<Pick<VideoClip, 'start_time' | 'end_time' | 'title' | 'notes'>>,
    ): Promise<{ success: boolean }> {
        return request(`/videos/${videoId}/clips/${clipId}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    async function deleteClip(clipId: number): Promise<{ success: boolean }> {
        return request(`/videos/${videoId}/clips/${clipId}`, {
            method: 'DELETE',
        });
    }

    async function setTimelineOffset(offset: number): Promise<{ success: boolean }> {
        return request(`/api/videos/${videoId}/clips/timeline-offset`, {
            method: 'POST',
            body: JSON.stringify({ timeline_offset: offset }),
        });
    }

    // ====== Clip Categories ======
    // Route: GET /api/clip-categories?video_id=X
    // Route: POST /admin/clip-categories (resource route)
    // Route: PUT /admin/clip-categories/{id}
    // Route: DELETE /admin/clip-categories/{id}
    async function getClipCategories(scope?: string): Promise<{
        categories: ClipCategory[];
        grouped: Record<string, ClipCategory[]>;
    }> {
        const params = new URLSearchParams();
        params.set('video_id', String(videoId));
        if (scope) params.set('scope', scope);
        return request(`/api/clip-categories?${params}`);
    }

    async function createCategory(data: {
        name: string;
        color: string;
        hotkey?: string;
        scope?: string;
        icon?: string;
        lead_seconds?: number;
        lag_seconds?: number;
    }): Promise<{ success: boolean; category: ClipCategory }> {
        return request('/admin/clip-categories', {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async function updateCategory(
        categoryId: number,
        data: Partial<ClipCategory>,
    ): Promise<{ success: boolean }> {
        return request(`/admin/clip-categories/${categoryId}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    async function deleteCategory(categoryId: number): Promise<{ success: boolean }> {
        return request(`/admin/clip-categories/${categoryId}`, {
            method: 'DELETE',
        });
    }

    // ====== Annotations ======
    // Routes: GET  /api/annotations/video/{videoId}
    //         POST /api/annotations/
    //         DELETE /api/annotations/{id}
    async function getAnnotations(): Promise<VideoAnnotation[]> {
        const resp = await request<{ success: boolean; annotations: VideoAnnotation[] }>(
            `/api/annotations/video/${videoId}`,
        );
        // Defensive: ensure we always return an array
        return Array.isArray(resp.annotations) ? resp.annotations : [];
    }

    async function saveAnnotation(data: {
        timestamp: number;
        annotation_data: string;
        annotation_type?: string;
        duration_seconds?: number;
        is_permanent?: boolean;
    }): Promise<{ success: boolean; annotation: VideoAnnotation }> {
        return request('/api/annotations', {
            method: 'POST',
            body: JSON.stringify({ ...data, video_id: videoId }),
        });
    }

    async function deleteAnnotation(annotationId: number): Promise<{ success: boolean }> {
        return request(`/api/annotations/${annotationId}`, {
            method: 'DELETE',
        });
    }

    // ====== Video Deletion ======
    async function deleteVideo(): Promise<{ success: boolean; message: string }> {
        return request(`/videos/${videoId}`, {
            method: 'DELETE',
        });
    }

    // ====== Multi-Camera ======
    // Route: DELETE /videos/{video}/multi-camera/remove?slave_id=X
    async function removeSlaveVideo(slaveId: number): Promise<{ success: boolean }> {
        return request(`/videos/${videoId}/multi-camera/remove?slave_id=${slaveId}`, {
            method: 'DELETE',
        });
    }

    return {
        // View tracking
        trackView,
        updateDuration,
        markCompleted,
        // Stats
        getStats,
        // Comments
        getComments,
        createComment,
        deleteComment,
        replyToComment,
        // Clips
        getClips,
        createClip,
        updateClip,
        deleteClip,
        setTimelineOffset,
        // Clip Categories
        getClipCategories,
        createCategory,
        updateCategory,
        deleteCategory,
        // Annotations
        getAnnotations,
        saveAnnotation,
        deleteAnnotation,
        // Video
        deleteVideo,
        // Multi-Camera
        removeSlaveVideo,
    };
}
