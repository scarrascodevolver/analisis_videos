

<?php $__env->startSection('title', 'Iniciar Sesión - RugbyHub'); ?>

<?php $__env->startSection('css'); ?>
    <style>
        .password-toggle-btn {
            border-left: 0 !important;
            border-color: rgba(255, 255, 255, 0.3);
            background-color: rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.2s ease;
        }

        .password-toggle-btn:hover {
            background-color: rgba(255, 255, 255, 0.35);
            color: white;
            border-color: rgba(255, 255, 255, 0.4);
        }

        .password-toggle-btn:focus {
            box-shadow: none;
            border-color: rgba(255, 255, 255, 0.4);
        }

        .password-toggle-btn.active {
            background-color: rgba(255, 255, 255, 0.35);
            color: white;
            border-color: rgba(255, 255, 255, 0.4);
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-icon">
                    <img src="<?php echo e(asset('logohub.png')); ?>" alt="RugbyHub Logo"
                        style="width: 234px; height: 150px; object-fit: contain;">
                </div>
            </div>

            <div class="auth-body">
                <h5 class="text-center mb-4">Iniciar Sesión</h5>

                <form method="POST" action="<?php echo e(route('login')); ?>">
                    <?php echo csrf_field(); ?>

                    <!-- Email -->
                    <div class="form-group">
                        <div class="input-group">
                            <input type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="email"
                                placeholder="Correo electrónico" value="<?php echo e(old('email')); ?>" required autofocus>
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                            </div>
                        </div>
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <small class="text-danger"><?php echo e($message); ?></small>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <div class="input-group">
                            <input type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="password" name="password" placeholder="Contraseña" required>
                            <div class="input-group-append">
                                <button type="button" class="btn password-toggle-btn" id="togglePassword"
                                    title="Mostrar contraseña">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <small class="text-danger"><?php echo e($message); ?></small>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Remember Me -->
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember"
                                <?php echo e(old('remember') ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="remember">
                                Recordarme
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-rugby btn-block btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </form>

                <!-- Links -->
                <div class="text-center mt-4">
                    <?php if(Route::has('password.request')): ?>
                        <p class="mb-2">
                            <a href="<?php echo e(route('password.request')); ?>">¿Olvidaste tu contraseña?</a>
                        </p>
                    <?php endif; ?>
                    <p class="mb-0">
                        ¿No tienes cuenta?
                        <a href="<?php echo e(route('register')); ?>"><strong>Regístrate aquí</strong></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            togglePassword.addEventListener('click', function() {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle the icon and button state
                if (type === 'text') {
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                    togglePassword.classList.add('active');
                    togglePassword.title = 'Ocultar contraseña';
                } else {
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                    togglePassword.classList.remove('active');
                    togglePassword.title = 'Mostrar contraseña';
                }
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\rugbyhub\resources\views/auth/login.blade.php ENDPATH**/ ?>