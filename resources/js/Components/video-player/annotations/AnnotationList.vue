<script setup lang="ts">
import { ref } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useAnnotationsStore } from '@/stores/annotationsStore';
import { useVideoApi } from '@/composables/useVideoApi';
import { formatTime } from '@/stores/videoStore';
import type { VideoAnnotation } from '@/types/video-player';

const videoStore = useVideoStore();
const annotationsStore = useAnnotationsStore();
const videoApi = useVideoApi(videoStore.video?.id || 0);

const isDeleting = ref<number | null>(null);

function getAnnotationTypeLabel(type: string): string {
    const labels: Record<string, string> = {
        select: 'Selección',
        arrow: 'Flecha',
        line: 'Línea',
        circle: 'Círculo',
        rectangle: 'Rectángulo',
        free_draw: 'Dibujo Libre',
        text: 'Texto',
        area: 'Área',
    };
    return labels[type] || type;
}

function getAnnotationTypeIcon(type: string): string {
    const icons: Record<string, string> = {
        select: 'fa-mouse-pointer',
        arrow: 'fa-arrow-right',
        line: 'fa-minus',
        circle: 'fa-circle',
        rectangle: 'fa-square',
        free_draw: 'fa-pencil-alt',
        text: 'fa-font',
        area: 'fa-draw-polygon',
    };
    return icons[type] || 'fa-pencil-alt';
}

function seekToAnnotation(annotation: VideoAnnotation) {
    videoStore.seek(annotation.timestamp);
    videoStore.pause();
}

async function deleteAnnotation(annotation: VideoAnnotation) {
    if (!confirm('¿Desea eliminar esta anotación?')) return;

    isDeleting.value = annotation.id;

    try {
        const result = await videoApi.deleteAnnotation(annotation.id);
        if (result.success) {
            annotationsStore.removeAnnotation(annotation.id);
        }
    } catch (error) {
        console.error('Error deleting annotation:', error);
        alert('Error al eliminar la anotación');
    } finally {
        isDeleting.value = null;
    }
}
</script>

<template>
    <div class="annotation-list">
        <div class="list-header">
            <h6>
                <i class="fas fa-pencil-alt"></i>
                Anotaciones
                <span v-if="annotationsStore.annotationCount > 0" class="count-badge">
                    {{ annotationsStore.annotationCount }}
                </span>
            </h6>
        </div>

        <div v-if="annotationsStore.annotations.length === 0" class="empty-state">
            <i class="fas fa-pencil-alt fa-3x"></i>
            <p>No hay anotaciones</p>
            <small>Active el modo de anotación para crear dibujos en el video</small>
        </div>

        <div v-else class="annotations-container">
            <div
                v-for="annotation in annotationsStore.annotations"
                :key="annotation.id"
                class="annotation-item"
                @click="seekToAnnotation(annotation)"
            >
                <div class="annotation-icon">
                    <i :class="['fas', getAnnotationTypeIcon(annotation.annotation_type)]"></i>
                </div>

                <div class="annotation-content">
                    <div class="annotation-meta">
                        <span class="annotation-type">
                            {{ getAnnotationTypeLabel(annotation.annotation_type) }}
                        </span>
                        <span class="annotation-timestamp">
                            {{ formatTime(annotation.timestamp) }}
                        </span>
                    </div>

                    <div v-if="annotation.user" class="annotation-user">
                        <i class="fas fa-user"></i>
                        {{ annotation.user.name }}
                    </div>

                    <div class="annotation-date">
                        <i class="far fa-clock"></i>
                        {{ new Date(annotation.created_at).toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) }}
                    </div>
                </div>

                <button
                    class="delete-btn"
                    :disabled="isDeleting === annotation.id"
                    @click.stop="deleteAnnotation(annotation)"
                    title="Eliminar anotación"
                >
                    <i :class="isDeleting === annotation.id ? 'fas fa-spinner fa-spin' : 'fas fa-trash'"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.annotation-list {
    display: flex;
    flex-direction: column;
    height: 100%;
    background: #1a1a1a;
}

.list-header {
    padding: 16px;
    border-bottom: 1px solid #333;
}

.list-header h6 {
    margin: 0;
    color: #00B7B5;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.count-badge {
    background: #00B7B5;
    color: #000;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: auto;
}

.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: #666;
}

.empty-state i {
    margin-bottom: 16px;
    color: #444;
}

.empty-state p {
    margin: 0 0 8px 0;
    font-size: 16px;
    color: #999;
}

.empty-state small {
    font-size: 12px;
    color: #666;
    max-width: 200px;
}

.annotations-container {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.annotation-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #0f0f0f;
    border: 1px solid #333;
    border-radius: 6px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.annotation-item:hover {
    background: #1a1a1a;
    border-color: #00B7B5;
    box-shadow: 0 2px 8px rgba(0, 183, 181, 0.2);
}

.annotation-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #00B7B5;
    color: #000;
    border-radius: 50%;
    font-size: 14px;
    flex-shrink: 0;
}

.annotation-content {
    flex: 1;
    min-width: 0;
}

.annotation-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}

.annotation-type {
    font-size: 13px;
    font-weight: 600;
    color: #fff;
}

.annotation-timestamp {
    font-size: 12px;
    color: #00B7B5;
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

.annotation-user,
.annotation-date {
    font-size: 11px;
    color: #999;
    display: flex;
    align-items: center;
    gap: 4px;
}

.annotation-user {
    margin-bottom: 2px;
}

.annotation-user i,
.annotation-date i {
    width: 12px;
    text-align: center;
}

.delete-btn {
    background: transparent;
    border: none;
    color: #dc3545;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}

.delete-btn:hover:not(:disabled) {
    background: rgba(220, 53, 69, 0.2);
    color: #ff4757;
}

.delete-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Scrollbar styles */
.annotations-container::-webkit-scrollbar {
    width: 6px;
}

.annotations-container::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.annotations-container::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 3px;
}

.annotations-container::-webkit-scrollbar-thumb:hover {
    background: #00B7B5;
}
</style>
