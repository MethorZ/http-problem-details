<?php

declare(strict_types=1);

namespace MethorZ\ProblemDetails\Tests\Unit\Middleware;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use MethorZ\ProblemDetails\Middleware\ErrorHandlerMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class ErrorHandlerMiddlewareTest extends TestCase
{
    public function testPassesThroughSuccessfulResponse(): void
    {
        $middleware = new ErrorHandlerMiddleware(new ResponseFactory());
        $request = new ServerRequest([], [], '/test', 'GET');
        $expectedResponse = (new ResponseFactory())->createResponse(200);

        $handler = $this->createHandler($expectedResponse);

        $result = $middleware->process($request, $handler);

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testCatchesExceptionAndReturns500(): void
    {
        $middleware = new ErrorHandlerMiddleware(new ResponseFactory());
        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createThrowingHandler(new \RuntimeException('Test error'));

        $result = $middleware->process($request, $handler);

        $this->assertSame(500, $result->getStatusCode());
        $this->assertSame('application/problem+json', $result->getHeaderLine('Content-Type'));
    }

    public function testIncludesExceptionMessageInResponse(): void
    {
        $middleware = new ErrorHandlerMiddleware(new ResponseFactory());
        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createThrowingHandler(new \RuntimeException('Custom error message'));

        $result = $middleware->process($request, $handler);

        $body = (string) $result->getBody();
        /** @var array<string, mixed> $data */
        $data = json_decode($body, true);

        $this->assertArrayHasKey('detail', $data);
        $this->assertSame('Custom error message', $data['detail']);
    }

    public function testLogsExceptionWhenLoggerProvided(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('error'),
                $this->stringContains('Test error'),
                $this->callback(function (array $context): bool {
                    $this->assertArrayHasKey('exception_class', $context);
                    $this->assertArrayHasKey('exception_message', $context);
                    $this->assertArrayHasKey('request_method', $context);

                    return true;
                }),
            );

        $middleware = new ErrorHandlerMiddleware(new ResponseFactory(), $mockLogger);
        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createThrowingHandler(new \RuntimeException('Test error'));

        $middleware->process($request, $handler);
    }

    public function testDevelopmentModeIncludesStackTrace(): void
    {
        $middleware = new ErrorHandlerMiddleware(
            new ResponseFactory(),
            isDevelopment: true,
        );

        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createThrowingHandler(new \RuntimeException('Test error'));

        $result = $middleware->process($request, $handler);
        $body = (string) $result->getBody();
        /** @var array<string, mixed> $data */
        $data = json_decode($body, true);

        $this->assertArrayHasKey('trace', $data);
        $this->assertArrayHasKey('file', $data);
        $this->assertArrayHasKey('line', $data);
        $this->assertArrayHasKey('exception_class', $data);
    }

    public function testProductionModeExcludesStackTrace(): void
    {
        $middleware = new ErrorHandlerMiddleware(
            new ResponseFactory(),
            isDevelopment: false,
        );

        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createThrowingHandler(new \RuntimeException('Test error'));

        $result = $middleware->process($request, $handler);
        $body = (string) $result->getBody();
        /** @var array<string, mixed> $data */
        $data = json_decode($body, true);

        $this->assertArrayNotHasKey('trace', $data);
        $this->assertArrayNotHasKey('file', $data);
        $this->assertArrayNotHasKey('line', $data);
        $this->assertArrayNotHasKey('exception_class', $data);
    }

    public function testUsesCustomExceptionStatusMap(): void
    {
        $middleware = new ErrorHandlerMiddleware(
            new ResponseFactory(),
            exceptionStatusMap: [
                \InvalidArgumentException::class => 400,
            ],
        );

        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createThrowingHandler(new \InvalidArgumentException('Bad input'));

        $result = $middleware->process($request, $handler);

        $this->assertSame(400, $result->getStatusCode());
    }

    public function testHandlesPreviousExceptionChain(): void
    {
        $middleware = new ErrorHandlerMiddleware(
            new ResponseFactory(),
            isDevelopment: true,
        );

        $previous = new \RuntimeException('Root cause');
        $exception = new \RuntimeException('Main error', 0, $previous);

        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createThrowingHandler($exception);

        $result = $middleware->process($request, $handler);
        $body = (string) $result->getBody();
        /** @var array<string, mixed> $data */
        $data = json_decode($body, true);

        $this->assertArrayHasKey('previous_exception', $data);
        $this->assertIsArray($data['previous_exception']);
        $this->assertArrayHasKey('message', $data['previous_exception']);
        $this->assertSame('Root cause', $data['previous_exception']['message']);
    }

    public function testLogs4xxAsWarningAnd5xxAsError(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);

        // 4xx should log as warning
        $mockLogger->expects($this->once())
            ->method('log')
            ->with('warning');

        $middleware = new ErrorHandlerMiddleware(
            new ResponseFactory(),
            $mockLogger,
            exceptionStatusMap: [
                \InvalidArgumentException::class => 400,
            ],
        );

        $request = new ServerRequest([], [], '/test', 'GET');
        $handler = $this->createThrowingHandler(new \InvalidArgumentException('Bad request'));

        $middleware->process($request, $handler);
    }

    private function createHandler(ResponseInterface $response): RequestHandlerInterface
    {
        return new class ($response) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    private function createThrowingHandler(\Throwable $exception): RequestHandlerInterface
    {
        return new class ($exception) implements RequestHandlerInterface {
            public function __construct(private \Throwable $exception)
            {
            }

            public function handle(ServerRequestInterface $request): never
            {
                throw $this->exception;
            }
        };
    }
}
