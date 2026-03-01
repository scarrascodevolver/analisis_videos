<script setup lang="ts">
defineProps<{
    visible: boolean;
}>();
</script>

<template>
    <Transition name="fade-slide">
        <div v-if="visible" class="area-tip">
            <i class="fas fa-lightbulb"></i>
            Clic para agregar puntos.
            <strong>Doble clic</strong> o <strong>Enter</strong> para cerrar.
        </div>
    </Transition>
</template>

<style scoped>
.area-tip {
    position: absolute;
    top: 10px;
    left: 210px;
    background: #000;
    color: #fff;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    z-index: 30;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.6);
    border: 2px solid #FFC300;
}

.area-tip i {
    color: #ffc107;
    margin-right: 10px;
    font-size: 18px;
}

.area-tip strong {
    color: #ffc107;
    font-weight: 800;
    text-decoration: underline;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.fade-slide-leave-to {
    opacity: 0;
}
</style>
