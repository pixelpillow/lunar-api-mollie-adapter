<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Pixelpillow\LunarApiMollieAdapter\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can get mollie payment methods', function () {

    $paymentIssuers = file_get_contents(__DIR__.'../../Stubs/Mollie/PaymentIssuers.json');

    Http::fake(
        ['https://api.mollie.com/v2/*' => Http::response(
            $paymentIssuers,
            200,
        )]
    );

    $response = $this
        ->jsonApi()
        ->expects('payment-methods')
        ->withData([
            'type' => 'payment-methods',
        ])
        ->get('/api/v1/payment-methods');

    $json = $response->json();

    // $response->assertStatusCode(200);

    $this->assertArrayHasKey('data', $json);

    $this->assertEquals('payment-methods', $json['data'][0]['type']);

    $this->assertEquals('ideal', $json['data'][0]['id']);
});
