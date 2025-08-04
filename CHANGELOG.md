# Changelog

All notable changes to Canvas LMS Kit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Complete Groups API implementation with student collaboration features (#63)
  - **Group** class enhanced with pagination support, activity streams, permissions, and membership management
  - **GroupCategory** class for organizing groups within courses with bulk member assignment
  - **GroupMembership** class for managing group membership, moderator status, and invitations
  - Added relationship methods to Course and User classes for group-related operations
  - Full support for student group collaboration workflows and self-signup groups
  - Comprehensive test coverage for all group-related functionality
- Support for current user endpoints using Canvas "self" pattern (#87)
  - Added `User::self()` static method to get instance for current authenticated user
  - Implemented "self" support for Canvas API endpoints that actually support it:
    - `getActivityStream()` - Get current user's activity stream
    - `getTodo()` - Get current user's todo items
    - `getProfile()` - Get current user's profile
    - `groups()` - Get current user's groups (special handling for /users/self/groups)
  - Other User methods require numeric user ID and throw exception when ID not set
  - Added comprehensive tests for self pattern functionality
  - No breaking changes - existing code continues to work unchanged

### Fixed
- Fixed hardcoded account ID in Course::create() method to use configured account ID from Config class (#84)
- Fixed hardcoded account ID in User::create() method to use configured account ID from Config class (#84)
- Both methods now properly use Config::getAccountId() which defaults to 1 when not explicitly configured (#84)
- Added tests to verify correct account ID usage in multi-tenant environments and default behavior (#84)

### Changed
- Enhanced User relationship methods to use pagination for groups listing (#63)
- Refactored Rubric API classes to follow SDK conventions for context handling (#80)
  - Rubric class now uses `setCourse()` pattern like other API classes
  - Removed context parameters from all Rubric methods
  - Account-scoped rubric operations moved to Account class (`getRubrics()`, `createRubric()`, etc.)
  - RubricAssessment simplified to accept rubric_association_id directly
  - RubricAssociation no longer accepts courseId parameters
  - All classes now follow consistent context pattern used throughout the SDK
  - Fixed RubricAssessment::rubric() and RubricAssociation::rubric() methods to properly use setCourse() pattern
- Updated Rubrics API classes (Rubric, RubricAssessment, RubricAssociation) to support array-based interface for consistency with the rest of the SDK (#78)
  - `create()` and `update()` methods now accept both arrays and DTOs as input
  - Added comprehensive tests for array input support

## [1.0.1] - 2025-08-01

### Fixed
- Fixed DateTime conversion issue in DTOs that was causing Module save() to fail when unlockAt was a string value
- Fixed README badges for CI workflow and license information

## [1.0.0] - 2025-01-31

### 🎉 Initial Production Release

Canvas LMS Kit is now production-ready with 90% Canvas API coverage, rate limiting, and comprehensive middleware support!

### Added

#### Core APIs (21 Implementations)
- **Course Management**: Courses, Modules, Module Items, Sections, Tabs, Pages
- **User & Enrollment**: Users, Enrollments, Admin/Account management
- **Assessment & Grading**: Assignments, Quizzes, Quiz Submissions, Submissions, Submission Comments, Rubrics
- **Communication**: Discussion Topics (Announcements and Groups coming in v1.1)
- **Tools & Integration**: Files, External Tools, Module Assignment Overrides, Calendar Events, Appointment Groups, Progress

#### Production Features
- **Rate Limiting** (#31) - Automatic Canvas API quota management (3000 req/hour)
- **HTTP Middleware** (#25) - Extensible request/response handling pipeline
- **Retry Logic** - Automatic retry with exponential backoff for failed requests
- **Multi-Tenant Support** (#22) - Manage multiple Canvas instances with isolated contexts
- **Pagination** (#13) - RFC 5988 compliant Link header parsing with automatic page fetching
- **File Uploads** (#14) - Canvas 3-step upload process implementation

#### Developer Experience
- **Array-based API** - Simple array syntax for all API operations
- **Active Record Pattern** - Intuitive `Course::find()`, `$course->save()` interface
- **Type Safety** - Full PHP 8.1+ type declarations
- **PSR Standards** - PSR-12 coding standards, PSR-3 logging support
- **Comprehensive Testing** - 95%+ code coverage with 1000+ tests

### Security
- Input validation for all API methods
- Secure file upload handling with path sanitization
- API key protection and secure storage
- Parameter whitelisting in update operations

### Documentation
- Complete API reference documentation
- Extensive usage examples
- Architecture overview and design patterns
- Contributing guidelines
- Wiki with implementation guides

[Unreleased]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/jjuanrivvera/canvas-lms-kit/releases/tag/v1.0.0