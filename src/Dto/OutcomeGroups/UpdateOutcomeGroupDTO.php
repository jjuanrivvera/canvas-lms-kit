<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\OutcomeGroups;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Data Transfer Object for updating outcome groups.
 */
class UpdateOutcomeGroupDTO extends AbstractBaseDto
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
     * Update only the title.
     *
     * @param string $title
     * @return self
     */
    public static function updateTitle(string $title): self
    {
        return new self(['title' => $title]);
    }

    /**
     * Update only the description.
     *
     * @param string $description
     * @return self
     */
    public static function updateDescription(string $description): self
    {
        return new self(['description' => $description]);
    }

    /**
     * Update the parent group (move group).
     *
     * @param int $parentGroupId
     * @return self
     */
    public static function moveToParent(int $parentGroupId): self
    {
        return new self(['parentOutcomeGroupId' => $parentGroupId]);
    }

    /**
     * Update vendor GUID.
     *
     * @param string $vendorGuid
     * @return self
     */
    public static function updateVendorGuid(string $vendorGuid): self
    {
        return new self(['vendorGuid' => $vendorGuid]);
    }

    /**
     * Create an UpdateOutcomeGroupDTO with only specific fields.
     *
     * @param array<string, mixed> $fields
     * @return self
     */
    public static function withFields(array $fields): self
    {
        $dto = new self();

        foreach ($fields as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }

        return $dto;
    }

    /**
     * Check if any field is set for update.
     *
     * @return bool
     */
    public function hasUpdates(): bool
    {
        return $this->title !== null ||
               $this->description !== null ||
               $this->vendorGuid !== null ||
               $this->parentOutcomeGroupId !== null;
    }
}
