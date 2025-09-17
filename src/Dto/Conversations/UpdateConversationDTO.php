<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Conversations;

/**
 * Class UpdateConversationDTO
 *
 * Data Transfer Object for updating existing conversations.
 * This DTO does not extend AbstractBaseDto because Conversations API
 * requires multipart format which differs from other Canvas APIs.
 *
 * @package CanvasLMS\Dto\Conversations
 */
class UpdateConversationDTO
{
    /**
     * The workflow state of the conversation (read, unread, archived)
     *
     * @var string|null
     */
    public ?string $workflowState = null;

    /**
     * Whether the user is subscribed to the conversation
     *
     * @var bool|null
     */
    public ?bool $subscribed = null;

    /**
     * Whether the conversation is starred
     *
     * @var bool|null
     */
    public ?bool $starred = null;

    /**
     * Used when generating "visible" in the API response
     *
     * @var string|null
     */
    public ?string $scope = null;

    /**
     * Used when generating "visible" in the API response
     *
     * @var array<string>|null
     */
    public ?array $filter = null;

    /**
     * Used when generating "visible" in the API response
     *
     * @var string|null
     */
    public ?string $filterMode = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            // Convert snake_case to camelCase
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $camelKey)) {
                $this->{$camelKey} = $value;
            }
        }
    }

    /**
     * Convert the DTO to Canvas API multipart format
     *
     * @return array<int, array<string, string>>
     */
    public function toApiArray(): array
    {
        $data = [];

        if ($this->workflowState !== null) {
            $data[] = ['name' => 'conversation[workflow_state]', 'contents' => $this->workflowState];
        }
        if ($this->subscribed !== null) {
            $data[] = ['name' => 'conversation[subscribed]', 'contents' => $this->subscribed ? '1' : '0'];
        }
        if ($this->starred !== null) {
            $data[] = ['name' => 'conversation[starred]', 'contents' => $this->starred ? '1' : '0'];
        }
        if ($this->scope !== null) {
            $data[] = ['name' => 'scope', 'contents' => $this->scope];
        }
        if ($this->filter !== null) {
            foreach ($this->filter as $filterItem) {
                $data[] = ['name' => 'filter[]', 'contents' => $filterItem];
            }
        }
        if ($this->filterMode !== null) {
            $data[] = ['name' => 'filter_mode', 'contents' => $this->filterMode];
        }

        return $data;
    }
}
