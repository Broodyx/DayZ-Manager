<?php

namespace App\Providers;

use App\Services\Dayz\ObjectCatalogService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Explicit binding required for MapConfigurationReader's optional constructor param
        // (see its docblock) to actually get auto-injected: Laravel's container skips
        // reflection-based auto-resolution for a parameter that has a default value UNLESS the
        // parameter's class is explicitly bound (Container::resolveClass() checks
        // `$this->bound($className)`) — an unbound class with a default is treated as "use the
        // default", not "try to resolve it anyway". Singleton is safe here: the service is
        // stateless and already wraps its own Cache::remember().
        $this->app->singleton(ObjectCatalogService::class);
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
