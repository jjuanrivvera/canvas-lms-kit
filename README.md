# CanvasLMS PHP SDK Library

This library provides a PHP SDK for the Canvas Learning Management System (Canvas LMS). It's designed to facilitate developers in integrating with the Canvas LMS API, enabling efficient management of courses, users, and other features provided by the Canvas LMS API.

## System Requirements

- PHP 8.0 or later.
- GuzzleHttp client for HTTP requests.
- Composer for managing dependencies.

## Installation

Install the library via Composer:

```bash
composer require jjuanrivvera/canvaslms-php-sdk
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

Before using the SDK, set up the API key and Base URL:

```php
CanvasLMS\Config::setApiKey('your-api-key');
CanvasLMS\Config::setBaseUrl('https://canvaslms.com/api/v1');
```

## Contributing

We welcome contributions to the SDK. Please adhere to the PHP coding standards and include tests for new features or bug fixes.

## License

This SDK is open-sourced under the [MIT License](LICENSE).

## Contact and Support

For any questions or support, feel free to contact us at [jjuanrivvera@gmail.com].