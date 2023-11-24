<?php

namespace Pixelpillow\LunarApiMollieAdapter\JsonApi\V1;

use LaravelJsonApi\Core\Server\Server as BaseServer;
use Pixelpillow\LunarApiMollieAdapter\Domain\Lunar\Mollie\PaymentMethods\JsonApi\V1\PaymentMethodSchema;
use Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\JsonApi\V1\PaymentIssuerSchema;

class Server extends BaseServer
{
    /**
     * The base URI namespace for this server.
     */
    protected string $baseUri = '/api/v1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     */
    public function serving(): void
    {
        // no-op
    }

    /**
     * Get the server's list of schemas.
     */
    protected function allSchemas(): array
    {
        return [
            PaymentIssuerSchema::class,
            PaymentMethodSchema::class,
        ];
    }
}
