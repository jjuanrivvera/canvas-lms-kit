<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Conferences;

use CanvasLMS\Api\Conferences\Conference;
use CanvasLMS\Config;
use CanvasLMS\Dto\Conferences\CreateConferenceDTO;
use CanvasLMS\Dto\Conferences\UpdateConferenceDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Objects\ConferenceRecording;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ConferenceTest extends TestCase
{
    private $mockHttpClient;
    private $mockResponse;
    private $mockStream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);

        Config::setApiKey('test-api-key');
        Config::setBaseUrl('https://canvas.test.com/api/v1');
        Config::setAccountId(1);

        Conference::setApiClient($this->mockHttpClient);
    }

    public function testFetchByCourse(): void
    {
        $courseId = 123;
        $responseData = [
            'conferences' => [
                [
                    'id' => 1,
                    'title' => 'Test Conference',
                    'conference_type' => 'BigBlueButton',
                    'description' => 'Test description',
                    'duration' => 60,
                    'status' => 'active',
                    'recordings' => []
                ],
                [
                    'id' => 2,
                    'title' => 'Another Conference',
                    'conference_type' => 'Zoom',
                    'description' => 'Another description',
                    'duration' => 90,
                    'status' => 'concluded',
                    'recordings' => [
                        [
                            'id' => 101,
                            'title' => 'Recording 1',
                            'duration' => 3600,
                            'playback_url' => 'https://example.com/recording1'
                        ]
                    ]
                ]
            ]
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with(
                sprintf('courses/%d/conferences', $courseId),
                ['query' => []]
            )
            ->willReturn($this->mockResponse);

        $conferences = Conference::fetchByCourse($courseId);

        $this->assertCount(2, $conferences);
        $this->assertInstanceOf(Conference::class, $conferences[0]);
        $this->assertEquals(1, $conferences[0]->id);
        $this->assertEquals('Test Conference', $conferences[0]->title);
        $this->assertEquals('BigBlueButton', $conferences[0]->conferenceType);
        $this->assertIsArray($conferences[0]->recordings);
        $this->assertEmpty($conferences[0]->recordings);

        $this->assertInstanceOf(Conference::class, $conferences[1]);
        $this->assertEquals(2, $conferences[1]->id);
        $this->assertCount(1, $conferences[1]->recordings);
        $this->assertInstanceOf(ConferenceRecording::class, $conferences[1]->recordings[0]);
    }

    public function testFetchByGroup(): void
    {
        $groupId = 456;
        $responseData = [
            'conferences' => [
                [
                    'id' => 3,
                    'title' => 'Group Conference',
                    'conference_type' => 'BigBlueButton',
                    'context_type' => 'Group',
                    'context_id' => $groupId
                ]
            ]
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with(
                sprintf('groups/%d/conferences', $groupId),
                ['query' => []]
            )
            ->willReturn($this->mockResponse);

        $conferences = Conference::fetchByGroup($groupId);

        $this->assertCount(1, $conferences);
        $this->assertEquals('Group Conference', $conferences[0]->title);
    }

    public function testFind(): void
    {
        $conferenceId = 789;
        $responseData = [
            'id' => $conferenceId,
            'title' => 'Single Conference',
            'conference_type' => 'Zoom',
            'duration' => 120,
            'settings' => [
                'enable_waiting_room' => true,
                'enable_recording' => false
            ],
            'recordings' => []
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with(sprintf('conferences/%d', $conferenceId))
            ->willReturn($this->mockResponse);

        $conference = Conference::find($conferenceId);

        $this->assertInstanceOf(Conference::class, $conference);
        $this->assertEquals($conferenceId, $conference->id);
        $this->assertEquals('Single Conference', $conference->title);
        $this->assertEquals('Zoom', $conference->conferenceType);
        $this->assertEquals(120, $conference->duration);
        $this->assertIsArray($conference->settings);
        $this->assertTrue($conference->settings['enable_waiting_room']);
    }

    public function testCreateForCourse(): void
    {
        $courseId = 123;
        $createData = [
            'title' => 'New Conference',
            'conference_type' => 'BigBlueButton',
            'description' => 'Test conference creation',
            'duration' => 60,
            'settings' => [
                'enable_recording' => true,
                'mute_on_join' => true
            ]
        ];

        $responseData = array_merge($createData, [
            'id' => 999,
            'status' => 'active',
            'url' => 'https://conference.example.com/999',
            'join_url' => 'https://conference.example.com/join/999'
        ]);

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $dto = new CreateConferenceDTO($createData);

        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with(
                sprintf('courses/%d/conferences', $courseId),
                ['multipart' => $dto->toApiArray()]
            )
            ->willReturn($this->mockResponse);

        $conference = Conference::createForCourse($courseId, $createData);

        $this->assertInstanceOf(Conference::class, $conference);
        $this->assertEquals(999, $conference->id);
        $this->assertEquals('New Conference', $conference->title);
        $this->assertEquals('active', $conference->status);
        $this->assertNotNull($conference->url);
        $this->assertNotNull($conference->joinUrl);
    }

    public function testCreateForGroup(): void
    {
        $groupId = 456;
        $createData = [
            'title' => 'Group Conference',
            'conference_type' => 'Zoom'
        ];

        $responseData = array_merge($createData, [
            'id' => 888,
            'context_type' => 'Group',
            'context_id' => $groupId
        ]);

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $dto = new CreateConferenceDTO($createData);

        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with(
                sprintf('groups/%d/conferences', $groupId),
                ['multipart' => $dto->toApiArray()]
            )
            ->willReturn($this->mockResponse);

        $conference = Conference::createForGroup($groupId, $dto);

        $this->assertInstanceOf(Conference::class, $conference);
        $this->assertEquals(888, $conference->id);
        $this->assertEquals('Group', $conference->contextType);
        $this->assertEquals($groupId, $conference->contextId);
    }

    public function testUpdate(): void
    {
        $conference = new Conference(['id' => 777, 'title' => 'Original Title']);
        
        $updateData = [
            'title' => 'Updated Title',
            'duration' => 90
        ];

        $responseData = array_merge(['id' => 777], $updateData);

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockResponse->method('getStatusCode')
            ->willReturn(200);

        $dto = new UpdateConferenceDTO($updateData);

        $this->mockHttpClient->expects($this->once())
            ->method('put')
            ->with(
                sprintf('conferences/%d', 777),
                ['multipart' => $dto->toApiArray()]
            )
            ->willReturn($this->mockResponse);

        $result = $conference->update($updateData);

        $this->assertInstanceOf(Conference::class, $result);
        $this->assertEquals('Updated Title', $conference->title);
        $this->assertEquals(90, $conference->duration);
    }

    public function testUpdateWithoutIdThrowsException(): void
    {
        $conference = new Conference();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Conference ID is required for updating');

        $conference->update(['title' => 'New Title']);
    }

    public function testDelete(): void
    {
        $conference = new Conference(['id' => 666]);

        $this->mockResponse->method('getStatusCode')
            ->willReturn(204);

        $this->mockHttpClient->expects($this->once())
            ->method('delete')
            ->with(sprintf('conferences/%d', 666))
            ->willReturn($this->mockResponse);

        $result = $conference->delete();

        $this->assertInstanceOf(Conference::class, $result);
    }

    public function testDeleteWithoutIdThrowsException(): void
    {
        $conference = new Conference();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Conference ID is required for deletion');

        $conference->delete();
    }

    public function testJoin(): void
    {
        $conference = new Conference(['id' => 555]);
        
        $joinData = [
            'url' => 'https://conference.example.com/join/555',
            'session_id' => 'abc123',
            'status' => 'joined'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($joinData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with(sprintf('conferences/%d/join', 555))
            ->willReturn($this->mockResponse);

        $result = $conference->join();

        $this->assertIsArray($result);
        $this->assertEquals('https://conference.example.com/join/555', $result['url']);
        $this->assertEquals('abc123', $result['session_id']);
        $this->assertEquals('joined', $result['status']);
    }

    public function testJoinWithoutIdThrowsException(): void
    {
        $conference = new Conference();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Conference ID is required for joining');

        $conference->join();
    }

    public function testGetRecordings(): void
    {
        $conference = new Conference(['id' => 444]);
        
        $recordingsData = [
            [
                'id' => 201,
                'title' => 'Recording 1',
                'duration' => 3600,
                'playback_url' => 'https://example.com/rec1',
                'created_at' => '2024-01-01T10:00:00Z'
            ],
            [
                'id' => 202,
                'title' => 'Recording 2',
                'duration' => 1800,
                'playback_url' => 'https://example.com/rec2',
                'created_at' => '2024-01-02T14:00:00Z'
            ]
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($recordingsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with(sprintf('conferences/%d/recording', 444))
            ->willReturn($this->mockResponse);

        $recordings = $conference->getRecordings();

        $this->assertCount(2, $recordings);
        $this->assertInstanceOf(ConferenceRecording::class, $recordings[0]);
        $this->assertEquals(201, $recordings[0]->id);
        $this->assertEquals('Recording 1', $recordings[0]->title);
        $this->assertEquals(3600, $recordings[0]->duration);
        $this->assertInstanceOf(\DateTime::class, $recordings[0]->createdAt);
    }

    public function testGetRecordingsWithoutIdThrowsException(): void
    {
        $conference = new Conference();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Conference ID is required for fetching recordings');

        $conference->getRecordings();
    }

    public function testProcessDateTimeFields(): void
    {
        $responseData = [
            'id' => 333,
            'title' => 'Conference with Dates',
            'started_at' => '2024-01-15T09:00:00Z',
            'ended_at' => '2024-01-15T10:00:00Z',
            'created_at' => '2024-01-10T08:00:00Z',
            'updated_at' => '2024-01-14T16:00:00Z'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with(sprintf('conferences/%d', 333))
            ->willReturn($this->mockResponse);

        $conference = Conference::find(333);

        $this->assertInstanceOf(\DateTime::class, $conference->startedAt);
        $this->assertInstanceOf(\DateTime::class, $conference->endedAt);
        $this->assertInstanceOf(\DateTime::class, $conference->createdAt);
        $this->assertInstanceOf(\DateTime::class, $conference->updatedAt);
    }
}