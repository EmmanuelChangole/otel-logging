<?php

namespace Changole\OtelLogging\Otel;

/**
 * Formatter for converting log attributes into OTLP format.
 */
class AttributeFormatter
{
    /**
     * Format an attribute for OTLP.
     *
     * @param string $key The attribute key
     * @param mixed $value The attribute value
     * @return array The formatted attribute
     */
    public function formatAttribute(string $key, mixed $value): array
    {
        // Null values
        if (is_null($value)) {
            return [
                'key' => $key,
                'value' => ['stringValue' => 'null'],
            ];
        }

        // Boolean values
        elseif (is_bool($value)) {
            return [
                'key' => $key,
                'value' => ['boolValue' => $value],
            ];
        }

        // Integer values
        elseif (is_int($value)) {
            return [
                'key' => $key,
                'value' => ['intValue' => $value],
            ];
        }

        // Float values
        elseif (is_float($value)) {
            return [
                'key' => $key,
                'value' => ['doubleValue' => $value],
            ];
        }

        // String values
        elseif (is_string($value)) {
            return [
                'key' => $key,
                'value' => ['stringValue' => $value],
            ];
        }

        // Array values
        elseif (is_array($value)) {
            return [
                'key' => $key,
                'value' => ['stringValue' => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)],
            ];
        }

        // Object values
        elseif (is_object($value)) {
            return $this->formatObjectAttribute($key, $value);
        }

        // Unsupported types
        else {
            return [
                'key' => $key,
                'value' => ['stringValue' => 'unsupported type: ' . gettype($value)],
            ];
        }
    }

    /**
     * Format an object attribute.
     *
     * @param string $key The attribute key
     * @param object $value The object to format
     * @return array The formatted attribute
     */
    protected function formatObjectAttribute(string $key, object $value): array
    {
        // Handle exceptions
        if ($value instanceof \Throwable) {
            return [
                'key' => $key,
                'value' => ['stringValue' => get_class($value) . ': ' . $value->getMessage()],
            ];
        }

        // Handle objects with __toString method
        elseif (method_exists($value, '__toString')) {
            return [
                'key' => $key,
                'value' => ['stringValue' => (string) $value],
            ];
        }

        // Handle other objects
        else {
            return [
                'key' => $key,
                'value' => ['stringValue' => get_class($value)],
            ];
        }
    }

    /**
     * Process context into attributes.
     *
     * @param array $context The log context
     * @return array The formatted attributes
     */
    public function processContext(array $context): array
    {
        $attributes = [];

        foreach ($context as $key => $value) {
            if ($key === 'exception' && $value instanceof \Throwable) {
                $attributes[] = $this->formatAttribute('exception.type', get_class($value));
                $attributes[] = $this->formatAttribute('exception.message', $value->getMessage());
                $attributes[] = $this->formatAttribute('exception.stacktrace', $value->getTraceAsString());

                // Add additional exception details
                if (method_exists($value, 'getFile') && method_exists($value, 'getLine')) {
                    $attributes[] = $this->formatAttribute('exception.file', $value->getFile());
                    $attributes[] = $this->formatAttribute('exception.line', $value->getLine());
                }

                // Add previous exceptions if available
                if (method_exists($value, 'getPrevious') && $value->getPrevious()) {
                    $attributes[] = $this->formatAttribute(
                        'exception.previous',
                        $this->formatPreviousException($value->getPrevious())
                    );
                }
            } else {
                $attributes[] = $this->formatAttribute($key, $value);
            }
        }

        return $attributes;
    }

    /**
     * Format a previous exception for inclusion in attributes.
     *
     * @param \Throwable $exception The previous exception
     * @return string Formatted exception details
     */
    protected function formatPreviousException(\Throwable $exception): string
    {
        return sprintf(
            "%s: %s in %s:%d\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
    }
}