<?php

declare(strict_types=1);

namespace Tests\Api\Announcements;

use CanvasLMS\Api\Announcements\Announcement;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Announcements\CreateAnnouncementDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \CanvasLMS\Api\Announcements\Announcement
 */
class AnnouncementTest extends TestCase
{
    private HttpClientInterface $httpClientMock;

    private Course $course;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->course = new Course(['id' => 123]);

        Announcement::setApiClient($this->httpClientMock);
        Announcement::setCourse($this->course);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Announcement::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 456]);
        Announcement::setCourse($course);

        $this->assertTrue(Announcement::checkCourse());
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course is required');

        $reflection = new \ReflectionClass(Announcement::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, null);

        Announcement::checkCourse();
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Important Announcement',
            'message' => 'This is an important announcement',
            'html_url' => 'https://canvas.example.com/announcements/1',
            'posted_at' => '2024-01-01T10:00:00Z',
            'delayed_post_at' => '2024-01-02T10:00:00Z',
            'discussion_type' => 'side_comment',
            'require_initial_post' => false,
            'locked' => false,
            'published' => true,
            'is_announcement' => true,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $announcement = new Announcement($data);

        $this->assertEquals(1, $announcement->getId());
        $this->assertEquals('Important Announcement', $announcement->getTitle());
        $this->assertEquals('This is an important announcement', $announcement->getMessage());
        $this->assertEquals('https://canvas.example.com/announcements/1', $announcement->getHtmlUrl());
        $this->assertEquals('2024-01-01T10:00:00Z', $announcement->getPostedAt());
        $this->assertEquals('2024-01-02T10:00:00Z', $announcement->getDelayedPostAt());
        $this->assertEquals('side_comment', $announcement->getDiscussionType());
        $this->assertFalse($announcement->getRequireInitialPost());
        $this->assertFalse($announcement->getLocked());
        $this->assertTrue($announcement->getPublished());
        $this->assertTrue($announcement->getIsAnnouncement());
    }

    public function testGetAddsAnnouncementFilter(): void
    {
        $responseData = [
            [
                'id' => 1,
                'title' => 'Announcement 1',
                'is_announcement' => true,
            ],
            [
                'id' => 2,
                'title' => 'Announcement 2',
                'is_announcement' => true,
            ],
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with(
                'courses/123/discussion_topics',
                ['query' => ['only_announcements' => true]]
            )
            ->willReturn($responseMock);

        $announcements = Announcement::get();

        $this->assertCount(2, $announcements);
        $this->assertInstanceOf(Announcement::class, $announcements[0]);
        $this->assertEquals(1, $announcements[0]->getId());
        $this->assertEquals('Announcement 1', $announcements[0]->getTitle());
        $this->assertTrue($announcements[0]->getIsAnnouncement());
    }

    public function testGetWithCustomParams(): void
    {
        $responseData = [
            [
                'id' => 1,
                'title' => 'Active Announcement',
                'is_announcement' => true,
                'published' => true,
            ],
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with(
                'courses/123/discussion_topics',
                ['query' => ['only_announcements' => true, 'active_only' => true]]
            )
            ->willReturn($responseMock);

        $announcements = Announcement::get(['active_only' => true]);

        $this->assertCount(1, $announcements);
        $this->assertEquals('Active Announcement', $announcements[0]->getTitle());
    }

    public function testFetchGlobalAnnouncements(): void
    {
        $responseData = [
            [
                'id' => 1,
                'title' => 'Course 123 Announcement',
                'context_code' => 'course_123',
                'is_announcement' => true,
            ],
            [
                'id' => 2,
                'title' => 'Course 456 Announcement',
                'context_code' => 'course_456',
                'is_announcement' => true,
            ],
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with(
                'announcements',
                ['query' => [
                    'context_codes[0]' => 'course_123',
                    'context_codes[1]' => 'course_456',
                ]]
            )
            ->willReturn($responseMock);

        $announcements = Announcement::fetchGlobalAnnouncements(['course_123', 'course_456']);

        $this->assertCount(2, $announcements);
        $this->assertEquals('course_123', $announcements[0]->getContextCode());
        $this->assertEquals('course_456', $announcements[1]->getContextCode());
    }

    public function testFetchGlobalAnnouncementsThrowsExceptionForEmptyContextCodes(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('At least one context code is required');

        Announcement::fetchGlobalAnnouncements([]);
    }

    public function testCreate(): void
    {
        $createData = [
            'title' => 'New Announcement',
            'message' => 'This is a new announcement',
            'published' => true,
        ];

        $responseData = array_merge($createData, [
            'id' => 1,
            'is_announcement' => true,
            'discussion_type' => 'side_comment',
            'require_initial_post' => false,
        ]);

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                'courses/123/discussion_topics',
                $this->callback(function ($options) {
                    return isset($options['multipart']) && is_array($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $announcement = Announcement::create($createData);

        $this->assertInstanceOf(Announcement::class, $announcement);
        $this->assertEquals(1, $announcement->getId());
        $this->assertEquals('New Announcement', $announcement->getTitle());
        $this->assertTrue($announcement->getIsAnnouncement());
    }

    public function testCreateWithDTO(): void
    {
        $createDTO = new CreateAnnouncementDTO([
            'title' => 'DTO Announcement',
            'message' => 'Created with DTO',
            'delayed_post_at' => '2024-03-01T10:00:00Z',
        ]);

        $responseData = [
            'id' => 1,
            'title' => 'DTO Announcement',
            'message' => 'Created with DTO',
            'is_announcement' => true,
            'delayed_post_at' => '2024-03-01T10:00:00Z',
            'published' => false,
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->willReturn($responseMock);

        $announcement = Announcement::create($createDTO);

        $this->assertEquals('DTO Announcement', $announcement->getTitle());
        $this->assertEquals('2024-03-01T10:00:00Z', $announcement->getDelayedPostAt());
    }

    public function testUpdate(): void
    {
        $updateData = ['title' => 'Updated Announcement'];

        $responseData = [
            'id' => 1,
            'title' => 'Updated Announcement',
            'is_announcement' => true,
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'courses/123/discussion_topics/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']) && is_array($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $announcement = Announcement::update(1, $updateData);

        $this->assertEquals('Updated Announcement', $announcement->getTitle());
    }

    public function testScheduleFor(): void
    {
        $announcement = new Announcement(['id' => 1]);

        $responseData = [
            'id' => 1,
            'title' => 'Scheduled Announcement',
            'delayed_post_at' => '2024-06-01T10:00:00Z',
            'is_announcement' => true,
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'courses/123/discussion_topics/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']) && is_array($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $result = $announcement->scheduleFor('2024-06-01T10:00:00Z');

        $this->assertSame($announcement, $result);
        $this->assertEquals('2024-06-01T10:00:00Z', $announcement->getDelayedPostAt());
    }

    public function testScheduleForThrowsExceptionWhenIdNotSet(): void
    {
        $announcement = new Announcement();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Announcement must be saved before scheduling');

        $announcement->scheduleFor('2024-06-01T10:00:00Z');
    }

    public function testPostImmediately(): void
    {
        $announcement = new Announcement(['id' => 1]);

        $responseData = [
            'id' => 1,
            'title' => 'Immediate Announcement',
            'delayed_post_at' => null,
            'published' => true,
            'is_announcement' => true,
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->willReturn($responseMock);

        $result = $announcement->postImmediately();

        $this->assertSame($announcement, $result);
        $this->assertNull($announcement->getDelayedPostAt());
        $this->assertTrue($announcement->getPublished());
    }

    public function testSaveEnsuresAnnouncementDefaults(): void
    {
        $announcement = new Announcement([
            'title' => 'New Announcement to Save',
            'message' => 'Test message',
        ]);

        $responseData = [
            'id' => 1,
            'title' => 'New Announcement to Save',
            'message' => 'Test message',
            'is_announcement' => true,
            'require_initial_post' => false,
            'published' => true,
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->willReturn($responseMock);

        $result = $announcement->save();

        $this->assertSame($announcement, $result);
        $this->assertTrue($announcement->getIsAnnouncement());
        $this->assertFalse($announcement->getRequireInitialPost());
    }

    public function testContextCodeGetterAndSetter(): void
    {
        $announcement = new Announcement();

        $this->assertNull($announcement->getContextCode());

        $announcement->setContextCode('course_789');
        $this->assertEquals('course_789', $announcement->getContextCode());
    }
}
