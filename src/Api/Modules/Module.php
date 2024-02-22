<?php

namespace CanvasLMS\Api\Modules;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\Modules\CreateModuleDTO;
use CanvasLMS\Dto\Modules\UpdateModuleDTO;
use CanvasLMS\Exceptions\CanvasApiException;

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
    public static function setCourse(Course $course)
    {
        self::$course = $course;
    }

    /**
     * Check if course exits and has id
     * @return bool
     */
    public static function checkCourse()
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
}
