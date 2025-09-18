<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Accounts;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for creating a new Canvas account
 *
 * @package CanvasLMS\Dto\Accounts
 */
class CreateAccountDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The name of the property in the API
     *
     * @var string
     */
    protected string $apiPropertyName = 'account';

    /**
     * The name of the new sub-account
     *
     * @var string
     */
    public string $name;

    /**
     * The account's identifier in the Student Information System
     *
     * @var string|null
     */
    public ?string $sisAccountId = null;

    /**
     * The account's identifier in external systems
     *
     * @var string|null
     */
    public ?string $integrationId = null;

    /**
     * The parent account ID (required for sub-accounts)
     *
     * @var int|null
     */
    public ?int $parentAccountId = null;

    /**
     * The default course storage quota in megabytes
     *
     * @var int|null
     */
    public ?int $defaultStorageQuotaMb = null;

    /**
     * The default user storage quota in megabytes
     *
     * @var int|null
     */
    public ?int $defaultUserStorageQuotaMb = null;

    /**
     * The default group storage quota in megabytes
     *
     * @var int|null
     */
    public ?int $defaultGroupStorageQuotaMb = null;

    /**
     * The default time zone of the account
     *
     * @var string|null
     */
    public ?string $defaultTimeZone = null;

    /**
     * Get the account name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the account name
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
     *
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
     *
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
     *
     * @return self
     */
    public function setParentAccountId(?int $parentAccountId): self
    {
        $this->parentAccountId = $parentAccountId;

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
     *
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
     *
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
     *
     * @return self
     */
    public function setDefaultGroupStorageQuotaMb(?int $defaultGroupStorageQuotaMb): self
    {
        $this->defaultGroupStorageQuotaMb = $defaultGroupStorageQuotaMb;

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
     *
     * @return self
     */
    public function setDefaultTimeZone(?string $defaultTimeZone): self
    {
        $this->defaultTimeZone = $defaultTimeZone;

        return $this;
    }
}
