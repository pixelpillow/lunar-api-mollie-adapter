<?php

namespace Pixelpillow\LunarApiMollieAdapter\Managers;

use Illuminate\Support\Facades\Config;
use Lunar\Models\Cart;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\IssuerCollection;
use Mollie\Api\Resources\MethodCollection;
use Mollie\Api\Resources\Payment;
use Mollie\Laravel\Facades\Mollie;
use Pixelpillow\LunarApiMollieAdapter\Exceptions\InvalidConfigurationException;
use Pixelpillow\LunarApiMollieAdapter\Generators\BaseUrlGenerator;

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
            'redirectUrl' => self::getRedirectUrl($cart),
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

    /**
     * Get the webhook URL
     *
     * @return string The webhook URL
     */
    public static function getWebhookUrl(): string
    {
        return app('url')->route('payments.webhook', ['mollie']);
    }

    /**
     * Get the redirect URL from the config
     *
     * @param  Cart  $cart The cart to get the webhook URL for.
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
     * @param  array  $parameters The parameters to filter the payment methods on.
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
