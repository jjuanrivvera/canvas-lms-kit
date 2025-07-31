<?php

namespace CanvasLMS\Api\Admins;

use CanvasLMS\Config;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Accounts\Account;
use CanvasLMS\Dto\Admins\CreateAdminDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * Admin Class
 *
 * Represents an admin user in the Canvas LMS. Admins are users with specific
 * permissions to manage accounts and their resources. This class provides methods
 * to create, manage, and remove admin privileges for users at the account level.
 *
 * Admin operations always require an account context, as admins are assigned
 * permissions within specific accounts in the Canvas hierarchy.
 *
 * Usage:
 *
 * ```php
 * // Creating a new admin for an account
 * $adminData = [
 *     'userId' => 123,
 *     'role' => 'AccountAdmin',
 *     'sendConfirmation' => true
 * ];
 * $admin = Admin::create($adminData, 1); // Account ID 1
 *
 * // Finding all admins for an account
 * $admins = Admin::fetchAll(1);
 *
 * // Removing admin privileges
 * $admin = Admin::find(123, 1); // User ID 123, Account ID 1
 * $admin->delete();
 *
 * // Getting available admin roles
 * $roles = Admin::getSelfAdminRoles(1);
 * ```
 *
 * @package CanvasLMS\Api\Admins
 */
class Admin extends AbstractBaseApi
{
    /**
     * The ID of the User object who is the admin
     * @var int|null
     */
    public ?int $id = null;

    /**
     * The account ID this admin is associated with
     * @var int|null
     */
    public ?int $accountId = null;

    /**
     * The admin's user name
     * @var string|null
     */
    public ?string $name = null;

    /**
     * The admin's email address
     * @var string|null
     */
    public ?string $email = null;

    /**
     * The admin's login ID
     * @var string|null
     */
    public ?string $loginId = null;

    /**
     * The role of the admin (e.g., 'AccountAdmin', 'SubAccountAdmin')
     * @var string|null
     */
    public ?string $role = null;

    /**
     * The ID of the role
     * @var int|null
     */
    public ?int $roleId = null;

    /**
     * The workflow state of the admin
     * @var string|null
     */
    public ?string $workflowState = null;

    /**
     * Additional user details (optional, based on include parameters)
     * @var array<string, mixed>|null
     */
    public ?array $user = null;

    /**
     * Create a new admin for an account
     *
     * @param array<string, mixed>|CreateAdminDTO $data Admin data
     * @param int|null $accountId Account ID (required)
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateAdminDTO $data, ?int $accountId = null): self
    {
        self::checkApiClient();

        $accountId = $accountId ?? Config::getAccountId();

        if (empty($accountId)) {
            throw new CanvasApiException("Account ID must be provided or set in Config");
        }

        if (is_array($data)) {
            $data = new CreateAdminDTO($data);
        }

        $endpoint = sprintf('accounts/%d/admins', $accountId);
        $response = self::$apiClient->post($endpoint, [
            'multipart' => $data->toApiArray()
        ]);

        $responseData = json_decode($response->getBody(), true);
        $admin = new self($responseData);
        $admin->accountId = $accountId;
        return $admin;
    }

    /**
     * Find an admin by user ID in a specific account
     * Note: This implementation differs from the interface as it requires account context
     *
     * @param int $userId User ID of the admin
     * @param array<string, mixed> $params Optional parameters including account_id
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $userId, array $params = []): self
    {
        self::checkApiClient();

        $accountId = $params['account_id'] ?? Config::getAccountId();

        if (empty($accountId)) {
            throw new CanvasApiException("Account ID must be provided or set in Config");
        }

        // First, we need to get all admins and find the specific one
        $admins = self::fetchAll(['account_id' => $accountId, 'user_id' => [$userId]]);

        if (empty($admins)) {
            throw new CanvasApiException("Admin with user ID {$userId} not found in account {$accountId}");
        }

        return $admins[0];
    }

    /**
     * Get a list of account admins
     *
     * @param array<string, mixed> $params Query parameters (can include 'account_id')
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        $accountId = $params['account_id'] ?? Config::getAccountId();
        unset($params['account_id']); // Remove from query params

        if (empty($accountId)) {
            throw new CanvasApiException("Account ID must be provided or set in Config");
        }

        $endpoint = sprintf('accounts/%d/admins', $accountId);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody(), true);

        return array_map(function ($item) use ($accountId) {
            $admin = new self($item);
            $admin->accountId = $accountId;
            return $admin;
        }, $responseData);
    }

    /**
     * Get admins with pagination support
     *
     * @param array<string, mixed> $params Query parameters (can include 'account_id')
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkApiClient();

        $accountId = $params['account_id'] ?? Config::getAccountId();
        unset($params['account_id']); // Remove from query params

        if (empty($accountId)) {
            throw new CanvasApiException("Account ID must be provided or set in Config");
        }

        $endpoint = sprintf('accounts/%d/admins', $accountId);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Get a specific page of admins
     *
     * @param array<string, mixed> $params Query parameters including page, per_page, and account_id
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        $accountId = $params['account_id'] ?? Config::getAccountId();
        $paginatedResponse = self::fetchAllPaginated($params);
        $data = self::convertPaginatedResponseToModels($paginatedResponse);

        // Add account ID to each admin
        foreach ($data as $admin) {
            $admin->accountId = $accountId;
        }

        return $paginatedResponse->toPaginationResult($data);
    }

    /**
     * Get all admins from all pages
     *
     * @param array<string, mixed> $params Query parameters (can include 'account_id')
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        self::checkApiClient();

        $accountId = $params['account_id'] ?? Config::getAccountId();
        unset($params['account_id']); // Remove from query params

        if (empty($accountId)) {
            throw new CanvasApiException("Account ID must be provided or set in Config");
        }

        $endpoint = sprintf('accounts/%d/admins', $accountId);
        $admins = self::fetchAllPagesAsModels($endpoint, $params);

        // Add account ID to each admin
        foreach ($admins as $admin) {
            $admin->accountId = $accountId;
        }

        return $admins;
    }

    /**
     * Remove admin privileges (delete admin)
     *
     * @return bool
     * @throws CanvasApiException
     */
    public function delete(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot delete admin without user ID");
        }

        if (!$this->accountId) {
            throw new CanvasApiException("Cannot delete admin without account ID");
        }

        $endpoint = sprintf('accounts/%d/admins/%d', $this->accountId, $this->id);
        $response = self::$apiClient->delete($endpoint);

        json_decode($response->getBody(), true);
        return true;
    }

    /**
     * Get a list of roles that can be assigned to users in the current account
     *
     * @param int|null $accountId Account ID
     * @return array<int, array<string, mixed>>
     * @throws CanvasApiException
     */
    public static function getSelfAdminRoles(?int $accountId = null): array
    {
        self::checkApiClient();

        $accountId = $accountId ?? Config::getAccountId();

        if (empty($accountId)) {
            throw new CanvasApiException("Account ID must be provided or set in Config");
        }

        $endpoint = sprintf('accounts/%d/admins/self/roles', $accountId);
        $response = self::$apiClient->get($endpoint);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the account this admin belongs to
     *
     * @return Account|null
     * @throws CanvasApiException
     */
    public function getAccount(): ?Account
    {
        if (!$this->accountId) {
            return null;
        }

        return Account::find($this->accountId);
    }

    /**
     * Get admin user ID
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set admin user ID
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
     * Get account ID
     *
     * @return int|null
     */
    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    /**
     * Set account ID
     *
     * @param int $accountId
     * @return self
     */
    public function setAccountId(int $accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }

    /**
     * Get admin name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set admin name
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
     * Get admin email
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set admin email
     *
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get admin role
     *
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * Set admin role
     *
     * @param string $role
     * @return self
     */
    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Get admin role ID
     *
     * @return int|null
     */
    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    /**
     * Set admin role ID
     *
     * @param int $roleId
     * @return self
     */
    public function setRoleId(int $roleId): self
    {
        $this->roleId = $roleId;
        return $this;
    }
}
