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
        if (! auth()->check()) {
            return $next($request);
        }

        // Verificar si la ruta está excluida
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $user = auth()->user();
        $currentOrg = $user->currentOrganization();

        // Si el usuario no tiene organización seleccionada
        if (! $currentOrg) {
            // Super admins ven todas las orgs activas del sistema
            // Org managers ven solo las que crearon
            if ($user->isSuperAdmin()) {
                $organizations = \App\Models\Organization::where('is_active', true)->get();
            } elseif ($user->isOrgManager()) {
                $organizations = \App\Models\Organization::where('is_active', true)
                    ->where('created_by', $user->id)
                    ->get();
            } else {
                $organizations = $user->organizations;
            }

            // Si tiene exactamente una organización, seleccionarla automáticamente
            if ($organizations->count() === 1) {
                $user->switchOrganization($organizations->first(), $user->isSuperAdmin() || $user->isOrgManager());

                return $next($request);
            }

            // Si tiene múltiples organizaciones, redirigir al selector
            if ($organizations->count() > 1) {
                return redirect()->route('select-organization')
                    ->with('info', 'Por favor selecciona una organización para continuar.');
            }

            // Si no hay ninguna organización (super admin u org manager en sistema vacío: dejar pasar)
            if ($user->isSuperAdmin() || $user->isOrgManager()) {
                return $next($request);
            }

            auth()->logout();

            return redirect()->route('login')
                ->with('error', 'Tu cuenta no está asociada a ninguna organización. Contacta al administrador.');
        }

        // Verificar que la organización esté activa
        if (! $currentOrg->is_active) {
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
