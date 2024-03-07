<?php

namespace Pixelpillow\LunarApiMollieAdapter;

use Illuminate\Support\Facades\Config;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Models\Transaction;
use Lunar\PaymentTypes\AbstractPayment;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Api\Types\RefundStatus;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;

class MolliePaymentType extends AbstractPayment
{
    /**
     * The Mollie Payment
     */
    protected Payment $molliePayment;

    public function __construct(protected MollieManager $mollie)
    {
    }

    /**
     * Authorize the payment for processing.
     */
    public function authorize(): PaymentAuthorize
    {
        if (! $this->order) {
            if (! $this->order = $this->cart->order) {
                $this->order = $this->cart->createOrder();
            }
        }
        $this->molliePayment = $this->mollie->getPayment($this->data['payment_intent']);

        $transaction = Transaction::where('reference', $this->data['payment_intent'])
            ->where('order_id', $this->order->id)
            ->where('driver', 'mollie')
            ->first();

        if (! $transaction || ! $this->molliePayment || ! $this->order) {
            return new PaymentAuthorize(
                success: false,
                message: 'Transaction not found',
                orderId: $this->order->id
            );
        }

        foreach ($this->molliePayment->refunds() as $refund) {
            $transaction = $this->order->refunds->where('reference', $refund->id)->first();

            if ($transaction) {
                $transaction->update([
                    'status' => $refund->status,
                ]);
            }
        }

        if ($this->order->placed_at) {
            return new PaymentAuthorize(
                success: false,
                message: 'This order has already been placed',
                orderId: $this->order->id
            );
        }

        if (is_null($this->molliePayment->amountRefunded) || $this->molliePayment->amountRefunded->value === '0.00') {
            $transaction->update([
                'success' => $this->molliePayment->isPaid(),
                'status' => $this->molliePayment->status,
                'notes' => $this->molliePayment->description,
                'card_type' => $this->molliePayment->method ?? '',
                'meta' => [
                    'method' => $this->molliePayment->method,
                    'locale' => $this->molliePayment->locale,
                    'details' => $this->molliePayment->details,
                    'links' => $this->molliePayment->_links,
                    'countryCode' => $this->molliePayment->countryCode,
                ],
            ]);
        }

        if ($this->molliePayment->status === PaymentStatus::STATUS_PAID) {
            $this->order->placed_at = $this->molliePayment->paidAt;
        }

        $paymentStatus = $this->molliePayment->status;

        $this->order->status = Config::get('lunar-api.mollie.payment_status_mappings.'.$paymentStatus) ?: $paymentStatus;

        $this->order->save();

        return new PaymentAuthorize(
            success: $this->molliePayment->status === PaymentStatus::STATUS_PAID,
            message: json_encode([
                'status' => $this->molliePayment->status,
            ]),
        );
    }

    /**
     * Capture a payment for a transaction.
     */
    public function capture(Transaction $transaction, $amount = 0): PaymentCapture
    {
        //Not applicable for Mollie

        return new PaymentCapture(success: true);
    }

    /**
     * Refund a captured transaction
     *
     * @param  string|null  $notes
     */
    public function refund(Transaction $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        try {
            $refund = $this->mollie->createRefund($transaction, $amount, $notes);
        } catch (ApiException $e) {
            return new PaymentRefund(
                success: false,
                message: $e->getMessage()
            );
        }

        $transaction->order->transactions()->create([
            'success' => $refund->status !== RefundStatus::STATUS_FAILED,
            'type' => 'refund',
            'driver' => 'mollie',
            'amount' => $this->mollie->normalizeAmountToInteger($refund->amount->value, $refund->amount->currency),
            'reference' => $refund->id,
            'status' => $refund->status,
            'notes' => $notes,
            'card_type' => $transaction->card_type,
        ]);

        return new PaymentRefund(
            success: true
        );
    }

    /**
     * Check if is successful.
     *
     * @param  Payment  $payment  The Mollie payment
     */
    public function isSuccessful(Payment $payment): bool
    {
        $notSuccessful = [
            PaymentStatus::STATUS_OPEN,
            PaymentStatus::STATUS_FAILED,
            PaymentStatus::STATUS_CANCELED,
            PaymentStatus::STATUS_EXPIRED,
            PaymentStatus::STATUS_PENDING,
        ];

        return ! in_array($payment->status, $notSuccessful);

    }
}
