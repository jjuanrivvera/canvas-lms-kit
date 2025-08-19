<div align="center">
  <img src="img/logos/logo.png" alt="Canvas LMS Kit" width="200">
  
  # Canvas LMS Kit
  
  [![Latest Version](https://img.shields.io/packagist/v/jjuanrivvera/canvas-lms-kit.svg?style=flat-square)](https://packagist.org/packages/jjuanrivvera/canvas-lms-kit)
  [![Total Downloads](https://img.shields.io/packagist/dt/jjuanrivvera/canvas-lms-kit.svg?style=flat-square)](https://packagist.org/packages/jjuanrivvera/canvas-lms-kit)
  [![CI](https://github.com/jjuanrivvera/canvas-lms-kit/actions/workflows/ci.yml/badge.svg)](https://github.com/jjuanrivvera/canvas-lms-kit/actions/workflows/ci.yml)
  [![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg?style=flat-square)](https://php.net)
  [![License](https://img.shields.io/github/license/jjuanrivvera/canvas-lms-kit?style=flat-square)](https://github.com/jjuanrivvera/canvas-lms-kit/blob/main/LICENSE)

  **The most comprehensive PHP SDK for Canvas LMS API. Production-ready with 37 APIs implemented.**
</div>

---

## ✨ Why Canvas LMS Kit?

- 🚀 **Production Ready**: Rate limiting, middleware support, battle-tested
- 📚 **Comprehensive**: 37 Canvas APIs fully implemented
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
$courses = Course::get();  // Get first page
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

### API Key Authentication (Simple)

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

### OAuth 2.0 Authentication (User-Based) 🆕

Canvas LMS Kit now supports OAuth 2.0 for user-specific authentication with automatic token refresh!

```php
use CanvasLMS\Config;
use CanvasLMS\Auth\OAuth;

// Configure OAuth credentials
Config::setOAuthClientId('your-client-id');
Config::setOAuthClientSecret('your-client-secret');
Config::setOAuthRedirectUri('https://yourapp.com/oauth/callback');
Config::setBaseUrl('https://canvas.instructure.com');

// Step 1: Get authorization URL
$authUrl = OAuth::getAuthorizationUrl(['state' => 'random-state']);
// Redirect user to $authUrl

// Step 2: Handle callback
$tokenData = OAuth::exchangeCode($_GET['code']);

// Step 3: Use OAuth mode
Config::useOAuth();

// Now all API calls use OAuth with automatic token refresh!
$courses = Course::get(); // User's courses (first page)
```

### Environment Variable Configuration

Perfect for containerized deployments and 12-factor apps:

```bash
# .env file
CANVAS_BASE_URL=https://canvas.instructure.com
CANVAS_API_KEY=your-api-key
# OR for OAuth:
CANVAS_OAUTH_CLIENT_ID=your-client-id
CANVAS_OAUTH_CLIENT_SECRET=your-client-secret
CANVAS_AUTH_MODE=oauth
```

```php
// Auto-detect from environment
Config::autoDetectFromEnvironment();
// Ready to use!
```

### Logging Configuration

The SDK supports any PSR-3 compatible logger for comprehensive debugging and monitoring:

```php
use CanvasLMS\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Configure with Monolog
$logger = new Logger('canvas-lms');
$logger->pushHandler(new StreamHandler('logs/canvas.log', Logger::INFO));
Config::setLogger($logger);

// All API calls, OAuth operations, pagination, and file uploads are now logged!
```

#### Symfony Integration

```php
use Psr\Log\LoggerInterface;

public function __construct(LoggerInterface $logger) {
    Config::setLogger($logger);
}
```

#### Advanced Logging Configuration

```php
// Enable detailed request/response logging
Config::setMiddleware([
    'logging' => [
        'enabled' => true,
        'log_requests' => true,
        'log_responses' => true,
        'log_errors' => true,
        'log_timing' => true,
        'log_level' => \Psr\Log\LogLevel::DEBUG,
        'sanitize_fields' => ['password', 'token', 'api_key', 'secret'],
        'max_body_length' => 1000
    ]
]);
```

**What Gets Logged:**
- ✅ All API requests and responses (with sensitive data sanitization)
- ✅ OAuth token operations (refresh, revoke, exchange)
- ✅ Pagination operations with performance metrics
- ✅ File upload progress (3-step process)
- ✅ Rate limit headers and API costs
- ✅ Error conditions with context

**Security:** The logging middleware automatically sanitizes sensitive fields like passwords, tokens, and API keys to prevent accidental exposure in logs.

## 💡 Usage Examples

### Working with Courses

```php
use CanvasLMS\Api\Courses\Course;

// Get first page of courses (memory efficient)
$courses = Course::get();

// Get ALL courses across all pages automatically
// ⚠️ Warning: Be cautious with large datasets (1000+ items)
$allCourses = Course::all();

// Get paginated results with metadata (recommended for large datasets)
$paginated = Course::paginate(['per_page' => 50]);
echo "Total courses: " . $paginated->getTotalCount();

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

// Save changes with fluent interface support
$course->name = 'Updated Course Name';
$course->save()->enrollments(); // Save and immediately get enrollments

// Delete a course (also returns self for chaining)
$course->delete();
```

### Managing Assignments

```php
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Api\Submissions\Submission;
use CanvasLMS\Api\Courses\Course;

// Set course context for Assignment
$course = Course::find(123);
Assignment::setCourse($course);

// Create an assignment - simple array syntax
$assignment = Assignment::create([
    'name' => 'Final Project',
    'description' => 'Build a web application',
    'points_possible' => 100,
    'due_at' => '2025-03-15T23:59:59Z',
    'submission_types' => ['online_upload', 'online_url']
]);

// Grade submissions (requires both Course and Assignment context)
Submission::setCourse($course);
Submission::setAssignment($assignment);

$submission = Submission::update($studentId, [
    'posted_grade' => 95,
    'comment' => 'Excellent work!'
]);
```

### Working with Modules and Module Items

```php
use CanvasLMS\Api\Modules\Module;
use CanvasLMS\Api\Modules\ModuleItem;

// Get course and set context
$course = Course::find(123);
Module::setCourse($course);

// Create a module
$module = Module::create([
    'name' => 'Week 1: Introduction',
    'position' => 1,
    'published' => true
]);

// Add items to the module (requires both course and module context)
ModuleItem::setCourse($course);
ModuleItem::setModule($module);

// Add an assignment to the module
$item = ModuleItem::create([
    'title' => 'Week 1 Assignment',
    'type' => 'Assignment',
    'content_id' => $assignment->id,
    'position' => 1,
    'completion_requirement' => [
        'type' => 'must_submit'
    ]
]);

// Or use the course instance method (recommended)
$modules = $course->modules();
```

### Working with Current User

```php
use CanvasLMS\Api\Users\User;

// Get current authenticated user instance
$currentUser = User::self();

// Canvas supports 'self' for these endpoints:
$profile = $currentUser->getProfile();
$activityStream = $currentUser->getActivityStream();
$todos = $currentUser->getTodoItems();
$groups = $currentUser->groups();

// Other methods require explicit user ID
$user = User::find(123);
$enrollments = $user->enrollments();
$courses = $user->courses();
```

### Managing Groups

```php
use CanvasLMS\Api\Groups\Group;
use CanvasLMS\Api\Groups\GroupMembership;

// Create a group in your account
$group = Group::create([
    'name' => 'Study Group Alpha',
    'description' => 'Weekly study sessions',
    'is_public' => false,
    'join_level' => 'invitation_only'
]);

// Add members to the group
$membership = $group->createMembership([
    'user_id' => 456,
    'workflow_state' => 'accepted'
]);

// Get group activity stream
$activities = $group->activityStream();

// Invite users by email
$group->invite(['student1@example.com', 'student2@example.com']);
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

### Feature Flags

```php
use CanvasLMS\Api\FeatureFlags\FeatureFlag;
use CanvasLMS\Api\Courses\Course;

// Get feature flags for the account
$flags = FeatureFlag::get();     // First page
$allFlags = FeatureFlag::all();  // All flags

// Get a specific feature flag
$flag = FeatureFlag::find('new_gradebook');

// Enable a feature for the account
$flag->enable();

// Disable a feature
$flag->disable();

// Feature flags for a specific course
$course = Course::find(123);
$courseFlags = $course->featureFlags();

// Enable a feature for a specific course
$courseFlag = $course->getFeatureFlag('anonymous_marking');
$courseFlag->enable();
```

### Conversations (Internal Messaging)

```php
use CanvasLMS\Api\Conversations\Conversation;

// Get conversations for the current user
$conversations = Conversation::get(['scope' => 'unread']); // First page
$allConversations = Conversation::all(['scope' => 'unread']); // All pages

// Create a new conversation
$conversation = Conversation::create([
    'recipients' => ['user_123', 'course_456_students'],
    'subject' => 'Assignment Feedback',
    'body' => 'Great work on your recent submission!',
    'group_conversation' => true
]);

// Add a message to existing conversation
$conversation->addMessage([
    'body' => 'Following up on my previous message...',
    'attachment_ids' => [789] // Attach files
]);

// Manage conversation state
$conversation->markAsRead();
$conversation->star();
$conversation->archive();

// Batch operations
Conversation::batchUpdate([1, 2, 3], ['event' => 'mark_as_read']);
Conversation::markAllAsRead();

// Get unread count
$unreadCount = Conversation::getUnreadCount();
```

### Working with Multi-Context Resources

Some Canvas resources exist in multiple contexts (Account, Course, User, Group). Canvas LMS Kit follows an **Account-as-Default** convention for consistency:

```php
use CanvasLMS\Api\Rubrics\Rubric;
use CanvasLMS\Api\ExternalTools\ExternalTool;
use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Api\Files\File;

// Direct calls default to Account context
$rubrics = Rubric::get();           // Account-level rubrics (first page)
$tools = ExternalTool::get();       // Account-level external tools (first page)
$events = CalendarEvent::get();     // Account-level calendar events (first page)
$files = File::get();               // Exception: User files (no account context)

// Course-specific access through Course instance
$course = Course::find(123);
$courseRubrics = $course->rubrics();            // Course-specific rubrics
$courseTools = $course->externalTools();        // Course-specific tools
$courseEvents = $course->calendarEvents();      // Course-specific events
$courseFiles = $course->files();                // Course-specific files

// Direct context access when needed
$userEvents = CalendarEvent::fetchByContext('user', 456);
$groupFiles = File::fetchByContext('groups', 789);
```

**Multi-Context Resources:**
- **Rubrics** (Account/Course)
- **External Tools** (Account/Course)
- **Calendar Events** (Account/Course/User/Group)
- **Files** (User/Course/Group) - No Account context
- **Groups** (Account/Course/User)
- **Content Migrations** (Account/Course/Group/User)

### Performance Considerations & Memory Usage

When working with large Canvas instances (universities, enterprise organizations), be mindful of memory usage:

```php
// ✅ GOOD: Memory-efficient for large datasets
$result = User::paginate(['per_page' => 100]);
while ($result) {
    foreach ($result->getData() as $user) {
        // Process batch of 100 users
    }
    $result = $result->hasNextPage() ? $result->getNextPage() : null;
}

// ✅ GOOD: Get only what you need
$recentCourses = Course::get(['per_page' => 20]); // Just first 20

// ⚠️ CAUTION: Loads entire dataset into memory
$allUsers = User::all();  // Could be 50,000+ users!
$allEnrollments = Enrollment::all(); // Could be millions!

// ⚠️ BETTER: Process in batches for large datasets
$page = 1;
do {
    $batch = Enrollment::paginate(['page' => $page++, 'per_page' => 500]);
    // Process batch
} while ($batch->hasNextPage());
```

**Memory Guidelines:**
- Use `get()` for dashboards and quick views (1 API call)
- Use `paginate()` for large datasets and UI tables (controlled memory)
- Use `all()` only when you need complete data AND know it's reasonably sized
- Consider your server's memory limit when using `all()` on production data

### Working with Course-Scoped Resources

Some Canvas resources are strictly course-scoped and require setting the course context before use:

```php
use CanvasLMS\Api\Pages\Page;
use CanvasLMS\Api\Quizzes\Quiz;
use CanvasLMS\Api\Modules\Module;
use CanvasLMS\Api\DiscussionTopics\DiscussionTopic;
use CanvasLMS\Api\Courses\Course;

// Get your course
$course = Course::find(123);

// Option 1: Set context for each API (required for direct API calls)
Page::setCourse($course);
Quiz::setCourse($course);
Module::setCourse($course);
DiscussionTopic::setCourse($course);

// Now you can use the APIs directly
$pages = Page::get();         // Get first page
$quizzes = Quiz::all();       // Get all quizzes
$modules = Module::get();     // Get first page
$discussions = DiscussionTopic::all(); // Get all discussions

// Option 2: Use course instance methods (recommended - no context setup needed)
$pages = $course->pages();          // Returns first page only
$quizzes = $course->quizzes();      // Returns first page only
$modules = $course->modules();      // Returns first page only
$discussions = $course->discussionTopics(); // Returns first page only
```

**Important Notes:**
1. These APIs will throw an exception if you try to use them without setting the course context first.
2. **Relationship methods return FIRST PAGE ONLY** for performance. To get all items:
   ```php
   // Get ALL modules for a course
   Module::setCourse($course);
   $allModules = Module::all();  // Gets all pages
   
   // Or paginate for control
   $paginated = Module::paginate(['per_page' => 50]);
   ```

### Learning Outcomes

```php
use CanvasLMS\Api\Outcomes\Outcome;
use CanvasLMS\Api\OutcomeGroups\OutcomeGroup;

// Create an outcome group
$group = OutcomeGroup::create([
    'title' => 'Critical Thinking Skills',
    'description' => 'Core competencies for analytical thinking'
]);

// Create a learning outcome
$outcome = Outcome::create([
    'title' => 'Analyze Complex Problems',
    'description' => 'Student can break down complex problems into manageable parts',
    'mastery_points' => 3,
    'ratings' => [
        ['description' => 'Exceeds', 'points' => 4],
        ['description' => 'Mastery', 'points' => 3],
        ['description' => 'Near Mastery', 'points' => 2],
        ['description' => 'Below Mastery', 'points' => 1]
    ]
]);

// Link outcome to a group
$group->linkOutcome($outcome->id);

// Align outcome with an assignment
$assignment = Assignment::find(123);
$assignment->alignOutcome($outcome->id, [
    'mastery_score' => 3,
    'possible_score' => 4
]);
```

### Content Migrations

```php
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\ContentMigrations\ContentMigration;

// Copy content between courses
$course = Course::find(456);
$migration = $course->copyContentFrom(123, [
    'except' => ['announcements', 'calendar_events']
]);

// Selective copy with specific items
$migration = $course->selectiveCopyFrom(123, [
    'assignments' => [1, 2, 3],
    'quizzes' => ['quiz-1', 'quiz-2'],
    'modules' => [10, 11]
]);

// Import a Common Cartridge file
$migration = $course->importCommonCartridge('/path/to/course.imscc');

// Copy with date shifting
$migration = $course->copyWithDateShift(123, '2024-01-01', '2025-01-01', [
    'shift_dates' => true,
    'old_start_date' => '2024-01-01',
    'new_start_date' => '2025-01-01'
]);

// Track migration progress
while (!$migration->isCompleted()) {
    $progress = $migration->getProgress();
    echo "Migration {$progress->workflow_state}: {$progress->completion}%\n";
    sleep(5);
    $migration->refresh();
}

// Handle migration issues
$issues = $migration->migrationIssues();
foreach ($issues as $issue) {
    if ($issue->workflow_state === 'active') {
        $issue->resolve();
    }
}
```

## 📊 Supported APIs

### ✅ Currently Implemented (37 APIs)

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

- ✅ **Users** - User management with self() pattern support
- ✅ **Enrollments** - Course enrollments
- ✅ **Admins** - Administrative roles
- ✅ **Accounts** - Account management
</details>

<details>
<summary><b>📝 Assessment & Grading</b></summary>

- ✅ **Assignments** - Assignment management
- ✅ **Quizzes** - Quiz creation and management
- ✅ **Quiz Submissions** - Student attempts
- ✅ **Submissions** - Assignment submissions
- ✅ **Submission Comments** - Feedback
- ✅ **Rubrics** - Grading criteria and assessment
- ✅ **Rubric Associations** - Link rubrics to assignments
- ✅ **Rubric Assessments** - Grade with rubrics
- ✅ **Outcomes** - Learning objectives and competencies
- ✅ **Outcome Groups** - Organize learning outcomes
- ✅ **Outcome Imports** - Import outcome data
- ✅ **Outcome Results** - Track student achievement
</details>

<details>
<summary><b>💬 Communication & Collaboration</b></summary>

- ✅ **Discussion Topics** - Forums and discussions
- ✅ **Groups** - Student groups and collaboration
- ✅ **Group Categories** - Organize and manage groups
- ✅ **Group Memberships** - Group member management
- ✅ **Conferences** - Web conferencing integration
- ✅ **Conversations** - Internal messaging system
- 🔄 **Announcements** - Course announcements (coming soon)
</details>

<details>
<summary><b>🔧 Tools & Integration</b></summary>

- ✅ **Files** - File management and uploads
- ✅ **External Tools** - LTI integrations
- ✅ **Module Assignment Overrides** - Custom dates
- ✅ **Calendar Events** - Event management
- ✅ **Appointment Groups** - Scheduling
- ✅ **Progress** - Async operation tracking
- ✅ **Content Migrations** - Import/export course content
- ✅ **Migration Issues** - Handle import problems
- ✅ **Feature Flags** - Manage Canvas feature toggles
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

### Pagination Support (Simplified API)

```php
// Three simple methods for all your pagination needs:

// 1. get() - Fetch first page only (fast, memory efficient)
$courses = Course::get(['per_page' => 100]); 

// 2. all() - Fetch ALL items across all pages automatically
$allCourses = Course::all();

// 3. paginate() - Get results with pagination metadata
$result = Course::paginate(['per_page' => 50]);
echo "Page {$result->getCurrentPage()} of {$result->getTotalPages()}";
echo "Total courses: {$result->getTotalCount()}";

// Access the data
foreach ($result->getData() as $course) {
    echo $course->name;
}

// Navigate pages
if ($result->hasNextPage()) {
    $nextPage = $result->getNextPage();
}
```

### Fluent Interface & Method Chaining

All `save()` and `delete()` methods return the instance, enabling method chaining:

```php
// Save and continue working with the object
$course->name = 'New Name';
$enrollments = $course->save()->enrollments();

// Chain multiple operations
$assignment = new Assignment();
$assignment->name = 'Final Project';
$assignment->points_possible = 100;
$submissions = $assignment->save()->getSubmissions();

// Error handling with fluent interface
try {
    $user->email = 'new@example.com';
    $user->save()->enrollments(); // Save and get enrollments
} catch (CanvasApiException $e) {
    // Handle error - no more silent failures!
}
```

### Relationship Methods

```php
// Efficient relationship loading
$course = Course::find(123);
$students = $course->getStudents();
$assignments = $course->assignments();
$modules = $course->modules();
```

### Context Management (Account-as-Default)

Canvas LMS Kit uses the **Account-as-Default** convention for multi-context resources:

```php
// Direct API calls use Account context (Config::getAccountId())
$groups = Group::get();              // First page of groups in the account
$allGroups = Group::all();           // All groups in the account
$rubrics = Rubric::get();            // First page of rubrics in the account
$migrations = ContentMigration::all(); // All migrations in the account

// Course-specific access via Course instance methods
$course = Course::find(123);
$courseGroups = $course->groups();              // Groups in this course
$courseRubrics = $course->rubrics();            // Rubrics in this course
$courseMigrations = $course->contentMigrations(); // Migrations in this course

// User-specific access via User instance methods
$user = User::find(456);
$userGroups = $user->groups();                    // User's groups
$userMigrations = $user->contentMigrations();    // User's migrations

// Group-specific access via Group instance methods
$group = Group::find(789);
$groupMigrations = $group->contentMigrations();  // Group's migrations
```

**Why Account-as-Default?**
- ✅ Consistency across all multi-context resources
- ✅ Respects Canvas hierarchy (Account → Course → User/Group)
- ✅ Clean separation of concerns
- ✅ No confusion about which context is being used

[📖 Full Context Management Guide](https://github.com/jjuanrivvera/canvas-lms-kit/wiki/Context-Management-Guide)

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