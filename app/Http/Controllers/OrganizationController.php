<?php

namespace App\Http\Controllers;

use App\Models\Organization;

class OrganizationController extends Controller
{
    /**
     * Mostrar selector de organizaciones
     */
    public function select()
    {
        $user = auth()->user();

        // Super admins pueden ver TODAS las organizaciones
        if ($user->isSuperAdmin()) {
            $organizations = Organization::where('is_active', true)->orderBy('name')->get();
        } elseif ($user->isOrgManager()) {
            // Org managers solo ven las organizaciones que ellos crearon
            $organizations = Organization::where('is_active', true)
                ->where('created_by', $user->id)
                ->orderBy('name')
                ->get();
        } else {
            $organizations = $user->organizations()->where('is_active', true)->get();
        }

        // Si solo tiene una organización, seleccionarla automáticamente
        if ($organizations->count() === 1) {
            $user->switchOrganization($organizations->first(), $user->isSuperAdmin() || $user->isOrgManager());

            return redirect()->route('home')
                ->with('success', 'Bienvenido a '.$organizations->first()->name);
        }

        // Si no tiene organizaciones, error (no aplica a super admins ni org managers)
        if ($organizations->count() === 0 && ! $user->isSuperAdmin() && ! $user->isOrgManager()) {
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

        // Super admins pueden cambiar a cualquier organización
        // Org managers solo pueden cambiar a orgs que ellos crearon
        if (! $user->isSuperAdmin()) {
            if ($user->isOrgManager()) {
                if ($organization->created_by !== $user->id) {
                    return redirect()->back()
                        ->with('error', 'No tienes acceso a esta organización.');
                }
            } elseif (! $user->organizations()->where('organizations.id', $organization->id)->exists()) {
                return redirect()->back()
                    ->with('error', 'No tienes acceso a esta organización.');
            }
        }

        // Verificar que la organización esté activa
        if (! $organization->is_active) {
            return redirect()->back()
                ->with('error', 'Esta organización está desactivada.');
        }

        // Cambiar de organización
        $user->switchOrganization($organization, $user->isSuperAdmin() || $user->isOrgManager());

        return redirect()->route('home')
            ->with('success', 'Cambiaste a '.$organization->name);
    }
}
