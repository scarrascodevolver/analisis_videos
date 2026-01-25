
<div class="reply comment-item border-left border-primary pl-3 mb-2" data-reply-id="<?php echo e($reply->id); ?>">
    <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
            <p class="mb-1"><?php echo e($reply->comment); ?></p>
            <small class="text-muted">
                <i class="fas fa-user"></i> <?php echo e($reply->user->name); ?>

                <span class="badge badge-sm badge-<?php echo e($reply->user->role === 'analista' ? 'primary' :
                    ($reply->user->role === 'entrenador' ? 'success' : 'info')); ?>">
                    <?php echo e(ucfirst($reply->user->role)); ?>

                </span>
                - <?php echo e($reply->created_at->diffForHumans()); ?>

            </small>
            <!-- Botón para responder a esta respuesta -->
            <button class="btn btn-sm btn-link text-rugby p-0 ml-2 reply-btn"
                    data-comment-id="<?php echo e($reply->id); ?>"
                    title="Responder a esta respuesta">
                <i class="fas fa-reply"></i> Responder
            </button>

            <!-- Badges de menciones -->
            <?php if($reply->mentionedUsers && $reply->mentionedUsers->count() > 0): ?>
                <div class="mt-2">
                    <span class="badge badge-light border">
                        <i class="fas fa-at text-primary"></i>
                        Menciona a:
                        <?php $__currentLoopData = $reply->mentionedUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mentionedUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="badge badge-<?php echo e($mentionedUser->role === 'jugador' ? 'info' :
                                ($mentionedUser->role === 'entrenador' ? 'success' : 'primary')); ?> ml-1">
                                <?php echo e($mentionedUser->name); ?>

                            </span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php if($reply->user_id === auth()->id()): ?>
            <button class="btn btn-sm btn-outline-danger delete-comment-btn"
                    data-comment-id="<?php echo e($reply->id); ?>"
                    title="Eliminar respuesta">
                <i class="fas fa-trash"></i>
            </button>
        <?php endif; ?>
    </div>

    <!-- Reply Form para respuestas anidadas -->
    <div class="reply-form mt-2" id="replyForm<?php echo e($reply->id); ?>" style="display: none;">
        <form class="reply-form-submit" data-comment-id="<?php echo e($reply->id); ?>" data-video-id="<?php echo e($video->id); ?>">
            <?php echo csrf_field(); ?>
            <textarea class="form-control form-control-sm mb-2" name="reply_comment" rows="2"
                      placeholder="Escribe tu respuesta..." required></textarea>
            <button class="btn btn-rugby btn-sm" type="submit">
                <i class="fas fa-reply"></i> Responder
            </button>
        </form>
    </div>

    <!-- Respuestas anidadas recursivas -->
    <?php if($reply->replies && $reply->replies->count() > 0): ?>
        <div class="replies ml-3 mt-2">
            <?php $__currentLoopData = $reply->replies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nestedReply): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('videos.partials.reply', ['reply' => $nestedReply, 'video' => $video], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\xampp\htdocs\rugbyhub\resources\views/videos/partials/reply.blade.php ENDPATH**/ ?>