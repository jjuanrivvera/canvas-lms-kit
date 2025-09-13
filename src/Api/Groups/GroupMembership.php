<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Groups;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Dto\Groups\CreateGroupMembershipDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Group Memberships API
 *
 * Group memberships are the objects that tie users and groups together.
 * They handle the relationships between users and groups including
 * invitations, acceptances, and membership states.
 *
 * @see https://canvas.instructure.com/doc/api/groups.html#group-memberships
 *
 * @package CanvasLMS\Api\Groups
 */
class GroupMembership extends AbstractBaseApi
{
    /**
     * The unique identifier for the membership
     */
    public ?int $id = null;

    /**
     * The ID of the group
     */
    public ?int $groupId = null;

    /**
     * The ID of the user
     */
    public ?int $userId = null;

    /**
     * The workflow state of the membership
     * Values: 'accepted', 'invited', 'requested'
     */
    public ?string $workflowState = null;

    /**
     * Whether the user is a moderator of the group (observer, admin, etc)
     */
    public ?bool $moderator = null;

    /**
     * The user object (when include[]=user)
     * @var User|null
     */
    public ?User $user = null;

    /**
     * The group object (when include[]=group)
     * @var Group|null
     */
    public ?Group $group = null;

    /**
     * When the membership was created
     */
    public ?string $createdAt = null;

    /**
     * When the membership was last updated
     */
    public ?string $updatedAt = null;

    /**
     * Just created membership flag
     */
    public ?bool $justCreated = null;

    /**
     * The SIS ID of the group
     */
    public ?string $sisGroupId = null;

    /**
     * The SIS import ID
     */
    public ?int $sisImportId = null;

    /**
     * Get a single group membership
     *
     * @param int $id Membership ID
     * @param array<string, mixed> $params Query parameters (must include 'group_id')
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        if (!isset($params['group_id'])) {
            throw new CanvasApiException(
                'Group ID is required in params array. Use find($id, [\'group_id\' => $groupId])'
            );
        }

        $groupId = (int) $params['group_id'];
        unset($params['group_id']); // Remove from query params

        self::checkApiClient();

        $endpoint = sprintf('groups/%d/memberships/%d', $groupId, $id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        return new self($data);
    }

    /**
     * List group memberships (interface requirement)
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<GroupMembership>
     * @throws CanvasApiException
     */
    public static function get(array $params = []): array
    {
        throw new CanvasApiException('Group ID is required. Use fetchAllForGroup($groupId, $params) instead.');
    }

    /**
     * List group memberships for a specific group
     *
     * @param int $groupId Group ID
     * @param array<string, mixed> $params Query parameters
     * @return array<GroupMembership>
     * @throws CanvasApiException
     */
    public static function fetchAllForGroup(int $groupId, array $params = []): array
    {
        self::checkApiClient();
        $endpoint = sprintf('groups/%d/memberships', $groupId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        $memberships = [];
        foreach ($allData as $item) {
            $memberships[] = new self($item);
        }

        return $memberships;
    }



    /**
     * Get all pages of group memberships
     *
     * @param array<string, mixed> $params Query parameters (must include 'group_id')
     * @return array<GroupMembership>
     * @throws CanvasApiException
     */
    public static function all(array $params = []): array
    {
        if (!isset($params['group_id'])) {
            throw new CanvasApiException(
                'Group ID is required in params array. Use all([\'group_id\' => $groupId])'
            );
        }

        $groupId = (int) $params['group_id'];
        unset($params['group_id']); // Remove from query params

        return self::fetchAllForGroup($groupId, $params);
    }

    /**
     * Create a membership
     *
     * @param int $groupId Group ID
     * @param array<string, mixed>|CreateGroupMembershipDTO $data Membership data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(int $groupId, array|CreateGroupMembershipDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateGroupMembershipDTO($data);
        }

        $endpoint = sprintf('groups/%d/memberships', $groupId);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $membershipData = json_decode($response->getBody()->getContents(), true);

        return new self($membershipData);
    }

    /**
     * Update a membership
     *
     * @param int $groupId Group ID
     * @param int $membershipId Membership ID
     * @param array<string, mixed> $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $groupId, int $membershipId, array $data): self
    {
        self::checkApiClient();

        $endpoint = sprintf('groups/%d/memberships/%d', $groupId, $membershipId);
        $multipart = [];

        if (isset($data['workflow_state'])) {
            $multipart[] = ['name' => 'workflow_state', 'contents' => $data['workflow_state']];
        }

        if (isset($data['moderator'])) {
            $multipart[] = ['name' => 'moderator', 'contents' => $data['moderator'] ? 'true' : 'false'];
        }

        $response = self::$apiClient->put($endpoint, ['multipart' => $multipart]);
        $membershipData = json_decode($response->getBody()->getContents(), true);

        return new self($membershipData);
    }

    /**
     * Update a membership by user ID
     *
     * @param int $groupId Group ID
     * @param int $userId User ID
     * @param array<string, mixed> $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public static function updateByUserId(int $groupId, int $userId, array $data): self
    {
        self::checkApiClient();

        $endpoint = sprintf('groups/%d/users/%d', $groupId, $userId);
        $multipart = [];

        if (isset($data['workflow_state'])) {
            $multipart[] = ['name' => 'workflow_state', 'contents' => $data['workflow_state']];
        }

        if (isset($data['moderator'])) {
            $multipart[] = ['name' => 'moderator', 'contents' => $data['moderator'] ? 'true' : 'false'];
        }

        $response = self::$apiClient->put($endpoint, ['multipart' => $multipart]);
        $membershipData = json_decode($response->getBody()->getContents(), true);

        return new self($membershipData);
    }

    /**
     * Delete a membership (leave group)
     *
     * @param int $groupId Group ID
     * @param int $membershipId Membership ID
     * @return void
     * @throws CanvasApiException
     */
    public static function deleteMembership(int $groupId, int $membershipId): void
    {
        self::checkApiClient();
        $endpoint = sprintf('groups/%d/memberships/%d', $groupId, $membershipId);
        self::$apiClient->delete($endpoint);
    }

    /**
     * Delete this membership instance
     *
     * @return self
     * @throws CanvasApiException
     */
    public function delete(): self
    {
        if (!$this->groupId || !$this->id) {
            throw new CanvasApiException("Cannot delete membership without group ID and membership ID");
        }

        self::deleteMembership($this->groupId, $this->id);

        return $this;
    }

    /**
     * Leave a group (for current user)
     *
     * @param int $groupId Group ID
     * @return void
     * @throws CanvasApiException
     */
    public static function leave(int $groupId): void
    {
        self::checkApiClient();
        $endpoint = sprintf('groups/%d/memberships/self', $groupId);
        self::$apiClient->delete($endpoint);
    }

    /**
     * Constructor override to handle nested objects
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $userData = null;
        $groupData = null;

        // Extract nested objects before parent constructor
        if (isset($data['user']) && is_array($data['user'])) {
            $userData = $data['user'];
            unset($data['user']);
        }

        if (isset($data['group']) && is_array($data['group'])) {
            $groupData = $data['group'];
            unset($data['group']);
        }

        // Call parent constructor with cleaned data
        parent::__construct($data);

        // Create nested objects after properties are set
        if ($userData !== null) {
            $this->user = new User($userData);
        }

        if ($groupData !== null) {
            $this->group = new Group($groupData);
        }
    }

    /**
     * Get membership by user ID
     *
     * @param int $groupId Group ID
     * @param int $userId User ID
     * @return self
     * @throws CanvasApiException
     */
    public static function findByUserId(int $groupId, int $userId): self
    {
        self::checkApiClient();

        $endpoint = sprintf('groups/%d/users/%d', $groupId, $userId);
        $response = self::$apiClient->get($endpoint);
        $data = json_decode($response->getBody()->getContents(), true);

        return new self($data);
    }


    /**
     * Accept this membership invitation
     *
     * @return self
     * @throws CanvasApiException
     */
    public function accept(): self
    {
        if (!$this->groupId || !$this->id) {
            throw new CanvasApiException('Group ID and Membership ID are required');
        }

        $updated = self::update($this->groupId, $this->id, ['workflow_state' => 'accepted']);
        // Update properties from the returned object
        foreach (get_object_vars($updated) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * Reject this membership invitation
     *
     * @return self
     * @throws CanvasApiException
     */
    public function reject(): self
    {
        if (!$this->groupId || !$this->id) {
            throw new CanvasApiException('Group ID and Membership ID are required');
        }

        self::deleteMembership($this->groupId, $this->id);

        return $this;
    }

    /**
     * Make this member a moderator
     *
     * @return self
     * @throws CanvasApiException
     */
    public function makeModerator(): self
    {
        if (!$this->groupId || !$this->id) {
            throw new CanvasApiException('Group ID and Membership ID are required');
        }

        $updated = self::update($this->groupId, $this->id, ['moderator' => true]);
        // Update properties from the returned object
        foreach (get_object_vars($updated) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * Remove moderator status
     *
     * @return self
     * @throws CanvasApiException
     */
    public function removeModerator(): self
    {
        if (!$this->groupId || !$this->id) {
            throw new CanvasApiException('Group ID and Membership ID are required');
        }

        $updated = self::update($this->groupId, $this->id, ['moderator' => false]);
        // Update properties from the returned object
        foreach (get_object_vars($updated) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
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

    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    public function setGroupId(?int $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    public function getModerator(): ?bool
    {
        return $this->moderator;
    }

    public function setModerator(?bool $moderator): void
    {
        $this->moderator = $moderator;
    }

    /**
     * Get the user object for this membership
     *
     * This method implements lazy-loading: if the user object is not already loaded
     * but a userId is present, it will make an API call to fetch the user data.
     * To avoid N+1 queries when processing multiple memberships, consider using
     * Canvas API include parameters when fetching memberships.
     *
     * @return User|null The user object or null if no user is associated
     */
    public function getUser(): ?User
    {
        if ($this->user === null && $this->userId !== null) {
            self::checkApiClient();
            $this->user = User::find($this->userId);
        }
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): void
    {
        $this->group = $group;
    }

    public function getJustCreated(): ?bool
    {
        return $this->justCreated;
    }

    public function setJustCreated(?bool $justCreated): void
    {
        $this->justCreated = $justCreated;
    }

    /**
     * Get the API endpoint for this resource
     * Note: GroupMembership is a nested resource under Group
     * @return string
     * @throws CanvasApiException
     */
    protected static function getEndpoint(): string
    {
        throw new CanvasApiException(
            'GroupMembership does not support direct endpoint access. ' .
            'Use context-specific methods like createForGroup()'
        );
    }
}
