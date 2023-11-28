<?php

namespace Pixelpillow\LunarApiMollieAdapter\Generators;

abstract class RedirectOnFailureUrlGenerator extends BaseUrlGenerator
{
    /**
     * Generate the webhook URL.
     */
    abstract public function generate(): string;
}
