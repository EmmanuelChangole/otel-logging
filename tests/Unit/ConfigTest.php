<?php

namespace Changole\OtelLogging\Tests\Unit;

use Changole\OtelLogging\Otel\Config;
use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function test_it_reads_auth_header_from_config(): void
    {
        $config = new Config(new Repository([
            'otel-logging' => [
                'exporter' => [
                    'auth_header' => 'Basic abc123',
                ],
            ],
        ]));

        $this->assertSame('Basic abc123', $config->getAuthHeader());
    }

    public function test_it_returns_empty_auth_header_when_missing(): void
    {
        $config = new Config(new Repository([
            'otel-logging' => [
                'exporter' => [],
            ],
        ]));

        $this->assertSame('', $config->getAuthHeader());
    }

    public function test_it_does_not_read_auth_header_from_env(): void
    {
        // env() must not be a fallback: config:cache environments cannot use it.
        $previous = getenv('OTEL_EXPORTER_AUTH_HEADER');
        putenv('OTEL_EXPORTER_AUTH_HEADER=Basic from-env');

        try {
            $config = new Config(new Repository([
                'otel-logging' => [
                    'exporter' => [],
                ],
            ]));

            $this->assertSame('', $config->getAuthHeader());
        } finally {
            if ($previous === false) {
                putenv('OTEL_EXPORTER_AUTH_HEADER');
            } else {
                putenv('OTEL_EXPORTER_AUTH_HEADER='.$previous);
            }
        }
    }
}
