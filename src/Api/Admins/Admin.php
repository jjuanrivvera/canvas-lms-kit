<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Admins;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Accounts\Account;
use CanvasLMS\Config;
use CanvasLMS\Dto\Admins\CreateAdminDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;

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
 * $admins = Admin::get(['account_id' => 1]);
 *
 * // Get all admins from all pages
 * $allAdmins = Admin::all(['account_id' => 1]);
 *
 * // Removing admin privileges
 * $admin = Admin::find(123, ['account_id' => 1]); // User ID 123, Account ID 1
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
     *
     * @var int|null
     */
    public ?int $id = null;

    /**
     * The account ID this admin is associated with
     *
     * @var int|null
     */
    public ?int $accountId = null;

    /**
     * The admin's user name
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * The admin's email address
     *
     * @var string|null
     */
    public ?string $email = null;

    /**
     * The admin's login ID
     *
     * @var string|null
     */
    public ?string $loginId = null;

    /**
     * The role of the admin (e.g., 'AccountAdmin', 'SubAccountAdmin')
     *
     * @var string|null
     */
    public ?string $role = null;

    /**
     * The ID of the role
     *
     * @var int|null
     */
    public ?int $roleId = null;

    /**
     * The workflow state of the admin
     *
     * @var string|null
     */
    public ?string $workflowState = null;

    /**
     * Additional user details (optional, based on include parameters)
     *
     * @var array<string, mixed>|null
     */
    public ?array $user = null;

    /**
     * Create a new admin for an account
     *
     * @param array<string, mixed>|CreateAdminDTO $data Admin data
     * @param int|null $accountId Account ID (required)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function create(array|CreateAdminDTO $data, ?int $accountId = null): self
    {
        self::checkApiClient();

        $accountId = $accountId ?? Config::getAccountId();

        if (empty($accountId)) {
            throw new CanvasApiException('Account ID must be provided or set in Config');
        }

        if (is_array($data)) {
            $data = new CreateAdminDTO($data);
        }

        $endpoint = sprintf('accounts/%d/admins', $accountId);
        $response = self::getApiClient()->post($endpoint, [
            'multipart' => $data->toApiArray(),
        ]);

        $responseData = self::parseJsonResponse($response);
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
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function find(int $userId, array $params = []): self
    {
        self::checkApiClient();

        $accountId = $params['account_id'] ?? Config::getAccountId();

        if (empty($accountId)) {
            throw new CanvasApiException('Account ID must be provided or set in Config');
        }

        // First, we need to get all admins and find the specific one
        $admins = self::get(['account_id' => $accountId, 'user_id' => [$userId]]);

        if (empty($admins)) {
            throw new CanvasApiException("Admin with user ID {$userId} not found in account {$accountId}");
        }

        return $admins[0];
    }

    /**
     * Get first page of admins
     *
     * @param array<string, mixed> $params Query parameters (can include 'account_id')
     *
     * @throws CanvasApiException
     *
     * @return array<int, self>
     */
    public static function get(array $params = []): array
    {
        self::checkApiClient();

        $accountId = $params['account_id'] ?? Config::getAccountId();
        unset($params['account_id']); // Remove from query params

        if (empty($accountId)) {
            throw new CanvasApiException('Account ID must be provided or set in Config');
        }

        $endpoint = sprintf('accounts/%d/admins', $accountId);
        $response = self::getApiClient()->get($endpoint, ['query' => $params]);
        $responseData = self::parseJsonResponse($response);

        return array_map(function ($item) use ($accountId) {
            $admin = new self($item);
            $admin->accountId = $accountId;

            return $admin;
        }, $responseData);
    }

    /**
     * Get all admins from all pages
     *
     * @param array<string, mixed> $params Query parameters (can include 'account_id')
     *
     * @throws CanvasApiException
     *
     * @return array<int, self>
     */
    public static function all(array $params = []): array
    {
        self::checkApiClient();

        $accountId = $params['account_id'] ?? Config::getAccountId();
        unset($params['account_id']); // Remove from query params

        if (empty($accountId)) {
            throw new CanvasApiException('Account ID must be provided or set in Config');
        }

        $endpoint = sprintf('accounts/%d/admins', $accountId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        $admins = [];
        foreach ($allData as $item) {
            $admin = new self($item);
            $admin->accountId = $accountId;
            $admins[] = $admin;
        }

        return $admins;
    }

    /**
     * Get paginated admins
     *
     * @param array<string, mixed> $params Query parameters (can include 'account_id')
     *
     * @throws CanvasApiException
     *
     * @return PaginationResult
     */
    public static function paginate(array $params = []): PaginationResult
    {
        self::checkApiClient();

        $accountId = $params['account_id'] ?? Config::getAccountId();
        unset($params['account_id']); // Remove from query params

        if (empty($accountId)) {
            throw new CanvasApiException('Account ID must be provided or set in Config');
        }

        $endpoint = sprintf('accounts/%d/admins', $accountId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        // Convert data to models with account ID
        $data = [];
        foreach ($paginatedResponse->getJsonData() as $item) {
            $admin = new self($item);
            $admin->accountId = $accountId;
            $data[] = $admin;
        }

        return $paginatedResponse->toPaginationResult($data);
    }

    /**
     * Remove admin privileges (delete admin)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot delete admin without user ID');
        }

        if (!$this->accountId) {
            throw new CanvasApiException('Cannot delete admin without account ID');
        }

        $endpoint = sprintf('accounts/%d/admins/%d', $this->accountId, $this->id);
        $response = self::getApiClient()->delete($endpoint);

        self::parseJsonResponse($response);

        return $this;
    }

    /**
     * Get a list of roles that can be assigned to users in the current account
     *
     * @param int|null $accountId Account ID
     *
     * @throws CanvasApiException
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getSelfAdminRoles(?int $accountId = null): array
    {
        self::checkApiClient();

        $accountId = $accountId ?? Config::getAccountId();

        if (empty($accountId)) {
            throw new CanvasApiException('Account ID must be provided or set in Config');
        }

        $endpoint = sprintf('accounts/%d/admins/self/roles', $accountId);
        $response = self::getApiClient()->get($endpoint);

        return self::parseJsonResponse($response);
    }

    /**
     * Get the account this admin belongs to
     *
     * @throws CanvasApiException
     *
     * @return Account|null
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
     * @return self
     */
    public function setRoleId(int $roleId): self
    {
        $this->roleId = $roleId;

        return $this;
    }

    /**
     * Get the API endpoint for this resource
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        $accountId = Config::getAccountId();
        if (empty($accountId)) {
            throw new CanvasApiException('Account ID must be set in Config for Admin operations');
        }

        return "accounts/{$accountId}/admins";
    }
}
