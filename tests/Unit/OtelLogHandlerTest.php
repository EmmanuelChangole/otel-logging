<?php

namespace Changole\OtelLogging\Tests\Unit;

use Changole\OtelLogging\Otel\OtelLogHandler;
use Changole\OtelLogging\Otel\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Config\Repository;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OtelLogHandlerTest extends TestCase
{
    public function test_it_reports_non_success_exporter_responses_to_fallback_logger(): void
    {
        $client = $this->makeClientWithResponses(new Response(500));
        $config = new Config(new Repository([
            'otel-logging' => [
                'enabled' => true,
                'exporter' => [
                    'endpoint' => 'http://collector.test/v1/logs',
                    'timeout' => 1,
                ],
                'service' => ['name' => 'test-app'],
                'env' => 'testing',
            ],
            'app' => ['debug' => false],
        ]));

        $fallbackLogger = $this->createMock(LoggerInterface::class);
        $fallbackLogger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('HTTP 500'),
                $this->arrayHasKey('exception')
            );

        $handler = new class(Logger::DEBUG, true, $client, $config, null, null, null, $fallbackLogger) extends OtelLogHandler {
            public function publicWrite(array $record): void
            {
                $this->write($record);
            }
        };

        $handler->publicWrite($this->makeRecord());
    }

    public function test_it_does_not_report_when_exporter_response_is_successful(): void
    {
        $client = $this->makeClientWithResponses(new Response(204));
        $config = new Config(new Repository([
            'otel-logging' => [
                'enabled' => true,
                'exporter' => [
                    'endpoint' => 'http://collector.test/v1/logs',
                    'timeout' => 1,
                ],
                'service' => ['name' => 'test-app'],
                'env' => 'testing',
            ],
            'app' => ['debug' => false],
        ]));

        $fallbackLogger = $this->createMock(LoggerInterface::class);
        $fallbackLogger->expects($this->never())->method('error');

        $handler = new class(Logger::DEBUG, true, $client, $config, null, null, null, $fallbackLogger) extends OtelLogHandler {
            public function publicWrite(array $record): void
            {
                $this->write($record);
            }
        };

        $handler->publicWrite($this->makeRecord());
    }

    public function test_it_skips_export_and_warns_once_when_endpoint_is_empty(): void
    {
        $mock = new MockHandler([new Response(204)]);
        $history = [];
        $stack = HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));
        $client = new Client(['handler' => $stack, 'http_errors' => false]);

        $config = new Config(new Repository([
            'otel-logging' => [
                'enabled' => true,
                'exporter' => [
                    'endpoint' => '   ',
                    'timeout' => 1,
                ],
                'service' => ['name' => 'test-app'],
                'env' => 'testing',
            ],
            'app' => ['debug' => false],
        ]));

        $fallbackLogger = $this->createMock(LoggerInterface::class);
        $fallbackLogger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('OTEL_EXPORTER_ENDPOINT is empty'));
        $fallbackLogger->expects($this->never())->method('error');

        $handler = new class(Logger::DEBUG, true, $client, $config, null, null, null, $fallbackLogger) extends OtelLogHandler {
            public function publicWrite(array $record): void
            {
                $this->write($record);
            }
        };

        // Repeated log calls must not send requests or warn again.
        $handler->publicWrite($this->makeRecord());
        $handler->publicWrite($this->makeRecord());

        $this->assertCount(0, $history);
    }

    public function test_it_sends_authorization_header_when_configured(): void
    {
        $mock = new MockHandler([new Response(204)]);
        $history = [];
        $stack = HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));
        $client = new Client(['handler' => $stack, 'http_errors' => false]);

        $config = new Config(new Repository([
            'otel-logging' => [
                'enabled' => true,
                'exporter' => [
                    'endpoint' => 'http://collector.test/v1/logs',
                    'timeout' => 1,
                    'auth_header' => 'Basic dXNlcjpwYXNz',
                ],
                'service' => ['name' => 'test-app'],
                'env' => 'testing',
            ],
            'app' => ['debug' => false],
        ]));

        $fallbackLogger = $this->createMock(LoggerInterface::class);
        $fallbackLogger->expects($this->never())->method('error');

        $handler = new class(Logger::DEBUG, true, $client, $config, null, null, null, $fallbackLogger) extends OtelLogHandler {
            public function publicWrite(array $record): void
            {
                $this->write($record);
            }
        };

        $handler->publicWrite($this->makeRecord());

        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertSame('Basic dXNlcjpwYXNz', $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function test_it_omits_authorization_header_when_not_configured(): void
    {
        $mock = new MockHandler([new Response(204)]);
        $history = [];
        $stack = HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));
        $client = new Client(['handler' => $stack, 'http_errors' => false]);

        $config = new Config(new Repository([
            'otel-logging' => [
                'enabled' => true,
                'exporter' => [
                    'endpoint' => 'http://collector.test/v1/logs',
                    'timeout' => 1,
                    'auth_header' => '',
                ],
                'service' => ['name' => 'test-app'],
                'env' => 'testing',
            ],
            'app' => ['debug' => false],
        ]));

        $fallbackLogger = $this->createMock(LoggerInterface::class);
        $fallbackLogger->expects($this->never())->method('error');

        $handler = new class(Logger::DEBUG, true, $client, $config, null, null, null, $fallbackLogger) extends OtelLogHandler {
            public function publicWrite(array $record): void
            {
                $this->write($record);
            }
        };

        $handler->publicWrite($this->makeRecord());

        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertFalse($request->hasHeader('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    private function makeClientWithResponses(Response ...$responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        return new Client([
            'handler' => $handlerStack,
            'http_errors' => false,
        ]);
    }

    private function makeRecord(): array
    {
        return [
            'datetime' => new \DateTimeImmutable('2025-01-01T00:00:00+00:00'),
            'level' => Logger::INFO,
            'message' => 'Order processed',
            'context' => [],
        ];
    }
}
