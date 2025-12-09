<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Global Payment Settings
    |--------------------------------------------------------------------------
    |
    | These settings apply to all payment gateways unless overridden.
    |
    */

    'currency' => env('PAYMENT_CURRENCY', 'USD'),

    'currency_symbol' => env('PAYMENT_CURRENCY_SYMBOL', '$'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Rates
    |--------------------------------------------------------------------------
    |
    | Exchange rates from your base currency to gateway-specific currencies.
    | These are used when a gateway doesn't support your base currency.
    |
    */

    'exchange_rates' => [
        'usd' => env('EXCHANGE_RATE_USD', 1.00),
        'inr' => env('EXCHANGE_RATE_INR', 83.00),
        'eur' => env('EXCHANGE_RATE_EUR', 0.92),
        'ngn' => env('EXCHANGE_RATE_NGN', 1550.00),
        'idr' => env('EXCHANGE_RATE_IDR', 15700.00),
        'zar' => env('EXCHANGE_RATE_ZAR', 18.50),
        'brl' => env('EXCHANGE_RATE_BRL', 4.97),
        'myr' => env('EXCHANGE_RATE_MYR', 4.72),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each supported payment gateway.
    | Credentials are stored in the database for multi-tenant support,
    | but defaults can be set via environment variables for single-tenant
    | deployments or fallback values.
    |
    */

    'gateways' => [

        /*
        |--------------------------------------------------------------------------
        | PayPal
        |--------------------------------------------------------------------------
        */
        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', false),
            'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
            'sandbox' => [
                'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
                'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
                'app_id' => env('PAYPAL_SANDBOX_APP_ID'),
            ],
            'live' => [
                'client_id' => env('PAYPAL_LIVE_CLIENT_ID'),
                'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET'),
                'app_id' => env('PAYPAL_LIVE_APP_ID'),
            ],
            'webhook_secret' => env('PAYPAL_WEBHOOK_SECRET'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Stripe
        |--------------------------------------------------------------------------
        */
        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
            'mode' => env('STRIPE_MODE', 'test'), // test or live
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Razorpay (India)
        |--------------------------------------------------------------------------
        */
        'razorpay' => [
            'enabled' => env('RAZORPAY_ENABLED', false),
            'mode' => env('RAZORPAY_MODE', 'test'),
            'api_key' => env('RAZORPAY_API_KEY'),
            'api_secret' => env('RAZORPAY_API_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Paytm (India)
        |--------------------------------------------------------------------------
        */
        'paytm' => [
            'enabled' => env('PAYTM_ENABLED', false),
            'mode' => env('PAYTM_MODE', 'test'),
            'merchant_id' => env('PAYTM_MERCHANT_ID'),
            'merchant_key' => env('PAYTM_MERCHANT_KEY'),
            'merchant_website' => env('PAYTM_MERCHANT_WEBSITE', 'WEBSTAGING'),
            'channel' => env('PAYTM_CHANNEL', 'WEB'),
            'industry_type' => env('PAYTM_INDUSTRY_TYPE', 'Retail'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Paystack (Africa)
        |--------------------------------------------------------------------------
        */
        'paystack' => [
            'enabled' => env('PAYSTACK_ENABLED', false),
            'mode' => env('PAYSTACK_MODE', 'test'),
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'merchant_email' => env('PAYSTACK_MERCHANT_EMAIL'),
            'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Flutterwave (Africa)
        |--------------------------------------------------------------------------
        */
        'flutterwave' => [
            'enabled' => env('FLUTTERWAVE_ENABLED', false),
            'mode' => env('FLUTTERWAVE_MODE', 'test'),
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Mollie (Europe)
        |--------------------------------------------------------------------------
        */
        'mollie' => [
            'enabled' => env('MOLLIE_ENABLED', false),
            'mode' => env('MOLLIE_MODE', 'test'),
            'api_key' => env('MOLLIE_API_KEY'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Midtrans (Indonesia)
        |--------------------------------------------------------------------------
        */
        'midtrans' => [
            'enabled' => env('MIDTRANS_ENABLED', false),
            'mode' => env('MIDTRANS_MODE', 'test'),
            'server_key' => env('MIDTRANS_SERVER_KEY'),
            'client_key' => env('MIDTRANS_CLIENT_KEY'),
        ],

        /*
        |--------------------------------------------------------------------------
        | PayFast (South Africa)
        |--------------------------------------------------------------------------
        */
        'payfast' => [
            'enabled' => env('PAYFAST_ENABLED', false),
            'mode' => env('PAYFAST_MODE', 'test'),
            'merchant_id' => env('PAYFAST_MERCHANT_ID'),
            'merchant_key' => env('PAYFAST_MERCHANT_KEY'),
            'passphrase' => env('PAYFAST_PASSPHRASE'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Cashfree (India)
        |--------------------------------------------------------------------------
        */
        'cashfree' => [
            'enabled' => env('CASHFREE_ENABLED', false),
            'mode' => env('CASHFREE_MODE', 'test'),
            'app_id' => env('CASHFREE_APP_ID'),
            'secret_key' => env('CASHFREE_SECRET_KEY'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Instamojo (India)
        |--------------------------------------------------------------------------
        */
        'instamojo' => [
            'enabled' => env('INSTAMOJO_ENABLED', false),
            'mode' => env('INSTAMOJO_MODE', 'test'),
            'client_id' => env('INSTAMOJO_CLIENT_ID'),
            'client_secret' => env('INSTAMOJO_CLIENT_SECRET'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Mercado Pago (Latin America)
        |--------------------------------------------------------------------------
        */
        'mercadopago' => [
            'enabled' => env('MERCADOPAGO_ENABLED', false),
            'mode' => env('MERCADOPAGO_MODE', 'test'),
            'client_id' => env('MERCADOPAGO_CLIENT_ID'),
            'client_secret' => env('MERCADOPAGO_CLIENT_SECRET'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Square (Global)
        |--------------------------------------------------------------------------
        */
        'squareup' => [
            'enabled' => env('SQUAREUP_ENABLED', false),
            'mode' => env('SQUAREUP_MODE', 'sandbox'),
            'location_id' => env('SQUAREUP_LOCATION_ID'),
            'access_token' => env('SQUAREUP_ACCESS_TOKEN'),
            'application_id' => env('SQUAREUP_APPLICATION_ID'),
        ],

        /*
        |--------------------------------------------------------------------------
        | CinetPay (Africa - Francophone)
        |--------------------------------------------------------------------------
        */
        'cinetpay' => [
            'enabled' => env('CINETPAY_ENABLED', false),
            'mode' => env('CINETPAY_MODE', 'test'),
            'api_key' => env('CINETPAY_API_KEY'),
            'site_id' => env('CINETPAY_SITE_ID'),
        ],

        /*
        |--------------------------------------------------------------------------
        | PayTabs (Middle East)
        |--------------------------------------------------------------------------
        */
        'paytabs' => [
            'enabled' => env('PAYTABS_ENABLED', false),
            'mode' => env('PAYTABS_MODE', 'test'),
            'profile_id' => env('PAYTABS_PROFILE_ID'),
            'server_key' => env('PAYTABS_SERVER_KEY'),
            'region' => env('PAYTABS_REGION', 'ARE'), // ARE, SAU, EGY, OMN, JOR, GLOBAL
        ],

        /*
        |--------------------------------------------------------------------------
        | Billplz (Malaysia)
        |--------------------------------------------------------------------------
        */
        'billplz' => [
            'enabled' => env('BILLPLZ_ENABLED', false),
            'mode' => env('BILLPLZ_MODE', 'test'),
            'api_key' => env('BILLPLZ_API_KEY'),
            'collection_name' => env('BILLPLZ_COLLECTION_NAME'),
            'x_signature' => env('BILLPLZ_X_SIGNATURE'),
            'version' => env('BILLPLZ_VERSION', 'v4'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Zitopay (Global)
        |--------------------------------------------------------------------------
        */
        'zitopay' => [
            'enabled' => env('ZITOPAY_ENABLED', false),
            'mode' => env('ZITOPAY_MODE', 'test'),
            'username' => env('ZITOPAY_USERNAME'),
        ],

        /*
        |--------------------------------------------------------------------------
        | ToyyibPay (Malaysia)
        |--------------------------------------------------------------------------
        */
        'toyyibpay' => [
            'enabled' => env('TOYYIBPAY_ENABLED', false),
            'mode' => env('TOYYIBPAY_MODE', 'test'),
            'user_secret_key' => env('TOYYIBPAY_USER_SECRET_KEY'),
            'category_code' => env('TOYYIBPAY_CATEGORY_CODE'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Pagali Pay
        |--------------------------------------------------------------------------
        */
        'pagali' => [
            'enabled' => env('PAGALI_ENABLED', false),
            'mode' => env('PAGALI_MODE', 'test'),
            'page_id' => env('PAGALI_PAGE_ID'),
            'entity_id' => env('PAGALI_ENTITY_ID'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Authorize.Net (USA)
        |--------------------------------------------------------------------------
        */
        'authorizenet' => [
            'enabled' => env('AUTHORIZENET_ENABLED', false),
            'mode' => env('AUTHORIZENET_MODE', 'test'),
            'merchant_login_id' => env('AUTHORIZENET_MERCHANT_LOGIN_ID'),
            'merchant_transaction_key' => env('AUTHORIZENET_MERCHANT_TRANSACTION_KEY'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Sitesway
        |--------------------------------------------------------------------------
        */
        'sitesway' => [
            'enabled' => env('SITESWAY_ENABLED', false),
            'mode' => env('SITESWAY_MODE', 'test'),
            'brand_id' => env('SITESWAY_BRAND_ID'),
            'api_key' => env('SITESWAY_API_KEY'),
        ],

        /*
        |--------------------------------------------------------------------------
        | KineticPay (Malaysia)
        |--------------------------------------------------------------------------
        */
        'kineticpay' => [
            'enabled' => env('KINETICPAY_ENABLED', false),
            'mode' => env('KINETICPAY_MODE', 'test'),
            'merchant_key' => env('KINETICPAY_MERCHANT_KEY'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Global webhook configuration for payment notifications.
    |
    */

    'webhooks' => [
        'route_prefix' => 'webhooks/payment',
        'middleware' => ['api'],
        'tolerance' => 300, // Webhook timestamp tolerance in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    */

    'transactions' => [
        'prefix' => env('TRANSACTION_PREFIX', 'TXN'),
        'log_all' => env('PAYMENT_LOG_ALL', true),
    ],

];

