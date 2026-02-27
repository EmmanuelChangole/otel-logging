<?php

namespace Changole\OtelLogging\Tests\Unit;

use Changole\OtelLogging\Otel\SeverityMapper;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class SeverityMapperTest extends TestCase
{
    public function test_it_maps_monolog_levels_to_otel_severity_numbers(): void
    {
        $mapper = new SeverityMapper();

        $this->assertSame(24, $mapper->mapLevelToSeverity(Logger::EMERGENCY));
        $this->assertSame(23, $mapper->mapLevelToSeverity(Logger::ALERT));
        $this->assertSame(22, $mapper->mapLevelToSeverity(Logger::CRITICAL));
        $this->assertSame(17, $mapper->mapLevelToSeverity(Logger::ERROR));
        $this->assertSame(13, $mapper->mapLevelToSeverity(Logger::WARNING));
        $this->assertSame(9, $mapper->mapLevelToSeverity(Logger::NOTICE));
        $this->assertSame(9, $mapper->mapLevelToSeverity(Logger::INFO));
        $this->assertSame(5, $mapper->mapLevelToSeverity(Logger::DEBUG));
    }

    public function test_it_returns_lowercase_level_name(): void
    {
        $mapper = new SeverityMapper();

        $this->assertSame('error', $mapper->getLevelName(Logger::ERROR));
    }
}
