<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Assignments;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for creating assignments in Canvas LMS
 *
 * This DTO handles the creation of new assignments with all the necessary
 * fields supported by the Canvas API.
 *
 * @package CanvasLMS\Dto\Assignments
 */
class CreateAssignmentDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'assignment';

    /**
     * Assignment name (required)
     */
    public ?string $name = null;

    /**
     * Assignment description (HTML content)
     */
    public ?string $description = null;

    /**
     * Assignment due date (ISO 8601 format)
     */
    public ?string $dueAt = null;

    /**
     * Assignment lock date (ISO 8601 format)
     */
    public ?string $lockAt = null;

    /**
     * Assignment unlock date (ISO 8601 format)
     */
    public ?string $unlockAt = null;

    /**
     * Maximum points possible for this assignment
     */
    public ?float $pointsPossible = null;

    /**
     * Grading type (points, percent, pass_fail, etc.)
     */
    public ?string $gradingType = null;

    /**
     * Allowed submission types
     * @var array<string>|null
     */
    public ?array $submissionTypes = null;

    /**
     * Allowed file extensions for submissions
     * @var array<string>|null
     */
    public ?array $allowedExtensions = null;

    /**
     * Maximum number of submission attempts allowed
     */
    public ?int $allowedAttempts = null;

    /**
     * Whether the assignment is published
     */
    public ?bool $published = null;

    /**
     * Assignment group ID
     */
    public ?int $assignmentGroupId = null;

    /**
     * Assignment position in the group
     */
    public ?int $position = null;

    /**
     * Whether assignment is only visible to users with overrides
     */
    public ?bool $onlyVisibleToOverrides = null;

    /**
     * Whether peer reviews are enabled
     */
    public ?bool $peerReviews = null;

    /**
     * Whether automatic peer review assignment is enabled
     */
    public ?bool $automaticPeerReviews = null;

    /**
     * Date when automatic peer review assignment should occur
     */
    public ?string $peerReviewsAssignAt = null;

    /**
     * Number of peer reviews to assign per student
     */
    public ?int $peerReviewCount = null;

    /**
     * Whether anonymous peer reviews are enabled
     */
    public ?bool $anonymousPeerReviews = null;

    /**
     * Whether grading is anonymous
     */
    public ?bool $anonymousGrading = null;

    /**
     * Whether students can see grader names
     */
    public ?bool $gradersAnonymousToGraders = null;

    /**
     * Whether graders can see student names
     */
    public ?bool $gradersAnonymousToStudents = null;

    /**
     * Whether anonymous instructor annotations are enabled
     */
    public ?bool $anonymousInstructorAnnotations = null;

    /**
     * Whether moderated grading is enabled
     */
    public ?bool $moderatedGrading = null;

    /**
     * Number of graders for moderated grading
     */
    public ?int $graderCount = null;

    /**
     * Grader comments visible to graders
     */
    public ?bool $graderCommentsVisibleToGraders = null;

    /**
     * Final grader ID for moderated grading
     */
    public ?int $finalGraderId = null;

    /**
     * Whether final grader can view other grader identities
     */
    public ?bool $graderNamesVisibleToFinalGrader = null;

    /**
     * Group category ID for group assignments
     */
    public ?int $groupCategoryId = null;

    /**
     * Whether to grade students individually in group assignments
     */
    public ?bool $gradeGroupStudentsIndividually = null;

    /**
     * External tool tag attributes
     * @var array<string, mixed>|null
     */
    public ?array $externalToolTagAttributes = null;

    /**
     * Assignment integration data
     * @var array<string, mixed>|null
     */
    public ?array $integrationData = null;

    /**
     * Assignment integration ID
     */
    public ?string $integrationId = null;

    /**
     * Whether to omit assignment from final grade
     */
    public ?bool $omitFromFinalGrade = null;

    /**
     * Whether to hide assignment in gradebook
     */
    public ?bool $hideInGradebook = null;

    /**
     * Get assignment name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set assignment name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get assignment description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set assignment description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get due date
     */
    public function getDueAt(): ?string
    {
        return $this->dueAt;
    }

    /**
     * Set due date
     */
    public function setDueAt(?string $dueAt): void
    {
        $this->dueAt = $dueAt;
    }

    /**
     * Get lock date
     */
    public function getLockAt(): ?string
    {
        return $this->lockAt;
    }

    /**
     * Set lock date
     */
    public function setLockAt(?string $lockAt): void
    {
        $this->lockAt = $lockAt;
    }

    /**
     * Get unlock date
     */
    public function getUnlockAt(): ?string
    {
        return $this->unlockAt;
    }

    /**
     * Set unlock date
     */
    public function setUnlockAt(?string $unlockAt): void
    {
        $this->unlockAt = $unlockAt;
    }

    /**
     * Get points possible
     */
    public function getPointsPossible(): ?float
    {
        return $this->pointsPossible;
    }

    /**
     * Set points possible
     */
    public function setPointsPossible(?float $pointsPossible): void
    {
        $this->pointsPossible = $pointsPossible;
    }

    /**
     * Get grading type
     */
    public function getGradingType(): ?string
    {
        return $this->gradingType;
    }

    /**
     * Set grading type
     */
    public function setGradingType(?string $gradingType): void
    {
        $this->gradingType = $gradingType;
    }

    /**
     * Get submission types
     * @return array<string>|null
     */
    public function getSubmissionTypes(): ?array
    {
        return $this->submissionTypes;
    }

    /**
     * Set submission types
     * @param array<string>|null $submissionTypes
     */
    public function setSubmissionTypes(?array $submissionTypes): void
    {
        $this->submissionTypes = $submissionTypes;
    }

    /**
     * Get allowed extensions
     * @return array<string>|null
     */
    public function getAllowedExtensions(): ?array
    {
        return $this->allowedExtensions;
    }

    /**
     * Set allowed extensions
     * @param array<string>|null $allowedExtensions
     */
    public function setAllowedExtensions(?array $allowedExtensions): void
    {
        $this->allowedExtensions = $allowedExtensions;
    }

    /**
     * Get allowed attempts
     */
    public function getAllowedAttempts(): ?int
    {
        return $this->allowedAttempts;
    }

    /**
     * Set allowed attempts
     */
    public function setAllowedAttempts(?int $allowedAttempts): void
    {
        $this->allowedAttempts = $allowedAttempts;
    }

    /**
     * Get published status
     */
    public function getPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * Set published status
     */
    public function setPublished(?bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get assignment group ID
     */
    public function getAssignmentGroupId(): ?int
    {
        return $this->assignmentGroupId;
    }

    /**
     * Set assignment group ID
     */
    public function setAssignmentGroupId(?int $assignmentGroupId): void
    {
        $this->assignmentGroupId = $assignmentGroupId;
    }

    /**
     * Get position
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Set position
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    /**
     * Get only visible to overrides status
     */
    public function getOnlyVisibleToOverrides(): ?bool
    {
        return $this->onlyVisibleToOverrides;
    }

    /**
     * Set only visible to overrides status
     */
    public function setOnlyVisibleToOverrides(?bool $onlyVisibleToOverrides): void
    {
        $this->onlyVisibleToOverrides = $onlyVisibleToOverrides;
    }

    /**
     * Get peer reviews status
     */
    public function getPeerReviews(): ?bool
    {
        return $this->peerReviews;
    }

    /**
     * Set peer reviews status
     */
    public function setPeerReviews(?bool $peerReviews): void
    {
        $this->peerReviews = $peerReviews;
    }

    /**
     * Get automatic peer reviews status
     */
    public function getAutomaticPeerReviews(): ?bool
    {
        return $this->automaticPeerReviews;
    }

    /**
     * Set automatic peer reviews status
     */
    public function setAutomaticPeerReviews(?bool $automaticPeerReviews): void
    {
        $this->automaticPeerReviews = $automaticPeerReviews;
    }

    /**
     * Get peer reviews assign at date
     */
    public function getPeerReviewsAssignAt(): ?string
    {
        return $this->peerReviewsAssignAt;
    }

    /**
     * Set peer reviews assign at date
     */
    public function setPeerReviewsAssignAt(?string $peerReviewsAssignAt): void
    {
        $this->peerReviewsAssignAt = $peerReviewsAssignAt;
    }

    /**
     * Get peer review count
     */
    public function getPeerReviewCount(): ?int
    {
        return $this->peerReviewCount;
    }

    /**
     * Set peer review count
     */
    public function setPeerReviewCount(?int $peerReviewCount): void
    {
        $this->peerReviewCount = $peerReviewCount;
    }

    /**
     * Get anonymous peer reviews status
     */
    public function getAnonymousPeerReviews(): ?bool
    {
        return $this->anonymousPeerReviews;
    }

    /**
     * Set anonymous peer reviews status
     */
    public function setAnonymousPeerReviews(?bool $anonymousPeerReviews): void
    {
        $this->anonymousPeerReviews = $anonymousPeerReviews;
    }

    /**
     * Get anonymous grading status
     */
    public function getAnonymousGrading(): ?bool
    {
        return $this->anonymousGrading;
    }

    /**
     * Set anonymous grading status
     */
    public function setAnonymousGrading(?bool $anonymousGrading): void
    {
        $this->anonymousGrading = $anonymousGrading;
    }

    /**
     * Get graders anonymous to graders status
     */
    public function getGradersAnonymousToGraders(): ?bool
    {
        return $this->gradersAnonymousToGraders;
    }

    /**
     * Set graders anonymous to graders status
     */
    public function setGradersAnonymousToGraders(?bool $gradersAnonymousToGraders): void
    {
        $this->gradersAnonymousToGraders = $gradersAnonymousToGraders;
    }

    /**
     * Get graders anonymous to students status
     */
    public function getGradersAnonymousToStudents(): ?bool
    {
        return $this->gradersAnonymousToStudents;
    }

    /**
     * Set graders anonymous to students status
     */
    public function setGradersAnonymousToStudents(?bool $gradersAnonymousToStudents): void
    {
        $this->gradersAnonymousToStudents = $gradersAnonymousToStudents;
    }

    /**
     * Get anonymous instructor annotations status
     */
    public function getAnonymousInstructorAnnotations(): ?bool
    {
        return $this->anonymousInstructorAnnotations;
    }

    /**
     * Set anonymous instructor annotations status
     */
    public function setAnonymousInstructorAnnotations(?bool $anonymousInstructorAnnotations): void
    {
        $this->anonymousInstructorAnnotations = $anonymousInstructorAnnotations;
    }

    /**
     * Get moderated grading status
     */
    public function getModeratedGrading(): ?bool
    {
        return $this->moderatedGrading;
    }

    /**
     * Set moderated grading status
     */
    public function setModeratedGrading(?bool $moderatedGrading): void
    {
        $this->moderatedGrading = $moderatedGrading;
    }

    /**
     * Get grader count
     */
    public function getGraderCount(): ?int
    {
        return $this->graderCount;
    }

    /**
     * Set grader count
     */
    public function setGraderCount(?int $graderCount): void
    {
        $this->graderCount = $graderCount;
    }

    /**
     * Get grader comments visible to graders status
     */
    public function getGraderCommentsVisibleToGraders(): ?bool
    {
        return $this->graderCommentsVisibleToGraders;
    }

    /**
     * Set grader comments visible to graders status
     */
    public function setGraderCommentsVisibleToGraders(?bool $graderCommentsVisibleToGraders): void
    {
        $this->graderCommentsVisibleToGraders = $graderCommentsVisibleToGraders;
    }

    /**
     * Get final grader ID
     */
    public function getFinalGraderId(): ?int
    {
        return $this->finalGraderId;
    }

    /**
     * Set final grader ID
     */
    public function setFinalGraderId(?int $finalGraderId): void
    {
        $this->finalGraderId = $finalGraderId;
    }

    /**
     * Get grader names visible to final grader status
     */
    public function getGraderNamesVisibleToFinalGrader(): ?bool
    {
        return $this->graderNamesVisibleToFinalGrader;
    }

    /**
     * Set grader names visible to final grader status
     */
    public function setGraderNamesVisibleToFinalGrader(?bool $graderNamesVisibleToFinalGrader): void
    {
        $this->graderNamesVisibleToFinalGrader = $graderNamesVisibleToFinalGrader;
    }

    /**
     * Get group category ID
     */
    public function getGroupCategoryId(): ?int
    {
        return $this->groupCategoryId;
    }

    /**
     * Set group category ID
     */
    public function setGroupCategoryId(?int $groupCategoryId): void
    {
        $this->groupCategoryId = $groupCategoryId;
    }

    /**
     * Get grade group students individually status
     */
    public function getGradeGroupStudentsIndividually(): ?bool
    {
        return $this->gradeGroupStudentsIndividually;
    }

    /**
     * Set grade group students individually status
     */
    public function setGradeGroupStudentsIndividually(?bool $gradeGroupStudentsIndividually): void
    {
        $this->gradeGroupStudentsIndividually = $gradeGroupStudentsIndividually;
    }

    /**
     * Get external tool tag attributes
     * @return array<string, mixed>|null
     */
    public function getExternalToolTagAttributes(): ?array
    {
        return $this->externalToolTagAttributes;
    }

    /**
     * Set external tool tag attributes
     * @param array<string, mixed>|null $externalToolTagAttributes
     */
    public function setExternalToolTagAttributes(?array $externalToolTagAttributes): void
    {
        $this->externalToolTagAttributes = $externalToolTagAttributes;
    }

    /**
     * Get integration data
     * @return array<string, mixed>|null
     */
    public function getIntegrationData(): ?array
    {
        return $this->integrationData;
    }

    /**
     * Set integration data
     * @param array<string, mixed>|null $integrationData
     */
    public function setIntegrationData(?array $integrationData): void
    {
        $this->integrationData = $integrationData;
    }

    /**
     * Get integration ID
     */
    public function getIntegrationId(): ?string
    {
        return $this->integrationId;
    }

    /**
     * Set integration ID
     */
    public function setIntegrationId(?string $integrationId): void
    {
        $this->integrationId = $integrationId;
    }

    /**
     * Get omit from final grade status
     */
    public function getOmitFromFinalGrade(): ?bool
    {
        return $this->omitFromFinalGrade;
    }

    /**
     * Set omit from final grade status
     */
    public function setOmitFromFinalGrade(?bool $omitFromFinalGrade): void
    {
        $this->omitFromFinalGrade = $omitFromFinalGrade;
    }

    /**
     * Get hide in gradebook status
     */
    public function getHideInGradebook(): ?bool
    {
        return $this->hideInGradebook;
    }

    /**
     * Set hide in gradebook status
     */
    public function setHideInGradebook(?bool $hideInGradebook): void
    {
        $this->hideInGradebook = $hideInGradebook;
    }
}
