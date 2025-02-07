<?php

namespace Pixelpillow\LunarApiMollieAdapter;

use Dystcz\LunarApi\Base\Facades\SchemaManifestFacade;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lunar\Facades\Payments;
use Pixelpillow\LunarApiMollieAdapter\Domain\PaymentMethods\JsonApi\V1\PaymentMethodSchema;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;

class LunarApiMollieAdapterServiceProvider extends ServiceProvider
{
    protected $root = __DIR__.'/..';

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/mollie.php', 'lunar-api.mollie');

        // Register schemas.
        $this->registerSchemas();
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        // Register the MollieManager as a singleton.
        $this->app->singleton(
            'gc:mollie',
            fn (Application $app) => $app->make(MollieManager::class),
        );

        // Register our payment type.
        Payments::extend('mollie', function ($app) {
            return $app->make(MolliePaymentType::class);
        });

        // Register our payment adapter.
        MolliePaymentAdapter::register();

        // Initialize the MollieManager.
        MolliePaymentAdapter::initMollieManager();

        // Register the MollieServiceProvider.
        $this->app->register(\Mollie\Laravel\MollieServiceProvider::class);

        // Publish the config file.
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mollie.php' => config_path('dystore/mollie.php'),
            ], 'dystore.mollie.config');
        }

        $this->loadRoutesFrom("{$this->root}/routes/api.php");
    }

    /**
     * Register schemas.
     */
    public function registerSchemas(): void
    {
        SchemaManifestFacade::registerSchema(PaymentMethodSchema::class);
    }
}
