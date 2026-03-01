<template>
    <Teleport to="body">
        <transition name="fade">
            <div v-if="show" class="modal-overlay" @click.self="handleClose">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <!-- Header -->
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Estadísticas del Video
                            </h5>
                            <button type="button" class="close" @click="handleClose">
                                <span>&times;</span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <!-- Loading State -->
                            <div v-if="isLoading" class="loading-state">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-3">Cargando estadísticas...</p>
                            </div>

                            <!-- Stats Content -->
                            <div v-else-if="stats">
                                <!-- Summary Cards -->
                                <div class="stats-summary">
                                    <div class="stat-card">
                                        <i class="fas fa-eye stat-icon"></i>
                                        <div class="stat-value">{{ stats.total_starts }}</div>
                                        <div class="stat-label">Total Visualizaciones</div>
                                    </div>
                                    <div class="stat-card">
                                        <i class="fas fa-users stat-icon"></i>
                                        <div class="stat-value">{{ stats.unique_viewers }}</div>
                                        <div class="stat-label">Usuarios Únicos</div>
                                    </div>
                                    <div class="stat-card">
                                        <i class="fas fa-clock stat-icon"></i>
                                        <div class="stat-value">{{ formatTime(stats.average_watch_time) }}</div>
                                        <div class="stat-label">Tiempo Promedio</div>
                                    </div>
                                </div>

                                <!-- Views Table -->
                                <div v-if="stats.views.length > 0" class="views-section">
                                    <h6 class="section-title">
                                        <i class="fas fa-list mr-2"></i>
                                        Detalle de Visualizaciones
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-dark table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>% Visto</th>
                                                    <th>Tiempo Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="view in stats.views" :key="view.user_id">
                                                    <td>
                                                        <i class="fas fa-user mr-2 text-muted"></i>
                                                        {{ view.user?.name || 'Usuario desconocido' }}
                                                    </td>
                                                    <td>
                                                        <div class="progress-wrapper">
                                                            <div class="progress">
                                                                <div
                                                                    class="progress-bar"
                                                                    :class="getProgressBarClass(view.watched_percentage)"
                                                                    :style="{ width: view.watched_percentage + '%' }"
                                                                ></div>
                                                            </div>
                                                            <span class="progress-label">{{ (typeof view.watched_percentage === 'number' && isFinite(view.watched_percentage) ? view.watched_percentage : 0).toFixed(1) }}%</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        {{ formatTime(view.total_watch_time) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div v-else class="empty-state">
                                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                    <p class="text-muted">Este video aún no tiene visualizaciones</p>
                                </div>
                            </div>

                            <!-- Error State -->
                            <div v-else-if="errorMessage" class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                {{ errorMessage }}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="handleClose">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useVideoApi } from '@/composables/useVideoApi';
import { formatTime } from '@/stores/videoStore';
import type { VideoStats } from '@/types/video-player';

interface Props {
    show: boolean;
    videoId: number;
}

interface Emits {
    (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const stats = ref<VideoStats | null>(null);
const isLoading = ref(false);
const errorMessage = ref('');

// Watch for show prop to load stats
watch(
    () => props.show,
    async (isShowing) => {
        if (isShowing && !stats.value) {
            await loadStats();
        }
    },
    { immediate: true }
);

// Methods
async function loadStats() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const api = useVideoApi(props.videoId);
        stats.value = await api.getStats();
    } catch (error: any) {
        errorMessage.value = error.message || 'Error al cargar las estadísticas';
        console.error('Error loading stats:', error);
    } finally {
        isLoading.value = false;
    }
}

function handleClose() {
    emit('close');
}

function getProgressBarClass(percentage: number): string {
    if (percentage >= 90) return 'bg-success';
    if (percentage >= 50) return 'bg-info';
    if (percentage >= 25) return 'bg-warning';
    return 'bg-danger';
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.modal-dialog {
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-lg {
    max-width: 900px;
}

.modal-content {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 6px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #333;
}

.modal-title {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.close {
    background: none;
    border: none;
    color: #ccc;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close:hover {
    color: #fff;
}

.modal-body {
    padding: 1.25rem;
    max-height: 70vh;
    overflow-y: auto;
}

.loading-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #ccc;
}

.stats-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #252525;
    border: 1px solid #333;
    border-radius: 6px;
    padding: 1.5rem;
    text-align: center;
}

.stat-icon {
    font-size: 2rem;
    color: var(--color-accent);
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #999;
}


.views-section {
    margin-top: 1.5rem;
}

.section-title {
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.table-responsive {
    overflow-x: auto;
}

.table {
    margin-bottom: 0;
    color: #ccc;
}

.table-dark {
    background-color: #0f0f0f;
}

.table-dark thead th {
    background-color: #252525;
    border-bottom: 2px solid #444;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.75rem;
}

.table-dark tbody td {
    border-top: 1px solid #333;
    padding: 0.75rem;
}

.table-hover tbody tr:hover {
    background-color: #252525;
}

.progress-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.progress {
    flex: 1;
    height: 20px;
    background-color: #333;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    transition: width 0.3s ease;
}

.bg-success {
    background-color: #28a745;
}

.bg-info {
    background-color: #17a2b8;
}

.bg-warning {
    background-color: #ffc107;
}

.bg-danger {
    background-color: #dc3545;
}

.progress-label {
    font-size: 0.875rem;
    color: #ccc;
    min-width: 45px;
    text-align: right;
}

.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    border-radius: 3px;
}

.badge-success {
    background-color: #28a745;
    color: #fff;
}

.badge-secondary {
    background-color: #6c757d;
    color: #fff;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #333;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-secondary {
    background-color: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.text-muted {
    color: #999 !important;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.15);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #f8d7da;
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
