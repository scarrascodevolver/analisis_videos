<script setup lang="ts">
import { computed } from 'vue';
import type { Video, User } from '@/types/video-player';

const props = defineProps<{
    video: Video;
    user: User;
}>();

const emit = defineEmits<{
    showStats: [];
    addAngle: [];
    toggleComments: [];
    deleteVideo: [];
    toggleTimelines: [];
}>();

const isAnalystOrCoach = computed(() =>
    ['analista', 'entrenador'].includes(props.user.role)
);

const canViewStats = computed(() =>
    ['analista', 'entrenador', 'jugador'].includes(props.user.role)
);

const canEdit = computed(() =>
    isAnalystOrCoach.value || props.user.id === props.video.uploaded_by
);

const canDelete = computed(() => isAnalystOrCoach.value);
</script>

<template>
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-play"></i>
            {{ video.title }}
            <br>
            <small class="text-muted">
                <i class="fas fa-eye"></i>
                <span>{{ video.view_count }}</span> visualizaciones
                &bull;
                <i class="fas fa-users"></i>
                <span>{{ video.unique_viewers }}</span> usuarios
            </small>
        </h3>
        <div class="card-tools">
            <button
                v-if="canViewStats"
                class="btn btn-sm btn-rugby-light mr-2"
                @click="$emit('showStats')"
            >
                <i class="fas fa-eye"></i> Visualizaciones
            </button>
            <button
                v-if="isAnalystOrCoach"
                class="btn btn-sm btn-rugby mr-2"
                @click="$emit('addAngle')"
            >
                <i class="fas fa-video"></i> Agregar Ángulo
            </button>
            <button
                v-if="isAnalystOrCoach"
                class="btn btn-sm btn-rugby-outline mr-2"
                @click="$emit('toggleTimelines')"
            >
                <i class="fas fa-film"></i> Timelines
            </button>
            <button
                v-if="user.role === 'jugador'"
                class="btn btn-sm btn-rugby-outline mr-2"
                @click="$emit('toggleComments')"
            >
                <i class="fas fa-eye-slash"></i> Ocultar Comentarios
            </button>
            <a
                v-if="canEdit"
                :href="video.edit_url"
                class="btn btn-sm btn-rugby-light"
            >
                <i class="fas fa-edit"></i> Editar
            </a>
            <button
                v-if="canDelete"
                class="btn btn-sm btn-rugby-dark"
                @click="$emit('deleteVideo')"
            >
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</template>

<style scoped>
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    padding: 0.5rem 0.9rem;
}

.card-title {
    flex: 1;
    margin: 0;
}

.card-tools {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    align-items: center;
    margin-left: auto;
    font-size: 11px;
}

.card-tools .btn {
    padding: 3px 7px;
    font-size: 11px;
    line-height: 1.1;
}

.btn-rugby {
    background-color: #005461;
    border-color: #005461;
    color: white;
}
.btn-rugby:hover {
    background-color: #003d4a;
    color: white;
}

.btn-rugby-light {
    background-color: #018790;
    border-color: #018790;
    color: white;
}
.btn-rugby-light:hover {
    background-color: #005461;
    color: white;
}

.btn-rugby-dark {
    background-color: #003d4a;
    border-color: #003d4a;
    color: white;
}
.btn-rugby-dark:hover {
    background-color: #002530;
    color: white;
}

.btn-rugby-outline {
    background: transparent;
    border: 1px solid #005461;
    color: #00B7B5;
}
.btn-rugby-outline:hover {
    background: #005461;
    color: white;
}

.text-muted {
    color: #aaa !important;
}
</style>
