<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Modules;

/**
 * Module Assignment Override Class
 *
 * Module Assignment Overrides allow applying modules to specific students or sections within a course.
 * When active overrides exist on a module, only students who have an applicable override can access
 * the module and are assigned its items.
 *
 * This is a data model class that represents a Module Assignment Override object.
 * The actual API operations are available through the Module class:
 * - Module::listOverrides()
 * - Module::bulkUpdateOverrides()
 *
 * @package CanvasLMS\Api\Modules
 */
class ModuleAssignmentOverride
{
    /**
     * The ID of the assignment override
     *
     * @var int
     */
    public int $id;

    /**
     * The ID of the module the override applies to
     *
     * @var int
     */
    public int $contextModuleId;

    /**
     * The title of the override
     *
     * @var string
     */
    public string $title;

    /**
     * An array of the override's target students
     * (present only if the override targets an adhoc set of students)
     *
     * @var mixed[]|null
     */
    public ?array $students;

    /**
     * The override's target section
     * (present only if the override targets a section)
     *
     * @var mixed[]|null
     */
    public ?array $courseSection;

    /**
     * The override's target group
     * (present only if the override targets a group and Differentiation Tags are enabled)
     *
     * @var mixed[]|null
     */
    public ?array $group;

    /**
     * Constructor
     *
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getContextModuleId(): int
    {
        return $this->contextModuleId;
    }

    /**
     * @param int $contextModuleId
     */
    public function setContextModuleId(int $contextModuleId): void
    {
        $this->contextModuleId = $contextModuleId;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed[]|null
     */
    public function getStudents(): ?array
    {
        return $this->students;
    }

    /**
     * @param mixed[]|null $students
     */
    public function setStudents(?array $students): void
    {
        $this->students = $students;
    }

    /**
     * @return mixed[]|null
     */
    public function getCourseSection(): ?array
    {
        return $this->courseSection;
    }

    /**
     * @param mixed[]|null $courseSection
     */
    public function setCourseSection(?array $courseSection): void
    {
        $this->courseSection = $courseSection;
    }

    /**
     * @return mixed[]|null
     */
    public function getGroup(): ?array
    {
        return $this->group;
    }

    /**
     * @param mixed[]|null $group
     */
    public function setGroup(?array $group): void
    {
        $this->group = $group;
    }
}
