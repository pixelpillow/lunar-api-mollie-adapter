<?php

namespace Pixelpillow\LunarApiMollieAdapter\Generators;

class PaymentIntentDescriptionGenerator extends BaseUrlGenerator
{
    /**
     * Generate the webhook URL.
     */
    public function generate(): string
    {
        return 'Payment for order #'.$this->cart->id;
    }
}
