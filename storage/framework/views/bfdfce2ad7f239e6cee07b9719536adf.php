<?php $__env->startSection('page_title', 'Configuración de Organización'); ?>

<?php $__env->startSection('breadcrumbs'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.index')); ?>">Mantenedor</a></li>
    <li class="breadcrumb-item active">Organización</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('main_content'); ?>
<div class="row">
    <div class="col-lg-8">
        <!-- Código de Invitación -->
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-ticket-alt mr-2"></i>
                    Código de Invitación para Jugadores
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Comparte este código con los jugadores de tu club para que puedan registrarse.
                    El código les permitirá unirse automáticamente a <strong><?php echo e($organization->name); ?></strong>.
                </p>

                <!-- Código actual -->
                <div class="form-group">
                    <label>Código actual:</label>
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control text-center font-weight-bold"
                               id="invitationCode"
                               value="<?php echo e($organization->invitation_code); ?>"
                               readonly
                               style="font-size: 1.5rem; letter-spacing: 3px; font-family: monospace;">
                        <div class="input-group-append">
                            <button class="btn btn-rugby" type="button" onclick="copyToClipboard('invitationCode', 'Código copiado!')">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Link completo -->
                <div class="form-group">
                    <label>Link de registro directo:</label>
                    <div class="input-group">
                        <input type="text" class="form-control"
                               id="registerUrl"
                               value="<?php echo e($registerUrl); ?>"
                               readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('registerUrl', 'Link copiado!')">
                                <i class="fas fa-link"></i> Copiar Link
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Los jugadores que usen este link tendrán el código pre-cargado.
                    </small>
                </div>

                <hr>

                <!-- Regenerar código -->
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Regenerar código automático</h6>
                        <small class="text-muted">Se generará un nuevo código aleatorio de 8 caracteres.</small>
                    </div>
                    <form action="<?php echo e(route('admin.organization.regenerate-code')); ?>" method="POST"
                          onsubmit="return confirm('¿Estás seguro? El código anterior dejará de funcionar.')">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-sync-alt"></i> Regenerar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Personalizar código -->
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-2"></i>
                    Personalizar Código
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Puedes crear un código personalizado más fácil de recordar (ej: TRONCOS2025, CLUBRUGBY).
                </p>

                <form action="<?php echo e(route('admin.organization.update-code')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="form-group">
                        <label for="custom_code">Nuevo código:</label>
                        <input type="text"
                               class="form-control <?php $__errorArgs = ['invitation_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               id="custom_code"
                               name="invitation_code"
                               value="<?php echo e(old('invitation_code', $organization->invitation_code)); ?>"
                               placeholder="Ej: TRONCOS2025"
                               maxlength="20"
                               style="text-transform: uppercase;">
                        <?php $__errorArgs = ['invitation_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <small class="form-text text-muted">
                            Solo letras y números. Mínimo 4, máximo 20 caracteres.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Guardar Código Personalizado
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Info de la organización -->
        <div class="card card-rugby">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building mr-2"></i>
                    <?php echo e($organization->name); ?>

                </h3>
            </div>
            <div class="card-body text-center">
                <?php if($organization->logo_path): ?>
                    <img src="<?php echo e(asset('storage/' . $organization->logo_path)); ?>"
                         alt="<?php echo e($organization->name); ?>"
                         class="img-fluid mb-3"
                         style="max-height: 150px;">
                <?php else: ?>
                    <i class="fas fa-building fa-5x text-muted mb-3"></i>
                <?php endif; ?>

                <div class="info-box bg-light mb-0">
                    <span class="info-box-icon bg-success"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Usuarios registrados</span>
                        <span class="info-box-number"><?php echo e($organization->users()->count()); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instrucciones -->
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title text-white">
                    <i class="fas fa-info-circle mr-2"></i>
                    Instrucciones
                </h3>
            </div>
            <div class="card-body">
                <ol class="pl-3 mb-0">
                    <li class="mb-2">Comparte el <strong>código</strong> o el <strong>link</strong> con tus jugadores.</li>
                    <li class="mb-2">Ellos irán a la página de registro e ingresarán el código.</li>
                    <li class="mb-2">Al registrarse, quedarán automáticamente en tu organización.</li>
                    <li>Podrás verlos en la sección de <a href="<?php echo e(route('admin.users.index')); ?>">Usuarios</a>.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function copyToClipboard(elementId, message) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(element.value);

    // Mostrar toast o alert
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    } else {
        // Fallback: mostrar mensaje temporal
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-rugby', 'btn-outline-secondary');
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            if (elementId === 'invitationCode') {
                btn.classList.add('btn-rugby');
            } else {
                btn.classList.add('btn-outline-secondary');
            }
        }, 2000);
    }
}

// Convertir a mayúsculas mientras escribe
document.getElementById('custom_code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\rugbyhub\resources\views/admin/organization.blade.php ENDPATH**/ ?>