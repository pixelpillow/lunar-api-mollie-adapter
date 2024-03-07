<?php

namespace Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\Http\Controllers;

use Dystcz\LunarApi\Base\Controller;
use LaravelJsonApi\Core\Responses\DataResponse;
use Pixelpillow\LunarApiMollieAdapter\Domain\PaymentIssuers\Models\PaymentIssuer;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;

class PaymentIssuerController extends Controller
{
    /**
     * @var MollieManager
     */
    private $mollieManager;

    public function __construct(MollieManager $mollieManager)
    {
        $this->mollieManager = $mollieManager;
    }

    public function index(): DataResponse
    {
        $paymentIssuers = $this->mollieManager->getMolliePaymentIssuers();

        $paymentIssuers = collect($paymentIssuers)->map(function ($paymentIssuer) {
            $model = new PaymentIssuer([
                'resource' => $paymentIssuer->resource,
                'name' => $paymentIssuer->name,
                'image' => $paymentIssuer->image,
                'issuer_id' => $paymentIssuer->id,
            ]);

            $model->id = $paymentIssuer->id;

            return $model;
        });

        return DataResponse::make($paymentIssuers)->didntCreate();
    }
}
