<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Modules;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Bulk Update Module Assignment Overrides DTO
 *
 * This DTO is used to bulk update module assignment overrides.
 * Overrides that already exist should include an ID and will be updated if needed.
 * New overrides will be created for overrides in the list without an ID.
 * Overrides not included in the list will be deleted.
 * Providing an empty list will delete all of the module's overrides.
 */
class BulkUpdateModuleAssignmentOverridesDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The name of the property in the API
     * @var string
     */
    protected string $apiPropertyName = 'overrides';

    /**
     * List of overrides to apply to the module
     * @var mixed[]
     */
    public array $overrides = [];

    /**
     * Constructor
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if (isset($data['overrides'])) {
            $this->overrides = $data['overrides'];
        }
    }

    /**
     * Add an override to the list
     *
     * @param mixed[] $override Override data containing optional 'id', and one of:
     *                          'student_ids', 'course_section_id', or 'group_id'
     * @return self
     */
    public function addOverride(array $override): self
    {
        $this->overrides[] = $override;
        return $this;
    }

    /**
     * Add a section override
     *
     * @param int $courseSectionId
     * @param int|null $id Optional override ID for updating existing override
     * @param string|null $title Optional title for the override
     * @return self
     */
    public function addSectionOverride(int $courseSectionId, ?int $id = null, ?string $title = null): self
    {
        $override = ['course_section_id' => $courseSectionId];

        if ($id !== null) {
            $override['id'] = $id;
        }

        if ($title !== null) {
            $override['title'] = $title;
        }

        $this->overrides[] = $override;
        return $this;
    }

    /**
     * Add a student override
     *
     * @param int[] $studentIds
     * @param int|null $id Optional override ID for updating existing override
     * @param string|null $title Optional title for the override
     * @return self
     */
    public function addStudentOverride(array $studentIds, ?int $id = null, ?string $title = null): self
    {
        $override = ['student_ids' => $studentIds];

        if ($id !== null) {
            $override['id'] = $id;
        }

        if ($title !== null) {
            $override['title'] = $title;
        }

        $this->overrides[] = $override;
        return $this;
    }

    /**
     * Add a group override (requires Differentiation Tags to be enabled)
     *
     * @param int $groupId
     * @param int|null $id Optional override ID for updating existing override
     * @param string|null $title Optional title for the override
     * @return self
     */
    public function addGroupOverride(int $groupId, ?int $id = null, ?string $title = null): self
    {
        $override = ['group_id' => $groupId];

        if ($id !== null) {
            $override['id'] = $id;
        }

        if ($title !== null) {
            $override['title'] = $title;
        }

        $this->overrides[] = $override;
        return $this;
    }

    /**
     * Clear all overrides (will delete all module overrides when submitted)
     *
     * @return self
     */
    public function clearOverrides(): self
    {
        $this->overrides = [];
        return $this;
    }

    /**
     * Get the overrides
     *
     * @return mixed[]
     */
    public function getOverrides(): array
    {
        return $this->overrides;
    }

    /**
     * Set the overrides
     *
     * @param mixed[] $overrides
     * @return self
     */
    public function setOverrides(array $overrides): self
    {
        $this->overrides = $overrides;
        return $this;
    }

    /**
     * Convert to API array format
     * This returns the overrides directly as Canvas expects them in JSON format
     *
     * @return mixed[]
     */
    public function toApiArray(): array
    {
        return ['overrides' => $this->overrides];
    }
}
