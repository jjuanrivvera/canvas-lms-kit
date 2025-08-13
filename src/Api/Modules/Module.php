<?php

namespace CanvasLMS\Api\Modules;

use Exception;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\Modules\CreateModuleDTO;
use CanvasLMS\Dto\Modules\UpdateModuleDTO;
use CanvasLMS\Dto\Modules\CreateModuleItemDTO;
use CanvasLMS\Dto\Modules\BulkUpdateModuleAssignmentOverridesDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Pagination\PaginatedResponse;

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
 * // Get all modules for a course (first page only)
 * $modules = Module::fetchAll();
 *
 * // Get modules with query parameters
 * $modules = Module::fetchAll([
 *     'include' => ['items', 'content_details'],
 *     'search_term' => 'Introduction',
 *     'student_id' => '123'
 * ]);
 *
 * // Find a specific module with includes
 * $module = Module::find(1, ['include' => ['items']]);
 *
 * // Fetch modules with pagination support
 * $paginatedResponse = Module::fetchAllPaginated(['per_page' => 10]);
 * $modules = $paginatedResponse->getJsonData();
 * $pagination = $paginatedResponse->toPaginationResult($modules);
 *
 * // Fetch a specific page of modules
 * $paginationResult = Module::fetchPage(['page' => 2, 'per_page' => 10]);
 * $modules = $paginationResult->getData();
 * $hasNext = $paginationResult->hasNext();
 *
 * // Fetch all modules from all pages
 * $allModules = Module::fetchAllPages(['per_page' => 50]);
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
 * // Update statically
 * $module = Module::update(1, [
 *   'name' => 'Module 1 Updated',
 *   'position' => 1,
 *   'requireSequentialProgress' => true,
 *   'prerequisiteModuleIds' => [1, 2],
 *   'publishFinalGrade' => true,
 *   'published' => true
 * ]);
 *
 * // Re-lock module progressions
 * $module->relock();
 *
 * // Get module items
 * $items = $module->items();
 * $items = $module->items(['include' => ['content_details']]);
 *
 * // Create a module item
 * $item = $module->createModuleItem([
 *     'title' => 'Assignment 1',
 *     'type' => 'Assignment',
 *     'content_id' => 123
 * ]);
 *
 * // List module assignment overrides
 * $overrides = $module->listOverrides();
 *
 * // Bulk update module assignment overrides
 * $dto = new BulkUpdateModuleAssignmentOverridesDTO();
 * $dto->addSectionOverride(123)
 *     ->addStudentOverride([456, 789], null, 'Special Students')
 *     ->addGroupOverride(321);
 * $module->bulkUpdateOverrides($dto);
 *
 * // Or update with array
 * $module->bulkUpdateOverrides([
 *     ['course_section_id' => 123],
 *     ['student_ids' => [456, 789], 'title' => 'Special Students'],
 *     ['id' => 999, 'course_section_id' => 321] // Update existing override
 * ]);
 *
 * // Clear all overrides
 * $module->bulkUpdateOverrides([]);
 *
 * // Delete a module
 * $module = Module::find(1);
 * $module->delete();
 * ```
 *
 * @package CanvasLMS\Api\Modules
 */
class Module extends AbstractBaseApi
{
    /**
     * Course
     * @var Course
     */
    protected static Course $course;

    /**
     * Module constructor.
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        // Initialize nullable properties to avoid uninitialized property errors
        $this->unlockAt = null;
        $this->requirementType = null;
        $this->state = null;
        $this->completedAt = null;
        $this->publishFinalGrade = null;
        $this->published = null;

        parent::__construct($data);
    }

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
    public bool $requireSequentialProgress;

    /**
     * Whether module requires all required items or one required item to be
     * considered complete (one of 'all' or 'one').
     *
     * @var string|null
     */
    public ?string $requirementType;

    /**
     * IDs of Modules that must be completed before this one is unlocked.
     *
     * @var int[]
     */
    public array $prerequisiteModuleIds;

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
     * @var mixed[]|null
     */
    public ?array $items;

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
     * @var bool|null
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
     * Validate that prerequisite modules have lower position values
     * @param int[] $prerequisiteModuleIds
     * @param int $position
     * @throws CanvasApiException
     */
    protected static function validatePrerequisitePositions(array $prerequisiteModuleIds, int $position): void
    {
        $modules = self::fetchAll();
        $modulePositions = [];

        foreach ($modules as $module) {
            $modulePositions[$module->getId()] = $module->getPosition();
        }

        foreach ($prerequisiteModuleIds as $prereqId) {
            if (isset($modulePositions[$prereqId]) && $modulePositions[$prereqId] >= $position) {
                throw new CanvasApiException(
                    sprintf(
                        'Prerequisite module %d must have a lower position than %d',
                        $prereqId,
                        $position
                    )
                );
            }
        }
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

        // Validate prerequisite module positions if provided
        if (!empty($data->prerequisiteModuleIds) && !empty($data->position)) {
            self::validatePrerequisitePositions($data->prerequisiteModuleIds, $data->position);
        }

        $response = self::$apiClient->post(sprintf('courses/%d/modules', self::$course->id), [
            'multipart' => $data->toApiArray()
        ]);

        $moduleData = json_decode($response->getBody()->getContents(), true) ?? [];

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

        // Validate prerequisite module positions if provided
        if (!empty($data->prerequisiteModuleIds) && !empty($data->position)) {
            self::validatePrerequisitePositions($data->prerequisiteModuleIds, $data->position);
        }

        $response = self::$apiClient->put(sprintf('courses/%d/modules/%d', self::$course->id, $id), [
            'multipart' => $data->toApiArray()
        ]);

        $moduleData = json_decode($response->getBody()->getContents(), true) ?? [];

        return new self($moduleData);
    }

    /**
     * Find a module by its ID.
     * @param int $id
     * @param mixed[] $params Query parameters (include[], student_id)
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();
        self::checkCourse();

        $response = self::$apiClient->get(sprintf('courses/%d/modules/%d', self::$course->id, $id), [
            'query' => $params
        ]);

        $moduleData = json_decode($response->getBody()->getContents(), true) ?? [];

        return new self($moduleData);
    }

    /**
     * Get all modules for a course.
     * @param mixed[] $params Query parameters (include[], search_term, student_id)
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

        $modulesData = json_decode($response->getBody()->getContents(), true) ?? [];

        $modules = [];
        foreach ($modulesData as $moduleData) {
            $modules[] = new self($moduleData);
        }

        return $modules;
    }

    /**
     * Fetch modules with pagination support
     * @param mixed[] $params Query parameters for the request
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkCourse();
        return self::getPaginatedResponse(sprintf('courses/%d/modules', self::$course->id), $params);
    }

    /**
     * Fetch modules from a specific page
     * @param mixed[] $params Query parameters for the request
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        $paginatedResponse = self::fetchAllPaginated($params);
        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all modules from all pages
     * @param mixed[] $params Query parameters for the request
     * @return Module[]
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        self::checkCourse();
        return self::fetchAllPagesAsModels(sprintf('courses/%d/modules', self::$course->id), $params);
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

            $moduleData = json_decode($response->getBody()->getContents(), true) ?? [];
            $this->populate($moduleData);
        } catch (CanvasApiException) {
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
        } catch (CanvasApiException) {
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

    /**
     * @return string|null
     */
    public function getRequirementType(): ?string
    {
        return $this->requirementType;
    }

    /**
     * @param string|null $requirementType
     */
    public function setRequirementType(?string $requirementType): void
    {
        $this->requirementType = $requirementType;
    }

    /**
     * Re-lock module progressions
     * Resets module progressions to their default locked state and recalculates them based on the current requirements.
     * @return bool
     * @throws CanvasApiException
     */
    public function relock(): bool
    {
        self::checkApiClient();
        self::checkCourse();

        try {
            $response = self::$apiClient->put(sprintf('courses/%d/modules/%d/relock', self::$course->id, $this->id));
            $moduleData = json_decode($response->getBody()->getContents(), true) ?? [];
            $this->populate($moduleData);
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Get module items
     * @param mixed[] $params Query parameters (include[], search_term, student_id)
     * @return ModuleItem[]
     * @throws CanvasApiException
     */
    public function items(array $params = []): array
    {
        self::checkApiClient();
        self::checkCourse();

        ModuleItem::setCourse(self::$course);
        ModuleItem::setModule($this);

        return ModuleItem::fetchAll($params);
    }

    /**
     * Create a module item
     * @param mixed[]|CreateModuleItemDTO $data
     * @return ModuleItem
     * @throws CanvasApiException
     * @throws Exception
     */
    public function createModuleItem(array | CreateModuleItemDTO $data): ModuleItem
    {
        self::checkApiClient();
        self::checkCourse();

        ModuleItem::setCourse(self::$course);
        ModuleItem::setModule($this);

        return ModuleItem::create($data);
    }

    /**
     * Convert the module to a DTO array
     * @return mixed[]
     */
    protected function toDtoArray(): array
    {
        $data = [];

        if (isset($this->id)) {
            $data['id'] = $this->id;
        }

        if (isset($this->name)) {
            $data['name'] = $this->name;
        }

        if (isset($this->unlockAt)) {
            $data['unlockAt'] = $this->unlockAt;
        }

        if (isset($this->position)) {
            $data['position'] = $this->position;
        }

        if (isset($this->requireSequentialProgress)) {
            $data['requireSequentialProgress'] = $this->requireSequentialProgress;
        }

        if (isset($this->prerequisiteModuleIds)) {
            $data['prerequisiteModuleIds'] = $this->prerequisiteModuleIds;
        }

        if (isset($this->publishFinalGrade)) {
            $data['publishFinalGrade'] = $this->publishFinalGrade;
        }

        if (isset($this->published)) {
            $data['published'] = $this->published;
        }

        return $data;
    }

    /**
     * List module assignment overrides
     * @param mixed[] $params Query parameters
     * @return ModuleAssignmentOverride[]
     * @throws CanvasApiException
     */
    public function listOverrides(array $params = []): array
    {
        self::checkApiClient();
        self::checkCourse();

        $response = self::$apiClient->get(
            sprintf('courses/%d/modules/%d/assignment_overrides', self::$course->id, $this->id),
            ['query' => $params]
        );

        $overridesData = json_decode($response->getBody()->getContents(), true) ?? [];

        $overrides = [];
        foreach ($overridesData as $overrideData) {
            $overrides[] = new ModuleAssignmentOverride($overrideData);
        }

        return $overrides;
    }

    /**
     * Bulk update module assignment overrides
     * @param mixed[]|BulkUpdateModuleAssignmentOverridesDTO $overrides
     * @return bool
     * @throws CanvasApiException
     */
    public function bulkUpdateOverrides(array | BulkUpdateModuleAssignmentOverridesDTO $overrides): bool
    {
        self::checkApiClient();
        self::checkCourse();

        if (is_array($overrides)) {
            $dto = new BulkUpdateModuleAssignmentOverridesDTO();
            $dto->setOverrides($overrides);
            $overrides = $dto;
        }

        try {
            self::$apiClient->put(
                sprintf('courses/%d/modules/%d/assignment_overrides', self::$course->id, $this->id),
                [
                    'json' => $overrides->toApiArray()
                ]
            );
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    // Relationship Methods

    /**
     * Get the course this module belongs to
     *
     * @return Course|null
     * @throws CanvasApiException
     */
    public function course(): ?Course
    {
        self::checkCourse();
        return self::$course;
    }

    /**
     * Get completion rate for this module
     *
     * @example
     * ```php
     * $course = Course::find(123);
     * Module::setCourse($course);
     *
     * $module = Module::find(456);
     *
     * // Get overall completion rate (current user)
     * $rate = $module->getCompletionRate();
     * echo "Module is {$rate}% complete\n";
     *
     * // Get completion rate for specific student
     * $studentRate = $module->getCompletionRate(789);
     * if ($studentRate === 100.0) {
     *     echo "Student has completed this module!\n";
     * }
     *
     * // Track progress across all modules
     * $modules = Module::fetchAll();
     * foreach ($modules as $module) {
     *     $rate = $module->getCompletionRate();
     *     echo "{$module->name}: {$rate}% complete\n";
     * }
     * ```
     *
     * @param int|null $userId Optional user ID to get completion rate for specific user
     * @return float Completion rate as a percentage (0.0 to 100.0)
     * @throws CanvasApiException
     */
    public function getCompletionRate(?int $userId = null): float
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Module ID is required to get completion rate');
        }

        self::checkApiClient();
        self::checkCourse();

        // Get all module items with completion status
        $params = ['include[]' => 'content_details'];
        if ($userId !== null) {
            $params['student_id'] = $userId;
        }

        $items = $this->items($params);

        if (count($items) === 0) {
            return 100.0; // No items means 100% complete
        }

        $completedCount = 0;
        foreach ($items as $item) {
            // Check if item is completed based on completion_requirement
            if (
                isset($item->completionRequirement) &&
                isset($item->completionRequirement['completed']) &&
                $item->completionRequirement['completed'] === true
            ) {
                $completedCount++;
            }
        }

        return round(($completedCount / count($items)) * 100, 2);
    }

    /**
     * Get prerequisite modules
     *
     * @example
     * ```php
     * $course = Course::find(123);
     * Module::setCourse($course);
     *
     * $module = Module::find(456);
     * $prerequisites = $module->prerequisites();
     *
     * foreach ($prerequisites as $prereq) {
     *     echo "Must complete: {$prereq->name}\n";
     * }
     * ```
     *
     * @return Module[] Array of prerequisite Module objects
     * @throws CanvasApiException
     */
    public function prerequisites(): array
    {
        if (!isset($this->prerequisiteModuleIds) || empty($this->prerequisiteModuleIds)) {
            return [];
        }

        self::checkApiClient();
        self::checkCourse();

        $prerequisites = [];
        foreach ($this->prerequisiteModuleIds as $moduleId) {
            try {
                $prerequisites[] = self::find($moduleId);
            } catch (\Exception $e) {
                // Skip modules that can't be loaded (might be deleted or inaccessible)
                continue;
            }
        }

        return $prerequisites;
    }
}
