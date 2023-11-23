<?php

namespace Pixelpillow\LunarApiMollieAdapter\Generators;

use Lunar\Models\Cart;

abstract class BaseUrlGenerator
{
    /**
     * The cart to generate the URL for.
     *
     * @var Cart
     */
    protected $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Generate the URL.
     */
    abstract public function generate(): string;

    /**
     * Get the cart.
     */
    public function getCart(): Cart
    {
        return $this->cart;
    }
}
