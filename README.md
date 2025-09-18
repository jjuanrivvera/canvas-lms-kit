<div align="center">
  <img src="img/logos/logo.png" alt="Canvas LMS Kit" width="200">
  
  # Canvas LMS Kit
  
  [![Latest Version](https://img.shields.io/packagist/v/jjuanrivvera/canvas-lms-kit.svg?style=flat-square)](https://packagist.org/packages/jjuanrivvera/canvas-lms-kit)
  [![Total Downloads](https://img.shields.io/packagist/dt/jjuanrivvera/canvas-lms-kit.svg?style=flat-square)](https://packagist.org/packages/jjuanrivvera/canvas-lms-kit)
  [![CI](https://github.com/jjuanrivvera/canvas-lms-kit/actions/workflows/ci.yml/badge.svg)](https://github.com/jjuanrivvera/canvas-lms-kit/actions/workflows/ci.yml)
  [![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg?style=flat-square)](https://php.net)
  [![License](https://img.shields.io/github/license/jjuanrivvera/canvas-lms-kit?style=flat-square)](https://github.com/jjuanrivvera/canvas-lms-kit/blob/main/LICENSE)

  **The most comprehensive PHP SDK for Canvas LMS API. Production-ready with 45 APIs implemented.**
</div>

---

## ✨ Why Canvas LMS Kit?

- 🚀 **Production Ready**: Rate limiting, middleware support, battle-tested
- 📚 **Comprehensive**: 45 Canvas APIs fully implemented
- 🛡️ **Type Safe**: Full PHP 8.1+ type declarations, strict_types, and PHPStan level 6
- 🔧 **Developer Friendly**: Intuitive Active Record pattern - just pass arrays!
- 📖 **Well Documented**: Extensive examples, guides, and API reference
- ⚡ **Performance**: Built-in pagination, caching support, and optimized queries
- 🎯 **Code Quality**: PHP-CS-Fixer integration, PSR-12 compliance

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
Config::autoDetect();
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

### Masquerading (Act As User) 🆕

The SDK supports Canvas's masquerading functionality, allowing administrators to perform API operations on behalf of other users. This is essential for administrative operations, support workflows, and testing.

#### Global Masquerading

Enable masquerading globally for all subsequent API calls:

```php
use CanvasLMS\Config;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Users\User;

// Enable masquerading as user 12345
Config::asUser(12345);

// All API calls will now include as_user_id=12345
$course = Course::find(123);              // Performed as user 12345
$assignments = $course->assignments();    // Also performed as user 12345
$enrollments = User::find(456)->enrollments(); // Also as user 12345

// Stop masquerading
Config::stopMasquerading();

// Subsequent calls are performed as the authenticated user
$normalCourse = Course::find(789);        // No masquerading
```

#### Context-Specific Masquerading

In multi-tenant environments, masquerading can be set per context:

```php
// Set masquerading for production context only
Config::setContext('production');
Config::asUser(12345, 'production');

// Only affects production context operations
Config::setContext('staging');
$stagingUser = User::find(1);  // Not masqueraded

Config::setContext('production');
$prodUser = User::find(1);      // Masqueraded as user 12345
```

#### Security Considerations

- **Permissions Required**: The authenticated user must have appropriate Canvas permissions to masquerade
- **Error Handling**: Canvas will return 401/403 errors if permissions are insufficient
- **Audit Trail**: Consider logging when masquerading is active for compliance

```php
// Check if masquerading is active
if (Config::isMasquerading()) {
    $masqueradeUserId = Config::getMasqueradeUserId();
    $logger->info("Performing operation as user {$masqueradeUserId}");
}
```

#### Common Use Cases

**Support Operations:**
```php
// Support agent helping a student
Config::asUser($studentId);
$submissions = Assignment::find($assignmentId)->getSubmission($studentId);
Config::stopMasquerading();
```

**Testing Permission-Based Features:**
```php
// Test different user roles
foreach ($testUsers as $userId => $role) {
    Config::asUser($userId);
    $canEdit = Course::find($courseId)->canEdit(); // Check permissions as this user
    echo "User {$userId} ({$role}): " . ($canEdit ? 'Can edit' : 'Cannot edit') . "\n";
}
Config::stopMasquerading();
```

**Batch Operations for Multiple Users:**
```php
// Process enrollments for multiple users
$userIds = [123, 456, 789];
foreach ($userIds as $userId) {
    Config::asUser($userId);
    $enrollment = Enrollment::create([
        'user_id' => $userId,
        'course_id' => $courseId,
        'enrollment_state' => 'active'
    ]);
    echo "Enrolled user {$userId} successfully\n";
}
Config::stopMasquerading();
```

## 📄 Pagination

Canvas LMS Kit provides three simple, consistent methods for handling paginated data across all resources:

### The Three Methods

```php
// 1. get() - Fetch first page only (default: 10 items)
$courses = Course::get();                    // First 10 courses
$courses = Course::get(['per_page' => 50]); // First 50 courses

// 2. paginate() - Get results with pagination metadata
$result = Course::paginate(['per_page' => 25]);
echo "Page {$result->getCurrentPage()} of {$result->getTotalPages()}";
echo "Total courses: {$result->getTotalCount()}";

// 3. all() - Fetch ALL items from all pages automatically
$allCourses = Course::all();  // ⚠️ Use with caution on large datasets!
```

### When to Use Each Method

| Method | Use Case | Memory Usage | API Calls |
|--------|----------|--------------|-----------|
| `get()` | Dashboards, quick views, limited displays | Low (1 page) | 1 |
| `paginate()` | UI tables, batch processing, when you need metadata | Low (1 page) | 1 per page |
| `all()` | Complete data export, small datasets (< 1000 items) | High (all data) | As many as needed |

### Memory-Safe Processing of Large Datasets

```php
// ❌ WRONG - May exhaust memory with large datasets
$allUsers = User::all();  // Could be 50,000+ users!
foreach ($allUsers as $user) {
    processUser($user);
}

// ✅ CORRECT - Process in batches using paginate()
$page = 1;
do {
    $batch = User::paginate(['page' => $page++, 'per_page' => 100]);
    
    foreach ($batch->getData() as $user) {
        processUser($user);
    }
    
    // Optional: Add delay to respect rate limits
    if ($batch->hasNextPage()) {
        sleep(1);
    }
    
} while ($batch->hasNextPage());
```

### Common Pagination Patterns

#### Pattern 1: Building a Paginated UI
```php
// In your controller
$page = $_GET['page'] ?? 1;
$result = Course::paginate(['page' => $page, 'per_page' => 25]);

// In your view
foreach ($result->getData() as $course) {
    echo "<tr><td>{$course->name}</td></tr>";
}

// Pagination controls
if ($result->hasPreviousPage()) {
    echo "<a href='?page=" . ($page - 1) . "'>Previous</a>";
}
if ($result->hasNextPage()) {
    echo "<a href='?page=" . ($page + 1) . "'>Next</a>";
}
echo "Page {$result->getCurrentPage()} of {$result->getTotalPages()}";
```

#### Pattern 2: Exporting All Data
```php
// For small datasets (< 1000 items)
$assignments = Assignment::all(['course_id' => 123]);
exportToCSV($assignments);

// For large datasets - stream to file
$csvFile = fopen('enrollments.csv', 'w');
$page = 1;

do {
    $batch = Enrollment::paginate(['page' => $page++, 'per_page' => 500]);
    
    foreach ($batch->getData() as $enrollment) {
        fputcsv($csvFile, [
            $enrollment->userId,
            $enrollment->courseId,
            $enrollment->enrollmentState
        ]);
    }
    
} while ($batch->hasNextPage());

fclose($csvFile);
```

#### Pattern 3: Finding Specific Items
```php
// When you need just a subset
$recentAssignments = Assignment::get([
    'per_page' => 10,
    'order_by' => 'due_at'
]);

// When searching through all pages
$found = false;
$page = 1;

do {
    $batch = User::paginate([
        'page' => $page++,
        'search_term' => 'john.doe@example.com'
    ]);
    
    foreach ($batch->getData() as $user) {
        if ($user->email === 'john.doe@example.com') {
            $found = $user;
            break 2; // Exit both loops
        }
    }
    
} while ($batch->hasNextPage() && !$found);
```

### PaginationResult Methods

The `paginate()` method returns a `PaginationResult` object with helpful methods:

```php
$result = Course::paginate(['per_page' => 20]);

// Data access
$courses = $result->getData();           // Array of Course objects
$total = $result->getTotalCount();       // Total number of courses

// Navigation
$result->hasNextPage();                  // true/false
$result->hasPreviousPage();              // true/false
$result->getNextPage();                  // Fetches next page (returns new PaginationResult)
$result->getPreviousPage();              // Fetches previous page

// Page information
$result->getCurrentPage();               // Current page number
$result->getTotalPages();                // Total number of pages
$result->getPerPage();                   // Items per page

// URL access (for custom implementations)
$result->getNextUrl();                   // Next page URL
$result->getPreviousUrl();               // Previous page URL
```

### Performance Guidelines

```php
// 📗 Small datasets (< 100 items): Safe to use all()
$modules = Module::all();
$sections = Section::all();

// 📙 Medium datasets (100-1000 items): Consider your use case
$assignments = Assignment::get(['per_page' => 100]);     // If you need subset
$assignments = Assignment::paginate(['per_page' => 100]); // If processing batches
$assignments = Assignment::all();                         // If you need everything

// 📕 Large datasets (1000+ items): Use paginate() for memory efficiency
$users = User::paginate(['per_page' => 100]);
$enrollments = Enrollment::paginate(['per_page' => 500]);

// Note: The SDK includes built-in rate limiting and retry logic,
// so fetching all pages is safe from an API perspective.
// The main consideration is memory usage for very large datasets.
```

### Relationship Methods & Large Datasets

**Important**: Relationship methods on Course/User/Group instances return **ALL pages** for completeness:

```php
$course = Course::find(123);
$modules = $course->modules();      // Returns ALL modules (all pages)
$enrollments = $course->enrollments(); // Returns ALL enrollments (could be thousands!)

// For large datasets, consider using pagination directly:
Enrollment::setCourse($course);
$paginatedEnrollments = Enrollment::paginate(['per_page' => 100]); // Memory efficient

// Or use paginate for control:
$paginatedModules = Module::paginate(['per_page' => 50]);
```

📖 **[View Complete Pagination Guide](https://github.com/jjuanrivvera/canvas-lms-kit/wiki/Pagination-Guide)** for advanced examples and patterns.

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

### ✅ Currently Implemented (45 APIs)

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
- ✅ **Announcements** - Course announcements extending DiscussionTopics
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
- ✅ **Brand Configs** - Theme variables and shared brand configurations
- ✅ **Gradebook History** - Grade change audit trail and submission version tracking
- ✅ **Course Reports** - Asynchronous report generation (grade exports, student data)
- ✅ **Developer Keys** - OAuth API key management for Canvas integrations
- ✅ **Login API** - User authentication credentials and login methods
- ✅ **Analytics** - Learning analytics data (account, course, user level)
- ✅ **Bookmarks** - User bookmark management for Canvas resources
- ✅ **MediaObjects** - Media files and captions/subtitles management
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

### Raw URL Support (Direct API Calls) 🆕

Make direct API calls to arbitrary Canvas URLs using the `Canvas` facade class:

```php
use CanvasLMS\Canvas;

// Follow pagination URLs returned by Canvas
$courses = Course::paginate();
while ($nextUrl = $courses->getNextUrl()) {
    $nextPage = Canvas::get($nextUrl);
    // Process next page data
}

// Call custom or undocumented endpoints
$analytics = Canvas::get('/api/v1/courses/123/analytics');
$customData = Canvas::post('/api/v1/custom/endpoint', [
    'key' => 'value'
]);

// Download files directly
$file = File::find(456);
$content = Canvas::get($file->url);
file_put_contents('download.pdf', $content);

// Process webhook callbacks
$webhookData = json_decode($request->getContent(), true);
$resource = Canvas::get($webhookData['resource_url']);

// All HTTP methods supported
$response = Canvas::get($url);        // GET request
$response = Canvas::post($url, $data); // POST request
$response = Canvas::put($url, $data);  // PUT request
$response = Canvas::delete($url);      // DELETE request
$response = Canvas::patch($url, $data); // PATCH request
$response = Canvas::request($url, 'HEAD'); // Any HTTP method
```

**Features:**
- ✅ Automatic authentication (API key or OAuth)
- ✅ Response parsing (JSON/non-JSON)
- ✅ Security validation (domain allowlisting, HTTPS enforcement)
- ✅ Supports absolute URLs and relative paths
- ✅ All middleware applied (rate limiting, retries, logging)

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
docker compose exec php composer test     # Run PHPUnit tests
docker compose exec php composer check    # Run all checks (CS, PHPStan, tests)
docker compose exec php composer cs       # Check PSR-12 coding standards
docker compose exec php composer cs-fix   # Fix coding standards automatically
docker compose exec php composer phpstan  # Run static analysis (level 6)

# Local development (requires PHP 8.1+)
composer test      # Run PHPUnit tests
composer check     # Run all checks
composer cs        # Check coding standards
composer cs-fix    # Fix coding standards automatically
composer phpstan   # Static analysis
```

### Code Quality Tools

- **PHP-CS-Fixer**: Automatically fixes code to comply with PSR-12 standards
- **PHPStan**: Static analysis at level 6 for maximum type safety
- **PHPUnit**: Comprehensive test suite with 2,300+ tests
- **Strict Types**: All files use `declare(strict_types=1)` for type safety

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