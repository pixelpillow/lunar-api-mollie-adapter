<?php

namespace Pixelpillow\LunarApiMollieAdapter\Domain\Payments\Data;

use Dystcz\LunarApi\Domain\Payments\Contracts\PaymentIntent as PaymentIntentContract;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;

class PaymentIntent implements PaymentIntentContract
{
    /**
     * @param  array<string,mixed>  $meta
     */
    public function __construct(
        public readonly mixed $intent,
        public array $meta = [],
    ) {
    }

    /**
     * Get ID.
     */
    public function getId(): mixed
    {
        return $this->intent->id;
    }

    // id: $molliePayment->id,
    // status: $molliePayment->status,
    // amount: MollieManager::normalizeAmountToInteger($molliePayment->amount->value),

    /**
     * Get amount.
     */
    public function getAmount(): int
    {
        return MollieManager::normalizeAmountToInteger($this->intent->amount->value);
    }

    /**
     * Get status.
     */
    public function getStatus(): string
    {
        return $this->intent->status;
    }

    /**
     * Get client secret.
     */
    public function getClientSecret(): string
    {
        return '';
    }

    /**
     * Get meta.
     *
     * @return array<string,mixed>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'amount' => $this->getAmount(),
            'status' => $this->getStatus(),
            'meta' => $this->getMeta(),
        ];
    }
}
