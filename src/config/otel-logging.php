<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry Logging Settings
    |--------------------------------------------------------------------------
    */

    // Enable or disable OpenTelemetry logging
    'enabled' => env('OTEL_ENABLED', false),

    // Exporter settings
    'exporter' => [
        'endpoint' => env('OTEL_EXPORTER_ENDPOINT'),
        'timeout' => env('OTEL_HTTP_TIMEOUT', 5),
    ],

    // Service information
    'service' => [
        'name' => env('OTEL_SERVICE_NAME', 'laravel-app'),
    ],

    // Additional attributes
    'attributes' => [
        'global' => [],
    ],

    'env' => env('APP_ENV','production')
];