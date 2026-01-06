<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * Mostrar selector de organizaciones
     */
    public function select()
    {
        $user = auth()->user();
        $organizations = $user->organizations()->where('is_active', true)->get();

        // Si solo tiene una organización, seleccionarla automáticamente
        if ($organizations->count() === 1) {
            $user->switchOrganization($organizations->first());
            return redirect()->route('home')
                ->with('success', 'Bienvenido a ' . $organizations->first()->name);
        }

        // Si no tiene organizaciones, error
        if ($organizations->count() === 0) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Tu cuenta no está asociada a ninguna organización activa.');
        }

        $currentOrg = $user->currentOrganization();

        return view('organizations.select', compact('organizations', 'currentOrg'));
    }

    /**
     * Cambiar a una organización específica
     */
    public function switch(Organization $organization)
    {
        $user = auth()->user();

        // Verificar que el usuario pertenece a esta organización
        if (!$user->organizations()->where('organizations.id', $organization->id)->exists()) {
            return redirect()->back()
                ->with('error', 'No tienes acceso a esta organización.');
        }

        // Verificar que la organización esté activa
        if (!$organization->is_active) {
            return redirect()->back()
                ->with('error', 'Esta organización está desactivada.');
        }

        // Cambiar de organización
        $user->switchOrganization($organization);

        return redirect()->route('home')
            ->with('success', 'Cambiaste a ' . $organization->name);
    }
}
