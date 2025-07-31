# Changelog

All notable changes to Canvas LMS Kit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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