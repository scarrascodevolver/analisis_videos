<?php $__env->startSection('main_content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-building text-primary mr-2"></i>
                    Organizaciones
                </h1>
                <p class="text-muted mb-0">Gestión de todas las organizaciones del sistema</p>
            </div>
            <a href="<?php echo e(route('super-admin.organizations.create')); ?>" class="btn btn-success">
                <i class="fas fa-plus mr-1"></i> Nueva Organización
            </a>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i><?php echo e(session('success')); ?>

            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo e(session('error')); ?>

            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 60px;">Logo</th>
                            <th>Nombre</th>
                            <th>Slug</th>
                            <th class="text-center">Usuarios</th>
                            <th class="text-center">Videos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width: 200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $organizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $org): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-center">
                                <?php if($org->logo_path): ?>
                                    <img src="<?php echo e(asset('storage/' . $org->logo_path)); ?>"
                                         alt="<?php echo e($org->name); ?>"
                                         class="img-thumbnail"
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-building fa-2x text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo e($org->name); ?></strong>
                                <br>
                                <small class="text-muted">Creada: <?php echo e($org->created_at->format('d/m/Y')); ?></small>
                            </td>
                            <td><code><?php echo e($org->slug); ?></code></td>
                            <td class="text-center">
                                <span class="badge badge-primary badge-pill"><?php echo e($org->users_count); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info badge-pill"><?php echo e($org->videos_count); ?></span>
                            </td>
                            <td class="text-center">
                                <?php if($org->is_active): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i> Activa
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">
                                        <i class="fas fa-pause"></i> Inactiva
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo e(route('super-admin.organizations.edit', $org)); ?>"
                                       class="btn btn-outline-primary"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo e(route('super-admin.organizations.assign-admin', $org)); ?>"
                                       class="btn btn-outline-success"
                                       title="Gestionar Usuarios">
                                        <i class="fas fa-users-cog"></i>
                                    </a>
                                    <?php if($org->users_count == 0 && $org->videos_count == 0): ?>
                                    <form action="<?php echo e(route('super-admin.organizations.destroy', $org)); ?>"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar esta organización?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay organizaciones registradas</p>
                                <a href="<?php echo e(route('super-admin.organizations.create')); ?>" class="btn btn-success">
                                    <i class="fas fa-plus mr-1"></i> Crear primera organización
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                <?php echo e($organizations->links()); ?>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\rugbyhub\resources\views/super-admin/organizations/index.blade.php ENDPATH**/ ?>