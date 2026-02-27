<?php

namespace Changole\OtelLogging\Tests\Unit;

use Changole\OtelLogging\Otel\AttributeFormatter;
use PHPUnit\Framework\TestCase;

class AttributeFormatterTest extends TestCase
{
    public function test_it_formats_scalar_attribute_types(): void
    {
        $formatter = new AttributeFormatter();

        $this->assertSame(['key' => 'str', 'value' => ['stringValue' => 'abc']], $formatter->formatAttribute('str', 'abc'));
        $this->assertSame(['key' => 'int', 'value' => ['intValue' => 12]], $formatter->formatAttribute('int', 12));
        $this->assertSame(['key' => 'float', 'value' => ['doubleValue' => 1.5]], $formatter->formatAttribute('float', 1.5));
        $this->assertSame(['key' => 'bool', 'value' => ['boolValue' => true]], $formatter->formatAttribute('bool', true));
    }

    public function test_it_expands_exception_context_using_otel_exception_keys(): void
    {
        $formatter = new AttributeFormatter();
        $previous = new \RuntimeException('previous');
        $exception = new \InvalidArgumentException('bad input', 0, $previous);

        $attributes = $formatter->processContext([
            'exception' => $exception,
            'request_id' => 'abc-123',
        ]);

        $keys = array_column($attributes, 'key');

        $this->assertContains('exception.type', $keys);
        $this->assertContains('exception.message', $keys);
        $this->assertContains('exception.stacktrace', $keys);
        $this->assertContains('exception.file', $keys);
        $this->assertContains('exception.line', $keys);
        $this->assertContains('exception.previous', $keys);
        $this->assertContains('request_id', $keys);
    }

    public function test_it_handles_array_json_encode_failures_safely(): void
    {
        $formatter = new AttributeFormatter();
        $invalidUtf8 = "\xB1\x31";
        $attribute = $formatter->formatAttribute('payload', ['bad' => $invalidUtf8]);

        $this->assertSame('payload', $attribute['key']);
        $this->assertArrayHasKey('stringValue', $attribute['value']);
        $this->assertStringStartsWith('[json_encode_error] ', $attribute['value']['stringValue']);
    }
}
