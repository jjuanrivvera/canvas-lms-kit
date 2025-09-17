<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Logins;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\Logins\CreateLoginDTO;
use CanvasLMS\Dto\Logins\UpdateLoginDTO;
use CanvasLMS\Dto\Logins\PasswordResetDTO;
use CanvasLMS\Config;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * Canvas LMS Login API
 *
 * API for creating and viewing user logins (pseudonyms) under an account.
 * Logins represent user authentication credentials associated with accounts.
 */
class Login extends AbstractBaseApi
{
    // Workflow states
    public const STATE_ACTIVE = 'active';
    public const STATE_SUSPENDED = 'suspended';

    // Declared user types
    public const TYPE_ADMINISTRATIVE = 'administrative';
    public const TYPE_OBSERVER = 'observer';
    public const TYPE_STAFF = 'staff';
    public const TYPE_STUDENT = 'student';
    public const TYPE_STUDENT_OTHER = 'student_other';
    public const TYPE_TEACHER = 'teacher';

    // Properties (camelCase following SDK convention)
    public ?int $accountId = null;
    public ?int $id = null;
    public ?string $sisUserId = null;
    public ?string $integrationId = null;
    public ?string $uniqueId = null;
    public ?int $userId = null;
    public ?int $authenticationProviderId = null;
    public ?string $authenticationProviderType = null;
    public ?string $workflowState = null;
    public ?string $declaredUserType = null;
    public ?string $createdAt = null;

    /**
     * Get endpoint for the API calls (required by AbstractBaseApi)
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        // Default to account context for the base endpoint
        $accountId = Config::getAccountId();
        return sprintf('accounts/%d/logins', $accountId);
    }

    /**
     * Get first page of logins (implements ApiInterface)
     * Uses account context by default
     *
     * @param array<string, mixed> $params Optional query parameters
     * @return array<Login> Array of Login objects
     */
    public static function get(array $params = []): array
    {
        self::checkApiClient();
        $endpoint = self::getEndpoint();
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = self::parseJsonResponse($response);

        return array_map(fn(array $item) => new self($item), $data);
    }


    /**
     * Find a specific login by ID (implements ApiInterface)
     * Uses account context by default
     *
     * @param int $id The login ID
     * @return self The Login instance
     */
    public static function find(int $id, array $params = []): self
    {
        // Canvas doesn't have direct login endpoint by ID
        // We need to fetch all and filter
        $logins = self::get();

        foreach ($logins as $login) {
            if ($login->id === $id) {
                return $login;
            }
        }

        throw new CanvasApiException("Login with ID {$id} not found");
    }


    /**
     * Create a new login for an existing user
     *
     * @param int $accountId The account ID to create the login under
     * @param array<string, mixed>|CreateLoginDTO $data Login creation data
     * @return self The created Login instance
     */
    public static function create(int $accountId, array|CreateLoginDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateLoginDTO($data);
        }

        $endpoint = sprintf('accounts/%d/logins', $accountId);
        $response = self::$apiClient->post($endpoint, [
            'multipart' => $data->toApiArray()
        ]);
        $responseData = self::parseJsonResponse($response);

        return new self($responseData);
    }

    /**
     * Find a specific login by account and login ID
     *
     * @param int $accountId The account ID
     * @param int $loginId The login ID
     * @return self The Login instance
     */
    public static function findByAccountAndId(int $accountId, int $loginId): self
    {
        // Canvas doesn't have a direct endpoint for individual login fetch
        // We'll need to get all account logins and filter
        $logins = self::fetchByContext('accounts', $accountId);

        foreach ($logins as $login) {
            if ($login->id === $loginId) {
                return $login;
            }
        }

        throw new CanvasApiException("Login with ID {$loginId} not found in account {$accountId}");
    }

    /**
     * Update this login
     *
     * @param array<string, mixed>|UpdateLoginDTO $data Login update data
     * @return self The updated Login instance
     */
    public function update(array|UpdateLoginDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateLoginDTO($data);
        }

        if (!$this->accountId || !$this->id) {
            throw new CanvasApiException('Login must have accountId and id to update');
        }

        $endpoint = sprintf('accounts/%d/logins/%d', $this->accountId, $this->id);
        $response = self::$apiClient->put($endpoint, [
            'multipart' => $data->toApiArray()
        ]);
        $responseData = self::parseJsonResponse($response);

        // Update current instance using parent constructor pattern
        $updatedInstance = new self($responseData);

        // Copy properties to current instance for consistency
        foreach (get_object_vars($updatedInstance) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Delete this login
     * Note: Canvas requires user context for login deletion
     *
     * @return array<string, mixed> The deleted login data
     */
    public function delete(): array
    {
        self::checkApiClient();

        if (!$this->userId || !$this->id) {
            throw new CanvasApiException('Login must have userId and id to delete');
        }

        $endpoint = sprintf('users/%d/logins/%d', $this->userId, $this->id);
        $response = self::$apiClient->delete($endpoint);

        return self::parseJsonResponse($response);
    }

    /**
     * Initiate password recovery flow for a user
     *
     * @param string|array<string, mixed>|PasswordResetDTO $email User email or data array
     * @return array<string, mixed> Recovery request status
     */
    public static function resetPassword(string|array|PasswordResetDTO $email): array
    {
        self::checkApiClient();

        if (is_string($email)) {
            $data = new PasswordResetDTO(['email' => $email]);
        } elseif (is_array($email)) {
            $data = new PasswordResetDTO($email);
        } else {
            $data = $email;
        }

        $response = self::$apiClient->post('users/reset_password', [
            'multipart' => $data->toApiArray()
        ]);

        return self::parseJsonResponse($response);
    }

    /**
     * List logins for a specific context
     *
     * @param string $contextType 'accounts' or 'users'
     * @param int $contextId Account or User ID
     * @param array<string, mixed> $params Query parameters
     * @return array<Login>
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        self::checkApiClient();
        $endpoint = sprintf('%s/%d/logins', $contextType, $contextId);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = self::parseJsonResponse($response);
        return array_map(fn(array $item) => new self($item), $data);
    }

    /**
     * Get paginated logins for a specific context
     *
     * @param string $contextType 'accounts' or 'users'
     * @param int $contextId Account or User ID
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     */
    public static function fetchByContextPaginated(
        string $contextType,
        int $contextId,
        array $params = []
    ): PaginatedResponse {
        $endpoint = sprintf('%s/%d/logins', $contextType, $contextId);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Check if this login is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->workflowState === self::STATE_ACTIVE;
    }

    /**
     * Check if this login is suspended
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this->workflowState === self::STATE_SUSPENDED;
    }

    /**
     * Get the declared user type display name
     *
     * @return string|null
     */
    public function getDeclaredUserTypeDisplay(): ?string
    {
        return match ($this->declaredUserType) {
            self::TYPE_ADMINISTRATIVE => 'Administrative',
            self::TYPE_OBSERVER => 'Observer',
            self::TYPE_STAFF => 'Staff',
            self::TYPE_STUDENT => 'Student',
            self::TYPE_STUDENT_OTHER => 'Student (Other)',
            self::TYPE_TEACHER => 'Teacher',
            default => $this->declaredUserType
        };
    }
}
