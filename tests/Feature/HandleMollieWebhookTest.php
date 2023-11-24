<?php

use Dystcz\LunarApi\Domain\Carts\Events\CartCreated;
use Dystcz\LunarApi\Domain\Carts\Models\Cart;
use Dystcz\LunarApi\Domain\Orders\Events\OrderPaid;
use Dystcz\LunarApi\Domain\Orders\Events\OrderPaymentCanceled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Lunar\Facades\CartSession;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Types\PaymentStatus;
use Pixelpillow\LunarApiMollieAdapter\MolliePaymentAdapter;
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

it('can handle succeeded event', function () {
    /** @var TestCase $this */
    Event::fake(OrderPaid::class);

    $mollieMockPayment = new Payment(app(MollieApiClient::class));
    $mollieMockPayment->id = uniqid('tr_');
    $mollieMockPayment->status = PaymentStatus::STATUS_PAID;
    $mollieMockPayment->paidAt = now()->toIso8601String();
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

    $intent = App::make(MolliePaymentAdapter::class)->createIntent($this->cart, [
        'payment_method_type' => 'ideal',
        'payment_method_issuer' => 'ideal_ABNANL2A',
    ]);

    $this->intent = $intent;

    $response = $this
        ->post(
            '/mollie/webhook',
            [
                'id' => $this->intent->id,
            ],
        );

    $response->assertSuccessful();

    Event::assertDispatched(OrderPaid::class);
});

it('can handle canceled event', function () {
    /** @var TestCase $this */
    Event::fake(OrderPaymentCanceled::class);

    $mollieMockPayment = new Payment(app(MollieApiClient::class));
    $mollieMockPayment->id = uniqid('tr_');
    $mollieMockPayment->status = PaymentStatus::STATUS_CANCELED;
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

    $intent = App::make(MolliePaymentAdapter::class)->createIntent($this->cart, [
        'payment_method_type' => 'ideal',
        'payment_method_issuer' => 'ideal_ABNANL2A',
    ]);

    $this->intent = $intent;

    $response = $this
        ->post(
            '/mollie/webhook',
            [
                'id' => $this->intent->id,
            ],
        );

    $response->assertSuccessful();

    Event::assertDispatched(OrderPaymentCanceled::class);
});

it('can handle failed event', function () {
    /** @var TestCase $this */
    Event::fake(OrderPaymentFailed::class);

    $mollieMockPayment = new Payment(app(MollieApiClient::class));
    $mollieMockPayment->id = uniqid('tr_');
    $mollieMockPayment->status = PaymentStatus::STATUS_FAILED;
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

    $intent = App::make(MolliePaymentAdapter::class)->createIntent($this->cart, [
        'payment_method_type' => 'ideal',
        'payment_method_issuer' => 'ideal_ABNANL2A',
    ]);

    $this->intent = $intent;

    $response = $this
        ->post(
            '/mollie/webhook',
            [
                'id' => $this->intent->id,
            ],
        );

    $response->assertSuccessful();

    Event::assertDispatched(OrderPaymentCanceled::class);
});
