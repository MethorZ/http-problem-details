<?php

declare(strict_types=1);

namespace MethorZ\ProblemDetails\Response;

use MethorZ\ProblemDetails\Util\HttpStatusText;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * RFC 7807 Problem Details response builder
 *
 * Creates standardized error responses following RFC 7807 specification.
 *
 * Usage:
 * ```php
 * $problem = HttpProblem::create(404, 'User not found')
 *     ->withType('https://api.example.com/errors/user-not-found')
 *     ->withDetail('User with ID 123 does not exist')
 *     ->withInstance('/api/users/123');
 * ```
 */
final readonly class ProblemDetails
{
    /**
     * @param array<string, mixed> $additional
     */
    private function __construct(
        private int $status,
        private string $title,
        private ?string $type = null,
        private ?string $detail = null,
        private ?string $instance = null,
        private array $additional = [],
    ) {
    }

    public static function create(int $status, string $title): self
    {
        return new self($status, $title);
    }

    public static function fromException(Throwable $exception, bool $includeTrace = false): self
    {
        $status = 500;

        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
            $status = is_int($statusCode) ? $statusCode : 500;
        }

        $problem = new self(
            status: $status,
            title: HttpStatusText::get($status),
            detail: $exception->getMessage(),
        );

        if ($includeTrace) {
            $problem = $problem->withAdditional('trace', $exception->getTraceAsString());
            $problem = $problem->withAdditional('file', $exception->getFile());
            $problem = $problem->withAdditional('line', $exception->getLine());
        }

        return $problem;
    }

    public function withType(string $type): self
    {
        return new self(
            $this->status,
            $this->title,
            $type,
            $this->detail,
            $this->instance,
            $this->additional,
        );
    }

    public function withDetail(string $detail): self
    {
        return new self(
            $this->status,
            $this->title,
            $this->type,
            $detail,
            $this->instance,
            $this->additional,
        );
    }

    public function withInstance(string $instance): self
    {
        return new self(
            $this->status,
            $this->title,
            $this->type,
            $this->detail,
            $instance,
            $this->additional,
        );
    }

    /**
     * @param mixed $value
     */
    public function withAdditional(string $key, $value): self
    {
        $additional = $this->additional;
        $additional[$key] = $value;

        return new self(
            $this->status,
            $this->title,
            $this->type,
            $this->detail,
            $this->instance,
            $additional,
        );
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type ?? 'about:blank',
            'title' => $this->title,
            'status' => $this->status,
        ];

        if ($this->detail !== null) {
            $data['detail'] = $this->detail;
        }

        if ($this->instance !== null) {
            $data['instance'] = $this->instance;
        }

        return [...$data, ...$this->additional];
    }

    /**
     * Convert to JSON string
     *
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Convert to PSR-7 response
     *
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function toResponse(ResponseInterface $response): ResponseInterface
    {
        $body = $response->getBody();
        $body->write($this->toJson());
        $body->rewind();

        return $response
            ->withStatus($this->status)
            ->withHeader('Content-Type', 'application/problem+json')
            ->withBody($body);
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
