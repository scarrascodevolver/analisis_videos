<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tournament;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function complete(Request $request)
    {
        $org = auth()->user()->currentOrganization();

        if (! $org || $org->onboarding_completed) {
            return redirect()->route('home');
        }

        if ($org->isClub()) {
            $request->validate([
                'categories' => 'required|array|min:1',
                'categories.*' => 'string|max:100',
            ]);

            // Eliminar categorías previas y crear las seleccionadas
            Category::withoutGlobalScopes()->where('organization_id', $org->id)->delete();
            foreach ($request->categories as $name) {
                $name = trim($name);
                if ($name) {
                    Category::create(['name' => $name, 'organization_id' => $org->id]);
                }
            }
        } else {
            // Asociación: crear primer torneo
            $request->validate([
                'tournament_name' => 'required|string|max:255',
            ]);
            Tournament::create([
                'name' => $request->tournament_name,
                'organization_id' => $org->id,
            ]);
        }

        $org->update(['onboarding_completed' => true]);

        return redirect()->route('home')->with('success', '¡Configuración completada! Ya podés empezar a subir videos.');
    }
}
