<?php

namespace Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\Http\Controllers;

use Dystore\Api\Base\Controller;
use LaravelJsonApi\Core\Responses\DataResponse;
use Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\Models\PaymentMethod;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;

class PaymentMethodController extends Controller
{
    /**
     * @var MollieManager
     */
    private $mollieManager;

    public function __construct(MollieManager $mollieManager)
    {
        $this->mollieManager = $mollieManager;
    }

    public function index()
    {
        $paymentMethods = $this->mollieManager->getMolliePaymentMethods();

        $paymentMethods = collect($paymentMethods)->map(function ($paymentMethod) {
            $model = new PaymentMethod([
                'name' => $paymentMethod->description,
                'image' => $paymentMethod->image,
                'method_id' => $paymentMethod->id,
            ]);

            $model->id = $paymentMethod->id;

            return $model;
        });

        return DataResponse::make($paymentMethods)->didntCreate();
    }
}
