# Description

This addon enables Mollie payments on your Lunar storefront when using the [Lunar API](https://github.com/dystcz/lunar-api) package.

## Installation

1. Install this package via composer:

```bash
composer require pixelpillow/lunar-api-mollie-adapter
```

2. Publish the config file:

```bash
php artisan vendor:publish --tag="lunar-api-mollie-adapter-config"
```

3. Add your Mollie API key to the config file:
