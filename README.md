# Laravel OpenTelemetry Logging

[![Latest Version on Packagist](https://img.shields.io/packagist/v/changole/otel-logging.svg?style=flat-square)](https://packagist.org/packages/changole/otel-logging)
[![Total Downloads](https://img.shields.io/packagist/dt/changole/otel-logging.svg?style=flat-square)](https://packagist.org/packages/changole/otel-logging)
[![License](https://img.shields.io/packagist/l/changole/otel-logging?style=flat-square)](https://packagist.org/packages/changole/otel-logging)

A Laravel package that seamlessly integrates with OpenTelemetry by sending your application logs to OpenTelemetry collectors using the OTLP protocol.

## Features

- 🔄 Sends Laravel logs to an OpenTelemetry collector
- 🧩 Properly formats log attributes according to OTLP specification
- 🛡️ Handles exceptions gracefully with comprehensive error reporting
- ⚙️ Configurable via environment variables or config file
- 🔍 Supports distributed tracing context (trace and span IDs)
- 🔌 Compatible with Laravel 8, 9, 10, and 11

## Installation

You can install the package via composer:

```bash
composer require changole/otel-logging
```

## Configuration

### Publishing the Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=otel-logging-config
```

This will create a `config/otel-logging.php` file where you can configure the package.

### Environment Variables

Configure the package using environment variables in your `.env` file:

```dotenv
OTEL_ENABLED=true
OTEL_EXPORTER_ENDPOINT=https://collector.example.com/v1/logs
OTEL_SERVICE_NAME=my-laravel-app
OTEL_HTTP_TIMEOUT=5
```

## Usage

### Configure the Logging Channel

Add the OpenTelemetry channel to your logging configuration in `config/logging.php`:

```php
'channels' => [
    // ...
    'otel' => [
        'driver' => 'monolog',
        'handler' => Changole\OtelLogging\Otel\OtelLogHandler::class,
    ],
    
    // Or use it in a stack
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'otel'],
        'ignore_exceptions' => false,
    ],
],
```

### Basic Usage

Once configured, the handler will automatically send logs to your OpenTelemetry collector:

```php
// Using the dedicated channel
Log::channel('otel')->info('This is a test message', ['user_id' => 123]);

// Or when using the stack (if you added 'otel' to your stack channels)
Log::info('This message will go to both file and OpenTelemetry', [
    'user_id' => 123,
]);
```

### Logging Exceptions

Exception details are automatically formatted according to OpenTelemetry conventions:

```php
try {
    // Your code
} catch (\Exception $e) {
    Log::error('Error processing payment', [
        'exception' => $e,
        'payment_id' => $paymentId,
    ]);
}
```

### Distributed Tracing

Add trace and span IDs to correlate logs with traces:

```php
Log::info('Processing order', [
    'order_id' => $order->id,
    'trace_id' => $traceId,
    'span_id' => $spanId,
]);
```

## Configuration Options

| Environment Variable | Description | Default |
|---------------------|-------------|---------|
| `OTEL_ENABLED` | Enable or disable OpenTelemetry logging | `false` |
| `OTEL_EXPORTER_ENDPOINT` | The URL of the OpenTelemetry collector | `https://collector.example.com/v1/logs` |
| `OTEL_SERVICE_NAME` | The name of your service | `laravel-app` |
| `OTEL_HTTP_TIMEOUT` | Timeout for HTTP requests in seconds | `5` |

## Troubleshooting

### Logs aren't being sent to the collector

1. Check that `OTEL_ENABLED` is set to `true` in your `.env` file
2. Verify your collector endpoint is correct and accessible
3. Check your Laravel logs for any error messages related to sending logs

### Performance considerations

The package is designed to have minimal impact on performance, but if you're concerned about performance in high-volume environments:

1. Consider using a batched collector endpoint
2. Set an appropriate log level to reduce the volume of logs

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Emmanuel Changole](https://github.com/EmmanuelChangole)
- [All Contributors](../../contributors)

## About OpenTelemetry

[OpenTelemetry](https://opentelemetry.io/) is a collection of tools, APIs, and SDKs used to instrument, generate, collect, and export telemetry data (logs) for analysis in order to understand what is happening on your software's.