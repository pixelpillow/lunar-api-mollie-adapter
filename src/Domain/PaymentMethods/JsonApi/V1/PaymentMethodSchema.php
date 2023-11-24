<?php

namespace Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\JsonApi\V1;

use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Schema;
use Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\Models\PaymentMethod;

class PaymentMethodSchema extends Schema
{
    /**
     * {@inheritDoc}
     */
    public static string $model = PaymentMethod::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name'),
            Str::make('method_id'),
            ArrayList::make('image'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function authorizable(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'payment-methods';
    }
}
