<?php

namespace Changole\OtelLogging;

use Changole\OtelLogging\Otel\OtelLogHandler;
use Illuminate\Support\ServiceProvider;
use Changole\OtelLogging\Otel\Config;
use Changole\OtelLogging\Otel\AttributeFormatter;
use Changole\OtelLogging\Otel\SeverityMapper;
use Changole\OtelLogging\Otel\LogPayloadBuilder;

class OtelLoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/config/otel-logging.php', 'otel-logging'
        );

        // Register services
        $this->app->singleton(Config::class, function ($app) {
            return new Config($app['config']);
        });

        $this->app->singleton(AttributeFormatter::class, function ($app) {
            return new AttributeFormatter();
        });

        $this->app->singleton(SeverityMapper::class, function ($app) {
            return new SeverityMapper();
        });

        $this->app->singleton(LogPayloadBuilder::class, function ($app) {
            return new LogPayloadBuilder(
                $app->make(AttributeFormatter::class),
                $app['config']->get('otel-logging.attributes.global', [])
            );
        });

        // Register log channel
        $this->app->make('config')->set('logging.channels.otel', [
            'driver' => 'monolog',
            'handler' => OtelLogHandler::class,
        ]);
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

    private function isEnabled(): bool
    {
        return config('otel-logging.enabled', env('OTEL_ENABLED', false));
    }
}