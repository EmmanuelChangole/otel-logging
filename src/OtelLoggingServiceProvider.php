<?php

namespace Changole\OtelLogging\Otel;

use Illuminate\Support\ServiceProvider;

/**
 * OpenTelemetry logging service provider.
 */
class OtelLoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/config/otel-logging.php', 'otel-logging'
        );

        // Register the Config class
        $this->app->singleton(Config::class, function ($app) {
            return new Config($app['config']);
        });

        // Other services...
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/otel-logging.php' => config_path('otel-logging.php'),
        ], 'otel-logging-config');
    }
}