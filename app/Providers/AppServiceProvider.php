<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo', function (array $config = []): BrevoTransport {
            return new BrevoTransport(
                Http::getFacadeRoot(),
                (string) config('services.brevo.api_key'),
                (string) config('services.brevo.endpoint'),
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
                (int) config('services.brevo.timeout', 10),
            );
        });
    }
}
