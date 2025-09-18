<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Conversations;

/**
 * Class CreateConversationDTO
 *
 * Data Transfer Object for creating new conversations.
 * This DTO does not extend AbstractBaseDto because Conversations API
 * requires multipart format which differs from other Canvas APIs.
 *
 * @package CanvasLMS\Dto\Conversations
 */
class CreateConversationDTO
{
    /**
     * An array of recipient ids. These may be user ids or course/group ids
     * prefixed with "course_" or "group_" respectively
     *
     * @var array<string>
     */
    public array $recipients;

    /**
     * The subject of the conversation (max 255 characters)
     *
     * @var string|null
     */
    public ?string $subject = null;

    /**
     * The message body to be sent
     *
     * @var string
     */
    public string $body;

    /**
     * Forces a new message to be created, even if there is an existing private conversation
     *
     * @var bool|null
     */
    public ?bool $forceNew = null;

    /**
     * When false, individual private conversations will be created with each recipient.
     * If true, this will be a group conversation
     *
     * @var bool|null
     */
    public ?bool $groupConversation = null;

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
     * Determines whether messages will be created/sent synchronously or asynchronously
     *
     * @var string|null
     */
    public ?string $mode = null;

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
     * The course or group that is the context for this conversation
     *
     * @var string|null
     */
    public ?string $contextCode = null;

    /**
     * Whether to send as a bulk message to each recipient
     *
     * @var bool|null
     */
    public ?bool $bulkMessage = null;

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

        // Required fields
        if (!empty($this->recipients)) {
            foreach ($this->recipients as $recipient) {
                $data[] = ['name' => 'recipients[]', 'contents' => $recipient];
            }
        }
        $data[] = ['name' => 'body', 'contents' => $this->body];

        // Optional fields
        if ($this->subject !== null) {
            $data[] = ['name' => 'subject', 'contents' => $this->subject];
        }
        if ($this->forceNew !== null) {
            $data[] = ['name' => 'force_new', 'contents' => $this->forceNew ? '1' : '0'];
        }
        if ($this->groupConversation !== null) {
            $data[] = ['name' => 'group_conversation', 'contents' => $this->groupConversation ? '1' : '0'];
        }
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
        if ($this->mode !== null) {
            $data[] = ['name' => 'mode', 'contents' => $this->mode];
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
        if ($this->contextCode !== null) {
            $data[] = ['name' => 'context_code', 'contents' => $this->contextCode];
        }
        if ($this->bulkMessage !== null) {
            $data[] = ['name' => 'bulk_message', 'contents' => $this->bulkMessage ? '1' : '0'];
        }

        return $data;
    }
}
