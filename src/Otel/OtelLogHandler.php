<?php

namespace Changole\OtelLogging\Otel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;

/**
 * Monolog handler for sending logs to an OpenTelemetry collector.
 */
class OtelLogHandler extends AbstractProcessingHandler
{
    protected Client $client;
    protected Config $config;
    protected AttributeFormatter $attributeFormatter;
    protected SeverityMapper $severityMapper;
    protected LogPayloadBuilder $payloadBuilder;
    protected ?LoggerInterface $fallbackLogger;

    /**
     * Constructor.
     */
    public function __construct(
        $level = Level::Debug,
        bool $bubble = true,
        ?Client $client = null,
        ?Config $config = null,
        ?AttributeFormatter $attributeFormatter = null,
        ?SeverityMapper $severityMapper = null,
        ?LogPayloadBuilder $payloadBuilder = null,
        ?LoggerInterface $fallbackLogger = null
    ) {
        parent::__construct($level, $bubble);

        $this->config = $config ?? new Config(app('config'));
        $this->attributeFormatter = $attributeFormatter ?? new AttributeFormatter();
        $this->severityMapper = $severityMapper ?? new SeverityMapper();
        $this->payloadBuilder = $payloadBuilder ?? new LogPayloadBuilder($this->attributeFormatter);
        $this->fallbackLogger = $fallbackLogger;

        $this->client = $client ?? new Client([
            'http_errors' => false,
            'timeout' => $this->config->getTimeout(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function write(LogRecord $record): void
    {
        try {
            $payload = $this->payloadBuilder->build(
                $record,
                $this->severityMapper,
                $this->config->getServiceName(),
                $this->config->getEnvironment()
            );

            $this->sendPayload($payload);
        } catch (\Throwable $e) {
            $this->handleSendError($e);
        }
    }

    /**
     * Send the payload to the OpenTelemetry collector.
     *
     * @param array $payload The formatted payload
     * @throws GuzzleException If the request fails
     */
    protected function sendPayload(array $payload): void
    {
        $this->client->post($this->config->getEndpoint(), [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);
    }

    /**
     * Handle errors that occur when sending logs.
     *
     * @param \Throwable $e The exception that occurred
     */
    protected function handleSendError(\Throwable $e): void
    {
        static  $inErrorHandler = false;
        if($inErrorHandler) {
            return; //We are already in the error handler exit to prevent loops
        }
        try{
            $inErrorHandler = true;
            if($this->fallbackLogger){
                $this->fallbackLogger->error('Failed to send log to OpenTelemetry: ' . $e->getMessage(), [
                    'exception' => $e,
                ]);
            } elseif ($this->config->isDebugEnabled()) {
                error_log('Failed to send log to OpenTelemetry: ' . $e->getMessage());
            }
        } finally {
            $inErrorHandler = false; //Reset the error handler even if an exception is thrown
        }
    }
}