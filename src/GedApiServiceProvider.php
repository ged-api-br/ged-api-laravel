<?php

namespace Ged\ApiLaravel;

use Illuminate\Support\ServiceProvider;
use Ged\ApiClient\GedApiClient;

class GedApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ged-api.php',
            'ged-api'
        );

        // Register singleton
        $this->app->singleton('ged-api', function ($app) {
            $config = $app['config']['ged-api'];

            return new GedApiClient(
                $config['base_url'],
                $config['api_key']
            );
        });

        // Register alias
        $this->app->alias('ged-api', GedApiClient::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/ged-api.php' => config_path('ged-api.php'),
        ], 'ged-api-config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['ged-api', GedApiClient::class];
    }
}

