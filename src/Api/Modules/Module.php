<?php

namespace CanvasLMS\Api\Modules;

use Exception;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\Modules\CreateModuleDTO;
use CanvasLMS\Dto\Modules\UpdateModuleDTO;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * Module Class
 *
 * Modules are collections of learning materials useful for organizing courses and optionally
 * providing a linear flow through them. Module items can be accessed linearly or sequentially
 * depending on module configuration. Items can be unlocked by various criteria such as reading a page or achieving
 * a minimum score on a quiz. Modules themselves can be unlocked by the completion of other Modules.
 *
 * Usage:
 * ```php
 * $course = Course::find(1); // or $course = new Course(['id' => 1]); to avoid making an API request
 * Module::setCourse($course);
 *
 * // Get all modules for a course
 * $modules = Module::fetchAll();
 *
 * // Create a new module
 * $module = Module::create([
 *    'name' => 'Module 1',
 *    'position' => 1,
 *    'requireSequentialProgress' => true,
 *    'prerequisiteModuleIds' => [1, 2],
 *    'publishFinalGrade' => true
 * ]);
 *
 * // Update a module
 * $module->setName('Module 1 Updated');
 * $module->save();
 *
 * // Update staticaly
 * $module = Module::update(1, [
 *   'name' => 'Module 1 Updated',
 *   'position' => 1,
 *   'requireSequentialProgress' => true,
 *   'prerequisiteModuleIds' => [1, 2],
 *   'publishFinalGrade' => true
 * ]);
 *
 * // Delete a module
 * $module = Module::find(1);
 * $module->delete();
 * ```
 */
class Module extends AbstractBaseApi
{
    /**
     * Course
     * @var Course
     */
    protected static Course $course;

    /**
     * The unique identifier for the module.
     *
     * @var int
     */
    public int $id;

    /**
     * The state of the module: 'active', 'deleted'.
     *
     * @var string
     */
    public string $workflowState;

    /**
     * The position of this module in the course (1-based).
     *
     * @var int
     */
    public int $position;

    /**
     * The name of this module.
     *
     * @var string
     */
    public string $name;

    /**
     * The date this module will unlock (Optional).
     *
     * @var string|null
     */
    public ?string $unlockAt;

    /**
     * Whether module items must be unlocked in order.
     *
     * @var bool
     */
    public $requireSequentialProgress;

    /**
     * IDs of Modules that must be completed before this one is unlocked.
     *
     * @var mixed[]
     */
    public $prerequisiteModuleIds;

    /**
     * The number of items in the module.
     *
     * @var int
     */
    public int $itemsCount;

    /**
     * The API URL to retrieve this module's items.
     *
     * @var string
     */
    public string $itemsUrl;

    /**
     * The contents of this module, as an array of Module Items.
     * (Present only if requested via include[]=items AND the module is not deemed too large by Canvas.)
     *
     * @var mixed[]
     */
    public array $items;

    /**
     * The state of this Module for the calling user: 'locked', 'unlocked', 'started', 'completed' (Optional).
     * (Present only if the caller is a student or if the optional parameter 'student_id' is included)
     *
     * @var string|null
     */
    public ?string $state;

    /**
     * The date the calling user completed the module (Optional).
     * (Present only if the caller is a student or if the optional parameter 'student_id' is included)
     *
     * @var string|null
     */
    public ?string $completedAt;

    /**
     * If the student's final grade for the course should be published to the SIS upon completion of this module.
     *
     * @var bool|null
     */
    public ?bool $publishFinalGrade;

    /**
     * Whether this module is published.
     * This field is present only if the caller has permission to view unpublished modules.
     *
     * @var bool
     */
    public ?bool $published;

    /**
     * Set the course
     * @param Course $course
     * @return void
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Check if course exits and has id
     * @return bool
     * @throws CanvasApiException
     */
    public static function checkCourse(): bool
    {
        if (!isset(self::$course) || !isset(self::$course->id)) {
            throw new CanvasApiException('Course is required');
        }

        return true;
    }

    /**
     * Create a new module
     * @param CreateModuleDTO|mixed[] $data
     * @return self
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function create(array | CreateModuleDTO $data): self
    {
        self::checkApiClient();
        self::checkCourse();

        if (is_array($data)) {
            $data = new CreateModuleDTO($data);
        }

        $response = self::$apiClient->post(sprintf('courses/%d/modules', self::$course->id), [
            'multipart' => $data->toApiArray()
        ]);

        $moduleData = json_decode($response->getBody(), true);

        return new self($moduleData);
    }

    /**
     * Update a module
     * @param int $id
     * @param UpdateModuleDTO|mixed[] $data
     * @return self
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function update(int $id, array | UpdateModuleDTO $data): self
    {
        self::checkApiClient();
        self::checkCourse();

        if (is_array($data)) {
            $data = new UpdateModuleDTO($data);
        }

        $response = self::$apiClient->put(sprintf('courses/%d/modules/%d', self::$course->id, $id), [
            'multipart' => $data->toApiArray()
        ]);

        $moduleData = json_decode($response->getBody(), true);

        return new self($moduleData);
    }

    /**
     * Find a module by its ID.
     * @param int $id
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkApiClient();
        self::checkCourse();

        $response = self::$apiClient->get(sprintf('courses/%d/modules/%d', self::$course->id, $id));

        $moduleData = json_decode($response->getBody(), true);

        return new self($moduleData);
    }

    /**
     * Get all modules for a course.
     * @param mixed[] $params
     * @return mixed[]
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();
        self::checkCourse();

        $response = self::$apiClient->get(sprintf('courses/%d/modules', self::$course->id), [
            'query' => $params
        ]);

        $modulesData = json_decode($response->getBody(), true);

        $modules = [];
        foreach ($modulesData as $moduleData) {
            $modules[] = new self($moduleData);
        }

        return $modules;
    }

    /**
     * Save the module
     * @return bool
     * @throws CanvasApiException
     * @throws Exception
     */
    public function save(): bool
    {
        self::checkApiClient();
        self::checkCourse();

        $data = $this->toDtoArray();

        $dto = isset($data['id']) ? new UpdateModuleDTO($data) : new CreateModuleDTO($data);
        $path = isset($data['id'])
            ? sprintf('courses/%d/modules/%d', self::$course->id, $data['id'])
            : sprintf('courses/%d/modules', self::$course->id);
        $method = isset($data['id']) ? 'PUT' : 'POST';

        try {
            $response = self::$apiClient->request($method, $path, [
                'multipart' => $dto->toApiArray()
            ]);

            $moduleData = json_decode($response->getBody(), true);
            $this->populate($moduleData);
        } catch (CanvasApiException $th) {
            return false;
        }

        return true;
    }

    /**
     * Delete a module
     * @return bool
     * @throws CanvasApiException
     */
    public function delete(): bool
    {
        self::checkApiClient();
        self::checkCourse();

        try {
            self::$apiClient->delete(sprintf('courses/%d/modules/%d', self::$course->id, $this->id));
        } catch (CanvasApiException $th) {
            return false;
        }

        return true;
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
     * @return string
     */
    public function getWorkflowState(): string
    {
        return $this->workflowState;
    }

    /**
     * @param string $workflowState
     */
    public function setWorkflowState(string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getUnlockAt(): ?string
    {
        return $this->unlockAt;
    }

    /**
     * @param string|null $unlockAt
     */
    public function setUnlockAt(?string $unlockAt): void
    {
        $this->unlockAt = $unlockAt;
    }

    /**
     * @return bool
     */
    public function isRequireSequentialProgress(): bool
    {
        return $this->requireSequentialProgress;
    }

    /**
     * @param bool $requireSequentialProgress
     */
    public function setRequireSequentialProgress(bool $requireSequentialProgress): void
    {
        $this->requireSequentialProgress = $requireSequentialProgress;
    }

    /**
     * @return mixed[]
     */
    public function getPrerequisiteModuleIds(): array
    {
        return $this->prerequisiteModuleIds;
    }

    /**
     * @param mixed[] $prerequisiteModuleIds
     */
    public function setPrerequisiteModuleIds(array $prerequisiteModuleIds): void
    {
        $this->prerequisiteModuleIds = $prerequisiteModuleIds;
    }

    /**
     * @return int
     */
    public function getItemsCount(): int
    {
        return $this->itemsCount;
    }

    /**
     * @param int $itemsCount
     */
    public function setItemsCount(int $itemsCount): void
    {
        $this->itemsCount = $itemsCount;
    }

    /**
     * @return string
     */
    public function getItemsUrl(): string
    {
        return $this->itemsUrl;
    }

    /**
     * @param string $itemsUrl
     */
    public function setItemsUrl(string $itemsUrl): void
    {
        $this->itemsUrl = $itemsUrl;
    }

    /**
     * @return mixed[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param mixed[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string|null $state
     */
    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return string|null
     */
    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    /**
     * @param string|null $completedAt
     */
    public function setCompletedAt(?string $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    /**
     * @return bool|null
     */
    public function getPublishFinalGrade(): ?bool
    {
        return $this->publishFinalGrade;
    }

    /**
     * @param bool|null $publishFinalGrade
     */
    public function setPublishFinalGrade(?bool $publishFinalGrade): void
    {
        $this->publishFinalGrade = $publishFinalGrade;
    }

    /**
     * @return bool|null
     */
    public function getPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * @param bool|null $published
     */
    public function setPublished(?bool $published): void
    {
        $this->published = $published;
    }
}
