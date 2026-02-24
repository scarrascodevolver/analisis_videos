<template>
    <div v-if="slaves.length > 0" class="slave-videos-list">
        <SlaveVideo
            v-for="slave in slaves"
            :key="`${slave.id}|${slave.stream_url}|${slave.youtube_video_id ?? ''}`"
            :slave="slave"
            @click="handleSwapMaster(slave.id)"
            @remove="handleRemoveSlave"
        />
    </div>
</template>

<script setup lang="ts">
import SlaveVideo from './SlaveVideo.vue';
import type { SlaveVideo as SlaveVideoType } from '@/types/video-player';

const emit = defineEmits<{
    swapMaster: [slaveId: number];
    removeSlave: [slaveId: number];
}>();

const props = defineProps<{
    slaves: SlaveVideoType[];
}>();

function handleSwapMaster(slaveId: number) {
    emit('swapMaster', slaveId);
}

function handleRemoveSlave(slaveId: number) {
    emit('removeSlave', slaveId);
}
</script>

<style scoped>
.slave-videos-list {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #000;
}

.slave-videos-list :deep(.slave-video-card) {
    flex: 0 0 auto;
    border-radius: 0;
    border: none;
    border-bottom: 1px solid #111;
}

.slave-videos-list :deep(.video-wrapper) {
    padding-bottom: 0;
    height: 18vh;
    max-height: 18vh;
}
</style>
