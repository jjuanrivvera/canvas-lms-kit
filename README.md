# CanvasLMS PHP Tool Kit

This library provides a PHP SDK for the Canvas Learning Management System (Canvas LMS). It's designed to facilitate developers in integrating with the Canvas LMS API, enabling efficient management of courses, users, and other features provided by the Canvas LMS API.

## System Requirements

- PHP 8.0 or later.
- GuzzleHttp client for HTTP requests.
- Composer for managing dependencies.

## Installation

Install the library via Composer:

```bash
composer require jjuanrivvera/canvas-lms-kit
```

Ensure your `composer.json` file includes the necessary dependencies.

## Basic Usage

Here are some examples of how to use the library:

### Creating a Course

```php
use CanvasLMS\Api\Courses\Course;

$courseData = [
    'name' => 'Introduction to Philosophy',
    'courseCode' => 'PHIL101',
    // ... additional course data ...
];
$course = Course::create($courseData);

// Or using an object

$course = new Course($courseData);
$course->save();
```

### Updating a Course

```php
$updatedData = [
    'name' => 'Advanced Philosophy',
    'courseCode' => 'PHIL201',
    // ... additional updated data ...
];
$updatedCourse = Course::update(123, $updatedData); // 123 is the course ID

// Or using an object
$course = Course::find(123);
$course->name = 'Advanced Philosophy';
$course->courseCode = 'PHIL201';
$course->save();
```

### Retrieving a Course

```php
$course = Course::find(123); // 123 is the course ID
```

## Configuration

### Basic Configuration

Before using the SDK, set up the API key and Base URL:

```php
use CanvasLMS\Config;

Config::setApiKey('your-api-key');
Config::setBaseUrl('https://canvas.instructure.com');
Config::setAccountId(1); // Optional: default is 1
```

### Environment-Based Configuration

The SDK can automatically detect configuration from environment variables:

```php
// Set these environment variables:
// CANVAS_API_KEY=your-api-key
// CANVAS_BASE_URL=https://canvas.instructure.com
// CANVAS_ACCOUNT_ID=1
// CANVAS_API_VERSION=v1
// CANVAS_TIMEOUT=30

Config::autoDetect(); // Reads from environment variables
```

### Multi-Tenant Configuration

The SDK supports multiple Canvas instances using contexts:

```php
// Configure first Canvas instance
Config::setContext('production');
Config::setApiKey('prod-api-key');
Config::setBaseUrl('https://prod.instructure.com');
Config::setAccountId(1);

// Configure second Canvas instance
Config::setContext('staging');
Config::setApiKey('staging-api-key');
Config::setBaseUrl('https://staging.instructure.com');
Config::setAccountId(2);

// Switch between contexts
Config::setContext('production');
$prodCourse = Course::find(123); // Uses production config

Config::setContext('staging');
$stagingCourse = Course::find(456); // Uses staging config
```

### Testing Configuration

For better test isolation, use contexts to prevent test interference:

```php
// In your test setup
Config::setContext('test');
Config::setApiKey('test-api-key');
Config::setBaseUrl('https://test.canvas.local');

// In your test teardown
Config::resetContext('test'); // Clean up test configuration
```

### Configuration Validation

Validate your configuration to ensure all required values are set:

```php
try {
    Config::validate(); // Throws exception if configuration is incomplete
} catch (ConfigurationException $e) {
    echo "Configuration error: " . $e->getMessage();
}
```

### Debugging Configuration

Debug your current configuration (masks sensitive data):

```php
$debug = Config::debugConfig();
print_r($debug);
// Output:
// [
//     'active_context' => 'default',
//     'app_key' => '***-key',  // Masked for security
//     'base_url' => 'https://canvas.instructure.com/',
//     'api_version' => 'v1',
//     'account_id' => 1,
//     'all_contexts' => ['default', 'production', 'staging']
// ]
```

## Troubleshooting

### Common Configuration Issues

#### Invalid URL Errors
```
Canvas URL must use HTTPS for security: http://canvas.example.com
```
**Solution**: Use HTTPS URLs for production Canvas instances. HTTP is only allowed for localhost/development:
```php
// ✅ Correct
Config::setBaseUrl('https://canvas.instructure.com');

// ❌ Incorrect (production)
Config::setBaseUrl('http://canvas.instructure.com');

// ✅ Allowed for development
Config::setBaseUrl('http://localhost:3000');
```

#### Environment Variable Validation Errors
```
CANVAS_ACCOUNT_ID must be a positive integer, got: invalid
```
**Solution**: Ensure environment variables contain valid values:
```bash
# ✅ Correct
export CANVAS_ACCOUNT_ID=123
export CANVAS_TIMEOUT=30

# ❌ Incorrect
export CANVAS_ACCOUNT_ID=invalid
export CANVAS_TIMEOUT=abc
```

#### Configuration Not Found
```
API key not set for context: production
```
**Solution**: Ensure all required configuration is set for each context:
```php
Config::setContext('production');
Config::setApiKey('your-api-key');
Config::setBaseUrl('https://canvas.example.com');

// Validate configuration
Config::validate(); // Throws exception if incomplete
```

#### Context Isolation Issues
If tests are interfering with each other, ensure proper context cleanup:
```php
// In test tearDown
Config::resetContext('test');

// Or use unique context names per test
Config::setContext('test-' . uniqid());
```

### Debugging Configuration

Use the debug method to inspect current configuration:
```php
$debug = Config::debugConfig();
print_r($debug);
```

Enable notices to see when default values are used:
```php
// This will trigger a notice if account ID isn't explicitly set
$accountId = Config::getAccountId();
```

### Performance Considerations

For applications with many contexts:
- Clean up unused contexts periodically
- Consider using context prefixes for organization
- Avoid frequent context switching in hot paths

## 🗺️ Project Roadmap

This SDK is actively developed with a clear strategic roadmap. See [STRATEGIC_ROADMAP.md](STRATEGIC_ROADMAP.md) for:

- **Current Canvas API coverage** (~15%) and target coverage (85%+)
- **5-phase development plan** over 9 months to production readiness
- **Prioritized feature development** based on real-world Canvas usage
- **Enterprise readiness requirements** for production deployments

**Current Phase**: Foundation completion (Enrollments, Grades, Submissions APIs)

## Contributing

We welcome contributions to the SDK. Please adhere to the PHP coding standards and include tests for new features or bug fixes.

## License

This SDK is open-sourced under the [MIT License](LICENSE).

## Contact and Support

For any questions or support, feel free to contact us at [jjuanrivvera@gmail.com].
