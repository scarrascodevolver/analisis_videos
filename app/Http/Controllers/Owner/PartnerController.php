<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function __construct()
    {
        // Verificar can_edit_settings para acciones de modificación
        $this->middleware(function ($request, $next) {
            $partner = view()->shared('currentPartner');
            if (!$partner || !$partner->can_edit_settings) {
                abort(403, 'Solo el propietario puede modificar socios.');
            }
            return $next($request);
        })->except(['index']);
    }

    /**
     * Listar todos los socios
     */
    public function index()
    {
        $partners = Partner::orderBy('split_percentage', 'desc')->get();
        $totalPercentage = $partners->where('is_active', true)->sum('split_percentage');

        return view('owner.partners.index', compact('partners', 'totalPercentage'));
    }

    /**
     * Formulario para crear nuevo socio
     */
    public function create()
    {
        return view('owner.partners.form', [
            'partner' => null,
            'isEdit' => false,
        ]);
    }

    /**
     * Guardar nuevo socio
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:partners,email',
            'role' => 'required|in:owner,partner',
            'paypal_email' => 'nullable|email',
            'mercadopago_email' => 'nullable|email',
            'split_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'can_edit_settings' => 'boolean',
        ]);

        // Validar que la suma no exceda 100%
        $currentTotal = Partner::where('is_active', true)->sum('split_percentage');
        $newTotal = $currentTotal + $validated['split_percentage'];

        if ($newTotal > 100) {
            return back()->withErrors([
                'split_percentage' => "El porcentaje excede el 100%. Disponible: " . (100 - $currentTotal) . "%"
            ])->withInput();
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['can_edit_settings'] = $request->boolean('can_edit_settings', false);

        Partner::create($validated);

        return redirect()->route('owner.partners.index')
            ->with('success', 'Socio creado exitosamente.');
    }

    /**
     * Formulario para editar socio
     */
    public function edit(Partner $partner)
    {
        return view('owner.partners.form', [
            'partner' => $partner,
            'isEdit' => true,
        ]);
    }

    /**
     * Actualizar socio
     */
    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:partners,email,' . $partner->id,
            'role' => 'required|in:owner,partner',
            'paypal_email' => 'nullable|email',
            'mercadopago_email' => 'nullable|email',
            'split_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'can_edit_settings' => 'boolean',
        ]);

        // Validar que la suma no exceda 100%
        $currentTotal = Partner::where('is_active', true)
            ->where('id', '!=', $partner->id)
            ->sum('split_percentage');

        $newIsActive = $request->boolean('is_active', true);
        if ($newIsActive) {
            $newTotal = $currentTotal + $validated['split_percentage'];
            if ($newTotal > 100) {
                return back()->withErrors([
                    'split_percentage' => "El porcentaje excede el 100%. Disponible: " . (100 - $currentTotal) . "%"
                ])->withInput();
            }
        }

        $validated['is_active'] = $newIsActive;
        $validated['can_edit_settings'] = $request->boolean('can_edit_settings', false);

        $partner->update($validated);

        return redirect()->route('owner.partners.index')
            ->with('success', 'Socio actualizado exitosamente.');
    }

    /**
     * Eliminar socio (solo si no tiene pagos)
     */
    public function destroy(Partner $partner)
    {
        // No permitir eliminar si tiene splits
        if ($partner->paymentSplits()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un socio con historial de pagos.');
        }

        // No permitir eliminar al owner principal
        if ($partner->email === 'eliascarrascoaguayo@gmail.com') {
            return back()->with('error', 'No se puede eliminar al propietario principal.');
        }

        $partner->delete();

        return redirect()->route('owner.partners.index')
            ->with('success', 'Socio eliminado.');
    }
}
