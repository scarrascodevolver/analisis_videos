<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PartnerOAuthController extends Controller
{
    protected MercadoPagoService $mercadopago;

    public function __construct(MercadoPagoService $mercadopago)
    {
        $this->mercadopago = $mercadopago;
    }

    /**
     * Mostrar estado de conexión de socios
     */
    public function index()
    {
        $partners = Partner::where('is_active', true)->get();

        return view('owner.partners.oauth', compact('partners'));
    }

    /**
     * Iniciar flujo OAuth para conectar cuenta de socio
     */
    public function connect(Partner $partner)
    {
        // Generar state único para prevenir CSRF
        $state = Str::random(40);
        session(['mp_oauth_state' => $state, 'mp_oauth_partner_id' => $partner->id]);

        $redirectUri = route('owner.partners.oauth.callback');
        $authUrl = $this->mercadopago->getAuthorizationUrl($redirectUri, $state);

        return redirect($authUrl);
    }

    /**
     * Callback de OAuth después de autorización
     */
    public function callback(Request $request)
    {
        $state = $request->input('state');
        $code = $request->input('code');
        $error = $request->input('error');

        // Verificar state
        if ($state !== session('mp_oauth_state')) {
            return redirect()->route('owner.partners.oauth.index')
                ->with('error', 'Error de seguridad. Por favor intenta de nuevo.');
        }

        $partnerId = session('mp_oauth_partner_id');
        $partner = Partner::find($partnerId);

        if (! $partner) {
            return redirect()->route('owner.partners.oauth.index')
                ->with('error', 'Socio no encontrado.');
        }

        // Verificar si hubo error en la autorización
        if ($error) {
            Log::warning('MercadoPago OAuth: Error de autorización', [
                'partner_id' => $partner->id,
                'error' => $error,
            ]);

            return redirect()->route('owner.partners.oauth.index')
                ->with('error', 'Autorización cancelada o denegada.');
        }

        // Intercambiar código por tokens
        $redirectUri = route('owner.partners.oauth.callback');
        $tokenData = $this->mercadopago->exchangeCodeForToken($code, $redirectUri);

        if (! $tokenData) {
            return redirect()->route('owner.partners.oauth.index')
                ->with('error', 'Error al obtener credenciales. Intenta de nuevo.');
        }

        // Obtener información del usuario
        $userInfo = $this->mercadopago->getUserInfo($tokenData['access_token']);

        // Guardar credenciales en el partner
        $partner->update([
            'mp_user_id' => $tokenData['user_id'] ?? $userInfo['id'] ?? null,
            'mp_access_token' => $tokenData['access_token'],
            'mp_refresh_token' => $tokenData['refresh_token'] ?? null,
            'mp_token_expires_at' => isset($tokenData['expires_in'])
                ? now()->addSeconds($tokenData['expires_in'])
                : null,
            'mp_connected' => true,
            'mercadopago_email' => $userInfo['email'] ?? $partner->mercadopago_email,
        ]);

        // Limpiar sesión
        session()->forget(['mp_oauth_state', 'mp_oauth_partner_id']);

        Log::info('MercadoPago OAuth: Cuenta conectada exitosamente', [
            'partner_id' => $partner->id,
            'mp_user_id' => $partner->mp_user_id,
        ]);

        return redirect()->route('owner.partners.oauth.index')
            ->with('success', "¡{$partner->name} conectó su cuenta de Mercado Pago exitosamente!");
    }

    /**
     * Desconectar cuenta de Mercado Pago de un socio
     */
    public function disconnect(Partner $partner)
    {
        $partner->disconnectMercadoPago();

        return redirect()->route('owner.partners.oauth.index')
            ->with('success', "Cuenta de Mercado Pago de {$partner->name} desconectada.");
    }

    /**
     * Refrescar token de un socio
     */
    public function refresh(Partner $partner)
    {
        if (! $partner->mp_refresh_token) {
            return redirect()->route('owner.partners.oauth.index')
                ->with('error', 'No hay token de refresco disponible. Reconecta la cuenta.');
        }

        $tokenData = $this->mercadopago->refreshToken($partner->mp_refresh_token);

        if (! $tokenData) {
            $partner->disconnectMercadoPago();

            return redirect()->route('owner.partners.oauth.index')
                ->with('error', 'Error al refrescar token. La cuenta fue desconectada.');
        }

        $partner->update([
            'mp_access_token' => $tokenData['access_token'],
            'mp_refresh_token' => $tokenData['refresh_token'] ?? $partner->mp_refresh_token,
            'mp_token_expires_at' => isset($tokenData['expires_in'])
                ? now()->addSeconds($tokenData['expires_in'])
                : null,
        ]);

        return redirect()->route('owner.partners.oauth.index')
            ->with('success', "Token de {$partner->name} actualizado.");
    }
}
