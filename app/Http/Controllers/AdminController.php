<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ClipCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Dashboard principal del Mantenedor
     */
    public function index()
    {
        // Obtener organización actual del usuario
        $currentOrg = auth()->user()->currentOrganization();

        // Contadores simples para las tarjetas (filtrados por organización)
        $stats = [
            'categories'      => Category::count(),
            'users'           => $currentOrg ? $currentOrg->users()->count() : 0,
            'clip_categories' => ClipCategory::count(),
        ];

        return view('admin.index', compact('stats'));
    }

    /**
     * Página de configuración de organización (código de invitación)
     */
    public function organization()
    {
        $organization = auth()->user()->currentOrganization();

        if (! $organization) {
            return redirect()->route('admin.index')
                ->with('error', 'No tienes una organización asignada.');
        }

        // URL base para el registro con código
        $registerUrl = url('/register?code='.$organization->invitation_code);

        return view('admin.organization', compact('organization', 'registerUrl'));
    }

    /**
     * Actualizar código de invitación (personalizado)
     */
    public function updateInvitationCode(Request $request)
    {
        $organization = auth()->user()->currentOrganization();

        if (! $organization) {
            return back()->with('error', 'No tienes una organización asignada.');
        }

        $validated = $request->validate([
            'invitation_code' => [
                'required',
                'string',
                'min:4',
                'max:20',
                'alpha_num',
                'unique:organizations,invitation_code,'.$organization->id,
            ],
        ], [
            'invitation_code.unique' => 'Este código ya está en uso por otra organización.',
            'invitation_code.alpha_num' => 'El código solo puede contener letras y números.',
            'invitation_code.min' => 'El código debe tener al menos 4 caracteres.',
            'invitation_code.max' => 'El código no puede tener más de 20 caracteres.',
        ]);

        $organization->invitation_code = strtoupper($validated['invitation_code']);
        $organization->save();

        return back()->with('success', 'Código de invitación actualizado correctamente.');
    }

    /**
     * Regenerar código de invitación automáticamente
     */
    public function regenerateInvitationCode()
    {
        $organization = auth()->user()->currentOrganization();

        if (! $organization) {
            return back()->with('error', 'No tienes una organización asignada.');
        }

        $newCode = $organization->regenerateInvitationCode();

        return back()->with('success', "Nuevo código generado: {$newCode}");
    }

    /**
     * Actualizar nombre y logo de la organización
     */
    public function updateBranding(Request $request)
    {
        $organization = auth()->user()->currentOrganization();

        if (! $organization) {
            return back()->with('error', 'No tienes una organización asignada.');
        }

        $request->validate([
            'org_name' => 'required|string|max:100',
            'logo'     => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ], [
            'org_name.required' => 'El nombre de la organización es obligatorio.',
            'org_name.max'      => 'El nombre no puede superar los 100 caracteres.',
            'logo.image'        => 'El logo debe ser una imagen.',
            'logo.max'          => 'El logo no puede pesar más de 10MB.',
        ]);

        $organization->name = $request->org_name;

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            $organization->logo_path = $request->file('logo')->store('organizations/logos', 'public');
        }

        $organization->save();

        return back()->with('success', 'Datos de la organización actualizados correctamente.');
    }

    public function renameCategory(Request $request, Category $category)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $category->update(['name' => $request->name]);

        return response()->json(['ok' => true, 'name' => $category->name]);
    }
}
