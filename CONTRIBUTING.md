# Installation Guide for Laravel OpenTelemetry Logging

This guide provides detailed steps to install and configure the `changole/otel-logging` package in your Laravel application.

## Prerequisites

- Laravel 8.x, 9.x, 10.x, or 11.x
- PHP 8.0 or higher
- An OpenTelemetry collector endpoint

## Step 1: Install the Package

Install the package via Composer:

```bash
composer require changole/otel-logging
```

## Step 2: Publish the Configuration

Publish the package configuration file:

```bash
php artisan vendor:publish --tag=otel-logging-config
```

This will create a `config/otel-logging.php` file in your application.

## Step 3: Configure Environment Variables

Add the following variables to your `.env` file:

```dotenv
# Enable/disable OpenTelemetry logging
OTEL_ENABLED=true

# Your OpenTelemetry collector endpoint
OTEL_EXPORTER_ENDPOINT=https://collector.example.com/v1/logs

# Your service/application name
OTEL_SERVICE_NAME=my-laravel-app

# Timeout for HTTP requests in seconds
OTEL_HTTP_TIMEOUT=5
```

## Step 4: Configure Logging Channels

Update your `config/logging.php` file to add the OpenTelemetry channel:

### Option A: Use as a standalone channel

```php
'channels' => [
    // ... other channels
    
    'otel' => [
        'driver' => 'monolog',
        'handler' => Changole\OtelLogging\Otel\OtelLogHandler::class,
    ],
],
```

### Option B: Add to your stack channel (recommended)

```php
'channels' => [
    // ... other channels
    
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'otel'], // Add 'otel' to your existing channels
        'ignore_exceptions' => false,
    ],
],
```

## Step 5: Verify Installation

To verify that the package is properly installed and configured, add a test log message to your application:

```php
// In a controller or route
Log::info('OpenTelemetry logging test', ['test_key' => 'test_value']);
```

Check your OpenTelemetry collector or visualization tool to confirm that the log was received.

## Advanced Configuration

### Custom Configuration

If you need to modify the default configuration, edit the published `config/otel-logging.php` file:

```php
return [
    // Enable or disable OpenTelemetry logging
    'enabled' => env('OTEL_ENABLED', false),
    
    // Exporter settings
    'exporter' => [
        'endpoint' => env('OTEL_EXPORTER_ENDPOINT', 'https://collector.example.com/v1/logs'),
        'timeout' => env('OTEL_HTTP_TIMEOUT', 5),
    ],
    
    // Service information
    'service' => [
        'name' => env('OTEL_SERVICE_NAME', 'laravel-app'),
    ],
    
    // Additional attributes
    'attributes' => [
        'global' => [
            // Add global attributes that will be included with every log
            // 'environment' => 'production',
            // 'region' => 'us-east-1',
        ],
    ],
];
```

### Integration with Distributed Tracing

If you're using distributed tracing, you can correlate your logs with traces:

```php
Log::info('Processing request', [
    'trace_id' => $traceId,  // The trace ID from your tracing system
    'span_id' => $spanId,    // The current span ID
    'request_id' => $request->id(),
]);
```

## Troubleshooting

### Missing Dependencies

If you encounter errors related to missing extensions, ensure your PHP installation has the required extensions:

```bash
# For Debian/Ubuntu
sudo apt-get install php-mbstring php-xml

# For CentOS/RHEL
sudo yum install php-mbstring php-xml
```

### Log Transport Errors

If logs aren't being sent to the collector, check your Laravel log file for transport errors. You might see messages about connection failures or timeouts when trying to send logs to the collector.

### Validate Your Collector Endpoint

Ensure your collector endpoint is accessible from your application server:

```bash
curl -v https://your-collector-endpoint.com/v1/logs
```

## Need More Help?

If you encounter any issues during installation or configuration, please:

1. Check the [GitHub repository](https://github.com/changole/otel-logging) for existing issues
2. Open a new issue if your problem hasn't been reported

## Next Steps

After installation, check out the README.md file for usage examples and advanced features.