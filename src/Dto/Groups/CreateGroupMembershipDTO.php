<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Groups;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Data Transfer Object for creating a group membership in Canvas LMS.
 *
 * @package CanvasLMS\Dto\Groups
 */
class CreateGroupMembershipDTO extends AbstractBaseDto
{
    /**
     * User ID to add to the group
     */
    public ?int $userId = null;

    /**
     * Array of user IDs to add to the group (for bulk operations)
     * @var array<int>|null
     */
    public ?array $userIds = null;

    /**
     * Email addresses to invite to the group
     * @var array<string>|null
     */
    public ?array $invitees = null;

    /**
     * Whether to make the user(s) a moderator
     */
    public ?bool $moderator = null;

    /**
     * Workflow state for the membership
     * Values: 'accepted', 'invited', 'requested'
     */
    public ?string $workflowState = null;

    /**
     * Convert DTO to API-compatible array format
     *
     * @return array<array{name: string, contents: string}>
     */
    public function toApiArray(): array
    {
        $data = [];

        if ($this->userId !== null) {
            $data[] = ['name' => 'user_id', 'contents' => (string)$this->userId];
        }

        if ($this->userIds !== null) {
            foreach ($this->userIds as $userId) {
                $data[] = ['name' => 'user_ids[]', 'contents' => (string)$userId];
            }
        }

        if ($this->invitees !== null) {
            foreach ($this->invitees as $email) {
                $data[] = ['name' => 'invitees[]', 'contents' => $email];
            }
        }

        if ($this->moderator !== null) {
            $data[] = ['name' => 'moderator', 'contents' => $this->moderator ? 'true' : 'false'];
        }

        if ($this->workflowState !== null) {
            $data[] = ['name' => 'workflow_state', 'contents' => $this->workflowState];
        }

        return $data;
    }
}
