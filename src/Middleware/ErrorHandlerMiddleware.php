<?php

declare(strict_types=1);

namespace MethorZ\ProblemDetails\Middleware;

use MethorZ\ProblemDetails\Response\ProblemDetails;
use MethorZ\ProblemDetails\Util\HttpStatusText;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

use function get_class;

/**
 * PSR-15 error handling middleware with RFC 7807 Problem Details support
 *
 * Features:
 * - RFC 7807 compliant error responses
 * - Environment-aware formatting (dev vs production)
 * - Automatic exception logging
 * - Stack trace in development mode
 * - Custom exception mapping
 * - HTTP status code handling
 *
 * Usage:
 * ```php
 * $middleware = new ErrorHandlerMiddleware(
 *     $responseFactory,
 *     $logger,
 *     isDevelopment: true
 * );
 * ```
 */
final readonly class ErrorHandlerMiddleware implements MiddlewareInterface
{
    /**
     * @param array<class-string<Throwable>, int> $exceptionStatusMap
     */
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ?LoggerInterface $logger = null,
        private bool $isDevelopment = false,
        private array $exceptionStatusMap = [],
    ) {
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request);
        }
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        // Log the exception
        if ($this->logger !== null) {
            $this->logException($exception, $request);
        }

        // Determine HTTP status code
        $status = $this->determineStatusCode($exception);

        // Create Problem Details response with correct status
        $problem = ProblemDetails::create($status, HttpStatusText::get($status))
            ->withDetail($exception->getMessage());

        // Add trace if in development
        if ($this->isDevelopment) {
            $problem = $problem
                ->withAdditional('trace', $exception->getTraceAsString())
                ->withAdditional('file', $exception->getFile())
                ->withAdditional('line', $exception->getLine());
        }

        // Add request context in development
        if ($this->isDevelopment) {
            $problem = $problem
                ->withAdditional('request_method', $request->getMethod())
                ->withAdditional('request_uri', (string) $request->getUri())
                ->withAdditional('exception_class', get_class($exception));

            // Add previous exception chain
            if ($exception->getPrevious() !== null) {
                $problem = $problem->withAdditional(
                    'previous_exception',
                    $this->formatPreviousException($exception->getPrevious()),
                );
            }
        }

        // Create response
        $response = $this->responseFactory->createResponse($status);

        return $problem->toResponse($response);
    }

    private function determineStatusCode(Throwable $exception): int
    {
        // Check custom exception mapping
        $exceptionClass = get_class($exception);

        if (isset($this->exceptionStatusMap[$exceptionClass])) {
            return $this->exceptionStatusMap[$exceptionClass];
        }

        // Check if exception has getStatusCode method
        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();

            return is_int($statusCode) ? $statusCode : 500;
        }

        // Default to 500
        return 500;
    }

    /**
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function logException(Throwable $exception, ServerRequestInterface $request): void
    {
        assert($this->logger !== null); // Called only when logger is not null

        $context = [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'request_method' => $request->getMethod(),
            'request_uri' => (string) $request->getUri(),
        ];

        $level = $this->determineLogLevel($exception);

        $this->logger->log($level, 'Exception caught: ' . $exception->getMessage(), $context);
    }

    private function determineLogLevel(Throwable $exception): string
    {
        $status = $this->determineStatusCode($exception);

        // 4xx errors are typically client errors (warning)
        // 5xx errors are server errors (error)
        return $status >= 500 ? LogLevel::ERROR : LogLevel::WARNING;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPreviousException(Throwable $exception): array
    {
        $data = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if ($exception->getPrevious() !== null) {
            $data['previous'] = $this->formatPreviousException($exception->getPrevious());
        }

        return $data;
    }
}
