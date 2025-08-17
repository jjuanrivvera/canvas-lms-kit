<?php

namespace Tests\Api\CalendarEvents;

use DateTime;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Dto\CalendarEvents\CreateCalendarEventDTO;
use CanvasLMS\Dto\CalendarEvents\UpdateCalendarEventDTO;
use CanvasLMS\Dto\CalendarEvents\CreateReservationDTO;

class CalendarEventTest extends TestCase
{
    /**
     * @var CalendarEvent
     */
    private $calendarEvent;

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
        CalendarEvent::setApiClient($this->httpClientMock);
        
        $this->calendarEvent = new CalendarEvent([]);
    }

    /**
     * Calendar event data provider
     * @return array
     */
    public static function calendarEventDataProvider(): array
    {
        return [
            [
                [
                    'contextCode' => 'course_123',
                    'title' => 'Test Event',
                    'description' => 'Test Description',
                    'startAt' => new DateTime('2024-01-15T10:00:00Z'),
                    'endAt' => new DateTime('2024-01-15T11:00:00Z'),
                ],
                [
                    'id' => 1,
                    'title' => 'Test Event',
                    'description' => 'Test Description',
                    'start_at' => '2024-01-15T10:00:00Z',
                    'end_at' => '2024-01-15T11:00:00Z',
                    'context_code' => 'course_123',
                ]
            ],
        ];
    }

    /**
     * Test the create calendar event method
     * @dataProvider calendarEventDataProvider
     * @param array $eventData
     * @param array $expectedResult
     * @return void
     */
    public function testCreateCalendarEvent(array $eventData, array $expectedResult): void
    {
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->method('post')
            ->willReturn($response);

        $dto = new CreateCalendarEventDTO($eventData);
        $event = CalendarEvent::create($dto);

        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertEquals('Test Event', $event->title);
    }

    /**
     * Test the create calendar event method with DTO
     * @dataProvider calendarEventDataProvider
     * @param array $eventData
     * @param array $expectedResult
     * @return void
     */
    public function testCreateCalendarEventWithDto(array $eventData, array $expectedResult): void
    {
        $dto = new CreateCalendarEventDTO($eventData);
        $expectedPayload = $dto->toApiArray();

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('calendar_events'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        $event = CalendarEvent::create($dto);

        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertEquals('Test Event', $event->title);
    }

    /**
     * Test the find calendar event method
     * @return void
     */
    public function testFindCalendarEvent(): void
    {
        $response = new Response(200, [], json_encode(['id' => 123, 'title' => 'Found Event']));

        $this->httpClientMock
            ->method('get')
            ->willReturn($response);

        $event = CalendarEvent::find(123);

        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertEquals(123, $event->id);
        $this->assertEquals('Found Event', $event->title);
    }

    /**
     * Test the update calendar event method
     * @return void
     */
    public function testUpdateCalendarEvent(): void
    {
        $updateData = [
            'title' => 'Updated Event',
        ];

        $response = new Response(200, [], json_encode(['id' => 1, 'title' => 'Updated Event']));

        $this->httpClientMock
            ->method('put')
            ->willReturn($response);

        $dto = new UpdateCalendarEventDTO($updateData);
        $event = CalendarEvent::update(1, $dto);

        $this->assertEquals('Updated Event', $event->title);
    }

    /**
     * Test the save calendar event method
     * @return void
     */
    public function testSaveCalendarEvent(): void
    {
        $this->calendarEvent->id = 1;
        $this->calendarEvent->title = 'Test Event';

        $responseBody = json_encode(['id' => 1, 'title' => 'Test Event']);
        $response = new Response(200, [], $responseBody);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($response);

        $result = $this->calendarEvent->save();

        $this->assertInstanceOf(CalendarEvent::class, $result);
        $this->assertEquals('Test Event', $result->title);
    }

    /**
     * Test the delete calendar event method
     * @return void
     */
    public function testDeleteCalendarEvent(): void
    {
        $this->calendarEvent->id = 1;

        $response = new Response(200, [], json_encode(['deleted' => true]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('calendar_events/1')
            ->willReturn($response);

        $result = $this->calendarEvent->delete();

        $this->assertInstanceOf(CalendarEvent::class, $result);
    }

    /**
     * Test the reserve calendar event method
     * @return void
     */
    public function testReserveCalendarEvent(): void
    {
        $this->calendarEvent->id = 1;

        $reservationData = new CreateReservationDTO([
            'participantId' => 456,
            'comments' => 'Test reservation'
        ]);

        $response = new Response(200, [], json_encode(['id' => 1, 'reserved' => true]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->stringContains('calendar_events/1/reservations'),
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($response);

        $result = $this->calendarEvent->reserve($reservationData);

        $this->assertInstanceOf(CalendarEvent::class, $result);
        $this->assertTrue($result->reserved);
    }

    /**
     * Test the update series method
     * @return void
     */
    public function testUpdateSeries(): void
    {
        $this->calendarEvent->id = 1;

        $updateData = new UpdateCalendarEventDTO([
            'title' => 'Updated Series Event'
        ]);

        $response = new Response(200, [], json_encode(['id' => 1, 'title' => 'Updated Series Event']));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($response);

        $result = $this->calendarEvent->updateSeries($updateData, 'all');

        $this->assertInstanceOf(CalendarEvent::class, $result);
        $this->assertEquals('Updated Series Event', $result->title);
    }

    /**
     * Test the delete series method
     * @return void
     */
    public function testDeleteSeries(): void
    {
        $this->calendarEvent->id = 1;

        $response = new Response(200, [], json_encode(['deleted' => true]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with(
                $this->stringContains('calendar_events/1'),
                $this->callback(function ($options) {
                    return isset($options['query']['which']) && 
                           $options['query']['which'] === 'following';
                })
            )
            ->willReturn($response);

        $result = $this->calendarEvent->deleteSeries('following');

        $this->assertInstanceOf(CalendarEvent::class, $result);
    }

    /**
     * Test DateTime casting
     * @return void
     */
    public function testDateTimeCasting(): void
    {
        // Test constructor casting
        $event = new CalendarEvent([
            'start_at' => '2024-01-15T10:00:00Z',
            'end_at' => '2024-01-15T11:00:00Z',
        ]);

        $this->assertInstanceOf(DateTime::class, $event->startAt);
        $this->assertInstanceOf(DateTime::class, $event->endAt);
        $this->assertEquals('2024-01-15 10:00:00', $event->startAt->format('Y-m-d H:i:s'));
    }

    /**
     * Test parse context code method
     * @return void
     */
    public function testParseContextCode(): void
    {
        $result = CalendarEvent::parseContextCode('course_123');
        
        $this->assertEquals('course', $result['type']);
        $this->assertEquals('123', $result['id']);

        $result = CalendarEvent::parseContextCode('user_456');
        
        $this->assertEquals('user', $result['type']);
        $this->assertEquals('456', $result['id']);
    }
}