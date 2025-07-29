<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Content Details Object
 *
 * Represents additional details specific to the content associated with a module item.
 * This is a read-only object that is embedded within ModuleItem responses when requested.
 *
 * @package CanvasLMS\Objects
 */
class ContentDetails
{
    /**
     * Points possible for the content
     */
    public ?float $pointsPossible = null;

    /**
     * Due date for the content
     */
    public ?string $dueAt = null;

    /**
     * Unlock date for the content
     */
    public ?string $unlockAt = null;

    /**
     * Lock date for the content
     */
    public ?string $lockAt = null;

    /**
     * Whether the content is locked for the user
     */
    public ?bool $lockedForUser = null;

    /**
     * Explanation of why the content is locked
     */
    public ?string $lockExplanation = null;

    /**
     * Additional lock information
     * @var mixed[]|null
     */
    public ?array $lockInfo = null;

    /**
     * Lock context (e.g., 'module', 'assignment')
     */
    public ?string $lockContext = null;

    /**
     * Constructor
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords((string) $key, '_')));
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
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
     * Get locked for user status
     */
    public function getLockedForUser(): ?bool
    {
        return $this->lockedForUser;
    }

    /**
     * Set locked for user status
     */
    public function setLockedForUser(?bool $lockedForUser): void
    {
        $this->lockedForUser = $lockedForUser;
    }

    /**
     * Get lock explanation
     */
    public function getLockExplanation(): ?string
    {
        return $this->lockExplanation;
    }

    /**
     * Set lock explanation
     */
    public function setLockExplanation(?string $lockExplanation): void
    {
        $this->lockExplanation = $lockExplanation;
    }

    /**
     * Get lock info
     * @return mixed[]|null
     */
    public function getLockInfo(): ?array
    {
        return $this->lockInfo;
    }

    /**
     * Set lock info
     * @param mixed[]|null $lockInfo
     */
    public function setLockInfo(?array $lockInfo): void
    {
        $this->lockInfo = $lockInfo;
    }

    /**
     * Check if content is locked
     */
    public function isLocked(): bool
    {
        return $this->lockedForUser === true;
    }

    /**
     * Check if content has a due date
     */
    public function hasDueDate(): bool
    {
        return $this->dueAt !== null;
    }

    /**
     * Get lock context
     */
    public function getLockContext(): ?string
    {
        return $this->lockContext;
    }

    /**
     * Set lock context
     */
    public function setLockContext(?string $lockContext): void
    {
        $this->lockContext = $lockContext;
    }

    /**
     * Check if content is available (based on unlock date)
     */
    public function isAvailable(): bool
    {
        if ($this->unlockAt === null) {
            return true;
        }

        return strtotime($this->unlockAt) <= time();
    }

    /**
     * Check if content is expired (based on lock date)
     */
    public function isExpired(): bool
    {
        if ($this->lockAt === null) {
            return false;
        }

        return strtotime($this->lockAt) < time();
    }

    /**
     * Check if content has a deadline
     */
    public function hasDeadline(): bool
    {
        return $this->dueAt !== null;
    }

    /**
     * Convert to array
     * @return mixed[]
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->pointsPossible !== null) {
            $data['points_possible'] = $this->pointsPossible;
        }

        if ($this->dueAt !== null) {
            $data['due_at'] = $this->dueAt;
        }

        if ($this->unlockAt !== null) {
            $data['unlock_at'] = $this->unlockAt;
        }

        if ($this->lockAt !== null) {
            $data['lock_at'] = $this->lockAt;
        }

        if ($this->lockedForUser !== null) {
            $data['locked_for_user'] = $this->lockedForUser;
        }

        if ($this->lockExplanation !== null) {
            $data['lock_explanation'] = $this->lockExplanation;
        }

        if ($this->lockInfo !== null) {
            $data['lock_info'] = $this->lockInfo;
        }

        if ($this->lockContext !== null) {
            $data['lock_context'] = $this->lockContext;
        }

        return $data;
    }
}
