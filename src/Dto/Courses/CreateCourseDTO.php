<?php

namespace CanvasLMS\Dto\Courses;

use CanvasLMS\Dto\BaseDto;

class CreateCourseDTO extends BaseDto
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
}