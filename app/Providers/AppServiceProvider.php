<?php

namespace App\Providers;

use App\Services\Tekomata\StaffTokenStore;
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

        // TokenStore is request-scoped: it wraps the active session.
        $this->app->scoped(TokenStore::class, function ($app) {
            return new TokenStore($app->make(Session::class));
        });

        // StaffTokenStore is the /internal staff principal — same session, but
        // its own keys, wholly separate from the tenant TokenStore above.
        $this->app->scoped(StaffTokenStore::class, function ($app) {
            return new StaffTokenStore($app->make(Session::class));
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
