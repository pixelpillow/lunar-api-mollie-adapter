<?php

namespace Pixelpillow\LunarApiMollieAdapter;

use Dystcz\LunarApi\Domain\Orders\Events\OrderPaymentCanceled;
use Dystcz\LunarApi\Domain\Orders\Events\OrderPaymentFailed;
use Dystcz\LunarApi\Domain\Payments\Contracts\PaymentIntent as PaymentIntentContract;
use Dystcz\LunarApi\Domain\Payments\PaymentAdapters\PaymentAdapter;
use Dystcz\LunarApi\Domain\Transactions\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Lunar\Models\Contracts\Cart as CartContract;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Laravel\Facades\Mollie;
use Pixelpillow\LunarApiMollieAdapter\Actions\AuthorizeMolliePayment;
use Pixelpillow\LunarApiMollieAdapter\Domain\Payments\Data\PaymentIntent;
use Pixelpillow\LunarApiMollieAdapter\Exceptions\MissingMetadataException;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;
use Throwable;

class MolliePaymentAdapter extends PaymentAdapter
{
    protected string $type;

    protected MollieManager $mollie;

    public function __construct()
    {
        $this->mollie = app(MollieManager::class);

        $this->type = Config::get('lunar-api.mollie.type', 'mollie');
    }

    /**
     * Get payment driver on which this adapter binds.
     *
     * Drivers for lunar are set in lunar.payments.types.
     * When mollie is set as a driver, this adapter will be used.
     */
    public function getDriver(): string
    {
        return Config::get('lunar-api.mollie.driver', 'mollie');
    }

    /**
     * Get payment type.
     *
     * This key serves is an identification for this adapter.
     * That means that stripe driver is handled by this adapter if configured.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set payment type.
     */
    protected function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Create payment intent.
     */
    public function createIntent(CartContract $cart, array $meta = [], ?int $amount = null): PaymentIntentContract
    {
        $paymentMethodType = $this->validatePaymentMethodType($meta['payment_method_type'] ?? null);

        try {
            $amount = $amount ?? null;

            $molliePayment = $this->mollie->createPayment(
                cart: $cart->calculate(),
                paymentMethod: $paymentMethodType,
                amount: $amount,
            );
        } catch (Throwable $e) {
            throw new ApiException('Mollie payment failed: '.$e->getMessage());
        }

        $meta = Arr::add($meta, 'mollie_checkout_url', $molliePayment->getCheckoutUrl());

        $paymentIntent = new PaymentIntent(
            intent: $molliePayment,
            meta: $meta,
        );

        $meta = Arr::add($meta, 'payment_method', $paymentMethodType);

        $this->setType($paymentMethodType);

        $this->createIntentTransaction($cart, $paymentIntent, $meta);

        return $paymentIntent;
    }

    /**
     * Validate the payment methose type against the Mollie payment method types
     *
     * @param  string|null  $paymentMethodType  The payment method type eg. ideal
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
     * Handle incoming webhook from Mollie.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $paymentId = $request->get('id');

        if (! $paymentId) {
            return response()->json(['error' => 'Payment id is required'], 400);
        }

        $payment = $this->mollie->getPayment($paymentId);

        if (! $payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $transaction = Transaction::query()
            ->where('reference', $payment->id)
            ->first();

        try {
            $order = $transaction->order;
        } catch (Throwable $e) {
            return new JsonResponse([
                'webhook_successful' => false,
                'message' => "Order not found for transaction #{$transaction->id}",
            ], 404);
        }

        $paymentIntent = new PaymentIntent(
            intent: $payment,
            meta: ['mollie_checkout_url' => $payment->getCheckoutUrl()]
        );

        if ($payment->isPaid() && $transaction->status === 'paid') {
            return response()->json(['message' => 'success']);
        }

        if ($payment->isPaid() && $transaction->status !== 'paid') {
            App::make(AuthorizeMolliePayment::class)($order, $paymentIntent, $transaction);

            $this->updateTransactionStatus($transaction, 'paid');

            return response()->json(['message' => 'success']);
        }

        if ($payment->isCanceled()) {
            OrderPaymentCanceled::dispatch($order, $this, $paymentIntent);

            $this->updateTransactionStatus($transaction, 'canceled');

            return response()->json(['message' => 'canceled']);
        }

        if ($payment->isFailed()) {
            OrderPaymentFailed::dispatch($order, $this, $paymentIntent);

            $this->updateTransactionStatus($transaction, 'failed');

            return response()->json(['message' => 'failed']);
        }

        if ($payment->isExpired()) {
            OrderPaymentFailed::dispatch($order, $this, $paymentIntent);

            $this->updateTransactionStatus($transaction, 'expired');

            return response()->json(['message' => 'expired']);
        }

        return response()->json(['message' => 'unknown event']);
    }

    /**
     * Update the transaction status
     */
    protected function updateTransactionStatus(Transaction $transaction, string $status): void
    {
        $transaction->update([
            'status' => $status,
        ]);
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
