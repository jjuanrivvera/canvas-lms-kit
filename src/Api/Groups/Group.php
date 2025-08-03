<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Groups;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Config;
use CanvasLMS\Dto\Groups\CreateGroupDTO;
use CanvasLMS\Dto\Groups\UpdateGroupDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Groups API
 *
 * Provides functionality to manage groups in Canvas LMS.
 * Groups can be used for collaborative work, discussions, and assignments.
 *
 * @see https://canvas.instructure.com/doc/api/groups.html
 *
 * @package CanvasLMS\Api\Groups
 */
class Group extends AbstractBaseApi
{
    /**
     * The unique identifier for the group
     */
    public ?int $id = null;

    /**
     * The display name of the group
     */
    public ?string $name = null;

    /**
     * The group description
     */
    public ?string $description = null;

    /**
     * Whether the group is public (applies only to community groups)
     */
    public ?bool $isPublic = null;

    /**
     * Whether the group has wiki pages
     */
    public ?bool $followedByUser = null;

    /**
     * The account ID the group belongs to
     */
    public ?int $accountId = null;

    /**
     * The course ID the group belongs to (if applicable)
     */
    public ?int $courseId = null;

    /**
     * The group category ID
     */
    public ?int $groupCategoryId = null;

    /**
     * The SIS ID of the group
     */
    public ?string $sisGroupId = null;

    /**
     * The SIS import ID
     */
    public ?int $sisImportId = null;

    /**
     * The storage quota for the group in bytes
     */
    public ?int $storageQuotaMb = null;

    /**
     * Current group join level
     */
    public ?string $joinLevel = null;

    /**
     * Number of members in the group
     */
    public ?int $membersCount = null;

    /**
     * URL to the group's avatar
     */
    public ?string $avatarUrl = null;

    /**
     * The context type (Course, Account)
     */
    public ?string $contextType = null;

    /**
     * The role of the current user in the group
     */
    public ?string $role = null;

    /**
     * Maximum membership allowed in the group
     */
    public ?int $maxMembership = null;

    /**
     * Whether the group is a favorite
     */
    public ?bool $isFavorite = null;

    /**
     * HTML URL to the group
     */
    public ?string $htmlUrl = null;

    /**
     * The workflow state of the group
     */
    public ?string $workflowState = null;

    /**
     * Permissions for the current user
     * @var array<string, bool>|null
     */
    public ?array $permissions = null;

    /**
     * Get a single group by ID
     *
     * @param int $id Group ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkApiClient();

        $endpoint = sprintf('groups/%d', $id);
        $response = self::$apiClient->get($endpoint);
        $data = json_decode($response->getBody()->getContents(), true);

        return new self($data);
    }

    /**
     * List groups in current account
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<Group>
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        $accountId = Config::getAccountId();
        $endpoint = sprintf('accounts/%d/groups', $accountId);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $groupsData = json_decode($response->getBody()->getContents(), true);

        return array_map(fn($data) => new self($data), $groupsData);
    }

    /**
     * List groups for a specific context
     *
     * @param string $contextType 'accounts' or 'courses'
     * @param int $contextId Account or Course ID
     * @param array<string, mixed> $params Query parameters
     * @return array<Group>
     * @throws CanvasApiException
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        self::checkApiClient();

        $endpoint = sprintf('%s/%d/groups', $contextType, $contextId);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $groupsData = json_decode($response->getBody()->getContents(), true);

        return array_map(fn($data) => new self($data), $groupsData);
    }

    /**
     * Get groups for a specific user
     *
     * @param int $userId User ID
     * @param array<string, mixed> $params Query parameters
     * @return array<Group>
     * @throws CanvasApiException
     */
    public static function fetchUserGroups(int $userId, array $params = []): array
    {
        self::checkApiClient();

        $endpoint = sprintf('users/%d/groups', $userId);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $groupsData = json_decode($response->getBody()->getContents(), true);

        return array_map(fn($data) => new self($data), $groupsData);
    }

    /**
     * Create a new group
     *
     * @param array<string, mixed>|CreateGroupDTO $data Group data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateGroupDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateGroupDTO($data);
        }

        $accountId = Config::getAccountId();
        $endpoint = sprintf('accounts/%d/groups', $accountId);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $groupData = json_decode($response->getBody()->getContents(), true);

        return new self($groupData);
    }

    /**
     * Update group
     *
     * @param int $id Group ID
     * @param array<string, mixed>|UpdateGroupDTO $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateGroupDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateGroupDTO($data);
        }

        $endpoint = sprintf('groups/%d', $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $groupData = json_decode($response->getBody()->getContents(), true);

        return new self($groupData);
    }

    /**
     * Save the group (create or update)
     *
     * @return bool
     */
    public function save(): bool
    {
        try {
            if ($this->id) {
                $dto = new UpdateGroupDTO($this->toDtoArray());
                $updated = self::update($this->id, $dto);
                $this->populate(get_object_vars($updated));
            } else {
                $dto = new CreateGroupDTO($this->toDtoArray());
                $created = self::create($dto);
                $this->populate(get_object_vars($created));
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete the group
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }

        try {
            self::checkApiClient();
            $endpoint = sprintf('groups/%d', $this->id);
            self::$apiClient->delete($endpoint);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get group members
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<User>
     * @throws CanvasApiException
     */
    public function members(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to fetch group members');
        }

        self::checkApiClient();

        $endpoint = sprintf('groups/%d/users', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $usersData = json_decode($response->getBody()->getContents(), true);

        return array_map(fn($data) => new User($data), $usersData);
    }

    /**
     * Add user to group
     *
     * @param int $userId User ID to add
     * @return bool
     * @throws CanvasApiException
     */
    public function addUser(int $userId): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to add user to group');
        }

        self::checkApiClient();

        try {
            $endpoint = sprintf('groups/%d/memberships', $this->id);
            $data = [
                ['name' => 'user_id', 'contents' => (string)$userId]
            ];
            self::$apiClient->post($endpoint, ['multipart' => $data]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove user from group
     *
     * @param int $userId User ID to remove
     * @return bool
     * @throws CanvasApiException
     */
    public function removeUser(int $userId): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to remove user from group');
        }

        self::checkApiClient();

        try {
            // First get the membership ID
            $endpoint = sprintf('groups/%d/memberships', $this->id);
            $response = self::$apiClient->get($endpoint, ['query' => ['filter_states[]' => 'accepted']]);
            $memberships = json_decode($response->getBody()->getContents(), true);

            $membershipId = null;
            foreach ($memberships as $membership) {
                if ($membership['user_id'] == $userId) {
                    $membershipId = $membership['id'];
                    break;
                }
            }

            if (!$membershipId) {
                return false;
            }

            // Delete the membership
            $deleteEndpoint = sprintf('groups/%d/memberships/%d', $this->id, $membershipId);
            self::$apiClient->delete($deleteEndpoint);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Convert group to DTO array
     *
     * @return array<string, mixed>
     */
    protected function toDtoArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'is_public' => $this->isPublic,
            'join_level' => $this->joinLevel,
            'storage_quota_mb' => $this->storageQuotaMb,
            'sis_group_id' => $this->sisGroupId,
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(?bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    public function getCourseId(): ?int
    {
        return $this->courseId;
    }

    public function setCourseId(?int $courseId): void
    {
        $this->courseId = $courseId;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function setAccountId(?int $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function getMembersCount(): ?int
    {
        return $this->membersCount;
    }

    public function setMembersCount(?int $membersCount): void
    {
        $this->membersCount = $membersCount;
    }

    public function getHtmlUrl(): ?string
    {
        return $this->htmlUrl;
    }

    public function setHtmlUrl(?string $htmlUrl): void
    {
        $this->htmlUrl = $htmlUrl;
    }
}
