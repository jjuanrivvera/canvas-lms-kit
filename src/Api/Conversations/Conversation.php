<?php

namespace CanvasLMS\Api\Conversations;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Objects\ConversationParticipant;
use CanvasLMS\Dto\Conversations\CreateConversationDTO;
use CanvasLMS\Dto\Conversations\UpdateConversationDTO;
use CanvasLMS\Dto\Conversations\AddMessageDTO;
use CanvasLMS\Dto\Conversations\AddRecipientsDTO;

/**
 * Class Conversation
 *
 * Represents a Canvas conversation (internal messaging system).
 * Conversations are user-scoped - they belong to the authenticated user making the API call.
 * All endpoints are at /api/v1/conversations without any context prefix.
 *
 * @package CanvasLMS\Api\Conversations
 *
 * @property int|null $id The unique identifier for the conversation
 * @property string|null $subject The subject of the conversation
 * @property string|null $workflowState The current state (read, unread, archived)
 * @property string|null $lastMessage A <=100 character preview from the most recent message
 * @property string|null $lastMessageAt The date and time at which the last message was sent
 * @property string|null $startAt The date and time at which the conversation started
 * @property int|null $messageCount The number of messages in the conversation
 * @property bool|null $subscribed Whether the current user is subscribed to the conversation
 * @property bool|null $private Whether the conversation is private
 * @property bool|null $starred Whether the conversation is starred
 * @property array<string>|null $properties Additional conversation flags (last_author, attachments, media_objects)
 * @property array<int>|null $audience Array of user ids involved in the conversation
 * @property array<string, mixed>|null $audienceContexts Most relevant shared contexts between participants
 * @property string|null $avatarUrl URL to appropriate icon for this conversation
 * @property array<ConversationParticipant>|null $participants Array of users participating in the conversation
 * @property bool|null $visible Whether the conversation is visible under current scope/filter
 * @property string|null $contextName Name of the course or group in which the conversation is occurring
 * @property array<array<string, mixed>>|null $messages Array of messages in the conversation
 */
class Conversation extends AbstractBaseApi
{
    protected static string $endpoint = '/conversations';

    public ?int $id = null;
    public ?string $subject = null;
    public ?string $workflowState = null;
    public ?string $lastMessage = null;
    public ?string $lastMessageAt = null;
    public ?string $startAt = null;
    public ?int $messageCount = null;
    public ?bool $subscribed = null;
    public ?bool $private = null;
    public ?bool $starred = null;
    /** @var array<string>|null */
    public ?array $properties = null;
    /** @var array<int>|null */
    public ?array $audience = null;
    /** @var array<string, mixed>|null */
    public ?array $audienceContexts = null;
    public ?string $avatarUrl = null;
    /** @var array<ConversationParticipant>|null */
    public ?array $participants = null;
    public ?bool $visible = null;
    public ?string $contextName = null;
    /** @var array<array<string, mixed>>|null */
    public ?array $messages = null;

    /**
     * Conversation constructor.
     * Handles special processing for participants array.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        // Process data before parent constructor
        foreach ($data as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            // Handle participants array - convert to ConversationParticipant objects
            if ($camelKey === 'participants' && is_array($value)) {
                $participants = [];
                foreach ($value as $participant) {
                    if (is_array($participant)) {
                        $participants[] = new ConversationParticipant($participant);
                    }
                }
                $data[$key] = $participants;
            }
        }

        parent::__construct($data);
    }

    /**
     * List conversations for the current user
     *
     * @param array<string, mixed> $params Optional parameters for filtering and pagination
     *                      - scope: unread, starred, archived, sent
     *                      - filter[]: Filter by courses, groups or users (e.g., "user_123", "course_456")
     *                      - filter_mode: and, or, default or
     *                      - include_all_conversation_ids: Return all conversation IDs
     *                      - include[]: participant_avatars
     * @return array<Conversation> Array of Conversation objects
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();
        $response = self::$apiClient->get(self::$endpoint, $params);

        $conversations = [];
        $data = json_decode($response->getBody(), true);

        // Handle the case where include_all_conversation_ids is true
        if (isset($data['conversations'])) {
            $conversationData = $data['conversations'];
        } else {
            $conversationData = $data;
        }

        foreach ($conversationData as $item) {
            $conversations[] = new self($item);
        }

        return $conversations;
    }

    /**
     * Get a single conversation
     *
     * @param int $id The conversation ID
     * @param array<string, mixed> $params Optional parameters
     *                      - auto_mark_as_read: Default true
     *                      - scope: unread, starred, archived
     *                      - filter[]: Used for generating "visible" in response
     *                      - filter_mode: and, or, default or
     * @return self
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();

        // Default to auto_mark_as_read = true as per Canvas API docs
        if (!isset($params['auto_mark_as_read'])) {
            $params['auto_mark_as_read'] = true;
        }

        $response = self::$apiClient->get(self::$endpoint . '/' . $id, $params);
        $data = json_decode($response->getBody(), true);

        return new self($data);
    }

    /**
     * Create a new conversation
     *
     * @param array<string, mixed>|CreateConversationDTO $data Conversation data
     * @return self
     */
    public static function create(array|CreateConversationDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateConversationDTO($data);
        }

        $response = self::$apiClient->post(self::$endpoint, ['multipart' => $data->toApiArray()]);
        $responseData = json_decode($response->getBody(), true);

        // Handle async mode where response might be empty
        if (empty($responseData)) {
            return new self([]);
        }

        // Response may be an array of conversations if group_conversation is false
        if (isset($responseData[0]) && is_array($responseData[0])) {
            return new self($responseData[0]);
        }

        return new self($responseData);
    }

    /**
     * Update a conversation
     *
     * @param array<string, mixed>|UpdateConversationDTO|null $data Optional update data
     * @return self
     */
    public function save(array|UpdateConversationDTO|null $data = null): self
    {
        self::checkApiClient();

        if ($data !== null) {
            if (is_array($data)) {
                $data = new UpdateConversationDTO($data);
            }
            $response = self::$apiClient->put(self::$endpoint . '/' . $this->id, ['multipart' => $data->toApiArray()]);
        } else {
            // Build update data from current object state
            $updateData = [];
            if ($this->workflowState !== null) {
                $updateData[] = ['name' => 'conversation[workflow_state]', 'contents' => $this->workflowState];
            }
            if ($this->subscribed !== null) {
                $updateData[] = ['name' => 'conversation[subscribed]', 'contents' => $this->subscribed ? '1' : '0'];
            }
            if ($this->starred !== null) {
                $updateData[] = ['name' => 'conversation[starred]', 'contents' => $this->starred ? '1' : '0'];
            }
            $response = self::$apiClient->put(self::$endpoint . '/' . $this->id, ['multipart' => $updateData]);
        }
        $responseData = json_decode($response->getBody(), true);

        $this->populate($responseData);
        return $this;
    }

    /**
     * Delete a conversation
     *
     * @return self
     */
    public function delete(): self
    {
        self::checkApiClient();

        self::$apiClient->delete(self::$endpoint . '/' . $this->id);
        return $this;
    }

    /**
     * Add a message to an existing conversation
     *
     * @param array<string, mixed>|AddMessageDTO $data Message data
     * @return self
     */
    public function addMessage(array|AddMessageDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new AddMessageDTO($data);
        }

        $response = self::$apiClient->post(
            self::$endpoint . '/' . $this->id . '/add_message',
            ['multipart' => $data->toApiArray()]
        );

        $responseData = json_decode($response->getBody(), true);
        $this->populate($responseData);

        return $this;
    }

    /**
     * Add recipients to an existing group conversation
     *
     * @param array<string, mixed>|AddRecipientsDTO $data Recipients data
     * @return self
     */
    public function addRecipients(array|AddRecipientsDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new AddRecipientsDTO($data);
        }

        $response = self::$apiClient->post(
            self::$endpoint . '/' . $this->id . '/add_recipients',
            ['multipart' => $data->toApiArray()]
        );

        $responseData = json_decode($response->getBody(), true);
        $this->populate($responseData);

        return $this;
    }

    /**
     * Remove messages from a conversation
     *
     * @param array<int> $messageIds Array of message IDs to remove
     * @return self
     */
    public function removeMessages(array $messageIds): self
    {
        self::checkApiClient();

        // Build multipart format for array parameters
        $data = [];
        foreach ($messageIds as $messageId) {
            $data[] = ['name' => 'remove[]', 'contents' => (string)$messageId];
        }

        $response = self::$apiClient->post(
            self::$endpoint . '/' . $this->id . '/remove_messages',
            ['multipart' => $data]
        );

        $responseData = json_decode($response->getBody(), true);
        $this->populate($responseData);

        return $this;
    }

    /**
     * Batch update multiple conversations
     *
     * @param array<int> $conversationIds Array of conversation IDs to update
     * @param array<string, mixed> $data Update data
     *                     (event: mark_as_read, mark_as_unread, star, unstar, archive, destroy)
     * @return array<string, mixed> Progress information
     */
    public static function batchUpdate(array $conversationIds, array $data): array
    {
        self::checkApiClient();

        // Build multipart format for array parameters
        $updateData = [];
        foreach ($conversationIds as $conversationId) {
            $updateData[] = ['name' => 'conversation_ids[]', 'contents' => (string)$conversationId];
        }

        // Add other data fields
        foreach ($data as $key => $value) {
            $updateData[] = ['name' => $key, 'contents' => (string)$value];
        }

        $response = self::$apiClient->put(self::$endpoint, ['multipart' => $updateData]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Mark all conversations as read
     *
     * @return bool Success status
     */
    public static function markAllAsRead(): bool
    {
        self::checkApiClient();

        $response = self::$apiClient->post(self::$endpoint . '/mark_all_as_read', []);

        return $response->getStatusCode() === 200;
    }

    /**
     * Get unread conversation count
     *
     * @return int Unread count
     */
    public static function getUnreadCount(): int
    {
        self::checkApiClient();

        $response = self::$apiClient->get(self::$endpoint . '/unread_count');
        $data = json_decode($response->getBody(), true);

        return $data['unread_count'] ?? 0;
    }

    /**
     * Get running conversation batches
     *
     * @return array<array<string, mixed>> Array of batch information
     */
    public static function getRunningBatches(): array
    {
        self::checkApiClient();

        $response = self::$apiClient->get(self::$endpoint . '/batches');

        return json_decode($response->getBody(), true);
    }

    /**
     * Populate the object with new data
     * Override to handle camelCase properties and participant objects
     *
     * @param array<string, mixed> $data
     * @return void
     */
    protected function populate(array $data): void
    {
        foreach ($data as $key => $value) {
            // Convert snake_case to camelCase
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $camelKey)) {
                // Handle participants array - convert to ConversationParticipant objects
                if ($camelKey === 'participants' && is_array($value)) {
                    $this->participants = [];
                    foreach ($value as $participant) {
                        if (is_array($participant)) {
                            $this->participants[] = new ConversationParticipant($participant);
                        }
                    }
                } else {
                    $this->{$camelKey} = $value;
                }
            }
        }
    }

    /**
     * Mark conversation as read
     *
     * @return self
     */
    public function markAsRead(): self
    {
        $this->workflowState = 'read';
        return $this->save();
    }

    /**
     * Mark conversation as unread
     *
     * @return self
     */
    public function markAsUnread(): self
    {
        $this->workflowState = 'unread';
        return $this->save();
    }

    /**
     * Star the conversation
     *
     * @return self
     */
    public function star(): self
    {
        $this->starred = true;
        return $this->save();
    }

    /**
     * Unstar the conversation
     *
     * @return self
     */
    public function unstar(): self
    {
        $this->starred = false;
        return $this->save();
    }

    /**
     * Archive the conversation
     *
     * @return self
     */
    public function archive(): self
    {
        $this->workflowState = 'archived';
        return $this->save();
    }
}
