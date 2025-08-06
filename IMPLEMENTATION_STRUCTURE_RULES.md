# Implementation Structure Rules

This document defines the rules for organizing classes and namespaces in the Canvas LMS SDK based on the official Canvas LMS API documentation.

## Directory Structure Rules

### 1. API Classes (Classes with Endpoints)

**Rule**: If a class has its own page in the Canvas LMS API documentation (found in `canvas-lms-docs/`) AND has any endpoints (GET, POST, PUT, DELETE), it must have its own namespace in the API folder.

**Note**: Read-only API resources (only GET endpoints) still belong in the API folder but may not extend AbstractBaseApi.

**Structure**:
```
src/Api/{ResourceName}/
    └── {ResourceName}.php
src/Dto/{ResourceName}/
    ├── Create{ResourceName}DTO.php
    └── Update{ResourceName}DTO.php
tests/Api/{ResourceName}/
    └── {ResourceName}Test.php
tests/Dto/{ResourceName}/
    ├── Create{ResourceName}DTOTest.php
    └── Update{ResourceName}DTOTest.php
```

**Examples**:
- `QuizSubmissions` has its own page → `src/Api/QuizSubmissions/QuizSubmission.php`
- `Modules` has its own page → `src/Api/Modules/Module.php`
- `Assignments` has its own page → `src/Api/Assignments/Assignment.php`

### 2. Data Objects (No Endpoints)

**Rule**: Objects that are referenced in the official Canvas LMS documentation but do not have ANY endpoints at all should be stored in the `src/Objects/` folder. These are pure data structures returned as part of other API responses.

**Structure**:
```
src/Objects/
    └── {ObjectName}.php
tests/Objects/
    └── {ObjectName}Test.php
```

**Characteristics of Data Objects**:
- No endpoints at all (not even GET)
- Only exist as data structures returned by other API calls
- Cannot be fetched directly from the API
- Examples: OutcomeLink, OutcomeRating, RubricCriteria, Grade objects

## Implementation Checklist

When implementing a new resource:

1. **Check Canvas LMS Documentation**
   - Look for the resource in `canvas-lms-docs/`
   - Determine if it has its own documentation page
   - Check if it has CRUD endpoints

2. **Determine Classification**
   - **Has own page + ANY endpoints (even just GET)** → API Class with own namespace
   - **Referenced but NO endpoints at all** → Objects folder
   - **Read-only APIs (GET only)** → API Class (may not extend AbstractBaseApi)

3. **Create Directory Structure**
   - Follow the patterns above
   - Maintain consistency with existing implementations

4. **Namespace Convention**
   - API Classes: `CanvasLMS\Api\{ResourceName}`
   - DTOs: `CanvasLMS\Dto\{ResourceName}`
   - Objects: `CanvasLMS\Objects`

## API Design Principles

### Account-as-Default Convention for Multi-Context Resources

**Rule**: Resources that can exist in multiple contexts (Account, Course, User) should default to Account context when accessed directly through the API class. Course-specific and other context-specific access should be provided through instance methods on the respective context classes.

**Implementation Pattern**:
```php
// Default: Account context
$rubrics = Rubric::fetchAll();  // Uses Config::getAccountId()
$tools = ExternalTool::fetchAll();  // Uses Config::getAccountId()

// Course context via Course instance
$course = Course::find(123);
$rubrics = $course->getRubrics();
$tools = $course->getExternalTools();

// User context via User instance (where applicable)
$user = User::find(456);
$groups = $user->getGroups();
```

**Benefits**:
- Consistency across all multi-context resources
- Respects Canvas LMS hierarchy (Account as parent context)
- Course class becomes the gateway for course-specific operations
- Maintains clean separation of concerns

**Examples of Multi-Context Resources**:
- Groups (Account/Course/User)
- Rubrics (Account/Course)  
- External Tools (Account/Course)
- Calendar Events (Account/Course/User)
- Outcomes (Account/Course)

### Array-Based User Interface

**Rule**: Public API methods should accept arrays as input parameters to provide a simpler, more intuitive interface for end users. DTOs should be used internally for type safety and data validation.

**Implementation Pattern**:
```php
// API methods should accept both arrays and DTOs
public static function create(array|CreateResourceDTO $data): self
{
    if (is_array($data)) {
        $data = new CreateResourceDTO($data);
    }
    // Continue with DTO processing
}
```

**Benefits**:
- Simpler API for end users (no need to import or instantiate DTO classes)
- Maintains type safety internally through DTO validation
- Follows modern PHP conventions
- Backward compatible with direct DTO usage

**Examples**:
```php
// User-friendly array syntax (recommended)
$course = Course::create([
    'name' => 'Introduction to PHP',
    'course_code' => 'PHP101'
]);

// Direct DTO usage (still supported)
$dto = new CreateCourseDTO(['name' => 'Introduction to PHP']);
$course = Course::create($dto);
```

### Read-Only API Classes

**Rule**: API resources that only have GET endpoints (no CREATE, UPDATE, DELETE) are still API classes but:
- Should be placed in `/src/Api/{ResourceName}/`
- May not extend `AbstractBaseApi` since they don't need full CRUD operations
- Should implement their own static methods for fetching data
- Examples: OutcomeResult (context-specific fetching only)

## Examples

### Standard API Class Example (Full CRUD)
```php
// src/Api/QuizSubmissions/QuizSubmission.php
namespace CanvasLMS\Api\QuizSubmissions;

class QuizSubmission extends AbstractBaseApi
{
    // CRUD operations accept arrays
    public static function create(array|CreateQuizSubmissionDTO $data): self
    {
        if (is_array($data)) {
            $data = new CreateQuizSubmissionDTO($data);
        }
        // Implementation continues with DTO
    }
}
```

### Read-Only API Class Example (GET endpoints only)
```php
// src/Api/OutcomeResult/OutcomeResult.php
namespace CanvasLMS\Api\Outcomes\OutcomeResult;

class OutcomeResult  // Note: Does not extend AbstractBaseApi
{
    // Properties for data
    public ?array $results = null;
    public ?array $linked = null;
    
    // Static methods for fetching
    public static function fetchByCourse(int $courseId, array $params = []): OutcomeResult
    {
        // Fetch from API endpoint
    }
}
```

### Data Object Example (No Endpoints)
```php
// src/Objects/RubricCriterion.php
namespace CanvasLMS\Objects;

class RubricCriterion
{
    // Properties only, no API operations
    public ?int $id = null;
    public ?string $description = null;
    public ?float $points = null;
}
```

## Validation Questions

Before implementing, ask:
1. Does this resource have its own page in `canvas-lms-docs/`?
2. Does it have any endpoints (GET, POST, PUT, DELETE)?
3. Is it only returned as part of other API responses?

## Migration Notes

When refactoring existing code to follow these rules:
1. Check all imports in affected files
2. Update test files to match new structure
3. Run PHPStan and tests to verify changes
4. Update any documentation references