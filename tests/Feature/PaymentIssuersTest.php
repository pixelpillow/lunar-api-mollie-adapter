<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Pixelpillow\LunarApiMollieAdapter\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can get mollie payment issuers', function () {

    $paymentIssuers = file_get_contents(__DIR__.'../../Stubs/Mollie/PaymentIssuers.json');

    Http::fake(
        ['https://api.mollie.com/v2/methods?include=issuers' => Http::response(
            $paymentIssuers,
            200,
        )]
    );

    $response = $this
        ->jsonApi()
        ->expects('payment-issuers')
        ->withData([
            'type' => 'payment-issuers',
        ])
        ->get('/api/v1/payment-issuers');

    $response->assertStatusCode(200);

    $json = $response->json();

    $this->assertArrayHasKey('data', $json);

    $this->assertEquals('payment-issuers', $json['data'][0]['type']);
    $this->assertEquals('ideal_ABNANL2A', $json['data'][0]['id']);
});
