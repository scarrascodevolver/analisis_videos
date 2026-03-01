<script setup lang="ts">
defineProps<{
    canMoveUp: boolean;
    canMoveDown: boolean;
}>();

const emit = defineEmits<{
    moveUp: [];
    moveDown: [];
}>();
</script>

<template>
    <div class="panel-order-wrapper">
        <slot />
        <div class="panel-order-overlay">
            <button
                class="panel-order-btn"
                :class="{ disabled: !canMoveUp }"
                :disabled="!canMoveUp"
                title="Mover panel arriba"
                @click.stop="canMoveUp && emit('moveUp')"
            >
                <i class="fas fa-chevron-up"></i>
            </button>
            <button
                class="panel-order-btn"
                :class="{ disabled: !canMoveDown }"
                :disabled="!canMoveDown"
                title="Mover panel abajo"
                @click.stop="canMoveDown && emit('moveDown')"
            >
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
</template>

<style scoped>
.panel-order-wrapper {
    position: relative;
}

.panel-order-overlay {
    position: absolute;
    top: 0;
    right: 30px;
    display: flex;
    flex-direction: row;
    gap: 2px;
    z-index: 10;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.panel-order-wrapper:hover .panel-order-overlay {
    opacity: 1;
}

.panel-order-btn {
    pointer-events: all;
    background: rgba(255, 195, 0, 0.12);
    border: 1px solid #FFC300;
    color: #FFC300;
    padding: 4px 8px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
    line-height: 1;
    transition: background 0.15s ease, color 0.15s ease;
}

.panel-order-btn:hover:not(.disabled) {
    background: #FFC300;
    color: #0f0f0f;
}

.panel-order-btn.disabled {
    opacity: 0.2;
    cursor: not-allowed;
}
</style>
