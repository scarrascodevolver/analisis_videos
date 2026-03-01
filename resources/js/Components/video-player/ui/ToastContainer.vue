<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toasts, removeToast } = useToast();

function iconClass(type: string): string {
    switch (type) {
        case 'success': return 'fas fa-check-circle';
        case 'error': return 'fas fa-exclamation-circle';
        case 'warning': return 'fas fa-exclamation-triangle';
        default: return 'fas fa-info-circle';
    }
}

function bgColor(type: string): string {
    switch (type) {
        case 'success': return COLOR_ACCENT;
        case 'error': return '#dc3545';
        case 'warning': return '#ffc107';
        default: return '#005461';
    }
}

function textColor(type: string): string {
    return type === 'warning' ? '#000' : '#fff';
}
</script>

<template>
    <Teleport to="body">
        <div class="toast-container">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="toast-item"
                    :style="{ background: bgColor(toast.type), color: textColor(toast.type) }"
                    @click="removeToast(toast.id)"
                >
                    <i :class="iconClass(toast.type)" class="mr-2"></i>
                    <span>{{ toast.message }}</span>
                    <button class="toast-close" :style="{ color: textColor(toast.type) }">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-container {
    position: fixed;
    top: 60px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-width: 400px;
}

.toast-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    cursor: pointer;
    font-size: 14px;
    min-width: 280px;
}

.toast-close {
    background: none;
    border: none;
    margin-left: auto;
    padding-left: 12px;
    cursor: pointer;
    opacity: 0.7;
}

.toast-close:hover {
    opacity: 1;
}

.toast-enter-active {
    transition: all 0.3s ease;
}

.toast-leave-active {
    transition: all 0.2s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(40px);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(40px);
}
</style>
