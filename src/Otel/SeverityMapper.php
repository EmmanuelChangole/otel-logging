<?php

namespace Changole\OtelLogging\Otel;

use Monolog\Logger;

/**
 * Map Monolog levels to OpenTelemetry severity numbers.
 */
class SeverityMapper
{
    /**
     * OpenTelemetry severity number constants.
     *
     * @see https://github.com/open-telemetry/opentelemetry-specification/blob/main/specification/logs/data-model.md#severity-fields
     */
    public const SEVERITY_TRACE = 1;
    public const SEVERITY_DEBUG = 5;
    public const SEVERITY_INFO = 9;
    public const SEVERITY_WARN = 13;
    public const SEVERITY_ERROR = 17;
    public const SEVERITY_FATAL = 21;

    /**
     * Map Monolog level to OpenTelemetry severity number.
     *
     * @param int $level Monolog level value
     * @return int OpenTelemetry severity number
     */
    public function mapLevelToSeverity(int $level): int
    {
        return match ($level) {
            Logger::EMERGENCY => 24,
            Logger::ALERT => 23,
            Logger::CRITICAL => 22,
            Logger::ERROR => self::SEVERITY_ERROR,
            Logger::WARNING => self::SEVERITY_WARN,
            Logger::NOTICE, Logger::INFO => self::SEVERITY_INFO,
            Logger::DEBUG => self::SEVERITY_DEBUG,
            default => self::SEVERITY_INFO,
        };
    }

    /**
     * Get the text representation of a Monolog level.
     *
     * @param int $level Monolog level
     * @return string The lower-case text representation
     */
    public function getLevelName(int $level): string
    {
        try {
            return strtolower(Logger::getLevelName($level));
        } catch (\Throwable) {
            return 'info';
        }
    }
}
