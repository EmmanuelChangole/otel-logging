<?php

return [

'enabled' => env('OTEL_LOGGING_ENABLED', false),

'service' => [

'name' => env('OTEL_SERVICE_NAME', 'my-app'),
],

'exporter' => [

'endpoint' => env('OTEL_EXPORTER_ENDPOINT', 'http://localhost:4318'),

'timeout' => env('OTEL_EXPORTER_TIMEOUT', 5),

],
'env' => env('OTEL_ENVIRONMENT', 'production'),
'debug' => env('APP_DEBUG', true),
];
