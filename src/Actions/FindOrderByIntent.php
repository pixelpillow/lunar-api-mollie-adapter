<?php

namespace Pixelpillow\LunarApiMollieAdapter\Actions;

use Dystore\Api\Domain\Orders\Actions\FindOrderByIntent as ActionsFindOrderByIntent;
use Dystore\Api\Domain\Orders\Models\Order;
use Dystore\Api\Domain\Payments\Contracts\PaymentIntent;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FindOrderByIntent extends ActionsFindOrderByIntent
{
    /**
     * Find order by intent.
     *
     * @throws ModelNotFoundException
     */
    public function __invoke(PaymentIntent $intent): Order
    {
        return Order::query()
            ->whereHas(
                'transactions',
                fn ($query) => $query
                    ->where('type', 'intent')
                    ->where('reference', $intent->id),
            )
            ->firstOrFail();
    }
}
