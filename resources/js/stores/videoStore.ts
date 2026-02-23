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

    // Clip playback boundary (null = play freely)
    const clipEndTime = ref<number | null>(null);

    // YouTube state
    const youtubePlayer   = ref<any>(null);   // YT.Player instance
    const isYoutubeActive = ref(false);
    let   ytPollInterval: ReturnType<typeof setInterval> | null = null;

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

    function updateTimelineOffset(offset: number) {
        if (video.value) {
            video.value.timeline_offset = offset;
        }
    }

    function setYouTubePlayer(player: any) {
        youtubePlayer.value   = player;
        isYoutubeActive.value = true;
        // Poll currentTime + duration every 250ms (YT API has no timeupdate event)
        ytPollInterval = setInterval(() => {
            if (!youtubePlayer.value) return;
            const ct  = youtubePlayer.value.getCurrentTime?.() ?? 0;
            const dur = youtubePlayer.value.getDuration?.()    ?? 0;
            currentTime.value = ct;
            if (dur > 0) duration.value = dur;
            // Respect clipEndTime boundary
            if (clipEndTime.value !== null && ct >= clipEndTime.value) {
                youtubePlayer.value.pauseVideo();
                clipEndTime.value = null;
            }
        }, 250);
    }

    function clearYouTubePlayer() {
        if (ytPollInterval) clearInterval(ytPollInterval);
        ytPollInterval        = null;
        youtubePlayer.value   = null;
        isYoutubeActive.value = false;
    }

    function play() {
        if (isYoutubeActive.value && youtubePlayer.value) {
            youtubePlayer.value.playVideo();
        } else {
            videoRef.value?.play();
        }
    }

    function pause() {
        if (isYoutubeActive.value && youtubePlayer.value) {
            youtubePlayer.value.pauseVideo();
        } else {
            videoRef.value?.pause();
        }
    }

    function togglePlay() {
        if (isYoutubeActive.value) {
            if (isPlaying.value) pause();
            else play();
            return;
        }
        if (!videoRef.value) return;
        if (videoRef.value.paused) {
            play();
        } else {
            pause();
        }
    }

    function seek(time: number) {
        const clamped = Math.max(0, Math.min(time, duration.value));
        currentTime.value = clamped;
        clipEndTime.value = null; // Cancel any active clip boundary on manual seek
        if (isYoutubeActive.value && youtubePlayer.value) {
            youtubePlayer.value.seekTo(clamped, true);
        } else if (videoRef.value) {
            videoRef.value.currentTime = clamped;
        }
    }

    /** Seek to start, play, and auto-pause when end is reached */
    function playClip(start: number, end: number) {
        const clamped = Math.max(0, Math.min(start, duration.value));
        currentTime.value = clamped;
        clipEndTime.value = end;
        if (isYoutubeActive.value && youtubePlayer.value) {
            youtubePlayer.value.seekTo(clamped, true);
            youtubePlayer.value.playVideo();
        } else if (videoRef.value) {
            videoRef.value.currentTime = clamped;
            videoRef.value.play();
        }
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
        if (clipEndTime.value !== null && currentTime.value >= clipEndTime.value) {
            videoRef.value.pause();
            clipEndTime.value = null;
        }
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
        clipEndTime.value = null; // Cancel boundary on manual pause
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
        // YouTube state
        youtubePlayer,
        isYoutubeActive,
        // Computed
        progress,
        formattedCurrentTime,
        formattedDuration,
        canCreateClips,
        // Actions
        setVideoRef,
        setVideo,
        updateTimelineOffset,
        setYouTubePlayer,
        clearYouTubePlayer,
        play,
        pause,
        togglePlay,
        seek,
        seekRelative,
        playClip,
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
