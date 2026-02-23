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
    background: rgba(15, 15, 15, 0.85);
    border: 1px solid #444;
    color: #555;
    padding: 2px 5px;
    border-radius: 2px;
    cursor: pointer;
    font-size: 9px;
    line-height: 1;
    transition: color 0.15s ease, border-color 0.15s ease;
}

.panel-order-btn:hover:not(.disabled) {
    color: #00B7B5;
    border-color: #00B7B5;
}

.panel-order-btn.disabled {
    opacity: 0.25;
    cursor: not-allowed;
}
</style>
