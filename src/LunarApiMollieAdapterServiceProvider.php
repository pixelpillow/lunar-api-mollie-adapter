<?php

namespace Pixelpillow\LunarApiMollieAdapter;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lunar\Facades\Payments;
use Pixelpillow\LunarApiMollieAdapter\Managers\MollieManager;

class LunarApiMollieAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/mollie.php', 'lunar-api.mollie');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {

        $this->app->singleton(
            'gc:mollie',
            fn (Application $app) => $app->make(MollieManager::class),
        );

        // Register our payment type.
        Payments::extend('mollie', function ($app) {
            return $app->make(MolliePaymentType::class);
        });

        MolliePaymentAdapter::register();

        MolliePaymentAdapter::initMollieManager();

        $this->app->register(\Mollie\Laravel\MollieServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mollie.php' => config_path('lunar-api/mollie.php'),
            ], 'lunar-api.mollie.config');
        }
    }
}
