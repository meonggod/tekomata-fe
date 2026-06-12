<?php

namespace App\Providers;

use App\Services\Tekomata\AdminFxApi;
use App\Services\Tekomata\TekomataClient;
use App\Services\Tekomata\TokenStore;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Single API client, configured from config/services.php → tekomata.
        $this->app->singleton(TekomataClient::class, function ($app) {
            return new TekomataClient($app['config']->get('services.tekomata'));
        });

        // Platform-admin FX client: same HTTP client, but carries the tekomata
        // admin key (X-Admin-Key) instead of a tenant JWT. Only the internal area
        // resolves it.
        $this->app->singleton(AdminFxApi::class, function ($app) {
            return new AdminFxApi(
                $app->make(TekomataClient::class),
                $app['config']->get('services.tekomata.admin_key'),
            );
        });

        // TokenStore is request-scoped: it wraps the active session.
        $this->app->scoped(TokenStore::class, function ($app) {
            return new TokenStore($app->make(Session::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
