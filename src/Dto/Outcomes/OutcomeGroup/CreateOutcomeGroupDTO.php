<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Outcomes\OutcomeGroup;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Data Transfer Object for creating outcome groups.
 */
class CreateOutcomeGroupDTO extends AbstractBaseDto
{
    protected string $apiPropertyName = 'outcome_group';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->vendorGuid = $data['vendorGuid'] ?? $data['vendor_guid'] ?? null;
        $this->parentOutcomeGroupId = isset($data['parentOutcomeGroupId'])
            ? (int)$data['parentOutcomeGroupId']
            : (isset($data['parent_outcome_group_id']) ? (int)$data['parent_outcome_group_id'] : null);

        parent::__construct($data);
    }

    public ?string $title = null;
    public ?string $description = null;
    public ?string $vendorGuid = null;
    public ?int $parentOutcomeGroupId = null;

    /**
     * Convert DTO to array for API request.
     *
     * @return array<int, array{name: string, contents: string}>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data[] = [
                'name' => 'title',
                'contents' => $this->title
            ];
        }

        if ($this->description !== null) {
            $data[] = [
                'name' => 'description',
                'contents' => $this->description
            ];
        }

        if ($this->vendorGuid !== null) {
            $data[] = [
                'name' => 'vendor_guid',
                'contents' => $this->vendorGuid
            ];
        }

        if ($this->parentOutcomeGroupId !== null) {
            $data[] = [
                'name' => 'parent_outcome_group_id',
                'contents' => (string)$this->parentOutcomeGroupId
            ];
        }

        return $data;
    }

    /**
     * Create a group with required title only.
     *
     * @param string $title
     * @return self
     */
    public static function withTitle(string $title): self
    {
        return new self(['title' => $title]);
    }

    /**
     * Create a group with title and description.
     *
     * @param string $title
     * @param string $description
     * @return self
     */
    public static function withTitleAndDescription(string $title, string $description): self
    {
        return new self(['title' => $title, 'description' => $description]);
    }

    /**
     * Create a subgroup with parent.
     *
     * @param string $title
     * @param int $parentGroupId
     * @return self
     */
    public static function asSubgroup(string $title, int $parentGroupId): self
    {
        return new self(['title' => $title, 'parentOutcomeGroupId' => $parentGroupId]);
    }

    /**
     * Set vendor GUID for external integration.
     *
     * @param string $vendorGuid
     * @return self
     */
    public function withVendorGuid(string $vendorGuid): self
    {
        $this->vendorGuid = $vendorGuid;
        return $this;
    }

    /**
     * Validate the DTO data.
     *
     * @return bool
     */
    public function validate(): bool
    {
        if ($this->title === null || trim($this->title) === '') {
            return false;
        }

        return true;
    }
}
