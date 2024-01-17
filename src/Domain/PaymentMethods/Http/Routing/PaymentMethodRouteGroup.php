<?php

namespace Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\Http\Routing;

use Dystcz\LunarApi\Routing\RouteGroup;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\Http\Controllers\PaymentMethodController;

class PaymentMethodRouteGroup extends RouteGroup
{
    /**
     * Register routes.
     */
    public function routes(?string $prefix = null, array|string $middleware = []): void
    {
        JsonApiRoute::server('v1')
            ->prefix('v1')
            ->resources(function ($server) {
                $server->resource($this->getPrefix(), PaymentMethodController::class)
                    ->only('index');
            });
    }
}
