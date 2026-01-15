
<?php if($paginator->hasPages()): ?>
    <nav aria-label="Navegación de páginas">
        <ul class="pagination pagination-sm justify-content-center">
            
            <?php if($paginator->onFirstPage()): ?>
                <li class="page-item disabled">
                    <span class="page-link">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </span>
                </li>
            <?php else: ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                </li>
            <?php endif; ?>

            
            <?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                
                <?php if(is_string($element)): ?>
                    <li class="page-item disabled"><span class="page-link"><?php echo e($element); ?></span></li>
                <?php endif; ?>

                
                <?php if(is_array($element)): ?>
                    <?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($page == $paginator->currentPage()): ?>
                            <li class="page-item active">
                                <span class="page-link rugby-active"><?php echo e($page); ?></span>
                            </li>
                        <?php else: ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo e($url); ?>"><?php echo e($page); ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            
            <?php if($paginator->hasMorePages()): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </span>
                </li>
            <?php endif; ?>
        </ul>

        
        <div class="text-center mt-2">
            <small class="text-muted">
                Mostrando <?php echo e($paginator->firstItem()); ?> a <?php echo e($paginator->lastItem()); ?>

                de <?php echo e($paginator->total()); ?> resultados
            </small>
        </div>
    </nav>

    <style>
    /* Rugby pagination styles */
    .pagination .page-link {
        color: var(--color-primary, #005461);
        border-color: #dee2e6;
    }

    .pagination .page-item.active .page-link.rugby-active {
        background-color: var(--color-primary, #005461);
        border-color: var(--color-primary, #005461);
        color: white;
    }

    .pagination .page-link:hover {
        color: var(--color-accent, #4B9DA9);
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }
    </style>
<?php endif; ?><?php /**PATH C:\xampp\htdocs\rugbyhub\resources\views/custom/pagination.blade.php ENDPATH**/ ?>