<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mollie payment driver
    |--------------------------------------------------------------------------
    |
    | Drivers for lunar are set in lunar.payments.types.
    | When mollie is set as a driver, this adapter will be used.
    |
    */
    'driver' => 'mollie',

    /*
    |--------------------------------------------------------------------------
    | Mollie payment type
    |--------------------------------------------------------------------------
    |
    | This key serves is an identification for this adapter.
    | That means that stripe driver is handled by this adapter if configured.
    |
    */
    'type' => 'mollie',

    /*
    |--------------------------------------------------------------------------
    | Mollie API key
    |--------------------------------------------------------------------------
    |
    | The Mollie API key for your website. You can find it in your
    | Mollie dashboard. It starts with 'test_' or 'live_'.
    |
    */
    'mollie_key' => env('MOLLIE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URL after payment success generator
    |--------------------------------------------------------------------------
    |
    | This generator is used to generate the redirect URL after a successful
    | payment. This generator is instantiated with the current Lunar Cart and
    | Transaction.
    |
    */
    'redirect_url_generator' => \Pixelpillow\LunarApiMollieAdapter\Generators\RedirectOnSuccessUrlGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Cancel URL after payment failure generator
    |--------------------------------------------------------------------------
    |
    | This generator is used to generate the cancel URL after a failed payment.
    | This generator is instantiated with the current Lunar Cart and
    | Transaction.
    |
    */
    'cancel_url_generator' => \Pixelpillow\LunarApiMollieAdapter\Generators\RedirectOnFailureUrlGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Payment description generator
    |--------------------------------------------------------------------------
    |
    | This generator is used to generate the payment description. This generator
    | is instantiated with the current Lunar Cart.
    |
    */
    'payment_description_generator' => \Pixelpillow\LunarApiMollieAdapter\Generators\PaymentIntentDescriptionGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Testing Webhook URL
    |--------------------------------------------------------------------------
    |
    | This URL is used when running tests. It is used to test the webhook
    | functionality.
    |
    */
    'webhook_url_testing' => env('MOLLIE_WEBHOOK_URL_TESTING'),

    /*
    |--------------------------------------------------------------------------
    | Payment status mappings
    |--------------------------------------------------------------------------
    |
    | The payment statuses you receive from Mollie will be mapped to the statuses
    | of your orders using the mapping below. Ideally, the values on the right
    | hand side should also be present in your lunar/orders.php config file.
    */
    'payment_status_mappings' => [
        'open' => 'payment-open',
        'canceled' => 'payment-canceled',
        'pending' => 'payment-pending',
        'expired' => 'payment-expired',
        'failed' => 'payment-failed',
        'paid' => 'payment-received',
        'refunded' => 'payment-refunded',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allow order status update
    |--------------------------------------------------------------------------
    |
    | This setting allows the adapter to update the order status when the payment
    | status changes. If set to false, the order status will not be updated.
    |
    */
    'allow_order_status_update' => true,

    /*
    |--------------------------------------------------------------------------
    | Lunar Api Domain Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is used to register the domain in the Lunar API.
    |
    */
    'domains' => [
        'payment-issuers' => [
            'model' => \Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\Models\PaymentIssuer::class,
            'lunar_model' => null,
            'policy' => null,
            'schema' => \Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\JsonApi\V1\PaymentIssuerSchema::class,
            'resource' => null,
            'query' => null,
            'collection_query' => null,
            'routes' => \Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\Http\Routing\PaymentIssuerRouteGroup::class,
            'route_actions' => [],
        ],
        'payment-methods' => [
            'model' => \Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\Models\PaymentMethod::class,
            'lunar_model' => null,
            'policy' => null,
            'schema' => \Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\JsonApi\V1\PaymentMethodSchema::class,
            'resource' => null,
            'query' => null,
            'collection_query' => null,
            'routes' => \Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\Http\Routing\PaymentMethodRouteGroup::class,
            'route_actions' => [],
        ],
    ],
];
