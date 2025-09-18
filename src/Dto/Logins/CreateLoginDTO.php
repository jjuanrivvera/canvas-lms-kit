<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Logins;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for creating a new login (pseudonym)
 */
class CreateLoginDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The ID of the user to create the login for (required)
     *
     * @var int|null
     */
    public ?int $userId = null;

    /**
     * A Canvas User ID to identify a user in a trusted account (non-OSS Canvas)
     * Alternative to userId, existingSisUserId, or existingIntegrationId
     *
     * @var string|null
     */
    public ?string $existingUserId = null;

    /**
     * An Integration ID to identify a user in a trusted account (non-OSS Canvas)
     * Alternative to userId, existingUserId, or existingSisUserId
     *
     * @var string|null
     */
    public ?string $existingIntegrationId = null;

    /**
     * An SIS User ID to identify a user in a trusted account (non-OSS Canvas)
     * Alternative to userId, existingUserId, or existingIntegrationId
     *
     * @var string|null
     */
    public ?string $existingSisUserId = null;

    /**
     * The domain of the account to search for the user (non-OSS Canvas)
     * Required when identifying a user in a trusted account
     *
     * @var string|null
     */
    public ?string $trustedAccount = null;

    /**
     * The unique ID for the new login (required)
     *
     * @var string|null
     */
    public ?string $uniqueId = null;

    /**
     * The new login's password
     *
     * @var string|null
     */
    public ?string $password = null;

    /**
     * SIS ID for the login
     * Requires manage SIS permissions on the account
     *
     * @var string|null
     */
    public ?string $sisUserId = null;

    /**
     * Integration ID for the login
     * Requires manage SIS permissions on the account
     * Secondary identifier for complex SIS integrations
     *
     * @var string|null
     */
    public ?string $integrationId = null;

    /**
     * The authentication provider this login is associated with
     * Can be integer ID or provider type
     *
     * @var string|null
     */
    public ?string $authenticationProviderId = null;

    /**
     * The declared intention of the user type
     * Valid values: administrative, observer, staff, student, student_other, teacher
     *
     * @var string|null
     */
    public ?string $declaredUserType = null;

    /**
     * Transform DTO properties to Canvas API multipart format
     *
     * @return array<array{name: string, contents: string}>
     */
    public function toApiArray(): array
    {
        $this->validateUserIdentification();

        $properties = get_object_vars($this);
        $modifiedProperties = [];

        foreach ($properties as $key => $value) {
            if (empty($value)) {
                continue;
            }

            // Map properties to Canvas API parameter names
            $apiKeyName = match ($key) {
                // User identification parameters
                'userId' => 'user[id]',
                'existingUserId' => 'user[existing_user_id]',
                'existingIntegrationId' => 'user[existing_integration_id]',
                'existingSisUserId' => 'user[existing_sis_user_id]',
                'trustedAccount' => 'user[trusted_account]',
                // Login parameters
                'uniqueId' => 'login[unique_id]',
                'password' => 'login[password]',
                'sisUserId' => 'login[sis_user_id]',
                'integrationId' => 'login[integration_id]',
                'authenticationProviderId' => 'login[authentication_provider_id]',
                'declaredUserType' => 'login[declared_user_type]',
                default => throw new \InvalidArgumentException("Unknown property: {$key}")
            };

            $modifiedProperties[] = [
                'name' => $apiKeyName,
                'contents' => (string) $value,
            ];
        }

        return $modifiedProperties;
    }

    /**
     * Validate that only one user identification method is provided
     *
     * @throws \InvalidArgumentException
     */
    private function validateUserIdentification(): void
    {
        $userIdFields = array_filter([
            'userId' => $this->userId,
            'existingUserId' => $this->existingUserId,
            'existingIntegrationId' => $this->existingIntegrationId,
            'existingSisUserId' => $this->existingSisUserId,
        ]);

        if (empty($userIdFields)) {
            throw new \InvalidArgumentException(
                'One user identification method is required: ' .
                'userId, existingUserId, existingIntegrationId, or existingSisUserId'
            );
        }

        if (count($userIdFields) > 1) {
            $providedFields = implode(', ', array_keys($userIdFields));

            throw new \InvalidArgumentException(
                "Only one user identification method should be provided. Found: {$providedFields}"
            );
        }

        // Validate trusted account requirement for existing user fields
        $existingUserFields = ['existingUserId', 'existingIntegrationId', 'existingSisUserId'];
        $hasExistingUserField = !empty(array_intersect(array_keys($userIdFields), $existingUserFields));

        if ($hasExistingUserField && empty($this->trustedAccount)) {
            throw new \InvalidArgumentException(
                'trustedAccount is required when using existing user identification fields'
            );
        }

        // Validate uniqueId is provided
        if (empty($this->uniqueId)) {
            throw new \InvalidArgumentException('uniqueId is required for login creation');
        }

        // Validate declaredUserType if provided
        if ($this->declaredUserType) {
            $validTypes = ['administrative', 'observer', 'staff', 'student', 'student_other', 'teacher'];
            if (!in_array($this->declaredUserType, $validTypes, true)) {
                $validTypesStr = implode(', ', $validTypes);

                throw new \InvalidArgumentException("Invalid declaredUserType. Valid values are: {$validTypesStr}");
            }
        }
    }

    // Getters and setters
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getExistingUserId(): ?string
    {
        return $this->existingUserId;
    }

    public function setExistingUserId(?string $existingUserId): void
    {
        $this->existingUserId = $existingUserId;
    }

    public function getExistingIntegrationId(): ?string
    {
        return $this->existingIntegrationId;
    }

    public function setExistingIntegrationId(?string $existingIntegrationId): void
    {
        $this->existingIntegrationId = $existingIntegrationId;
    }

    public function getExistingSisUserId(): ?string
    {
        return $this->existingSisUserId;
    }

    public function setExistingSisUserId(?string $existingSisUserId): void
    {
        $this->existingSisUserId = $existingSisUserId;
    }

    public function getTrustedAccount(): ?string
    {
        return $this->trustedAccount;
    }

    public function setTrustedAccount(?string $trustedAccount): void
    {
        $this->trustedAccount = $trustedAccount;
    }

    public function getUniqueId(): ?string
    {
        return $this->uniqueId;
    }

    public function setUniqueId(?string $uniqueId): void
    {
        $this->uniqueId = $uniqueId;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getSisUserId(): ?string
    {
        return $this->sisUserId;
    }

    public function setSisUserId(?string $sisUserId): void
    {
        $this->sisUserId = $sisUserId;
    }

    public function getIntegrationId(): ?string
    {
        return $this->integrationId;
    }

    public function setIntegrationId(?string $integrationId): void
    {
        $this->integrationId = $integrationId;
    }

    public function getAuthenticationProviderId(): ?string
    {
        return $this->authenticationProviderId;
    }

    public function setAuthenticationProviderId(?string $authenticationProviderId): void
    {
        $this->authenticationProviderId = $authenticationProviderId;
    }

    public function getDeclaredUserType(): ?string
    {
        return $this->declaredUserType;
    }

    public function setDeclaredUserType(?string $declaredUserType): void
    {
        $this->declaredUserType = $declaredUserType;
    }
}
