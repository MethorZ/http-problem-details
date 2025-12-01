<?php

declare(strict_types=1);

namespace MethorZ\ProblemDetails\Integration\Mezzio;

use MethorZ\ProblemDetails\Middleware\ErrorHandlerMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Mezzio integration ConfigProvider for RFC 7807 Problem Details
 *
 * Provides automatic registration of ErrorHandlerMiddleware with
 * environment-aware configuration.
 *
 * Configuration keys:
 * - 'debug' (bool): Enable development mode with stack traces
 * - 'problem_details.exception_map' (array): Exception-to-status-code mapping
 */
final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array<string, array<string, callable>>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                ErrorHandlerMiddleware::class => static function (
                    ContainerInterface $container,
                ): ErrorHandlerMiddleware {
                    /** @var ResponseFactoryInterface $responseFactory */
                    $responseFactory = $container->get(ResponseFactoryInterface::class);

                    // Optional logger
                    $logger = $container->has(LoggerInterface::class)
                        ? $container->get(LoggerInterface::class)
                        : null;

                    // Get configuration
                    /** @var array<string, mixed> $config */
                    $config = $container->has('config') ? $container->get('config') : [];

                    // Check if debug/development mode
                    $isDevelopment = (bool) ($config['debug'] ?? false);

                    // Custom exception to status code mapping
                    $problemDetailsConfig = $config['problem_details'] ?? [];
                    /** @var array<class-string<\Throwable>, int> $exceptionStatusMap */
                    $exceptionStatusMap = is_array($problemDetailsConfig)
                        ? (array) ($problemDetailsConfig['exception_map'] ?? [])
                        : [];

                    /** @var LoggerInterface|null $logger */
                    return new ErrorHandlerMiddleware(
                        $responseFactory,
                        $logger,
                        $isDevelopment,
                        $exceptionStatusMap,
                    );
                },
            ],
        ];
    }
}
