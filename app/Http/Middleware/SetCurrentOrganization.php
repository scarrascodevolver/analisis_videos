<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    /**
     * Rutas excluidas del middleware (no requieren organización)
     */
    protected array $except = [
        'select-organization',
        'set-organization/*',
        'logout',
        'login',
        'register',
        'password/*',
        'super-admin',
        'super-admin/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario no está autenticado, continuar
        if (!auth()->check()) {
            return $next($request);
        }

        // Verificar si la ruta está excluida
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $user = auth()->user();

        // Super admins pueden acceder sin organización seleccionada
        if ($user->isSuperAdmin()) {
            return $next($request);
        }
        $currentOrg = $user->currentOrganization();

        // Si el usuario no tiene organización seleccionada
        if (!$currentOrg) {
            $organizations = $user->organizations;

            // Si tiene exactamente una organización, seleccionarla automáticamente
            if ($organizations->count() === 1) {
                $user->switchOrganization($organizations->first());
                return $next($request);
            }

            // Si tiene múltiples organizaciones, redirigir al selector
            if ($organizations->count() > 1) {
                return redirect()->route('select-organization')
                    ->with('info', 'Por favor selecciona una organización para continuar.');
            }

            // Si no tiene ninguna organización, error
            if ($organizations->count() === 0) {
                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'Tu cuenta no está asociada a ninguna organización. Contacta al administrador.');
            }
        }

        // Verificar que la organización esté activa
        if (!$currentOrg->is_active) {
            // Intentar cambiar a otra organización activa
            $activeOrg = $user->organizations()->where('is_active', true)->first();

            if ($activeOrg) {
                $user->switchOrganization($activeOrg);
            } else {
                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'Tu organización está desactivada. Contacta al administrador.');
            }
        }

        return $next($request);
    }
}
