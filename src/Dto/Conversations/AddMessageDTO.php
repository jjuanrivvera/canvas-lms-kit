<?php

namespace CanvasLMS\Dto\Conversations;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Class AddMessageDTO
 *
 * Data Transfer Object for adding messages to existing conversations.
 *
 * @package CanvasLMS\Dto\Conversations
 */
class AddMessageDTO extends AbstractBaseDto
{
    /**
     * The message body to be sent
     * @var string
     */
    public string $body;

    /**
     * An array of attachment IDs. These must be files previously uploaded
     * to the sender's "conversation attachments" folder
     * @var array<int>|null
     */
    public ?array $attachmentIds = null;

    /**
     * Media comment id of an audio or video file
     * @var string|null
     */
    public ?string $mediaCommentId = null;

    /**
     * Type of the associated media file
     * @var string|null
     */
    public ?string $mediaCommentType = null;

    /**
     * An array of recipient ids to add to the conversation.
     * These may be user ids or course/group ids prefixed accordingly
     * @var array<string>|null
     */
    public ?array $recipients = null;

    /**
     * An array of message ids from this conversation to forward to recipients
     * @var array<int>|null
     */
    public ?array $includedMessages = null;

    /**
     * Whether to send as a private message to each recipient
     * @var bool|null
     */
    public ?bool $userNote = null;

    /**
     * Convert the DTO to Canvas API format
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $data = [];

        // Required field
        $data['body'] = $this->body;

        // Optional fields
        if ($this->attachmentIds !== null) {
            foreach ($this->attachmentIds as $attachmentId) {
                $data['attachment_ids[]'] = $attachmentId;
            }
        }
        if ($this->mediaCommentId !== null) {
            $data['media_comment_id'] = $this->mediaCommentId;
        }
        if ($this->mediaCommentType !== null) {
            $data['media_comment_type'] = $this->mediaCommentType;
        }
        if ($this->recipients !== null) {
            foreach ($this->recipients as $recipient) {
                $data['recipients[]'] = $recipient;
            }
        }
        if ($this->includedMessages !== null) {
            foreach ($this->includedMessages as $messageId) {
                $data['included_messages[]'] = $messageId;
            }
        }
        if ($this->userNote !== null) {
            $data['user_note'] = $this->userNote ? '1' : '0';
        }

        return $data;
    }
}
