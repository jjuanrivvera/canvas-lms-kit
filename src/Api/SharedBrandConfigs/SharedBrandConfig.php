<?php

declare(strict_types=1);

namespace CanvasLMS\Api\SharedBrandConfigs;

use CanvasLMS\Config;
use CanvasLMS\Dto\SharedBrandConfigs\CreateSharedBrandConfigDTO;
use CanvasLMS\Dto\SharedBrandConfigs\UpdateSharedBrandConfigDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Http\HttpClient;

/**
 * SharedBrandConfig API
 *
 * Manage shared brand configurations (themes) that can be reused across multiple
 * accounts. This API allows creating, updating, and deleting shared themes.
 *
 * Canvas API Documentation: https://canvas.instructure.com/doc/api/shared_brand_configs.html
 *
 * IMPORTANT LIMITATIONS:
 * - Canvas does NOT provide endpoints to list or fetch shared brand configs
 * - You must track shared config IDs externally
 * - The DELETE endpoint has a different path pattern (no account_id)
 *
 * Note: This class does not extend AbstractBaseApi because Canvas doesn't provide
 * fetch/list operations for shared brand configs.
 */
class SharedBrandConfig
{
    /**
     * The shared brand config identifier
     */
    public ?int $id = null;

    /**
     * The id of the account it should be shared within
     */
    public ?string $accountId = null;

    /**
     * MD5 hash of the brand config to share
     * (BrandConfigs are identified by MD5, not numeric id)
     */
    public ?string $brandConfigMd5 = null;

    /**
     * The name to share this theme as
     */
    public ?string $name = null;

    /**
     * When this was created
     */
    public ?string $createdAt = null;

    /**
     * When this was last updated
     */
    public ?string $updatedAt = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data The shared brand config data from Canvas API
     */
    public function __construct(array $data = [])
    {
        // Convert snake_case keys from Canvas API to camelCase properties
        foreach ($data as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $camelKey) && !is_null($value)) {
                $this->{$camelKey} = $value;
            }
        }
    }

    /**
     * Create a new shared brand config
     *
     * Creates a SharedBrandConfig which gives the specified brand_config a name
     * and makes it available to other users of this account.
     *
     * API Endpoint: POST /api/v1/accounts/:account_id/shared_brand_configs
     *
     * @param array<string, mixed>|CreateSharedBrandConfigDTO $data The shared brand config data
     * @return self The created SharedBrandConfig object
     * @throws CanvasApiException If the API request fails
     *
     * @example
     * ```php
     * $sharedConfig = SharedBrandConfig::create([
     *     'name' => 'Spring 2024 Theme',
     *     'brand_config_md5' => 'a1f113321fa024e7a14cb0948597a2a4'
     * ]);
     * ```
     */
    public static function create(array|CreateSharedBrandConfigDTO $data): self
    {
        if (is_array($data)) {
            $data = new CreateSharedBrandConfigDTO($data);
        }

        $httpClient = new HttpClient();
        $accountId = Config::getAccountId();

        try {
            $response = $httpClient->post(
                "/accounts/{$accountId}/shared_brand_configs",
                $data->toApiArray()
            );

            // HttpClient returns ResponseInterface
            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new CanvasApiException(
                    'Failed to decode response JSON: ' . json_last_error_msg()
                );
            }

            return new self($decoded);
        } catch (\Exception $e) {
            if ($e instanceof CanvasApiException) {
                throw $e;
            }

            throw new CanvasApiException(
                'Failed to create shared brand config: ' . $e->getMessage(),
                0,
                []
            );
        }
    }

    /**
     * Update an existing shared brand config
     *
     * Updates the specified shared_brand_config with a new name or to point
     * to a new brand_config MD5.
     *
     * API Endpoint: PUT /api/v1/accounts/:account_id/shared_brand_configs/:id
     *
     * @param int $id The ID of the shared brand config to update
     * @param array<string, mixed>|UpdateSharedBrandConfigDTO $data The update data
     * @return self The updated SharedBrandConfig object
     * @throws CanvasApiException If the API request fails
     *
     * @example
     * ```php
     * $updated = SharedBrandConfig::update(987, [
     *     'name' => 'Updated Theme Name'
     * ]);
     * ```
     */
    public static function update(int $id, array|UpdateSharedBrandConfigDTO $data): self
    {
        if (is_array($data)) {
            $data = new UpdateSharedBrandConfigDTO($data);
        }

        $httpClient = new HttpClient();
        $accountId = Config::getAccountId();

        try {
            $response = $httpClient->put(
                "/accounts/{$accountId}/shared_brand_configs/{$id}",
                $data->toApiArray()
            );

            // HttpClient returns ResponseInterface
            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new CanvasApiException(
                    'Failed to decode response JSON: ' . json_last_error_msg()
                );
            }

            return new self($decoded);
        } catch (\Exception $e) {
            if ($e instanceof CanvasApiException) {
                throw $e;
            }

            throw new CanvasApiException(
                'Failed to update shared brand config: ' . $e->getMessage(),
                0,
                []
            );
        }
    }

    /**
     * Delete a shared brand config
     *
     * Deletes a SharedBrandConfig, which will unshare it so neither you nor
     * anyone else in your account will see it as an option to pick from.
     *
     * IMPORTANT: The DELETE endpoint has a different path pattern - it does NOT
     * include the account_id in the path.
     *
     * API Endpoint: DELETE /api/v1/shared_brand_configs/:id
     *
     * @param int $id The ID of the shared brand config to delete
     * @return self The deleted SharedBrandConfig object
     * @throws CanvasApiException If the API request fails
     *
     * @example
     * ```php
     * $deleted = SharedBrandConfig::delete(987);
     * ```
     */
    public static function delete(int $id): self
    {
        $httpClient = new HttpClient();

        try {
            // NOTE: Different endpoint pattern - no account_id in path!
            $response = $httpClient->delete("/shared_brand_configs/{$id}");

            // HttpClient returns ResponseInterface
            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new CanvasApiException(
                    'Failed to decode response JSON: ' . json_last_error_msg()
                );
            }

            return new self($decoded);
        } catch (\Exception $e) {
            if ($e instanceof CanvasApiException) {
                throw $e;
            }

            throw new CanvasApiException(
                'Failed to delete shared brand config: ' . $e->getMessage(),
                0,
                []
            );
        }
    }

    /**
     * Delete this shared brand config (instance method)
     *
     * Deletes the current SharedBrandConfig instance.
     *
     * @return self The deleted SharedBrandConfig object
     * @throws CanvasApiException If the config has no ID or the API request fails
     *
     * @example
     * ```php
     * $sharedConfig->remove();
     * ```
     */
    public function remove(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot delete SharedBrandConfig without an ID');
        }

        return self::delete($this->id);
    }

    /**
     * Convert the shared brand config to an array
     *
     * @return array<string, mixed> The shared brand config data as an array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->accountId,
            'brand_config_md5' => $this->brandConfigMd5,
            'name' => $this->name,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
