<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Courses;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;
use CanvasLMS\Utilities\Str;
use DateTime;

class CreateCourseDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The name of the course. If omitted, the course will be named “Unnamed Course.”
     *
     * @var string $name
     */
    public string $name = 'Unnamed Course';

    /**
     * The course code for the course.
     *
     * @var string $courseCode
     */
    public string $courseCode = '';

    /**
     * Course start date in ISO8601 format, e.g. 2011-01-01T01:00Z.
     * This value is ignored unless ‘restrict_enrollments_to_course_dates’ is set to true.
     *
     * @var DateTime|null $startAt
     */
    public ?DateTime $startAt = null;

    /**
     * Course end date in ISO8601 format, e.g. 2011-01-01T01:00Z.
     * This value is ignored unless ‘restrict_enrollments_to_course_dates’ is set to true.
     *
     * @var DateTime|null $endAt
     */
    public ?DateTime $endAt = null;

    /**
     * The name of the licensing. Should be one of the following abbreviations
     * (a descriptive name is included in parenthesis for reference):
     * - ‘private’ (Private Copyrighted)
     * - ‘cc_by_nc_nd’ (CC Attribution Non-Commercial No Derivatives)
     * - ‘cc_by_nc_sa’ (CC Attribution Non-Commercial Share Alike)
     * - ‘cc_by_nc’ (CC Attribution Non-Commercial)
     * - ‘cc_by_nd’ (CC Attribution No Derivatives)
     * - ‘cc_by_sa’ (CC Attribution Share Alike)
     * - ‘cc_by’ (CC Attribution)
     * - ‘public_domain’ (Public Domain).
     *
     * @var string $license
     */
    public string $license = 'private';

    /**
     * Set to true if course is public to both authenticated and unauthenticated users.
     *
     * @var bool $isPublic
     */
    public bool $isPublic = false;

    /**
     * Set to true if course is public only to authenticated users.
     *
     * @var bool $isPublicToAuthUsers
     */
    public bool $isPublicToAuthUsers = false;

    /**
     * Set to true to make the course syllabus public.
     *
     * @var bool $publicSyllabus
     */
    public bool $publicSyllabus = false;

    /**
     * Set to true to make the course syllabus public for authenticated users.
     *
     * @var bool $publicSyllabusToAuth
     */
    public bool $publicSyllabusToAuth = false;

    /**
     * A publicly visible description of the course.
     *
     * @var string|null $publicDescription
     */
    public ?string $publicDescription = null;

    /**
     * If true, students will be able to modify the course wiki.
     *
     * @var bool $allowStudentWikiEdits
     */
    public bool $allowStudentWikiEdits = false;

    /**
     * If true, course members will be able to comment on wiki pages.
     *
     * @var bool $allowWikiComments
     */
    public bool $allowWikiComments = false;

    /**
     * If true, students can attach files to forum posts.
     *
     * @var bool $allowStudentForumAttachments
     */
    public bool $allowStudentForumAttachments = false;

    /**
     * Set to true if the course is open enrollment.
     *
     * @var bool $openEnrollment
     */
    public bool $openEnrollment = false;

    /**
     * Set to true if the course is self enrollment.
     *
     * @var bool $selfEnrollment
     */
    public bool $selfEnrollment = false;

    /**
     * Set to true to restrict user enrollments to the start and end dates of the course.
     * This value must be set to true in order to specify a course start date and/or end date.
     *
     * @var bool $restrictEnrollmentsToCourseDates
     */
    public bool $restrictEnrollmentsToCourseDates = false;

    /**
     * The unique ID of the term to create to course in.
     *
     * @var int|null $termId
     */
    public ?int $termId = null;

    /**
     * The unique SIS identifier.
     *
     * @var string|null $sisCourseId
     */
    public ?string $sisCourseId = null;

    /**
     * The unique Integration identifier.
     *
     * @var string|null $integrationId
     */
    public ?string $integrationId = null;

    /**
     * If this option is set to true, the totals in student grades summary will be hidden.
     *
     * @var bool $hideFinalGrades
     */
    public bool $hideFinalGrades = false;

    /**
     * Set to true to weight final grade based on assignment groups percentages.
     *
     * @var bool $applyAssignmentGroupWeights
     */
    public bool $applyAssignmentGroupWeights = false;

    /**
     * The time zone for the course. Allowed time zones are IANA time zones
     * or friendlier Ruby on Rails time zones.
     *
     * @var string $timeZone
     */
    public string $timeZone = 'UTC';

    /**
     * If this option is set to true, the course will be available to students immediately.
     *
     * @var bool $offer
     */
    public bool $offer = false;

    /**
     * Set to true to enroll the current user as the teacher.
     *
     * @var bool $enrollMe
     */
    public bool $enrollMe = false;

    /**
     * The type of page that users will see when they first visit the course
     * - ‘feed’ Recent Activity Dashboard
     * - ‘modules’ Course Modules/Sections Page
     * - ‘assignments’ Course Assignments List
     * - ‘syllabus’ Course Syllabus Page
     * - other types may be added in the future
     * Allowed values: feed, wiki, modules, syllabus, assignments
     *
     * @var string $defaultView
     */
    public string $defaultView = 'syllabus';

    /**
     * The syllabus body for the course.
     *
     * @var string|null $syllabusBody
     */
    public ?string $syllabusBody = null;

    /**
     * The grading standard id to set for the course.
     * If no value is provided for this argument the current grading_standard will be un-set from this course.
     *
     * @var int|null $gradingStandardId
     */
    public ?int $gradingStandardId = null;

    /**
     * Optional. The grade_passback_setting for the course. Only ‘nightly_sync’, ‘disabled’, and ” are allowed.
     *
     * @var string|null $gradePassbackSetting
     */
    public ?string $gradePassbackSetting = null;

    /**
     * Optional. Specifies the format of the course. (Should be ‘on_campus’, ‘online’, or ‘blended’)
     *
     * @var string|null $courseFormat
     */
    public ?string $courseFormat = null;

    /**
     * When true, will first try to re-activate a deleted course with matching sis_course_id if possible.
     *
     * @var bool $enableSisReactivation
     */
    public bool $enableSisReactivation = false;

    /**
     * Default is false. When true, all grades in the course must be posted manually,
     * and will not be automatically posted. When false, all grades in the course will be automatically posted.
     *
     * @var bool $postManually
     */
    public bool $postManually = false;

    /**
     * Convert the DTO to an array for API requests
     *
     * @return mixed[]
     */
    public function toApiArray(): array
    {
        $properties = get_object_vars($this);

        $modifiedProperties = [];

        foreach ($properties as $key => &$value) {
            if ($value instanceof DateTime) {
                $value = $value->format('c');
            }

            if ($value === null || $value === '') {
                unset($properties[$key]);
                continue;
            }

            // Rename keys to this format course[{key}]
            $modifiedProperties[] = [
                'name' => 'course[' . Str::toSnakeCase($key) . ']',
                'contents' => $value,
            ];
        }

        return $modifiedProperties;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCourseCode(): string
    {
        return $this->courseCode;
    }

    /**
     * @param string $courseCode
     */
    public function setCourseCode(string $courseCode): void
    {
        $this->courseCode = $courseCode;
    }

    /**
     * @return DateTime|null
     */
    public function getStartAt(): ?DateTime
    {
        return $this->startAt;
    }

    /**
     * @param DateTime|null $startAt
     */
    public function setStartAt(?DateTime $startAt): void
    {
        $this->startAt = $startAt;
    }

    /**
     * @return DateTime|null
     */
    public function getEndAt(): ?DateTime
    {
        return $this->endAt;
    }

    /**
     * @param DateTime|null $endAt
     */
    public function setEndAt(?DateTime $endAt): void
    {
        $this->endAt = $endAt;
    }

    /**
     * @return string
     */
    public function getLicense(): string
    {
        return $this->license;
    }

    /**
     * @param string $license
     */
    public function setLicense(string $license): void
    {
        $this->license = $license;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * @param bool $isPublic
     */
    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    /**
     * @return bool
     */
    public function isPublicToAuthUsers(): bool
    {
        return $this->isPublicToAuthUsers;
    }

    /**
     * @param bool $isPublicToAuthUsers
     */
    public function setIsPublicToAuthUsers(bool $isPublicToAuthUsers): void
    {
        $this->isPublicToAuthUsers = $isPublicToAuthUsers;
    }

    /**
     * @return bool
     */
    public function isPublicSyllabus(): bool
    {
        return $this->publicSyllabus;
    }

    /**
     * @param bool $publicSyllabus
     */
    public function setPublicSyllabus(bool $publicSyllabus): void
    {
        $this->publicSyllabus = $publicSyllabus;
    }

    /**
     * @return bool
     */
    public function isPublicSyllabusToAuth(): bool
    {
        return $this->publicSyllabusToAuth;
    }

    /**
     * @param bool $publicSyllabusToAuth
     */
    public function setPublicSyllabusToAuth(bool $publicSyllabusToAuth): void
    {
        $this->publicSyllabusToAuth = $publicSyllabusToAuth;
    }

    /**
     * @return string|null
     */
    public function getPublicDescription(): ?string
    {
        return $this->publicDescription;
    }

    /**
     * @param string|null $publicDescription
     */
    public function setPublicDescription(?string $publicDescription): void
    {
        $this->publicDescription = $publicDescription;
    }

    /**
     * @return bool
     */
    public function isAllowStudentWikiEdits(): bool
    {
        return $this->allowStudentWikiEdits;
    }

    /**
     * @param bool $allowStudentWikiEdits
     */
    public function setAllowStudentWikiEdits(bool $allowStudentWikiEdits): void
    {
        $this->allowStudentWikiEdits = $allowStudentWikiEdits;
    }

    /**
     * @return bool
     */
    public function isAllowWikiComments(): bool
    {
        return $this->allowWikiComments;
    }

    /**
     * @param bool $allowWikiComments
     */
    public function setAllowWikiComments(bool $allowWikiComments): void
    {
        $this->allowWikiComments = $allowWikiComments;
    }

    /**
     * @return bool
     */
    public function isAllowStudentForumAttachments(): bool
    {
        return $this->allowStudentForumAttachments;
    }

    /**
     * @param bool $allowStudentForumAttachments
     */
    public function setAllowStudentForumAttachments(bool $allowStudentForumAttachments): void
    {
        $this->allowStudentForumAttachments = $allowStudentForumAttachments;
    }

    /**
     * @return bool
     */
    public function isOpenEnrollment(): bool
    {
        return $this->openEnrollment;
    }

    /**
     * @param bool $openEnrollment
     */
    public function setOpenEnrollment(bool $openEnrollment): void
    {
        $this->openEnrollment = $openEnrollment;
    }

    /**
     * @return bool
     */
    public function isSelfEnrollment(): bool
    {
        return $this->selfEnrollment;
    }

    /**
     * @param bool $selfEnrollment
     */
    public function setSelfEnrollment(bool $selfEnrollment): void
    {
        $this->selfEnrollment = $selfEnrollment;
    }

    /**
     * @return bool
     */
    public function isRestrictEnrollmentsToCourseDates(): bool
    {
        return $this->restrictEnrollmentsToCourseDates;
    }

    /**
     * @param bool $restrictEnrollmentsToCourseDates
     */
    public function setRestrictEnrollmentsToCourseDates(bool $restrictEnrollmentsToCourseDates): void
    {
        $this->restrictEnrollmentsToCourseDates = $restrictEnrollmentsToCourseDates;
    }

    /**
     * @return int|null
     */
    public function getTermId(): ?int
    {
        return $this->termId;
    }

    /**
     * @param int|null $termId
     */
    public function setTermId(?int $termId): void
    {
        $this->termId = $termId;
    }

    /**
     * @return string|null
     */
    public function getSisCourseId(): ?string
    {
        return $this->sisCourseId;
    }

    /**
     * @param string|null $sisCourseId
     */
    public function setSisCourseId(?string $sisCourseId): void
    {
        $this->sisCourseId = $sisCourseId;
    }

    /**
     * @return string|null
     */
    public function getIntegrationId(): ?string
    {
        return $this->integrationId;
    }

    /**
     * @param string|null $integrationId
     */
    public function setIntegrationId(?string $integrationId): void
    {
        $this->integrationId = $integrationId;
    }

    /**
     * @return bool
     */
    public function isHideFinalGrades(): bool
    {
        return $this->hideFinalGrades;
    }

    /**
     * @param bool $hideFinalGrades
     */
    public function setHideFinalGrades(bool $hideFinalGrades): void
    {
        $this->hideFinalGrades = $hideFinalGrades;
    }

    /**
     * @return bool
     */
    public function isApplyAssignmentGroupWeights(): bool
    {
        return $this->applyAssignmentGroupWeights;
    }

    /**
     * @param bool $applyAssignmentGroupWeights
     */
    public function setApplyAssignmentGroupWeights(bool $applyAssignmentGroupWeights): void
    {
        $this->applyAssignmentGroupWeights = $applyAssignmentGroupWeights;
    }

    /**
     * @return string
     */
    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    /**
     * @param string $timeZone
     */
    public function setTimeZone(string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @return bool
     */
    public function isOffer(): bool
    {
        return $this->offer;
    }

    /**
     * @param bool $offer
     */
    public function setOffer(bool $offer): void
    {
        $this->offer = $offer;
    }

    /**
     * @return bool
     */
    public function isEnrollMe(): bool
    {
        return $this->enrollMe;
    }

    /**
     * @param bool $enrollMe
     */
    public function setEnrollMe(bool $enrollMe): void
    {
        $this->enrollMe = $enrollMe;
    }

    /**
     * @return string
     */
    public function getDefaultView(): string
    {
        return $this->defaultView;
    }

    /**
     * @param string $defaultView
     */
    public function setDefaultView(string $defaultView): void
    {
        $this->defaultView = $defaultView;
    }

    /**
     * @return string|null
     */
    public function getSyllabusBody(): ?string
    {
        return $this->syllabusBody;
    }

    /**
     * @param string|null $syllabusBody
     */
    public function setSyllabusBody(?string $syllabusBody): void
    {
        $this->syllabusBody = $syllabusBody;
    }

    /**
     * @return int|null
     */
    public function getGradingStandardId(): ?int
    {
        return $this->gradingStandardId;
    }

    /**
     * @param int|null $gradingStandardId
     */
    public function setGradingStandardId(?int $gradingStandardId): void
    {
        $this->gradingStandardId = $gradingStandardId;
    }

    /**
     * @return string|null
     */
    public function getGradePassbackSetting(): ?string
    {
        return $this->gradePassbackSetting;
    }

    /**
     * @param string|null $gradePassbackSetting
     */
    public function setGradePassbackSetting(?string $gradePassbackSetting): void
    {
        $this->gradePassbackSetting = $gradePassbackSetting;
    }

    /**
     * @return string|null
     */
    public function getCourseFormat(): ?string
    {
        return $this->courseFormat;
    }

    /**
     * @param string|null $courseFormat
     */
    public function setCourseFormat(?string $courseFormat): void
    {
        $this->courseFormat = $courseFormat;
    }

    /**
     * @return bool
     */
    public function isEnableSisReactivation(): bool
    {
        return $this->enableSisReactivation;
    }

    /**
     * @param bool $enableSisReactivation
     */
    public function setEnableSisReactivation(bool $enableSisReactivation): void
    {
        $this->enableSisReactivation = $enableSisReactivation;
    }

    /**
     * @return bool
     */
    public function isPostManually(): bool
    {
        return $this->postManually;
    }

    /**
     * @param bool $postManually
     */
    public function setPostManually(bool $postManually): void
    {
        $this->postManually = $postManually;
    }
}
