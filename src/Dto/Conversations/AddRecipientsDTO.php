<?php

namespace CanvasLMS\Dto\Conversations;

/**
 * Class AddRecipientsDTO
 *
 * Data Transfer Object for adding recipients to existing group conversations.
 * This DTO does not extend AbstractBaseDto because Conversations API
 * requires multipart format which differs from other Canvas APIs.
 *
 * @package CanvasLMS\Dto\Conversations
 */
class AddRecipientsDTO
{
    /**
     * An array of recipient ids to add to the conversation.
     * These may be user ids or course/group ids prefixed with "course_" or "group_"
     * @var array<string>
     */
    public array $recipients;

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

        foreach ($this->recipients as $recipient) {
            $data[] = ['name' => 'recipients[]', 'contents' => $recipient];
        }

        return $data;
    }
}
