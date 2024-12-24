<?php

namespace Pixelpillow\LunarApiMollieAdapter\Tests\Utils;

use Lunar\Actions\Carts\AddOrUpdatePurchasable;
use Lunar\DataTypes\Price as PriceDataType;
use Lunar\DataTypes\ShippingOption;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;
use Lunar\Models\CartAddress;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

class CartBuilder
{
    public static function build(array $cartParams = [])
    {
        Language::factory()->create([
            'default' => true,
        ]);

        $currency = Currency::getDefault();

        $taxClass = TaxClass::factory()->create(
            [
                'default' => true,
            ]
        );

        $cart = Cart::factory()->create(array_merge([
            'currency_id' => $currency->id,
        ], $cartParams));

        ShippingManifest::addOption(
            new ShippingOption(
                name: 'Basic Delivery',
                description: 'Basic Delivery',
                identifier: 'BASDEL',
                price: new PriceDataType(500, $cart->currency, 1),
                taxClass: $taxClass
            )
        );

        CartAddress::factory()->create([
            'cart_id' => $cart->id,
            'shipping_option' => 'BASDEL',
            'country_id' => Country::factory()->state(['iso2' => 'NL', 'iso3' => 'NLD']),
        ]);

        CartAddress::factory()->create([
            'cart_id' => $cart->id,
            'type' => 'billing',
        ]);

        $purchasable = ProductVariant::factory()->create();

        Price::factory()->create([
            'price' => 100,
            'currency_id' => $currency->id,
            'priceable_type' => get_class($purchasable),
            'priceable_id' => $purchasable->id,
        ]);

        $action = new AddOrUpdatePurchasable;

        $action->execute($cart, $purchasable, 1);

        return $cart;
    }
}
