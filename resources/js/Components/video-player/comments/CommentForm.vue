<script setup lang="ts">
import { ref, inject } from 'vue';
import { useVideoStore, formatTime } from '@/stores/videoStore';
import { useCommentsStore } from '@/stores/commentsStore';
import { useToast } from '@/composables/useToast';
import MentionInput from './MentionInput.vue';
import type { User, CommentCategory, CommentPriority } from '@/types/video-player';

const props = defineProps<{
    videoId: number;
    allUsers: Pick<User, 'id' | 'name' | 'role'>[];
}>();

const videoStore = useVideoStore();
const commentsStore = useCommentsStore();
const toast = useToast();

const commentText = ref('');
const category = ref<CommentCategory>('tecnico');
const priority = ref<CommentPriority>('media');
const timestampSeconds = ref(0);

const categories: { value: CommentCategory; label: string }[] = [
    { value: 'tecnico', label: 'Técnico' },
    { value: 'tactico', label: 'Táctico' },
    { value: 'fisico', label: 'Físico' },
    { value: 'mental', label: 'Mental' },
];

const priorities: { value: CommentPriority; label: string }[] = [
    { value: 'baja', label: 'Baja' },
    { value: 'media', label: 'Media' },
    { value: 'alta', label: 'Alta' },
    { value: 'critica', label: 'Crítica' },
];

function setCurrentTimestamp() {
    timestampSeconds.value = Math.floor(videoStore.currentTime);
}

async function submitComment() {
    if (!commentText.value.trim()) {
        toast.error('Por favor escribe un comentario');
        return;
    }

    const result = await commentsStore.addComment(props.videoId, {
        comment: commentText.value.trim(),
        timestamp_seconds: timestampSeconds.value,
        category: category.value,
        priority: priority.value,
    });

    if (result) {
        commentText.value = '';
        timestampSeconds.value = 0;
        category.value = 'tecnico';
        priority.value = 'media';
        toast.success('Comentario agregado exitosamente');
    } else {
        toast.error('Error al enviar el comentario');
    }
}

// Called externally when "Comentar aquí" button is clicked
function focusWithTimestamp() {
    setCurrentTimestamp();
}

defineExpose({ focusWithTimestamp });
</script>

<template>
    <form @submit.prevent="submitComment" class="comment-form p-3">
        <!-- Timestamp -->
        <div class="mb-2">
            <div class="d-flex align-items-center gap-2">
                <button
                    type="button"
                    class="btn btn-sm btn-primary timestamp-set-btn"
                    @click="setCurrentTimestamp"
                    title="Actualizar al tiempo actual del video"
                >
                    <i class="fas fa-clock"></i>
                    {{ formatTime(timestampSeconds) }}
                </button>
                <small class="text-muted ml-2">Se actualiza automáticamente al escribir</small>
            </div>
        </div>

        <!-- Comment Text with Mentions -->
        <MentionInput
            v-model="commentText"
            :users="allUsers"
            placeholder="Escribe tu comentario... Usa @nombre para mencionar"
            :rows="3"
            @focus="setCurrentTimestamp"
        />

        <!-- Category and Priority -->
        <div class="d-flex gap-2 mt-2">
            <select v-model="category" class="form-control form-control-sm" style="flex: 1;">
                <option v-for="c in categories" :key="c.value" :value="c.value">
                    {{ c.label }}
                </option>
            </select>
            <select v-model="priority" class="form-control form-control-sm" style="flex: 1;">
                <option v-for="p in priorities" :key="p.value" :value="p.value">
                    {{ p.label }}
                </option>
            </select>
        </div>

        <!-- Submit -->
        <button
            type="submit"
            class="btn btn-rugby btn-sm btn-block mt-2"
            :disabled="commentsStore.isSubmitting"
        >
            <i v-if="commentsStore.isSubmitting" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-comment"></i>
            {{ commentsStore.isSubmitting ? 'Enviando...' : 'Agregar' }}
        </button>
    </form>
</template>

<style scoped>
.comment-form {
    background: #1a1a1a;
    border-bottom: 1px solid #333;
}

.timestamp-set-btn {
    background: #005461;
    border: none;
    font-family: monospace;
    font-size: 12px;
}

.timestamp-set-btn:hover {
    background: #00B7B5;
}

.form-control-sm {
    background-color: #003d4a;
    border-color: #018790;
    color: #fff;
    font-size: 12px;
}

.form-control-sm:focus {
    background-color: #005461;
    border-color: #00B7B5;
    color: #fff;
}

.form-control-sm option {
    background-color: #003d4a;
    color: #fff;
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
.btn-rugby:disabled {
    opacity: 0.6;
}

.gap-2 {
    gap: 8px;
}
</style>
