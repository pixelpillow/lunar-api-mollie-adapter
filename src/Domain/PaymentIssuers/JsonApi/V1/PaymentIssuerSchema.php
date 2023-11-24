<?php

namespace Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\JsonApi\V1;

use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Schema;
use Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\Models\PaymentIssuer;

class PaymentIssuerSchema extends Schema
{
    /**
     * {@inheritDoc}
     */
    public static string $model = PaymentIssuer::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('resource'),
            Str::make('name'),
            Str::make('issuer_id'),
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
        return 'payment-issuers';
    }
}
