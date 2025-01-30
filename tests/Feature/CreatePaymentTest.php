<?php

use Dystcz\LunarApi\Domain\Carts\Events\CartCreated;
use Dystcz\LunarApi\Domain\Carts\Models\Cart;
use Dystcz\LunarApi\Domain\Orders\Events\OrderPaymentSuccessful;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Lunar\Facades\CartSession;
use Lunar\Models\Currency;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Types\PaymentMethod;
use Mollie\Api\Types\PaymentStatus;
use Pixelpillow\LunarApiMollieAdapter\Generators\PaymentIntentDescriptionGenerator;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;
use Pixelpillow\LunarApiMollieAdapter\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    /** @var TestCase $this */
    Event::fake(CartCreated::class);

    /** @var Cart $cart */
    $cart = Cart::factory()
        ->withAddresses()
        ->withLines()
        ->create();

    CartSession::use($cart);

    $this->order = $cart->createOrder();
    $this->cart = $cart;
});

test('a ideal payment intent can be created', function () {
    /** @var TestCase $this */
    $url = URL::signedRoute(
        'v1.orders.createPaymentIntent',
        [
            'order' => $this->order->getRouteKey(),
        ],
    );

    Event::fake(OrderPaymentSuccessful::class);

    $mollieMockPayment = new Payment(app(MollieApiClient::class));
    $mollieMockPayment->id = uniqid('tr_');
    $mollieMockPayment->status = PaymentStatus::STATUS_PAID;
    $mollieMockPayment->method = PaymentMethod::IDEAL;
    $mollieMockPayment->amount = [
        'value' => '10.00',
        'currency' => 'EUR',
    ];

    $mollieMockPayment->_links = [
        'checkout' => [
            'href' => 'https://www.mollie.com/checkout/test-mode?method=ideal&token=6.5gwscs',
        ],
    ];

    Http::fake([
        'https://api.mollie.com/*' => Http::response(json_encode($mollieMockPayment)),
    ]);

    $response = $this
        ->jsonApi()
        ->expects('orders')
        ->withData([
            'type' => 'orders',
            'id' => (string) $this->order->getRouteKey(),
            'attributes' => [
                'payment_method' => 'mollie',
                'meta' => [
                    'payment_method_type' => PaymentMethod::IDEAL,
                    'payment_method_issuer' => 'ideal_ABNANL2A',
                ],
            ],
        ])
        ->post($url);

    $response->assertSuccessful();

    // Expect a checkout url to be returned
    expect($response->json('meta.payment_intent.meta.mollie_checkout_url'))->toBeString();

    // Expect a transaction to be created
    $this->assertDatabaseHas('lunar_transactions', [
        'order_id' => $this->order->id,
        'card_type' => PaymentMethod::IDEAL,
    ]);

});

test('a Bankcontact payment intent can be created', function () {
    /** @var TestCase $this */
    $url = URL::signedRoute(
        'v1.orders.createPaymentIntent',
        [
            'order' => $this->order->getRouteKey(),
        ],
    );

    Event::fake(OrderPaymentSuccessful::class);

    $mollieMockPayment = new Payment(app(MollieApiClient::class));
    $mollieMockPayment->id = uniqid('tr_');
    $mollieMockPayment->status = PaymentStatus::STATUS_PAID;
    $mollieMockPayment->method = PaymentMethod::BANCONTACT;
    $mollieMockPayment->amount = [
        'value' => '10.00',
        'currency' => 'EUR',
    ];

    $mollieMockPayment->_links = [
        'checkout' => [
            'href' => 'https://www.mollie.com/checkout/test-mode?method=ideal&token=6.5gwscs',
        ],
    ];

    Http::fake([
        'https://api.mollie.com/*' => Http::response(json_encode($mollieMockPayment)),
    ]);

    $response = $this
        ->jsonApi()
        ->expects('orders')
        ->withData([
            'type' => 'orders',
            'id' => (string) $this->order->getRouteKey(),
            'attributes' => [
                'payment_method' => 'mollie',
                'meta' => [
                    'payment_method_type' => PaymentMethod::BANCONTACT,
                ],
            ],
        ])
        ->post($url);

    $response->assertSuccessful();

    // Expect a checkout url to be returned
    expect($response->json('meta.payment_intent.meta.mollie_checkout_url'))->toBeString();

    // Expect a transaction to be created
    $this->assertDatabaseHas('lunar_transactions', [
        'order_id' => $this->order->id,
        'card_type' => PaymentMethod::BANCONTACT,
    ]);
});

test('a payment with a custom amount can be created', function () {
    /** @var TestCase $this */
    $url = URL::signedRoute(
        'v1.orders.createPaymentIntent',
        [
            'order' => $this->order->getRouteKey(),
        ],
    );

    Event::fake(OrderPaymentSuccessful::class);

    $mollieMockPayment = new Payment(app(MollieApiClient::class));
    $mollieMockPayment->id = uniqid('tr_');
    $mollieMockPayment->status = PaymentStatus::STATUS_PAID;
    $mollieMockPayment->method = PaymentMethod::IDEAL;
    $mollieMockPayment->amount = [
        'value' => '20.00',
        'currency' => 'EUR',
    ];

    $mollieMockPayment->_links = [
        'checkout' => [
            'href' => 'https://www.mollie.com/checkout/test-mode?method=ideal&token=6.5gwscs',
        ],
    ];

    Http::fake([
        'https://api.mollie.com/*' => Http::response(json_encode($mollieMockPayment)),
    ]);

    $currency = Currency::getDefault();

    $amount = MollieManager::normalizeAmountToInteger('20.00', $currency->code);

    $response = $this
        ->jsonApi()
        ->expects('orders')
        ->withData([
            'type' => 'orders',
            'id' => (string) $this->order->getRouteKey(),
            'attributes' => [
                'payment_method' => 'mollie',
                'amount' => $amount,
                'meta' => [
                    'payment_method_type' => PaymentMethod::IDEAL,
                    'payment_method_issuer' => 'ideal_ABNANL2A',
                ],
            ],
        ])
        ->post($url);

    $response->assertSuccessful();

    // Expect a checkout url to be returned
    expect($response->json('meta.payment_intent.meta.mollie_checkout_url'))->toBeString();

    // Expect a transaction to be created
    $this->assertDatabaseHas('lunar_transactions', [
        'order_id' => $this->order->id,
        'card_type' => PaymentMethod::IDEAL,
        'amount' => $amount,
    ]);
});

test('a payment with a currency with 4 decimals can be created', function () {
    /** @var TestCase $this */
    $url = URL::signedRoute(
        'v1.orders.createPaymentIntent',
        [
            'order' => $this->order->getRouteKey(),
        ],
    );

    Event::fake(OrderPaymentSuccessful::class);

    $mollieMockPayment = new Payment(app(MollieApiClient::class));
    $mollieMockPayment->id = uniqid('tr_');
    $mollieMockPayment->status = PaymentStatus::STATUS_PAID;
    $mollieMockPayment->method = PaymentMethod::IDEAL;
    $mollieMockPayment->amount = [
        'value' => '20.00',
        'currency' => 'USD',
    ];

    $mollieMockPayment->_links = [
        'checkout' => [
            'href' => 'https://www.mollie.com/checkout/test-mode?method=ideal&token=6.5gwscs',
        ],
    ];

    Http::fake([
        'https://api.mollie.com/*' => Http::response(json_encode($mollieMockPayment)),
    ]);

    Currency::factory()->create([
        'code' => 'USD',
        'decimal_places' => 4,
    ]);

    $amount = MollieManager::normalizeAmountToInteger('20.00', 'USD');

    $response = $this
        ->jsonApi()
        ->expects('orders')
        ->withData([
            'type' => 'orders',
            'id' => (string) $this->order->getRouteKey(),
            'attributes' => [
                'payment_method' => 'mollie',
                'amount' => $amount,
                'meta' => [
                    'payment_method_type' => PaymentMethod::IDEAL,
                    'payment_method_issuer' => 'ideal_ABNANL2A',
                ],
            ],
        ])
        ->post($url);

    $response->assertSuccessful();

    // Expect a checkout url to be returned
    expect($response->json('meta.payment_intent.meta.mollie_checkout_url'))->toBeString();

    // Expect a transaction to be created
    $this->assertDatabaseHas('lunar_transactions', [
        'order_id' => $this->order->id,
        'card_type' => PaymentMethod::IDEAL,
        'amount' => $amount,
    ]);
});

test('can normalize amount to integer', function () {
    $currency = Currency::getDefault();

    $amount = MollieManager::normalizeAmountToInteger('20.00', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(2000);

    $amount = MollieManager::normalizeAmountToInteger('20.96', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(2096);

    $amount = MollieManager::normalizeAmountToInteger('20.60', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(2060);

    $amount = MollieManager::normalizeAmountToInteger('20.6', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(2060);

    $amount = MollieManager::normalizeAmountToInteger('20', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(2000);

    $amount = MollieManager::normalizeAmountToInteger('20.00', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(2000);
});

test('can normalize amount to integer for a currency with 4 decimals', function () {
    $currency = Currency::factory()->create([
        'code' => 'USD',
        'decimal_places' => 4,
    ]);

    $amount = MollieManager::normalizeAmountToInteger('20.00', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(200000);

    $amount = MollieManager::normalizeAmountToInteger('20.96', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(209600);

    $amount = MollieManager::normalizeAmountToInteger('20.60', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(206000);

    $amount = MollieManager::normalizeAmountToInteger('20', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(200000);

    $amount = MollieManager::normalizeAmountToInteger('20.6', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(206000);

    $amount = MollieManager::normalizeAmountToInteger('20.00', $currency->code);
    expect($amount)->toBeInt();
    expect($amount)->toBe(200000);
});

test('can normalize amount to string', function () {
    $amount = MollieManager::normalizeAmountToString(2000);
    expect($amount)->toBeString();
    expect($amount)->toBe('20.00');

    $amount = MollieManager::normalizeAmountToString(2096);
    expect($amount)->toBeString();
    expect($amount)->toBe('20.96');

    $amount = MollieManager::normalizeAmountToString(2060);
    expect($amount)->toBeString();
    expect($amount)->toBe('20.60');
});

test('can normalize amount to string for a currency with 4 decimals', function () {
    $amount = MollieManager::normalizeAmountToString(200000, 4);
    expect($amount)->toBeString();
    expect($amount)->toBe('20.00');

    $amount = MollieManager::normalizeAmountToString(209600, 4);
    expect($amount)->toBeString();
    expect($amount)->toBe('20.96');

    $amount = MollieManager::normalizeAmountToString(206000, 4);
    expect($amount)->toBeString();
    expect($amount)->toBe('20.60');
});

test('can generate a payment intent description', function () {
    $cart = Cart::factory()->create();

    $generator = new PaymentIntentDescriptionGenerator($cart);

    $description = $generator->generate();

    expect($description)->toBeString();
    expect($description)->toBe('Payment for order #'.$cart->id);
});
