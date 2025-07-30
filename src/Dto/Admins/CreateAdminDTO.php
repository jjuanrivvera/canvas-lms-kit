<?php

namespace CanvasLMS\Dto\Admins;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for creating a new Canvas admin
 *
 * @package CanvasLMS\Dto\Admins
 */
class CreateAdminDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The ID of the user to be made an admin
     * @var int
     */
    public int $userId;

    /**
     * The role to give the user. Can be a string role name or integer role ID
     * @var string|int|null
     */
    public string|int|null $role = null;

    /**
     * The ID of the role to assign. Alternative to using role name
     * @var int|null
     */
    public ?int $roleId = null;

    /**
     * Send a notification email to the new admin (defaults to true)
     * @var bool|null
     */
    public ?bool $sendConfirmation = null;

    /**
     * Get the user ID
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Set the user ID
     *
     * @param int $userId
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get the role
     *
     * @return string|int|null
     */
    public function getRole(): string|int|null
    {
        return $this->role;
    }

    /**
     * Set the role
     *
     * @param string|int|null $role
     * @return self
     */
    public function setRole(string|int|null $role): self
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Get the role ID
     *
     * @return int|null
     */
    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    /**
     * Set the role ID
     *
     * @param int|null $roleId
     * @return self
     */
    public function setRoleId(?int $roleId): self
    {
        $this->roleId = $roleId;
        return $this;
    }

    /**
     * Get send confirmation flag
     *
     * @return bool|null
     */
    public function getSendConfirmation(): ?bool
    {
        return $this->sendConfirmation;
    }

    /**
     * Set send confirmation flag
     *
     * @param bool|null $sendConfirmation
     * @return self
     */
    public function setSendConfirmation(?bool $sendConfirmation): self
    {
        $this->sendConfirmation = $sendConfirmation;
        return $this;
    }

    /**
     * Convert the DTO to an array for API requests
     *
     * @return array<int, array{name: string, contents: mixed}>
     */
    public function toApiArray(): array
    {
        $modifiedProperties = [];

        // User ID is required
        $modifiedProperties[] = [
            'name' => 'user_id',
            'contents' => $this->userId
        ];

        // Role can be either string or ID
        if ($this->role !== null) {
            $modifiedProperties[] = [
                'name' => 'role',
                'contents' => $this->role
            ];
        } elseif ($this->roleId !== null) {
            $modifiedProperties[] = [
                'name' => 'role_id',
                'contents' => $this->roleId
            ];
        }

        // Send confirmation
        if ($this->sendConfirmation !== null) {
            $modifiedProperties[] = [
                'name' => 'send_confirmation',
                'contents' => $this->sendConfirmation ? '1' : '0'
            ];
        }

        return $modifiedProperties;
    }
}
