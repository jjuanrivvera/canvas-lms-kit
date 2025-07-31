<?php

namespace Tests\Api\CalendarEvents;

use DateTime;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Dto\CalendarEvents\CreateCalendarEventDTO;
use CanvasLMS\Dto\CalendarEvents\UpdateCalendarEventDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;

class CalendarEventTest extends TestCase
{
    /**
     * @var CalendarEvent
     */
    private $calendarEvent;

    /**
     * @var HttpClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $httpClientMock;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        CalendarEvent::setApiClient($this->httpClientMock);
        
        $this->calendarEvent = new CalendarEvent([]);
    }

    /**
     * Calendar event data provider
     * @return array<array<mixed>>
     */
    public static function calendarEventDataProvider(): array
    {
        return [
            [
                [
                    'context_code' => 'course_123',
                    'title' => 'Midterm Exam',
                    'start_at' => new DateTime('2025-03-15 10:00:00'),
                    'end_at' => new DateTime('2025-03-15 12:00:00'),
                    'location_name' => 'Room 101',
                    'description' => 'Covers chapters 1-5'
                ],
                [
                    'id' => 1,
                    'context_code' => 'course_123',
                    'title' => 'Midterm Exam',
                    'start_at' => '2025-03-15T10:00:00Z',
                    'end_at' => '2025-03-15T12:00:00Z',
                    'location_name' => 'Room 101',
                    'description' => 'Covers chapters 1-5',
                    'workflow_state' => 'active',
                    'hidden' => false,
                    'all_day' => false,
                    'created_at' => '2025-01-01T10:00:00Z',
                    'updated_at' => '2025-01-01T10:00:00Z'
                ]
            ],
        ];
    }

    /**
     * Test the create calendar event method
     * @dataProvider calendarEventDataProvider
     * @param array<string, mixed> $eventData
     * @param array<string, mixed> $expectedResult
     * @return void
     */
    public function testCreateCalendarEvent(array $eventData, array $expectedResult): void
    {
        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('/calendar_events')
            ->willReturn($response);

        $event = CalendarEvent::create($eventData);

        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertEquals('Midterm Exam', $event->title);
        $this->assertEquals('course_123', $event->contextCode);
    }

    /**
     * Test the create calendar event method with DTO
     * @dataProvider calendarEventDataProvider
     * @param array<string, mixed> $eventData
     * @param array<string, mixed> $expectedResult
     * @return void
     */
    public function testCreateCalendarEventWithDto(array $eventData, array $expectedResult): void
    {
        $eventDto = new CreateCalendarEventDTO($eventData);
        $expectedPayload = $eventDto->toApiArray();

        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/calendar_events'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        $event = CalendarEvent::create($eventDto);

        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertEquals('Midterm Exam', $event->title);
    }

    /**
     * Test the find calendar event method
     * @return void
     */
    public function testFindCalendarEvent(): void
    {
        $expectedResult = [
            'id' => 456,
            'title' => 'Found Event',
            'context_code' => 'user_789',
            'start_at' => '2025-02-01T14:00:00Z',
            'end_at' => '2025-02-01T15:00:00Z'
        ];

        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/calendar_events/456')
            ->willReturn($response);

        $event = CalendarEvent::find(456);

        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertEquals(456, $event->id);
        $this->assertEquals('Found Event', $event->title);
    }

    /**
     * Test the fetch all calendar events method
     * @return void
     */
    public function testFetchAllCalendarEvents(): void
    {
        $expectedResult = [
            [
                'id' => 1,
                'title' => 'Event 1',
                'context_code' => 'course_123'
            ],
            [
                'id' => 2,
                'title' => 'Event 2',
                'context_code' => 'course_123'
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/calendar_events', ['query' => []])
            ->willReturn($response);

        $events = CalendarEvent::fetchAll();

        $this->assertIsArray($events);
        $this->assertCount(2, $events);
        $this->assertInstanceOf(CalendarEvent::class, $events[0]);
        $this->assertEquals('Event 1', $events[0]->title);
    }

    /**
     * Test the fetch all for context method
     * @return void
     */
    public function testFetchAllForContext(): void
    {
        $contextCode = 'course_456';
        $expectedResult = [
            [
                'id' => 3,
                'title' => 'Course Event',
                'context_code' => $contextCode
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/calendar_events', ['query' => ['context_codes' => [$contextCode]]])
            ->willReturn($response);

        $events = CalendarEvent::fetchAllForContext($contextCode);

        $this->assertIsArray($events);
        $this->assertCount(1, $events);
        $this->assertEquals($contextCode, $events[0]->contextCode);
    }

    /**
     * Test the update calendar event method
     * @return void
     */
    public function testUpdateCalendarEvent(): void
    {
        $updateData = [
            'title' => 'Updated Event Title',
            'location_name' => 'Room 202'
        ];

        $expectedResult = [
            'id' => 789,
            'title' => 'Updated Event Title',
            'location_name' => 'Room 202',
            'context_code' => 'course_123'
        ];

        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('/calendar_events/789')
            ->willReturn($response);

        $event = CalendarEvent::update(789, $updateData);

        $this->assertEquals('Updated Event Title', $event->title);
        $this->assertEquals('Room 202', $event->locationName);
    }

    /**
     * Test the update calendar event method with DTO
     * @return void
     */
    public function testUpdateCalendarEventWithDto(): void
    {
        $updateDto = new UpdateCalendarEventDTO([
            'title' => 'DTO Updated Title',
            'all_day' => true
        ]);

        $expectedResult = [
            'id' => 999,
            'title' => 'DTO Updated Title',
            'all_day' => true,
            'context_code' => 'user_111'
        ];

        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('/calendar_events/999')
            ->willReturn($response);

        $event = CalendarEvent::update(999, $updateDto);

        $this->assertEquals('DTO Updated Title', $event->title);
        $this->assertTrue($event->allDay);
    }

    /**
     * Test the save instance method
     * @return void
     */
    public function testSaveCalendarEvent(): void
    {
        $event = new CalendarEvent([
            'id' => 111,
            'title' => 'Original Title',
            'context_code' => 'group_222'
        ]);

        $event->setTitle('Modified Title');
        $event->setLocationName('New Location');

        $expectedResult = [
            'id' => 111,
            'title' => 'Modified Title',
            'location_name' => 'New Location',
            'context_code' => 'group_222'
        ];

        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('/calendar_events/111')
            ->willReturn($response);

        $result = $event->save();

        $this->assertTrue($result);
        $this->assertEquals('Modified Title', $event->title);
        $this->assertEquals('New Location', $event->locationName);
    }

    /**
     * Test the delete calendar event method
     * @return void
     */
    public function testDeleteCalendarEvent(): void
    {
        $event = new CalendarEvent([
            'id' => 222,
            'title' => 'Event to Delete'
        ]);

        $response = new Response(200, [], json_encode([]));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('/calendar_events/222')
            ->willReturn($response);

        $result = $event->delete();

        $this->assertTrue($result);
    }

    /**
     * Test the reserve appointment method
     * @return void
     */
    public function testReserveAppointment(): void
    {
        $event = new CalendarEvent([
            'id' => 333,
            'appointment_group_id' => 444
        ]);

        $expectedResult = [
            'id' => 333,
            'reserved' => true,
            'own_reservation' => true
        ];

        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('/calendar_events/333/reservations')
            ->willReturn($response);

        $reservation = $event->reserve();

        $this->assertInstanceOf(CalendarEvent::class, $reservation);
        $this->assertTrue($reservation->reserved);
        $this->assertTrue($reservation->ownReservation);
    }

    /**
     * Test the unreserve appointment method
     * @return void
     */
    public function testUnreserveAppointment(): void
    {
        $event = new CalendarEvent([
            'id' => 555,
            'reserved' => true
        ]);

        $response = new Response(200, [], json_encode([]));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('/calendar_events/555/reservations')
            ->willReturn($response);

        $result = $event->unreserve();

        $this->assertTrue($result);
    }

    /**
     * Test duplicate event method
     * @return void
     */
    public function testDuplicateEvent(): void
    {
        $originalEvent = new CalendarEvent([
            'id' => 666,
            'title' => 'Original Event',
            'context_code' => 'course_777',
            'start_at' => new DateTime('2025-04-01 10:00:00'),
            'end_at' => new DateTime('2025-04-01 11:00:00')
        ]);

        $duplicateData = [
            'title' => 'Duplicated Event',
            'start_at' => new DateTime('2025-04-08 10:00:00')
        ];

        $expectedResult = [
            'id' => 888,
            'title' => 'Duplicated Event',
            'context_code' => 'course_777',
            'start_at' => '2025-04-08T10:00:00Z',
            'end_at' => '2025-04-08T11:00:00Z'
        ];

        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('/calendar_events')
            ->willReturn($response);

        $duplicate = $originalEvent->duplicate($duplicateData);

        $this->assertInstanceOf(CalendarEvent::class, $duplicate);
        $this->assertEquals('Duplicated Event', $duplicate->title);
        $this->assertEquals(888, $duplicate->id);
        $this->assertNotEquals($originalEvent->id, $duplicate->id);
    }

    /**
     * Test create series method
     * @return void
     */
    public function testCreateSeries(): void
    {
        $baseEvent = new CalendarEvent([
            'title' => 'Weekly Meeting',
            'context_code' => 'course_999',
            'start_at' => new DateTime('2025-02-01 14:00:00'),
            'end_at' => new DateTime('2025-02-01 15:00:00')
        ]);

        $dates = [
            new DateTime('2025-02-08 14:00:00'),
            new DateTime('2025-02-15 14:00:00'),
            new DateTime('2025-02-22 14:00:00')
        ];

        $response1 = new Response(200, [], json_encode(['id' => 1001, 'title' => 'Weekly Meeting']));
        $response2 = new Response(200, [], json_encode(['id' => 1002, 'title' => 'Weekly Meeting']));
        $response3 = new Response(200, [], json_encode(['id' => 1003, 'title' => 'Weekly Meeting']));
        
        $this->httpClientMock
            ->expects($this->exactly(3))
            ->method('post')
            ->with('/calendar_events')
            ->willReturnOnConsecutiveCalls(
                $response1,
                $response2,
                $response3
            );

        $series = $baseEvent->createSeries($dates);

        $this->assertIsArray($series);
        $this->assertCount(3, $series);
        $this->assertInstanceOf(CalendarEvent::class, $series[0]);
    }

    /**
     * Test context parsing methods
     * @return void
     */
    public function testContextParsing(): void
    {
        $event = new CalendarEvent([
            'context_code' => 'course_12345'
        ]);

        $this->assertEquals('course', $event->getContextType());
        $this->assertEquals(12345, $event->getContextId());
    }

    /**
     * Test event state checking methods
     * @return void
     */
    public function testEventStateChecking(): void
    {
        $activeEvent = new CalendarEvent([
            'workflow_state' => 'active',
            'location_name' => 'Conference Room',
            'all_day' => true
        ]);

        $this->assertTrue($activeEvent->isActive());
        $this->assertTrue($activeEvent->hasLocation());
        $this->assertTrue($activeEvent->isAllDay());

        $inactiveEvent = new CalendarEvent([
            'workflow_state' => 'deleted',
            'all_day' => false
        ]);

        $this->assertFalse($inactiveEvent->isActive());
        $this->assertFalse($inactiveEvent->hasLocation());
        $this->assertFalse($inactiveEvent->isAllDay());
    }

    /**
     * Test series detection methods
     * @return void
     */
    public function testSeriesDetection(): void
    {
        $seriesEvent = new CalendarEvent([
            'series_uuid' => 'abc-123-def',
            'rrule' => 'FREQ=WEEKLY;COUNT=10'
        ]);

        $this->assertTrue($seriesEvent->isPartOfSeries());

        $standaloneEvent = new CalendarEvent([
            'title' => 'One-time Event'
        ]);

        $this->assertFalse($standaloneEvent->isPartOfSeries());
    }

    /**
     * Test appointment detection
     * @return void
     */
    public function testAppointmentDetection(): void
    {
        $appointmentEvent = new CalendarEvent([
            'appointment_group_id' => 789
        ]);

        $this->assertTrue($appointmentEvent->isAppointment());

        $regularEvent = new CalendarEvent([
            'title' => 'Regular Event'
        ]);

        $this->assertFalse($regularEvent->isAppointment());
    }

    /**
     * Test date/time status methods
     * @return void
     */
    public function testDateTimeStatus(): void
    {
        $pastEvent = new CalendarEvent([
            'start_at' => new DateTime('-2 hours'),
            'end_at' => new DateTime('-1 hour')
        ]);

        $this->assertTrue($pastEvent->hasStarted());
        $this->assertTrue($pastEvent->hasEnded());
        $this->assertFalse($pastEvent->isOngoing());

        $currentEvent = new CalendarEvent([
            'start_at' => new DateTime('-1 hour'),
            'end_at' => new DateTime('+1 hour')
        ]);

        $this->assertTrue($currentEvent->hasStarted());
        $this->assertFalse($currentEvent->hasEnded());
        $this->assertTrue($currentEvent->isOngoing());

        $futureEvent = new CalendarEvent([
            'start_at' => new DateTime('+1 hour'),
            'end_at' => new DateTime('+2 hours')
        ]);

        $this->assertFalse($futureEvent->hasStarted());
        $this->assertFalse($futureEvent->hasEnded());
        $this->assertFalse($futureEvent->isOngoing());
    }

    /**
     * Test exception handling for save without ID
     * @return void
     */
    public function testSaveWithoutIdThrowsException(): void
    {
        $event = new CalendarEvent(['title' => 'New Event']);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot save calendar event without ID');

        $event->save();
    }

    /**
     * Test exception handling for delete without ID
     * @return void
     */
    public function testDeleteWithoutIdThrowsException(): void
    {
        $event = new CalendarEvent(['title' => 'New Event']);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot delete calendar event without ID');

        $event->delete();
    }

    /**
     * Test exception handling for reserve without ID
     * @return void
     */
    public function testReserveWithoutIdThrowsException(): void
    {
        $event = new CalendarEvent(['title' => 'New Event']);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot reserve appointment without event ID');

        $event->reserve();
    }

    /**
     * Test exception handling for create series without context
     * @return void
     */
    public function testCreateSeriesWithoutContextThrowsException(): void
    {
        $event = new CalendarEvent(['title' => 'Event Without Context']);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot create series without context code');

        $event->createSeries([new DateTime()]);
    }
}