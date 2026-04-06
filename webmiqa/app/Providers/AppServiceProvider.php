<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SalamService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->singleton(OngkirService::class, function ($app) {
        //     return new OngkirService(
        //         apiKey: config('services.rajaongkir.api_key'),
        //         baseUrl: config('services.rajaongkir.base_url'),
        //     );
        // });

        // $this->app->bind(
        //     PaymentGatewayInterface::class,
        //     MidtransPaymentGateway::class,
        // );

        // $this->app->bind(
        //     NotifikasiInterface::class,
        //     WhatsAppNotifikasiService::class,
        // );

        // Singleton -- hanya satu instance yang dibuat selama siklus hidup aplikasi
        // $this->app->singleton(EmailService)

        $this->app->singleton(SalamService::class, function ($app) {
            return new SalamService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Custom validation rule
        // Validator::extend('Indonesian_phone', function ($attr, $value) {
        //     return preg_match('/^(\+62|62|0)8[1-9][0-9]{6,9}$/', $value);
        // });

        // Model observer
        // Product::observe(ProductObserver::class);

        // View composer — inject data ke semua view tertentu
        // View::composer('layouts.app', function ($view) {
        //     $view->with('cartCount', Cart::getCurrentCount());
        // });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
