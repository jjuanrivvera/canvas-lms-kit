<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * GradebookHistoryDay represents a date with grading activity.
 * This is a read-only object that does not extend AbstractBaseApi.
 *
 * @see https://canvas.instructure.com/doc/api/gradebook_history.html#Day
 */
class GradebookHistoryDay
{
    public ?string $date = null;
    /** @var array<GradebookHistoryGrader> */
    public array $graders = [];

    /**
     * Constructor to hydrate the object from API response.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->date = $data['date'] ?? null;

        if (isset($data['graders']) && is_array($data['graders'])) {
            $this->graders = array_map(
                fn($graderData) => new GradebookHistoryGrader($graderData),
                $data['graders']
            );
        }
    }

    /**
     * Create a GradebookHistoryDay from an array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Get all unique assignment IDs that had activity on this day.
     *
     * @return array<int>
     */
    public function getAllAssignmentIds(): array
    {
        $assignmentIds = [];
        foreach ($this->graders as $grader) {
            $assignmentIds = array_merge($assignmentIds, $grader->assignments);
        }
        return array_unique($assignmentIds);
    }

    /**
     * Find a grader by ID.
     *
     * @param int $graderId
     * @return GradebookHistoryGrader|null
     */
    public function findGrader(int $graderId): ?GradebookHistoryGrader
    {
        foreach ($this->graders as $grader) {
            if ($grader->id === $graderId) {
                return $grader;
            }
        }
        return null;
    }

    /**
     * Get the number of graders who had activity on this day.
     *
     * @return int
     */
    public function getGraderCount(): int
    {
        return count($this->graders);
    }

    /**
     * Get the total number of assignments graded on this day.
     *
     * @return int
     */
    public function getTotalAssignmentCount(): int
    {
        return count($this->getAllAssignmentIds());
    }
}
