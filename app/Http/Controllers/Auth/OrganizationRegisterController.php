<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\BunnyStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrganizationRegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showForm()
    {
        return view('auth.register-organization');
    }

    public function store(Request $request)
    {
        $request->validate([
            'org_name'       => 'required|string|max:255',
            'org_type'       => 'required|in:club,asociacion',
            'logo'           => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        // Generar slug único
        $slug = Str::slug($request->org_name);
        $originalSlug = $slug;
        $counter = 1;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        // Subir logo si existe
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('organizations/logos', 'public');
        }

        // Crear organización
        $organization = Organization::create([
            'name'     => $request->org_name,
            'type'     => $request->org_type,
            'slug'     => $slug,
            'logo_path' => $logoPath,
            'is_active' => true,
        ]);

        // Intentar crear library en Bunny Stream
        $bunnyWarning = null;
        try {
            $libraryName = ucfirst($organization->type) . ' - ' . $organization->name;
            $bunnyData   = BunnyStreamService::createLibrary($libraryName);

            $organization->update([
                'bunny_library_id'   => $bunnyData['library_id'],
                'bunny_api_key'      => $bunnyData['api_key'],
                'bunny_cdn_hostname' => $bunnyData['cdn_hostname'],
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo crear la library en Bunny al auto-registrar org ' . $organization->id, [
                'error' => $e->getMessage(),
            ]);
            $bunnyWarning = 'La organización fue creada, pero no se pudo configurar el servidor de video. Un administrador deberá configurarlo manualmente.';
        }

        // Categorías por defecto para clubes
        if ($organization->type === Organization::TYPE_CLUB) {
            foreach (['Masculino', 'Juveniles', 'Femenino'] as $catName) {
                $organization->categories()->create(['name' => $catName]);
            }
        }

        // Crear usuario administrador
        $user = User::create([
            'name'     => $request->admin_name,
            'email'    => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'role'     => 'analista',
        ]);

        $organization->users()->attach($user->id, [
            'role'        => 'analista',
            'is_current'  => true,
            'is_org_admin' => true,
        ]);

        Auth::login($user);

        $successMessage = "¡Bienvenido a Rugby Key Performance! La organización '{$organization->name}' fue creada exitosamente.";
        $redirect = redirect()->route('home')->with('success', $successMessage);

        if ($bunnyWarning) {
            $redirect = $redirect->with('warning', $bunnyWarning);
        }

        return $redirect;
    }
}
