<?php

namespace Tests\Api\Pages;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Pages\Page;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Pages\CreatePageDTO;
use CanvasLMS\Dto\Pages\UpdatePageDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Objects\PageRevision;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \CanvasLMS\Api\Pages\Page
 */
class PageTest extends TestCase
{
    private HttpClientInterface $httpClientMock;
    private Course $course;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->course = new Course(['id' => 123]);

        Page::setApiClient($this->httpClientMock);
        Page::setCourse($this->course);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Page::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, new Course(['id' => 0]));
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 456]);
        Page::setCourse($course);

        $this->assertTrue(Page::checkCourse());
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course is required');

        $reflection = new \ReflectionClass(Page::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, new Course([]));

        Page::checkCourse();
    }

    public function testConstructor(): void
    {
        $data = [
            'page_id' => 789,
            'url' => 'course-syllabus',
            'title' => 'Course Syllabus',
            'body' => '<h1>Welcome to the course</h1>',
            'workflow_state' => 'active',
            'editing_roles' => 'teachers',
            'published' => true,
            'front_page' => false,
            'publish_at' => '2024-02-01T00:00:00Z',
            'editor' => 'rce',
            'block_editor_attributes' => ['id' => 123, 'version' => '0.2'],
            'html_url' => 'https://canvas.example.com/courses/123/pages/course-syllabus',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
            'revision_id' => 1,
            'page_views_count' => 42
        ];

        $page = new Page($data);

        $this->assertEquals(789, $page->getPageId());
        $this->assertEquals('course-syllabus', $page->getUrl());
        $this->assertEquals('Course Syllabus', $page->getTitle());
        $this->assertEquals('<h1>Welcome to the course</h1>', $page->getBody());
        $this->assertEquals('active', $page->getWorkflowState());
        $this->assertEquals('teachers', $page->getEditingRoles());
        $this->assertTrue($page->getPublished());
        $this->assertFalse($page->getFrontPage());
        $this->assertEquals('2024-02-01T00:00:00Z', $page->getPublishAt());
        $this->assertEquals('rce', $page->getEditor());
        $this->assertEquals(['id' => 123, 'version' => '0.2'], $page->getBlockEditorAttributes());
        $this->assertEquals('https://canvas.example.com/courses/123/pages/course-syllabus', $page->getHtmlUrl());
        $this->assertEquals('2024-01-01T00:00:00Z', $page->getCreatedAt());
        $this->assertEquals('2024-01-01T00:00:00Z', $page->getUpdatedAt());
        $this->assertEquals(1, $page->getRevisionId());
        $this->assertEquals(42, $page->getPageViewsCount());
    }

    public function testGettersAndSetters(): void
    {
        $page = new Page();

        $page->setPageId(456);
        $this->assertEquals(456, $page->getPageId());

        $page->setUrl('test-page');
        $this->assertEquals('test-page', $page->getUrl());

        $page->setTitle('Test Page');
        $this->assertEquals('Test Page', $page->getTitle());

        $page->setBody('<p>Test content</p>');
        $this->assertEquals('<p>Test content</p>', $page->getBody());

        $page->setWorkflowState('active');
        $this->assertEquals('active', $page->getWorkflowState());

        $page->setEditingRoles('students');
        $this->assertEquals('students', $page->getEditingRoles());

        $page->setPublished(true);
        $this->assertTrue($page->getPublished());

        $page->setFrontPage(true);
        $this->assertTrue($page->getFrontPage());

        $page->setPublishAt('2024-02-01T00:00:00Z');
        $this->assertEquals('2024-02-01T00:00:00Z', $page->getPublishAt());

        $page->setEditor('block_editor');
        $this->assertEquals('block_editor', $page->getEditor());

        $blockEditorAttrs = ['id' => 456, 'version' => '0.3', 'blocks' => '{}'];
        $page->setBlockEditorAttributes($blockEditorAttrs);
        $this->assertEquals($blockEditorAttrs, $page->getBlockEditorAttributes());

        $page->setLockInfo('locked');
        $this->assertEquals('locked', $page->getLockInfo());

        $page->setHtmlUrl('https://example.com/page');
        $this->assertEquals('https://example.com/page', $page->getHtmlUrl());

        $page->setCreatedAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $page->getCreatedAt());

        $page->setUpdatedAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $page->getUpdatedAt());

        $lastEditedBy = ['id' => 1, 'name' => 'John Doe'];
        $page->setLastEditedBy($lastEditedBy);
        $this->assertEquals($lastEditedBy, $page->getLastEditedBy());

        $page->setLockedForUser(false);
        $this->assertFalse($page->getLockedForUser());

        $page->setLockExplanation('Page is locked');
        $this->assertEquals('Page is locked', $page->getLockExplanation());

        $page->setRevisionId(5);
        $this->assertEquals(5, $page->getRevisionId());

        $page->setPageViewsCount(100);
        $this->assertEquals(100, $page->getPageViewsCount());
    }

    public function testFindSuccess(): void
    {
        $pagesListResponse = [
            ['page_id' => 100, 'url' => 'other-page', 'title' => 'Other Page'],
            ['page_id' => 123, 'url' => 'test-page', 'title' => 'Test Page'],
            ['page_id' => 200, 'url' => 'another-page', 'title' => 'Another Page']
        ];

        $pageDetailResponse = [
            'page_id' => 123,
            'url' => 'test-page',
            'title' => 'Test Page',
            'body' => '<p>Test content</p>',
            'published' => true
        ];

        // Mock for list request - simplified for initial fetch
        $listResponseMock = $this->createMock(ResponseInterface::class);
        $listStreamMock = $this->createMock(StreamInterface::class);

        $listStreamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($pagesListResponse));

        $listResponseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($listStreamMock);

        // Mock for detail request
        $detailResponseMock = $this->createMock(ResponseInterface::class);
        $detailStreamMock = $this->createMock(StreamInterface::class);

        $detailStreamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($pageDetailResponse));

        $detailResponseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($detailStreamMock);

        $this->httpClientMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($endpoint, $options = []) use ($listResponseMock, $detailResponseMock) {
                if (str_contains($endpoint, 'pages/test-page')) {
                    return $detailResponseMock;
                }
                return $listResponseMock;
            });

        Page::setApiClient($this->httpClientMock);
        Page::setCourse($this->course);

        $page = Page::find(123);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals(123, $page->getPageId());
        $this->assertEquals('test-page', $page->getUrl());
        $this->assertEquals('Test Page', $page->getTitle());
    }

    public function testFindNotFound(): void
    {
        $pagesListResponse = [
            ['page_id' => 100, 'url' => 'other-page', 'title' => 'Other Page'],
            ['page_id' => 200, 'url' => 'another-page', 'title' => 'Another Page']
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($pagesListResponse));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        // No need to check headers in simplified implementation

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/pages')
            ->willReturn($responseMock);

        Page::setApiClient($this->httpClientMock);
        Page::setCourse($this->course);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Page with ID 999 not found in course 123');

        Page::find(999);
    }

    public function testFindByUrl(): void
    {
        $responseData = [
            'url' => 'test-page',
            'title' => 'Test Page',
            'body' => '<p>Test content</p>',
            'published' => true,
            'front_page' => false
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
            ->with('courses/123/pages/test-page')
            ->willReturn($responseMock);

        $page = Page::findByUrl('test-page');

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('test-page', $page->getUrl());
        $this->assertEquals('Test Page', $page->getTitle());
        $this->assertEquals('<p>Test content</p>', $page->getBody());
        $this->assertTrue($page->getPublished());
        $this->assertFalse($page->getFrontPage());
    }

    public function testFindByUrlWithSpecialCharactersInUrl(): void
    {
        $responseData = [
            'url' => 'test-page-with-spaces',
            'title' => 'Test Page With Spaces',
            'body' => '<p>Content</p>',
            'published' => true
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
            ->with('courses/123/pages/test%20page%20with%20spaces')
            ->willReturn($responseMock);

        $page = Page::findByUrl('test page with spaces');

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('test-page-with-spaces', $page->getUrl());
        $this->assertEquals('Test Page With Spaces', $page->getTitle());
    }

    public function testFetchAll(): void
    {
        $responseData = [
            [
                'url' => 'page-1',
                'title' => 'Page 1',
                'published' => true,
                'front_page' => true
            ],
            [
                'url' => 'page-2',
                'title' => 'Page 2',
                'published' => false,
                'front_page' => false
            ]
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
            ->with('courses/123/pages', ['query' => []])
            ->willReturn($responseMock);

        $pages = Page::fetchAll();

        $this->assertCount(2, $pages);
        $this->assertInstanceOf(Page::class, $pages[0]);
        $this->assertInstanceOf(Page::class, $pages[1]);
        $this->assertEquals('page-1', $pages[0]->getUrl());
        $this->assertEquals('page-2', $pages[1]->getUrl());
        $this->assertEquals('Page 1', $pages[0]->getTitle());
        $this->assertEquals('Page 2', $pages[1]->getTitle());
    }

    public function testFetchAllWithParams(): void
    {
        $params = ['published' => true, 'sort' => 'title'];
        $responseData = [];

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
            ->with('courses/123/pages', ['query' => $params])
            ->willReturn($responseMock);

        $pages = Page::fetchAll($params);

        $this->assertCount(0, $pages);
    }

    public function testFetchAllPaginated(): void
    {
        $this->assertTrue(method_exists(Page::class, 'fetchAllPaginated'));
    }

    public function testCreate(): void
    {
        $pageData = [
            'title' => 'New Page',
            'body' => '<p>New content</p>',
            'published' => true,
            'editing_roles' => 'teachers'
        ];

        $responseData = [
            'url' => 'new-page',
            'title' => 'New Page',
            'body' => '<p>New content</p>',
            'published' => true,
            'editing_roles' => 'teachers',
            'front_page' => false
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
            ->with('courses/123/pages', $this->anything())
            ->willReturn($responseMock);

        $page = Page::create($pageData);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('new-page', $page->getUrl());
        $this->assertEquals('New Page', $page->getTitle());
        $this->assertEquals('<p>New content</p>', $page->getBody());
        $this->assertTrue($page->getPublished());
    }

    public function testCreateWithDTO(): void
    {
        $createDto = new CreatePageDTO([
            'title' => 'New Page',
            'body' => '<p>New content</p>',
            'published' => true,
            'editing_roles' => 'teachers'
        ]);

        $responseData = [
            'url' => 'new-page',
            'title' => 'New Page',
            'body' => '<p>New content</p>',
            'published' => true,
            'editing_roles' => 'teachers'
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
            ->with('courses/123/pages', ['multipart' => $createDto->toApiArray()])
            ->willReturn($responseMock);

        $page = Page::create($createDto);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('new-page', $page->getUrl());
        $this->assertEquals('New Page', $page->getTitle());
    }

    public function testUpdate(): void
    {
        $pageUrl = 'test-page';
        $updateData = [
            'title' => 'Updated Page',
            'body' => '<p>Updated content</p>'
        ];

        $responseData = [
            'url' => 'test-page',
            'title' => 'Updated Page',
            'body' => '<p>Updated content</p>',
            'published' => true
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
            ->with('courses/123/pages/test-page', $this->anything())
            ->willReturn($responseMock);

        $page = Page::update($pageUrl, $updateData);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('test-page', $page->getUrl());
        $this->assertEquals('Updated Page', $page->getTitle());
        $this->assertEquals('<p>Updated content</p>', $page->getBody());
    }

    public function testUpdateWithDTO(): void
    {
        $updateDto = new UpdatePageDTO([
            'title' => 'Updated Page',
            'body' => '<p>Updated content</p>'
        ]);

        $responseData = [
            'url' => 'test-page',
            'title' => 'Updated Page',
            'body' => '<p>Updated content</p>',
            'published' => true
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
            ->with('courses/123/pages/test-page', ['multipart' => $updateDto->toApiArray()])
            ->willReturn($responseMock);

        $page = Page::update('test-page', $updateDto);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('Updated Page', $page->getTitle());
    }

    public function testSaveCreate(): void
    {
        $page = new Page();
        $page->setTitle('New Page');
        $page->setBody('<p>New content</p>');

        $responseData = [
            'url' => 'new-page',
            'title' => 'New Page',
            'body' => '<p>New content</p>',
            'published' => false
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

        $result = $page->save();

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals('new-page', $page->getUrl());
    }

    public function testSaveUpdate(): void
    {
        $page = new Page(['url' => 'existing-page']);
        $page->setTitle('Updated Title');

        $responseData = [
            'url' => 'existing-page',
            'title' => 'Updated Title',
            'body' => '<p>Content</p>',
            'published' => true
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

        $result = $page->save();

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals('Updated Title', $page->getTitle());
    }

    public function testSaveThrowsExceptionForMissingTitle(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Page title is required');

        $page = new Page();
        $page->save();
    }

    public function testSaveThrowsExceptionForInvalidEditingRoles(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid editing role. Must be one of: teachers, students, members, public');

        $page = new Page();
        $page->setTitle('Test Page');
        $page->setEditingRoles('invalid_role');
        $page->save();
    }

    public function testDelete(): void
    {
        $page = new Page(['url' => 'test-page']);

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('courses/123/pages/test-page')
            ->willReturn($responseMock);

        $result = $page->delete();

        $this->assertInstanceOf(Page::class, $result);
    }

    public function testDeleteThrowsExceptionForMissingUrl(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Page URL is required for deletion');

        $page = new Page();
        $page->delete();
    }

    public function testFetchFrontPage(): void
    {
        $responseData = [
            'url' => 'home',
            'title' => 'Course Home',
            'body' => '<h1>Welcome</h1>',
            'published' => true,
            'front_page' => true
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
            ->with('courses/123/front_page')
            ->willReturn($responseMock);

        $frontPage = Page::fetchFrontPage();

        $this->assertInstanceOf(Page::class, $frontPage);
        $this->assertEquals('home', $frontPage->getUrl());
        $this->assertEquals('Course Home', $frontPage->getTitle());
        $this->assertTrue($frontPage->getFrontPage());
    }

    public function testFetchFrontPageReturnsNullWhenNotSet(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/front_page')
            ->willThrowException(new CanvasApiException('404 Not Found'));

        $frontPage = Page::fetchFrontPage();

        $this->assertNull($frontPage);
    }

    public function testSetAsFrontPage(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('courses/123/pages/test-page', ['multipart' => ['wiki_page' => ['front_page' => true]]])
            ->willReturn($responseMock);

        $result = Page::setAsFrontPage('test-page');

        $this->assertInstanceOf(Page::class, $result);
    }

    public function testMakeFrontPage(): void
    {
        $page = new Page(['url' => 'test-page']);

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('courses/123/pages/test-page', ['multipart' => ['wiki_page' => ['front_page' => true]]])
            ->willReturn($responseMock);

        $result = $page->makeFrontPage();

        $this->assertInstanceOf(Page::class, $result);
        $this->assertTrue($page->getFrontPage());
    }

    public function testMakeFrontPageThrowsExceptionForMissingUrl(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Page URL is required');

        $page = new Page();
        $page->makeFrontPage();
    }

    public function testPublish(): void
    {
        $page = new Page(['url' => 'test-page']);

        $responseData = [
            'url' => 'test-page',
            'title' => 'Test Page',
            'published' => true,
            'workflow_state' => 'active'
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

        $result = $page->publish();

        $this->assertInstanceOf(Page::class, $result);
        $this->assertTrue($page->getPublished());
        $this->assertEquals('active', $page->getWorkflowState());
    }

    public function testUnpublish(): void
    {
        $page = new Page(['url' => 'test-page']);

        $responseData = [
            'url' => 'test-page',
            'title' => 'Test Page',
            'published' => false,
            'workflow_state' => 'unpublished'
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

        $result = $page->unpublish();

        $this->assertInstanceOf(Page::class, $result);
        $this->assertFalse($page->getPublished());
        $this->assertEquals('unpublished', $page->getWorkflowState());
    }

    public function testIsPublished(): void
    {
        $page1 = new Page(['published' => true]);
        $this->assertTrue($page1->isPublished());

        $page2 = new Page(['workflow_state' => 'active']);
        $this->assertTrue($page2->isPublished());

        $page3 = new Page(['published' => false]);
        $this->assertFalse($page3->isPublished());
    }

    public function testIsDraft(): void
    {
        $page1 = new Page(['published' => false]);
        $this->assertTrue($page1->isDraft());

        $page2 = new Page(['workflow_state' => 'unpublished']);
        $this->assertTrue($page2->isDraft());

        $page3 = new Page(['published' => true]);
        $this->assertFalse($page3->isDraft());
    }

    public function testGenerateSlug(): void
    {
        $this->assertEquals('hello-world', Page::generateSlug('Hello World'));
        $this->assertEquals('test-page-123', Page::generateSlug('Test Page 123'));
        $this->assertEquals('remove-special-chars', Page::generateSlug('Remove!@#$%^&*()Special Chars'));
        $this->assertEquals('multiple-spaces', Page::generateSlug('Multiple    Spaces'));
        $this->assertEquals('trim-hyphens', Page::generateSlug('---Trim Hyphens---'));
    }

    public function testGetUrlSlug(): void
    {
        $page = new Page(['url' => 'test-page']);
        $this->assertEquals('test-page', $page->getUrlSlug());

        $page2 = new Page();
        $this->assertEquals('', $page2->getUrlSlug());
    }

    public function testUpdateUrlSlug(): void
    {
        $page = new Page(['url' => 'old-slug']);

        $responseData = [
            'url' => 'new-slug',
            'title' => 'Test Page'
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

        $result = $page->updateUrlSlug('new-slug');

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals('new-slug', $page->getUrl());
    }

    public function testUpdateUrlSlugThrowsExceptionForMissingUrl(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Current page URL is required');

        $page = new Page();
        $page->updateUrlSlug('new-slug');
    }

    public function testGetRevisions(): void
    {
        $page = new Page(['url' => 'test-page']);

        $responseData = [
            ['revision_id' => 1, 'updated_at' => '2024-01-01T00:00:00Z'],
            ['revision_id' => 2, 'updated_at' => '2024-01-02T00:00:00Z']
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
            ->with('courses/123/pages/test-page/revisions')
            ->willReturn($responseMock);

        $revisions = $page->getRevisions();

        $this->assertCount(2, $revisions);
        $this->assertInstanceOf(PageRevision::class, $revisions[0]);
        $this->assertInstanceOf(PageRevision::class, $revisions[1]);
        $this->assertEquals(1, $revisions[0]->getRevisionId());
        $this->assertEquals(2, $revisions[1]->getRevisionId());
    }

    public function testGetRevisionsThrowsExceptionForMissingUrl(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Page URL is required');

        $page = new Page();
        $page->getRevisions();
    }

    public function testToArray(): void
    {
        $page = new Page([
            'url' => 'test-page',
            'title' => 'Test Page',
            'body' => '<p>Content</p>',
            'workflow_state' => 'active',
            'editing_roles' => 'teachers',
            'published' => true,
            'front_page' => false
        ]);

        $array = $page->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test-page', $array['url']);
        $this->assertEquals('Test Page', $array['title']);
        $this->assertEquals('<p>Content</p>', $array['body']);
        $this->assertEquals('active', $array['workflow_state']);
        $this->assertEquals('teachers', $array['editing_roles']);
        $this->assertTrue($array['published']);
        $this->assertFalse($array['front_page']);
    }

    public function testToDtoArray(): void
    {
        $page = new Page([
            'url' => 'test-page',
            'title' => 'Test Page',
            'body' => '<p>Content</p>',
            'editing_roles' => 'teachers',
            'published' => true,
            'front_page' => false
        ]);

        $dtoArray = $page->toDtoArray();

        $this->assertIsArray($dtoArray);
        $this->assertEquals('Test Page', $dtoArray['title']);
        $this->assertEquals('<p>Content</p>', $dtoArray['body']);
        $this->assertEquals('teachers', $dtoArray['editing_roles']);
        $this->assertTrue($dtoArray['published']);
        $this->assertFalse($dtoArray['front_page']);
        $this->assertArrayNotHasKey('url', $dtoArray); // URL is not included in DTO array
    }

    public function testDuplicate(): void
    {
        $page = new Page(['url' => 'original-page', 'title' => 'Original Page']);

        $duplicatedData = [
            'url' => 'original-page-copy',
            'title' => 'Original Page Copy',
            'body' => '<p>Duplicated content</p>',
            'published' => false
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($duplicatedData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/123/pages/original-page/duplicate')
            ->willReturn($responseMock);

        $duplicatedPage = $page->duplicate();

        $this->assertInstanceOf(Page::class, $duplicatedPage);
        $this->assertEquals('original-page-copy', $duplicatedPage->getUrl());
        $this->assertEquals('Original Page Copy', $duplicatedPage->getTitle());
        $this->assertNotEquals($page->getUrl(), $duplicatedPage->getUrl());
    }

    public function testDuplicateWithPageId(): void
    {
        $page = new Page(['page_id' => 456, 'title' => 'Original Page']);

        $duplicatedData = [
            'url' => 'original-page-copy',
            'title' => 'Original Page Copy'
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($duplicatedData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/123/pages/456/duplicate')
            ->willReturn($responseMock);

        $duplicatedPage = $page->duplicate();

        $this->assertInstanceOf(Page::class, $duplicatedPage);
    }

    public function testDuplicateThrowsExceptionForMissingIdentifier(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Page URL or ID is required for duplication');

        $page = new Page();
        $page->duplicate();
    }

    public function testGetRevision(): void
    {
        $page = new Page(['url' => 'test-page']);

        $revisionData = [
            'revision_id' => 3,
            'updated_at' => '2024-01-15T00:00:00Z',
            'latest' => false,
            'edited_by' => ['id' => 1, 'name' => 'John Doe'],
            'url' => 'test-page',
            'title' => 'Test Page - Old Version',
            'body' => '<p>Old content</p>'
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($revisionData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/pages/test-page/revisions/3', ['query' => []])
            ->willReturn($responseMock);

        $revision = $page->getRevision(3);

        $this->assertInstanceOf(PageRevision::class, $revision);
        $this->assertEquals(3, $revision->getRevisionId());
        $this->assertEquals('Test Page - Old Version', $revision->getTitle());
    }

    public function testGetRevisionLatest(): void
    {
        $page = new Page(['url' => 'test-page']);

        $revisionData = [
            'revision_id' => 5,
            'latest' => true
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($revisionData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/pages/test-page/revisions/latest', ['query' => []])
            ->willReturn($responseMock);

        $revision = $page->getRevision('latest');

        $this->assertInstanceOf(PageRevision::class, $revision);
        $this->assertTrue($revision->getLatest());
    }

    public function testGetRevisionWithSummary(): void
    {
        $page = new Page(['url' => 'test-page']);

        $revisionData = [
            'revision_id' => 3,
            'updated_at' => '2024-01-15T00:00:00Z',
            'latest' => false
            // Body excluded when summary=true
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($revisionData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/pages/test-page/revisions/3', ['query' => ['summary' => true]])
            ->willReturn($responseMock);

        $revision = $page->getRevision(3, true);

        $this->assertInstanceOf(PageRevision::class, $revision);
        $this->assertNull($revision->getBody());
    }

    public function testRevertToRevision(): void
    {
        $page = new Page(['url' => 'test-page']);

        $revertedData = [
            'revision_id' => 6,
            'updated_at' => '2024-01-20T00:00:00Z',
            'title' => 'Reverted Page Title',
            'body' => '<p>Reverted content</p>'
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($revertedData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/123/pages/test-page/revisions/3')
            ->willReturn($responseMock);

        $result = $page->revertToRevision(3);

        $this->assertInstanceOf(PageRevision::class, $result);
        $this->assertEquals(6, $result->getRevisionId());
        $this->assertEquals('Reverted Page Title', $result->getTitle());
    }

    public function testUpdateFrontPage(): void
    {
        $updateData = [
            'title' => 'New Front Page',
            'body' => '<h1>Welcome!</h1>'
        ];

        $responseData = [
            'url' => 'new-front-page',
            'title' => 'New Front Page',
            'body' => '<h1>Welcome!</h1>',
            'front_page' => true,
            'published' => true
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
            ->with('courses/123/front_page', $this->anything())
            ->willReturn($responseMock);

        $frontPage = Page::updateFrontPage($updateData);

        $this->assertInstanceOf(Page::class, $frontPage);
        $this->assertEquals('New Front Page', $frontPage->getTitle());
        $this->assertTrue($frontPage->getFrontPage());
    }

    public function testFetchAllWithQueryParams(): void
    {
        $params = [
            'sort' => 'title',
            'order' => 'desc',
            'search_term' => 'test',
            'published' => true,
            'include' => ['body']
        ];

        $responseData = [
            [
                'url' => 'test-page-1',
                'title' => 'Test Page 1',
                'body' => '<p>Content 1</p>',
                'published' => true
            ],
            [
                'url' => 'test-page-2',
                'title' => 'Test Page 2',
                'body' => '<p>Content 2</p>',
                'published' => true
            ]
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $expectedParams = $params;
        $expectedParams['include'] = 'body'; // Converted from array to string

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/pages', ['query' => $expectedParams])
            ->willReturn($responseMock);

        $pages = Page::fetchAll($params);

        $this->assertCount(2, $pages);
        $this->assertEquals('Test Page 1', $pages[0]->getTitle());
        $this->assertEquals('<p>Content 1</p>', $pages[0]->getBody());
    }

    public function testGetSafeBody(): void
    {
        $page = new Page();

        // Test null body
        $this->assertNull($page->getSafeBody());

        // Test safe content
        $page->setBody('<p>This is <strong>safe</strong> content</p>');
        $this->assertEquals('<p>This is <strong>safe</strong> content</p>', $page->getSafeBody());

        // Test script removal
        $page->setBody('<p>Text before<script>alert("XSS")</script>Text after</p>');
        $this->assertEquals('<p>Text beforeText after</p>', $page->getSafeBody());

        // Test event handler removal
        $page->setBody('<p>Click me</p>');
        $this->assertEquals('<p>Click me</p>', $page->getSafeBody());
        
        // Now test with event handlers - using double quotes
        $htmlWithHandlers = '<p onclick="alert(123)">Click me</p>';
        $page->setBody($htmlWithHandlers);
        $safeResult = $page->getSafeBody();
        // Should remove onclick but keep tags and content
        $this->assertNotEmpty($safeResult, 'Safe body should not be empty. Got: ' . var_export($safeResult, true));
        $this->assertStringNotContainsString('onclick', $safeResult);
        $this->assertStringContainsString('<p', $safeResult);
        $this->assertStringContainsString('Click me', $safeResult);

        // Test javascript: protocol removal
        $page->setBody('<a href="javascript:void(0)">Bad Link</a>');
        $safeResult = $page->getSafeBody();
        $this->assertStringNotContainsString('javascript:', $safeResult);
        $this->assertStringContainsString('<a href="#">Bad Link</a>', $safeResult);

        // Test data: protocol removal
        $page->setBody('<img src="data:text/html,<script>alert(\'XSS\')</script>">');
        $safeResult = $page->getSafeBody();
        $this->assertStringNotContainsString('data:', $safeResult);
        $this->assertStringContainsString('<img', $safeResult);

        // Test iframe removal
        $page->setBody('<p>Before</p><iframe src="evil.com"></iframe><p>After</p>');
        $this->assertEquals('<p>Before</p><p>After</p>', $page->getSafeBody());

        // Test complex malicious content
        $maliciousContent = <<<HTML
<div>
    <h1>Title</h1>
    <p>Safe paragraph</p>
    <script type="text/javascript">
        document.cookie = "stolen";
    </script>
    <a href="javascript:void(0)" onclick="stealData()">Click</a>
    <iframe src="https://evil.com"></iframe>
    <object data="malicious.swf"></object>
    <embed src="bad.swf">
    <p onmouseover="track()">Hover paragraph</p>
    <img src="valid.jpg" onerror="alert('XSS')" />
</div>
HTML;

        $page->setBody($maliciousContent);
        $safeBody = $page->getSafeBody();

        // Verify dangerous content is removed
        $this->assertStringNotContainsString('<script', $safeBody);
        $this->assertStringNotContainsString('onclick', $safeBody);
        $this->assertStringNotContainsString('onmouseover', $safeBody);
        $this->assertStringNotContainsString('onerror', $safeBody);
        $this->assertStringNotContainsString('javascript:', $safeBody);
        $this->assertStringNotContainsString('<iframe', $safeBody);
        $this->assertStringNotContainsString('<object', $safeBody);
        $this->assertStringNotContainsString('<embed', $safeBody);

        // Verify safe content is preserved
        $this->assertStringContainsString('<h1>Title</h1>', $safeBody);
        $this->assertStringContainsString('<p>Safe paragraph</p>', $safeBody);
        // img tag should be present with src but without onerror
        if (strpos($safeBody, '<img') !== false) {
            $this->assertStringContainsString('valid.jpg', $safeBody);
            $this->assertStringNotContainsString('onerror', $safeBody);
        }
    }
}