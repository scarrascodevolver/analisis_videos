<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function __construct()
    {
        // Verificar can_edit_settings para acciones de modificación
        $this->middleware(function ($request, $next) {
            $partner = view()->shared('currentPartner');
            if (! $partner || ! $partner->can_edit_settings) {
                abort(403, 'Solo el propietario puede modificar planes.');
            }

            return $next($request);
        })->except(['index']);
    }

    /**
     * Listar todos los planes
     */
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('duration_months')->get();

        return view('owner.plans.index', compact('plans'));
    }

    /**
     * Formulario para crear nuevo plan
     */
    public function create()
    {
        return view('owner.plans.form', [
            'plan' => null,
            'isEdit' => false,
        ]);
    }

    /**
     * Guardar nuevo plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subscription_plans,slug',
            'description' => 'nullable|string',
            'price_clp' => 'required|numeric|min:0',
            'price_usd' => 'required|numeric|min:0',
            'price_eur' => 'required|numeric|min:0',
            'price_pen' => 'required|numeric|min:0',
            'price_brl' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1|max:24',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'string',
        ]);

        // Generar slug si no se proporciona
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['features'] = array_filter($validated['features'] ?? []);

        SubscriptionPlan::create($validated);

        return redirect()->route('owner.plans.index')
            ->with('success', 'Plan creado exitosamente.');
    }

    /**
     * Formulario para editar plan
     */
    public function edit(SubscriptionPlan $plan)
    {
        return view('owner.plans.form', [
            'plan' => $plan,
            'isEdit' => true,
        ]);
    }

    /**
     * Actualizar plan
     */
    public function update(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subscription_plans,slug,'.$plan->id,
            'description' => 'nullable|string',
            'price_clp' => 'required|numeric|min:0',
            'price_usd' => 'required|numeric|min:0',
            'price_eur' => 'required|numeric|min:0',
            'price_pen' => 'required|numeric|min:0',
            'price_brl' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1|max:24',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'string',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['features'] = array_filter($validated['features'] ?? []);

        $plan->update($validated);

        return redirect()->route('owner.plans.index')
            ->with('success', 'Plan actualizado exitosamente.');
    }

    /**
     * Eliminar plan (solo si no tiene suscripciones)
     */
    public function destroy(SubscriptionPlan $plan)
    {
        if ($plan->subscriptions()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un plan con suscripciones activas.');
        }

        $plan->delete();

        return redirect()->route('owner.plans.index')
            ->with('success', 'Plan eliminado.');
    }

    /**
     * Toggle activo/inactivo
     */
    public function toggle(SubscriptionPlan $plan)
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        $status = $plan->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Plan {$status}.");
    }
}
