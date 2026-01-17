<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    */
    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),

        // URLs base según el modo
        'base_url' => env('PAYPAL_MODE', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mercado Pago Configuration
    |--------------------------------------------------------------------------
    */
    'mercadopago' => [
        'app_id' => env('MERCADOPAGO_APP_ID'),
        'client_secret' => env('MERCADOPAGO_CLIENT_SECRET'),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monedas soportadas por proveedor
    |--------------------------------------------------------------------------
    */
    'currencies' => [
        'paypal' => ['USD', 'EUR'], // PayPal para pagos internacionales
        'mercadopago' => ['CLP', 'PEN', 'BRL'], // MercadoPago para LATAM
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapeo de país a moneda preferida
    |--------------------------------------------------------------------------
    */
    'country_currency' => [
        'CL' => 'CLP',
        'PE' => 'PEN',
        'BR' => 'BRL',
        'US' => 'USD',
        'default' => 'USD',
    ],
];
