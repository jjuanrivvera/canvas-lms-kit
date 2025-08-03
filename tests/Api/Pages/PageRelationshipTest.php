<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Pages;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Pages\Page;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PageRelationshipTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;
    private ResponseInterface $mockResponse;
    private StreamInterface $mockStream;
    private Course $mockCourse;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);
        $this->mockCourse = $this->createMock(Course::class);
        $this->mockCourse->id = 123;

        // Set up the API client
        Page::setApiClient($this->mockHttpClient);
        Page::setCourse($this->mockCourse);
    }

    protected function tearDown(): void
    {
        // Reset course context
        Page::setCourse(new Course([]));
        parent::tearDown();
    }

    public function testCourseReturnsAssociatedCourse(): void
    {
        // Create test page
        $page = new Page([
            'url' => 'test-page',
            'title' => 'Test Page',
            'body' => 'Page content'
        ]);

        // Test the method
        $course = $page->course();

        // Assertions
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(123, $course->id);
        $this->assertSame($this->mockCourse, $course);
    }

    public function testCourseReturnsNullWhenNoCourseSet(): void
    {
        // Skip this test as we can't set course to null
        $this->markTestSkipped('Cannot set course context to null');
    }

    public function testRevisionsReturnsArrayOfRevisions(): void
    {
        // Create test page
        $page = new Page([
            'url' => 'test-page',
            'title' => 'Test Page'
        ]);

        // Mock revisions response
        $revisionsData = [
            [
                'revision_id' => 1,
                'url' => 'test-page',
                'title' => 'Test Page V1',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'edited_by' => ['id' => 100, 'display_name' => 'User 1']
            ],
            [
                'revision_id' => 2,
                'url' => 'test-page',
                'title' => 'Test Page V2',
                'created_at' => '2024-01-02T00:00:00Z',
                'updated_at' => '2024-01-02T00:00:00Z',
                'edited_by' => ['id' => 101, 'display_name' => 'User 2']
            ]
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($revisionsData));
        
        $this->mockStream->method('__toString')
            ->willReturn(json_encode($revisionsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/pages/test-page/revisions')
            ->willReturn($this->mockResponse);

        // Test the method
        $revisions = $page->revisions();

        // Assertions
        $this->assertIsArray($revisions);
        $this->assertCount(2, $revisions);
        $this->assertInstanceOf(\CanvasLMS\Objects\PageRevision::class, $revisions[0]);
        $this->assertInstanceOf(\CanvasLMS\Objects\PageRevision::class, $revisions[1]);
    }

    public function testRevisionsThrowsExceptionWhenUrlMissing(): void
    {
        // Create page without URL
        $page = new Page(['title' => 'Test Page']);

        // Test revisions
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Page URL is required');
        $page->revisions();
    }

    public function testRevisionsHandlesApiError(): void
    {
        // Create test page
        $page = new Page(['url' => 'test-page']);

        // Set up mock to throw exception
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new CanvasApiException('API error'));

        // Test the method - the exception is thrown directly from API client
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('API error');
        $page->revisions();
    }

    public function testLastEditorReturnsUserObject(): void
    {
        // Create test page with last_edited_by data
        $page = new Page([
            'url' => 'test-page',
            'title' => 'Test Page',
            'last_edited_by' => [
                'id' => 456,
                'display_name' => 'Test User'
            ]
        ]);

        // Mock user response
        $userData = [
            'id' => 456,
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($userData));
        
        $this->mockStream->method('__toString')
            ->willReturn(json_encode($userData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/users/456')
            ->willReturn($this->mockResponse);

        // Test the method
        $editor = $page->lastEditor();

        // Assertions
        $this->assertInstanceOf(User::class, $editor);
        $this->assertEquals(456, $editor->id);
        $this->assertEquals('Test User', $editor->name);
    }

    public function testLastEditorReturnsNullWhenNoLastEditedBy(): void
    {
        // Create page without last_edited_by
        $page = new Page([
            'url' => 'test-page',
            'title' => 'Test Page'
        ]);

        // Test the method
        $editor = $page->lastEditor();

        // Assertions
        $this->assertNull($editor);
    }

    public function testLastEditorReturnsNullWhenLastEditedByHasNoId(): void
    {
        // Create page with last_edited_by but no id
        $page = new Page([
            'url' => 'test-page',
            'title' => 'Test Page',
            'last_edited_by' => [
                'display_name' => 'Test User'
            ]
        ]);

        // Test the method
        $editor = $page->lastEditor();

        // Assertions
        $this->assertNull($editor);
    }

    public function testLastEditorThrowsExceptionOnApiError(): void
    {
        // Create test page
        $page = new Page([
            'url' => 'test-page',
            'last_edited_by' => ['id' => 456]
        ]);

        // Set up mock to throw exception
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new CanvasApiException('User not found'));

        // Test the method
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Could not load user who last edited page: User not found');
        $page->lastEditor();
    }
}