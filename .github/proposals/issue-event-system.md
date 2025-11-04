# Feature: Implement Event System for lifecycle hooks and webhooks

**Labels:** `enhancement`

## Description

Implement an event system that allows users to hook into SDK lifecycle events and easily handle Canvas webhooks. This would enable observability, logging, custom business logic, and seamless webhook integration.

## Motivation

Currently, there's no standardized way to:
- React to SDK events (e.g., when a course is created, updated, or deleted)
- Log operations for audit trails
- Trigger custom business logic on Canvas operations
- Handle Canvas webhooks in a structured way
- Integrate with external systems (notifications, analytics, etc.)

## Proposed Event System

### 1. Lifecycle Events

```php
use CanvasLMS\Events\Event;

// Listen to course creation
Event::listen('course.created', function(Course $course) {
    // Send notification
    Notification::send("New course: {$course->name}");

    // Log to analytics
    Analytics::track('course_created', [
        'course_id' => $course->id,
        'name' => $course->name
    ]);
});

// Listen to any course event
Event::listen('course.*', function(string $event, Course $course) {
    AuditLog::record($event, $course);
});

// Listen to enrollment changes
Event::listen('enrollment.updated', function(Enrollment $enrollment) {
    if ($enrollment->enrollmentState === 'active') {
        // Welcome email, setup user, etc.
        onboardNewStudent($enrollment);
    }
});
```

### 2. Webhook Handling

```php
use CanvasLMS\Webhooks\WebhookHandler;

// Register webhook handlers
WebhookHandler::register('submission.created', function($payload) {
    $submission = Submission::fromWebhook($payload);

    // Trigger plagiarism check
    PlagiarismChecker::check($submission);

    // Notify instructor
    notifyInstructor($submission);
});

// In your webhook endpoint
Route::post('/webhooks/canvas', function(Request $request) {
    return WebhookHandler::handle($request);
});
```

### 3. Observer Pattern

```php
use CanvasLMS\Events\Observer;

class CourseObserver extends Observer
{
    public function creating(Course $course): void
    {
        // Before course is created
        $course->code = $course->code ?? $this->generateCourseCode();
    }

    public function created(Course $course): void
    {
        // After course is created
        $this->setupDefaultModules($course);
        $this->notifyAdmins($course);
    }

    public function updating(Course $course): void
    {
        // Before course is updated
        $this->validateChanges($course);
    }

    public function updated(Course $course): void
    {
        // After course is updated
        Cache::forget("course.{$course->id}");
    }

    public function deleted(Course $course): void
    {
        // After course is deleted
        $this->cleanupRelatedData($course);
    }
}

// Register observer
Course::observe(CourseObserver::class);
```

## Event Types

### Lifecycle Events

- `{resource}.creating` - Before create API call
- `{resource}.created` - After successful create
- `{resource}.updating` - Before update API call
- `{resource}.updated` - After successful update
- `{resource}.deleting` - Before delete API call
- `{resource}.deleted` - After successful delete
- `{resource}.retrieved` - After fetching resource(s)

### System Events

- `api.request.sending` - Before any API request
- `api.request.sent` - After API request completes
- `api.error` - When API error occurs
- `oauth.token.refreshed` - After OAuth token refresh
- `pagination.page.loaded` - After loading a page

### Canvas Webhook Events

- `submission.created`
- `submission.updated`
- `grade.changed`
- `enrollment.created`
- `enrollment.updated`
- `discussion_entry.created`
- `conversation.created`
- And all other Canvas webhook events...

## Implementation Tasks

- [ ] Create `Event` facade class
- [ ] Implement event dispatcher (PSR-14 compatible?)
- [ ] Add `listen()`, `dispatch()`, `forget()` methods
- [ ] Create `Observer` base class
- [ ] Add observer support to `AbstractBaseApi`
- [ ] Implement event dispatching in CRUD operations
- [ ] Create `WebhookHandler` class
- [ ] Add webhook signature verification
- [ ] Implement webhook routing/dispatching
- [ ] Add `fromWebhook()` factory methods to models
- [ ] Support for async event handlers (queues)
- [ ] Event serialization for queue support
- [ ] Write comprehensive tests
- [ ] Document all events and their payloads
- [ ] Provide example webhook implementations

## Example Implementation

```php
namespace CanvasLMS\Events;

class Event
{
    private static array $listeners = [];

    public static function listen(string $event, callable $handler): void
    {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }
        self::$listeners[$event][] = $handler;
    }

    public static function dispatch(string $event, mixed ...$args): void
    {
        // Exact match
        if (isset(self::$listeners[$event])) {
            foreach (self::$listeners[$event] as $handler) {
                $handler(...$args);
            }
        }

        // Wildcard match (e.g., 'course.*')
        foreach (self::$listeners as $pattern => $handlers) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -2);
                if (str_starts_with($event, $prefix)) {
                    foreach ($handlers as $handler) {
                        $handler($event, ...$args);
                    }
                }
            }
        }
    }
}
```

## Integration Example

```php
// In AbstractBaseApi or specific API classes
public static function create(array $data): self
{
    Event::dispatch('course.creating', $data);

    $course = // ... API call

    Event::dispatch('course.created', $course);

    return $course;
}
```

## Use Cases

1. **Audit Logging**: Track all Canvas operations
2. **Notifications**: Send alerts on important events
3. **Data Sync**: Sync Canvas data to external systems
4. **Business Logic**: Trigger workflows based on Canvas events
5. **Analytics**: Track usage and generate reports
6. **Webhooks**: Handle Canvas webhook events elegantly
7. **Testing**: Mock events in tests
8. **Debugging**: Log events for troubleshooting

## Technical Considerations

1. **PSR-14 Compatibility**: Consider implementing PSR-14 event dispatcher
2. **Performance**: Ensure event dispatching doesn't slow down operations
3. **Error Handling**: Failed event handlers shouldn't break the main flow
4. **Async Support**: Allow queuing events for background processing
5. **Memory**: Be mindful of memory when storing many listeners
6. **Security**: Verify webhook signatures from Canvas

## Configuration Example

```php
// config/canvas.php
return [
    'events' => [
        'enabled' => true,
        'async' => env('CANVAS_EVENTS_ASYNC', false),
        'queue' => env('CANVAS_EVENTS_QUEUE', 'default'),
    ],
    'webhooks' => [
        'secret' => env('CANVAS_WEBHOOK_SECRET'),
        'verify_signatures' => env('CANVAS_WEBHOOK_VERIFY', true),
    ],
];
```

## Related Issues

This complements other features like Query Builder and improves overall SDK extensibility.

## Priority

**Medium** - This is a significant enhancement that enables many advanced use cases, though the SDK is functional without it.

## Timeline

Estimated 3-4 weeks for full implementation including:
- Core event system (1 week)
- Observer pattern integration (1 week)
- Webhook handling (1 week)
- Tests and documentation (1 week)
