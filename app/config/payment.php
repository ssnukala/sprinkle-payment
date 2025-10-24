<?php

/*
 * UserFrosting Payment Sprinkle Configuration
 *
 * Configuration for payment gateways and settings
 */

return [
    'payment' => [
        // Enabled payment methods
        'enabled_methods' => [
            'ST' => 'stripe',
            'PP' => 'paypal',
            'AP' => 'apple_pay',
            'GP' => 'google_pay',
            'MC' => 'manual_check',
        ],
        'status' =>
        [
            'PP' => 'Pending Payment',
            'AU' => 'Authorized',
            'CA' => 'Captured',
            'CO' => 'Completed',
            'FA' => 'Failed',
            'RE' => 'Refunded',
            'CN' => 'Cancelled',
        ],

        // Default currency
        'default_currency' => 'USD',

        // Stripe configuration
        'stripe' => [
            'public_key' => getenv('STRIPE_PUBLIC_KEY'),
            'secret_key' => getenv('STRIPE_SECRET_KEY'),
            'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET'),
        ],

        // PayPal configuration
        'paypal' => [
            'client_id' => getenv('PAYPAL_CLIENT_ID'),
            'client_secret' => getenv('PAYPAL_CLIENT_SECRET'),
            'mode' => getenv('PAYPAL_MODE') ?: 'sandbox', // 'sandbox' or 'live'
        ],

        // Apple Pay configuration
        'apple_pay' => [
            'merchant_id' => getenv('APPLE_PAY_MERCHANT_ID'),
            'certificate_path' => getenv('APPLE_PAY_CERTIFICATE_PATH'),
        ],

        // Google Pay configuration
        'google_pay' => [
            'merchant_id' => getenv('GOOGLE_PAY_MERCHANT_ID'),
            'merchant_name' => getenv('GOOGLE_PAY_MERCHANT_NAME'),
        ],

        // Manual check settings
        'manual_check' => [
            'require_approval' => true,
        ],

        // Order settings
        'order' => [
            'number_prefix' => 'ORD-',
            'auto_complete_on_payment' => true,
        ],

        // Payment settings
        'payment' => [
            'number_prefix' => 'PAY-',
            'allow_partial_payments' => true,
            'allow_refunds' => true,
        ],
    ],
];