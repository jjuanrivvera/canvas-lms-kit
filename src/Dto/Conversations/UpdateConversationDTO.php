<?php

namespace CanvasLMS\Dto\Conversations;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Class UpdateConversationDTO
 *
 * Data Transfer Object for updating existing conversations.
 *
 * @package CanvasLMS\Dto\Conversations
 */
class UpdateConversationDTO extends AbstractBaseDto
{
    /**
     * The workflow state of the conversation (read, unread, archived)
     * @var string|null
     */
    public ?string $workflowState = null;

    /**
     * Whether the user is subscribed to the conversation
     * @var bool|null
     */
    public ?bool $subscribed = null;

    /**
     * Whether the conversation is starred
     * @var bool|null
     */
    public ?bool $starred = null;

    /**
     * Used when generating "visible" in the API response
     * @var string|null
     */
    public ?string $scope = null;

    /**
     * Used when generating "visible" in the API response
     * @var array<string>|null
     */
    public ?array $filter = null;

    /**
     * Used when generating "visible" in the API response
     * @var string|null
     */
    public ?string $filterMode = null;

    /**
     * Convert the DTO to Canvas API format
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
