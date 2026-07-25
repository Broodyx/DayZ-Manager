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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Coolify terminates TLS at the proxy. Keep signed Livewire URLs and assets HTTPS.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            if ($this->app->runningInConsole() === false && request()->getHost() !== '') {
                URL::forceRootUrl('https://'.request()->getHost());
            }
        }
    }
}
