<?php

namespace Changole\OtelLogging\Otel;

use GuzzleHttp\Client;
use Monolog\Level;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating OtelLogHandler instances.
 */
class OtelHandlerFactory
{
    /**
     * Create a new OtelLogHandler instance with all dependencies
     *
     * @param array $config Configuration options
     * @param LoggerInterface|null $fallbackLogger Optional fallback logger
     * @param int|Level $level The minimum logging level
     * @param bool $bubble Whether to bubble to other handlers
     * @return OtelLogHandler
     */
    public static function create(
        ?LoggerInterface $fallbackLogger = null,
        int|Level        $level = Level::Debug,
        bool             $bubble = true
    ): OtelLogHandler {
        // Create configuration
        $configObj = new Config(app('config'));

        // Create formatter and mappers
        $attributeFormatter = new AttributeFormatter();
        $severityMapper = new SeverityMapper();

        // Create HTTP client
        $client = new Client([
            'http_errors' => false,
            'timeout' => $configObj->getTimeout(),
        ]);


        // Create payload builder
        $payloadBuilder = new LogPayloadBuilder(
            $attributeFormatter,
        );

        // Create and return the handler
        return new OtelLogHandler(
            $level,
            $bubble,
            $client,
            $configObj,
            $attributeFormatter,
            $severityMapper,
            $payloadBuilder,
            $fallbackLogger
        );
    }
}