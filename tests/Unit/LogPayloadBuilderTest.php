<?php

namespace Changole\OtelLogging\Tests\Unit;

use Changole\OtelLogging\Otel\AttributeFormatter;
use Changole\OtelLogging\Otel\LogPayloadBuilder;
use Changole\OtelLogging\Otel\SeverityMapper;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LogPayloadBuilderTest extends TestCase
{
    public function test_it_builds_otlp_payload_with_resource_and_log_data(): void
    {
        $builder = new LogPayloadBuilder(new AttributeFormatter(), ['region' => 'us-east-1']);
        $record = [
            'datetime' => new \DateTimeImmutable('2025-01-01T00:00:00+00:00'),
            'level' => Logger::INFO,
            'message' => 'Order processed',
            'context' => [
                'order_id' => 42,
                'trace_id' => '0123456789abcdef0123456789abcdef',
                'span_id' => '0123456789abcdef',
            ],
        ];

        $payload = $builder->build($record, new SeverityMapper(), 'shop-api', 'testing');

        $this->assertArrayHasKey('resourceLogs', $payload);
        $logRecord = $payload['resourceLogs'][0]['scopeLogs'][0]['logRecords'][0];

        $this->assertSame('Order processed', $logRecord['body']['stringValue']);
        $this->assertSame(9, $logRecord['severityNumber']);
        $this->assertSame('info', $logRecord['severityText']);
        $this->assertSame('0123456789abcdef0123456789abcdef', $logRecord['traceId']);
        $this->assertSame('0123456789abcdef', $logRecord['spanId']);

        $attributeKeys = array_column($logRecord['attributes'], 'key');
        $this->assertContains('region', $attributeKeys);
        $this->assertContains('order_id', $attributeKeys);
        $this->assertNotContains('trace_id', $attributeKeys);
        $this->assertNotContains('span_id', $attributeKeys);

        $resourceAttributes = $payload['resourceLogs'][0]['resource']['attributes'];
        $this->assertSame('shop-api', $this->attributeValue($resourceAttributes, 'service.name'));
        $this->assertSame('testing', $this->attributeValue($resourceAttributes, 'deployment.environment'));
    }

    public function test_it_drops_invalid_trace_context_and_sets_flags_to_zero(): void
    {
        $builder = new LogPayloadBuilder(new AttributeFormatter());
        $record = [
            'datetime' => new \DateTimeImmutable('2025-01-01T00:00:00+00:00'),
            'level' => Logger::INFO,
            'message' => 'Invalid trace context',
            'context' => [
                'trace_id' => 'not-a-trace-id',
                'span_id' => 'bad-span',
                'trace_flags' => 255,
            ],
        ];

        $payload = $builder->build($record, new SeverityMapper(), 'shop-api', 'testing');
        $logRecord = $payload['resourceLogs'][0]['scopeLogs'][0]['logRecords'][0];
        $attributeKeys = array_column($logRecord['attributes'], 'key');

        $this->assertSame('', $logRecord['traceId']);
        $this->assertSame('', $logRecord['spanId']);
        $this->assertSame(0, $logRecord['flags']);
        $this->assertNotContains('trace_id', $attributeKeys);
        $this->assertNotContains('span_id', $attributeKeys);
        $this->assertNotContains('trace_flags', $attributeKeys);
    }

    public function test_it_uses_trace_flags_from_context_when_trace_context_is_valid(): void
    {
        $builder = new LogPayloadBuilder(new AttributeFormatter());
        $record = [
            'datetime' => new \DateTimeImmutable('2025-01-01T00:00:00+00:00'),
            'level' => Logger::INFO,
            'message' => 'Valid trace context',
            'context' => [
                'trace_id' => '0123456789abcdef0123456789abcdef',
                'span_id' => '0123456789abcdef',
                'trace_flags' => '5',
            ],
        ];

        $payload = $builder->build($record, new SeverityMapper(), 'shop-api', 'testing');
        $logRecord = $payload['resourceLogs'][0]['scopeLogs'][0]['logRecords'][0];

        $this->assertSame('0123456789abcdef0123456789abcdef', $logRecord['traceId']);
        $this->assertSame('0123456789abcdef', $logRecord['spanId']);
        $this->assertSame(5, $logRecord['flags']);
    }

    private function attributeValue(array $attributes, string $key): mixed
    {
        foreach ($attributes as $attribute) {
            if (($attribute['key'] ?? null) !== $key) {
                continue;
            }

            return reset($attribute['value']);
        }

        return null;
    }
}
