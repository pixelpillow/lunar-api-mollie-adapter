<?php

namespace Pixelpillow\LunarApiMollieAdapter\JsonApi\V1;

use LaravelJsonApi\Core\Server\Server as BaseServer;
use Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\JsonApi\V1\PaymentMethodSchema;

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
            PaymentMethodSchema::class,
        ];
    }
}
