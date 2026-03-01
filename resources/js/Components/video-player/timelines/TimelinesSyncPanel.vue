<template>
    <div class="timelines-sync-panel">
        <!-- Toggle Header -->
        <button
            class="timeline-toggle"
            @click="toggleExpanded"
        >
            <div class="toggle-content">
                <i class="fas fa-film mr-2" style="color: #FFC300;"></i>
                <strong>Sincronización de Timelines</strong>
                <span v-if="!isExpanded" class="timeline-count ml-2">
                    ({{ totalTimelines }} {{ totalTimelines === 1 ? 'timeline' : 'timelines' }})
                </span>
            </div>
            <i class="fas" :class="isExpanded ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
        </button>

        <!-- Timelines Container -->
        <transition name="slide-down">
            <div v-show="isExpanded" class="timelines-container">
                <!-- Master Timeline (referencia fija) -->
                <TimelineRow
                    type="master"
                    label="Master Video"
                    :current-time="currentTime"
                    :duration="duration"
                    :offset="0"
                    :draggable="false"
                    @seek="handleSeek"
                />

                <!-- Slave Timelines (arrastrables para multi-cámara) -->
                <TimelineRow
                    v-for="slave in slaves"
                    :key="slave.id"
                    type="slave"
                    :label="slave.title"
                    :current-time="getSlaveCurrentTime(slave.id)"
                    :duration="duration"
                    :offset="Number(slave.sync_offset || 0)"
                    :draggable="true"
                    @offset-changed="handleSlaveOffsetChanged(slave.id, $event)"
                    @reset-offset="handleSlaveOffsetChanged(slave.id, 0)"
                    @seek="handleSeek"
                />

                <!-- Clips Timeline (arrastrable para sincronizar clips XML) -->
                <TimelineRow
                    v-if="hasClips"
                    type="clips"
                    label="Clips XML"
                    :current-time="currentTime"
                    :duration="duration"
                    :offset="Number(clipsOffset)"
                    :draggable="true"
                    @offset-changed="handleClipsOffsetChanged"
                    @reset-offset="handleClipsOffsetChanged(0)"
                    @seek="handleSeek"
                />

                <!-- Help message -->
                <div class="help-message">
                    <i class="fas fa-info-circle"></i>
                    <span>
                        <strong>Click</strong> en cualquier timeline para adelantar/atrasar el video.
                        <strong>Arrastra</strong> las barras para sincronizar (se guarda automáticamente).
                    </span>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, inject } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useClipsStore } from '@/stores/clipsStore';
import TimelineRow from './TimelineRow.vue';
import type { SlaveVideo } from '@/types/video-player';

const props = defineProps<{
    slaves: SlaveVideo[];
}>();

const videoStore = useVideoStore();
const clipsStore = useClipsStore();
const videoApi = inject<any>('videoApi');
const toast = inject<any>('toast');
const multiCamera = inject<any>('multiCamera');

const isExpanded = ref(true);

const currentTime = computed(() => videoStore.currentTime);
const duration = computed(() => videoStore.duration || 1);
const hasClips = computed(() => clipsStore.clips.length > 0);
const clipsOffset = computed(() => videoStore.video?.timeline_offset || 0);

const totalTimelines = computed(() => {
    let count = 1; // Master
    count += props.slaves.length;
    if (hasClips.value) count += 1;
    return count;
});

function toggleExpanded(event?: MouseEvent) {
    isExpanded.value = !isExpanded.value;
    (event?.currentTarget as HTMLElement)?.blur();
}

function getSlaveCurrentTime(slaveId: number): number {
    if (!multiCamera || !multiCamera.getSlaveCurrentTime) return 0;
    return multiCamera.getSlaveCurrentTime(slaveId);
}

async function handleSlaveOffsetChanged(slaveId: number, newOffset: number) {
    if (!videoApi || !multiCamera) {
        console.error('❌ videoApi or multiCamera not available');
        toast?.error('Error: Servicio no disponible');
        return;
    }

    try {
        // Guardar en backend
        await videoApi.updateSlaveSync?.(slaveId, { sync_offset: newOffset });

        // Aplicar en multiCamera
        multiCamera.adjustSyncOffset(slaveId, newOffset);

        toast?.success(`Cámara sincronizada (offset: ${newOffset > 0 ? '+' : ''}${newOffset.toFixed(1)}s)`);
    } catch (error) {
        console.error('Error updating slave offset:', error);
        toast?.error('Error al sincronizar cámara');
    }
}

async function handleClipsOffsetChanged(newOffset: number) {
    if (!videoApi) {
        console.error('❌ videoApi not available');
        toast?.error('Error: Servicio no disponible');
        return;
    }

    try {
        // Guardar en backend
        await videoApi.setTimelineOffset(newOffset);

        // Actualizar en videoStore
        videoStore.updateTimelineOffset(newOffset);

        toast?.success(`Clips sincronizados (offset: ${newOffset > 0 ? '+' : ''}${newOffset.toFixed(1)}s)`);
    } catch (error) {
        console.error('Error updating clips offset:', error);
        toast?.error('Error al sincronizar clips');
    }
}

function handleSeek(time: number) {
    // Seek to the specified time in the master video
    videoStore.seek(time);
}
</script>

<style scoped>
.timelines-sync-panel {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.timeline-toggle {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #252525;
    border: none;
    color: #fff;
    cursor: pointer;
    transition: background-color 0.2s;
    font-size: 12px;
}

.timeline-toggle:hover {
    background: #2a2a2a;
}

.timeline-toggle:focus {
    outline: none;
}

.toggle-content {
    display: flex;
    align-items: center;
}

.timeline-count {
    color: #888;
    font-size: 11px;
}

.timelines-container {
    padding: 12px;
    background: #0f0f0f;
}

.help-message {
    margin-top: 12px;
    padding: 8px 12px;
    background: rgba(255, 195, 0, 0.1);
    border: 1px solid rgba(255, 195, 0, 0.3);
    border-radius: 4px;
    color: #FFC300;
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.help-message i {
    flex-shrink: 0;
}

.help-message strong {
    color: #fff;
}

/* Slide down transition */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
    max-height: 600px;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    max-height: 0;
    opacity: 0;
}
</style>
