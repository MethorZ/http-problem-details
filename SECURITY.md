# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please report it responsibly.

### How to Report

1. **Do NOT** create a public GitHub issue for security vulnerabilities
2. Email the maintainer directly at: **methorz@spammerz.de**
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 7 days
- **Resolution Timeline**: Depends on severity (critical: ASAP, high: 30 days, medium: 90 days)

### After Resolution

- Security fixes will be released as patch versions
- Credit will be given to reporters (unless anonymity is requested)
- A security advisory will be published for significant vulnerabilities

## Security Best Practices

When using this package:

- **Keep dependencies updated** - Run `composer update` regularly
- **Use latest PHP version** - Security fixes are backported to supported versions only
- **Use production mode** - Set `isDevelopment: false` in production to hide stack traces
- **Sanitize exception messages** - Don't include sensitive data in exception messages
- **Review logged context** - Ensure logs don't contain passwords, tokens, or PII

## Known Security Considerations

This package:

### Information Disclosure Prevention

```php
// Production mode hides sensitive details
$middleware = new ErrorHandlerMiddleware(
    $responseFactory,
    $logger,
    isDevelopment: false  // CRITICAL: Set to false in production!
);
```

### Exception Message Security

```php
// BAD: Leaks sensitive information
throw new Exception("DB error: password='secret123'");

// GOOD: Generic message, sensitive data in logs only
throw new DatabaseException("Database connection failed");
$logger->error('DB failed', ['host' => $host]); // Log context separately
```

### What Development Mode Exposes

When `isDevelopment: true`:
- Full stack traces
- File paths and line numbers
- Exception class names
- Previous exception chain

**Never enable development mode in production!**

## Contact

- **Security Issues**: methorz@spammerz.de
- **General Issues**: [GitHub Issues](https://github.com/MethorZ/http-problem-details/issues)

---

Thank you for helping keep this project secure!

