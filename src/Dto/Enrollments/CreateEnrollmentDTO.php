<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Enrollments;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

class CreateEnrollmentDTO extends AbstractBaseDto implements DTOInterface
{
    protected string $apiPropertyName = 'enrollment';

    // Required fields
    public ?string $userId = null;
    public ?string $type = null;

    // Optional enrollment configuration
    public ?string $enrollmentState = 'active';
    public ?string $courseSectionId = null;
    public ?string $roleId = null;
    public ?bool $limitPrivilegesToCourseSection = null;
    public ?bool $notify = null;
    public ?string $selfEnrollmentCode = null;

    // Date constraints
    public ?\DateTime $startAt = null;
    public ?\DateTime $endAt = null;

    // SIS integration
    public ?string $sisUserId = null;

    // Associated user fields (when creating via email/sis)
    public ?string $userEmail = null;
    public ?string $userFirstName = null;
    public ?string $userLastName = null;
    public ?string $userSisId = null;

    /**
     * Get the user ID
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * Set the user ID
     */
    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Get the enrollment type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the enrollment type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

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
     * Get self-enrollment code
     */
    public function getSelfEnrollmentCode(): ?string
    {
        return $this->selfEnrollmentCode;
    }

    /**
     * Set self-enrollment code
     */
    public function setSelfEnrollmentCode(?string $selfEnrollmentCode): void
    {
        $this->selfEnrollmentCode = $selfEnrollmentCode;
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
     * Get SIS user ID
     */
    public function getSisUserId(): ?string
    {
        return $this->sisUserId;
    }

    /**
     * Set SIS user ID
     */
    public function setSisUserId(?string $sisUserId): void
    {
        $this->sisUserId = $sisUserId;
    }

    /**
     * Get user email
     */
    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    /**
     * Set user email
     */
    public function setUserEmail(?string $userEmail): void
    {
        $this->userEmail = $userEmail;
    }

    /**
     * Get user first name
     */
    public function getUserFirstName(): ?string
    {
        return $this->userFirstName;
    }

    /**
     * Set user first name
     */
    public function setUserFirstName(?string $userFirstName): void
    {
        $this->userFirstName = $userFirstName;
    }

    /**
     * Get user last name
     */
    public function getUserLastName(): ?string
    {
        return $this->userLastName;
    }

    /**
     * Set user last name
     */
    public function setUserLastName(?string $userLastName): void
    {
        $this->userLastName = $userLastName;
    }

    /**
     * Get user SIS ID
     */
    public function getUserSisId(): ?string
    {
        return $this->userSisId;
    }

    /**
     * Set user SIS ID
     */
    public function setUserSisId(?string $userSisId): void
    {
        $this->userSisId = $userSisId;
    }
}
