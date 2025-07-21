<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Quizzes;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating quizzes in Canvas LMS
 *
 * This DTO handles the update of existing quizzes with all the necessary
 * fields supported by the Canvas API. All fields are optional since this is
 * for updating existing quizzes.
 *
 * @package CanvasLMS\Dto\Quizzes
 */
class UpdateQuizDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'quiz';

    /**
     * Quiz title
     */
    public ?string $title = null;

    /**
     * Quiz description (HTML content)
     */
    public ?string $description = null;

    /**
     * Quiz type (assignment, practice_quiz, survey, graded_survey)
     */
    public ?string $quizType = null;

    /**
     * Assignment group ID for graded quizzes
     */
    public ?int $assignmentGroupId = null;

    /**
     * Time limit in minutes
     */
    public ?int $timeLimit = null;

    /**
     * Maximum points possible for this quiz
     */
    public ?float $pointsPossible = null;

    /**
     * Quiz due date (ISO 8601 format)
     */
    public ?string $dueAt = null;

    /**
     * Quiz lock date (ISO 8601 format)
     */
    public ?string $lockAt = null;

    /**
     * Quiz unlock date (ISO 8601 format)
     */
    public ?string $unlockAt = null;

    /**
     * Whether the quiz is published
     */
    public ?bool $published = null;

    /**
     * Whether to shuffle answers
     */
    public ?bool $shuffleAnswers = null;

    /**
     * Whether to show correct answers after submission
     */
    public ?bool $showCorrectAnswers = null;

    /**
     * Number of allowed attempts (-1 for unlimited)
     */
    public ?int $allowedAttempts = null;

    /**
     * Whether to display one question at a time
     */
    public ?bool $oneQuestionAtATime = null;

    /**
     * When to hide quiz results (null, always, until_after_last_attempt)
     */
    public ?string $hideResults = null;

    /**
     * IP address filter/restriction
     */
    public ?string $ipFilter = null;

    /**
     * Access code/password for the quiz
     */
    public ?string $accessCode = null;

    /**
     * Whether the quiz requires lockdown browser
     */
    public ?bool $requireLockdownBrowser = null;

    /**
     * Whether the quiz requires lockdown browser for results
     */
    public ?bool $requireLockdownBrowserForResults = null;

    /**
     * Whether the quiz requires lockdown browser monitor
     */
    public ?bool $requireLockdownBrowserMonitor = null;

    /**
     * Lockdown browser monitor data
     */
    public ?string $lockdownBrowserMonitorData = null;

    /**
     * Get quiz title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set quiz title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get quiz description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set quiz description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get quiz type
     */
    public function getQuizType(): ?string
    {
        return $this->quizType;
    }

    /**
     * Set quiz type
     */
    public function setQuizType(?string $quizType): void
    {
        $this->quizType = $quizType;
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
     * Get time limit
     */
    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    /**
     * Set time limit
     */
    public function setTimeLimit(?int $timeLimit): void
    {
        $this->timeLimit = $timeLimit;
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
     * Get shuffle answers status
     */
    public function getShuffleAnswers(): ?bool
    {
        return $this->shuffleAnswers;
    }

    /**
     * Set shuffle answers status
     */
    public function setShuffleAnswers(?bool $shuffleAnswers): void
    {
        $this->shuffleAnswers = $shuffleAnswers;
    }

    /**
     * Get show correct answers status
     */
    public function getShowCorrectAnswers(): ?bool
    {
        return $this->showCorrectAnswers;
    }

    /**
     * Set show correct answers status
     */
    public function setShowCorrectAnswers(?bool $showCorrectAnswers): void
    {
        $this->showCorrectAnswers = $showCorrectAnswers;
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
     * Get one question at a time status
     */
    public function getOneQuestionAtATime(): ?bool
    {
        return $this->oneQuestionAtATime;
    }

    /**
     * Set one question at a time status
     */
    public function setOneQuestionAtATime(?bool $oneQuestionAtATime): void
    {
        $this->oneQuestionAtATime = $oneQuestionAtATime;
    }

    /**
     * Get hide results setting
     */
    public function getHideResults(): ?string
    {
        return $this->hideResults;
    }

    /**
     * Set hide results setting
     */
    public function setHideResults(?string $hideResults): void
    {
        $this->hideResults = $hideResults;
    }

    /**
     * Get IP filter
     */
    public function getIpFilter(): ?string
    {
        return $this->ipFilter;
    }

    /**
     * Set IP filter
     */
    public function setIpFilter(?string $ipFilter): void
    {
        $this->ipFilter = $ipFilter;
    }

    /**
     * Get access code
     */
    public function getAccessCode(): ?string
    {
        return $this->accessCode;
    }

    /**
     * Set access code
     */
    public function setAccessCode(?string $accessCode): void
    {
        $this->accessCode = $accessCode;
    }

    /**
     * Get require lockdown browser status
     */
    public function getRequireLockdownBrowser(): ?bool
    {
        return $this->requireLockdownBrowser;
    }

    /**
     * Set require lockdown browser status
     */
    public function setRequireLockdownBrowser(?bool $requireLockdownBrowser): void
    {
        $this->requireLockdownBrowser = $requireLockdownBrowser;
    }

    /**
     * Get require lockdown browser for results status
     */
    public function getRequireLockdownBrowserForResults(): ?bool
    {
        return $this->requireLockdownBrowserForResults;
    }

    /**
     * Set require lockdown browser for results status
     */
    public function setRequireLockdownBrowserForResults(?bool $requireLockdownBrowserForResults): void
    {
        $this->requireLockdownBrowserForResults = $requireLockdownBrowserForResults;
    }

    /**
     * Get require lockdown browser monitor status
     */
    public function getRequireLockdownBrowserMonitor(): ?bool
    {
        return $this->requireLockdownBrowserMonitor;
    }

    /**
     * Set require lockdown browser monitor status
     */
    public function setRequireLockdownBrowserMonitor(?bool $requireLockdownBrowserMonitor): void
    {
        $this->requireLockdownBrowserMonitor = $requireLockdownBrowserMonitor;
    }

    /**
     * Get lockdown browser monitor data
     */
    public function getLockdownBrowserMonitorData(): ?string
    {
        return $this->lockdownBrowserMonitorData;
    }

    /**
     * Set lockdown browser monitor data
     */
    public function setLockdownBrowserMonitorData(?string $lockdownBrowserMonitorData): void
    {
        $this->lockdownBrowserMonitorData = $lockdownBrowserMonitorData;
    }
}
