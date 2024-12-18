<?php

namespace Pixelpillow\LunarApiMollieAdapter\Actions;

use Dystore\Api\Domain\Orders\Events\OrderPaymentSuccessful;
use Dystore\Api\Domain\Orders\Models\Order;
use Dystore\Api\Domain\Payments\Contracts\PaymentIntent;
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
                'payment_intent' => $intent->getId(),
                'order_id' => $order->getRouteKey(),
            ])
            ->authorize();

        if (! $payment->success) {
            report('Payment failed for order: '.$order->getRouteKey().' with reason: '.$payment->message);

            return;
        }

        $transaction->update([
            'type' => 'capture',
        ]);

        OrderPaymentSuccessful::dispatch($order);
    }
}
