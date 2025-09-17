<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Conversations;

/**
 * Class AddMessageDTO
 *
 * Data Transfer Object for adding messages to existing conversations.
 * This DTO does not extend AbstractBaseDto because Conversations API
 * requires multipart format which differs from other Canvas APIs.
 *
 * @package CanvasLMS\Dto\Conversations
 */
class AddMessageDTO
{
    /**
     * The message body to be sent
     *
     * @var string
     */
    public string $body;

    /**
     * An array of attachment IDs. These must be files previously uploaded
     * to the sender's "conversation attachments" folder
     *
     * @var array<int>|null
     */
    public ?array $attachmentIds = null;

    /**
     * Media comment id of an audio or video file
     *
     * @var string|null
     */
    public ?string $mediaCommentId = null;

    /**
     * Type of the associated media file
     *
     * @var string|null
     */
    public ?string $mediaCommentType = null;

    /**
     * An array of recipient ids to add to the conversation.
     * These may be user ids or course/group ids prefixed accordingly
     *
     * @var array<string>|null
     */
    public ?array $recipients = null;

    /**
     * An array of message ids from this conversation to forward to recipients
     *
     * @var array<int>|null
     */
    public ?array $includedMessages = null;

    /**
     * Whether to send as a private message to each recipient
     *
     * @var bool|null
     */
    public ?bool $userNote = null;

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

        // Required field
        $data[] = ['name' => 'body', 'contents' => $this->body];

        // Optional fields
        if ($this->attachmentIds !== null) {
            foreach ($this->attachmentIds as $attachmentId) {
                $data[] = ['name' => 'attachment_ids[]', 'contents' => (string) $attachmentId];
            }
        }
        if ($this->mediaCommentId !== null) {
            $data[] = ['name' => 'media_comment_id', 'contents' => $this->mediaCommentId];
        }
        if ($this->mediaCommentType !== null) {
            $data[] = ['name' => 'media_comment_type', 'contents' => $this->mediaCommentType];
        }
        if ($this->recipients !== null) {
            foreach ($this->recipients as $recipient) {
                $data[] = ['name' => 'recipients[]', 'contents' => $recipient];
            }
        }
        if ($this->includedMessages !== null) {
            foreach ($this->includedMessages as $messageId) {
                $data[] = ['name' => 'included_messages[]', 'contents' => (string) $messageId];
            }
        }
        if ($this->userNote !== null) {
            $data[] = ['name' => 'user_note', 'contents' => $this->userNote ? '1' : '0'];
        }

        return $data;
    }
}
