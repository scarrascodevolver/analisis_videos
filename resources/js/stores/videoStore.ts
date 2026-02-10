import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Video, PlaybackSpeed } from '@/types/video-player';

export const useVideoStore = defineStore('video', () => {
    // Core refs
    const videoRef = ref<HTMLVideoElement | null>(null);
    const video = ref<Video | null>(null);

    // Playback state
    const currentTime = ref(0);
    const duration = ref(0);
    const isPlaying = ref(false);
    const playbackRate = ref<PlaybackSpeed>(1);
    const isBuffering = ref(false);
    const volume = ref(1);
    const isMuted = ref(false);

    // UI state
    const isPiPActive = ref(false);

    // Computed
    const progress = computed(() => {
        if (!duration.value) return 0;
        return (currentTime.value / duration.value) * 100;
    });

    const formattedCurrentTime = computed(() => formatTime(currentTime.value));
    const formattedDuration = computed(() => formatTime(duration.value));

    const canCreateClips = computed(() => {
        return video.value !== null;
    });

    // Actions
    function setVideoRef(el: HTMLVideoElement) {
        videoRef.value = el;
    }

    function setVideo(v: Video) {
        video.value = v;
    }

    function play() {
        videoRef.value?.play();
    }

    function pause() {
        videoRef.value?.pause();
    }

    function togglePlay() {
        if (!videoRef.value) return;
        if (videoRef.value.paused) {
            play();
        } else {
            pause();
        }
    }

    function seek(time: number) {
        if (!videoRef.value) return;
        const clamped = Math.max(0, Math.min(time, duration.value));
        videoRef.value.currentTime = clamped;
        currentTime.value = clamped;
    }

    function seekRelative(delta: number) {
        seek(currentTime.value + delta);
    }

    function setPlaybackRate(rate: PlaybackSpeed) {
        if (!videoRef.value) return;
        const numericRate = Number(rate);
        videoRef.value.defaultPlaybackRate = numericRate;
        videoRef.value.playbackRate = numericRate;
        playbackRate.value = numericRate as PlaybackSpeed;
    }

    function setVolume(v: number) {
        if (!videoRef.value) return;
        const clamped = Math.max(0, Math.min(1, v));
        videoRef.value.volume = clamped;
        volume.value = clamped;
        isMuted.value = clamped === 0;
    }

    function toggleMute() {
        if (!videoRef.value) return;
        videoRef.value.muted = !videoRef.value.muted;
        isMuted.value = videoRef.value.muted;
    }

    async function togglePiP() {
        if (!videoRef.value) return;
        try {
            if (document.pictureInPictureElement) {
                await document.exitPictureInPicture();
                isPiPActive.value = false;
            } else {
                await videoRef.value.requestPictureInPicture();
                isPiPActive.value = true;
            }
        } catch (e) {
            console.warn('PiP not supported:', e);
        }
    }

    // Event handlers (called from VideoElement)
    function onTimeUpdate() {
        if (!videoRef.value) return;
        currentTime.value = videoRef.value.currentTime;
    }

    function onDurationChange() {
        if (!videoRef.value) return;
        duration.value = videoRef.value.duration;
    }

    function onPlay() {
        isPlaying.value = true;
    }

    function onPause() {
        isPlaying.value = false;
    }

    function onWaiting() {
        isBuffering.value = true;
    }

    function onCanPlay() {
        isBuffering.value = false;
    }

    function onVolumeChange() {
        if (!videoRef.value) return;
        volume.value = videoRef.value.volume;
        isMuted.value = videoRef.value.muted;
    }

    return {
        // State
        videoRef,
        video,
        currentTime,
        duration,
        isPlaying,
        playbackRate,
        isBuffering,
        volume,
        isMuted,
        isPiPActive,
        // Computed
        progress,
        formattedCurrentTime,
        formattedDuration,
        canCreateClips,
        // Actions
        setVideoRef,
        setVideo,
        play,
        pause,
        togglePlay,
        seek,
        seekRelative,
        setPlaybackRate,
        setVolume,
        toggleMute,
        togglePiP,
        // Event handlers
        onTimeUpdate,
        onDurationChange,
        onPlay,
        onPause,
        onWaiting,
        onCanPlay,
        onVolumeChange,
    };
});

// Utility
function formatTime(seconds: number): string {
    if (!seconds || isNaN(seconds)) return '00:00';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    if (h > 0) {
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

export { formatTime };
