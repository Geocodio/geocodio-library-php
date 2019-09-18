<?php

namespace Geocodio;

use Illuminate\Support\ServiceProvider;

class GeocodioServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('geocodio.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'geocodio');

        $this->app->bind(Geocodio\Geocodio::class, function () {
            return (new Geocodio)
                ->setApiKey(config('geocodio.api_key'))
                ->setHostname(config('geocodio.hostname'))
                ->setApiVersion(config('geocodio.api_version'));
        });

        $this->app->alias('geocodio', Geocodio::class);
    }
}
