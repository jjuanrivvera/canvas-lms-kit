<?php

declare(strict_types=1);

namespace Tests\Api\CalendarEvents;

use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Dto\CalendarEvents\CreateCalendarEventDTO;
use CanvasLMS\Dto\CalendarEvents\CreateReservationDTO;
use CanvasLMS\Dto\CalendarEvents\UpdateCalendarEventDTO;
use CanvasLMS\Http\HttpClient;
use DateTime;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

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
     *
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
                ],
            ],
        ];
    }

    /**
     * Test the create calendar event method
     *
     * @dataProvider calendarEventDataProvider
     *
     * @param array $eventData
     * @param array $expectedResult
     *
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
     *
     * @dataProvider calendarEventDataProvider
     *
     * @param array $eventData
     * @param array $expectedResult
     *
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
     *
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
     *
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
     *
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
     *
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
     *
     * @return void
     */
    public function testReserveCalendarEvent(): void
    {
        $this->calendarEvent->id = 1;

        $reservationData = new CreateReservationDTO([
            'participantId' => 456,
            'comments' => 'Test reservation',
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
     *
     * @return void
     */
    public function testUpdateSeries(): void
    {
        $this->calendarEvent->id = 1;

        $updateData = new UpdateCalendarEventDTO([
            'title' => 'Updated Series Event',
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
     *
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
     *
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
     *
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

    /**
     * Test the update calendar event method uses multipart format
     *
     * @return void
     */
    public function testUpdateCalendarEventUsesMultipartFormat(): void
    {
        $updateData = [
            'title' => 'Updated Event',
            'description' => 'Updated Description',
        ];

        $response = new Response(200, [], json_encode(['id' => 123, 'title' => 'Updated Event']));

        $dto = new UpdateCalendarEventDTO($updateData);
        $expectedPayload = $dto->toApiArray();

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                $this->equalTo('calendar_events/123'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return isset($subject['multipart']) && $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        $event = CalendarEvent::update(123, $dto);

        $this->assertEquals('Updated Event', $event->title);
    }

    /**
     * Test the saveEnabledAccountCalendars method uses multipart format
     *
     * @return void
     */
    public function testSaveEnabledAccountCalendarsUsesMultipartFormat(): void
    {
        $accountIds = [1, 2, 3];
        $expectedResult = ['status' => 'success'];
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('calendar_events/save_enabled_account_calendars'),
                $this->callback(function ($subject) use ($accountIds) {
                    // Check that multipart format is used
                    if (!isset($subject['multipart'])) {
                        return false;
                    }

                    $multipart = $subject['multipart'];

                    // Check account IDs are properly formatted
                    $accountIdCount = 0;
                    foreach ($multipart as $part) {
                        if (strpos($part['name'], 'enabled_account_calendars[') === 0) {
                            $accountIdCount++;
                        }
                    }

                    return $accountIdCount === count($accountIds);
                })
            )
            ->willReturn($response);

        $result = CalendarEvent::saveEnabledAccountCalendars($accountIds);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test the saveEnabledAccountCalendars with markAsSeen option
     *
     * @return void
     */
    public function testSaveEnabledAccountCalendarsWithMarkAsSeen(): void
    {
        $accountIds = [1, 2];
        $expectedResult = ['status' => 'success'];
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('calendar_events/save_enabled_account_calendars'),
                $this->callback(function ($subject) {
                    // Check that multipart format is used
                    if (!isset($subject['multipart'])) {
                        return false;
                    }

                    $multipart = $subject['multipart'];

                    // Check mark_feature_as_seen is included
                    $hasMarkAsSeen = false;
                    foreach ($multipart as $part) {
                        if ($part['name'] === 'mark_feature_as_seen' && $part['contents'] === '1') {
                            $hasMarkAsSeen = true;
                            break;
                        }
                    }

                    return $hasMarkAsSeen;
                })
            )
            ->willReturn($response);

        $result = CalendarEvent::saveEnabledAccountCalendars($accountIds, true);

        $this->assertEquals($expectedResult, $result);
    }
}
