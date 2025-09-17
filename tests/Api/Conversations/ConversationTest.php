<?php

declare(strict_types=1);

namespace Tests\Api\Conversations;

use CanvasLMS\Api\Conversations\Conversation;
use CanvasLMS\Dto\Conversations\CreateConversationDTO;
use CanvasLMS\Http\HttpClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ConversationTest extends TestCase
{
    /**
     * @var mixed
     */
    private $httpClientMock;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        Conversation::setApiClient($this->httpClientMock);
    }

    /**
     * Create a mock response
     *
     * @param array $data
     * @param int $statusCode
     *
     * @return Response
     */
    private function createMockResponse(array $data, int $statusCode = 200): Response
    {
        return new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($data));
    }

    /**
     * Create a mock response with status code
     *
     * @param array $data
     * @param int $statusCode
     *
     * @return Response
     */
    private function createMockResponseWithStatus(array $data, int $statusCode): Response
    {
        return $this->createMockResponse($data, $statusCode);
    }

    /**
     * Test fetching all conversations
     */
    public function testGet(): void
    {
        $mockResponse = $this->createMockResponse([
            [
                'id' => 1,
                'subject' => 'Test Conversation',
                'workflow_state' => 'unread',
                'last_message' => 'Hello there',
                'message_count' => 2,
                'starred' => false,
                'private' => true,
            ],
            [
                'id' => 2,
                'subject' => 'Another Conversation',
                'workflow_state' => 'read',
                'last_message' => 'How are you?',
                'message_count' => 5,
                'starred' => true,
                'private' => false,
            ],
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/conversations', [])
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $conversations = Conversation::get();

        $this->assertCount(2, $conversations);
        $this->assertInstanceOf(Conversation::class, $conversations[0]);
        $this->assertEquals(1, $conversations[0]->id);
        $this->assertEquals('Test Conversation', $conversations[0]->subject);
        $this->assertEquals('unread', $conversations[0]->workflowState);
    }

    /**
     * Test fetching all conversations with include_all_conversation_ids
     */
    public function testGetWithAllIds(): void
    {
        $mockResponse = $this->createMockResponse([
            'conversations' => [
                [
                    'id' => 1,
                    'subject' => 'Test Conversation',
                    'workflow_state' => 'unread',
                ],
            ],
            'conversation_ids' => [1, 2, 3, 4, 5],
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/conversations', ['include_all_conversation_ids' => true])
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $conversations = Conversation::get(['include_all_conversation_ids' => true]);

        $this->assertCount(1, $conversations);
        $this->assertEquals(1, $conversations[0]->id);
    }

    /**
     * Test finding a single conversation
     */
    public function testFind(): void
    {
        $mockResponse = $this->createMockResponse([
            'id' => 123,
            'subject' => 'Important Discussion',
            'workflow_state' => 'read',
            'last_message' => 'Thanks for the update',
            'message_count' => 10,
            'messages' => [
                [
                    'id' => 1,
                    'body' => 'First message',
                    'author_id' => 456,
                ],
            ],
            'participants' => [
                ['id' => 456, 'name' => 'John Doe'],
            ],
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/conversations/123', ['auto_mark_as_read' => true])
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $conversation = Conversation::find(123);

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals(123, $conversation->id);
        $this->assertEquals('Important Discussion', $conversation->subject);
        $this->assertIsArray($conversation->messages);
        $this->assertCount(1, $conversation->messages);
    }

    /**
     * Test creating a new conversation
     */
    public function testCreate(): void
    {
        $createData = [
            'recipients' => ['1', '2', 'course_123'],
            'subject' => 'New Conversation',
            'body' => 'Hello everyone!',
            'groupConversation' => true,
        ];

        $mockResponse = $this->createMockResponse([
            'id' => 456,
            'subject' => 'New Conversation',
            'workflow_state' => 'read',
            'last_message' => 'Hello everyone!',
            'message_count' => 1,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                '/conversations',
                $this->callback(function ($options) {
                    // Check that the data is in the expected multipart format
                    if (!isset($options['multipart']) || !is_array($options['multipart'])) {
                        return false;
                    }

                    $hasRecipients = false;
                    $hasBody = false;
                    $bodyContent = '';

                    foreach ($options['multipart'] as $field) {
                        if ($field['name'] === 'recipients[]') {
                            $hasRecipients = true;
                        }
                        if ($field['name'] === 'body') {
                            $hasBody = true;
                            $bodyContent = $field['contents'];
                        }
                    }

                    return $hasRecipients && $hasBody && $bodyContent === 'Hello everyone!';
                })
            )
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $conversation = Conversation::create($createData);

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals(456, $conversation->id);
        $this->assertEquals('New Conversation', $conversation->subject);
    }

    /**
     * Test creating a conversation with DTO
     */
    public function testCreateWithDto(): void
    {
        $dto = new CreateConversationDTO([
            'recipients' => ['user_1', 'group_456'],
            'subject' => 'DTO Conversation',
            'body' => 'Created with DTO',
            'groupConversation' => false,
        ]);

        $mockResponse = $this->createMockResponse([
            [
                'id' => 789,
                'subject' => 'DTO Conversation',
                'workflow_state' => 'read',
            ],
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $conversation = Conversation::create($dto);

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals(789, $conversation->id);
    }

    /**
     * Test updating a conversation
     */
    public function testSave(): void
    {
        $conversation = new Conversation(['id' => 123, 'starred' => false]);
        $conversation->starred = true;
        $conversation->workflowState = 'archived';

        $mockResponse = $this->createMockResponse([
            'id' => 123,
            'starred' => true,
            'workflow_state' => 'archived',
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                '/conversations/123',
                $this->callback(function ($options) {
                    // Check that the data is in the expected multipart format
                    if (!isset($options['multipart']) || !is_array($options['multipart'])) {
                        return false;
                    }

                    $hasStarred = false;
                    $hasWorkflowState = false;

                    foreach ($options['multipart'] as $field) {
                        if ($field['name'] === 'conversation[starred]') {
                            $hasStarred = true;
                        }
                        if ($field['name'] === 'conversation[workflow_state]') {
                            $hasWorkflowState = true;
                        }
                    }

                    return $hasStarred && $hasWorkflowState;
                })
            )
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $result = $conversation->save();

        $this->assertSame($conversation, $result);
        $this->assertTrue($conversation->starred);
        $this->assertEquals('archived', $conversation->workflowState);
    }

    /**
     * Test deleting a conversation
     */
    public function testDelete(): void
    {
        $conversation = new Conversation(['id' => 123]);

        $mockResponse = $this->createMockResponse([]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('/conversations/123')
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $result = $conversation->delete();

        $this->assertSame($conversation, $result);
    }

    /**
     * Test adding a message to a conversation
     */
    public function testAddMessage(): void
    {
        $conversation = new Conversation(['id' => 123]);

        $messageData = [
            'body' => 'This is a new message',
            'attachmentIds' => [456, 789],
        ];

        $mockResponse = $this->createMockResponse([
            'id' => 123,
            'last_message' => 'This is a new message',
            'message_count' => 5,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                '/conversations/123/add_message',
                $this->callback(function ($options) {
                    // Check that the data is in the expected multipart format
                    if (!isset($options['multipart']) || !is_array($options['multipart'])) {
                        return false;
                    }

                    $hasBody = false;
                    $bodyContent = '';

                    foreach ($options['multipart'] as $field) {
                        if ($field['name'] === 'body') {
                            $hasBody = true;
                            $bodyContent = $field['contents'];
                        }
                    }

                    return $hasBody && $bodyContent === 'This is a new message';
                })
            )
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $result = $conversation->addMessage($messageData);

        $this->assertSame($conversation, $result);
        $this->assertEquals('This is a new message', $conversation->lastMessage);
    }

    /**
     * Test adding recipients to a conversation
     */
    public function testAddRecipients(): void
    {
        $conversation = new Conversation(['id' => 123]);

        $recipientData = [
            'recipients' => ['user_456', 'user_789'],
        ];

        $mockResponse = $this->createMockResponse([
            'id' => 123,
            'audience' => [456, 789],
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('/conversations/123/add_recipients')
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $result = $conversation->addRecipients($recipientData);

        $this->assertSame($conversation, $result);
        $this->assertIsArray($conversation->audience);
    }

    /**
     * Test removing messages from a conversation
     */
    public function testRemoveMessages(): void
    {
        $conversation = new Conversation(['id' => 123]);

        $mockResponse = $this->createMockResponse([
            'id' => 123,
            'message_count' => 3,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                '/conversations/123/remove_messages',
                $this->callback(function ($options) {
                    // Check that the data is in the expected multipart format
                    if (!isset($options['multipart']) || !is_array($options['multipart'])) {
                        return false;
                    }

                    $removeCount = 0;
                    foreach ($options['multipart'] as $field) {
                        if ($field['name'] === 'remove[]') {
                            $removeCount++;
                        }
                    }

                    return $removeCount === 3; // We're removing 3 messages
                })
            )
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $result = $conversation->removeMessages([1, 2, 3]);

        $this->assertSame($conversation, $result);
        $this->assertEquals(3, $conversation->messageCount);
    }

    /**
     * Test batch updating conversations
     */
    public function testBatchUpdate(): void
    {
        $mockResponse = $this->createMockResponse([
            'progress' => 100,
            'message' => 'Complete',
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                '/conversations',
                $this->callback(function ($options) {
                    // Check that the data is in the expected multipart format
                    if (!isset($options['multipart']) || !is_array($options['multipart'])) {
                        return false;
                    }

                    $conversationCount = 0;
                    $hasEvent = false;

                    foreach ($options['multipart'] as $field) {
                        if ($field['name'] === 'conversation_ids[]') {
                            $conversationCount++;
                        }
                        if ($field['name'] === 'event' && $field['contents'] === 'mark_as_read') {
                            $hasEvent = true;
                        }
                    }

                    return $conversationCount === 3 && $hasEvent;
                })
            )
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $result = Conversation::batchUpdate([1, 2, 3], ['event' => 'mark_as_read']);

        $this->assertIsArray($result);
        $this->assertEquals(100, $result['progress']);
    }

    /**
     * Test marking all conversations as read
     */
    public function testMarkAllAsRead(): void
    {
        $mockResponse = $this->createMockResponseWithStatus([], 200);

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('/conversations/mark_all_as_read', [])
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $result = Conversation::markAllAsRead();

        $this->assertTrue($result);
    }

    /**
     * Test getting unread count
     */
    public function testGetUnreadCount(): void
    {
        $mockResponse = $this->createMockResponse([
            'unread_count' => 42,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/conversations/unread_count')
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $count = Conversation::getUnreadCount();

        $this->assertEquals(42, $count);
    }

    /**
     * Test getting running batches
     */
    public function testGetRunningBatches(): void
    {
        $mockResponse = $this->createMockResponse([
            [
                'id' => 1,
                'workflow_state' => 'running',
                'completion' => 0.5,
            ],
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/conversations/batches')
            ->willReturn($mockResponse);

        Conversation::setApiClient($this->httpClientMock);
        $batches = Conversation::getRunningBatches();

        $this->assertIsArray($batches);
        $this->assertEquals('running', $batches[0]['workflow_state']);
    }

    /**
     * Test convenience methods
     */
    public function testConvenienceMethods(): void
    {
        $conversation = new Conversation(['id' => 123]);

        Conversation::setApiClient($this->httpClientMock);

        // Test markAsRead
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($this->createMockResponse(['id' => 123, 'workflow_state' => 'read']));

        $conversation->markAsRead();
        $this->assertEquals('read', $conversation->workflowState);

        // Reset mock for markAsUnread
        $this->httpClientMock = $this->createMock(HttpClient::class);
        Conversation::setApiClient($this->httpClientMock);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($this->createMockResponse(['id' => 123, 'workflow_state' => 'unread']));

        $conversation->markAsUnread();
        $this->assertEquals('unread', $conversation->workflowState);

        // Reset mock for star
        $this->httpClientMock = $this->createMock(HttpClient::class);
        Conversation::setApiClient($this->httpClientMock);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($this->createMockResponse(['id' => 123, 'starred' => true]));

        $conversation->star();
        $this->assertTrue($conversation->starred);

        // Reset mock for unstar
        $this->httpClientMock = $this->createMock(HttpClient::class);
        Conversation::setApiClient($this->httpClientMock);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($this->createMockResponse(['id' => 123, 'starred' => false]));

        $conversation->unstar();
        $this->assertFalse($conversation->starred);

        // Reset mock for archive
        $this->httpClientMock = $this->createMock(HttpClient::class);
        Conversation::setApiClient($this->httpClientMock);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($this->createMockResponse(['id' => 123, 'workflow_state' => 'archived']));

        $conversation->archive();
        $this->assertEquals('archived', $conversation->workflowState);
    }
}
