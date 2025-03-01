<?php

namespace Changole\OtelLogging\Otel;

use Monolog\Level;

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
    public const SEVERITY_FATAL = 1;
    public const SEVERITY_ERROR = 2;
    public const SEVERITY_ERROR2 = 3;  // Alternative ERROR level
    public const SEVERITY_WARN = 4;
    public const SEVERITY_INFO = 5;
    public const SEVERITY_INFO2 = 9;   // Alternative INFO level
    public const SEVERITY_DEBUG = 17;
    public const SEVERITY_TRACE = 18;  // Not used by Monolog but here for completeness

    /**
     * Map Monolog level to OpenTelemetry severity number.
     *
     * @param int $level Monolog level value
     * @return int OpenTelemetry severity number
     */
    public function mapLevelToSeverity(int $level): int
    {
        return match ($level) {
            Level::Emergency->value => self::SEVERITY_FATAL,
            Level::Alert->value, Level::Critical->value => self::SEVERITY_ERROR,
            Level::Error->value => self::SEVERITY_ERROR2,
            Level::Warning->value => self::SEVERITY_WARN,
            Level::Notice->value => self::SEVERITY_INFO,
            Level::Info->value => self::SEVERITY_INFO2,
            Level::Debug->value => self::SEVERITY_DEBUG,
            default => self::SEVERITY_INFO2,
        };
    }

    /**
     * Get the text representation of a Monolog level.
     *
     * @param Level $level Monolog level
     * @return string The lower-case text representation
     */
    public function getLevelName(Level $level): string
    {
        return strtolower($level->name);
    }
}