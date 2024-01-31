<?php

namespace CanvasLMS\Dto\Courses;

use DateTime;
use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

class UpdateCourseDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * @var int|null The unique ID of the account to move the course to.
     */
    public ?int $accountId = null;

    /**
     * @var string|null The name of the course. If omitted, the course will be named “Unnamed Course.”
     */
    public ?string $name = null;

    /**
     * @var string|null The course code for the course.
     */
    public ?string $courseCode = null;

    /**
     * @var DateTime|null Course start date in ISO8601 format, e.g. 2011-01-01T01:00Z.
     * This value is ignored unless ‘restrict_enrollments_to_course_dates’ is set to true,
     * or the course is already published.
     */
    public ?DateTime $startAt = null;

    /**
     * @var DateTime|null Course end date in ISO8601 format, e.g. 2011-01-01T01:00Z.
     * This value is ignored unless ‘restrict_enrollments_to_course_dates’ is set to true.
     */
    public ?DateTime $endAt = null;

    /**
     * @var string|null The name of the licensing.
     * Should be one of the following abbreviations:
     * - ‘private’ (Private Copyrighted)
     * - ‘cc_by_nc_nd’ (CC Attribution Non-Commercial No Derivatives)
     * - ‘cc_by_nc_sa’ (CC Attribution Non-Commercial Share Alike)
     * - ‘cc_by_nc’ (CC Attribution Non-Commercial)
     * - ‘cc_by_nd’ (CC Attribution No Derivatives)
     * - ‘cc_by_sa’ (CC Attribution Share Alike)
     * - ‘cc_by’ (CC Attribution)
     * - ‘public_domain’ (Public Domain).
     */
    public ?string $license = null;

    /**
     * @var bool|null Set to true if course is public to both authenticated and unauthenticated users.
     */
    public ?bool $isPublic = null;

    /**
     * @var bool|null Set to true if course is public only to authenticated users.
     */
    public ?bool $isPublicToAuthUsers = null;

    /**
     * @var bool|null Set to true to make the course syllabus public.
     */
    public ?bool $publicSyllabus = null;

    /**
     * @var bool|null Set to true to make the course syllabus public for authenticated users.
     */
    public ?bool $publicSyllabusToAuth = null;

    /**
     * @var string|null A publicly visible description of the course.
     */
    public ?string $publicDescription = null;

    /**
     * @var bool|null If true, students will be able to modify the course wiki.
     */
    public ?bool $allowStudentWikiEdits = null;

    /**
     * @var bool|null If true, course members will be able to comment on wiki pages.
     */
    public ?bool $allowWikiComments = null;

    /**
     * @var bool|null If true, students can attach files to forum posts.
     */
    public ?bool $allowStudentForumAttachments = null;

    /**
     * @var bool|null Set to true if the course is open enrollment.
     */
    public ?bool $openEnrollment = null;

    /**
     * @var bool|null Set to true if the course is self enrollment.
     */
    public ?bool $selfEnrollment = null;

    /**
     * @var bool|null Set to true to restrict user enrollments to the start and end dates of the course.
     * Setting this value to false will remove the course end date (if it exists),
     * as well as the course start date (if the course is unpublished).
     */
    public ?bool $restrictEnrollmentsToCourseDates = null;

    /**
     * @var int|null The unique ID of the term to create to course in.
     */
    public ?int $termId = null;

    /**
     * @var string|null The unique SIS identifier.
     */
    public ?string $sisCourseId = null;

    /**
     * @var string|null The unique Integration identifier.
     */
    public ?string $integrationId = null;

    /**
     * @var bool|null If this option is set to true, the totals in student grades summary will be hidden.
     */
    public ?bool $hideFinalGrades = null;

    /**
     * @var string|null The time zone for the course.
     * Allowed time zones are IANA time zones or friendlier Ruby on Rails time zones.
     */
    public ?string $timeZone = null;

    /**
     * @var bool|null Set to true to weight final grade based on assignment groups percentages.
     */
    public ?bool $applyAssignmentGroupWeights = null;

    /**
     * @var int|null Set the storage quota for the course, in megabytes.
     * The caller must have the “Manage storage quotas” account permission.
     */
    public ?int $storageQuotaMb = null;

    /**
     * @var bool|null If this option is set to true, the course will be available to students immediately.
     */
    public ?bool $offer = null;

    /**
     * @var string|null The action to take on each course.
     * Allowed values: claim, offer, conclude, delete, undelete
     */
    public ?string $event = null;

    /**
     * @var string The type of page that users will see when they first visit the course.
     * Allowed values: feed, wiki, modules, syllabus, assignments
     */
    public string $defaultView = 'syllabus';

    /**
     * @var string|null The syllabus body for the course.
     */
    public ?string $syllabusBody = null;

    /**
     * @var bool|null Indicates whether the Course Summary (consisting of the course’s assignments and calendar events)
     * is displayed on the syllabus page. Defaults to true.
     */
    public ?bool $syllabusCourseSummary = null;

    /**
     * @var int|null The grading standard id to set for the course.
     * If no value is provided for this argument the current grading_standard will be un-set from this course.
     */
    public ?int $gradingStandardId = null;

    /**
     * @var string|null The grade_passback_setting for the course. Only ‘nightly_sync’ and ” are allowed.
     */
    public ?string $gradePassbackSetting = null;

    /**
     * @var string|null Specifies the format of the course. (Should be either ‘on_campus’ or ‘online’)
     */
    public ?string $courseFormat = null;

    /**
     * @var int|null This is a file ID corresponding to an image file in the course that will be used
     * as the course image. This will clear the course’s image_url setting if set.
     * If you attempt to provide image_url and image_id in a request it will fail.
     */
    public ?int $imageId = null;

    /**
     * @var string|null This is a URL to an image to be used as the course image.
     * This will clear the course’s image_id setting if set.
     * If you attempt to provide image_url and image_id in a request it will fail.
     */
    public ?string $imageUrl = null;

    /**
     * @var bool|null If this option is set to true, the course image url and course image ID are both set to nil.
     */
    public ?bool $removeImage = null;

    /**
     * @var bool|null If this option is set to true,
     * the course banner image url and course banner image ID are both set to nil.
     */
    public ?bool $removeBannerImage = null;

    /**
     * @var bool|null Sets the course as a blueprint course.
     */
    public ?bool $blueprint = null;

    /**
     * @var bool|null When enabled, the blueprint_restrictions parameter
     * will be ignored in favor of the blueprint_restrictions_by_object_type parameter.
     */
    public ?bool $useBlueprintRestrictionsByObjectType = null;

    /**
     * @var mixed[]|null Allows setting multiple Blueprint Restriction to
     * apply to blueprint course objects of the matching type when restricted.
     * The possible object types are “assignment”, “attachment”, “discussion_topic”, “quiz” and “wiki_page”.
     */
    public ?array $blueprintRestrictionsByObjectType = null;

    /**
     * @var bool|null Sets the course as a homeroom course.
     * The setting takes effect only when the course is associated with a Canvas for Elementary-enabled account.
     */
    public ?bool $homeroomCourse = null;

    /**
     * @var string|null Syncs enrollments from the homeroom that is set in homeroom_course_id.
     * The setting only takes effect when the course is associated with a Canvas for
     * Elementary-enabled account and sync_enrollments_from_homeroom is enabled.
     */
    public ?string $syncEnrollmentsFromHomeroom = null;

    /**
     * @var string|null Sets the Homeroom Course id to be used with sync_enrollments_from_homeroom.
     * The setting only takes effect when the course is associated with a
     * Canvas for Elementary-enabled account and sync_enrollments_from_homeroom is enabled.
     */
    public ?string $homeroomCourseId = null;

    /**
     * @var bool|null Enable or disable the course as a template that can be selected by an account.
     */
    public ?bool $template = null;

    /**
     * @var string|null Sets a color in hex code format to be associated with the course.
     * The setting takes effect only when the course is associated with a Canvas for Elementary-enabled account.
     */
    public ?string $courseColor = null;

    /**
     * @var string|null Set a friendly name for the course.
     * If this is provided and the course is associated with a Canvas for Elementary account,
     * it will be shown instead of the course name.
     * This setting takes priority over course nicknames defined by individual users.
     */
    public ?string $friendlyName = null;

    /**
     * @var bool|null Enable or disable Course Pacing for the course.
     * This setting only has an effect when the Course Pacing feature flag is enabled for the sub-account.
     * Otherwise, Course Pacing are always disabled.
     *
     * Note: Course Pacing is in active development.
     */
    public ?bool $enableCoursePaces = null;

    /**
     * @var bool|null Enable or disable individual learning paths for students based on assessment.
     */
    public ?bool $conditionalRelease = null;

    /**
     * @var bool|null Default is true. If false, any fields containing “sticky” changes will not be updated.
     * See SIS CSV Format documentation for information on which fields can have SIS stickiness.
     */
    public ?bool $overrideSisStickiness = null;

    /**
     * Convert the DTO to an array for API requests
     * @return mixed[]
     */
    public function toApiArray(): array
    {
        $properties = get_object_vars($this);

        $modifiedProperties = [];

        foreach ($properties as $key => &$value) {
            if ($value instanceof DateTime) {
                $value = $value->format('c'); // Convert DateTime to ISO 8601 string
            }

            if (empty($value)) {
                unset($properties[$key]);
                continue;
            }

            // Rename keys to this format course[{key}]
            $modifiedProperties[] = [
                "name" => 'course[' . str_to_snake_case($key) . ']',
                "contents" => $value
            ];
        }

        return $modifiedProperties;
    }
}
