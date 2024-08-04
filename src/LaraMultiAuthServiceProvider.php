<?php

namespace AhmedEbead\LaraMultiAuth;

use AhmedEbead\LaraMultiAuth\Console\SetupCommand;
use Illuminate\Support\ServiceProvider;
use AhmedEbead\LaraMultiAuth\Services\AuthService;

class LaraMultiAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('auth.service', function ($app) {
            return new AuthService();
        });
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'laramultiauth');

        // Publish the views
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/ahmedebead/laramultiauth'),
        ], 'views');
        $this->publishes([
            __DIR__.'/config/multiauth.php' => config_path('multiauth.php'),
        ], 'config');

        $this->commands([
            SetupCommand::class,
        ]);

        // Load routes, migrations, etc.
    }
}
