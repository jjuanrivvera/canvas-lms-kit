<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Groups;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Config;
use CanvasLMS\Dto\Groups\CreateGroupDTO;
use CanvasLMS\Dto\Groups\CreateGroupMembershipDTO;
use CanvasLMS\Dto\Groups\UpdateGroupDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Api\ContentMigrations\ContentMigration;
use CanvasLMS\Dto\ContentMigrations\CreateContentMigrationDTO;

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
     * Whether the current user is following this group
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
     * The course or account name that the group belongs to
     */
    public ?string $contextName = null;

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
     * Optional list of users that are members in the group
     * Returned only if include[]=users
     * @var array<mixed>|null
     */
    public ?array $users = null;

    /**
     * Indicates whether this group category is non-collaborative
     */
    public ?bool $nonCollaborative = null;

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
     * Get the API endpoint for this resource
     * @return string
     */
    protected static function getEndpoint(): string
    {
        $accountId = Config::getAccountId();
        return sprintf('accounts/%d/groups', $accountId);
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
        return self::fetchAllPagesAsModels(sprintf('%s/%d/groups', $contextType, $contextId), $params);
    }

    /**
     * Get paginated groups for a specific context
     *
     * @param string $contextType 'accounts' or 'courses'
     * @param int $contextId Account or Course ID
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchByContextPaginated(
        string $contextType,
        int $contextId,
        array $params = []
    ): PaginatedResponse {
        return self::getPaginatedResponse(sprintf('%s/%d/groups', $contextType, $contextId), $params);
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
        return self::fetchAllPagesAsModels(sprintf('users/%d/groups', $userId), $params);
    }

    /**
     * Get paginated groups for a specific user
     *
     * @param int $userId User ID
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchUserGroupsPaginated(int $userId, array $params = []): PaginatedResponse
    {
        return self::getPaginatedResponse(sprintf('users/%d/groups', $userId), $params);
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
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        if ($this->id) {
            $dto = new UpdateGroupDTO($this->toDtoArray());
            $updated = self::update($this->id, $dto);
            $this->populate(get_object_vars($updated));
        } else {
            $dto = new CreateGroupDTO($this->toDtoArray());
            $created = self::create($dto);
            $this->populate(get_object_vars($created));
        }
        return $this;
    }

    /**
     * Delete the group
     *
     * @return self
     * @throws CanvasApiException
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required for deletion');
        }

        self::checkApiClient();
        $endpoint = sprintf('groups/%d', $this->id);
        self::$apiClient->delete($endpoint);
        return $this;
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
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->fetchAllPages();

        return array_map(fn($data) => new User($data), $allData);
    }

    /**
     * Get paginated group members
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function membersPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to fetch group members');
        }

        return self::getPaginatedResponse(sprintf('groups/%d/users', $this->id), $params);
    }

    /**
     * Add user to group
     *
     * @param int $userId User ID to add
     * @return bool
     * @throws CanvasApiException
     * @deprecated Use createMembership() for more control
     */
    public function addUser(int $userId): bool
    {
        try {
            $membership = $this->createMembership(['user_id' => $userId]);
            return $membership !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a membership in this group
     *
     * @param array<string, mixed>|CreateGroupMembershipDTO $data Membership data
     * @return GroupMembership
     * @throws CanvasApiException
     */
    public function createMembership(array|CreateGroupMembershipDTO $data): GroupMembership
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to create membership');
        }

        return GroupMembership::create($this->id, $data);
    }

    /**
     * Get memberships for this group
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<GroupMembership>
     * @throws CanvasApiException
     */
    public function memberships(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to fetch memberships');
        }

        return GroupMembership::fetchAllForGroup($this->id, $params);
    }

    /**
     * Get paginated memberships for this group
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function membershipsPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to fetch memberships');
        }

        return GroupMembership::fetchAllPaginated($this->id, $params);
    }

    /**
     * Invite users to this group
     *
     * @param array<string> $emails Email addresses to invite
     * @return self
     * @throws CanvasApiException
     */
    public function invite(array $emails): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to invite users');
        }

        // Validate email addresses
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new CanvasApiException("Invalid email address: {$email}");
            }
        }

        self::checkApiClient();

        $endpoint = sprintf('groups/%d/invite', $this->id);
        $data = [];
        foreach ($emails as $email) {
            $data[] = ['name' => 'invitees[]', 'contents' => $email];
        }
        self::$apiClient->post($endpoint, ['multipart' => $data]);
        return $this;
    }

    /**
     * Remove user from group
     *
     * Note: Canvas API doesn't provide a direct endpoint to remove a user by user ID.
     * This method fetches memberships to find the correct membership ID for deletion.
     * For better performance when removing multiple users, consider fetching all
     * memberships once and managing them locally.
     *
     * @param int $userId User ID to remove
     * @return self
     * @throws CanvasApiException
     */
    public function removeUser(int $userId): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to remove user from group');
        }

        // Find the user's membership
        $memberships = $this->memberships(['filter_states[]' => 'accepted']);
        $membershipToDelete = null;

        foreach ($memberships as $membership) {
            if ($membership->userId == $userId) {
                $membershipToDelete = $membership;
                break;
            }
        }

        if (!$membershipToDelete) {
            throw new CanvasApiException('User not found in group');
        }

        // Delete the membership
        $membershipToDelete->delete();
        return $this;
    }

    /**
     * Get group activity stream
     *
     * Returns an array of activity stream items which may include:
     * - Discussion topics (type: 'DiscussionTopic')
     * - Announcements (type: 'Announcement')
     * - Conversations (type: 'Conversation')
     * - Messages (type: 'Message')
     * - Submissions (type: 'Submission')
     * - Conference invitations (type: 'WebConference')
     * - Collaborations (type: 'Collaboration')
     * - AssessmentRequests (type: 'AssessmentRequest')
     *
     * Each item contains: id, title, message, type, read_state, created_at, updated_at,
     * and context-specific fields based on the activity type.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array{
     *   0?: array{
     *     id: int,
     *     title: string,
     *     message: string,
     *     type: string,
     *     read_state: bool,
     *     created_at: string,
     *     updated_at: string,
     *     ...
     *   }
     * } Array of activity stream items
     * @throws CanvasApiException
     */
    public function activityStream(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to fetch activity stream');
        }

        self::checkApiClient();

        $endpoint = sprintf('groups/%d/activity_stream', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get group activity stream summary
     *
     * Returns a summary of the group's activity stream with counts by type.
     *
     * @return array{
     *   type: string,
     *   unread_count: int,
     *   count: int
     * }[] Array of activity type summaries
     * @throws CanvasApiException
     */
    public function activityStreamSummary(): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to fetch activity stream summary');
        }

        self::checkApiClient();

        $endpoint = sprintf('groups/%d/activity_stream/summary', $this->id);
        $response = self::$apiClient->get($endpoint);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get permissions for the current user
     *
     * @param array<string> $permissions Optional array of permission names to check
     * @return array<string, bool>
     * @throws CanvasApiException
     */
    public function permissions(array $permissions = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Group ID is required to fetch permissions');
        }

        self::checkApiClient();

        $endpoint = sprintf('groups/%d/permissions', $this->id);
        $params = [];
        if (!empty($permissions)) {
            $params['permissions'] = $permissions;
        }

        $response = self::$apiClient->get($endpoint, ['query' => $params]);

        return json_decode($response->getBody()->getContents(), true);
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

    public function getContextName(): ?string
    {
        return $this->contextName;
    }

    public function setContextName(?string $contextName): void
    {
        $this->contextName = $contextName;
    }

    /**
     * @return array<mixed>|null
     */
    public function getUsers(): ?array
    {
        return $this->users;
    }

    /**
     * @param array<mixed>|null $users
     */
    public function setUsers(?array $users): void
    {
        $this->users = $users;
    }

    public function getNonCollaborative(): ?bool
    {
        return $this->nonCollaborative;
    }

    public function setNonCollaborative(?bool $nonCollaborative): void
    {
        $this->nonCollaborative = $nonCollaborative;
    }

    public function getContextType(): ?string
    {
        return $this->contextType;
    }

    public function setContextType(?string $contextType): void
    {
        $this->contextType = $contextType;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): void
    {
        $this->role = $role;
    }

    public function getGroupCategoryId(): ?int
    {
        return $this->groupCategoryId;
    }

    public function setGroupCategoryId(?int $groupCategoryId): void
    {
        $this->groupCategoryId = $groupCategoryId;
    }

    /**
     * @return array<string, bool>|null
     */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    /**
     * @param array<string, bool>|null $permissions
     */
    public function setPermissions(?array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function getJoinLevel(): ?string
    {
        return $this->joinLevel;
    }

    public function setJoinLevel(?string $joinLevel): void
    {
        $this->joinLevel = $joinLevel;
    }

    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * Get content migrations for this group
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<ContentMigration>
     * @throws CanvasApiException
     */
    public function contentMigrations(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Group ID is required to fetch content migrations');
        }

        return ContentMigration::fetchByContext('groups', $this->id, $params);
    }

    /**
     * Get a specific content migration for this group
     *
     * @param int $migrationId Content migration ID
     * @return ContentMigration
     * @throws CanvasApiException
     */
    public function contentMigration(int $migrationId): ContentMigration
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Group ID is required to fetch content migration');
        }

        return ContentMigration::findByContext('groups', $this->id, $migrationId);
    }

    /**
     * Create a content migration for this group
     *
     * @param array<string, mixed>|CreateContentMigrationDTO $data Migration data
     * @return ContentMigration
     * @throws CanvasApiException
     */
    public function createContentMigration(array|CreateContentMigrationDTO $data): ContentMigration
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Group ID is required to create content migration');
        }

        return ContentMigration::createInContext('groups', $this->id, $data);
    }

    /**
     * Import content from a Common Cartridge file
     *
     * @param string $filePath Path to the .imscc file
     * @param array<string, mixed> $options Additional options
     * @return ContentMigration
     * @throws CanvasApiException
     */
    public function importCommonCartridge(string $filePath, array $options = []): ContentMigration
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Group ID is required to import content');
        }

        $migration = ContentMigration::createInContext('groups', $this->id, array_merge($options, [
            'migration_type' => ContentMigration::TYPE_COMMON_CARTRIDGE,
            'pre_attachment' => [
                'name' => basename($filePath),
                'size' => filesize($filePath)
            ]
        ]));

        if ($migration->isFileUploadPending()) {
            $migration->processFileUpload($filePath);
        }

        return $migration;
    }

    /**
     * Import content from a ZIP file
     *
     * @param string $filePath Path to the .zip file
     * @param array<string, mixed> $options Additional options
     * @return ContentMigration
     * @throws CanvasApiException
     */
    public function importZipFile(string $filePath, array $options = []): ContentMigration
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Group ID is required to import content');
        }

        $migration = ContentMigration::createInContext('groups', $this->id, array_merge($options, [
            'migration_type' => ContentMigration::TYPE_ZIP_FILE,
            'pre_attachment' => [
                'name' => basename($filePath),
                'size' => filesize($filePath)
            ]
        ]));

        if ($migration->isFileUploadPending()) {
            $migration->processFileUpload($filePath);
        }

        return $migration;
    }
}
