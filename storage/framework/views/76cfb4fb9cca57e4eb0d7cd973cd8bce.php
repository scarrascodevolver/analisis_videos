<?php $__env->startSection('page_title', 'Videos del Equipo'); ?>

<?php $__env->startSection('breadcrumbs'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('videos.index')); ?>"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Videos del Equipo</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('main_content'); ?>
    <!-- Filters (only for non-players) -->
    <?php if(auth()->user()->role !== 'jugador'): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter"></i> Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?php echo e(route('videos.index')); ?>" class="row" id="filter-form">
                        <div class="col-md-3 mb-2">
                            <input type="text" name="search" id="search-input" class="form-control" placeholder="Buscar por título..." value="<?php echo e(request('search')); ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <select name="rugby_situation" id="situation-select" class="form-control">
                                <option value="">Situación</option>
                                <?php $__currentLoopData = $rugbySituations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoryName => $situations): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <optgroup label="<?php echo e($categoryName); ?>">
                                        <?php $__currentLoopData = $situations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $situation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($situation->id); ?>" <?php echo e(request('rugby_situation') == $situation->id ? 'selected' : ''); ?>>
                                                <?php echo e($situation->name); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </optgroup>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <?php if(in_array(auth()->user()->role, ['analista', 'entrenador'])): ?>
                        <div class="col-md-2 mb-2">
                            <select name="category" id="category-select" class="form-control">
                                <option value="">Categoría</option>
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($category->id); ?>" <?php echo e(request('category') == $category->id ? 'selected' : ''); ?>>
                                        <?php echo e($category->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-2 mb-2">
                            <input type="text" name="team" id="team-input" class="form-control"
                                   placeholder="Buscar equipo..."
                                   value="<?php echo e(request('team')); ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <a href="<?php echo e(route('videos.index')); ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Videos List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-video"></i>
                        Lista de Videos
                    </h3>
                    <div class="card-tools">
                        <?php if(in_array(auth()->user()->role, ['analista', 'entrenador'])): ?>
                            <a href="<?php echo e(route('videos.create')); ?>" class="btn btn-rugby">
                                <i class="fas fa-plus"></i> Subir Video
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(isset($videos) && $videos->count() > 0): ?>
                        <div class="row">
                            <?php $__currentLoopData = $videos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $video): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-lg-4 col-md-6 col-sm-12 mb-2" id="video-card-<?php echo e($video->id); ?>">
                                    <div class="card video-card h-100">
                                        <!-- Video Thumbnail -->
                                        <div class="card-img-top video-thumbnail-container"
                                             style="height: 120px; overflow: hidden; position: relative; cursor: pointer;"
                                             onclick="window.location.href='<?php echo e(route('videos.show', $video)); ?>'">

                                            <video class="w-100 h-100"
                                                   style="object-fit: cover;"
                                                   preload="metadata"
                                                   muted>
                                                <source src="<?php echo e(route('videos.stream', $video)); ?>#t=5" type="video/mp4">
                                            </video>
                                        </div>
                                        <div class="card-body py-1 px-2">
                                            <h6 class="card-title mb-1 video-title"><?php echo e($video->title); ?></h6>
                                            <p class="card-text mb-1">
                                                <small class="text-muted">
                                                    <?php echo e($video->analyzed_team_name); ?>

                                                    <?php if($video->rival_name): ?>
                                                        vs <?php echo e($video->rival_name); ?>

                                                    <?php endif; ?>
                                                </small>
                                            </p>
                                            <div class="mb-1">
                                                <span class="badge badge-rugby badge-sm"><?php echo e($video->category->name ?? 'Sin categoría'); ?></span>
                                                <?php if($video->division && $video->category && $video->category->name === 'Adultas'): ?>
                                                    <span class="badge badge-secondary badge-sm ml-1">
                                                        <?php echo e(ucfirst($video->division)); ?>

                                                    </span>
                                                <?php endif; ?>
                                                <?php if($video->rugbySituation): ?>
                                                    <span class="badge badge-rugby-light badge-sm ml-1">
                                                        <?php echo e($video->rugbySituation->name); ?>

                                                    </span>
                                                <?php endif; ?>

                                                
                                                <?php if($video->visibility_type && $video->visibility_type !== 'public'): ?>
                                                    <?php if($video->visibility_type === 'forwards'): ?>
                                                        <span class="badge badge-secondary badge-sm ml-1" title="Solo visible para Forwards">
                                                            <i class="fas fa-shield-alt"></i> Forwards
                                                        </span>
                                                    <?php elseif($video->visibility_type === 'backs'): ?>
                                                        <span class="badge badge-info badge-sm ml-1" title="Solo visible para Backs">
                                                            <i class="fas fa-running"></i> Backs
                                                        </span>
                                                    <?php elseif($video->visibility_type === 'specific'): ?>
                                                        <span class="badge badge-dark badge-sm ml-1" title="Asignado a jugadores específicos">
                                                            <i class="fas fa-user-check"></i> Específico
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <p class="card-text mb-1">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> <?php echo e($video->match_date->format('d/m/Y')); ?>

                                                </small>
                                            </p>
                                            <p class="card-text mb-0">
                                                <small class="text-muted">
                                                    <i class="fas fa-eye"></i> <?php echo e($video->view_count); ?> vistas
                                                </small>
                                            </p>
                                        </div>
                                        <div class="card-footer">
                                            <a href="<?php echo e(route('videos.show', $video)); ?>" class="btn btn-rugby btn-sm">
                                                <i class="fas fa-play"></i> Ver Video
                                            </a>
                                            <?php if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador' || auth()->id() === $video->uploaded_by): ?>
                                                <a href="<?php echo e(route('videos.edit', $video)); ?>" class="btn btn-rugby-light btn-sm">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            <?php endif; ?>
                                            <?php if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador'): ?>
                                                <button type="button" class="btn btn-rugby-dark btn-sm" data-toggle="modal" data-target="#deleteModal-<?php echo e($video->id); ?>">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <!-- Modales de Confirmación para Eliminar Videos -->
                        <?php if(auth()->user()->role === 'analista' || auth()->user()->role === 'entrenador'): ?>
                            <?php $__currentLoopData = $videos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $video): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="modal fade" id="deleteModal-<?php echo e($video->id); ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel-<?php echo e($video->id); ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="deleteModalLabel-<?php echo e($video->id); ?>">
                                                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                                                </div>
                                                <h5 class="text-center mb-3">¿Estás seguro de eliminar este video?</h5>
                                                <div class="alert alert-warning">
                                                    <strong>Video:</strong> <?php echo e($video->title); ?><br>
                                                    <strong>Archivo:</strong> <?php echo e($video->file_name); ?><br>
                                                    <strong>Tamaño:</strong> <?php echo e(number_format($video->file_size / 1048576, 2)); ?> MB<br>
                                                    <strong>Fecha:</strong> <?php echo e($video->match_date->format('d/m/Y')); ?>

                                                </div>
                                                <p class="text-danger text-center">
                                                    <strong>⚠️ Esta acción no se puede deshacer.</strong><br>
                                                    Se eliminará el video, todos sus comentarios y asignaciones.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-rugby-outline" data-dismiss="modal">
                                                    <i class="fas fa-times"></i> Cancelar
                                                </button>
                                                <button type="button" class="btn btn-rugby-dark btn-delete-video"
                                                        data-video-id="<?php echo e($video->id); ?>"
                                                        data-url="<?php echo e(route('videos.destroy', $video)); ?>">
                                                    <i class="fas fa-trash"></i> Eliminar Video
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>

                        <?php if(method_exists($videos, 'links')): ?>
                            <div class="d-flex justify-content-center">
                                <?php echo e($videos->links('custom.pagination')); ?>

                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-video fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay videos disponibles</h5>
                            <?php if(auth()->user()->role === 'analista'): ?>
                                <p class="text-muted">Comienza subiendo tu primer video de análisis</p>
                                <a href="<?php echo e(route('videos.create')); ?>" class="btn btn-rugby">
                                    <i class="fas fa-plus"></i> Subir Primer Video
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<style>
/* Rugby badges */
.badge-rugby {
    background: var(--color-primary, #005461);
    color: white;
    font-size: 0.875em;
    font-weight: 500;
}

.badge-rugby-light {
    background: var(--color-accent, #4B9DA9);
    color: white;
    font-size: 0.875em;
    font-weight: 500;
}

.badge-sm {
    font-size: 0.75em;
    padding: 0.25rem 0.5rem;
}

/* Video title overflow fix */
.video-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
    max-height: 2.4em;
    font-size: 0.9rem;
}

/* Rugby button variations */
.btn-rugby-light {
    background: var(--color-accent, #4B9DA9);
    border: none;
    color: white;
    border-radius: 6px;
    font-weight: 500;
}

.btn-rugby-light:hover {
    background: var(--color-primary-hover, #003d4a);
    color: white;
}

.btn-rugby-dark {
    background: var(--color-primary-hover, #003d4a);
    border: none;
    color: white;
    border-radius: 6px;
    font-weight: 500;
}

.btn-rugby-dark:hover {
    background: var(--color-primary, #005461);
    color: white;
}

.btn-rugby-outline {
    background: transparent;
    border: 2px solid var(--color-primary, #005461);
    color: var(--color-primary, #005461);
    border-radius: 6px;
    font-weight: 500;
}

.btn-rugby-outline:hover {
    background: var(--color-primary, #005461);
    border-color: var(--color-primary, #005461);
    color: white;
}

/* Video card improvements */
.video-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.video-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.video-card .card-img-top {
    transition: all 0.3s ease;
}

.video-card .card-img-top:hover {
    transform: scale(1.02);
}

.video-card .card-img-top img {
    transition: opacity 0.3s ease;
}

.video-card .card-img-top:hover img {
    opacity: 0.9;
}

/* Rugby thumbnail placeholder */
.rugby-thumbnail {
    background: var(--color-primary, #005461);
    position: relative;
}

.play-button-circle {
    width: 50px;
    height: 50px;
    background: var(--color-accent, #4B9DA9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content-center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.rugby-thumbnail:hover .play-button-circle {
    transform: scale(1.1);
    background: var(--color-primary-hover, #003d4a);
}

</style>

<!-- Toast Container -->
<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

<script>

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filter-form');
    if (form) {
        let filterTimeout;
        function autoFilter() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => form.submit(), 500);
        }

        const on = (id, evt, cb) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener(evt, cb);
        };

        on('search-input', 'input', autoFilter);
        on('situation-select', 'change', () => form.submit());
        on('category-select', 'change', () => form.submit());
        on('team-input', 'input', autoFilter);
    }

    // AJAX Delete Video
    document.querySelectorAll('.btn-delete-video').forEach(btn => {
        btn.addEventListener('click', function() {
            const videoId = this.dataset.videoId;
            const url = this.dataset.url;
            const button = this;

            // Disable button and show loading
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Close modal
                $(`#deleteModal-${videoId}`).modal('hide');

                if (data.success) {
                    // Show success toast
                    showToast(data.message, 'success');

                    // Remove video card with animation
                    const card = document.getElementById(`video-card-${videoId}`);
                    if (card) {
                        card.style.transition = 'opacity 0.3s, transform 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        setTimeout(() => card.remove(), 300);
                    }
                } else {
                    showToast(data.message || 'Error al eliminar', 'error');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash"></i> Eliminar Video';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al eliminar el video', 'error');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash"></i> Eliminar Video';
                $(`#deleteModal-${videoId}`).modal('hide');
            });
        });
    });
});

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const bgColor = type === 'success' ? '#00B7B5' : '#dc3545';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';

    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.style.cssText = `
        background: ${bgColor};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease;
        max-width: 350px;
    `;
    toast.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;

    container.appendChild(toast);

    // Auto remove after 4 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
</script>

<style>
@keyframes slideIn {
    from { opacity: 0; transform: translateX(100px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes slideOut {
    from { opacity: 1; transform: translateX(0); }
    to { opacity: 0; transform: translateX(100px); }
}
</style>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\rugbyhub\resources\views/videos/index.blade.php ENDPATH**/ ?>