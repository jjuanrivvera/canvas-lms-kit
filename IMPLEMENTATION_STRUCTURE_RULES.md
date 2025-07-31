# Implementation Structure Rules

This document defines the rules for organizing classes and namespaces in the Canvas LMS SDK based on the official Canvas LMS API documentation.

## Directory Structure Rules

### 1. API Classes (Classes with Endpoints)

**Rule**: If a class has its own page in the Canvas LMS API documentation (found in `canvas-lms-docs/`), it must have its own namespace.

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

### 2. Read-Only Objects (No Endpoints)

**Rule**: Objects that are referenced in the official Canvas LMS documentation but do not have any endpoint usage (read-only objects) should be stored in the `src/Objects/` folder.

**Structure**:
```
src/Objects/
    └── {ObjectName}.php
tests/Objects/
    └── {ObjectName}Test.php
```

**Characteristics of Read-Only Objects**:
- No CRUD operations (no create, update, delete endpoints)
- Typically returned as part of other API responses
- May contain nested data structures
- Examples: Grade objects, RubricCriteria, QuizStatistics, etc.

## Implementation Checklist

When implementing a new resource:

1. **Check Canvas LMS Documentation**
   - Look for the resource in `canvas-lms-docs/`
   - Determine if it has its own documentation page
   - Check if it has CRUD endpoints

2. **Determine Classification**
   - **Has own page + endpoints** → API Class with own namespace
   - **Referenced but no endpoints** → Objects folder

3. **Create Directory Structure**
   - Follow the patterns above
   - Maintain consistency with existing implementations

4. **Namespace Convention**
   - API Classes: `CanvasLMS\Api\{ResourceName}`
   - DTOs: `CanvasLMS\Dto\{ResourceName}`
   - Objects: `CanvasLMS\Objects`

## API Design Principles

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

## Examples

### API Class Example (Has Endpoints)
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

### Read-Only Object Example (No Endpoints)
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