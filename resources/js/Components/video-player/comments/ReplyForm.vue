<script setup lang="ts">
import { ref } from 'vue';
import { useCommentsStore } from '@/stores/commentsStore';
import { useToast } from '@/composables/useToast';
import MentionInput from './MentionInput.vue';
import type { User } from '@/types/video-player';

const props = defineProps<{
    videoId: number;
    parentId: number;
    allUsers: Pick<User, 'id' | 'name' | 'role'>[];
}>();

const emit = defineEmits<{
    submitted: [];
    cancel: [];
}>();

const commentsStore = useCommentsStore();
const toast = useToast();
const replyText = ref('');

async function submit() {
    if (!replyText.value.trim()) {
        toast.error('Por favor escribe una respuesta');
        return;
    }

    const result = await commentsStore.addReply(
        props.videoId,
        props.parentId,
        replyText.value.trim(),
    );

    if (result) {
        replyText.value = '';
        toast.success('Respuesta agregada');
        emit('submitted');
    } else {
        toast.error('Error al enviar la respuesta');
    }
}
</script>

<template>
    <div class="reply-form mt-2">
        <MentionInput
            v-model="replyText"
            :users="allUsers"
            placeholder="Escribe tu respuesta..."
            :rows="2"
        />
        <div class="d-flex gap-2 mt-2">
            <button
                class="btn btn-rugby btn-sm"
                :disabled="commentsStore.isSubmitting"
                @click="submit"
            >
                <i v-if="commentsStore.isSubmitting" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-reply"></i>
                Responder
            </button>
            <button
                class="btn btn-sm btn-secondary"
                @click="$emit('cancel')"
            >
                Cancelar
            </button>
        </div>
    </div>
</template>

<style scoped>
.reply-form {
    background: rgba(0, 84, 97, 0.1);
    padding: 8px;
    border-radius: 6px;
}

.btn-rugby {
    background-color: #005461;
    border-color: #005461;
    color: white;
    font-size: 12px;
}
.btn-rugby:hover {
    background-color: #003d4a;
    color: white;
}

.btn-secondary {
    font-size: 12px;
}

.gap-2 {
    gap: 8px;
}
</style>
