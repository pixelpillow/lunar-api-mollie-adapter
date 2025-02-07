<?php

namespace Pixelpillow\LunarApiMollieAdapter\Tests;

use Dystcz\LunarApi\JsonApiServiceProvider;
use Dystcz\LunarApi\LunarApiServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use LaravelJsonApi\Testing\TestExceptionHandler;
use Lunar\Base\ShippingModifiers;
use Lunar\Facades\Taxes;
use Lunar\Models\Cart;
use Lunar\Models\Channel;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Order;
use Lunar\Models\TaxClass;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Pixelpillow\LunarApiMollieAdapter\Tests\Stubs\Lunar\TestShippingModifier;
use Pixelpillow\LunarApiMollieAdapter\Tests\Stubs\Lunar\TestTaxDriver;
use Pixelpillow\LunarApiMollieAdapter\Tests\Stubs\Lunar\TestUrlGenerator;
use Pixelpillow\LunarApiMollieAdapter\Tests\Stubs\TestRedirectGenerator;

class TestCase extends Orchestra
{
    use MakesJsonApiRequests;
    use WithWorkbench;

    /**
     * The order.
     *
     * @return Order
     */
    public $order;

    /**
     * The cart.
     *
     * @return Cart
     */
    public $cart;

    /**
     * The payment intent.
     *
     * @return PaymentIntent
     */
    public $intent;

    protected function setUp(): void
    {
        parent::setUp();

        Taxes::extend(
            'test',
            fn (Application $app) => $app->make(TestTaxDriver::class),
        );

        Currency::factory()->create([
            'code' => 'EUR',
            'decimal_places' => 2,
        ]);

        Country::factory()->create([
            'name' => 'United Kingdom',
            'iso3' => 'GBR',
            'iso2' => 'GB',
            'phonecode' => '+44',
            'capital' => 'London',
            'currency' => 'GBP',
            'native' => 'English',
        ]);

        Channel::factory()->create([
            'default' => true,
        ]);

        CustomerGroup::factory()->create([
            'default' => true,
        ]);

        TaxClass::factory()->create();

        App::get(ShippingModifiers::class)->add(TestShippingModifier::class);

        activity()->disableLogging();
    }

    protected function getPackageProviders($app): array
    {
        return [
            // Ray
            \Spatie\LaravelRay\RayServiceProvider::class,

            // Laravel JsonApi
            \LaravelJsonApi\Encoder\Neomerx\ServiceProvider::class,
            \LaravelJsonApi\Laravel\ServiceProvider::class,
            \LaravelJsonApi\Spec\ServiceProvider::class,

            // Lunar core
            \Lunar\LunarServiceProvider::class,
            \Spatie\MediaLibrary\MediaLibraryServiceProvider::class,
            \Spatie\Activitylog\ActivitylogServiceProvider::class,
            \Cartalyst\Converter\Laravel\ConverterServiceProvider::class,
            \Kalnoy\Nestedset\NestedSetServiceProvider::class,
            \Spatie\LaravelBlink\BlinkServiceProvider::class,

            // Livewire
            \Livewire\LivewireServiceProvider::class,

            // Dystore API
            LunarApiServiceProvider::class,
            JsonApiServiceProvider::class,

            // Lunar API Mollie Adapter
            \Pixelpillow\LunarApiMollieAdapter\LunarApiMollieAdapterServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    public function getEnvironmentSetUp($app): void
    {
        $app->useEnvironmentPath(__DIR__.'/..');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);

        Config::set('lunar-api.mollie.mollie_key', 'test_G3ys6guxc9Su7VJ2xctR4N4VqvGbQR');
        Config::set('lunar-api.mollie.redirect_url_generator', TestRedirectGenerator::class);
        Config::set('lunar-api.mollie.cancel_url_generator', TestRedirectGenerator::class);

        /**
         * Lunar configuration
         */
        Config::set('lunar.urls.generator', TestUrlGenerator::class);
        Config::set('lunar.taxes.driver', 'test');

        /**
         * App configuration
         */
        Config::set('database.default', 'sqlite');
        Config::set('database.migrations', 'migrations');
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        // Default payment driver
        Config::set('lunar.payments.default', 'mollie');
        Config::set('lunar.payments.types', [
            'mollie' => [
                'driver' => 'mollie',
                'authorized' => 'payment-received',
            ],
        ]);

        Config::set('lunar.payments.default', 'mollie');
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }

    /**
     * Resolve application HTTP exception handler implementation.
     */
    protected function resolveApplicationExceptionHandler($app): void
    {
        $app->singleton(
            ExceptionHandler::class,
            TestExceptionHandler::class
        );
    }
}
