<script setup lang="ts">
import { computed } from 'vue';
import type { Video } from '@/types/video-player';

const props = defineProps<{
    video: Video;
}>();

const fileSizeMB = computed(() => {
    const size = props.video.file_size;
    if (!size || typeof size !== 'number' || !isFinite(size)) return '0.00';
    return (size / 1024 / 1024).toFixed(2);
});

const matchDate = computed(() => {
    if (!props.video.match_date) return '';
    const d = new Date(props.video.match_date);
    return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
});
</script>

<template>
    <div class="card mt-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle"></i> Información del Video</h6>
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td><strong>Equipos:</strong></td>
                                <td>
                                    {{ video.analyzed_team_name }}
                                    <template v-if="video.rival_name">
                                        vs {{ video.rival_name }}
                                    </template>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Categoría:</strong></td>
                                <td>
                                    <span class="badge badge-rugby">
                                        {{ video.category?.name ?? 'Sin categoría' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Fecha:</strong></td>
                                <td>{{ matchDate }}</td>
                            </tr>
                            <tr>
                                <td><strong>Subido por:</strong></td>
                                <td>
                                    {{ video.uploader.name }}
                                    <span class="badge badge-sm badge-info">
                                        {{ video.uploader.role.charAt(0).toUpperCase() + video.uploader.role.slice(1) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-align-left"></i> Descripción</h6>
                    <p class="text-muted">{{ video.description ?? 'Sin descripción' }}</p>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-file"></i> {{ video.file_name }}
                            ({{ fileSizeMB }} MB)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.badge-rugby {
    background-color: #00B7B5;
    color: white;
}
</style>
