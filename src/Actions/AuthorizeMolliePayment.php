<?php

namespace Pixelpillow\LunarApiMollieAdapter\Actions;

use Dystcz\LunarApi\Domain\Orders\Events\OrderPaid;
use Dystcz\LunarApi\Domain\Orders\Models\Order;
use Dystcz\LunarApi\Domain\Payments\PaymentAdapters\PaymentIntent;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Facades\Payments;
use Lunar\Models\Transaction;

class AuthorizeMolliePayment
{
    public function __invoke(Order $order, PaymentIntent $intent, Transaction $transaction): void
    {
        /** @var PaymentAuthorize $payment */
        $payment = Payments::driver('mollie')
            ->cart($order->cart)
            ->withData([
                'payment_intent' => $intent->id,
            ])
            ->authorize();

        if (! $payment->success) {
            report('Payment failed for order: '.$order->id.' with reason: '.$payment->message);

            return;
        }

        $transaction->update([
            'type' => 'capture',
        ]);

        OrderPaid::dispatch($order);
    }
}
