<?php

namespace Changole\OtelLogging\Otel;

use GuzzleHttp\Client;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating OtelLogHandler instances.
 */
class OtelHandlerFactory
{
    /**
     * Create a new OtelLogHandler instance with all dependencies
     *
     * @param LoggerInterface|null $fallbackLogger Optional fallback logger
     * @param int $level The minimum logging level
     * @param bool $bubble Whether to bubble to other handlers
     * @return OtelLogHandler
     */
    public static function create(
        ?LoggerInterface $fallbackLogger = null,
        int              $level = Logger::DEBUG,
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
        $globalAttributes = app('config')->get('otel-logging.attributes.global', []);
        $payloadBuilder = new LogPayloadBuilder(
            $attributeFormatter,
            is_array($globalAttributes) ? $globalAttributes : []
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
