import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { VideoClip, ClipCategory } from '@/types/video-player';
import { useVideoApi } from '@/composables/useVideoApi';

export const useClipsStore = defineStore('clips', () => {
    // State
    const clips = ref<VideoClip[]>([]);
    const categories = ref<ClipCategory[]>([]);
    const isRecording = ref(false);
    const recordingCategoryId = ref<number | null>(null);
    const recordingStartTime = ref(0);
    const isLoading = ref(false);

    // Computed
    const clipsByCategory = computed(() => {
        const grouped: Record<number, VideoClip[]> = {};

        // Defensive: ensure clips.value is always an array
        const safeClips = Array.isArray(clips.value) ? clips.value : [];

        safeClips.forEach((clip) => {
            if (!grouped[clip.clip_category_id]) {
                grouped[clip.clip_category_id] = [];
            }
            grouped[clip.clip_category_id].push(clip);
        });

        // Sort clips within each category by start_time
        Object.keys(grouped).forEach((catId) => {
            grouped[Number(catId)].sort((a, b) => a.start_time - b.start_time);
        });

        return grouped;
    });

    const activeCategories = computed(() => {
        // API already filters with .active() scope, so all returned categories are active
        return categories.value;
    });

    const recordingCategory = computed(() => {
        if (!recordingCategoryId.value) return null;
        return categories.value.find((cat) => cat.id === recordingCategoryId.value) || null;
    });

    // Actions
    async function loadClips(videoId: number) {
        isLoading.value = true;
        try {
            const api = useVideoApi(videoId);
            const loadedClips = await api.getClips();
            clips.value = loadedClips;
        } catch (error) {
            console.error('Error loading clips:', error);
            throw error;
        } finally {
            isLoading.value = false;
        }
    }

    async function loadCategories(videoId: number) {
        isLoading.value = true;
        try {
            const api = useVideoApi(videoId);
            const response = await api.getClipCategories();
            categories.value = response.categories;
        } catch (error) {
            console.error('Error loading clip categories:', error);
            throw error;
        } finally {
            isLoading.value = false;
        }
    }

    function startRecording(categoryId: number, currentTime: number) {
        if (isRecording.value) {
            console.warn('Already recording. Stop current recording first.');
            return;
        }

        isRecording.value = true;
        recordingCategoryId.value = categoryId;
        recordingStartTime.value = currentTime;

        // Auto-play video if paused when starting recording
        // This is imported at the top level, so we need to access it through the composable
        // We'll handle this in the component that calls startRecording
    }

    async function stopRecording(videoId: number, currentTime: number) {
        if (!isRecording.value || recordingCategoryId.value === null) {
            console.warn('No active recording to stop.');
            return null;
        }

        const categoryId = recordingCategoryId.value;
        const startTime = recordingStartTime.value;
        const endTime = currentTime;

        // Apply lead/lag seconds from category
        const category = categories.value.find((cat) => cat.id === categoryId);
        const adjustedStartTime = category?.lead_seconds
            ? Math.max(0, startTime - category.lead_seconds)
            : startTime;
        const adjustedEndTime = category?.lag_seconds
            ? endTime + category.lag_seconds
            : endTime;

        // Validate minimum duration (0.5 seconds)
        const duration = adjustedEndTime - adjustedStartTime;
        if (duration < 0.5) {
            // Reset recording state
            isRecording.value = false;
            recordingCategoryId.value = null;
            recordingStartTime.value = 0;

            throw new Error('El clip debe durar al menos 0.5 segundos. Graba por más tiempo.');
        }

        // Reset recording state
        isRecording.value = false;
        recordingCategoryId.value = null;
        recordingStartTime.value = 0;

        try {
            const api = useVideoApi(videoId);
            const response = await api.createClip({
                clip_category_id: categoryId,
                start_time: adjustedStartTime,
                end_time: adjustedEndTime,
            });

            if (response.success && response.clip) {
                // Add category info if available
                if (category) {
                    response.clip.category = category;
                }
                clips.value.push(response.clip);
                return response.clip;
            }
            return null;
        } catch (error) {
            console.error('Error creating clip:', error);
            throw error;
        }
    }

    function cancelRecording() {
        isRecording.value = false;
        recordingCategoryId.value = null;
        recordingStartTime.value = 0;
    }

    async function toggleRecording(videoId: number, categoryId: number, currentTime: number) {
        if (isRecording.value) {
            // If recording the same category, stop it
            if (recordingCategoryId.value === categoryId) {
                return await stopRecording(videoId, currentTime);
            } else {
                // If recording a different category, cancel current and start new
                cancelRecording();
                startRecording(categoryId, currentTime);
                return null;
            }
        } else {
            // Start new recording
            startRecording(categoryId, currentTime);
            return null;
        }
    }

    async function removeClip(videoId: number, clipId: number) {
        try {
            const api = useVideoApi(videoId);
            await api.deleteClip(clipId);

            // Remove from local array
            const index = clips.value.findIndex((c) => c.id === clipId);
            if (index !== -1) {
                clips.value.splice(index, 1);
            }
        } catch (error) {
            console.error('Error deleting clip:', error);
            throw error;
        }
    }

    async function updateClip(
        videoId: number,
        clipId: number,
        data: Partial<Pick<VideoClip, 'start_time' | 'end_time' | 'title' | 'notes'>>
    ) {
        try {
            const api = useVideoApi(videoId);
            await api.updateClip(clipId, data);

            // Update local clip
            const clip = clips.value.find((c) => c.id === clipId);
            if (clip) {
                Object.assign(clip, data);
            }
        } catch (error) {
            console.error('Error updating clip:', error);
            throw error;
        }
    }

    function reset() {
        clips.value = [];
        categories.value = [];
        isRecording.value = false;
        recordingCategoryId.value = null;
        recordingStartTime.value = 0;
        isLoading.value = false;
    }

    return {
        // State
        clips,
        categories,
        isRecording,
        recordingCategoryId,
        recordingStartTime,
        isLoading,
        // Computed
        clipsByCategory,
        activeCategories,
        recordingCategory,
        // Actions
        loadClips,
        loadCategories,
        startRecording,
        stopRecording,
        cancelRecording,
        toggleRecording,
        removeClip,
        updateClip,
        reset,
    };
});
