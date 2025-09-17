<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * SubmissionHistory represents the complete history of versions for a submission.
 * This is a read-only object that does not extend AbstractBaseApi.
 *
 * @see https://canvas.instructure.com/doc/api/gradebook_history.html#SubmissionHistory
 */
class SubmissionHistory
{
    public ?int $submissionId = null;

    /** @var array<SubmissionVersion> */
    public array $versions = [];

    /**
     * Constructor to hydrate the object from API response.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->submissionId = isset($data['submission_id']) ? (int) $data['submission_id'] : null;

        if (isset($data['versions']) && is_array($data['versions'])) {
            $this->versions = array_map(
                fn ($versionData) => new SubmissionVersion($versionData),
                $data['versions']
            );
        }
    }

    /**
     * Create a SubmissionHistory from an array.
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
     * Get the number of versions in this submission history.
     *
     * @return int
     */
    public function getVersionCount(): int
    {
        return count($this->versions);
    }

    /**
     * Get the latest version of the submission.
     *
     * @return SubmissionVersion|null
     */
    public function getLatestVersion(): ?SubmissionVersion
    {
        if (empty($this->versions)) {
            return null;
        }

        // Versions are typically returned in descending order (newest first)
        return $this->versions[0];
    }

    /**
     * Get the earliest version of the submission.
     *
     * @return SubmissionVersion|null
     */
    public function getEarliestVersion(): ?SubmissionVersion
    {
        if (empty($this->versions)) {
            return null;
        }

        return $this->versions[count($this->versions) - 1];
    }

    /**
     * Find a version by its ID.
     *
     * @param int $versionId
     *
     * @return SubmissionVersion|null
     */
    public function findVersion(int $versionId): ?SubmissionVersion
    {
        foreach ($this->versions as $version) {
            if ($version->id === $versionId) {
                return $version;
            }
        }

        return null;
    }

    /**
     * Get all versions that have grade changes.
     *
     * @return array<SubmissionVersion>
     */
    public function getVersionsWithGradeChanges(): array
    {
        return array_filter(
            $this->versions,
            fn ($version) => $version->hasGradeChange()
        );
    }

    /**
     * Get all unique grader IDs from the submission history.
     *
     * @return array<int>
     */
    public function getGraderIds(): array
    {
        $graderIds = [];
        foreach ($this->versions as $version) {
            if ($version->graderId !== null) {
                $graderIds[] = $version->graderId;
            }
        }

        return array_unique($graderIds);
    }
}
