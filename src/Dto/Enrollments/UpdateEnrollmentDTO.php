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
    public function getStartAt(): ?string
    {
        return $this->startAt?->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Set start date
     */
    public function setStartAt(\DateTime|string|null $startAt): void
    {
        if (is_string($startAt)) {
            $this->startAt = new \DateTime($startAt);
        } else {
            $this->startAt = $startAt;
        }
    }

    /**
     * Get end date
     */
    public function getEndAt(): ?string
    {
        return $this->endAt?->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Set end date
     */
    public function setEndAt(\DateTime|string|null $endAt): void
    {
        if (is_string($endAt)) {
            $this->endAt = new \DateTime($endAt);
        } else {
            $this->endAt = $endAt;
        }
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

    /**
     * Convert the DTO to an array with proper date formatting
     * @return mixed[]
     */
    public function toArray(): array
    {
        $properties = get_object_vars($this);

        foreach ($properties as $key => &$value) {
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d\TH:i:s\Z'); // Use Z format to match tests
            }

            if (empty($value) && $value !== false && $value !== 0) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }
}
