<?php

declare(strict_types=1);

namespace MethorZ\ProblemDetails\Tests\Unit\Response;

use Laminas\Diactoros\Response;
use MethorZ\ProblemDetails\Response\ProblemDetails;
use PHPUnit\Framework\TestCase;

final class ProblemDetailsTest extends TestCase
{
    public function testCreatesBasicProblemDetails(): void
    {
        $problem = ProblemDetails::create(404, 'Not Found');
        $array = $problem->toArray();

        $this->assertSame(404, $array['status']);
        $this->assertSame('Not Found', $array['title']);
        $this->assertSame('about:blank', $array['type']);
    }

    public function testAddsTypeToProblemDetails(): void
    {
        $problem = ProblemDetails::create(404, 'Not Found')
            ->withType('https://api.example.com/errors/not-found');

        $array = $problem->toArray();

        $this->assertSame('https://api.example.com/errors/not-found', $array['type']);
    }

    public function testAddsDetailToProblemDetails(): void
    {
        $problem = ProblemDetails::create(404, 'Not Found')
            ->withDetail('User with ID 123 not found');

        $array = $problem->toArray();

        $this->assertSame('User with ID 123 not found', $array['detail']);
    }

    public function testAddsInstanceToProblemDetails(): void
    {
        $problem = ProblemDetails::create(404, 'Not Found')
            ->withInstance('/api/users/123');

        $array = $problem->toArray();

        $this->assertSame('/api/users/123', $array['instance']);
    }

    public function testAddsAdditionalProperties(): void
    {
        $problem = ProblemDetails::create(400, 'Bad Request')
            ->withAdditional('field', 'email')
            ->withAdditional('reason', 'Invalid format');

        $array = $problem->toArray();

        $this->assertArrayHasKey('field', $array);
        $this->assertSame('email', $array['field']);
        $this->assertArrayHasKey('reason', $array);
        $this->assertSame('Invalid format', $array['reason']);
    }

    public function testConvertsToJson(): void
    {
        $problem = ProblemDetails::create(404, 'Not Found')
            ->withDetail('Resource not found');

        $json = $problem->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertSame(404, $decoded['status']);
        $this->assertSame('Not Found', $decoded['title']);
    }

    public function testConvertsToResponse(): void
    {
        $problem = ProblemDetails::create(404, 'Not Found');
        $response = new Response();

        $result = $problem->toResponse($response);

        $this->assertSame(404, $result->getStatusCode());
        $this->assertSame('application/problem+json', $result->getHeaderLine('Content-Type'));
        $this->assertNotEmpty((string) $result->getBody());
    }

    public function testCreatesFromException(): void
    {
        $exception = new \RuntimeException('Something went wrong');
        $problem = ProblemDetails::fromException($exception);

        $array = $problem->toArray();

        $this->assertSame(500, $array['status']);
        $this->assertSame('Internal Server Error', $array['title']);
        $this->assertSame('Something went wrong', $array['detail']);
    }

    public function testCreatesFromExceptionWithTrace(): void
    {
        $exception = new \RuntimeException('Test error');
        $problem = ProblemDetails::fromException($exception, includeTrace: true);

        $array = $problem->toArray();

        $this->assertArrayHasKey('trace', $array);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('line', $array);
    }

    public function testCreatesFromExceptionWithoutTrace(): void
    {
        $exception = new \RuntimeException('Test error');
        $problem = ProblemDetails::fromException($exception, includeTrace: false);

        $array = $problem->toArray();

        $this->assertArrayNotHasKey('trace', $array);
        $this->assertArrayNotHasKey('file', $array);
        $this->assertArrayNotHasKey('line', $array);
    }

    public function testGetStatusReturnsCorrectValue(): void
    {
        $problem = ProblemDetails::create(422, 'Validation Failed');

        $this->assertSame(422, $problem->getStatus());
    }

    public function testFluentInterfaceChaining(): void
    {
        $problem = ProblemDetails::create(400, 'Bad Request')
            ->withType('https://example.com/errors/validation')
            ->withDetail('Email is required')
            ->withInstance('/api/users')
            ->withAdditional('field', 'email');

        $array = $problem->toArray();

        $this->assertSame(400, $array['status']);
        $this->assertSame('Bad Request', $array['title']);
        $this->assertSame('https://example.com/errors/validation', $array['type']);
        $this->assertSame('Email is required', $array['detail']);
        $this->assertSame('/api/users', $array['instance']);
        $this->assertSame('email', $array['field']);
    }
}
