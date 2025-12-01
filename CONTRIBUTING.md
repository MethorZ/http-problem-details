# Contributing to MethorZ Error Handler Middleware

Thank you for considering contributing to this project!

## Code of Conduct

Please be respectful and professional when contributing. We expect all contributors to:
- Be welcoming and inclusive
- Respect differing viewpoints and experiences
- Accept constructive criticism gracefully
- Focus on what is best for the community

## How to Contribute

### Reporting Bugs

Before submitting a bug report:
1. Check the [existing issues](https://github.com/methorz/http-problem-details/issues) to avoid duplicates
2. Use the latest version of the package
3. Verify the bug is reproducible

When reporting bugs, please include:
- PHP version
- Package version
- Minimal reproduction steps
- Expected vs actual behavior
- Error messages or stack traces

### Suggesting Features

Feature suggestions are welcome! Please:
1. Check if it's already been suggested
2. Explain the use case clearly
3. Provide examples if possible
4. Consider implementation complexity

### Pull Requests

#### Before Submitting

1. **Fork the repository** and create a feature branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Make your changes** following our coding standards

4. **Run quality checks** - all must pass:
   ```bash
   composer quality  # PHPStan Level 9 + PHPCS
   composer test     # PHPUnit tests
   ```

#### Coding Standards

- **PSR-12** coding style
- **PHPStan Level 9** type safety (0 errors required)
- **PHP 8.2+** features encouraged
- **Type hints** on all parameters and return types
- **Readonly properties** where appropriate
- **Strict types** declaration in every file

#### Code Quality Requirements

All pull requests must:
- Pass PHPStan Level 9 (0 errors)
- Pass all unit tests (100%)
- Add tests for new functionality
- Update documentation if needed
- Follow existing code patterns

#### Testing

- **Write tests** for all new features
- **Update tests** when modifying existing features
- **Aim for high coverage** (80%+ preferred)
- Follow the **AAA pattern** (Arrange, Act, Assert)

Example test:
```php
public function testCreatesProblemDetailsFromException(): void
{
    // Arrange
    $exception = new RuntimeException('Test error');

    // Act
    $problem = ProblemDetails::fromException($exception);

    // Assert
    $this->assertSame(500, $problem->getStatus());
    $this->assertSame('Test error', $problem->getDetail());
}
```

#### Commit Messages

Use clear, descriptive commit messages:
- Start with a verb (Add, Fix, Update, Remove, Refactor)
- Keep the subject line under 72 characters
- Use present tense ("Add feature" not "Added feature")
- Reference issues if applicable (#123)

Good examples:
```
Add support for custom exception status mapping
Fix RFC 7807 type URI generation
Update README with production configuration (#42)
```

#### Pull Request Process

1. **Update documentation** - README, CHANGELOG, code comments
2. **Run quality checks** - Ensure all pass
3. **Create PR** with clear description
4. **Wait for review** - Address feedback promptly

### Development Workflow

```bash
# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run static analysis
composer analyze

# Run all quality checks
composer quality
```

### Project Structure

```
src/
├── Middleware/     # PSR-15 error handling middleware
├── Response/       # ProblemDetails implementation
└── Util/           # HTTP status text utilities

tests/
├── Unit/           # Unit tests
└── Fixtures/       # Test fixtures
```

## Questions?

- **Issues**: [GitHub Issues](https://github.com/methorz/http-problem-details/issues)
- **Discussions**: [GitHub Discussions](https://github.com/methorz/http-problem-details/discussions)

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing!

