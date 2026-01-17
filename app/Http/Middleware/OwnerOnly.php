<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnerOnly
{
    /**
     * Permite acceso a partners activos del sistema.
     * Los partners con can_edit_settings=true pueden modificar configuraciones.
     * Los partners con can_edit_settings=false solo pueden ver reportes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Verificar si es partner activo
        $partner = Partner::where('email', $user->email)
            ->where('is_active', true)
            ->first();

        if (!$partner) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        // Compartir el partner con las vistas
        view()->share('currentPartner', $partner);

        return $next($request);
    }
}
