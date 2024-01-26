<?php

namespace CanvasLMS\Dto\Courses;

use DateTime;
use CanvasLMS\Dto\BaseDto;
use CanvasLMS\Interfaces\DTOInterface;

class CreateCourseDTO extends BaseDto implements DTOInterface
{
    public string $name = 'Unnamed Course';
    public string $courseCode = '';
    public ?\DateTime $startAt = null;
    public ?\DateTime $endAt = null;
    public string $license = 'private';
    public bool $isPublic = false;
    public bool $isPublicToAuthUsers = false;
    public bool $publicSyllabus = false;
    public bool $publicSyllabusToAuth = false;
    public ?string $publicDescription = null;
    public bool $allowStudentWikiEdits = false;
    public bool $allowWikiComments = false;
    public bool $allowStudentForumAttachments = false;
    public bool $openEnrollment = false;
    public bool $selfEnrollment = false;
    public bool $restrictEnrollmentsToCourseDates = false;
    public ?string $termId = null;
    public ?string $sisCourseId = null;
    public ?string $integrationId = null;
    public bool $hideFinalGrades = false;
    public bool $applyAssignmentGroupWeights = false;
    public string $timeZone = 'UTC';
    public bool $offer = false;
    public bool $enrollMe = false;
    public string $defaultView = 'syllabus';
    public ?string $syllabusBody = null;
    public ?int $gradingStandardId = null;
    public ?string $gradePassbackSetting = null;
    public ?string $courseFormat = null;
    public bool $enableSisReactivation = false;

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
