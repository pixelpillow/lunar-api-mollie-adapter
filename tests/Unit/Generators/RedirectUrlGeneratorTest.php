<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;
use Pixelpillow\LunarApiMollieAdapter\Tests\TestCase;
use Pixelpillow\LunarApiMollieAdapter\Tests\Utils\CartBuilder;

uses(TestCase::class, RefreshDatabase::class);

test('Redirect is generate as expected', function () {
    $cart = CartBuilder::build();

    $url = MollieManager::getRedirectUrl($cart);

    $this->assertEquals('https://example.com/confirmation?orderId='.$cart->id, $url);
});
