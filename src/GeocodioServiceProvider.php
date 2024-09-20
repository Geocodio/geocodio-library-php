<?php

namespace Geocodio;

use Illuminate\Support\ServiceProvider;

class GeocodioServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/geocodio.php' => config_path('geocodio.php'),
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/geocodio.php', 'geocodio');

        $this->app->bind(Geocodio::class, fn (): \Geocodio\Geocodio => (new Geocodio)
            ->setApiKey(config('geocodio.api_key'))
            ->setHostname(config('geocodio.hostname'))
            ->setApiVersion(config('geocodio.api_version')));

        $this->app->alias(Geocodio::class, 'geocodio');
    }
}
