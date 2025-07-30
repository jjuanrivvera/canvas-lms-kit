<?php

namespace CanvasLMS\Objects;

/**
 * Todo item for users
 */
class TodoItem
{
    /**
     * @var string Type of todo item (grading, submitting)
     */
    public string $type;

    /**
     * @var array<string, mixed>|null Assignment object for grading/submitting types
     */
    public ?array $assignment = null;

    /**
     * @var array<string, mixed>|null Quiz object for ungraded quizzes
     */
    public ?array $quiz = null;

    /**
     * @var string URL to ignore this item
     */
    public string $ignore;

    /**
     * @var string URL to ignore this item permanently
     */
    public string $ignorePermanently;

    /**
     * @var string URL to the Canvas web UI for this item
     */
    public string $htmlUrl;

    /**
     * @var int|null Number of submissions that need grading (for grading type)
     */
    public ?int $needsGradingCount = null;

    /**
     * @var string Context type (Course|Group)
     */
    public string $contextType;

    /**
     * @var int|null Course ID
     */
    public ?int $courseId = null;

    /**
     * @var int|null Group ID
     */
    public ?int $groupId = null;

    /**
     * Constructor
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Check if this is a grading todo
     * @return bool
     */
    public function isGrading(): bool
    {
        return $this->type === 'grading';
    }

    /**
     * Check if this is a submitting todo
     * @return bool
     */
    public function isSubmitting(): bool
    {
        return $this->type === 'submitting';
    }

    /**
     * Convert to array
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
