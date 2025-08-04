<div align="center">
  <img src="img/logos/logo.png" alt="Canvas LMS Kit" width="200">
  
  # Canvas LMS Kit
  
  [![Latest Version](https://img.shields.io/packagist/v/jjuanrivvera/canvas-lms-kit.svg?style=flat-square)](https://packagist.org/packages/jjuanrivvera/canvas-lms-kit)
  [![Total Downloads](https://img.shields.io/packagist/dt/jjuanrivvera/canvas-lms-kit.svg?style=flat-square)](https://packagist.org/packages/jjuanrivvera/canvas-lms-kit)
  [![CI](https://github.com/jjuanrivvera/canvas-lms-kit/actions/workflows/ci.yml/badge.svg)](https://github.com/jjuanrivvera/canvas-lms-kit/actions/workflows/ci.yml)
  [![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg?style=flat-square)](https://php.net)
  [![License](https://img.shields.io/github/license/jjuanrivvera/canvas-lms-kit?style=flat-square)](https://github.com/jjuanrivvera/canvas-lms-kit/blob/main/LICENSE)

  **The most comprehensive PHP SDK for Canvas LMS API. Production-ready with 90% API coverage.**
</div>

---

## ✨ Why Canvas LMS Kit?

- 🚀 **Production Ready**: Rate limiting, middleware support, battle-tested
- 📚 **Comprehensive**: 21 Canvas APIs fully implemented (90% coverage)
- 🛡️ **Type Safe**: Full PHP 8.1+ type declarations and PHPStan level 6
- 🔧 **Developer Friendly**: Intuitive Active Record pattern - just pass arrays!
- 📖 **Well Documented**: Extensive examples, guides, and API reference
- ⚡ **Performance**: Built-in pagination, caching support, and optimized queries

## 🎯 Quick Start

```php
use CanvasLMS\Config;
use CanvasLMS\Api\Courses\Course;

Config::setApiKey('your-api-key');
Config::setBaseUrl('https://canvas.instructure.com');

// It's that simple!
$courses = Course::fetchAll();
foreach ($courses as $course) {
    echo $course->name . "\n";
}
```

## 📑 Table of Contents

- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage Examples](#-usage-examples)
- [Supported APIs](#-supported-apis)
- [Advanced Features](#-advanced-features)
- [Testing](#-testing)
- [Contributing](#-contributing)
- [Support](#-support)

## 📋 Requirements

- PHP 8.1 or higher
- Composer
- Canvas LMS API token
- Extensions: `json`, `curl`, `mbstring`

## 📦 Installation

```bash
composer require jjuanrivvera/canvas-lms-kit
```

## ⚙️ Configuration

```php
use CanvasLMS\Config;

// Basic configuration
Config::setApiKey('your-api-key');
Config::setBaseUrl('https://canvas.instructure.com');

// Optional: Set account ID for scoped operations
Config::setAccountId(1);

// Optional: Configure middleware
Config::setMiddleware([
    'retry' => ['max_attempts' => 3],
    'rate_limit' => ['wait_on_limit' => true],
]);
```

## 💡 Usage Examples

### Working with Courses

```php
use CanvasLMS\Api\Courses\Course;

// List all courses
$courses = Course::fetchAll();

// Find a specific course
$course = Course::find(123);

// Create a new course - just pass an array!
$course = Course::create([
    'name' => 'Introduction to PHP',
    'course_code' => 'PHP101',
    'start_at' => '2025-02-01T00:00:00Z'
]);

// Update a course
$course->update([
    'name' => 'Advanced PHP Programming'
]);

// Delete a course
$course->delete();
```

### Managing Assignments

```php
use CanvasLMS\Api\Assignments\Assignment;

// Create an assignment - simple array syntax
$assignment = Assignment::create([
    'course_id' => 123,
    'name' => 'Final Project',
    'description' => 'Build a web application',
    'points_possible' => 100,
    'due_at' => '2025-03-15T23:59:59Z',
    'submission_types' => ['online_upload', 'online_url']
]);

// Grade submissions
$submission = $assignment->getSubmission($studentId);
$submission->grade([
    'posted_grade' => 95,
    'comment' => 'Excellent work!'
]);
```

### Working with Current User

```php
use CanvasLMS\Api\Users\User;

// Get current authenticated user instance
$currentUser = User::self();

// Canvas supports 'self' for these endpoints:
$profile = $currentUser->getProfile();
$activityStream = $currentUser->getActivityStream();
$todoItems = $currentUser->getTodo();
$groups = $currentUser->groups();

// Other methods require explicit user ID
$user = User::find(123);
$enrollments = $user->enrollments();
$courses = $user->courses();
```

### File Uploads

```php
use CanvasLMS\Api\Files\File;

// Upload a file to a course
$file = File::upload([
    'course_id' => 123,
    'file_path' => '/path/to/document.pdf',
    'name' => 'Course Syllabus.pdf',
    'parent_folder_path' => 'course_documents'
]);
```

## 📊 Supported APIs

### ✅ Currently Implemented (21 APIs - 90% Coverage)

<details>
<summary><b>📚 Core Course Management</b></summary>

- ✅ **Courses** - Full CRUD operations
- ✅ **Modules** - Content organization
- ✅ **Module Items** - Individual content items
- ✅ **Sections** - Course sections
- ✅ **Tabs** - Navigation customization
- ✅ **Pages** - Wiki-style content
</details>

<details>
<summary><b>👥 Users & Enrollment</b></summary>

- ✅ **Users** - User management
- ✅ **Enrollments** - Course enrollments
- ✅ **Admin/Account** - Administrative functions
</details>

<details>
<summary><b>📝 Assessment & Grading</b></summary>

- ✅ **Assignments** - Assignment management
- ✅ **Quizzes** - Quiz creation and management
- ✅ **Quiz Submissions** - Student attempts
- ✅ **Submissions** - Assignment submissions
- ✅ **Submission Comments** - Feedback
- ✅ **Rubrics** - Grading criteria
</details>

<details>
<summary><b>💬 Communication & Collaboration</b></summary>

- ✅ **Discussion Topics** - Forums and discussions
- 🔄 **Announcements** - Course announcements (coming soon)
- 🔄 **Groups** - Student groups (coming soon)
</details>

<details>
<summary><b>🔧 Tools & Integration</b></summary>

- ✅ **Files** - File management
- ✅ **External Tools** - LTI integrations
- ✅ **Module Assignment Overrides** - Custom dates
- ✅ **Calendar Events** - Event management
- ✅ **Appointment Groups** - Scheduling
- ✅ **Progress** - Async operation tracking
</details>

## 🚀 Advanced Features

### Production-Ready Middleware

```php
// The SDK automatically handles rate limiting and retries
$course = Course::find(123); // Protected by middleware

// Canvas API Rate Limiting (3000 requests/hour)
// ✅ Automatic throttling when approaching limits
// ✅ Smart backoff strategies
// ✅ Transparent to your application
```

### Multi-Tenant Support

```php
// Manage multiple Canvas instances
Config::setContext('production');
$prodCourse = Course::find(123);

Config::setContext('test');
$testCourse = Course::find(456);
```

### Pagination Support

```php
// Automatic pagination handling
$allCourses = Course::fetchAll(); // Fetches ALL pages automatically

// Manual pagination control
$paginator = Course::fetchAllPaginated(['per_page' => 50]);
foreach ($paginator as $page) {
    foreach ($page as $course) {
        // Process each course
    }
}
```

### Relationship Methods

```php
// Efficient relationship loading
$course = Course::find(123);
$students = $course->getStudents();
$assignments = $course->getAssignments();
$modules = $course->getModules();
```

### Context Management

```php
// Account-as-Default Convention
// Resources default to account context when accessed directly
$groups = Group::fetchAll();  // Uses Config::getAccountId()
$rubrics = Rubric::fetchAll(); // Account-level rubrics

// Course-specific access via Course instance
$course = Course::find(123);
$courseGroups = $course->getGroups();
$courseRubrics = $course->getRubrics();

// User-specific access via User instance
$user = User::find(456);
$userGroups = $user->getGroups();
```

## 🧪 Testing

```bash
# Using Docker (recommended)
docker compose exec php composer test
docker compose exec php composer check  # Run all checks

# Local development
composer test
composer cs-fix   # Fix coding standards
composer phpstan  # Static analysis
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md).

### Quick Contribution Guide

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure all tests pass (`composer check`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 💬 Support

<table>
<tr>
<td align="center">
<a href="https://github.com/jjuanrivvera/canvas-lms-kit/issues/new?assignees=&labels=bug&template=bug_report.yml">
<img src="https://img.shields.io/badge/Report%20a%20Bug-red?style=for-the-badge" alt="Report a Bug">
</a>
</td>
<td align="center">
<a href="https://github.com/jjuanrivvera/canvas-lms-kit/issues/new?assignees=&labels=enhancement&template=feature_request.yml">
<img src="https://img.shields.io/badge/Request%20Feature-blue?style=for-the-badge" alt="Request Feature">
</a>
</td>
<td align="center">
<a href="https://github.com/jjuanrivvera/canvas-lms-kit/discussions">
<img src="https://img.shields.io/badge/Ask%20Question-green?style=for-the-badge" alt="Ask Question">
</a>
</td>
</tr>
</table>

### Resources

- 📚 **[Wiki Documentation](https://github.com/jjuanrivvera/canvas-lms-kit/wiki)** - Comprehensive guides
- 🔍 **[API Reference](https://jjuanrivvera.github.io/canvas-lms-kit/)** - Detailed API docs
- 💬 **[GitHub Discussions](https://github.com/jjuanrivvera/canvas-lms-kit/discussions)** - Community forum
- 📧 **Email**: jjuanrivvera@gmail.com

## ⭐ Show Your Support

If you find this project helpful, please consider giving it a star on GitHub! It helps others discover the project and motivates continued development.

<div align="center">

[![Star History Chart](https://api.star-history.com/svg?repos=jjuanrivvera/canvas-lms-kit&type=Date)](https://star-history.com/#jjuanrivvera/canvas-lms-kit&Date)

---

<b>Built with ❤️ by the Canvas LMS Kit community</b>

</div>