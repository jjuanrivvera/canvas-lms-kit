<?php

declare(strict_types=1);

namespace Tests\Api\CalendarEvents;

use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class CalendarEventAccountContextTest extends TestCase
{
    private HttpClientInterface&MockObject $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        CalendarEvent::setApiClient($this->mockClient);
        Config::setAccountId(1);
    }

    public function testGetDefaultsToAccountContext(): void
    {
        $mockResponse = $this->createMockResponseWithBody([
            [
                'id' => 1,
                'title' => 'Account Meeting',
                'context_code' => 'account_1',
                'start_at' => '2025-03-15T10:00:00Z',
                'end_at' => '2025-03-15T11:00:00Z',
            ],
            [
                'id' => 2,
                'title' => 'Department Review',
                'context_code' => 'account_1',
                'start_at' => '2025-03-16T14:00:00Z',
                'end_at' => '2025-03-16T15:00:00Z',
            ],
        ]);

        $expectedParams = [
            'query' => [
                'context_codes' => ['account_1'],
            ],
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('calendar_events', $expectedParams)
            ->willReturn($mockResponse);

        $events = CalendarEvent::get();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(CalendarEvent::class, $events[0]);
        $this->assertEquals('Account Meeting', $events[0]->title);
        $this->assertEquals('account', $events[0]->getContextType());
        $this->assertEquals(1, $events[0]->getContextId());
    }

    public function testGetWithCustomContextCodes(): void
    {
        $mockResponse = $this->createMockResponseWithBody([
            ['id' => 3, 'title' => 'Course Event', 'context_code' => 'course_123'],
        ]);

        $params = ['context_codes' => ['course_123', 'user_456']];
        $expectedParams = [
            'query' => $params,
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('calendar_events', $expectedParams)
            ->willReturn($mockResponse);

        $events = CalendarEvent::get($params);

        $this->assertCount(1, $events);
        $this->assertEquals('Course Event', $events[0]->title);
    }

    public function testGetPaginatedDefaultsToAccountContext(): void
    {
        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn([
                ['id' => 1, 'title' => 'Test Event', 'context_code' => 'account_1'],
            ]);

        $mockPaginationResult = $this->createMock(PaginationResult::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('toPaginationResult')
            ->with($this->isType('array'))
            ->willReturn($mockPaginationResult);

        $expectedParams = [
            'query' => [
                'context_codes' => ['account_1'],
            ],
        ];

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('calendar_events', $expectedParams)
            ->willReturn($mockPaginatedResponse);

        $response = CalendarEvent::paginate();

        $this->assertInstanceOf(PaginationResult::class, $response);
    }

    public function testFetchByContextForCourse(): void
    {
        $mockResponse = $this->createMockResponseWithBody([
            [
                'id' => 4,
                'title' => 'Midterm Exam',
                'context_code' => 'course_789',
                'start_at' => '2025-03-20T10:00:00Z',
                'end_at' => '2025-03-20T12:00:00Z',
            ],
        ]);

        $expectedParams = [
            'query' => [
                'context_codes' => ['course_789'],
            ],
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('calendar_events', $expectedParams)
            ->willReturn($mockResponse);

        $events = CalendarEvent::fetchByContext('course', 789);

        $this->assertCount(1, $events);
        $this->assertEquals('Midterm Exam', $events[0]->title);
        $this->assertEquals('course', $events[0]->getContextType());
        $this->assertEquals(789, $events[0]->getContextId());
    }

    public function testFetchByContextForUser(): void
    {
        $mockResponse = $this->createMockResponseWithBody([
            [
                'id' => 5,
                'title' => 'Personal Task',
                'context_code' => 'user_456',
                'start_at' => '2025-03-25T09:00:00Z',
                'end_at' => '2025-03-25T09:30:00Z',
            ],
        ]);

        $expectedParams = [
            'query' => [
                'context_codes' => ['user_456'],
            ],
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('calendar_events', $expectedParams)
            ->willReturn($mockResponse);

        $events = CalendarEvent::fetchByContext('user', 456);

        $this->assertCount(1, $events);
        $this->assertEquals('Personal Task', $events[0]->title);
        $this->assertEquals('user', $events[0]->getContextType());
        $this->assertEquals(456, $events[0]->getContextId());
    }

    public function testFetchByContextForGroup(): void
    {
        $mockResponse = $this->createMockResponseWithBody([
            [
                'id' => 6,
                'title' => 'Group Meeting',
                'context_code' => 'group_321',
                'start_at' => '2025-03-28T15:00:00Z',
                'end_at' => '2025-03-28T16:00:00Z',
            ],
        ]);

        $expectedParams = [
            'query' => [
                'context_codes' => ['group_321'],
            ],
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('calendar_events', $expectedParams)
            ->willReturn($mockResponse);

        $events = CalendarEvent::fetchByContext('group', 321);

        $this->assertCount(1, $events);
        $this->assertEquals('Group Meeting', $events[0]->title);
        $this->assertEquals('group', $events[0]->getContextType());
        $this->assertEquals(321, $events[0]->getContextId());
    }

    public function testFetchByContextWithAdditionalParams(): void
    {
        $mockResponse = $this->createMockResponseWithBody([
            ['id' => 7, 'title' => 'Important Event', 'context_code' => 'course_999'],
        ]);

        $params = [
            'important_dates' => true,
            'start_date' => '2025-03-01',
            'end_date' => '2025-03-31',
        ];

        $expectedParams = [
            'query' => array_merge($params, [
                'context_codes' => ['course_999'],
            ]),
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('calendar_events', $expectedParams)
            ->willReturn($mockResponse);

        $events = CalendarEvent::fetchByContext('course', 999, $params);

        $this->assertCount(1, $events);
        $this->assertEquals('Important Event', $events[0]->title);
    }

    public function testCourseCalendarEventsMethod(): void
    {
        $mockResponse = $this->createMockResponseWithBody([
            ['id' => 8, 'title' => 'Course Assignment Due', 'context_code' => 'course_555'],
        ]);

        $expectedParams = [
            'query' => [
                'context_codes' => ['course_555'],
            ],
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('calendar_events', $expectedParams)
            ->willReturn($mockResponse);

        $course = new \CanvasLMS\Api\Courses\Course(['id' => 555]);
        $events = $course->calendarEvents();

        $this->assertCount(1, $events);
        $this->assertEquals('Course Assignment Due', $events[0]->title);
    }

    public function testContextPropertyParsing(): void
    {
        $event = new CalendarEvent([
            'id' => 9,
            'title' => 'Test Event',
            'context_code' => 'course_123',
        ]);

        $this->assertEquals('course', $event->getContextType());
        $this->assertEquals(123, $event->getContextId());
    }

    public function testGetThrowsExceptionWithoutAccountId(): void
    {
        // Set account ID to 0 (invalid)
        Config::setAccountId(0);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Account ID must be configured to fetch calendar events');

        CalendarEvent::get();

        // Reset to valid value for other tests
        Config::setAccountId(1);
    }

    private function createMockResponseWithBody(array $data): ResponseInterface&MockObject
    {
        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('__toString')->willReturn(json_encode($data));
        $mockBody->method('getContents')->willReturn(json_encode($data));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockBody);

        return $mockResponse;
    }
}
