<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PayPalService;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    protected PayPalService $paypal;
    protected MercadoPagoService $mercadopago;

    public function __construct(PayPalService $paypal, MercadoPagoService $mercadopago)
    {
        $this->paypal = $paypal;
        $this->mercadopago = $mercadopago;
    }

    /**
     * Mostrar página de precios/planes
     */
    public function pricing()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('duration_months')
            ->get();

        // Detectar moneda según IP del usuario
        $currency = $this->detectCurrency();

        return view('subscription.pricing', compact('plans', 'currency'));
    }

    /**
     * Iniciar checkout para un plan
     */
    public function checkout(Request $request, SubscriptionPlan $plan)
    {
        $user = auth()->user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return redirect()->back()->with('error', 'Debes pertenecer a una organización para suscribirte.');
        }

        // Verificar si ya tiene suscripción activa
        $activeSubscription = $organization->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if ($activeSubscription) {
            return redirect()->back()->with('error', 'Ya tienes una suscripción activa hasta ' . $activeSubscription->ends_at->format('d/m/Y'));
        }

        // Determinar moneda y precio
        $currency = $request->input('currency', $this->detectCurrency());
        $price = $plan->getPriceForCurrency($currency);

        // Determinar proveedor de pago según moneda
        $provider = $this->getProviderForCurrency($currency);

        return view('subscription.checkout', compact('plan', 'organization', 'currency', 'price', 'provider'));
    }

    /**
     * Crear orden de PayPal
     */
    public function createPayPalOrder(Request $request, SubscriptionPlan $plan)
    {
        $user = auth()->user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json(['error' => 'Organización no encontrada'], 400);
        }

        $currency = $request->input('currency', 'USD');
        $price = $plan->getPriceForCurrency($currency);

        // Crear referencia única
        $referenceId = 'SUB-' . $organization->id . '-' . $plan->id . '-' . time();

        $orderData = [
            'reference_id' => $referenceId,
            'description' => "Suscripción {$plan->name} - {$organization->name}",
            'currency' => $currency,
            'amount' => $price,
            'return_url' => route('subscription.paypal.capture', ['plan' => $plan->id]),
            'cancel_url' => route('subscription.checkout', ['plan' => $plan->id, 'cancelled' => 1]),
        ];

        $order = $this->paypal->createOrder($orderData);

        if (!$order) {
            return response()->json(['error' => 'Error al crear orden de pago'], 500);
        }

        // Guardar referencia en sesión
        session([
            'paypal_order' => [
                'order_id' => $order['id'],
                'reference_id' => $referenceId,
                'plan_id' => $plan->id,
                'organization_id' => $organization->id,
                'currency' => $currency,
                'amount' => $price,
            ],
        ]);

        // Encontrar el link de aprobación
        $approveUrl = collect($order['links'])->firstWhere('rel', 'approve')['href'] ?? null;

        return response()->json([
            'id' => $order['id'],
            'approve_url' => $approveUrl,
        ]);
    }

    /**
     * Capturar pago de PayPal después de aprobación
     */
    public function capturePayPalOrder(Request $request, SubscriptionPlan $plan)
    {
        $orderId = $request->input('token'); // PayPal envía el order ID como 'token'
        $sessionOrder = session('paypal_order');

        if (!$orderId || !$sessionOrder || $sessionOrder['order_id'] !== $orderId) {
            return redirect()->route('subscription.pricing')
                ->with('error', 'Orden de pago inválida.');
        }

        // Capturar el pago
        $capture = $this->paypal->captureOrder($orderId);

        if (!$capture || $capture['status'] !== 'COMPLETED') {
            Log::error('PayPal: Captura fallida', ['order_id' => $orderId, 'response' => $capture]);
            return redirect()->route('subscription.checkout', $plan)
                ->with('error', 'Error al procesar el pago. Por favor intenta de nuevo.');
        }

        // Obtener detalles del pago
        $captureDetails = $capture['purchase_units'][0]['payments']['captures'][0] ?? null;

        // Crear suscripción
        $organization = Organization::find($sessionOrder['organization_id']);
        $subscription = $organization->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonths($plan->duration_months),
            'payment_provider' => 'paypal',
            'payment_provider_id' => $orderId,
        ]);

        // Registrar el pago
        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'organization_id' => $organization->id,
            'amount' => $sessionOrder['amount'],
            'currency' => $sessionOrder['currency'],
            'status' => 'completed',
            'payment_provider' => 'paypal',
            'payment_provider_id' => $captureDetails['id'] ?? $orderId,
            'payment_data' => $capture,
            'paid_at' => now(),
        ]);

        // Crear splits para los socios
        $payment->createSplits();

        // Limpiar sesión
        session()->forget('paypal_order');

        Log::info('Suscripción creada exitosamente', [
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
            'organization' => $organization->name,
        ]);

        return redirect()->route('subscription.success', $subscription)
            ->with('success', '¡Pago completado! Tu suscripción está activa.');
    }

    /**
     * Webhook de PayPal
     */
    public function paypalWebhook(Request $request)
    {
        $headers = $request->headers->all();
        $body = $request->getContent();

        // Verificar firma del webhook
        $headerArray = [];
        foreach ($headers as $key => $value) {
            $headerArray[strtoupper(str_replace('-', '_', $key))] = is_array($value) ? $value[0] : $value;
        }

        if (!$this->paypal->verifyWebhookSignature($headerArray, $body)) {
            Log::warning('PayPal Webhook: Firma inválida');
            return response('Invalid signature', 400);
        }

        $event = json_decode($body, true);
        $eventType = $event['event_type'] ?? '';

        Log::info('PayPal Webhook recibido', ['type' => $eventType]);

        switch ($eventType) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                // El pago ya se procesa en capturePayPalOrder
                break;

            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.REFUNDED':
                $captureId = $event['resource']['id'] ?? null;
                if ($captureId) {
                    $payment = Payment::where('payment_provider_id', $captureId)->first();
                    if ($payment) {
                        $payment->update(['status' => 'refunded']);
                        $payment->subscription?->update(['status' => 'cancelled']);
                    }
                }
                break;
        }

        return response('OK', 200);
    }

    /**
     * Crear preferencia de Mercado Pago
     */
    public function createMercadoPagoOrder(Request $request, SubscriptionPlan $plan)
    {
        $user = auth()->user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json(['error' => 'Organización no encontrada'], 400);
        }

        $currency = $request->input('currency', 'CLP');
        $price = $plan->getPriceForCurrency($currency);

        // Crear referencia única
        $referenceId = 'SUB-' . $organization->id . '-' . $plan->id . '-' . time();

        $preferenceData = [
            'reference_id' => $referenceId,
            'description' => "Suscripción {$plan->name} - {$organization->name}",
            'currency' => $currency,
            'amount' => $price,
            'payer_email' => $user->email,
            'success_url' => route('subscription.mercadopago.callback', ['plan' => $plan->id, 'status' => 'approved']),
            'failure_url' => route('subscription.mercadopago.callback', ['plan' => $plan->id, 'status' => 'failure']),
            'pending_url' => route('subscription.mercadopago.callback', ['plan' => $plan->id, 'status' => 'pending']),
            'notification_url' => route('webhooks.mercadopago'),
        ];

        $preference = $this->mercadopago->createPreference($preferenceData);

        if (!$preference) {
            return response()->json(['error' => 'Error al crear preferencia de pago'], 500);
        }

        // Guardar referencia en sesión
        session([
            'mercadopago_order' => [
                'preference_id' => $preference['id'],
                'reference_id' => $referenceId,
                'plan_id' => $plan->id,
                'organization_id' => $organization->id,
                'currency' => $currency,
                'amount' => $price,
            ],
        ]);

        return response()->json([
            'id' => $preference['id'],
            'init_point' => $preference['init_point'],
        ]);
    }

    /**
     * Callback de Mercado Pago después del pago
     */
    public function mercadoPagoCallback(Request $request, SubscriptionPlan $plan)
    {
        $status = $request->input('status');
        $paymentId = $request->input('payment_id');
        $externalReference = $request->input('external_reference');
        $sessionOrder = session('mercadopago_order');

        if ($status === 'failure') {
            return redirect()->route('subscription.checkout', ['plan' => $plan->id, 'cancelled' => 1])
                ->with('error', 'El pago fue rechazado. Por favor intenta con otro método.');
        }

        if ($status === 'pending') {
            return redirect()->route('subscription.pricing')
                ->with('info', 'Tu pago está pendiente de confirmación. Te notificaremos cuando se complete.');
        }

        // Status approved
        if (!$paymentId || !$sessionOrder) {
            return redirect()->route('subscription.pricing')
                ->with('error', 'Error al procesar el pago.');
        }

        // Verificar el pago con Mercado Pago
        $paymentDetails = $this->mercadopago->getPayment($paymentId);

        if (!$paymentDetails || $paymentDetails['status'] !== 'approved') {
            Log::error('MercadoPago: Pago no aprobado', ['payment_id' => $paymentId, 'details' => $paymentDetails]);
            return redirect()->route('subscription.checkout', $plan)
                ->with('error', 'Error al verificar el pago.');
        }

        // Crear suscripción
        $organization = Organization::find($sessionOrder['organization_id']);
        $subscription = $organization->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonths($plan->duration_months),
            'payment_provider' => 'mercadopago',
            'payment_provider_id' => $paymentId,
        ]);

        // Registrar el pago
        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'organization_id' => $organization->id,
            'amount' => $sessionOrder['amount'],
            'currency' => $sessionOrder['currency'],
            'status' => 'completed',
            'payment_provider' => 'mercadopago',
            'payment_provider_id' => $paymentId,
            'payment_data' => $paymentDetails,
            'paid_at' => now(),
        ]);

        // Crear splits para los socios
        $payment->createSplits();

        // Limpiar sesión
        session()->forget('mercadopago_order');

        Log::info('Suscripción creada exitosamente via MercadoPago', [
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
            'organization' => $organization->name,
        ]);

        return redirect()->route('subscription.success', $subscription)
            ->with('success', '¡Pago completado! Tu suscripción está activa.');
    }

    /**
     * Webhook de Mercado Pago
     */
    public function mercadoPagoWebhook(Request $request)
    {
        $xSignature = $request->header('x-signature', '');
        $xRequestId = $request->header('x-request-id', '');
        $dataId = $request->input('data.id', '');

        // Verificar firma (opcional, según configuración)
        if (!$this->mercadopago->verifyWebhookSignature($xSignature, $xRequestId, $dataId)) {
            Log::warning('MercadoPago Webhook: Firma inválida');
            // No rechazar, MercadoPago puede no enviar firma en algunos casos
        }

        $type = $request->input('type');
        $action = $request->input('action');

        Log::info('MercadoPago Webhook recibido', ['type' => $type, 'action' => $action]);

        if ($type === 'payment') {
            $paymentId = $request->input('data.id');

            if ($paymentId) {
                $paymentDetails = $this->mercadopago->getPayment($paymentId);

                if ($paymentDetails) {
                    $externalReference = $paymentDetails['external_reference'] ?? null;
                    $status = $paymentDetails['status'];

                    // Buscar pago existente
                    $payment = Payment::where('payment_provider', 'mercadopago')
                        ->where('payment_provider_id', $paymentId)
                        ->first();

                    if ($payment) {
                        if ($status === 'approved' && $payment->status !== 'completed') {
                            $payment->update([
                                'status' => 'completed',
                                'paid_at' => now(),
                            ]);
                            $payment->subscription?->update(['status' => 'active']);
                            $payment->createSplits();
                        } elseif (in_array($status, ['refunded', 'cancelled', 'rejected'])) {
                            $payment->update(['status' => 'refunded']);
                            $payment->subscription?->update(['status' => 'cancelled']);
                        }
                    }
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * Página de éxito después del pago
     */
    public function success(Subscription $subscription)
    {
        return view('subscription.success', compact('subscription'));
    }

    /**
     * Detectar moneda según ubicación del usuario
     */
    protected function detectCurrency(): string
    {
        // En producción, usar un servicio de geolocalización
        // Por ahora, usar USD como default
        $country = request()->header('CF-IPCountry', 'US');

        return config("payments.country_currency.{$country}", 'USD');
    }

    /**
     * Obtener proveedor de pago según moneda
     */
    protected function getProviderForCurrency(string $currency): string
    {
        $paypalCurrencies = config('payments.currencies.paypal', []);
        $mpCurrencies = config('payments.currencies.mercadopago', []);

        if (in_array($currency, $mpCurrencies)) {
            return 'mercadopago';
        }

        return 'paypal';
    }
}
