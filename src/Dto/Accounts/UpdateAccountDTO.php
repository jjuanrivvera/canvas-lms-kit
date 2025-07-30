<?php

namespace CanvasLMS\Dto\Accounts;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating an existing Canvas account
 *
 * @package CanvasLMS\Dto\Accounts
 */
class UpdateAccountDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The name of the property in the API
     * @var string
     */
    protected string $apiPropertyName = 'account';

    /**
     * Updates the account name
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Updates the account sis_account_id
     * @var string|null
     */
    public ?string $sisAccountId = null;

    /**
     * Updates the account integration_id
     * @var string|null
     */
    public ?string $integrationId = null;

    /**
     * The ID of a parent account to move the account to
     * @var int|null
     */
    public ?int $parentAccountId = null;

    /**
     * The default time zone of the account.
     * Allowed time zones are IANA time zones or friendlier Ruby on Rails time zones.
     * @var string|null
     */
    public ?string $defaultTimeZone = null;

    /**
     * The default course storage quota in megabytes
     * @var int|null
     */
    public ?int $defaultStorageQuotaMb = null;

    /**
     * The default user storage quota in megabytes
     * @var int|null
     */
    public ?int $defaultUserStorageQuotaMb = null;

    /**
     * The default group storage quota in megabytes
     * @var int|null
     */
    public ?int $defaultGroupStorageQuotaMb = null;

    /**
     * The ID of a course to be used as a template for all newly created courses
     * @var int|null
     */
    public ?int $courseTemplateId = null;

    /**
     * Account settings
     * @var array<string, mixed>|null
     */
    public ?array $settings = null;

    /**
     * Override SIS stickiness for this update (default true)
     * @var bool|null
     */
    public ?bool $overrideSisStickiness = null;

    /**
     * Enable or disable services (hash of service names to boolean values)
     * @var array<string, bool>|null
     */
    public ?array $services = null;

    /**
     * Get the account name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the account name
     *
     * @param string|null $name
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the SIS account ID
     *
     * @return string|null
     */
    public function getSisAccountId(): ?string
    {
        return $this->sisAccountId;
    }

    /**
     * Set the SIS account ID
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
     * Get the integration ID
     *
     * @return string|null
     */
    public function getIntegrationId(): ?string
    {
        return $this->integrationId;
    }

    /**
     * Set the integration ID
     *
     * @param string|null $integrationId
     * @return self
     */
    public function setIntegrationId(?string $integrationId): self
    {
        $this->integrationId = $integrationId;
        return $this;
    }

    /**
     * Get the parent account ID
     *
     * @return int|null
     */
    public function getParentAccountId(): ?int
    {
        return $this->parentAccountId;
    }

    /**
     * Set the parent account ID
     *
     * @param int|null $parentAccountId
     * @return self
     */
    public function setParentAccountId(?int $parentAccountId): self
    {
        $this->parentAccountId = $parentAccountId;
        return $this;
    }

    /**
     * Get the default time zone
     *
     * @return string|null
     */
    public function getDefaultTimeZone(): ?string
    {
        return $this->defaultTimeZone;
    }

    /**
     * Set the default time zone
     *
     * @param string|null $defaultTimeZone
     * @return self
     */
    public function setDefaultTimeZone(?string $defaultTimeZone): self
    {
        $this->defaultTimeZone = $defaultTimeZone;
        return $this;
    }

    /**
     * Get the default storage quota in MB
     *
     * @return int|null
     */
    public function getDefaultStorageQuotaMb(): ?int
    {
        return $this->defaultStorageQuotaMb;
    }

    /**
     * Set the default storage quota in MB
     *
     * @param int|null $defaultStorageQuotaMb
     * @return self
     */
    public function setDefaultStorageQuotaMb(?int $defaultStorageQuotaMb): self
    {
        $this->defaultStorageQuotaMb = $defaultStorageQuotaMb;
        return $this;
    }

    /**
     * Get the default user storage quota in MB
     *
     * @return int|null
     */
    public function getDefaultUserStorageQuotaMb(): ?int
    {
        return $this->defaultUserStorageQuotaMb;
    }

    /**
     * Set the default user storage quota in MB
     *
     * @param int|null $defaultUserStorageQuotaMb
     * @return self
     */
    public function setDefaultUserStorageQuotaMb(?int $defaultUserStorageQuotaMb): self
    {
        $this->defaultUserStorageQuotaMb = $defaultUserStorageQuotaMb;
        return $this;
    }

    /**
     * Get the default group storage quota in MB
     *
     * @return int|null
     */
    public function getDefaultGroupStorageQuotaMb(): ?int
    {
        return $this->defaultGroupStorageQuotaMb;
    }

    /**
     * Set the default group storage quota in MB
     *
     * @param int|null $defaultGroupStorageQuotaMb
     * @return self
     */
    public function setDefaultGroupStorageQuotaMb(?int $defaultGroupStorageQuotaMb): self
    {
        $this->defaultGroupStorageQuotaMb = $defaultGroupStorageQuotaMb;
        return $this;
    }

    /**
     * Get the course template ID
     *
     * @return int|null
     */
    public function getCourseTemplateId(): ?int
    {
        return $this->courseTemplateId;
    }

    /**
     * Set the course template ID
     *
     * @param int|null $courseTemplateId
     * @return self
     */
    public function setCourseTemplateId(?int $courseTemplateId): self
    {
        $this->courseTemplateId = $courseTemplateId;
        return $this;
    }

    /**
     * Get the account settings
     *
     * @return array<string, mixed>|null
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }

    /**
     * Set the account settings
     *
     * @param array<string, mixed>|null $settings
     * @return self
     */
    public function setSettings(?array $settings): self
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * Add a setting to the account settings
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addSetting(string $key, $value): self
    {
        if ($this->settings === null) {
            $this->settings = [];
        }

        $this->settings[$key] = $value;
        return $this;
    }

    /**
     * Get the override SIS stickiness flag
     *
     * @return bool|null
     */
    public function getOverrideSisStickiness(): ?bool
    {
        return $this->overrideSisStickiness;
    }

    /**
     * Set the override SIS stickiness flag
     *
     * @param bool|null $overrideSisStickiness
     * @return self
     */
    public function setOverrideSisStickiness(?bool $overrideSisStickiness): self
    {
        $this->overrideSisStickiness = $overrideSisStickiness;
        return $this;
    }

    /**
     * Get the services
     *
     * @return array<string, bool>|null
     */
    public function getServices(): ?array
    {
        return $this->services;
    }

    /**
     * Set the services
     *
     * @param array<string, bool>|null $services
     * @return self
     */
    public function setServices(?array $services): self
    {
        $this->services = $services;
        return $this;
    }

    /**
     * Enable or disable a specific service
     *
     * @param string $serviceName
     * @param bool $enabled
     * @return self
     */
    public function setService(string $serviceName, bool $enabled): self
    {
        if ($this->services === null) {
            $this->services = [];
        }

        $this->services[$serviceName] = $enabled;
        return $this;
    }

    /**
     * Convert the DTO to an array for API requests
     * This override handles nested settings array properly
     *
     * @return array<int, array{name: string, contents: mixed}>
     */
    public function toApiArray(): array
    {
        $properties = get_object_vars($this);
        $modifiedProperties = [];

        foreach ($properties as $property => $value) {
            // Skip the apiPropertyName itself
            if ($property === 'apiPropertyName') {
                continue;
            }

            // Skip null values
            if ($value === null) {
                continue;
            }

            // Handle nested settings array
            if ($property === 'settings' && is_array($value)) {
                foreach ($value as $settingKey => $settingValue) {
                    // Handle nested setting structures
                    if (is_array($settingValue)) {
                        foreach ($settingValue as $subKey => $subValue) {
                            $modifiedProperties[] = [
                                'name' => sprintf('%s[settings][%s][%s]', $this->apiPropertyName, $settingKey, $subKey),
                                'contents' => $subValue
                            ];
                        }
                    } else {
                        $modifiedProperties[] = [
                            'name' => sprintf('%s[settings][%s]', $this->apiPropertyName, $settingKey),
                            'contents' => $settingValue
                        ];
                    }
                }
                continue;
            }

            // Handle services array
            if ($property === 'services' && is_array($value)) {
                foreach ($value as $serviceKey => $serviceValue) {
                    $modifiedProperties[] = [
                        'name' => sprintf('%s[services][%s]', $this->apiPropertyName, $serviceKey),
                        'contents' => $serviceValue ? 'true' : 'false'
                    ];
                }
                continue;
            }

            // Handle override_sis_stickiness separately (not wrapped in account[])
            if ($property === 'overrideSisStickiness') {
                $modifiedProperties[] = [
                    'name' => 'override_sis_stickiness',
                    'contents' => $value ? 'true' : 'false'
                ];
                continue;
            }

            // Convert property name to snake_case
            $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $property));

            // Handle boolean values
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $modifiedProperties[] = [
                'name' => sprintf('%s[%s]', $this->apiPropertyName, $snakeCase),
                'contents' => $value
            ];
        }

        return $modifiedProperties;
    }
}
