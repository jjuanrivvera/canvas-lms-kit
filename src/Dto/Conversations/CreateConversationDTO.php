<?php

namespace CanvasLMS\Dto\Conversations;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Class CreateConversationDTO
 *
 * Data Transfer Object for creating new conversations.
 *
 * @package CanvasLMS\Dto\Conversations
 */
class CreateConversationDTO extends AbstractBaseDto
{
    /**
     * An array of recipient ids. These may be user ids or course/group ids
     * prefixed with "course_" or "group_" respectively
     * @var array<string>
     */
    public array $recipients;

    /**
     * The subject of the conversation (max 255 characters)
     * @var string|null
     */
    public ?string $subject = null;

    /**
     * The message body to be sent
     * @var string
     */
    public string $body;

    /**
     * Forces a new message to be created, even if there is an existing private conversation
     * @var bool|null
     */
    public ?bool $forceNew = null;

    /**
     * When false, individual private conversations will be created with each recipient.
     * If true, this will be a group conversation
     * @var bool|null
     */
    public ?bool $groupConversation = null;

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
     * Determines whether messages will be created/sent synchronously or asynchronously
     * @var string|null
     */
    public ?string $mode = null;

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
     * The course or group that is the context for this conversation
     * @var string|null
     */
    public ?string $contextCode = null;

    /**
     * Convert the DTO to Canvas API format
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $data = [];

        // Required fields
        foreach ($this->recipients as $recipient) {
            $data['recipients[]'] = $recipient;
        }
        $data['body'] = $this->body;

        // Optional fields
        if ($this->subject !== null) {
            $data['subject'] = $this->subject;
        }
        if ($this->forceNew !== null) {
            $data['force_new'] = $this->forceNew ? '1' : '0';
        }
        if ($this->groupConversation !== null) {
            $data['group_conversation'] = $this->groupConversation ? '1' : '0';
        }
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
        if ($this->mode !== null) {
            $data['mode'] = $this->mode;
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
        if ($this->contextCode !== null) {
            $data['context_code'] = $this->contextCode;
        }

        return $data;
    }
}
