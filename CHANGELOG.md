# Changelog

All notable changes to Canvas LMS Kit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2025-01-17

### Added
- OAuth 2.0 authentication support (#44)
  - Full OAuth 2.0 implementation with client credentials flow
  - Automatic token management and refresh
  - Secure token storage and handling
  - Comprehensive middleware support for OAuth workflows
  - Backward compatible with existing API key authentication
- Conversations API for Canvas internal messaging system (#65)
  - **Conversation** class for managing user conversations and messages
  - User-scoped resource (all conversations belong to authenticated user)
  - Support for individual and group conversations
  - File attachment support via integration with File API
  - Media comment support for audio/video messages
  - Batch operations for bulk conversation management
  - Message threading and forwarding capabilities
  - **ConversationParticipant** object for participant data
  - **CreateConversationDTO** for creating new conversations with recipients
  - **UpdateConversationDTO** for updating conversation properties
  - **AddMessageDTO** for adding messages to existing conversations
  - **AddRecipientsDTO** for adding participants to group conversations
  - Support for complex recipient types (users, courses, groups)
  - Filtering by scope (unread, starred, archived, sent)
  - Convenience methods: `markAsRead()`, `markAsUnread()`, `star()`, `unstar()`, `archive()`
  - Static methods: `markAllAsRead()`, `getUnreadCount()`, `getRunningBatches()`
  - Comprehensive test coverage for all conversation operations

### Changed
- **IMPROVED**: Simplified pagination API to three clear, intuitive methods
  - `get()` - Fetch first page only (fast, memory efficient)
  - `all()` - Fetch all pages automatically (handles pagination transparently)
  - `paginate()` - Get results with pagination metadata (PaginationResult)
  - Old method names (`fetchAll`, `fetchAllPages`, `fetchAllPaginated`, `fetchPage`) still work via aliases for backward compatibility
  - Added `getEndpoint()` method to all API classes for consistency
  - Context-aware behavior preserved for Files (user context) and ExternalTools (account context)
  - Updated all tests and documentation to use new method names
  - No breaking changes - existing code continues to work with aliases
- **IMPROVED**: Standardized `save()` and `delete()` methods across all API classes to return `self` for fluent interface support (#99)
  - Enables method chaining: `$course->save()->enrollments()` 
  - Changed from returning `bool` to returning instance
  - Exceptions now thrown on errors instead of returning false
  - Affects 18 classes: Account, Assignment, Course, DiscussionTopic, Enrollment, ExternalTool, Group, GroupCategory, Module, ModuleItem, Page, Quiz, QuizSubmission, Section, Submission, SubmissionComment, Tab, User
  - Consistent with existing pattern in AppointmentGroup, CalendarEvent, Outcome, Rubric, RubricAssociation
  - No breaking changes as SDK is pre-release

## [1.1.0] - 2025-01-13

### Added
- Conferences API for web conferencing integration (#67)
  - **Conference** class for managing web conferences with BigBlueButton, Zoom, and other providers
  - Multi-context support for both Course and Group contexts
  - **ConferenceRecording** object for managing conference recordings
  - Special actions: `join()` method for joining conferences, `getRecordings()` for retrieving recordings
  - **CreateConferenceDTO** and **UpdateConferenceDTO** for handling complex provider settings
  - Provider-specific settings support through flexible array structures
  - Integration with Course class via `conferences()` and `createConference()` methods
  - Participant management and invitation capabilities
  - Support for long-running conferences and advanced settings
  - Comprehensive test coverage for all conference operations
- Feature Flags API for managing Canvas feature toggles (#68)
  - **FeatureFlag** class for managing feature states at Account/Course/User levels
  - Account-as-Default pattern implementation for multi-context support
  - Support for feature states (off, allowed, on) with inheritance hierarchy
  - Feature flag locking and hiding capabilities
  - Beta and development feature identification
  - Integration with Course and User classes via instance methods
  - **UpdateFeatureFlagDTO** for managing feature flag updates with validation
  - Convenience methods for enable/disable/allow operations
  - Context-specific feature flag management
- Outcomes API for learning objectives and competency tracking (#64)
  - **Outcome** class (`Api\Outcomes`) with Account-as-Default pattern for managing learning outcomes
  - **OutcomeGroup** class (`Api\OutcomeGroups`) for hierarchical organization of outcomes with global context support
  - **OutcomeResult** class (`Api\OutcomeResults`) for tracking individual student mastery (context-specific only)
  - **OutcomeImport** class (`Api\OutcomeImports`) for bulk importing outcomes from CSV files with async processing
  - Outcome rollups integrated into Course class (`outcomeRollups()`, `outcomeRollupsAggregate()`, `outcomeRollupsExportCSV()`)
  - Support for multiple calculation methods (decaying average, n_mastery, latest, highest, average)
  - Rating scales configuration with mastery points
  - Outcome alignment with assignments and rubrics
  - Bulk import/export capabilities for outcome standards
  - CSV template generation and validation for imports
  - Async import status tracking with progress monitoring
  - Error handling and reporting for failed imports
  - Vendor GUID support for external standard integration
  - Course instance methods for context-specific outcome operations and imports
  - Comprehensive DTOs for create/update operations with validation
  - Full support for competency-based education workflows
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
- Content Migrations API for course content import/export workflows (#61, #89)
  - **ContentMigration** class with multi-context support (Account/Course/Group/User)
  - **MigrationIssue** class for handling migration warnings, errors, and todos
  - **Migrator** read-only object for available migration systems
  - Support for various migration types: course copy, ZIP files, Common Cartridge, QTI, Moodle
  - Integration with Progress API for tracking async operations
  - File upload handling with pre-attachment support
  - Selective import functionality with copy parameters
  - Date shifting options for course content
  - Asset ID mapping for course migrations
  - Comprehensive DTOs for create/update operations with complex nested settings
  - Context-specific methods added to Course, Group, and User classes for content migrations (#89)
  - Full test coverage for all context-specific content migration methods (#89)

### Fixed
- Fixed hardcoded account ID in Course::create() method to use configured account ID from Config class (#84)
- Fixed hardcoded account ID in User::create() method to use configured account ID from Config class (#84)
- Both methods now properly use Config::getAccountId() which defaults to 1 when not explicitly configured (#84)
- Added tests to verify correct account ID usage in multi-tenant environments and default behavior (#84)
- Fixed MigrationIssue property update methods to use direct property assignment instead of populate() (#89)
- Improved file resource management in ContentMigration with proper try-finally blocks (#89)
- Added constants for magic numbers in ContentMigration polling logic (#89)

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
- Extracted multipart building logic to dedicated method in ContentMigration class for better code organization (#89)
- Removed deprecated static properties and methods from MigrationIssue class (#89)

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

[Unreleased]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/jjuanrivvera/canvas-lms-kit/releases/tag/v1.0.0