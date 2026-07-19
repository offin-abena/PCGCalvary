<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($rootUrl = config('app.url')) {
            URL::forceRootUrl($rootUrl);
        }

        // Railway terminates TLS at the proxy; force HTTPS so asset/url()
        // helpers never emit http:// links (mixed content).
        if (str_starts_with((string) config('app.url'), 'https://')
            || $this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
