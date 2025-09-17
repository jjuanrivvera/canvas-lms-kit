<?php

declare(strict_types=1);

namespace Tests\Api\MediaObjects;

use CanvasLMS\Api\MediaObjects\MediaObject;
use CanvasLMS\Config;
use CanvasLMS\Dto\MediaObjects\UpdateMediaObjectDTO;
use CanvasLMS\Dto\MediaObjects\UpdateMediaTracksDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Objects\MediaSource;
use CanvasLMS\Objects\MediaTrack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Api\MediaObjects\MediaObject
 */
class MediaObjectTest extends TestCase
{
    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up Config
        Config::setBaseUrl('https://canvas.example.com/');
        Config::setApiKey('test-api-key');

        // Reset HTTP client for each test
        MediaObject::setApiClient($this->createMock(HttpClientInterface::class));
    }

    /**
     * Tear down test environment
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // Reset the HTTP client
        MediaObject::setApiClient($this->createMock(HttpClientInterface::class));
    }

    /**
     * Set HTTP client mock
     */
    private function setHttpClient(HttpClientInterface $client): void
    {
        MediaObject::setApiClient($client);
    }

    /**
     * Test fetching all media objects (global context)
     */
    public function testGet(): void
    {
        $mockData = [
            'media_objects' => [
                [
                    'can_add_captions' => true,
                    'user_entered_title' => 'Test Video',
                    'title' => 'Test Video',
                    'media_id' => 'm-test123',
                    'media_type' => 'video',
                    'media_tracks' => [],
                    'media_sources' => [],
                ],
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with('/media_objects', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($mockData)));

        $this->setHttpClient($mockClient);

        $mediaObjects = MediaObject::get();

        $this->assertIsArray($mediaObjects);
        $this->assertCount(1, $mediaObjects);
        $this->assertInstanceOf(MediaObject::class, $mediaObjects[0]);
        $this->assertEquals('Test Video', $mediaObjects[0]->title);
        $this->assertEquals('m-test123', $mediaObjects[0]->mediaId);
    }

    /**
     * Test fetching media objects with parameters
     */
    public function testGetWithParams(): void
    {
        $params = [
            'sort' => 'title',
            'order' => 'asc',
            'exclude[]' => ['sources', 'tracks'],
        ];

        $mockData = ['media_objects' => []];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with('/media_objects', ['query' => $params])
            ->willReturn(new Response(200, [], json_encode($mockData)));

        $this->setHttpClient($mockClient);

        $mediaObjects = MediaObject::get($params);

        $this->assertIsArray($mediaObjects);
        $this->assertEmpty($mediaObjects);
    }

    /**
     * Test fetching media attachments
     */
    public function testFetchAttachments(): void
    {
        $mockData = [
            'media_objects' => [
                [
                    'can_add_captions' => true,
                    'title' => 'Attachment Video',
                    'media_id' => 'm-attach123',
                    'media_type' => 'video',
                ],
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with('/media_attachments', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($mockData)));

        $this->setHttpClient($mockClient);

        $attachments = MediaObject::fetchAttachments();

        $this->assertIsArray($attachments);
        $this->assertCount(1, $attachments);
        $this->assertEquals('Attachment Video', $attachments[0]->title);
    }

    /**
     * Test fetching media objects by course
     */
    public function testFetchByCourse(): void
    {
        $courseId = 123;
        $mockData = [
            'media_objects' => [
                [
                    'title' => 'Course Video',
                    'media_id' => 'm-course123',
                    'media_type' => 'video',
                ],
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with("/courses/{$courseId}/media_objects", ['query' => []])
            ->willReturn(new Response(200, [], json_encode($mockData)));

        $this->setHttpClient($mockClient);

        $mediaObjects = MediaObject::fetchByCourse($courseId);

        $this->assertIsArray($mediaObjects);
        $this->assertCount(1, $mediaObjects);
        $this->assertEquals('Course Video', $mediaObjects[0]->title);
    }

    /**
     * Test fetching media attachments by course
     */
    public function testFetchAttachmentsByCourse(): void
    {
        $courseId = 123;
        $mockData = [
            'media_objects' => [
                [
                    'title' => 'Course Attachment',
                    'media_id' => 'm-courseattach123',
                ],
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with("/courses/{$courseId}/media_attachments", ['query' => []])
            ->willReturn(new Response(200, [], json_encode($mockData)));

        $this->setHttpClient($mockClient);

        $attachments = MediaObject::fetchAttachmentsByCourse($courseId);

        $this->assertIsArray($attachments);
        $this->assertCount(1, $attachments);
        $this->assertEquals('Course Attachment', $attachments[0]->title);
    }

    /**
     * Test fetching media objects by group
     */
    public function testFetchByGroup(): void
    {
        $groupId = 456;
        $mockData = [
            'media_objects' => [
                [
                    'title' => 'Group Video',
                    'media_id' => 'm-group456',
                ],
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with("/groups/{$groupId}/media_objects", ['query' => []])
            ->willReturn(new Response(200, [], json_encode($mockData)));

        $this->setHttpClient($mockClient);

        $mediaObjects = MediaObject::fetchByGroup($groupId);

        $this->assertIsArray($mediaObjects);
        $this->assertCount(1, $mediaObjects);
        $this->assertEquals('Group Video', $mediaObjects[0]->title);
    }

    /**
     * Test fetching media attachments by group
     */
    public function testFetchAttachmentsByGroup(): void
    {
        $groupId = 456;
        $mockData = [
            'media_objects' => [
                [
                    'title' => 'Group Attachment',
                    'media_id' => 'm-groupattach456',
                ],
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with("/groups/{$groupId}/media_attachments", ['query' => []])
            ->willReturn(new Response(200, [], json_encode($mockData)));

        $this->setHttpClient($mockClient);

        $attachments = MediaObject::fetchAttachmentsByGroup($groupId);

        $this->assertIsArray($attachments);
        $this->assertCount(1, $attachments);
        $this->assertEquals('Group Attachment', $attachments[0]->title);
    }

    /**
     * Test that find method throws exception
     */
    public function testFindThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Direct media object retrieval is not supported by Canvas API');

        MediaObject::find(123);
    }

    /**
     * Test updating a media object
     */
    public function testUpdate(): void
    {
        $mediaObject = new MediaObject([
            'media_id' => 'm-test123',
            'title' => 'Old Title',
        ]);

        $updateData = ['userEnteredTitle' => 'New Title'];
        $responseData = [
            'user_entered_title' => 'New Title',
            'title' => 'New Title',
            'media_id' => 'm-test123',
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('put')
            ->with(
                '/media_objects/m-test123',
                ['json' => ['user_entered_title' => 'New Title']]
            )
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $this->setHttpClient($mockClient);

        $result = $mediaObject->update($updateData);

        $this->assertInstanceOf(MediaObject::class, $result);
        $this->assertEquals('New Title', $result->userEnteredTitle);
        $this->assertEquals('New Title', $result->title);
    }

    /**
     * Test updating a media object with DTO
     */
    public function testUpdateWithDTO(): void
    {
        $mediaObject = new MediaObject([
            'media_id' => 'm-test123',
        ]);

        $dto = new UpdateMediaObjectDTO('New Title via DTO');
        $responseData = [
            'user_entered_title' => 'New Title via DTO',
            'title' => 'New Title via DTO',
            'media_id' => 'm-test123',
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('put')
            ->with(
                '/media_objects/m-test123',
                ['json' => ['user_entered_title' => 'New Title via DTO']]
            )
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $this->setHttpClient($mockClient);

        $result = $mediaObject->update($dto);

        $this->assertEquals('New Title via DTO', $result->userEnteredTitle);
    }

    /**
     * Test updating without media ID throws exception
     */
    public function testUpdateWithoutMediaIdThrowsException(): void
    {
        $mediaObject = new MediaObject();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Media object ID is required for update');

        $mediaObject->update(['userEnteredTitle' => 'New Title']);
    }

    /**
     * Test updating by attachment ID
     */
    public function testUpdateByAttachment(): void
    {
        $mediaObject = new MediaObject();
        $attachmentId = 789;

        $updateData = ['userEnteredTitle' => 'Updated via Attachment'];
        $responseData = [
            'user_entered_title' => 'Updated via Attachment',
            'title' => 'Updated via Attachment',
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('put')
            ->with(
                "/media_attachments/{$attachmentId}",
                ['json' => ['user_entered_title' => 'Updated via Attachment']]
            )
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $this->setHttpClient($mockClient);

        $result = $mediaObject->updateByAttachment($attachmentId, $updateData);

        $this->assertEquals('Updated via Attachment', $result->userEnteredTitle);
    }

    /**
     * Test getting media tracks
     */
    public function testGetTracks(): void
    {
        $mediaObject = new MediaObject(['media_id' => 'm-test123']);

        $tracksData = [
            [
                'id' => 1,
                'user_id' => 100,
                'media_object_id' => 'm-test123',
                'kind' => 'subtitles',
                'locale' => 'en',
                'content' => 'Test content',
            ],
            [
                'id' => 2,
                'kind' => 'captions',
                'locale' => 'es',
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with('/media_objects/m-test123/media_tracks', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($tracksData)));

        $this->setHttpClient($mockClient);

        $tracks = $mediaObject->getTracks();

        $this->assertIsArray($tracks);
        $this->assertCount(2, $tracks);
        $this->assertInstanceOf(MediaTrack::class, $tracks[0]);
        $this->assertEquals('subtitles', $tracks[0]->kind);
        $this->assertEquals('en', $tracks[0]->locale);
    }

    /**
     * Test getting tracks without media ID throws exception
     */
    public function testGetTracksWithoutMediaIdThrowsException(): void
    {
        $mediaObject = new MediaObject();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Media object ID is required to get tracks');

        $mediaObject->getTracks();
    }

    /**
     * Test getting tracks by attachment
     */
    public function testGetTracksByAttachment(): void
    {
        $mediaObject = new MediaObject();
        $attachmentId = 999;

        $tracksData = [
            [
                'id' => 3,
                'kind' => 'descriptions',
                'locale' => 'fr',
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with("/media_attachments/{$attachmentId}/media_tracks", ['query' => []])
            ->willReturn(new Response(200, [], json_encode($tracksData)));

        $this->setHttpClient($mockClient);

        $tracks = $mediaObject->getTracksByAttachment($attachmentId);

        $this->assertIsArray($tracks);
        $this->assertCount(1, $tracks);
        $this->assertEquals('descriptions', $tracks[0]->kind);
    }

    /**
     * Test updating tracks
     */
    public function testUpdateTracks(): void
    {
        $mediaObject = new MediaObject(['media_id' => 'm-test123']);

        $tracksToUpdate = [
            ['locale' => 'en', 'content' => 'English subtitles', 'kind' => 'subtitles'],
            ['locale' => 'es', 'content' => 'Subtítulos en español', 'kind' => 'subtitles'],
        ];

        $responseData = [
            [
                'id' => 10,
                'locale' => 'en',
                'kind' => 'subtitles',
                'content' => 'English subtitles',
            ],
            [
                'id' => 11,
                'locale' => 'es',
                'kind' => 'subtitles',
                'content' => 'Subtítulos en español',
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('put')
            ->with(
                '/media_objects/m-test123/media_tracks',
                [
                    'json' => $tracksToUpdate,
                    'query' => [],
                ]
            )
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $this->setHttpClient($mockClient);

        $result = $mediaObject->updateTracks($tracksToUpdate);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(MediaTrack::class, $result[0]);
        $this->assertEquals('en', $result[0]->locale);
        $this->assertIsArray($mediaObject->mediaTracks);
        $this->assertCount(2, $mediaObject->mediaTracks);
    }

    /**
     * Test updating tracks with DTO
     */
    public function testUpdateTracksWithDTO(): void
    {
        $mediaObject = new MediaObject(['media_id' => 'm-test123']);

        $dto = new UpdateMediaTracksDTO([
            ['locale' => 'de', 'content' => 'Deutsche Untertitel', 'kind' => 'subtitles'],
        ]);

        $responseData = [
            [
                'id' => 12,
                'locale' => 'de',
                'kind' => 'subtitles',
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('put')
            ->with(
                '/media_objects/m-test123/media_tracks',
                [
                    'json' => $dto->toArray(),
                    'query' => [],
                ]
            )
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $this->setHttpClient($mockClient);

        $result = $mediaObject->updateTracks($dto);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('de', $result[0]->locale);
    }

    /**
     * Test updating tracks without media ID throws exception
     */
    public function testUpdateTracksWithoutMediaIdThrowsException(): void
    {
        $mediaObject = new MediaObject();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Media object ID is required to update tracks');

        $mediaObject->updateTracks([]);
    }

    /**
     * Test updating tracks by attachment
     */
    public function testUpdateTracksByAttachment(): void
    {
        $mediaObject = new MediaObject();
        $attachmentId = 555;

        $tracksToUpdate = [
            ['locale' => 'ja', 'kind' => 'captions'],
        ];

        $responseData = [
            [
                'id' => 13,
                'locale' => 'ja',
                'kind' => 'captions',
            ],
        ];

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->expects($this->once())
            ->method('put')
            ->with(
                "/media_attachments/{$attachmentId}/media_tracks",
                [
                    'json' => $tracksToUpdate,
                    'query' => [],
                ]
            )
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $this->setHttpClient($mockClient);

        $result = $mediaObject->updateTracksByAttachment($attachmentId, $tracksToUpdate);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('ja', $result[0]->locale);
    }

    /**
     * Test media tracks parsing in constructor
     */
    public function testMediaTracksParsingInConstructor(): void
    {
        $data = [
            'media_id' => 'm-test123',
            'media_tracks' => [
                ['id' => 1, 'locale' => 'en', 'kind' => 'subtitles'],
                ['id' => 2, 'locale' => 'es', 'kind' => 'captions'],
            ],
        ];

        $mediaObject = new MediaObject($data);

        $this->assertIsArray($mediaObject->mediaTracks);
        $this->assertCount(2, $mediaObject->mediaTracks);
        $this->assertInstanceOf(MediaTrack::class, $mediaObject->mediaTracks[0]);
        $this->assertEquals('en', $mediaObject->mediaTracks[0]->locale);
    }

    /**
     * Test media sources parsing in constructor
     */
    public function testMediaSourcesParsingInConstructor(): void
    {
        $data = [
            'media_id' => 'm-test123',
            'media_sources' => [
                [
                    'height' => '720',
                    'width' => '1280',
                    'content_type' => 'video/mp4',
                    'url' => 'https://example.com/video.mp4',
                ],
                [
                    'height' => '480',
                    'width' => '854',
                    'content_type' => 'video/webm',
                    'url' => 'https://example.com/video.webm',
                ],
            ],
        ];

        $mediaObject = new MediaObject($data);

        $this->assertIsArray($mediaObject->mediaSources);
        $this->assertCount(2, $mediaObject->mediaSources);
        $this->assertInstanceOf(MediaSource::class, $mediaObject->mediaSources[0]);
        $this->assertEquals('720', $mediaObject->mediaSources[0]->height);
        $this->assertEquals('video/mp4', $mediaObject->mediaSources[0]->contentType);
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $mediaObject = new MediaObject([
            'can_add_captions' => true,
            'user_entered_title' => 'My Video',
            'title' => 'My Video',
            'media_id' => 'm-test123',
            'media_type' => 'video',
            'media_tracks' => [
                ['id' => 1, 'locale' => 'en'],
            ],
            'media_sources' => [
                ['height' => '720', 'width' => '1280'],
            ],
        ]);

        $array = $mediaObject->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(true, $array['can_add_captions']);
        $this->assertEquals('My Video', $array['user_entered_title']);
        $this->assertEquals('My Video', $array['title']);
        $this->assertEquals('m-test123', $array['media_id']);
        $this->assertEquals('video', $array['media_type']);
        $this->assertIsArray($array['media_tracks']);
        $this->assertIsArray($array['media_sources']);
    }
}
