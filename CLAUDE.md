# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Development Commands
**All PHP/Composer commands must be run through Docker:**
- `docker compose exec php composer install` - Install dependencies
- `docker compose exec php composer check` - Run all checks (coding standards, static analysis, tests)
- `docker compose exec php composer test` - Run PHPUnit tests with testdox output
- `docker compose exec php composer cs` - Check PSR-12 coding standards
- `docker compose exec php composer cs-fix` - Fix PSR-12 coding standard violations automatically
- `docker compose exec php composer phpstan` - Run PHPStan static analysis (level 8)

### Running Single Tests
```bash
docker compose exec php ./vendor/bin/phpunit tests/Api/Courses/CourseTest.php
docker compose exec php ./vendor/bin/phpunit --filter testMethodName
```

## Architecture Overview

This is a PHP SDK for Canvas LMS API following these patterns:

### Directory Structure Rules
**IMPORTANT**: Follow the implementation structure rules defined in `IMPLEMENTATION_STRUCTURE_RULES.md`:
- Resources with their own Canvas API documentation page get their own namespace
- Read-only objects without endpoints go in `src/Objects/` folder
- Always check `canvas-lms-docs/` to determine proper placement

### Active Record Pattern
API classes (Course, Module, User) act as Active Records:
- Static methods: `create()`, `find()`, `get()`, `update()`
- Instance methods: `save()`, `delete()`
- Example: `$course = Course::find(123)` or `$course->save()`

### Context Management Convention (IMPORTANT)
**Account-as-Default**: Resources that exist in multiple contexts (Account/Course/User) ALWAYS default to Account context when called directly:

```php
// ✅ CORRECT - Direct API calls use Account context
$groups = Group::get();              // Uses Config::getAccountId()
$rubrics = Rubric::get();            // Uses Config::getAccountId()
$migrations = ContentMigration::get(); // Uses Config::getAccountId()
$events = CalendarEvent::get();      // Uses Config::getAccountId()
$tools = ExternalTool::get();        // Uses Config::getAccountId()
$files = File::get();                // Uses current user context (exception)

// ✅ CORRECT - Context-specific access via instance methods
$course = Course::find(123);
$courseGroups = $course->groups();              // Course context
$courseMigrations = $course->contentMigrations(); // Course context
$courseRubrics = $course->rubrics();            // Course context
$courseTools = $course->externalTools();        // Course context
$courseEvents = $course->calendarEvents();      // Course context
$courseFiles = $course->files();                // Course context

$user = User::find(456);
$userGroups = $user->groups();                    // User context

// ✅ CORRECT - Direct context-specific access when needed
$courseEvents = CalendarEvent::fetchByContext('course', 123);
$userFiles = File::fetchByContext('users', 456);

// ❌ NEVER DO THIS - No context parameters in direct calls
$groups = Group::get($courseId);  // WRONG - not supported
$rubrics = Rubric::get($courseId); // WRONG - not supported
```

**Multi-Context Resources**:
- Groups (Account/Course/User)
- Content Migrations (Account/Course/Group/User)
- Rubrics (Account/Course)
- External Tools (Account/Course)
- Calendar Events (Account/Course/User/Group)
- Files (User/Course/Group/Folder) - Note: No Account context support

**Special Cases**:
- **Files API**: Defaults to current user context (`/users/self/files`) as Canvas doesn't support Account-level files
- **Calendar Events**: Uses `context_codes` parameter format (e.g., `account_1`, `course_123`)

**Benefits**: Consistency, respects Canvas hierarchy, clean separation of concerns, no ambiguity

### DTO Pattern
Separate DTOs handle API request formatting:
- `CreateCourseDTO`, `UpdateCourseDTO` for each entity
- Transform data to Canvas API multipart format
- Base class `AbstractBaseDto` handles common transformations

### Configuration
Global configuration via static `Config` class:
```php
Config::setApiKey('your-api-key');
Config::setBaseUrl('https://canvas.example.com/api/v1');
Config::setAccountId(1);
```

### HTTP Client
- Uses Guzzle HTTP client with interface abstraction
- Bearer token authentication
- Custom exceptions for API errors
- PSR-3 logger support

### Key Classes Structure
```
src/
├── Api/
│   ├── AbstractBaseApi.php    # Base class with common CRUD operations
│   ├── Courses/Course.php      # Canvas course operations
│   ├── Modules/Module.php      # Canvas module operations
│   └── Users/User.php          # Canvas user operations
├── Dto/                        # Data Transfer Objects for API requests
├── Http/HttpClient.php         # HTTP client implementation
└── Config.php                  # Global configuration management
```

## Testing Guidelines

- Unit tests use PHPUnit 10.5+
- Mock HTTP client for API tests using `$this->createMock(HttpClientInterface::class)`
- Test files mirror source structure in `tests/` directory
- Run PHPStan at level 8 before committing

## Code Standards

- PSR-12 coding standard enforced
- PHP 8.1+ features used (typed properties, union types)
- All public methods have PHPDoc comments
- Properties use getter/setter pattern with type declarations

## Documentation Structure

### Archived Implementation Plans
Implementation plans and progress files for completed issues are archived in `docs/archive/implementation-plans/`:

**Completed Issues:**
- **Issue #8**: Assignments API - Full CRUD operations with DTO support
- **Issue #10**: ModuleItem API - Dual dependency pattern (Course + Module)
- **Issue #11**: Tabs API - Course navigation management
- **Issue #13**: Pagination Support - RFC 5988 compliant Link header parsing
- **Issue #14**: File Uploads - Canvas 3-step upload process
- **Issue #18**: GitHub Actions Update - CI/CD pipeline maintenance
- **Issue #22**: Configuration Management - Multi-tenant context support
- **Issue #35**: Quizzes API - Complete quiz management with publishing workflow

These archived files demonstrate the documentation-driven development approach and comprehensive planning methodology used throughout the project.

### Strategic Planning
- Active development tracked via [GitHub Issues](https://github.com/jjuanrivvera/canvas-lms-kit/issues)

## Git Rules and Commit Guidelines

### Critical Git Safety Rules
**NEVER run `git stash --include-untracked`** - This command will permanently remove untracked files including CLAUDE.md and other documentation files that are essential for project operation. Use `git stash` (without --include-untracked) or `git stash push <specific-files>` instead.

**Safe stash alternatives:**
- `git stash` - Stash only tracked files
- `git stash push <file1> <file2>` - Stash specific files
- `git add <files> && git stash` - Stage files first, then stash

### Staging Files Selectively
- **Only stage files related to the current feature/fix** - use `git add <specific-files>` instead of `git add .`
- **Review changes before staging** - use `git status` and `git diff` to verify only relevant changes are included
- **Exclude unrelated modifications** - development artifacts, temporary files, or changes from other features
- **Keep commits focused** - each commit should represent a single logical change

### Commit Message Requirements
- **NEVER mention collaboration with Claude** in commit messages, PR descriptions, or code comments
- **NEVER include AI-generated signatures** like "Generated with Claude Code" or "Co-Authored-By: Claude"
- Use conventional commit format: `feat:`, `fix:`, `refactor:`, etc.
- Write clear, descriptive commit messages that explain the changes
- Focus on what the code does, not how it was created

### File Management Rules
**The following files must NEVER be committed:**
- Any temporary documentation files created during development
- Any files containing AI collaboration references

### Commit Content Guidelines
- Commit only production-ready code and documentation
- Exclude development artifacts, temporary files, and AI-related documentation
- Include proper documentation for new features in appropriate locations
- Ensure commit messages are professional and focus on business value

### Example Commit Messages
✅ **Good:**
```
feat: Add user registration form with password validation
fix: Resolve authentication timeout in SSOController
refactor: Improve database connection handling
```

❌ **Bad:**
```
feat: Add registration form (generated with Claude)
fix: Authentication issue - Claude helped debug this
refactor: DB connection (Co-Authored-By: Claude)
```