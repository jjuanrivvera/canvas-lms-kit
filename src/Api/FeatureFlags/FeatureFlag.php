<?php

declare(strict_types=1);

namespace CanvasLMS\Api\FeatureFlags;

use CanvasLMS\Config;
use CanvasLMS\Dto\FeatureFlags\UpdateFeatureFlagDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;

/**
 * Feature Flags API class for managing Canvas feature toggles.
 *
 * Feature flags allow you to see what optional features apply to a given Account, Course, or User.
 * Some feature flags can only be seen by users with administrative rights.
 *
 * This class does NOT extend AbstractBaseApi as feature flags don't follow standard CRUD patterns.
 * Instead, it implements custom methods specific to feature flag operations.
 *
 * @package CanvasLMS\Api\FeatureFlags
 *
 * @see https://canvas.instructure.com/doc/api/feature_flags.html
 */
class FeatureFlag
{
    /**
     * @var HttpClientInterface
     */
    protected static HttpClientInterface $apiClient;

    /**
     * The symbolic name of the feature
     *
     * @var string|null
     */
    public ?string $feature = null;

    /**
     * The user-visible name of the feature
     *
     * @var string|null
     */
    public ?string $displayName = null;

    /**
     * The type of object the feature applies to (RootAccount, Account, Course, or User)
     *
     * @var string|null
     */
    public ?string $appliesTo = null;

    /**
     * The name of the feature that this feature depends on, if any
     *
     * @var string|null
     */
    public ?string $enableAt = null;

    /**
     * The FeatureFlag that applies to the caller
     *
     * @var array<string, mixed>|null
     */
    public ?array $featureFlag = null;

    /**
     * If true, a feature flag associated with this feature may only be set on the Root Account
     *
     * @var bool|null
     */
    public ?bool $rootOptIn = null;

    /**
     * If true, the feature is a beta feature that may change or be removed
     *
     * @var bool|null
     */
    public ?bool $beta = null;

    /**
     * If true, the feature is in development. Should not be used in production
     *
     * @var bool|null
     */
    public ?bool $development = null;

    /**
     * A URL to the release notes describing the feature
     *
     * @var string|null
     */
    public ?string $releaseNotesUrl = null;

    /**
     * The type of context (for tracking purposes, not from Canvas API)
     *
     * @var string|null
     */
    public ?string $contextType = null;

    /**
     * The ID of the context (for tracking purposes, not from Canvas API)
     *
     * @var int|null
     */
    public ?int $contextId = null;

    /**
     * The state of the feature flag (off, allowed, on)
     *
     * @var string|null
     */
    public ?string $state = null;

    /**
     * If true, the feature flag cannot be changed at lower levels
     *
     * @var bool|null
     */
    public ?bool $locked = null;

    /**
     * If true, the feature flag is hidden from the UI
     *
     * @var bool|null
     */
    public ?bool $hidden = null;

    /**
     * Set the API client
     *
     * @param HttpClientInterface $client
     */
    public static function setApiClient(HttpClientInterface $client): void
    {
        self::$apiClient = $client;
    }

    /**
     * Validate that the context type is supported
     *
     * @param string $contextType The context type to validate
     *
     * @throws \InvalidArgumentException If the context type is invalid
     */
    private static function validateContextType(string $contextType): void
    {
        $validContexts = ['accounts', 'courses', 'users'];
        if (!in_array($contextType, $validContexts, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid context type "%s". Valid types are: %s',
                    $contextType,
                    implode(', ', $validContexts)
                )
            );
        }
    }

    /**
     * Normalize the context type from plural to singular
     *
     * @param string $contextType The plural context type
     *
     * @return string The singular context type
     */
    private static function normalizeContextType(string $contextType): string
    {
        $mapping = [
            'accounts' => 'account',
            'courses' => 'course',
            'users' => 'user',
        ];

        return $mapping[$contextType] ?? $contextType;
    }

    /**
     * List features for the default account context
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, self> Array of FeatureFlag objects
     */
    public static function get(array $params = []): array
    {
        $accountId = Config::getAccountId();

        return self::fetchByContext('accounts', $accountId, $params);
    }

    /**
     * List features for a specific context
     *
     * @param string $contextType The context type (accounts, courses, users)
     * @param int $contextId The context ID
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, self> Array of FeatureFlag objects
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        self::validateContextType($contextType);

        $endpoint = sprintf('%s/%d/features', $contextType, $contextId);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $featuresData = self::parseJsonResponse($response);

        $features = [];
        foreach ($featuresData as $featureData) {
            $feature = new self();
            self::hydrateProperties($feature, $featureData);
            $feature->contextType = self::normalizeContextType($contextType);
            $feature->contextId = $contextId;
            $features[] = $feature;
        }

        return $features;
    }

    /**
     * Get a specific feature flag for the default account context
     *
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function find(string $featureName): self
    {
        $accountId = Config::getAccountId();

        return self::findByContext('accounts', $accountId, $featureName);
    }

    /**
     * Get a specific feature flag for a specific context
     *
     * @param string $contextType The context type (accounts, courses, users)
     * @param int $contextId The context ID
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function findByContext(string $contextType, int $contextId, string $featureName): self
    {
        self::validateContextType($contextType);

        $endpoint = sprintf('%s/%d/features/flags/%s', $contextType, $contextId, $featureName);
        $response = self::$apiClient->get($endpoint);
        $featureData = self::parseJsonResponse($response);

        $feature = new self();
        self::hydrateProperties($feature, $featureData);
        $feature->contextType = self::normalizeContextType($contextType);
        $feature->contextId = $contextId;

        return $feature;
    }

    /**
     * Update a feature flag for the default account context
     *
     * @param string $featureName The symbolic name of the feature
     * @param array<string, mixed>|UpdateFeatureFlagDTO $data The update data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function update(string $featureName, array|UpdateFeatureFlagDTO $data): self
    {
        $accountId = Config::getAccountId();

        return self::updateByContext('accounts', $accountId, $featureName, $data);
    }

    /**
     * Update a feature flag for a specific context
     *
     * @param string $contextType The context type (accounts, courses, users)
     * @param int $contextId The context ID
     * @param string $featureName The symbolic name of the feature
     * @param array<string, mixed>|UpdateFeatureFlagDTO $data The update data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function updateByContext(
        string $contextType,
        int $contextId,
        string $featureName,
        array|UpdateFeatureFlagDTO $data
    ): self {
        self::validateContextType($contextType);

        if (is_array($data)) {
            $data = new UpdateFeatureFlagDTO($data);
        }

        $endpoint = sprintf('%s/%d/features/flags/%s', $contextType, $contextId, $featureName);
        $response = self::$apiClient->put($endpoint, [
            'multipart' => $data->toMultipart(),
        ]);
        $featureData = self::parseJsonResponse($response);

        $feature = new self();
        self::hydrateProperties($feature, $featureData);
        $feature->contextType = self::normalizeContextType($contextType);
        $feature->contextId = $contextId;

        return $feature;
    }

    /**
     * Remove a feature flag for the default account context
     *
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return bool
     */
    public static function delete(string $featureName): bool
    {
        $accountId = Config::getAccountId();

        return self::deleteByContext('accounts', $accountId, $featureName);
    }

    /**
     * Remove a feature flag for a specific context
     *
     * @param string $contextType The context type (accounts, courses, users)
     * @param int $contextId The context ID
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return bool
     */
    public static function deleteByContext(string $contextType, int $contextId, string $featureName): bool
    {
        self::validateContextType($contextType);

        $endpoint = sprintf('%s/%d/features/flags/%s', $contextType, $contextId, $featureName);

        try {
            self::$apiClient->delete($endpoint);

            return true;
        } catch (CanvasApiException $e) {
            if ($e->getCode() === 404) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Set the state of a feature flag for the default account context
     *
     * @param string $featureName The symbolic name of the feature
     * @param string $state The state to set (off, allowed, on)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function setFeatureState(string $featureName, string $state): self
    {
        return self::update($featureName, ['state' => $state]);
    }

    /**
     * Set the state of a feature flag for a specific context
     *
     * @param string $contextType The context type (accounts, courses, users)
     * @param int $contextId The context ID
     * @param string $featureName The symbolic name of the feature
     * @param string $state The state to set (off, allowed, on)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function setFeatureStateByContext(
        string $contextType,
        int $contextId,
        string $featureName,
        string $state
    ): self {
        self::validateContextType($contextType);

        return self::updateByContext($contextType, $contextId, $featureName, ['state' => $state]);
    }

    /**
     * Enable a feature for the default account context
     *
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function enable(string $featureName): self
    {
        return self::setFeatureState($featureName, 'on');
    }

    /**
     * Enable a feature for a specific context
     *
     * @param string $contextType The context type (accounts, courses, users)
     * @param int $contextId The context ID
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function enableByContext(string $contextType, int $contextId, string $featureName): self
    {
        return self::setFeatureStateByContext($contextType, $contextId, $featureName, 'on');
    }

    /**
     * Disable a feature for the default account context
     *
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function disable(string $featureName): self
    {
        return self::setFeatureState($featureName, 'off');
    }

    /**
     * Disable a feature for a specific context
     *
     * @param string $contextType The context type (accounts, courses, users)
     * @param int $contextId The context ID
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function disableByContext(string $contextType, int $contextId, string $featureName): self
    {
        return self::setFeatureStateByContext($contextType, $contextId, $featureName, 'off');
    }

    /**
     * Allow a feature to be toggled for the default account context
     *
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function allow(string $featureName): self
    {
        return self::setFeatureState($featureName, 'allowed');
    }

    /**
     * Allow a feature to be toggled for a specific context
     *
     * @param string $contextType The context type (accounts, courses, users)
     * @param int $contextId The context ID
     * @param string $featureName The symbolic name of the feature
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function allowByContext(string $contextType, int $contextId, string $featureName): self
    {
        return self::setFeatureStateByContext($contextType, $contextId, $featureName, 'allowed');
    }

    /**
     * Check if a feature is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        if (isset($this->featureFlag['state']) && is_string($this->featureFlag['state'])) {
            return $this->featureFlag['state'] === 'on';
        }

        return $this->state === 'on';
    }

    /**
     * Check if a feature is disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        if (isset($this->featureFlag['state']) && is_string($this->featureFlag['state'])) {
            return $this->featureFlag['state'] === 'off';
        }

        return $this->state === 'off';
    }

    /**
     * Check if a feature is allowed (can be toggled)
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        if (isset($this->featureFlag['state']) && is_string($this->featureFlag['state'])) {
            return $this->featureFlag['state'] === 'allowed';
        }

        return $this->state === 'allowed';
    }

    /**
     * Check if a feature is locked
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        if (isset($this->featureFlag['locked']) && is_bool($this->featureFlag['locked'])) {
            return $this->featureFlag['locked'] === true;
        }

        return $this->locked === true;
    }

    /**
     * Check if a feature is hidden
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        if (isset($this->featureFlag['hidden']) && is_bool($this->featureFlag['hidden'])) {
            return $this->featureFlag['hidden'] === true;
        }

        return $this->hidden === true;
    }

    /**
     * Check if a feature is in beta
     *
     * @return bool
     */
    public function isBeta(): bool
    {
        return $this->beta === true;
    }

    /**
     * Check if a feature is in development
     *
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return $this->development === true;
    }

    /**
     * Parse JSON response from HTTP response
     *
     * @param \Psr\Http\Message\ResponseInterface $response The HTTP response
     *
     * @throws CanvasApiException If JSON parsing fails
     *
     * @return array<string, mixed> The parsed JSON data
     */
    protected static function parseJsonResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        $content = $response->getBody()->getContents();
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new CanvasApiException('Failed to parse JSON response: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Hydrate object properties from API response data
     *
     * @param self $object The object to hydrate
     * @param array<string, mixed> $data The API response data
     */
    protected static function hydrateProperties(self $object, array $data): void
    {
        foreach ($data as $key => $value) {
            // Convert snake_case to camelCase
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($object, $camelKey)) {
                $object->$camelKey = $value;
            }
        }

        // Handle nested feature_flag data with proper type checking
        if (isset($data['feature_flag']) && is_array($data['feature_flag'])) {
            $object->featureFlag = $data['feature_flag'];

            // Extract state, locked, and hidden from nested feature_flag if present
            // Only override if not already set at top level
            if (isset($data['feature_flag']['state']) && is_string($data['feature_flag']['state'])) {
                if ($object->state === null) {
                    $object->state = $data['feature_flag']['state'];
                }
            }
            if (isset($data['feature_flag']['locked']) && is_bool($data['feature_flag']['locked'])) {
                if ($object->locked === null) {
                    $object->locked = $data['feature_flag']['locked'];
                }
            }
            if (isset($data['feature_flag']['hidden']) && is_bool($data['feature_flag']['hidden'])) {
                if ($object->hidden === null) {
                    $object->hidden = $data['feature_flag']['hidden'];
                }
            }
        }
    }
}
