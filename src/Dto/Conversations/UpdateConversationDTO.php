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
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $data = [];

        if ($this->workflowState !== null) {
            $data['conversation[workflow_state]'] = $this->workflowState;
        }
        if ($this->subscribed !== null) {
            $data['conversation[subscribed]'] = $this->subscribed ? '1' : '0';
        }
        if ($this->starred !== null) {
            $data['conversation[starred]'] = $this->starred ? '1' : '0';
        }
        if ($this->scope !== null) {
            $data['scope'] = $this->scope;
        }
        if ($this->filter !== null) {
            foreach ($this->filter as $filterItem) {
                $data['filter[]'] = $filterItem;
            }
        }
        if ($this->filterMode !== null) {
            $data['filter_mode'] = $this->filterMode;
        }

        return $data;
    }
}
