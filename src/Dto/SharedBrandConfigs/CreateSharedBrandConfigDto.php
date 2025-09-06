<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\SharedBrandConfigs;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * CreateSharedBrandConfigDto
 *
 * Data Transfer Object for creating a shared brand configuration.
 * Handles the data transformation for the POST endpoint.
 *
 * Canvas API Documentation: https://canvas.instructure.com/doc/api/shared_brand_configs.html
 */
class CreateSharedBrandConfigDto extends AbstractBaseDto
{
    /**
     * @var string $apiPropertyName The API property name for multipart form data
     */
    protected string $apiPropertyName = 'shared_brand_config';

    /**
     * Name to share this BrandConfig (theme) as
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * MD5 of brand_config to share
     *
     * @var string|null
     */
    public ?string $brandConfigMd5 = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data The data for creating a shared brand config
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
     * @throws \InvalidArgumentException If required fields are missing
     */
    public function validate(): void
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Name is required for creating a shared brand config');
        }

        if (empty($this->brandConfigMd5)) {
            throw new \InvalidArgumentException('Brand config MD5 is required for creating a shared brand config');
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

        // Add name field
        $properties[] = [
            'name' => sprintf('%s[name]', $this->apiPropertyName),
            'contents' => $this->name
        ];

        // Add brand_config_md5 field (Canvas expects snake_case)
        $properties[] = [
            'name' => sprintf('%s[brand_config_md5]', $this->apiPropertyName),
            'contents' => $this->brandConfigMd5
        ];

        return $properties;
    }
}
