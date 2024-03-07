<?php

namespace Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\Http\Routing;

use Dystcz\LunarApi\Routing\RouteGroup;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\Http\Controllers\PaymentIssuerController;

class PaymentIssuerRouteGroup extends RouteGroup
{
    /**
     * Register routes.
     */
    public function routes(): void
    {
        JsonApiRoute::server('v1')
            ->prefix('v1')
            ->resources(function ($server) {
                $server->resource($this->getPrefix(), PaymentIssuerController::class)
                    ->only('index');
            });
    }
}
