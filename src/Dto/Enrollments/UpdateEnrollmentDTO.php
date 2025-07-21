<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Enrollments;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

class UpdateEnrollmentDTO extends AbstractBaseDto implements DTOInterface
{
    protected string $apiPropertyName = 'enrollment';

    // Updatable enrollment fields
    public ?string $enrollmentState = null;
    public ?string $courseSectionId = null;
    public ?string $roleId = null;
    public ?bool $limitPrivilegesToCourseSection = null;

    // Date constraints
    public ?\DateTime $startAt = null;
    public ?\DateTime $endAt = null;

    // Notification preference for updates
    public ?bool $notify = null;

    /**
     * Get the enrollment state
     */
    public function getEnrollmentState(): ?string
    {
        return $this->enrollmentState;
    }

    /**
     * Set the enrollment state
     */
    public function setEnrollmentState(?string $enrollmentState): void
    {
        $this->enrollmentState = $enrollmentState;
    }

    /**
     * Get the course section ID
     */
    public function getCourseSectionId(): ?string
    {
        return $this->courseSectionId;
    }

    /**
     * Set the course section ID
     */
    public function setCourseSectionId(?string $courseSectionId): void
    {
        $this->courseSectionId = $courseSectionId;
    }

    /**
     * Get the role ID
     */
    public function getRoleId(): ?string
    {
        return $this->roleId;
    }

    /**
     * Set the role ID
     */
    public function setRoleId(?string $roleId): void
    {
        $this->roleId = $roleId;
    }

    /**
     * Get limit privileges to course section flag
     */
    public function isLimitPrivilegesToCourseSection(): ?bool
    {
        return $this->limitPrivilegesToCourseSection;
    }

    /**
     * Set limit privileges to course section flag
     */
    public function setLimitPrivilegesToCourseSection(?bool $limitPrivilegesToCourseSection): void
    {
        $this->limitPrivilegesToCourseSection = $limitPrivilegesToCourseSection;
    }

    /**
     * Get start date
     */
    public function getStartAt(): ?\DateTime
    {
        return $this->startAt;
    }

    /**
     * Set start date
     */
    public function setStartAt(?\DateTime $startAt): void
    {
        $this->startAt = $startAt;
    }

    /**
     * Get end date
     */
    public function getEndAt(): ?\DateTime
    {
        return $this->endAt;
    }

    /**
     * Set end date
     */
    public function setEndAt(?\DateTime $endAt): void
    {
        $this->endAt = $endAt;
    }

    /**
     * Get notification preference
     */
    public function isNotify(): ?bool
    {
        return $this->notify;
    }

    /**
     * Set notification preference
     */
    public function setNotify(?bool $notify): void
    {
        $this->notify = $notify;
    }
}
