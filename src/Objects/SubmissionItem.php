<?php

namespace CanvasLMS\Objects;

/**
 * Submission activity stream item
 */
class SubmissionItem extends ActivityStreamItem
{
    /**
     * @var string|null
     */
    public ?string $grade;

    /**
     * @var string|null
     */
    public ?string $score;

    /**
     * @var string
     */
    public string $submittedAt;

    /**
     * @var array<string, mixed>
     */
    public array $assignment;
}
