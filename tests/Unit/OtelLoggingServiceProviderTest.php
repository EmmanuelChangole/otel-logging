<?php

namespace Changole\OtelLogging\Tests\Unit;

use Changole\OtelLogging\Otel\OtelLogHandler;
use Changole\OtelLogging\OtelLoggingServiceProvider;
use Orchestra\Testbench\TestCase;

class OtelLoggingServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [OtelLoggingServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('otel-logging.enabled', true);
        $app['config']->set('logging.channels', []);
    }

    public function test_it_registers_the_otel_channel_when_enabled(): void
    {
        (new OtelLoggingServiceProvider($this->app))->register();
        $channel = $this->app['config']->get('logging.channels.otel');

        $this->assertSame('monolog', $channel['driver']);
        $this->assertSame(OtelLogHandler::class, $channel['handler']);
    }

    public function test_it_does_not_override_existing_otel_channel(): void
    {
        $this->app['config']->set('logging.channels.otel', [
            'driver' => 'single',
            'path' => storage_path('logs/custom-otel.log'),
        ]);

        (new OtelLoggingServiceProvider($this->app))->register();

        $channel = $this->app['config']->get('logging.channels.otel');
        $this->assertSame('single', $channel['driver']);
        $this->assertSame(storage_path('logs/custom-otel.log'), $channel['path']);
    }
}
