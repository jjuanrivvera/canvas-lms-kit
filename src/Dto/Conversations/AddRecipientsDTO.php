<?php

namespace CanvasLMS\Dto\Conversations;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Class AddRecipientsDTO
 *
 * Data Transfer Object for adding recipients to existing group conversations.
 *
 * @package CanvasLMS\Dto\Conversations
 */
class AddRecipientsDTO extends AbstractBaseDto
{
    /**
     * An array of recipient ids to add to the conversation.
     * These may be user ids or course/group ids prefixed with "course_" or "group_"
     * @var array<string>
     */
    public array $recipients;

    /**
     * Convert the DTO to Canvas API format
     *
     * @return array<int, array<string, string>>
     */
    public function toApiArray(): array
    {
        $data = [];

        foreach ($this->recipients as $recipient) {
            $data[] = ['name' => 'recipients[]', 'contents' => $recipient];
        }

        return $data;
    }
}
