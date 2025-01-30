![lunar-api-mollie-adapter](https://github.com/pixelpillow/lunar-api-mollie-adapter/assets/224501/f16d946c-5861-46df-a56d-06c972b7adc7)

# Table of Contents

- [Description](#description)
- [Dependencies](#dependencies)
- [Features](#features)
- [Installation](#installation)

# Description

This package is designed to seamlessly integrate Mollie payments into your [Lunar](https://lunarphp.io/) storefront. By leveraging the [Dystore Lunar API](https://github.com/dystcz/dystore-api), this adapter makes it easy to accept and manage payments through Mollie, a popular payment service provider.

Whether you're running an e-commerce platform or a subscription service, this adapter will help you handle payments efficiently and securely. With easy installation and configuration, you can have Mollie payments up and running on your Lunar storefront in no time.

Please follow the installation and configuration instructions in the Installation section to get started.

## Dependencies

This package depends on the following packages:

- [Lunar](https://lunarphp.io/)
- [Dystore Lunar API](https://github.com/dystcz/dystore-api)

## Features

- [x] Multiple payment methods, including iDEAL, credit card, PayPal, Apple Pay, and more
- [x] Expose Payment methods in the API
- [x] Expose Payment issuers for ideal payments in the API

## Installation

1. Install this package via composer:

```bash
composer require pixelpillow/lunar-api-mollie-adapter
```

2. Publish the config file:

```bash
php artisan vendor:publish --tag="lunar-api-mollie-adapter-config"
```

3. Add your Mollie API key (You can obtain your API key by signing up on the [Mollie](https://mollie.com) website.) to the `.env` file:

```bash
MOLLIE_API_KEY=your-api-key
```

4. Add the Mollie payment type to the `config/lunar/payments.php` file:

```php
<?php

return [
    'default' => env('PAYMENTS_TYPE', 'cash-in-hand'),
    'types' => [
        'cash-in-hand' => [
            'driver' => 'offline',
            'authorized' => 'payment-offline',
        ],
        // Add the Mollie payment type here:
        'mollie' => [
            'driver' => 'mollie',
            'authorized' => 'payment-received',
        ],
    ],
];
```

5. Manage the redirect on success in your own `RedirectOnSuccessUrlGenerator` by defining your own generator in the `config/lunar-api-mollie-adapter.php` file:

```php
<?php

namespace App\Mollie\Generators;

use Dystcz\LunarApi\Domain\Carts\Models\Cart;
use Pixelpillow\LunarApiMollieAdapter\Generators\RedirectOnSuccessUrlGenerator;

class CustomRedirectOnSuccessUrlGenerator extends RedirectOnSuccessUrlGenerator
{
    /**
     * @var Cart
     */
    protected $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Generate the webhook URL.
     */
    public function generate(): string
    {
        $order = $this->cart->orders()->first();

        if (! $order) {
            throw new \Exception('Order not found');
        }

        // Return your own redirect URL here
        return 'https://example.com/checkout/success?order_id=' . $order->id;
    }
}
```

6. Manage the redirect on failure in your the `RedirectOnFailureUrlGenerator` by defining your own generator in the `config/lunar-api-mollie-adapter.php` file:

```php
<?php

namespace App\Mollie\Generators;

use Dystcz\LunarApi\Domain\Carts\Models\Cart;
use Lunar\Models\Order;
use Pixelpillow\LunarApiMollieAdapter\Generators\RedirectOnSuccessUrlGenerator;

class CustomRedirectOnFailureUrlGenerator extends RedirectOnSuccessUrlGenerator
{
    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var Order
     */
    protected $order;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Generate the webhook URL.
     */
    public function generate(): string
    {

        $order = $this->cart->orders()->first();

        if (! $order) {
            throw new \Exception('Order not found');
        }

        // Return your own redirect URL here
        return 'https://example.com/checkout/failure?order_id=' . $order->id;
    }
}
```

### Example JSON:API request for creating a Mollie IDEAL paymentIntent

The create-payment-intent url is a signed url can be found in the response of the POST /api/v1/carts/{cart}/-actions/checkout request.

`POST api/v1/orders/{order}/-actions/create-payment-intent`

```json
{
  "data": {
    "type": "orders",
    "id": 1,
    "attributes": {
      "payment_method": "mollie",
      "meta": {
        "payment_method_type": "ideal"
      }
    }
  }
}
```

### Example JSON:API request for creating a Mollie Bancontact paymentIntent

The create-payment-intent url is a signed url can be found in the response of the POST /api/v1/carts/{cart}/-actions/checkout request.

`POST api/v1/orders/{order}/-actions/create-payment-intent`

```json
{
  "data": {
    "type": "orders",
    "id": 1,
    "attributes": {
      "payment_method": "mollie",
      "meta": {
        "payment_method_type": "bancontact"
      }
    }
  }
}
```

## Endpoints

This package extends the Lunar API with the following endpoints:

### GET /api/v1/payment-methods

Returns a list of enabled payment methods in the Mollie dashboard. The results are not paginated. See the [Mollie API documentation](https://docs.mollie.com/reference/v2/methods-api/list-methods) for more information.

Example response:

```json
{
  "jsonapi": {
    "version": "1.0"
  },
  "data": [
    {
      "type": "payment-methods",
      "id": "ideal",
      "attributes": {
        "name": "iDEAL",
        "method_id": "ideal",
        "image": [
          "https://www.mollie.com/external/icons/payment-methods/ideal.png",
          "https://www.mollie.com/external/icons/payment-methods/ideal%402x.png",
          "https://www.mollie.com/external/icons/payment-methods/ideal.svg"
        ]
      },
      "links": {
        "self": "https://api.monoz.test/api/v1/payment-methods/ideal"
      }
    }
  ]
}
```

### Security

If you discover any security related issues, please email security[at]pixelpillow.nl instead of using the issue tracker.

## Acknowledgements

- [All Contributors](../../contributors)
- [Lunar](https://github.com/lunarphp/lunar) for providing awesome e-commerce package.
- [Dystcz](https://dy.st) for creating the Lunar API package.
- [Laravel JSON:API](https://github.com/laravel-json-api/laravel) which is a brilliant JSON:API layer for Laravel applications
- [Mollie](https://mollie.com) for providing a great payment service.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
