<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\GroupCategories;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Data Transfer Object for updating a group category in Canvas LMS.
 *
 * @package CanvasLMS\Dto\GroupCategories
 */
class UpdateGroupCategoryDTO extends AbstractBaseDto
{
    /**
     * Name of the group category
     */
    public ?string $name = null;

    /**
     * Allow students to sign up for a group themselves
     * Values: 'enabled', 'restricted', null
     */
    public ?string $selfSignup = null;

    /**
     * Assigns group leaders automatically
     * Values: 'first', 'random', null
     */
    public ?string $autoLeader = null;

    /**
     * Limit the maximum number of users in each group
     * Requires self signup
     */
    public ?int $groupLimit = null;

    /**
     * The unique SIS identifier
     */
    public ?string $sisGroupCategoryId = null;

    /**
     * Create this number of groups
     */
    public ?int $createGroupCount = null;

    /**
     * (Deprecated) Create groups and evenly distribute students
     *
     * @deprecated Use assign_unassigned_members endpoint instead
     */
    public ?string $splitGroupCount = null;

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

        if ($this->selfSignup !== null) {
            $data[] = ['name' => 'self_signup', 'contents' => $this->selfSignup];
        }

        if ($this->autoLeader !== null) {
            $data[] = ['name' => 'auto_leader', 'contents' => $this->autoLeader];
        }

        if ($this->groupLimit !== null) {
            $data[] = ['name' => 'group_limit', 'contents' => (string) $this->groupLimit];
        }

        if ($this->sisGroupCategoryId !== null) {
            $data[] = ['name' => 'sis_group_category_id', 'contents' => $this->sisGroupCategoryId];
        }

        if ($this->createGroupCount !== null) {
            $data[] = ['name' => 'create_group_count', 'contents' => (string) $this->createGroupCount];
        }

        if ($this->splitGroupCount !== null) {
            $data[] = ['name' => 'split_group_count', 'contents' => $this->splitGroupCount];
        }

        return $data;
    }
}
