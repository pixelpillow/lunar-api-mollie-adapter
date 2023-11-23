<?php

namespace Pixelpillow\LunarApiMollieAdapter\Tests\Stubs;

use Pixelpillow\LunarApiMollieAdapter\Generators\RedirectOnSuccessUrlGenerator;

class TestRedirectGenerator extends RedirectOnSuccessUrlGenerator
{
    /**
     * Generate the webhook URL.
     */
    public function generate(): string
    {
        return 'https://example.com/confirmation?orderId='.$this->getCart()->id;
    }
}
