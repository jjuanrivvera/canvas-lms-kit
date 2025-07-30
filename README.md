# Canvas LMS PHP SDK

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PSR-12](https://img.shields.io/badge/PSR-12-blue.svg)](https://www.php-fig.org/psr/psr-12/)

A modern PHP SDK for the Canvas Learning Management System (LMS) API. Built with PHP 8.1+ features, this SDK provides an intuitive Active Record interface for managing Canvas resources with automatic retry logic and rate limiting out of the box.

## 📑 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Project Structure](#-project-structure)
- [Automatic Protection](#️-automatic-protection)
- [Multi-Tenant Configuration](#-multi-tenant-configuration)
- [Documentation](#-documentation)
- [Supported Canvas APIs](#-supported-canvas-apis)
- [Testing](#-testing)
- [Contributing](#-contributing)
- [License](#-license)
- [Support](#-support)
- [Links](#-links)

## 🚀 Features

- **Zero-Configuration Middleware**: Automatic retry and rate limiting protection
- **Active Record Pattern**: Intuitive object-oriented API (`Course::find()`, `$course->save()`)
- **Multi-Tenant Support**: Manage multiple Canvas instances with isolated contexts
- **Type Safety**: Full PHP 8.1+ type declarations and PHPStan level 6 compliance
- **Comprehensive Testing**: 1000+ tests with full coverage
- **PSR Standards**: PSR-12 coding standards and PSR-3 logging support

## 📋 Requirements

- PHP 8.1 or higher
- Composer
- Canvas LMS API token

## 📦 Installation

Install via Composer:

```bash
composer require jjuanrivvera/canvas-lms-kit
```

## 🔧 Quick Start

### Basic Configuration

```php
use CanvasLMS\Config;
use CanvasLMS\Api\Courses\Course;

// Configure the SDK
Config::setApiKey('your-canvas-api-key');
Config::setBaseUrl('https://your-canvas-instance.instructure.com');

// That's it! The SDK is ready to use with automatic retry and rate limiting
$course = Course::find(123); // Automatically protected against failures
```

### Creating a Course

```php
use CanvasLMS\Api\Courses\Course;

// Using static method
$course = Course::create([
    'name' => 'Introduction to PHP',
    'course_code' => 'PHP101',
    'is_public' => false
]);

// Using instance method
$course = new Course([
    'name' => 'Advanced PHP',
    'course_code' => 'PHP201'
]);
$course->save();
```

### Managing Users

```php
use CanvasLMS\Api\Users\User;

// Create a user
$user = User::create([
    'name' => 'Jane Doe',
    'email' => 'jane.doe@example.com',
    'login_id' => 'jane.doe'
]);

// Find and update a user
$user = User::find(456);
$user->name = 'Jane Smith';
$user->save();
```

## 📁 Project Structure

```
canvas-lms-kit/
├── src/
│   ├── Api/                    # API resource classes (Active Record pattern)
│   │   ├── AbstractBaseApi.php # Base class for all API resources
│   │   ├── Courses/           # Course management
│   │   ├── Users/             # User management
│   │   ├── Enrollments/       # Enrollment management
│   │   ├── Assignments/       # Assignment handling
│   │   ├── Modules/           # Module organization
│   │   ├── ModuleItems/       # Module item management
│   │   ├── Quizzes/           # Quiz management
│   │   ├── Sections/          # Course sections
│   │   ├── Tabs/              # Course navigation tabs
│   │   ├── ExternalTools/     # LTI integrations
│   │   └── Files/             # File uploads
│   ├── Dto/                   # Data Transfer Objects
│   ├── Exceptions/            # Custom exceptions
│   ├── Http/                  # HTTP client and middleware
│   │   ├── HttpClient.php     # Main HTTP client
│   │   └── Middleware/        # Request/response middleware
│   │       ├── RetryMiddleware.php      # Automatic retry logic
│   │       ├── RateLimitMiddleware.php  # Rate limit handling
│   │       └── LoggingMiddleware.php    # Request logging
│   ├── Interfaces/            # PHP interfaces
│   ├── Objects/               # Read-only value objects
│   ├── Pagination/            # Pagination support
│   └── Config.php             # Global configuration
├── tests/                     # PHPUnit tests
├── docs/                      # Additional documentation
├── wiki/                      # Wiki documentation (gitignored)
├── docker-compose.yml         # Docker development setup
├── composer.json              # Composer dependencies
├── phpstan.neon              # PHPStan configuration
└── README.md                 # This file
```

## 🛡️ Automatic Protection

The SDK includes intelligent middleware that automatically handles:

### Retry Logic
- Failed requests are retried up to 3 times
- Exponential backoff prevents overwhelming the server
- Handles transient network issues and 5xx errors

### Rate Limiting
- Monitors Canvas API rate limit headers
- Automatically throttles requests when approaching limits
- Prevents 403 rate limit errors

### Request Logging
- Logs all API interactions when configured
- Sanitizes sensitive data automatically
- Helps with debugging and monitoring

### Customizing Protection

```php
// Adjust retry and rate limiting behavior
Config::setMiddleware([
    'retry' => [
        'max_attempts' => 5,        // More retries
        'delay' => 2000,            // Start with 2s delay
    ],
    'rate_limit' => [
        'wait_on_limit' => false,   // Fail fast instead of waiting
        'max_wait_time' => 60,      // Wait up to 60 seconds
    ],
]);
```

## 🏢 Multi-Tenant Configuration

Manage multiple Canvas instances with context isolation:

```php
// Configure production instance
Config::setContext('production');
Config::setApiKey('prod-api-key');
Config::setBaseUrl('https://canvas.company.com');
Config::setMiddleware([
    'retry' => ['max_attempts' => 3],
    'rate_limit' => ['wait_on_limit' => true],
]);

// Configure test instance
Config::setContext('test');
Config::setApiKey('test-api-key');
Config::setBaseUrl('https://test.canvas.company.com');
Config::setMiddleware([
    'retry' => ['max_attempts' => 5],
    'rate_limit' => ['enabled' => false], // No limits in test
]);

// Switch contexts as needed
Config::setContext('production');
$prodCourse = Course::find(123); // Uses production settings

Config::setContext('test');
$testCourse = Course::find(456); // Uses test settings
```

## 📚 Documentation

### API Reference
- **[PHPDoc API Reference](https://jjuanrivvera.github.io/canvas-lms-kit/)** - Complete API documentation with class references, method signatures, and examples

### Guides and Tutorials
Comprehensive guides are available in our [GitHub Wiki](https://github.com/jjuanrivvera/canvas-lms-kit/wiki):

- **[Getting Started](https://github.com/jjuanrivvera/canvas-lms-kit/wiki)** - Installation and configuration guide
- **[Implementation Examples](https://github.com/jjuanrivvera/canvas-lms-kit/wiki/Implementation‐Examples)** - Real-world usage patterns
- **[Architecture Overview](https://github.com/jjuanrivvera/canvas-lms-kit/wiki/Architecture‐Overview)** - Design patterns and structure
- **[API Coverage](https://github.com/jjuanrivvera/canvas-lms-kit/wiki/MVP‐Progress‐Tracking)** - Supported Canvas API endpoints
- **[Contributing Guidelines](https://github.com/jjuanrivvera/canvas-lms-kit/wiki/Contributing‐Guidelines)** - How to contribute

## 🎯 Supported Canvas APIs

The SDK currently supports **17 Canvas API resources** organized into four main categories:

### 📚 Core Course Management
- **Courses** - Full CRUD operations for course creation and management
- **Modules** - Content organization with position ordering
- **Module Items** - Individual items within modules (assignments, pages, files, etc.)
- **Sections** - Course section management
- **Tabs** - Course navigation customization
- **Pages** - Wiki-style content pages

### 👥 User & Enrollment Management
- **Users** - User profiles and account management
- **Enrollments** - Course enrollment with role management

### 📝 Assessment & Grading
- **Assignments** - Assignment creation with due dates and grading
- **Quizzes** - Quiz management with time limits and attempts
- **Quiz Submissions** - Student quiz attempts and answers
- **Submissions** - Assignment submissions with file uploads
- **Submission Comments** - Feedback and grading comments

### 🔧 Content & Tools
- **Discussion Topics** - Threaded discussions with grading support
- **Files** - File uploads using Canvas 3-step process
- **External Tools** - LTI (Learning Tools Interoperability) integrations
- **Module Assignment Overrides** - Custom dates for specific sections/students

## 🧪 Testing

Run the test suite using Docker:

```bash
# Run all tests
docker compose exec php composer test

# Run with coverage
docker compose exec php composer test:coverage

# Run specific test
docker compose exec php ./vendor/bin/phpunit tests/Api/Courses/CourseTest.php
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](https://github.com/jjuanrivvera/canvas-lms-kit/wiki/Contributing‐Guidelines) for details on:

- Code standards (PSR-12)
- Testing requirements
- Pull request process
- Development setup

## 📄 License

This SDK is open-sourced software licensed under the [MIT license](LICENSE).

## 💬 Support

- **Issues**: [GitHub Issues](https://github.com/jjuanrivvera/canvas-lms-kit/issues)
- **Discussions**: [GitHub Discussions](https://github.com/jjuanrivvera/canvas-lms-kit/discussions)
- **Email**: jjuanrivvera@gmail.com

## 🔗 Links

- **Repository**: [https://github.com/jjuanrivvera/canvas-lms-kit](https://github.com/jjuanrivvera/canvas-lms-kit)
- **API Documentation**: [https://jjuanrivvera.github.io/canvas-lms-kit/](https://jjuanrivvera.github.io/canvas-lms-kit/)
- **Wiki Documentation**: [https://github.com/jjuanrivvera/canvas-lms-kit/wiki](https://github.com/jjuanrivvera/canvas-lms-kit/wiki)
- **Packagist**: [https://packagist.org/packages/jjuanrivvera/canvas-lms-kit](https://packagist.org/packages/jjuanrivvera/canvas-lms-kit)
- **Canvas API Docs**: [https://canvas.instructure.com/doc/api/](https://canvas.instructure.com/doc/api/)