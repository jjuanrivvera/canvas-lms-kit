<?php

declare(strict_types=1);

namespace CanvasLMS\Api\DeveloperKeys;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Config;
use CanvasLMS\Dto\DeveloperKeys\CreateDeveloperKeyDTO;
use CanvasLMS\Dto\DeveloperKeys\UpdateDeveloperKeyDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * Canvas Developer Keys API
 *
 * Manages Canvas API keys used for OAuth access. This API handles both Canvas API keys
 * and LTI 1.3 registrations, though this implementation focuses on Canvas API keys.
 *
 * Developer Keys follow Account-as-default context pattern:
 * - Direct calls use Account context from Config::getAccountId()
 * - Mixed endpoint routing: CREATE/LIST use account context, UPDATE/DELETE use direct ID
 *
 * @see https://canvas.instructure.com/doc/api/developer_keys.html
 */
class DeveloperKey extends AbstractBaseApi
{
    // Workflow states
    public const STATE_ACTIVE = 'active';
    public const STATE_INACTIVE = 'inactive';
    public const STATE_DELETED = 'deleted';

    // OAuth client credentials audiences
    public const AUDIENCE_EXTERNAL = 'external';
    public const AUDIENCE_INTERNAL = 'internal';

    // Core properties
    public ?int $id = null;

    public ?string $name = null;

    public ?string $createdAt = null;

    public ?string $updatedAt = null;

    public ?string $workflowState = null;

    // LTI identification
    public ?bool $isLtiKey = null;

    // Contact and display
    public ?string $email = null;

    public ?string $iconUrl = null;

    public ?string $notes = null;

    public ?string $vendorCode = null;

    public ?string $accountName = null;

    public ?bool $visible = null;

    // OAuth properties

    /** @var array<string>|null */
    public ?array $scopes = null;

    public ?string $redirectUri = null; // Deprecated

    /** @var array<string>|null */
    public ?array $redirectUris = null;

    public ?int $accessTokenCount = null;

    public ?string $lastUsedAt = null;

    public ?bool $testClusterOnly = null;

    public ?bool $allowIncludes = null;

    public ?bool $requireScopes = null;

    public ?string $clientCredentialsAudience = null;

    public ?string $apiKey = null;

    // LTI properties

    /** @var array<string, mixed>|null */
    public ?array $toolConfiguration = null;

    /** @var array<string, mixed>|null */
    public ?array $publicJwk = null;

    public ?string $publicJwkUrl = null;

    /** @var array<string, mixed>|null */
    public ?array $ltiRegistration = null;

    public ?bool $isLtiRegistration = null;

    // Unused properties (maintained for API compatibility)
    public ?string $userName = null;

    public ?string $userId = null;

    /**
     * Get the base endpoint for account-scoped operations (CREATE/LIST)
     *
     * @return string The API endpoint
     */
    protected static function getEndpoint(): string
    {
        $accountId = Config::getAccountId();

        return "accounts/{$accountId}/developer_keys";
    }

    /**
     * Create a new developer key
     * Uses account-scoped endpoint: POST /api/v1/accounts/:account_id/developer_keys
     *
     * @param array<string, mixed>|CreateDeveloperKeyDTO $data Developer key creation data
     *
     * @throws CanvasApiException If creation fails
     *
     * @return self The created DeveloperKey instance
     */
    public static function create(array|CreateDeveloperKeyDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateDeveloperKeyDTO($data);
        }

        $endpoint = self::getEndpoint();
        $response = self::$apiClient->post($endpoint, [
            'multipart' => $data->toApiArray(),
        ]);
        $responseData = self::parseJsonResponse($response);

        return new self($responseData);
    }

    /**
     * Update an existing developer key
     * Uses direct ID endpoint: PUT /api/v1/developer_keys/:id
     *
     * @param int $id The developer key ID
     * @param array<string, mixed>|UpdateDeveloperKeyDTO $data Update data
     *
     * @throws CanvasApiException If update fails
     *
     * @return self The updated DeveloperKey instance
     */
    public static function update(int $id, array|UpdateDeveloperKeyDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateDeveloperKeyDTO($data);
        }

        // Use direct ID endpoint (not account-scoped)
        $endpoint = "developer_keys/{$id}";
        $response = self::$apiClient->put($endpoint, [
            'multipart' => $data->toApiArray(),
        ]);
        $responseData = self::parseJsonResponse($response);

        return new self($responseData);
    }

    /**
     * Delete a developer key
     * Uses direct ID endpoint: DELETE /api/v1/developer_keys/:id
     *
     * @param int $id The developer key ID
     *
     * @throws CanvasApiException If deletion fails
     *
     * @return array<string, mixed> The deleted developer key data
     */
    public static function delete(int $id): array
    {
        self::checkApiClient();

        // Use direct ID endpoint (not account-scoped)
        $endpoint = "developer_keys/{$id}";
        $response = self::$apiClient->delete($endpoint);

        return self::parseJsonResponse($response);
    }

    /**
     * Find a developer key by ID
     * Note: Canvas doesn't have a direct GET endpoint for individual developer keys
     * This method fetches all keys and filters by ID
     *
     * @param int $id The developer key ID
     *
     * @throws CanvasApiException If key not found
     *
     * @return self The DeveloperKey instance
     */
    public static function find(int $id, array $params = []): self
    {
        $keys = self::get();

        foreach ($keys as $key) {
            if ($key->id === $id) {
                return $key;
            }
        }

        throw new CanvasApiException("Developer key with ID {$id} not found");
    }

    /**
     * Get all developer keys using account context
     * Uses account-scoped endpoint: GET /api/v1/accounts/:account_id/developer_keys
     *
     * @param array<string, mixed> $params Query parameters (e.g., 'inherited' => true)
     *
     * @return array<self> Array of DeveloperKey instances
     */
    public static function get(array $params = []): array
    {
        self::checkApiClient();

        $endpoint = self::getEndpoint();
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = self::parseJsonResponse($response);

        return array_map(fn (array $item) => new self($item), $data);
    }

    /**
     * Get paginated developer keys
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @return PaginatedResponse
     */
    public static function getPaginated(array $params = []): PaginatedResponse
    {
        $endpoint = self::getEndpoint();

        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Get developer keys with inherited keys from Site Admin
     *
     * @return array<self> Array of DeveloperKey instances including inherited keys
     */
    public static function getWithInherited(): array
    {
        return self::get(['inherited' => true]);
    }

    /**
     * Update this developer key instance
     *
     * @param array<string, mixed>|UpdateDeveloperKeyDTO $data Update data
     *
     * @throws CanvasApiException If update fails or key has no ID
     *
     * @return self The updated DeveloperKey instance
     */
    public function save(array|UpdateDeveloperKeyDTO $data): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Developer key must have an ID to update');
        }

        $updatedKey = self::update($this->id, $data);

        // Copy properties to current instance for consistency
        foreach (get_object_vars($updatedKey) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Delete this developer key instance
     *
     * @throws CanvasApiException If key has no ID
     *
     * @return array<string, mixed> The deleted developer key data
     */
    public function remove(): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Developer key must have an ID to delete');
        }

        return self::delete($this->id);
    }

    /**
     * Check if this developer key is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->workflowState === self::STATE_ACTIVE;
    }

    /**
     * Check if this developer key is inactive
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->workflowState === self::STATE_INACTIVE;
    }

    /**
     * Check if this developer key is deleted
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->workflowState === self::STATE_DELETED;
    }

    /**
     * Check if this is an LTI key (vs Canvas API key)
     *
     * @return bool
     */
    public function isLti(): bool
    {
        return $this->isLtiKey === true;
    }

    /**
     * Check if this is a Canvas API key (vs LTI key)
     *
     * @return bool
     */
    public function isApiKey(): bool
    {
        return $this->isLtiKey === false;
    }

    /**
     * Check if key is restricted to test cluster only
     *
     * @return bool
     */
    public function isTestClusterOnly(): bool
    {
        return $this->testClusterOnly === true;
    }

    /**
     * Check if key allows includes parameters
     *
     * @return bool
     */
    public function allowsIncludes(): bool
    {
        return $this->allowIncludes === true;
    }

    /**
     * Check if key requires scopes in token requests
     *
     * @return bool
     */
    public function requiresScopes(): bool
    {
        return $this->requireScopes === true;
    }

    /**
     * Get client credentials audience display name
     *
     * @return string|null
     */
    public function getClientCredentialsAudienceDisplay(): ?string
    {
        return match ($this->clientCredentialsAudience) {
            self::AUDIENCE_EXTERNAL => 'External',
            self::AUDIENCE_INTERNAL => 'Internal',
            default => $this->clientCredentialsAudience
        };
    }

    /**
     * Get redirect URIs as comma-separated string
     *
     * @return string|null
     */
    public function getRedirectUrisString(): ?string
    {
        return $this->redirectUris ? implode(', ', $this->redirectUris) : null;
    }

    /**
     * Get scopes as comma-separated string
     *
     * @return string|null
     */
    public function getScopesString(): ?string
    {
        return $this->scopes ? implode(', ', $this->scopes) : null;
    }
}
