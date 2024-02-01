<?php

use Dystcz\LunarApi\Domain\Carts\Events\CartCreated;
use Dystcz\LunarApi\Domain\Carts\Models\Cart;
use Dystcz\LunarApi\Domain\Orders\Events\OrderPaid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Lunar\Facades\CartSession;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Types\PaymentMethod;
use Mollie\Api\Types\PaymentStatus;
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

    Event::fake(OrderPaid::class);

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

    Event::fake(OrderPaid::class);

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

    Event::fake(OrderPaid::class);

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

    $amount = 2000;

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
