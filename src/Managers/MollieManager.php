<?php

namespace Pixelpillow\LunarApiMollieAdapter\Managers;

use Dystcz\LunarApi\Domain\Payments\PaymentAdapters\PaymentIntent;
use Lunar\Models\Cart;
use Mollie\Api\Resources\Payment;
use Mollie\Laravel\Facades\Mollie;

class MollieManager
{
    public static function createPayment(Cart $cart, string $paymentMethod, string $issuer): Payment
    {
        return Mollie::api()->payments->create([
            'amount' => [
                'currency' => 'EUR',
                'value' => self::normalizeAmountToString($cart->total->value),
            ],
            'description' => 'Order #'.$cart->id,
            'redirectUrl' => 'https://example.com/redirect',
            'webhookUrl' => self::getWebhookUrl(),
            'metadata' => [
                'order_id' => $cart->id,
            ],
            'method' => $paymentMethod,
            'issuer' => $issuer,
        ]);
    }

    public static function getPayment(string $paymentId): Payment
    {
        return Mollie::api()->payments->get($paymentId);
    }

    public static function getWebhookUrl(): string
    {
        // return route('payments.webhook', ['paymentDriver' => 'mollie']);
        return 'https://4544-95-98-164-198.ngrok-free.app/mollie/webhook';
    }

    /**
     * Normalizes an amount to the correct format for Mollie to use.
     * The amount shoudn't be a integer but a string with a dot as decimal separator.
     * eg. 10.00 instead of 1000
     *
     * @see https://docs.mollie.com/reference/v2/payments-api/create-payment
     *
     * @param  int  $amount The amount in cents
     */
    public static function normalizeAmountToString(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * Normalizes an amount to a integer.
     * The amount should be a integer without a dot as decimal separator.
     * eg. 1000 instead of 10.00
     * This is the opposite of normalizeAmountToString
     */
    public static function normalizeAmountToInteger(string $amount): int
    {
        return (int) str_replace('.', '', $amount);
    }

    /**
     * Create a payment intent from a Cart
     *
     * @return PaymentIntent
     */
    public static function createIntent(Cart $cart)
    {
        $shipping = $cart->shippingAddress;

        $meta = (array) $cart->meta;

        if ($meta && ! empty($meta['payment_intent'])) {
            $intent = $this->fetchIntent(
                $meta['payment_intent']
            );

            if ($intent) {
                return $intent;
            }
        }

        $paymentIntent = $this->buildIntent(
            $cart->total->value,
            $cart->currency->code,
            $shipping,
        );

        if (! $meta) {
            $cart->update([
                'meta' => [
                    'payment_intent' => $paymentIntent->id,
                ],
            ]);
        } else {
            $meta['payment_intent'] = $paymentIntent->id;
            $cart->meta = $meta;
            $cart->save();
        }

        return $paymentIntent;
    }
}
