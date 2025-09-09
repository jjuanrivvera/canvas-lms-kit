<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\SharedBrandConfigs;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * UpdateSharedBrandConfigDTO
 *
 * Data Transfer Object for updating a shared brand configuration.
 * Handles the data transformation for the PUT endpoint.
 *
 * Canvas API Documentation: https://canvas.instructure.com/doc/api/shared_brand_configs.html
 */
class UpdateSharedBrandConfigDTO extends AbstractBaseDto
{
    /**
     * @var string $apiPropertyName The API property name for multipart form data
     */
    protected string $apiPropertyName = 'shared_brand_config';

    /**
     * Updated name for the shared theme (optional)
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Updated MD5 of brand_config (optional)
     *
     * @var string|null
     */
    public ?string $brandConfigMd5 = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data The data for updating a shared brand config
     */
    public function __construct(array $data = [])
    {
        // Map input keys to DTO properties
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }

        // Handle both camelCase and snake_case for MD5
        if (isset($data['brand_config_md5'])) {
            $this->brandConfigMd5 = $data['brand_config_md5'];
        } elseif (isset($data['brandConfigMd5'])) {
            $this->brandConfigMd5 = $data['brandConfigMd5'];
        }

        // Don't call parent constructor as we handle property mapping here
    }

    /**
     * Validate the DTO data
     *
     * @throws \InvalidArgumentException If no fields are provided for update
     */
    public function validate(): void
    {
        if (empty($this->name) && empty($this->brandConfigMd5)) {
            throw new \InvalidArgumentException(
                'At least one field (name or brand_config_md5) must be provided for update'
            );
        }
    }

    /**
     * Convert DTO to API array format
     *
     * @return array<int, array<string, string>> The formatted data for the Canvas API
     */
    public function toApiArray(): array
    {
        $this->validate();

        $properties = [];

        // Add name field if provided
        if (!empty($this->name)) {
            $properties[] = [
                'name' => sprintf('%s[name]', $this->apiPropertyName),
                'contents' => $this->name
            ];
        }

        // Add brand_config_md5 field if provided (Canvas expects snake_case)
        if (!empty($this->brandConfigMd5)) {
            $properties[] = [
                'name' => sprintf('%s[brand_config_md5]', $this->apiPropertyName),
                'contents' => $this->brandConfigMd5
            ];
        }

        return $properties;
    }
}
