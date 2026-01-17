<?php

namespace App\Services;

use App\Models\Partner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    protected ?string $accessToken;
    protected ?string $appId;
    protected ?string $clientSecret;
    protected string $baseUrl = 'https://api.mercadopago.com';
    protected string $authUrl = 'https://auth.mercadopago.cl';

    public function __construct()
    {
        $this->accessToken = config('payments.mercadopago.access_token');
        $this->appId = config('payments.mercadopago.app_id');
        $this->clientSecret = config('payments.mercadopago.client_secret');
    }

    /**
     * Verificar si Mercado Pago está configurado
     */
    public function isConfigured(): bool
    {
        return !empty($this->accessToken);
    }

    /**
     * Generar URL de autorización OAuth para conectar cuenta de socio
     */
    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $params = http_build_query([
            'client_id' => $this->appId,
            'response_type' => 'code',
            'platform_id' => 'mp',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return "{$this->authUrl}/authorization?{$params}";
    }

    /**
     * Intercambiar código de autorización por tokens
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): ?array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
            'client_id' => $this->appId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('MercadoPago OAuth: Error intercambiando código', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Refrescar token de acceso
     */
    public function refreshToken(string $refreshToken): ?array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
            'client_id' => $this->appId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('MercadoPago OAuth: Error refrescando token', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Obtener información del usuario autorizado
     */
    public function getUserInfo(string $accessToken): ?array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/users/me");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Crear una preferencia de pago (checkout)
     */
    public function createPreference(array $data): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $preferenceData = [
            'items' => [
                [
                    'id' => $data['reference_id'],
                    'title' => $data['description'],
                    'quantity' => 1,
                    'currency_id' => $data['currency'],
                    'unit_price' => (float) $data['amount'],
                ],
            ],
            'payer' => [
                'email' => $data['payer_email'] ?? null,
            ],
            'back_urls' => [
                'success' => $data['success_url'],
                'failure' => $data['failure_url'],
                'pending' => $data['pending_url'] ?? $data['success_url'],
            ],
            'auto_return' => 'approved',
            'external_reference' => $data['reference_id'],
            'notification_url' => $data['notification_url'] ?? null,
            'statement_descriptor' => config('app.name'),
        ];

        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/checkout/preferences", $preferenceData);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('MercadoPago: Error creando preferencia', [
            'status' => $response->status(),
            'body' => $response->body(),
            'request' => $preferenceData,
        ]);

        return null;
    }

    /**
     * Obtener detalles de un pago
     */
    public function getPayment(string $paymentId): ?array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/v1/payments/{$paymentId}");

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('MercadoPago: Error obteniendo pago', [
            'payment_id' => $paymentId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Verificar webhook signature
     */
    public function verifyWebhookSignature(string $xSignature, string $xRequestId, string $dataId): bool
    {
        $secret = config('payments.mercadopago.webhook_secret');

        if (!$secret) {
            // Si no hay secreto configurado, aceptar (modo desarrollo)
            return true;
        }

        // Parsear x-signature header
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = explode('=', trim($part), 2);
            $parts[$key] = $value;
        }

        $ts = $parts['ts'] ?? '';
        $signature = $parts['v1'] ?? '';

        // Crear el string a firmar
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";

        // Calcular HMAC
        $expectedSignature = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Procesar notificación webhook
     */
    public function processWebhook(array $data): ?array
    {
        $type = $data['type'] ?? '';
        $action = $data['action'] ?? '';

        if ($type === 'payment') {
            $paymentId = $data['data']['id'] ?? null;

            if ($paymentId) {
                return $this->getPayment($paymentId);
            }
        }

        return null;
    }

    /**
     * Crear preferencia con split de pagos automático
     *
     * @param array $data Datos del pago
     * @param array $splits Array de splits [['partner' => Partner, 'amount' => float], ...]
     */
    public function createPreferenceWithSplit(array $data, array $splits): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $preferenceData = [
            'items' => [
                [
                    'id' => $data['reference_id'],
                    'title' => $data['description'],
                    'quantity' => 1,
                    'currency_id' => $data['currency'],
                    'unit_price' => (float) $data['amount'],
                ],
            ],
            'payer' => [
                'email' => $data['payer_email'] ?? null,
            ],
            'back_urls' => [
                'success' => $data['success_url'],
                'failure' => $data['failure_url'],
                'pending' => $data['pending_url'] ?? $data['success_url'],
            ],
            'auto_return' => 'approved',
            'external_reference' => $data['reference_id'],
            'notification_url' => $data['notification_url'] ?? null,
            'statement_descriptor' => config('app.name'),
            'marketplace_fee' => 0, // Sin comisión de marketplace
        ];

        // Agregar disbursements (splits) para cada socio
        $disbursements = [];
        foreach ($splits as $split) {
            $partner = $split['partner'];

            // Solo agregar si el partner tiene MP conectado
            if ($partner->hasMercadoPagoConnected()) {
                $disbursements[] = [
                    'collector_id' => (int) $partner->mp_user_id,
                    'amount' => (float) $split['amount'],
                    'external_reference' => $data['reference_id'] . '-' . $partner->id,
                    'application_fee' => 0,
                ];
            }
        }

        // Si hay disbursements, usar el endpoint de marketplace
        if (!empty($disbursements)) {
            $preferenceData['marketplace'] = config('payments.mercadopago.app_id');

            // Crear pago con split usando Advanced Payments
            return $this->createAdvancedPayment($preferenceData, $disbursements);
        }

        // Si no hay splits configurados, crear preferencia normal
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/checkout/preferences", $preferenceData);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('MercadoPago: Error creando preferencia con split', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Crear pago avanzado con split (Marketplace)
     */
    protected function createAdvancedPayment(array $preferenceData, array $disbursements): ?array
    {
        // Para split automático, usamos el checkout normal pero con la configuración de marketplace
        // El split real se hace después del pago usando la API de disbursements

        // Por ahora, crear preferencia normal y guardar los disbursements en external_reference
        $preferenceData['metadata'] = [
            'disbursements' => json_encode($disbursements),
        ];

        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/checkout/preferences", $preferenceData);

        if ($response->successful()) {
            $result = $response->json();
            $result['_disbursements'] = $disbursements;
            return $result;
        }

        Log::error('MercadoPago: Error creando pago avanzado', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Ejecutar disbursement (transferir a socio) después del pago
     */
    public function executeDisbursement(string $paymentId, Partner $partner, float $amount): ?array
    {
        if (!$partner->hasMercadoPagoConnected()) {
            Log::warning('MercadoPago: Partner sin cuenta conectada', ['partner_id' => $partner->id]);
            return null;
        }

        // Usar el token del partner para transferir
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/v1/account_holders/{$partner->mp_user_id}/transfers", [
                'amount' => $amount,
                'currency_id' => 'CLP',
                'description' => "Split de pago #{$paymentId}",
                'external_reference' => "split-{$paymentId}-{$partner->id}",
            ]);

        if ($response->successful()) {
            Log::info('MercadoPago: Disbursement exitoso', [
                'payment_id' => $paymentId,
                'partner_id' => $partner->id,
                'amount' => $amount,
            ]);
            return $response->json();
        }

        Log::error('MercadoPago: Error en disbursement', [
            'payment_id' => $paymentId,
            'partner_id' => $partner->id,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Verificar si todos los socios tienen MP conectado
     */
    public function allPartnersConnected(): bool
    {
        $partners = Partner::where('is_active', true)->get();

        foreach ($partners as $partner) {
            if (!$partner->hasMercadoPagoConnected()) {
                return false;
            }
        }

        return $partners->count() > 0;
    }

    /**
     * Obtener socios sin MP conectado
     */
    public function getUnconnectedPartners()
    {
        return Partner::where('is_active', true)
            ->where(function ($q) {
                $q->where('mp_connected', false)
                  ->orWhereNull('mp_user_id');
            })
            ->get();
    }
}
