<?php

namespace CanvasLMS\Dto\Courses;

use CanvasLMS\Dto\BaseDto;

class UpdateCourseDTO extends BaseDto
{
    public ?string $name = null;
    public ?string $courseCode = null;
    public ?\DateTime $startAt = null;
    public ?\DateTime $endAt = null;
    public ?string $license = null;
    public ?bool $isPublic = null;
    public ?bool $isPublicToAuthUsers = null;
    public ?bool $publicSyllabus = null;
    public ?bool $publicSyllabusToAuth = null;
    public ?string $publicDescription = null;
    public ?bool $allowStudentWikiEdits = null;
    public ?bool $allowWikiComments = null;
    public ?bool $allowStudentForumAttachments = null;
    public ?bool $openEnrollment = null;
    public ?bool $selfEnrollment = null;
    public ?bool $restrictEnrollmentsToCourseDates = null;
    public ?string $termId = null;
    public ?string $sisCourseId = null;
    public ?string $integrationId = null;
    public ?bool $hideFinalGrades = null;
    public ?bool $applyAssignmentGroupWeights = null;
    public ?string $timeZone = null;
    public ?bool $offer = null;
    public string $defaultView = 'syllabus';
    public ?string $syllabusBody = null;
    public ?int $gradingStandardId = null;
    public ?string $gradePassbackSetting = null;
    public ?string $courseFormat = null;
    public ?bool $enableSisReactivation = null;
}
