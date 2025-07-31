# Contributing to Canvas LMS Kit

First off, thank you for considering contributing to Canvas LMS Kit! It's people like you that make Canvas LMS Kit such a great tool.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)
- [API Implementation Guide](#api-implementation-guide)

## Code of Conduct

This project and everyone participating in it is governed by the [Canvas LMS Kit Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code. Please report unacceptable behavior to jjuanrivvera@gmail.com.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a new branch for your feature or bugfix
4. Make your changes
5. Push to your fork and submit a pull request

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

- Use a clear and descriptive title
- Describe the exact steps to reproduce the problem
- Provide specific examples to demonstrate the steps
- Describe the behavior you observed and what you expected
- Include PHP version, Canvas instance details, and any error messages

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- Use a clear and descriptive title
- Provide a step-by-step description of the suggested enhancement
- Provide specific examples to demonstrate the steps
- Describe the current behavior and explain the expected behavior
- Explain why this enhancement would be useful

### Your First Code Contribution

Unsure where to begin? Look for issues labeled:

- `good first issue` - Simple issues ideal for beginners
- `help wanted` - Issues where we need community help
- `documentation` - Documentation improvements

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Docker and Docker Compose (recommended)
- Git

### Setting Up Your Development Environment

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/canvas-lms-kit.git
cd canvas-lms-kit

# Add upstream remote
git remote add upstream https://github.com/jjuanrivvera/canvas-lms-kit.git

# Install dependencies
composer install

# Using Docker (recommended)
docker compose up -d
docker compose exec php composer install
```

### Running Tests

```bash
# Run all tests
docker compose exec php composer test

# Run with coverage
docker compose exec php composer test:coverage

# Run specific test
docker compose exec php ./vendor/bin/phpunit tests/Api/Courses/CourseTest.php

# Run all quality checks
docker compose exec php composer check
```

## Coding Standards

We follow PSR-12 coding standards. Before submitting:

```bash
# Check coding standards
docker compose exec php composer cs

# Fix coding standard violations
docker compose exec php composer cs-fix
```

### Key Guidelines

1. **Type Declarations**: Use PHP 8.1+ type declarations for all parameters and return types
2. **Property Types**: Declare types for all class properties
3. **PHPDoc**: Add PHPDoc blocks for all public methods
4. **Naming**: Use descriptive variable and method names
5. **Array Syntax**: Use short array syntax `[]` instead of `array()`

### Example Code Style

```php
<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Courses;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\CreateCourseDTO;

/**
 * Manages Canvas course operations
 */
class Course extends AbstractBaseApi
{
    protected string $resourcePath = 'courses';

    /**
     * Create a new course
     *
     * @param array|CreateCourseDTO $data Course data
     * @return self
     */
    public static function create(array|CreateCourseDTO $data): self
    {
        if (is_array($data)) {
            $data = new CreateCourseDTO($data);
        }

        // Implementation
    }
}
```

## Testing Guidelines

### Writing Tests

1. **Test Coverage**: Aim for 100% code coverage for new code
2. **Test Structure**: Follow AAA pattern (Arrange, Act, Assert)
3. **Mock External Calls**: Mock all HTTP client calls
4. **Test Edge Cases**: Include tests for error conditions

### Example Test

```php
public function testCreateCourseWithArray(): void
{
    // Arrange
    $courseData = [
        'name' => 'Test Course',
        'course_code' => 'TC101'
    ];

    $this->mockClient->method('post')
        ->with('courses', $this->anything())
        ->willReturn(['id' => 123, 'name' => 'Test Course']);

    // Act
    $course = Course::create($courseData);

    // Assert
    $this->assertEquals(123, $course->id);
    $this->assertEquals('Test Course', $course->name);
}
```

## Pull Request Process

1. **Update Documentation**: Update README.md with details of changes if applicable
2. **Add Tests**: Add tests covering your changes
3. **Update CHANGELOG**: Add a note to the Unreleased section
4. **Pass All Checks**: Ensure all tests and quality checks pass
5. **Request Review**: Request review from maintainers

### PR Title Format

Use conventional commit format:

- `feat: Add Groups API support`
- `fix: Handle null values in Course name`
- `docs: Update file upload examples`
- `refactor: Improve pagination handling`
- `test: Add tests for Quiz submissions`

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Added new tests
- [ ] Updated existing tests

## Checklist
- [ ] My code follows the style guidelines
- [ ] I have performed a self-review
- [ ] I have commented my code where necessary
- [ ] I have updated the documentation
- [ ] My changes generate no new warnings
```

## API Implementation Guide

When implementing a new Canvas API:

### 1. Check Canvas Documentation

Review the official [Canvas API documentation](https://canvas.instructure.com/doc/api/) for the endpoint you're implementing.

### 2. Follow Project Structure

```
src/Api/YourApi/
├── YourApi.php          # Main API class
└── YourApiDTO.php       # Data Transfer Objects (if needed)

tests/Api/YourApi/
└── YourApiTest.php      # Comprehensive tests
```

### 3. Implementation Checklist

- [ ] Extend `AbstractBaseApi`
- [ ] Accept arrays as input (DTOs used internally)
- [ ] Implement standard CRUD methods if applicable
- [ ] Add relationship methods where appropriate
- [ ] Handle pagination properly
- [ ] Add comprehensive PHPDoc comments
- [ ] Write thorough tests

### 4. Example Implementation Pattern

```php
public static function create(array|CreateYourApiDTO $data): self
{
    if (is_array($data)) {
        $data = new CreateYourApiDTO($data);
    }

    $response = self::getClient()->post(
        self::getResourcePath(),
        $data->toMultipart()
    );

    return new self($response);
}
```

## Questions?

Feel free to:
- Open an issue for discussion
- Join our [GitHub Discussions](https://github.com/jjuanrivvera/canvas-lms-kit/discussions)
- Email: jjuanrivvera@gmail.com

Thank you for contributing! 🎉
