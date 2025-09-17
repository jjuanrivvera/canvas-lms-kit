<?php

declare(strict_types=1);

namespace CanvasLMS\Api\GroupCategories;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Groups\Group;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Config;
use CanvasLMS\Dto\GroupCategories\CreateGroupCategoryDTO;
use CanvasLMS\Dto\GroupCategories\UpdateGroupCategoryDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Group Categories API
 *
 * Group Categories allow organizing groups together in Canvas. They provide a way to
 * manage collections of groups with shared settings and permissions.
 *
 * @see https://canvas.instructure.com/doc/api/group_categories.html
 *
 * @package CanvasLMS\Api\GroupCategories
 */
class GroupCategory extends AbstractBaseApi
{
    /**
     * Course context for group categories
     */
    protected static ?Course $course = null;

    /**
     * The ID of the group category
     */
    public ?int $id = null;

    /**
     * The display name of the group category
     */
    public ?string $name = null;

    /**
     * Special role designations: 'communities', 'student_organized', 'imported', or null
     */
    public ?string $role = null;

    /**
     * Self signup configuration: 'enabled', 'restricted', or null
     */
    public ?string $selfSignup = null;

    /**
     * Auto leader configuration: 'first', 'random', or null
     */
    public ?string $autoLeader = null;

    /**
     * The context type (Course or Account)
     */
    public ?string $contextType = null;

    /**
     * The ID of the context (course_id or account_id)
     */
    public ?int $contextId = null;

    /**
     * The course ID if context_type is Course
     */
    public ?int $courseId = null;

    /**
     * The account ID if context_type is Account
     */
    public ?int $accountId = null;

    /**
     * Maximum number of users in each group (if self-signup enabled)
     */
    public ?int $groupLimit = null;

    /**
     * The SIS identifier for the group category
     */
    public ?string $sisGroupCategoryId = null;

    /**
     * The unique identifier for the SIS import
     */
    public ?int $sisImportId = null;

    /**
     * Progress object for async operations
     * @var array<string, mixed>|null
     */
    public ?array $progress = null;

    /**
     * Indicates whether this group category is non-collaborative
     */
    public ?bool $nonCollaborative = null;

    /**
     * Get a single group category by ID
     *
     * @param int $id Group Category ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();

        $endpoint = sprintf('group_categories/%d', $id);
        $response = self::$apiClient->get($endpoint);
        $data = self::parseJsonResponse($response);

        return new self($data);
    }

    /**
     * Set the course context for group category operations
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Check if course context is set
     */
    public static function checkCourse(): bool
    {
        return isset(self::$course);
    }

    /**
     * Get the endpoint for this resource.
     *
     * @return string
     * @throws CanvasApiException
     */
    protected static function getEndpoint(): string
    {
        if (self::checkCourse()) {
            return sprintf('courses/%d/group_categories', self::$course->getId());
        }

        $accountId = Config::getAccountId();
        return sprintf('accounts/%d/group_categories', $accountId);
    }





    /**
     * Create a new group category in the current account
     *
     * @param array<string, mixed>|CreateGroupCategoryDTO $data Group category data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateGroupCategoryDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateGroupCategoryDTO($data);
        }

        $accountId = Config::getAccountId();
        $endpoint = sprintf('accounts/%d/group_categories', $accountId);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $categoryData = self::parseJsonResponse($response);

        return new self($categoryData);
    }

    /**
     * Update group category
     *
     * @param int $id Group Category ID
     * @param array<string, mixed>|UpdateGroupCategoryDTO $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateGroupCategoryDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateGroupCategoryDTO($data);
        }

        $endpoint = sprintf('group_categories/%d', $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $categoryData = self::parseJsonResponse($response);

        return new self($categoryData);
    }

    /**
     * Save the group category (create or update)
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        if ($this->id) {
            $dto = new UpdateGroupCategoryDTO($this->toDtoArray());
            $updated = self::update($this->id, $dto);
            $this->populate(get_object_vars($updated));
        } else {
            $dto = new CreateGroupCategoryDTO($this->toDtoArray());
            $created = self::create($dto);
            $this->populate(get_object_vars($created));
        }
        return $this;
    }

    /**
     * Delete the group category
     *
     * @return self
     * @throws CanvasApiException
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Group category ID is required for deletion');
        }

        self::checkApiClient();
        $endpoint = sprintf('group_categories/%d', $this->id);
        self::$apiClient->delete($endpoint);
        return $this;
    }

    /**
     * List groups in this category
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<Group>
     * @throws CanvasApiException
     */
    public function groups(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Group Category ID is required to fetch groups');
        }

        self::checkApiClient();

        $endpoint = sprintf('group_categories/%d/groups', $this->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        return array_map(fn($data) => new Group($data), $allData);
    }

    /**
     * Get paginated groups in this category
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function groupsPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException('Group Category ID is required to fetch groups');
        }

        return self::getPaginatedResponse(sprintf('group_categories/%d/groups', $this->id), $params);
    }

    /**
     * List users in this category
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<User>
     * @throws CanvasApiException
     */
    public function users(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Group Category ID is required to fetch users');
        }

        self::checkApiClient();

        $endpoint = sprintf('group_categories/%d/users', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $usersData = self::parseJsonResponse($response);

        return array_map(fn($data) => new User($data), $usersData);
    }

    /**
     * Get paginated users in this category
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function usersPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException('Group Category ID is required to fetch users');
        }

        return self::getPaginatedResponse(sprintf('group_categories/%d/users', $this->id), $params);
    }

    /**
     * Assign unassigned members to groups
     *
     * @param bool $sync Whether to perform synchronously (default: false)
     * @return array<Group>|array<mixed> Groups if sync=true, progress object if async
     * @throws CanvasApiException
     */
    public function assignUnassignedMembers(bool $sync = false): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Group Category ID is required to assign members');
        }

        self::checkApiClient();

        $endpoint = sprintf('group_categories/%d/assign_unassigned_members', $this->id);

        // Consistent multipart parameter structure
        $multipart = [];
        if ($sync) {
            $multipart[] = ['name' => 'sync', 'contents' => 'true'];
        }
        $params = empty($multipart) ? [] : ['multipart' => $multipart];

        $response = self::$apiClient->post($endpoint, $params);
        $data = self::parseJsonResponse($response);

        // If sync=true, we get groups back. Otherwise, we get a progress object
        if ($sync && isset($data[0]['id']) && isset($data[0]['name'])) {
            return array_map(fn($groupData) => new Group($groupData), $data);
        }

        return $data;
    }

    /**
     * Export groups and users in this category
     *
     * @return array<mixed>
     * @throws CanvasApiException
     */
    public function export(): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Group Category ID is required for export');
        }

        self::checkApiClient();

        $endpoint = sprintf('group_categories/%d/export', $this->id);
        $response = self::$apiClient->get($endpoint);

        return self::parseJsonResponse($response);
    }

    /**
     * Convert group category to DTO array
     *
     * @return array<string, mixed>
     */
    public function toDtoArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'self_signup' => $this->selfSignup,
            'auto_leader' => $this->autoLeader,
            'group_limit' => $this->groupLimit,
            'sis_group_category_id' => $this->sisGroupCategoryId,
            'non_collaborative' => $this->nonCollaborative,
        ], fn($value) => $value !== null);
    }

    // Getter and setter methods

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): void
    {
        $this->role = $role;
    }

    public function getSelfSignup(): ?string
    {
        return $this->selfSignup;
    }

    public function setSelfSignup(?string $selfSignup): void
    {
        $this->selfSignup = $selfSignup;
    }

    public function getAutoLeader(): ?string
    {
        return $this->autoLeader;
    }

    public function setAutoLeader(?string $autoLeader): void
    {
        $this->autoLeader = $autoLeader;
    }

    public function getContextType(): ?string
    {
        return $this->contextType;
    }

    public function setContextType(?string $contextType): void
    {
        $this->contextType = $contextType;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setContextId(?int $contextId): void
    {
        $this->contextId = $contextId;
    }

    public function getGroupLimit(): ?int
    {
        return $this->groupLimit;
    }

    public function setGroupLimit(?int $groupLimit): void
    {
        $this->groupLimit = $groupLimit;
    }

    public function getNonCollaborative(): ?bool
    {
        return $this->nonCollaborative;
    }

    public function setNonCollaborative(?bool $nonCollaborative): void
    {
        $this->nonCollaborative = $nonCollaborative;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProgress(): ?array
    {
        return $this->progress;
    }

    /**
     * @param array<string, mixed>|null $progress
     */
    public function setProgress(?array $progress): void
    {
        $this->progress = $progress;
    }
}
