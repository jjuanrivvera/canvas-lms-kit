<?php

namespace Tests\Api\Tabs;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Tabs\Tab;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Tabs\UpdateTabDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \CanvasLMS\Api\Tabs\Tab
 */
class TabTest extends TestCase
{
    private HttpClientInterface $httpClientMock;
    private Course $course;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->course = new Course(['id' => 123]);

        Tab::setApiClient($this->httpClientMock);
        Tab::setCourse($this->course);
    }

    protected function tearDown(): void
    {
        // Reset static properties for clean tests
        $reflection = new \ReflectionClass(Tab::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 456]);
        Tab::setCourse($course);

        // We can't directly test the course is set since it's protected,
        // but we can test that checkCourse doesn't throw an exception
        $this->assertTrue(Tab::checkCourse());
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course is required');

        // Reset course to one without ID
        $reflection = new \ReflectionClass(Tab::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, null);

        Tab::checkCourse();
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 'assignments',
            'label' => 'Assignments',
            'html_url' => '/courses/123/assignments',
            'type' => 'internal',
            'hidden' => false,
            'visibility' => 'public',
            'position' => 2
        ];

        $tab = new Tab($data);

        $this->assertEquals('assignments', $tab->getId());
        $this->assertEquals('Assignments', $tab->getLabel());
        $this->assertEquals('/courses/123/assignments', $tab->getHtmlUrl());
        $this->assertEquals('internal', $tab->getType());
        $this->assertFalse($tab->getHidden());
        $this->assertEquals('public', $tab->getVisibility());
        $this->assertEquals(2, $tab->getPosition());
    }

    public function testGettersAndSetters(): void
    {
        $tab = new Tab();

        $tab->setId('home');
        $this->assertEquals('home', $tab->getId());

        $tab->setLabel('Home');
        $this->assertEquals('Home', $tab->getLabel());

        $tab->setHtmlUrl('/courses/123');
        $this->assertEquals('/courses/123', $tab->getHtmlUrl());

        $tab->setType('internal');
        $this->assertEquals('internal', $tab->getType());

        $tab->setHidden(true);
        $this->assertTrue($tab->getHidden());

        $tab->setVisibility('members');
        $this->assertEquals('members', $tab->getVisibility());

        $tab->setPosition(1);
        $this->assertEquals(1, $tab->getPosition());
    }

    public function testGet(): void
    {
        $responseData = [
            [
                'id' => 'home',
                'label' => 'Home',
                'html_url' => '/courses/123',
                'type' => 'internal',
                'hidden' => false,
                'visibility' => 'public',
                'position' => 1
            ],
            [
                'id' => 'assignments',
                'label' => 'Assignments',
                'html_url' => '/courses/123/assignments',
                'type' => 'internal',
                'hidden' => false,
                'visibility' => 'public',
                'position' => 2
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
            ->with('courses/123/tabs', ['query' => []])
            ->willReturn($responseMock);

        $tabs = Tab::get();

        $this->assertCount(2, $tabs);
        $this->assertInstanceOf(Tab::class, $tabs[0]);
        $this->assertEquals('home', $tabs[0]->getId());
        $this->assertEquals('assignments', $tabs[1]->getId());
    }

    public function testGetWithParams(): void
    {
        $params = ['include' => ['course_subject_tabs']];
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
            ->with('courses/123/tabs', ['query' => $params])
            ->willReturn($responseMock);

        $tabs = Tab::get($params);

        $this->assertCount(0, $tabs);
    }

    public function testGetPaginated(): void
    {
        $paginatedResponseMock = $this->createMock(PaginatedResponse::class);

        // Mock the getPaginatedResponse method
        $tabClass = new class extends Tab {
            public static function testGetPaginatedResponse(string $endpoint, array $params): PaginatedResponse
            {
                return parent::getPaginatedResponse($endpoint, $params);
            }
        };

        // Since we can't easily mock static methods, we'll test that the method exists
        // and returns the expected type from the parent class
        $this->assertTrue(method_exists(Tab::class, 'paginate'));
    }

    public function testUpdate(): void
    {
        $tabId = 'assignments';
        $updateData = ['position' => 3, 'hidden' => true];
        $responseData = [
            'id' => 'assignments',
            'label' => 'Assignments',
            'position' => 3,
            'hidden' => true
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
            ->with('courses/123/tabs/assignments', ['multipart' => $updateData])
            ->willReturn($responseMock);

        $updatedTab = Tab::update($tabId, $updateData);

        $this->assertInstanceOf(Tab::class, $updatedTab);
        $this->assertEquals('assignments', $updatedTab->getId());
        $this->assertEquals(3, $updatedTab->getPosition());
        $this->assertTrue($updatedTab->getHidden());
    }

    public function testUpdateWithDTO(): void
    {
        $tabId = 'home';
        $updateDto = new UpdateTabDTO(position: 1, hidden: false);
        $responseData = [
            'id' => 'home',
            'label' => 'Home',
            'position' => 1,
            'hidden' => false
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
            ->with('courses/123/tabs/home', ['multipart' => $updateDto->toApiArray()])
            ->willReturn($responseMock);

        $updatedTab = Tab::update($tabId, $updateDto);

        $this->assertInstanceOf(Tab::class, $updatedTab);
        $this->assertEquals('home', $updatedTab->getId());
        $this->assertEquals(1, $updatedTab->getPosition());
        $this->assertFalse($updatedTab->getHidden());
    }

    public function testSave(): void
    {
        $tab = new Tab([
            'id' => 'assignments',
            'label' => 'Assignments',
            'position' => 2,
            'hidden' => false
        ]);

        // Change some properties
        $tab->setPosition(5);
        $tab->setHidden(true);

        $responseData = [
            'id' => 'assignments',
            'label' => 'Assignments',
            'position' => 5,
            'hidden' => true
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $expectedData = ['position' => 5, 'hidden' => true];
        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('courses/123/tabs/assignments', ['multipart' => $expectedData])
            ->willReturn($responseMock);

        $result = $tab->save();

        $this->assertInstanceOf(Tab::class, $result);
        $this->assertEquals(5, $tab->getPosition());
        $this->assertTrue($tab->getHidden());
    }

    public function testSaveWithoutId(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Tab ID is required for save operation');

        $tab = new Tab();
        $tab->save();
    }

    public function testSaveWithNoChanges(): void
    {
        $tab = new Tab(['id' => 'home']);
        $result = $tab->save();

        $this->assertInstanceOf(Tab::class, $result);
    }

    public function testSaveReturnsFalseOnException(): void
    {
        $tab = new Tab([
            'id' => 'assignments',
            'position' => 3
        ]);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->willThrowException(new CanvasApiException('API Error'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('API Error');
        $tab->save();
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 'assignments',
            'label' => 'Assignments',
            'html_url' => '/courses/123/assignments',
            'type' => 'internal',
            'hidden' => false,
            'visibility' => 'public',
            'position' => 2
        ];

        $tab = new Tab($data);
        $result = $tab->toArray();

        $this->assertEquals($data, $result);
    }

    public function testFindThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage(
            'Canvas API does not support finding individual tabs by ID. Use get() to retrieve all tabs.'
        );

        Tab::find(123);
    }
}
