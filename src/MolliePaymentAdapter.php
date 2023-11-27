<?php

namespace Pixelpillow\LunarApiMollieAdapter;

use Dystcz\LunarApi\Domain\Orders\Actions\FindOrderByIntent;
use Dystcz\LunarApi\Domain\Orders\Events\OrderPaymentCanceled;
use Dystcz\LunarApi\Domain\Orders\Events\OrderPaymentFailed;
use Dystcz\LunarApi\Domain\Payments\PaymentAdapters\PaymentAdapter;
use Dystcz\LunarApi\Domain\Payments\PaymentAdapters\PaymentIntent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Lunar\Models\Cart;
use Pixelpillow\LunarApiMollieAdapter\Actions\AuthorizeMolliePayment;
use Pixelpillow\LunarApiMollieAdapter\Exceptions\MissingMetadataException;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;
use Throwable;

class MolliePaymentAdapter extends PaymentAdapter
{
    protected Cart $cart;

    public function getDriver(): string
    {
        return Config::get('lunar-api.mollie.driver', 'mollie');
    }

    public function getType(): string
    {
        return Config::get('lunar-api.mollie.type', 'mollie');
    }

    /**
     * Set cart.
     */
    protected function setCart(Cart $cart): void
    {
        $this->cart = $cart;
    }

    public function createIntent(Cart $cart, array $meta = []): PaymentIntent
    {
        $this->setCart($cart);

        $paymentMethodType = $this->validatePaymentMethodType($meta['payment_method_type'] ?? null);
        $paymentMethodIssuer = $this->validatePaymentIssuer($meta['payment_method_issuer'] ?? null);

        $molliePayment = MollieManager::createPayment($cart->calculate(), $paymentMethodType, $paymentMethodIssuer);

        $paymentIntent = new PaymentIntent(
            id: $molliePayment->id,
            status: $molliePayment->status,
            amount: MollieManager::normalizeAmountToInteger($molliePayment->amount->value),
            meta: [
                'mollie_checkout_url' => $molliePayment->getCheckoutUrl(),
            ]
        );

        $this->createTransaction($paymentIntent, [
            'meta' => [
                'payment_method' => $paymentMethodType,
                'payment_method_issuer' => $paymentMethodIssuer,
                'mollie_checkout_url' => $molliePayment->getCheckoutUrl(),
            ],
        ]);

        return $paymentIntent;
    }

    /**
     * Validate the payment methose type against the Mollie payment method types
     *
     * @param  string|null  $paymentMethodType The payment method type eg. ideal
     */
    public function validatePaymentMethodType(?string $paymentMethodType): string
    {
        if (! $paymentMethodType) {
            throw new MissingMetadataException('Payment method type is required');
        }

        if (! defined('Mollie\Api\Types\PaymentMethod::'.strtoupper($paymentMethodType))) {
            throw new MissingMetadataException('Payment method type is not a valid Mollie payment method type');
        }

        return $paymentMethodType;
    }

    /**
     * Validate the payment issuer against the Mollie payment issuers
     *
     * @param  string|null  $paymentIssuer The payment issuer eg. ideal_ABNANL2A
     */
    public function validatePaymentIssuer(?string $paymentIssuer): ?string
    {
        if (! $paymentIssuer) {
            throw new MissingMetadataException('Payment issuer is required');
        }

        return $paymentIssuer;
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        $paymentId = $request->get('id');

        if (! $paymentId) {
            return response()->json(['error' => 'Payment id is required'], 400);
        }

        $payment = MollieManager::getPayment($paymentId);

        if (! $payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $paymentIntent = new PaymentIntent(
            id: $payment->id,
            status: $payment->status,
            amount: MollieManager::normalizeAmountToInteger($payment->amount->value),
            meta: [
                'mollie_checkout_url' => $payment->getCheckoutUrl(),
            ]
        );

        try {
            $order = App::make(FindOrderByIntent::class)($paymentIntent);
        } catch (Throwable $e) {
            return new JsonResponse([
                'webhook_successful' => false,
                'message' => "Order not found for payment intent {$paymentIntent->id}",
            ], 404);
        }

        $this->setCart($order->cart);

        if ($payment->isPaid()) {
            App::make(AuthorizeMolliePayment::class)($order, $paymentIntent);

            return response()->json(['message' => 'success']);
        }

        if ($payment->isCanceled()) {
            OrderPaymentCanceled::dispatch($order, $this, $paymentIntent);

            return response()->json(['message' => 'cancelled']);
        }

        if ($payment->isFailed()) {
            OrderPaymentFailed::dispatch($order, $this, $paymentIntent);

            return response()->json(['message' => 'failed']);
        }

        if ($payment->isExpired()) {
            OrderPaymentFailed::dispatch($order, $this, $paymentIntent);

            return response()->json(['message' => 'expired']);
        }

        return response()->json(['message' => 'unknown event']);
    }

    /**
     * Initialize the Mollie manager with the Mollie key
     */
    public static function initMollieManager(): void
    {
        $mollieKey = Config::get('lunar-api.mollie.mollie_key');

        Config::set('mollie.key', $mollieKey);
    }
}
