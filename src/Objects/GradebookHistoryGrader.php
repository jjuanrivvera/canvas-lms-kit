<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * GradebookHistoryGrader represents a grader who worked on assignments on a specific day.
 * This is a read-only object that does not extend AbstractBaseApi.
 *
 * @see https://canvas.instructure.com/doc/api/gradebook_history.html#Grader
 */
class GradebookHistoryGrader
{
    public ?int $id = null;

    public ?string $name = null;

    /** @var array<int> */
    public array $assignments = [];

    /**
     * Constructor to hydrate the object from API response.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->name = $data['name'] ?? null;
        $this->assignments = isset($data['assignments']) && is_array($data['assignments'])
            ? array_map('intval', $data['assignments'])
            : [];
    }

    /**
     * Create a GradebookHistoryGrader from an array.
     *
     * @param array<string, mixed> $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Check if the grader worked on a specific assignment.
     *
     * @param int $assignmentId
     *
     * @return bool
     */
    public function workedOnAssignment(int $assignmentId): bool
    {
        return in_array($assignmentId, $this->assignments, true);
    }

    /**
     * Get the number of assignments this grader worked on.
     *
     * @return int
     */
    public function getAssignmentCount(): int
    {
        return count($this->assignments);
    }
}
