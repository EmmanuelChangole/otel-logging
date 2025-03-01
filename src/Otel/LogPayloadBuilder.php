<?php

namespace Changole\OtelLogging\Otel;

use Monolog\LogRecord;

/**
 * Builder for creating an OTLP log payload.
 */
class LogPayloadBuilder
{
    protected AttributeFormatter $attributeFormatter;

    /**
     * Constructor.
     */
    public function __construct(AttributeFormatter $attributeFormatter)
    {
        $this->attributeFormatter = $attributeFormatter;
    }

    /**
     * Build the complete OTLP payload from a log record.
     *
     * @param LogRecord $record The Monolog log record
     * @param SeverityMapper $severityMapper The severity mapper
     * @param string $serviceName The service name
     * @param string $environment The deployment environment
     * @return array The complete OTLP payload
     */
    public function build(
        LogRecord $record,
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
                                'version' => app()->version(),
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
     * @param LogRecord $record The Monolog log record
     * @param SeverityMapper $severityMapper The severity mapper
     * @return array The formatted log record
     */
    protected function createLogRecord(LogRecord $record, SeverityMapper $severityMapper): array
    {
        // Format timestamp as nanoseconds since epoch
        $timeUnixNano = (int) ($record->datetime->format('U.u') * 1e9);
        $severityNumber = $severityMapper->mapLevelToSeverity($record->level->value);
        $severityText = $severityMapper->getLevelName($record->level);

        // Create the log record
        return [
            'timeUnixNano' => (string) $timeUnixNano,
            'severityNumber' => $severityNumber,
            'severityText' => $severityText,
            'body' => [
                'stringValue' => $record->message,
            ],
            'attributes' => $this->attributeFormatter->processContext($record->context),
            'droppedAttributesCount' => 0,
            'flags' => 1, // Bit field used to represent boolean attributes
            'traceId' => $record->context['trace_id'] ?? '',
            'spanId' => $record->context['span_id'] ?? '',
        ];
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
        $resourceAttributes[] = $this->attributeFormatter->formatAttribute('service.version', config('app.version', '1.0.0'));
        $resourceAttributes[] = $this->attributeFormatter->formatAttribute('host.name', gethostname());

        return $resourceAttributes;
    }
}