<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Logins;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating an existing login (pseudonym)
 */
class UpdateLoginDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The new unique ID for the login
     *
     * @var string|null
     */
    public ?string $uniqueId = null;

    /**
     * The new password for the login
     * Admins can only set password if "Password setting by admins" account setting is enabled
     *
     * @var string|null
     */
    public ?string $password = null;

    /**
     * The prior password for the login
     * Required if the caller is changing their own password
     *
     * @var string|null
     */
    public ?string $oldPassword = null;

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
     * Specify null or empty string to unassociate from known provider
     *
     * @var string|null
     */
    public ?string $authenticationProviderId = null;

    /**
     * Used to suspend or re-activate a login
     * Valid values: active, suspended
     *
     * @var string|null
     */
    public ?string $workflowState = null;

    /**
     * The declared intention of the user type
     * Valid values: administrative, observer, staff, student, student_other, teacher
     *
     * @var string|null
     */
    public ?string $declaredUserType = null;

    /**
     * Default is true. If false, any fields containing "sticky" changes will not be updated
     * See SIS CSV Format documentation for information on which fields can have SIS stickiness
     *
     * @var bool|null
     */
    public ?bool $overrideSisStickiness = null;

    /**
     * Transform DTO properties to Canvas API multipart format
     *
     * @return array<array{name: string, contents: string}>
     */
    public function toApiArray(): array
    {
        $this->validateParameters();

        $properties = get_object_vars($this);
        $modifiedProperties = [];

        foreach ($properties as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // All login update parameters go under login[*] prefix
            $apiKeyName = match ($key) {
                'uniqueId' => 'login[unique_id]',
                'password' => 'login[password]',
                'oldPassword' => 'login[old_password]',
                'sisUserId' => 'login[sis_user_id]',
                'integrationId' => 'login[integration_id]',
                'authenticationProviderId' => 'login[authentication_provider_id]',
                'workflowState' => 'login[workflow_state]',
                'declaredUserType' => 'login[declared_user_type]',
                'overrideSisStickiness' => 'override_sis_stickiness',
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
     * Validate parameters
     *
     * @throws \InvalidArgumentException
     */
    private function validateParameters(): void
    {
        // Validate workflow state if provided
        if ($this->workflowState && !in_array($this->workflowState, ['active', 'suspended'], true)) {
            throw new \InvalidArgumentException('Invalid workflowState. Valid values are: active, suspended');
        }

        // Validate declared user type if provided
        if ($this->declaredUserType) {
            $validTypes = ['administrative', 'observer', 'staff', 'student', 'student_other', 'teacher'];
            if (!in_array($this->declaredUserType, $validTypes, true)) {
                $validTypesStr = implode(', ', $validTypes);

                throw new \InvalidArgumentException("Invalid declaredUserType. Valid values are: {$validTypesStr}");
            }
        }
    }

    // Getters and setters
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

    public function getOldPassword(): ?string
    {
        return $this->oldPassword;
    }

    public function setOldPassword(?string $oldPassword): void
    {
        $this->oldPassword = $oldPassword;
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

    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    public function getDeclaredUserType(): ?string
    {
        return $this->declaredUserType;
    }

    public function setDeclaredUserType(?string $declaredUserType): void
    {
        $this->declaredUserType = $declaredUserType;
    }

    public function getOverrideSisStickiness(): ?bool
    {
        return $this->overrideSisStickiness;
    }

    public function setOverrideSisStickiness(?bool $overrideSisStickiness): void
    {
        $this->overrideSisStickiness = $overrideSisStickiness;
    }
}
