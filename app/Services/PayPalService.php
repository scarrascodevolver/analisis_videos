<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    protected string $baseUrl;

    protected ?string $clientId;

    protected ?string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('payments.paypal.base_url', 'https://api-m.sandbox.paypal.com');
        $this->clientId = config('payments.paypal.client_id');
        $this->clientSecret = config('payments.paypal.client_secret');
    }

    /**
     * Verificar si PayPal está configurado
     */
    public function isConfigured(): bool
    {
        return ! empty($this->clientId) && ! empty($this->clientSecret);
    }

    /**
     * Obtener token de acceso de PayPal
     */
    public function getAccessToken(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return Cache::remember('paypal_access_token', 3500, function () {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('PayPal: Error obteniendo token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        });
    }

    /**
     * Crear una orden de pago
     */
    public function createOrder(array $data): ?array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return null;
        }

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $data['reference_id'],
                    'description' => $data['description'],
                    'amount' => [
                        'currency_code' => $data['currency'],
                        'value' => number_format($data['amount'], 2, '.', ''),
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => config('app.name'),
                'landing_page' => 'NO_PREFERENCE',
                'user_action' => 'PAY_NOW',
                'return_url' => $data['return_url'],
                'cancel_url' => $data['cancel_url'],
            ],
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders", $orderData);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('PayPal: Error creando orden', [
            'status' => $response->status(),
            'body' => $response->body(),
            'request' => $orderData,
        ]);

        return null;
    }

    /**
     * Capturar el pago de una orden
     */
    public function captureOrder(string $orderId): ?array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return null;
        }

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('PayPal: Error capturando orden', [
            'order_id' => $orderId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Obtener detalles de una orden
     */
    public function getOrder(string $orderId): ?array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return null;
        }

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Verificar webhook signature
     */
    public function verifyWebhookSignature(array $headers, string $body): bool
    {
        $token = $this->getAccessToken();
        $webhookId = config('payments.paypal.webhook_id');

        if (! $token || ! $webhookId) {
            return false;
        }

        $verifyData = [
            'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? '',
            'cert_url' => $headers['PAYPAL-CERT-URL'] ?? '',
            'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
            'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
            'webhook_id' => $webhookId,
            'webhook_event' => json_decode($body, true),
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v1/notifications/verify-webhook-signature", $verifyData);

        if ($response->successful()) {
            return $response->json('verification_status') === 'SUCCESS';
        }

        return false;
    }
}
