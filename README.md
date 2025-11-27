# MethorZ Error Handler Middleware

**Comprehensive error handling middleware for PSR-15 applications with RFC 7807 Problem Details support**

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Production-ready error handling with RFC 7807 Problem Details, environment-aware formatting, automatic logging, and developer-friendly stack traces. Zero configuration, works out-of-the-box.

---

## ✨ Features

- 📋 **RFC 7807 Compliance** - Standardized error responses (Problem Details)
- 🔍 **Environment-Aware** - Stack traces in dev, sanitized messages in production
- 📝 **Automatic Logging** - PSR-3 logger integration with context
- 🎯 **Custom Exception Mapping** - Map exceptions to HTTP status codes
- 🔗 **Exception Chaining** - Captures and formats previous exception details
- 💡 **Developer-Friendly** - Detailed debugging info in development mode
- 🌍 **Production-Safe** - No sensitive data leaks in production
- 🎨 **Framework Agnostic** - Works with any PSR-15 application

---

## 📦 Installation

```bash
composer require methorz/mezzio-error-handler
```

---

## 🚀 Quick Start

### **Basic Usage**

```php
use Methorz\ErrorHandler\Middleware\ErrorHandlerMiddleware;
use Laminas\Diactoros\ResponseFactory;

$middleware = new ErrorHandlerMiddleware(
    new ResponseFactory()
);

// Add to middleware pipeline (first!)
$app->pipe($middleware);
```

**Production Response** (500 Internal Server Error):
```json
{
  "type": "about:blank",
  "title": "Internal Server Error",
  "status": 500,
  "detail": "An error occurred"
}
```

**Development Response** (with stack trace):
```json
{
  "type": "about:blank",
  "title": "Internal Server Error",
  "status": 500,
  "detail": "Division by zero",
  "trace": "#0 /path/to/file.php(42): calculate()...",
  "file": "/path/to/file.php",
  "line": 42,
  "request_method": "POST",
  "request_uri": "https://api.example.com/calculate",
  "exception_class": "DivisionByZeroError"
}
```

---

## 📖 Detailed Usage

### **With Logger Integration**

```php
use Methorz\ErrorHandler\Middleware\ErrorHandlerMiddleware;
use Laminas\Diactoros\ResponseFactory;
use Monolog\Logger;

$logger = new Logger('app');

$middleware = new ErrorHandlerMiddleware(
    new ResponseFactory(),
    $logger // PSR-3 logger
);
```

**Logged Context**:
```
[2024-11-26 10:30:00] app.ERROR: Exception caught: User not found {
    "exception_class": "App\\Exception\\NotFoundException",
    "exception_message": "User not found",
    "exception_code": 0,
    "exception_file": "/app/src/Service/UserService.php",
    "exception_line": 42,
    "request_method": "GET",
    "request_uri": "https://api.example.com/users/123"
}
```

### **Development vs Production Mode**

```php
// Development: Include stack traces and debug info
$devMiddleware = new ErrorHandlerMiddleware(
    $responseFactory,
    $logger,
    isDevelopment: true // ← Enable debug mode
);

// Production: Sanitized error messages
$prodMiddleware = new ErrorHandlerMiddleware(
    $responseFactory,
    $logger,
    isDevelopment: false // ← Production safe
);
```

### **Custom Exception Status Mapping**

```php
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

$middleware = new ErrorHandlerMiddleware(
    $responseFactory,
    $logger,
    exceptionStatusMap: [
        NotFoundException::class => 404,
        ValidationException::class => 422,
        \InvalidArgumentException::class => 400,
    ]
);
```

**Before**:
- `NotFoundException` → 500 Internal Server Error ❌

**After**:
- `NotFoundException` → 404 Not Found ✅
- `ValidationException` → 422 Unprocessable Entity ✅

---

## 🎯 RFC 7807 Problem Details

### **Building Custom Problem Details**

```php
use Methorz\ErrorHandler\Response\ProblemDetails;
use Laminas\Diactoros\Response;

$problem = ProblemDetails::create(404, 'Not Found')
    ->withType('https://api.example.com/problems/user-not-found')
    ->withDetail('User with ID 123 does not exist')
    ->withInstance('/api/users/123')
    ->withAdditional('user_id', 123);

$response = $problem->toResponse(new Response());
```

**Response**:
```json
{
  "type": "https://api.example.com/problems/user-not-found",
  "title": "Not Found",
  "status": 404,
  "detail": "User with ID 123 does not exist",
  "instance": "/api/users/123",
  "user_id": 123
}
```

### **Creating from Exception**

```php
$exception = new NotFoundException('User not found');

// Production mode
$problem = ProblemDetails::fromException($exception, includeTrace: false);

// Development mode
$problem = ProblemDetails::fromException($exception, includeTrace: true);
```

---

## 🔍 Environment-Aware Behavior

### **Development Mode** (`isDevelopment: true`)

**Response includes**:
- ✅ Full exception message
- ✅ Stack trace
- ✅ File path and line number
- ✅ Exception class name
- ✅ Request method and URI
- ✅ Previous exception chain

**Use when**: Local development, staging, testing

### **Production Mode** (`isDevelopment: false`)

**Response includes**:
- ✅ HTTP status code
- ✅ Generic title
- ✅ Exception message (if safe)
- ❌ NO stack traces
- ❌ NO file paths
- ❌ NO internal details

**Use when**: Production, public APIs

---

## 📊 HTTP Status Code Mapping

| Exception Type | Status Code | Log Level |
|----------------|-------------|-----------|
| Client errors (4xx) | 400-499 | `warning` |
| Server errors (5xx) | 500-599 | `error` |

**Supported Status Codes**:
```
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
405 Method Not Allowed
408 Request Timeout
409 Conflict
422 Unprocessable Entity
429 Too Many Requests
500 Internal Server Error
501 Not Implemented
502 Bad Gateway
503 Service Unavailable
504 Gateway Timeout
```

---

## 🔗 Exception Chaining

Automatically captures and formats exception chains:

```php
try {
    $db->connect(); // Throws PDOException
} catch (PDOException $e) {
    throw new DatabaseException('Failed to connect', 0, $e); // Wraps PDOException
}
```

**Development Response** (with `previous_exception`):
```json
{
  "status": 500,
  "title": "Internal Server Error",
  "detail": "Failed to connect",
  "trace": "...",
  "previous_exception": {
    "class": "PDOException",
    "message": "SQLSTATE[HY000] [2002] Connection refused",
    "file": "/app/src/Database.php",
    "line": 25
  }
}
```

---

## 🧪 Testing

```bash
# Run tests
composer test

# Static analysis
composer analyze

# Code style
composer cs-check
composer cs-fix
```

**Test Coverage**: 21 tests, 59 assertions, 100% passing

---

## 🛠️ Use Cases

### **1. REST API Error Handling**

```php
// Global error handler (first middleware)
$app->pipe(new ErrorHandlerMiddleware(
    $responseFactory,
    $logger,
    isDevelopment: $_ENV['APP_ENV'] === 'development'
));

// All uncaught exceptions become RFC 7807 responses
```

### **2. Custom Application Exceptions**

```php
namespace App\Exception;

class UserNotFoundException extends \RuntimeException
{
    public function getStatusCode(): int
    {
        return 404; // ← Automatically used by middleware
    }
}
```

### **3. Validation Error Responses**

```php
$middleware = new ErrorHandlerMiddleware(
    $responseFactory,
    $logger,
    exceptionStatusMap: [
        ValidationException::class => 422,
    ]
);

throw new ValidationException('Email is required');
// → 422 Unprocessable Entity with Problem Details
```

### **4. Microservices Error Consistency**

All services return the same RFC 7807 format:
```json
{
  "type": "about:blank",
  "title": "Not Found",
  "status": 404,
  "detail": "Resource not found"
}
```

---

## 🔧 Configuration Examples

### **Mezzio / Laminas**

```php
// config/autoload/middleware.global.php
use Methorz\ErrorHandler\Middleware\ErrorHandlerMiddleware;

return [
    'dependencies' => [
        'factories' => [
            ErrorHandlerMiddleware::class => function ($container): ErrorHandlerMiddleware {
                return new ErrorHandlerMiddleware(
                    $container->get(ResponseFactoryInterface::class),
                    $container->get(LoggerInterface::class),
                    isDevelopment: $_ENV['APP_ENV'] === 'development',
                    exceptionStatusMap: [
                        NotFoundException::class => 404,
                        ValidationException::class => 422,
                    ],
                );
            },
        ],
    ],
];

// config/pipeline.php
$app->pipe(ErrorHandlerMiddleware::class); // FIRST middleware!
```

### **Slim Framework**

```php
use Methorz\ErrorHandler\Middleware\ErrorHandlerMiddleware;

$app->add(new ErrorHandlerMiddleware(
    $responseFactory,
    $logger,
    isDevelopment: $_ENV['DEBUG'] === 'true'
));
```

---

## 📚 Resources

- [RFC 7807: Problem Details for HTTP APIs](https://tools.ietf.org/html/rfc7807)
- [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/)
- [PSR-15: HTTP Server Middleware](https://www.php-fig.org/psr/psr-15/)

---

## 💡 Best Practices

### **DO**
- ✅ Place error middleware FIRST in pipeline
- ✅ Use `isDevelopment` based on environment variable
- ✅ Map domain exceptions to appropriate HTTP status codes
- ✅ Log exceptions with context for debugging
- ✅ Use PSR-3 logger for centralized log management

### **DON'T**
- ❌ Don't expose stack traces in production (`isDevelopment: false`)
- ❌ Don't return 500 for client errors (use 4xx instead)
- ❌ Don't log sensitive data (passwords, tokens) in error context
- ❌ Don't catch errors before error middleware (let it handle them)

---

## 🔒 Security Considerations

### **Sensitive Data**
- ✅ Production mode hides file paths and stack traces
- ✅ Exception messages are still included (ensure they're safe!)
- ✅ Logger context can be filtered/redacted
- ❌ Don't include passwords, tokens, or PII in exception messages

### **Information Disclosure**
```php
// ❌ BAD: Leaks sensitive info
throw new Exception("DB connection failed: password='secret123'");

// ✅ GOOD: Generic message
throw new DatabaseException("Failed to connect to database");
```

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🤝 Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## 🔗 Links

- [Documentation](docs/)
- [Changelog](CHANGELOG.md)
- [Issues](https://github.com/MethorZ/mezzio-error-handler/issues)

