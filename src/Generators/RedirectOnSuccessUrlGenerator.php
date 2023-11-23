<?php

namespace Pixelpillow\LunarApiMollieAdapter\Generators;

abstract class RedirectOnSuccessUrlGenerator extends BaseUrlGenerator
{
    /**
     * Generate the webhook URL.
     */
    abstract public function generate(): string;
}
