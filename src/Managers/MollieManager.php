<?php

namespace Pixelpillow\LunarApiMollieAdapter\Managers;

use Illuminate\Support\Facades\Config;
use Lunar\Models\Cart;
use Lunar\Models\Transaction;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\IssuerCollection;
use Mollie\Api\Resources\MethodCollection;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Refund;
use Mollie\Api\Types\PaymentMethod;
use Mollie\Laravel\Facades\Mollie;
use Pixelpillow\LunarApiMollieAdapter\Exceptions\InvalidConfigurationException;
use Pixelpillow\LunarApiMollieAdapter\Generators\BaseUrlGenerator;

class MollieManager
{
    /**
     * Create a Mollie payment
     *
     * @param  Cart  $cart  The cart to create the payment for.
     * @param  string  $paymentMethod  The payment method to use.
     * @param  string|null  $issuer  The issuer to use for iDEAL payments.
     * @param  string|null  $description  The description to use for the payment.
     * @param  int|null  $amount  A custom amount in cents to use for the payment.
     * @return Payment The Mollie payment
     *
     * @throws ApiException When the payment cannot be created
     */
    public function createPayment(
        Cart $cart,
        string $paymentMethod,
        ?string $issuer = null,
        ?string $description = null,
        ?int $amount = null
    ): Payment {
        $amount = $amount ?? $cart->total->value;

        $payment = [
            'amount' => [
                'currency' => $cart->currency->code,
                'value' => self::normalizeAmountToString($amount),
            ],
            'description' => 'Payment for order '.$cart->id,
            'redirectUrl' => self::getRedirectUrl($cart),
            'cancelUrl' => self::getCancelUrl($cart),
            'webhookUrl' => self::getWebhookUrl(),
            'metadata' => [
                'order_id' => $cart->id,
            ],
            'method' => $paymentMethod,
        ];

        if ($paymentMethod === PaymentMethod::IDEAL) {
            $payment['issuer'] = $issuer;
        }

        return Mollie::api()->payments->create($payment);
    }

    public function createRefund(Transaction $transaction, int $amount, ?string $notes = null): Refund
    {
        $payment = [
            'amount' => [
                'currency' => $transaction->currency->code,
                'value' => self::normalizeAmountToString($amount),
            ],
            'description' => $notes ?? 'Refund for order '.$transaction->order->reference,
        ];

        return Mollie::api()->payments->refund($transaction->reference, $payment);
    }

    /**
     * Get a Mollie payment by ID
     *
     * @param  string  $paymentId  The payment ID
     * @return Payment The Mollie payment
     *
     * @throws ApiException When the payment is not found
     */
    public function getPayment(string $paymentId): Payment
    {
        return Mollie::api()->payments->get($paymentId);
    }

    /**
     * Get the webhook URL
     *
     * @return string The webhook URL
     */
    public static function getWebhookUrl(): string
    {
        $webhookUrl = null;

        // return a different webhook URL when Testing
        if (app()->environment('testing')) {
            $webhookUrl = Config::get('lunar-api.mollie.webhook_url_testing', null);
        }

        if ($webhookUrl === null) {
            $appUrl = Config::get('app.url', null);
            $webhookRouteUrl = route('payments.webhook', ['mollie'], absolute: false);

            // @TODO why is this needed? And why is url() not working?
            $webhookUrl = $appUrl.$webhookRouteUrl;
        }

        return $webhookUrl;
    }

    /**
     * Get the redirect URL from the config
     *
     * @param  Cart  $cart  The cart to get the webhook URL for.
     * @return string The redirect URL
     *
     * @throws InvalidConfigurationException When the redirect URL is not set
     */
    public static function getRedirectUrl(Cart $cart): string
    {
        $redirectUrlGeneratorClass = Config::get('lunar-api.mollie.redirect_url_generator');

        if (! $redirectUrlGeneratorClass && ! class_exists($redirectUrlGeneratorClass)) {
            throw new InvalidConfigurationException('Mollie redirect URL generator not set in config');
        }

        /**
         * @var BaseUrlGenerator $redirectUrlGenerator
         */
        $redirectUrlGenerator = new $redirectUrlGeneratorClass($cart);

        return $redirectUrlGenerator->generate();
    }

    /**
     * Get the cancel URL from the config
     *
     * @param  Cart  $cart  The cart to get the webhook URL for.
     * @return string The cancel URL
     *
     * @throws InvalidConfigurationException When the cancel URL is not set
     */
    public static function getCancelUrl(Cart $cart): string
    {
        $cancelUrlGeneratorClass = Config::get('lunar-api.mollie.cancel_url_generator');

        if (! $cancelUrlGeneratorClass && ! class_exists($cancelUrlGeneratorClass)) {
            throw new InvalidConfigurationException('Mollie cancel URL generator not set in config');
        }

        /**
         * @var BaseUrlGenerator $cancelUrlGenerator
         */
        $cancelUrlGenerator = new $cancelUrlGeneratorClass($cart);

        return $cancelUrlGenerator->generate();
    }

    /**
     * Normalizes an amount to the correct format for Mollie to use.
     * The amount shoudn't be a integer but a string with a dot as decimal separator.
     * eg. 10.00 instead of 1000
     *
     * @see https://docs.mollie.com/reference/v2/payments-api/create-payment
     *
     * @param  int  $amount  The amount in cents
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
     * Get a list of Mollie payment issuers for iDEAL payments
     *
     * @return IssuerCollection The Mollie payment issuers.
     *
     * @see https://docs.mollie.com/reference/v2/methods-api/list-methods
     */
    public function getMolliePaymentIssuers(): ?IssuerCollection
    {
        try {
            $reponse = Mollie::api()->methods->get(\Mollie\Api\Types\PaymentMethod::IDEAL, ['include' => 'issuers']);

            return $reponse->issuers();
        } catch (ApiException $e) {
            report($e);
        }

        return null;
    }

    /**
     * Get a list of Mollie payment methods
     *
     * @param  array  $parameters  The parameters to filter the payment methods on.
     * @return \Mollie\Api\Resources\BaseCollection|\Mollie\Api\Resources\MethodCollection|null
     *
     * @see https://docs.mollie.com/reference/v2/methods-api/list-methods
     */
    public function getMolliePaymentMethods(
        array $parameters = []
    ): ?MethodCollection {
        try {
            $reponse = Mollie::api()->methods->allActive(empty($parameters) ? $parameters : null);

            return $reponse;
        } catch (ApiException $e) {
            report($e);
        }

        return null;
    }
}
