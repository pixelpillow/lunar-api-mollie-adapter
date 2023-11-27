# Description

OG Image!

This package is designed to seamlessly integrate Mollie payments into your [Lunar](https://lunarphp.io/) storefront. By leveraging the [Lunar API](https://github.com/dystcz/lunar-api), this adapter makes it easy to accept and manage payments through Mollie, a popular payment service provider.

Whether you're running an e-commerce platform or a subscription service, this adapter will help you handle payments efficiently and securely. With easy installation and configuration, you can have Mollie payments up and running on your Lunar storefront in no time.

Please follow the installation and configuration instructions in the Installation section to get started.

## Dependencies

This package depends on these greate following packages:

- [Lunar](https://lunarphp.io/)
- [Lunar API](https://github.com/dystcz/lunar-api)

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

### POST /api/v1/payment-issuers

Returns a list of available payment issuers for ideal payments, including the issuer ID, issuer name and more. See the [Mollie API documentation](https://docs.mollie.com/reference/v2/methods-api/list-methods#includes) for more information.

Example response:

```json
{
  "jsonapi": {
    "version": "1.0"
  },
  "data": [
    {
      "type": "payment-issuers",
      "id": "ideal_ABNANL2A",
      "attributes": {
        "resource": "issuer",
        "name": "ABN AMRO",
        "issuer_id": "ideal_ABNANL2A",
        "image": [
          "https://www.mollie.com/external/icons/ideal-issuers/ABNANL2A.png",
          "https://www.mollie.com/external/icons/ideal-issuers/ABNANL2A%402x.png",
          "https://www.mollie.com/external/icons/ideal-issuers/ABNANL2A.svg"
        ]
      },
      "links": {
        "self": "https://api.monoz.test/api/v1/payment-issuers/ideal_ABNANL2A"
      }
    }
  ]
}
```
