<?php

namespace CanvasLMS\Api\Accounts;

use CanvasLMS\Config;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Accounts\CreateAccountDTO;
use CanvasLMS\Dto\Accounts\UpdateAccountDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Dto\CalendarEvents\CreateCalendarEventDTO;
use CanvasLMS\Api\Rubrics\Rubric;
use CanvasLMS\Api\Rubrics\RubricAssociation;
use CanvasLMS\Dto\Rubrics\CreateRubricDTO;

/**
 * Account Class
 *
 * Represents an account in the Canvas LMS. Accounts are organizational units that contain
 * courses, sub-accounts, users, and other Canvas resources. This class provides methods
 * to create, update, find, and manage accounts and their hierarchical relationships.
 *
 * Usage:
 *
 * ```php
 * // Finding an account by ID
 * $account = Account::find(1);
 *
 * // Creating a new sub-account
 * $accountData = [
 *     'name' => 'Mathematics Department',
 *     'sisAccountId' => 'MATH_DEPT',
 *     'parentAccountId' => 1
 * ];
 * $account = Account::create($accountData);
 *
 * // Updating an existing account
 * $account->name = 'Mathematics and Statistics Department';
 * $account->save();
 *
 * // Getting sub-accounts
 * $subAccounts = $account->getSubAccounts();
 *
 * // Getting account settings
 * $settings = $account->getSettings();
 *
 * // Fetching manageable accounts
 * $manageableAccounts = Account::getManageableAccounts();
 * ```
 *
 * @package CanvasLMS\Api\Accounts
 */
class Account extends AbstractBaseApi
{
    /**
     * The ID of the Account object
     * @var int|null
     */
    public ?int $id = null;

    /**
     * The display name of the account
     * @var string|null
     */
    public ?string $name = null;

    /**
     * The UUID of the account
     * @var string|null
     */
    public ?string $uuid = null;

    /**
     * The account's parent ID, or null if this is the root account
     * @var int|null
     */
    public ?int $parentAccountId = null;

    /**
     * The ID of the root account, or null if this is the root account
     * @var int|null
     */
    public ?int $rootAccountId = null;

    /**
     * The storage quota for the account in megabytes
     * @var int|null
     */
    public ?int $defaultStorageQuotaMb = null;

    /**
     * The storage quota for a user in the account in megabytes
     * @var int|null
     */
    public ?int $defaultUserStorageQuotaMb = null;

    /**
     * The storage quota for a group in the account in megabytes
     * @var int|null
     */
    public ?int $defaultGroupStorageQuotaMb = null;

    /**
     * The default time zone of the account
     * @var string|null
     */
    public ?string $defaultTimeZone = null;

    /**
     * The account's identifier in the Student Information System
     * @var string|null
     */
    public ?string $sisAccountId = null;

    /**
     * The account's identifier in the Student Information System (alternative field)
     * @var string|null
     */
    public ?string $integrationId = null;

    /**
     * The id of the SIS import if created through SIS
     * @var int|null
     */
    public ?int $sisImportId = null;

    /**
     * The account's identifier that is sent as context_id in LTI launches
     * @var string|null
     */
    public ?string $ltiGuid = null;

    /**
     * The state of the account. Can be 'active' or 'deleted'
     * @var string|null
     */
    public ?string $workflowState = null;

    /**
     * The number of courses directly under the account
     * @var int|null
     */
    public ?int $courseCount = null;

    /**
     * The number of sub-accounts directly under the account
     * @var int|null
     */
    public ?int $subAccountCount = null;

    /**
     * Account settings
     * @var array<string, mixed>|null
     */
    public ?array $settings = null;

    /**
     * Create a new account
     *
     * @param array<string, mixed>|CreateAccountDTO $data Account data
     * @param int|null $parentAccountId Parent account ID (if creating sub-account)
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateAccountDTO $data, ?int $parentAccountId = null): self
    {
        self::checkApiClient();

        $parentId = $parentAccountId ?? Config::getAccountId();

        if (empty($parentId)) {
            throw new CanvasApiException("Parent account ID must be provided or set in Config");
        }

        if (is_array($data)) {
            $data = new CreateAccountDTO($data);
        }

        $endpoint = sprintf('accounts/%d/sub_accounts', $parentId);
        $response = self::$apiClient->post($endpoint, [
            'multipart' => $data->toApiArray()
        ]);

        $responseData = json_decode($response->getBody(), true);
        return new self($responseData);
    }

    /**
     * Find an account by ID
     *
     * @param int|string $id Account ID or SIS ID
     * @param array<string, mixed> $params Additional query parameters
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int|string $id, array $params = []): self
    {
        self::checkApiClient();

        $endpoint = sprintf('accounts/%s', $id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody(), true);

        return new self($responseData);
    }

    /**
     * Get a list of accounts the current user can view or manage
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        $endpoint = 'accounts';
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody(), true);

        return array_map(function ($item) {
            return new self($item);
        }, $responseData);
    }

    /**
     * Get accounts with pagination support
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkApiClient();

        $endpoint = 'accounts';
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Get a specific page of accounts
     *
     * @param array<string, mixed> $params Query parameters including page and per_page
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        $paginatedResponse = self::fetchAllPaginated($params);
        $data = self::convertPaginatedResponseToModels($paginatedResponse);

        return $paginatedResponse->toPaginationResult($data);
    }

    /**
     * Get all accounts from all pages
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        return self::fetchAllPagesAsModels('accounts', $params);
    }

    /**
     * Get accounts where the current user has permission to create or manage courses
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function getManageableAccounts(array $params = []): array
    {
        self::checkApiClient();

        $endpoint = 'manageable_accounts';
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody(), true);

        return array_map(function ($item) {
            return new self($item);
        }, $responseData);
    }

    /**
     * Get accounts where the current user has permission to create courses
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function getCourseCreationAccounts(array $params = []): array
    {
        self::checkApiClient();

        $endpoint = 'course_creation_accounts';
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody(), true);

        return array_map(function ($item) {
            return new self($item);
        }, $responseData);
    }

    /**
     * Get accounts that the current user can view through their admin course enrollments
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function getCourseAccounts(array $params = []): array
    {
        self::checkApiClient();

        $endpoint = 'course_accounts';
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody(), true);

        return array_map(function ($item) {
            return new self($item);
        }, $responseData);
    }

    /**
     * Get the root account
     *
     * @return self
     * @throws CanvasApiException
     */
    public static function getRootAccount(): self
    {
        $accountId = Config::getAccountId();
        if (empty($accountId)) {
            throw new CanvasApiException("Account ID must be set in Config");
        }

        return self::find($accountId);
    }

    /**
     * Update an account
     *
     * @param int $id Account ID
     * @param array<string, mixed>|UpdateAccountDTO $data Account data to update
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateAccountDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateAccountDTO($data);
        }

        $endpoint = sprintf('accounts/%d', $id);
        $response = self::$apiClient->put($endpoint, [
            'multipart' => $data->toApiArray()
        ]);

        $responseData = json_decode($response->getBody(), true);
        return new self($responseData);
    }

    /**
     * Save the current account instance
     *
     * @return bool
     * @throws CanvasApiException
     */
    public function save(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot save account without ID");
        }

        $dto = new UpdateAccountDTO($this->toDtoArray());
        $updated = self::update($this->id, $dto);

        // Update properties from the response
        foreach (get_object_vars($updated) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return true;
    }

    /**
     * Delete the account (only sub-accounts can be deleted)
     *
     * @return bool
     * @throws CanvasApiException
     */
    public function delete(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot delete account without ID");
        }

        if (!$this->parentAccountId) {
            throw new CanvasApiException("Cannot delete root account");
        }

        $endpoint = sprintf('accounts/%d/sub_accounts/%d', $this->parentAccountId, $this->id);
        $response = self::$apiClient->delete($endpoint);

        json_decode($response->getBody(), true);
        return true;
    }

    /**
     * Get sub-accounts of this account
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public function getSubAccounts(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException("Account ID is required");
        }

        $endpoint = sprintf('accounts/%d/sub_accounts', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody(), true);

        return array_map(function ($item) {
            return new self($item);
        }, $responseData);
    }

    /**
     * Get the parent account
     *
     * @return self|null
     * @throws CanvasApiException
     */
    public function getParentAccount(): ?self
    {
        if (!$this->parentAccountId) {
            return null;
        }

        return self::find($this->parentAccountId);
    }

    /**
     * Check if this is a root account
     *
     * @return bool
     */
    public function isRootAccount(): bool
    {
        return $this->parentAccountId === null && $this->rootAccountId === null;
    }

    /**
     * Get account settings
     *
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public function getSettings(): array
    {
        if (!$this->id) {
            throw new CanvasApiException("Account ID is required");
        }

        $endpoint = sprintf('accounts/%d/settings', $this->id);
        $response = self::$apiClient->get($endpoint);

        return json_decode($response->getBody(), true);
    }

    /**
     * Update account settings
     *
     * @param array<string, mixed> $settings Settings to update
     * @return bool
     * @throws CanvasApiException
     */
    public function updateSettings(array $settings): bool
    {
        if (!$this->id) {
            throw new CanvasApiException("Account ID is required");
        }

        $dto = new UpdateAccountDTO(['settings' => $settings]);
        $endpoint = sprintf('accounts/%d', $this->id);

        $response = self::$apiClient->put($endpoint, [
            'multipart' => $dto->toApiArray()
        ]);

        json_decode($response->getBody(), true);

        // Refresh settings
        $this->settings = $this->getSettings();

        return true;
    }

    /**
     * Get permissions for the calling user and this account
     *
     * @param array<int, string> $permissions List of permissions to check
     * @return array<string, bool>
     * @throws CanvasApiException
     */
    public function getPermissions(array $permissions = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException("Account ID is required");
        }

        $endpoint = sprintf('accounts/%d/permissions', $this->id);
        $params = [];

        if (!empty($permissions)) {
            $params['permissions'] = $permissions;
        }

        $response = self::$apiClient->get($endpoint, ['query' => $params]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the terms of service for this account
     *
     * @return array<string, mixed>|null
     * @throws CanvasApiException
     */
    public function getTermsOfService(): ?array
    {
        if (!$this->id) {
            throw new CanvasApiException("Account ID is required");
        }

        $endpoint = sprintf('accounts/%d/terms_of_service', $this->id);

        try {
            $response = self::$apiClient->get($endpoint);
            return json_decode($response->getBody(), true);
        } catch (CanvasApiException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Get help links for this account
     *
     * @return array<string, mixed>|null
     * @throws CanvasApiException
     */
    public function getHelpLinks(): ?array
    {
        if (!$this->id) {
            throw new CanvasApiException("Account ID is required");
        }

        $endpoint = sprintf('accounts/%d/help_links', $this->id);

        try {
            $response = self::$apiClient->get($endpoint);
            return json_decode($response->getBody(), true);
        } catch (CanvasApiException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Get active courses in this account
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, Course>
     * @throws CanvasApiException
     */
    public function getCourses(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException("Account ID is required");
        }

        $endpoint = sprintf('accounts/%d/courses', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody(), true);

        // Convert to Course objects
        return array_map(function ($courseData) {
            return new Course($courseData);
        }, $responseData);
    }

    /**
     * Get account ID getter
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set account ID
     *
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get account name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set account name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get SIS account ID
     *
     * @return string|null
     */
    public function getSisAccountId(): ?string
    {
        return $this->sisAccountId;
    }

    /**
     * Set SIS account ID
     *
     * @param string|null $sisAccountId
     * @return self
     */
    public function setSisAccountId(?string $sisAccountId): self
    {
        $this->sisAccountId = $sisAccountId;
        return $this;
    }

    /**
     * Get calendar events for this account
     *
     * @param array<string, mixed> $params Query parameters
     * @return CalendarEvent[]
     * @throws CanvasApiException
     */
    public function getCalendarEvents(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Account ID is required to get calendar events');
        }

        $params['context_codes'] = [sprintf('account_%d', $this->id)];
        return CalendarEvent::fetchAll($params);
    }

    /**
     * Get paginated calendar events for this account
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function getCalendarEventsPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException('Account ID is required to get calendar events');
        }

        $params['context_codes'] = [sprintf('account_%d', $this->id)];
        return CalendarEvent::fetchAllPaginated($params);
    }

    /**
     * Create a calendar event for this account
     *
     * @param CreateCalendarEventDTO|array<string, mixed> $data
     * @return CalendarEvent
     * @throws CanvasApiException
     */
    public function createCalendarEvent($data): CalendarEvent
    {
        if (!$this->id) {
            throw new CanvasApiException('Account ID is required to create calendar event');
        }

        $dto = $data instanceof CreateCalendarEventDTO ? $data : new CreateCalendarEventDTO($data);
        $dto->contextCode = sprintf('account_%d', $this->id);
        return CalendarEvent::create($dto);
    }

    /**
     * Get rubrics for this account
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, Rubric>
     * @throws CanvasApiException
     */
    public function getRubrics(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Account ID is required to get rubrics');
        }

        $endpoint = sprintf('accounts/%d/rubrics', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody(), true);

        return array_map(function ($rubricData) {
            return new Rubric($rubricData);
        }, $responseData);
    }

    /**
     * Get paginated rubrics for this account
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function getRubricsPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException('Account ID is required to get rubrics');
        }

        $endpoint = sprintf('accounts/%d/rubrics', $this->id);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Create a rubric for this account
     *
     * @param CreateRubricDTO|array<string, mixed> $data
     * @return Rubric
     * @throws CanvasApiException
     */
    public function createRubric($data): Rubric
    {
        if (!$this->id) {
            throw new CanvasApiException('Account ID is required to create rubric');
        }

        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateRubricDTO($data);
        }

        $endpoint = sprintf('accounts/%d/rubrics', $this->id);
        $response = self::$apiClient->post($endpoint, $data->toApiArray());
        $responseData = json_decode($response->getBody(), true);

        // Handle non-standard response format
        if (isset($responseData['rubric'])) {
            $rubric = new Rubric($responseData['rubric']);
            if (isset($responseData['rubric_association'])) {
                $rubric->association = new RubricAssociation($responseData['rubric_association']);
            }
            return $rubric;
        }

        // Fallback to standard response
        return new Rubric($responseData);
    }

    /**
     * Find a rubric by ID in this account
     *
     * @param int $rubricId The rubric ID
     * @param array<string, mixed> $params Query parameters
     * @return Rubric
     * @throws CanvasApiException
     */
    public function findRubric(int $rubricId, array $params = []): Rubric
    {
        if (!$this->id) {
            throw new CanvasApiException('Account ID is required to find rubric');
        }

        self::checkApiClient();

        $endpoint = sprintf('accounts/%d/rubrics/%d', $this->id, $rubricId);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode($response->getBody(), true);

        return new Rubric($data);
    }
}
