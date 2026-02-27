<?php

namespace Changole\OtelLogging\Otel;

/**
 * Builder for creating an OTLP log payload.
 */
class LogPayloadBuilder
{
    protected AttributeFormatter $attributeFormatter;
    protected array $globalAttributes;

    /**
     * Constructor.
     */
    public function __construct(AttributeFormatter $attributeFormatter, array $globalAttributes = [])
    {
        $this->attributeFormatter = $attributeFormatter;
        $this->globalAttributes = $globalAttributes;
    }

    /**
     * Build the complete OTLP payload from a log record.
     *
     * @param mixed $record The Monolog log record (array for Monolog 2, LogRecord for Monolog 3)
     * @param SeverityMapper $severityMapper The severity mapper
     * @param string $serviceName The service name
     * @param string $environment The deployment environment
     * @return array The complete OTLP payload
     */
    public function build(
        mixed $record,
        SeverityMapper $severityMapper,
        string $serviceName,
        string $environment
    ): array {
        $logRecord = $this->createLogRecord($record, $severityMapper);
        $resourceAttributes = $this->createResourceAttributes($serviceName, $environment);

        return [
            'resourceLogs' => [
                [
                    'resource' => [
                        'attributes' => $resourceAttributes,
                    ],
                    'scopeLogs' => [
                        [
                            'scope' => [
                                'name' => 'laravel-logs',
                                'version' => $this->resolveScopeVersion(),
                            ],
                            'logRecords' => [$logRecord],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create the log record structure.
     *
     * @param mixed $record The Monolog log record
     * @param SeverityMapper $severityMapper The severity mapper
     * @return array The formatted log record
     */
    protected function createLogRecord(mixed $record, SeverityMapper $severityMapper): array
    {
        // Format timestamp as nanoseconds since epoch
        $timestamp = $this->extractTimestamp($record);
        $timeUnixNano = (string) (int) ($timestamp->format('U.u') * 1e9);
        $level = $this->extractLevel($record);
        $severityNumber = $severityMapper->mapLevelToSeverity($level);
        $severityText = $severityMapper->getLevelName($level);
        $context = $this->extractContext($record);
        $traceId = $this->sanitizeTraceId($context['trace_id'] ?? null);
        $spanId = $this->sanitizeSpanId($context['span_id'] ?? null);
        $flags = $this->resolveTraceFlags($context['trace_flags'] ?? null, $traceId, $spanId);
        unset($context['trace_id'], $context['span_id']);
        unset($context['trace_flags']);
        $attributes = array_merge(
            $this->attributeFormatter->processContext($this->globalAttributes),
            $this->attributeFormatter->processContext($context)
        );

        // Create the log record
        return [
            'timeUnixNano' => $timeUnixNano,
            'severityNumber' => $severityNumber,
            'severityText' => $severityText,
            'body' => [
                'stringValue' => $this->extractMessage($record),
            ],
            'attributes' => $attributes,
            'droppedAttributesCount' => 0,
            'flags' => $flags,
            'traceId' => $traceId,
            'spanId' => $spanId,
        ];
    }

    protected function sanitizeTraceId(mixed $traceId): string
    {
        if (!is_scalar($traceId)) {
            return '';
        }

        $value = strtolower(trim((string) $traceId));
        if (preg_match('/^[0-9a-f]{32}$/', $value) !== 1 || $value === str_repeat('0', 32)) {
            return '';
        }

        return $value;
    }

    protected function sanitizeSpanId(mixed $spanId): string
    {
        if (!is_scalar($spanId)) {
            return '';
        }

        $value = strtolower(trim((string) $spanId));
        if (preg_match('/^[0-9a-f]{16}$/', $value) !== 1 || $value === str_repeat('0', 16)) {
            return '';
        }

        return $value;
    }

    protected function resolveTraceFlags(mixed $traceFlags, string $traceId, string $spanId): int
    {
        if ($traceId === '' || $spanId === '') {
            return 0;
        }

        if (is_int($traceFlags) && $traceFlags >= 0 && $traceFlags <= 255) {
            return $traceFlags;
        }

        if (is_string($traceFlags) && ctype_digit($traceFlags)) {
            $numericTraceFlags = (int) $traceFlags;
            if ($numericTraceFlags >= 0 && $numericTraceFlags <= 255) {
                return $numericTraceFlags;
            }
        }

        // Mark as sampled by default when valid tracing context exists.
        return 1;
    }

    /**
     * Create the resource attributes.
     *
     * @param string $serviceName The service name
     * @param string $environment The deployment environment
     * @return array The formatted resource attributes
     */
    protected function createResourceAttributes(string $serviceName, string $environment): array
    {
        $resourceAttributes = [];
        $resourceAttributes[] = $this->attributeFormatter->formatAttribute('service.name', $serviceName);
        $resourceAttributes[] = $this->attributeFormatter->formatAttribute('deployment.environment', $environment);
        $resourceAttributes[] = $this->attributeFormatter->formatAttribute('service.version', $this->resolveServiceVersion());
        $resourceAttributes[] = $this->attributeFormatter->formatAttribute('host.name', gethostname() ?: 'unknown');

        return $resourceAttributes;
    }

    protected function resolveServiceVersion(): string
    {
        if (function_exists('config')) {
            try {
                return (string) config('app.version', '1.0.0');
            } catch (\Throwable) {
                // Fall back outside Laravel runtime.
            }
        }

        return '1.0.0';
    }

    protected function resolveScopeVersion(): string
    {
        if (function_exists('app')) {
            try {
                $app = app();
                if (is_object($app) && method_exists($app, 'version')) {
                    return (string) $app->version();
                }
            } catch (\Throwable) {
                // Fall back outside Laravel runtime.
            }
        }

        return 'unknown';
    }

    protected function extractTimestamp(mixed $record): \DateTimeImmutable
    {
        if (is_array($record) && isset($record['datetime']) && $record['datetime'] instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($record['datetime']);
        }

        if (is_object($record) && isset($record->datetime) && $record->datetime instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($record->datetime);
        }

        return new \DateTimeImmutable();
    }

    protected function extractLevel(mixed $record): int
    {
        if (is_array($record)) {
            return (int) ($record['level'] ?? 200);
        }

        if (is_object($record) && isset($record->level)) {
            $level = $record->level;
            if (is_object($level) && isset($level->value)) {
                return (int) $level->value;
            }

            return (int) $level;
        }

        return 200;
    }

    protected function extractMessage(mixed $record): string
    {
        if (is_array($record)) {
            return (string) ($record['message'] ?? '');
        }

        if (is_object($record) && isset($record->message)) {
            return (string) $record->message;
        }

        return '';
    }

    protected function extractContext(mixed $record): array
    {
        if (is_array($record) && isset($record['context']) && is_array($record['context'])) {
            return $record['context'];
        }

        if (is_object($record) && isset($record->context) && is_array($record->context)) {
            return $record->context;
        }

        return [];
    }
}
