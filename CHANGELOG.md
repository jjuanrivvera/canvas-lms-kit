# Changelog

All notable changes to Canvas LMS Kit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.5.0] - 2025-09-09

### Added
- Course Reports API implementation (#124)
  - New `CourseReports` class for managing asynchronous course report generation
  - Support for all Canvas report types (grade export, student assignment data, etc.)
  - Three main operations: create reports, check status, get last report status
  - Course-context pattern with `setCourse()` and `checkCourse()` methods
  - Status checking helper methods: `isCompleted()`, `isRunning()`, `isFailed()`, `isReady()`
  - Progress tracking with `getProgress()` and human-readable status descriptions
  - Canvas API endpoints: POST/GET for report generation and status checking
  - Course class integration with convenience methods: `createReport()`, `getReport()`, `getLastReport()`
  - Full test coverage with 34 unit tests covering all functionality and edge cases
  - PSR-12 compliant code with PHPStan level 6 static analysis passing
- Developer Keys API implementation (#128)
  - New `DeveloperKey` class for managing Canvas API keys used for OAuth access
  - Full CRUD operations: create, read, update, delete with mixed endpoint routing
  - Support for both Canvas API keys and LTI 1.3 registrations
  - OAuth parameter handling: scopes, redirect URIs, security settings
  - Account-as-default context pattern with Config::getAccountId() integration
  - Mixed endpoint routing: account-scoped CREATE/LIST, direct ID UPDATE/DELETE
  - `CreateDeveloperKeyDTO` and `UpdateDeveloperKeyDTO` with fluent interfaces
  - Advanced array manipulation methods for URIs and scopes management
  - Status checking methods: isActive(), isLti(), isTestClusterOnly(), etc.
  - Helper methods: getRedirectUrisString(), getScopesString()
  - Instance methods: save() and remove() for existing objects
  - Support for inherited keys from Site Admin
  - Comprehensive test coverage with 60 tests for API and DTO functionality

- Login API implementation (#121)
  - New `Login` class for managing user login credentials and authentication methods
  - Multi-context support: Account, Course, and User-scoped login management
  - Full CRUD operations with support for trusted accounts and existing users
  - `CreateLoginDTO` and `UpdateLoginDTO` with comprehensive validation
  - Support for multiple authentication providers (Canvas, LDAP, CAS, SAML, etc.)
  - User identification via SIS ID, integration ID, or username
  - Unique ID management for external authentication systems
  - Integration with User class via `logins()` and login management methods
  - Account and Course integration for administrative login operations
  - Declared user type support for user creation workflows
  - Comprehensive test coverage for all contexts and operations

- Analytics API implementation (#123)
  - New `Analytics` class for accessing Canvas learning analytics data
  - Support for Account/Department level analytics (activity, grades, statistics)
  - Support for Course level analytics (activity, assignments, student summaries)
  - Support for User-in-Course level analytics (activity, assignments, communication)
  - Support for current and completed term filtering
  - Support for subaccount statistics breakdown
  - Integration with Course class via `analytics()`, `assignmentAnalytics()`, `studentSummaries()`, and `studentAnalytics()` methods
  - Integration with User class via `courseAnalytics()` method
  - Returns raw arrays matching Canvas API JSON responses for flexibility
  - Comprehensive test coverage for all 20+ analytics endpoints

- Bookmarks API implementation (#120)
  - New `Bookmark` class for managing user bookmarks in Canvas
  - Support for bookmarking various Canvas resources (courses, groups, users, etc.)
  - Full CRUD operations: create, read, update, delete
  - `CreateBookmarkDTO` and `UpdateBookmarkDTO` for data validation
  - Position management for custom bookmark ordering
  - Metadata support via JSON data field
  - User-specific context - always operates on current user (`/users/self/bookmarks`)
  - Comprehensive test coverage for all operations

- Brand Configs API implementation (#122)
  - New `BrandConfig` class for retrieving brand variables (colors, fonts, logos)
  - New `SharedBrandConfig` class for managing shared theme configurations
  - Support for creating, updating, and deleting shared brand configs
  - `CreateSharedBrandConfigDTO` and `UpdateSharedBrandConfigDTO` for data validation
  - Account integration via `getBrandVariables()` and shared config methods
  - Note: Canvas API has limitations - no list/fetch endpoints for shared configs
  - Comprehensive test coverage for all operations

- MediaObjects API implementation (#47)
  - New `MediaObject` class for managing media objects and tracks in Canvas
  - Support for media objects in global, course, and group contexts
  - Media attachments endpoints for parallel attachment-based operations
  - Media tracks management (captions/subtitles) with SRT and WEBVTT format support
  - `UpdateMediaObjectDTO` for updating media object titles
  - `UpdateMediaTracksDTO` for managing media tracks with validation
  - `MediaTrack` and `MediaSource` data objects for structured data handling
  - Course and Group integration via `mediaObjects()` and `mediaAttachments()` methods
  - Support for 12 Canvas API endpoints covering all media operations
  - Comprehensive test coverage for API operations and DTOs

- Announcements API implementation (#41)
  - New `Announcement` class extending `DiscussionTopic` with announcement-specific features
  - Automatic filtering for announcements only (adds `only_announcements=true`)
  - Global announcements endpoint support for cross-course announcements
  - Scheduled announcements with `scheduleFor()` and `postImmediately()` methods
  - Section-targeted announcements support
  - Comment locking functionality for one-way broadcasts
  - `CreateAnnouncementDTO` and `UpdateAnnouncementDTO` with announcement defaults
  - Course integration via `$course->announcements()` relationship method
  - Comprehensive test coverage for all announcement operations

### Changed
- Property naming convention standardization (#117)
  - Converted all snake_case properties to camelCase across 4 API classes (23 properties total)
  - Section.php: `$passback_status` → `$passbackStatus`
  - Tab.php: `$html_url` → `$htmlUrl` with constructor fix for proper inheritance
  - FeatureFlag.php: 6 properties converted (displayName, appliesTo, enableAt, featureFlag, rootOptIn, releaseNotesUrl)
  - Conference.php: 10 properties converted with enhanced DateTime handling in constructor
  - ConferenceRecording.php: 5 properties converted with snake_case to camelCase conversion
  - Fixed DTO class naming: UploadFileDto → UploadFileDTO, CreateSharedBrandConfigDto → CreateSharedBrandConfigDTO, UpdateSharedBrandConfigDto → UpdateSharedBrandConfigDTO
  - Updated corresponding test files to use new camelCase property names
  - Maintains full backward compatibility through AbstractBaseApi automatic conversion
  - Achieves 100% camelCase property naming compliance across entire SDK

## [1.4.1] - 2025-01-28

### Changed
- Removed unused instance masquerading code from AbstractBaseApi
  - Cleaned up 120 lines of partially implemented code that was never integrated
  - Global masquerading via Config::asUser() remains fully functional
  - Simplified codebase by removing unnecessary complexity

## [1.4.0] - 2025-01-28

### Added
- Canvas Masquerading (Act As User) Support (#91)
  - New Config methods: `asUser()`, `stopMasquerading()`, `getMasqueradeUserId()`, `isMasquerading()`
  - Automatic `as_user_id` parameter injection in all API requests when masquerading is active
  - Multi-context support: Different masquerade users per context/tenant
  - Support for both regular and raw API requests
  - Comprehensive test coverage for masquerading scenarios
  - Security features: Permission validation by Canvas, logging support for audit trails
  - Use cases: Admin operations, support workflows, permission testing, batch user operations
- Raw URL support for direct Canvas API calls (#92)
  - New `Canvas` facade class for making raw API calls to arbitrary Canvas URLs
  - Added `rawRequest()` method to HttpClientInterface and HttpClient
  - Support for both absolute Canvas URLs and relative paths
  - Automatic authentication header inclusion
  - Response parsing based on Content-Type (JSON/non-JSON)
  - Security validation to prevent SSRF attacks
  - Domain allowlisting with subdomain support
  - Useful for:
    - Following pagination URLs returned by Canvas
    - Calling custom or undocumented endpoints
    - Processing webhook callbacks with embedded URLs
    - Following URLs in API responses (e.g., file downloads)
    - Accessing beta or experimental Canvas features
## [1.3.1] - 2025-01-20

### Fixed
- Critical OAuth authentication issues (#112)
  - Fixed OAuth2 token endpoints incorrectly getting /api/v1/ prefix added to URLs
  - Added proper URL handling in HttpClient to detect OAuth2 endpoints and bypass API prefix
  - Only OAuth2 token endpoints (/login/oauth2/*) now bypass the API prefix
  - Regular API endpoints including /login/session_token continue to use /api/v1/ prefix

### Added
- User::fetchSelf() method for retrieving complete current user data (#112)
  - New method that actually fetches full user data from Canvas API
  - Returns fully populated User instance with all properties (id, name, email, etc.)
  - Complements existing User::self() which returns empty instance for "self" pattern methods
  - Works with both API key and OAuth authentication modes

### Changed
- Simplified OAuth class URL handling (#112)
  - Removed unnecessary URL manipulation in OAuth::exchangeCode() and OAuth::refreshToken()
  - OAuth methods now rely on HttpClient's improved URL handling
  - Cleaner code with consistent URL patterns across all OAuth endpoints

## [1.3.0] - 2025-01-19

### Fixed
- OAuth token exchange authentication bypass issue (#110)
  - Fixed OAuth::exchangeCode() and OAuth::refreshToken() methods failing due to authentication chicken-and-egg problem
  - Added `skipAuth` option to HttpClient for OAuth endpoints that should be unauthenticated
  - OAuth token exchange and refresh now work without requiring existing API key or OAuth token
  - OAuth token revocation and session creation continue to require authentication as expected
  - Added integration tests to verify authentication bypass behavior

### Added
- Gradebook History API for grade change audit trail (#66)
  - **GradebookHistory** class for tracking all grade changes with timestamps
  - Course-scoped resource requiring course context
  - Four main endpoints for comprehensive grade history access:
    - `fetchDays()` - List days with grading activity
    - `fetchDay()` - Get graders and assignments for a specific day
    - `fetchSubmissions()` - Get detailed submission versions
    - `fetchFeed()` - Paginated feed of all submission versions
  - Full pagination support via `fetchFeedPaginated()` method
  - Data objects for structured responses:
    - **GradebookHistoryGrader** - Grader information with assignments
    - **GradebookHistoryDay** - Days with grading activity
    - **SubmissionVersion** - Individual submission version with grade changes
    - **SubmissionHistory** - Complete history of submission versions
  - Integration with Course class via `gradebookHistory()` method
  - Support for filtering by assignment, user, and date range
  - Grade change tracking with previous/current/new values
  - Essential for academic integrity and compliance requirements
- Comprehensive logging system activation and improvements (#107)
  - PSR-3 compatible logger configuration via `Config::setLogger()`
  - Context-aware logging support for multi-tenant applications
  - Activity logging trait for standardized API operation logging
  - OAuth token operation logging with sensitive data sanitization
  - Pagination metrics and performance tracking
  - File upload progress logging (3-step process)
  - Automatic sensitive data sanitization in logs
  - Integration examples with Monolog, Symfony, and other PSR-3 loggers

### Changed
- Replaced `trigger_error()` and `error_log()` calls with proper PSR-3 logger usage
- AbstractBaseApi now uses configured logger instead of hardcoded NullLogger
- Enhanced error handling with contextual logging throughout the SDK

### Security
- Automatic sanitization of sensitive fields (passwords, tokens, API keys) in log output
- OAuth token masking in log entries to prevent credential exposure

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

[Unreleased]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.5.0...HEAD
[1.5.0]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.4.1...v1.5.0
[1.4.1]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.4.0...v1.4.1
[1.4.0]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.3.1...v1.4.0
[1.3.1]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.3.0...v1.3.1
[1.3.0]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/jjuanrivvera/canvas-lms-kit/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/jjuanrivvera/canvas-lms-kit/releases/tag/v1.0.0