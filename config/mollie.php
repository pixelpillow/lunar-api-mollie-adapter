<?php

return [
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
