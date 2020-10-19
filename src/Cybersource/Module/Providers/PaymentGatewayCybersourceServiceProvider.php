<?php

namespace RefinedDigital\Cybersource\Module\Providers;

use Illuminate\Support\ServiceProvider;
use RefinedDigital\CMS\Modules\Core\Aggregates\PaymentGatewayAggregate;

class PaymentGatewayCybersourceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->addNamespace('payment-gateways-cybersource', [
            base_path('resources/views'),
            __DIR__.'/../Resources/views',
        ]);

        $this->publishes([
            __DIR__.'/../../../config/payment-gateway-cybersource.php' => config_path('payment-gateway-cybersource.php'),
        ], 'payment-gateway-cybersource');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        app(PaymentGatewayAggregate::class)
            ->addGateway('Cybersource', \RefinedDigital\Cybersource\Module\Classes\Cybersource::class);
    }
}
