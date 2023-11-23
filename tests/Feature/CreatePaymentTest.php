<?php

use Dystcz\LunarApi\Domain\Carts\Events\CartCreated;
use Dystcz\LunarApi\Domain\Carts\Models\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Lunar\Facades\CartSession;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
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

test('a payment intent can be created', function (string $paymentMethod) {
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
                'payment_method' => $paymentMethod,
                'meta' => [
                    'payment_method_type' => 'ideal',
                    'payment_method_issuer' => 'ideal_ABNANL2A',
                ],
            ],
        ])
        ->post($url);

    $response->assertSuccessful();

    expect($response->json('meta.payment_intent.meta.mollie_checkout_url'))->toBeString();

})->with(['mollie']);
