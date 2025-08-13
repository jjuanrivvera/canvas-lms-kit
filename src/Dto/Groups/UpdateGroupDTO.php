<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Groups;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Data Transfer Object for updating a group in Canvas LMS.
 *
 * @package CanvasLMS\Dto\Groups
 */
class UpdateGroupDTO extends AbstractBaseDto
{
    /**
     * The name of the group
     */
    public ?string $name = null;

    /**
     * A description of the group
     */
    public ?string $description = null;

    /**
     * Whether the group is public (applies only to community groups)
     */
    public ?bool $isPublic = null;

    /**
     * Who can join the group: 'invitation_only', 'request', 'free_to_join'
     */
    public ?string $joinLevel = null;

    /**
     * The storage quota for the group, in megabytes
     */
    public ?int $storageQuotaMb = null;

    /**
     * The SIS ID of the group
     */
    public ?string $sisGroupId = null;

    /**
     * The ID of the avatar attachment to use for the group
     */
    public ?int $avatarId = null;

    /**
     * An array of user IDs to add as group members
     * @var array<int>|null
     */
    public ?array $members = null;

    /**
     * Override any sis stickiness
     */
    public ?bool $overrideSisStickiness = null;

    /**
     * Convert DTO to API-compatible array format
     *
     * @return array<array{name: string, contents: string}>
     */
    public function toApiArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data[] = ['name' => 'name', 'contents' => $this->name];
        }

        if ($this->description !== null) {
            $data[] = ['name' => 'description', 'contents' => $this->description];
        }

        if ($this->isPublic !== null) {
            $data[] = ['name' => 'is_public', 'contents' => $this->isPublic ? 'true' : 'false'];
        }

        if ($this->joinLevel !== null) {
            $data[] = ['name' => 'join_level', 'contents' => $this->joinLevel];
        }

        if ($this->storageQuotaMb !== null) {
            $data[] = ['name' => 'storage_quota_mb', 'contents' => (string)$this->storageQuotaMb];
        }

        if ($this->sisGroupId !== null) {
            $data[] = ['name' => 'sis_group_id', 'contents' => $this->sisGroupId];
        }

        if ($this->avatarId !== null) {
            $data[] = ['name' => 'avatar_id', 'contents' => (string)$this->avatarId];
        }

        if ($this->members !== null) {
            foreach ($this->members as $memberId) {
                $data[] = ['name' => 'members[]', 'contents' => (string)$memberId];
            }
        }

        if ($this->overrideSisStickiness !== null) {
            $data[] = [
                'name' => 'override_sis_stickiness',
                'contents' => $this->overrideSisStickiness ? 'true' : 'false'
            ];
        }

        return $data;
    }
}
