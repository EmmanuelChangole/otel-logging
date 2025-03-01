<?php

namespace Changole\OtelLogging\Otel;

use Illuminate\Contracts\Config\Repository;

/**
 * Configuration class for OpenTelemetry logging.
 */
class Config
{
    protected Repository $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Check if OpenTelemetry logging is enabled.
     *
     * @return bool True if OpenTelemetry logging is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config->get('otel-logging.enabled', false);
    }

    /**
     * Get the OpenTelemetry exporter endpoint.
     *
     * @return string The endpoint URL
     */
    public function getEndpoint(): string
    {
        return $this->config->get('otel-logging.exporter.endpoint');
    }

    /**
     * Get the service name for this application.
     *
     * @return string The service name
     */
    public function getServiceName(): string
    {
        return $this->config->get('otel-logging.service.name');
    }

    /**
     * Get the current environment.
     *
     * @return string The environment name
     */
    public function getEnvironment(): string
    {
        dump($this->config->get('otel-logging')); // Debug all otel-logging config
        dump($this->config->get('otel-logging.env'));
        return $this->config->get('otel-logging.env', 'production');
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool True if debug is enabled
     */
    public function isDebugEnabled(): bool
    {
        return $this->config->get('app.debug', false);
    }

    /**
     * Get the HTTP client timeout in seconds.
     *
     * @return int The timeout in seconds
     */
    public function getTimeout(): int
    {
        return (int) $this->config->get('otel-logging.exporter.timeout', 5);
    }
}