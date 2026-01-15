<?php $__env->startSection('page_title', 'Mis Videos'); ?>

<?php $__env->startSection('breadcrumbs'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('videos.index')); ?>"><i class="fas fa-home"></i></a></li>
    <li class="breadcrumb-item active">Mis Videos</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('main_content'); ?>

    <!-- Assigned Videos -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-circle"></i>
                Videos Asignados para <?php echo e(auth()->user()->name); ?>

            </h3>
        </div>
        <div class="card-body">
            <?php if($assignedVideos->count() > 0): ?>
                <div class="row">
                    <?php $__currentLoopData = $assignedVideos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card video-card h-100">
                                
                                <!-- Video Thumbnail -->
                                <div class="card-img-top video-thumbnail-container"
                                     style="height: 200px; overflow: hidden; background: #f8f9fa; position: relative;"
                                     data-video-url="<?php echo e(route('videos.stream', $assignment->video)); ?>"
                                     data-video-id="<?php echo e($assignment->video->id); ?>"
                                     onclick="window.location.href='<?php echo e(route('assignments.show', $assignment)); ?>'">

                                    <!-- Video Thumbnail using native poster -->
                                    <video class="w-100 h-100"
                                           style="object-fit: cover;"
                                           preload="metadata"
                                           muted>
                                        <source src="<?php echo e(route('videos.stream', $assignment->video)); ?>#t=5" type="video/mp4">
                                    </video>

                                </div>

                                <!-- Video Info -->
                                <div class="card-body p-3">
                                    <h6 class="card-title font-weight-bold"><?php echo e($assignment->video->title); ?></h6>
                                    
                                    <!-- Rugby Situation Badge -->
                                    <?php if($assignment->video->rugbySituation): ?>
                                        <span class="badge badge-rugby-light mb-2">
                                            <?php echo e($assignment->video->rugbySituation->name); ?>

                                        </span>
                                    <?php endif; ?>


                                    <!-- Teams -->
                                    <p class="card-text small text-muted mb-2">
                                        <i class="fas fa-users"></i>
                                        <?php echo e($assignment->video->analyzed_team_name); ?>

                                        <?php if($assignment->video->rival_team_name): ?>
                                            vs <?php echo e($assignment->video->rival_team_name); ?>

                                        <?php endif; ?>
                                    </p>

                                    <!-- Assigned By -->
                                    <p class="card-text small text-muted mb-2">
                                        <i class="fas fa-user"></i>
                                        Asignado por: <?php echo e($assignment->assignedBy->name); ?>

                                    </p>


                                    <!-- Notes -->
                                    <?php if($assignment->notes): ?>
                                        <div class="alert alert-info alert-sm p-2 mt-2">
                                            <small><strong>Instrucciones:</strong><br><?php echo e($assignment->notes); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    <?php echo e($assignedVideos->links()); ?>

                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-video fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No tienes videos asignados</h4>
                    <p class="text-muted">Los analistas y entrenadores te asignarán videos para análisis aquí.</p>
                    <a href="<?php echo e(route('videos.index')); ?>" class="btn btn-rugby">
                        <i class="fas fa-video"></i> Ver Videos del Equipo
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<style>
    .video-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .video-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .alert-sm {
        font-size: 0.875rem;
    }

    /* Rugby badge light */
    .badge-rugby-light {
        background: var(--color-accent, #4B9DA9);
        color: white;
        font-size: 0.875em;
        font-weight: 500;
    }

    /* Rugby thumbnail placeholder */
    .rugby-thumbnail {
        background: var(--color-primary, #005461);
        position: relative;
        transition: all 0.3s ease;
    }

    .rugby-thumbnail:hover {
        background: var(--color-primary-hover, #003d4a);
    }

    .play-button-circle {
        width: 60px;
        height: 60px;
        background: var(--color-accent, #4B9DA9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
        margin: 0 auto;
    }

    .rugby-thumbnail:hover .play-button-circle {
        transform: scale(1.1);
        background: var(--color-secondary, #018790);
    }

    /* Video thumbnail improvements */
    .video-thumbnail-container {
        transition: all 0.3s ease;
    }

    .video-thumbnail-container:hover {
        transform: scale(1.02);
    }

    .video-thumbnail-img {
        transition: opacity 0.3s ease;
    }

    .video-thumbnail-container:hover .video-thumbnail-img {
        opacity: 0.9;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎬 Iniciando sistema de thumbnails nativo en Mis Videos...');

    const videoThumbnails = document.querySelectorAll('.video-thumbnail-container video');

    videoThumbnails.forEach((video, index) => {
        const container = video.closest('.video-thumbnail-container');
        const videoId = container.dataset.videoId;

        console.log(`📹 Configurando thumbnail nativo para video ${videoId}`);

        // Error handler - log only, no fallback needed
        video.addEventListener('error', function() {
            console.log(`❌ Error cargando video ${videoId}`);
        });

        // Success handler
        video.addEventListener('loadedmetadata', function() {
            console.log(`✅ Video ${videoId} cargado correctamente con thumbnail`);
        });
    });
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\rugbyhub\resources\views/my-videos/index.blade.php ENDPATH**/ ?>