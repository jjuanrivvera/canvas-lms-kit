<?php

namespace Tests\Api\AppointmentGroups;

use DateTime;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\AppointmentGroups\AppointmentGroup;
use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Dto\AppointmentGroups\CreateAppointmentGroupDTO;
use CanvasLMS\Dto\AppointmentGroups\UpdateAppointmentGroupDTO;

class AppointmentGroupTest extends TestCase
{
    /**
     * @var AppointmentGroup
     */
    private $appointmentGroup;

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
        AppointmentGroup::setApiClient($this->httpClientMock);
        CalendarEvent::setApiClient($this->httpClientMock);
        
        $this->appointmentGroup = new AppointmentGroup([]);
    }

    /**
     * Appointment group data provider
     * @return array
     */
    public static function appointmentGroupDataProvider(): array
    {
        return [
            [
                [
                    'contextCodes' => ['course_123'],
                    'title' => 'Office Hours',
                    'description' => 'Weekly office hours',
                    'locationName' => 'Room 101',
                    'participantsPerAppointment' => 1,
                    'newAppointments' => [
                        ['start_at' => '2024-01-15T10:00:00Z', 'end_at' => '2024-01-15T11:00:00Z'],
                        ['start_at' => '2024-01-16T10:00:00Z', 'end_at' => '2024-01-16T11:00:00Z']
                    ]
                ],
                [
                    'id' => 1,
                    'title' => 'Office Hours',
                    'description' => 'Weekly office hours',
                    'location_name' => 'Room 101',
                    'context_codes' => ['course_123'],
                    'participants_per_appointment' => 1,
                    'appointments' => [
                        ['id' => 10, 'start_at' => '2024-01-15T10:00:00Z', 'end_at' => '2024-01-15T11:00:00Z'],
                        ['id' => 11, 'start_at' => '2024-01-16T10:00:00Z', 'end_at' => '2024-01-16T11:00:00Z']
                    ]
                ]
            ],
        ];
    }

    /**
     * Test the create appointment group method
     * @dataProvider appointmentGroupDataProvider
     * @param array $groupData
     * @param array $expectedResult
     * @return void
     */
    public function testCreateAppointmentGroup(array $groupData, array $expectedResult): void
    {
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->method('post')
            ->willReturn($response);

        $dto = new CreateAppointmentGroupDTO($groupData);
        $group = AppointmentGroup::create($dto);

        $this->assertInstanceOf(AppointmentGroup::class, $group);
        $this->assertEquals('Office Hours', $group->title);
    }

    /**
     * Test the create appointment group method with DTO
     * @dataProvider appointmentGroupDataProvider
     * @param array $groupData
     * @param array $expectedResult
     * @return void
     */
    public function testCreateAppointmentGroupWithDto(array $groupData, array $expectedResult): void
    {
        $dto = new CreateAppointmentGroupDTO($groupData);
        $expectedPayload = $dto->toApiArray();

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('appointment_groups'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        $group = AppointmentGroup::create($dto);

        $this->assertInstanceOf(AppointmentGroup::class, $group);
        $this->assertEquals('Office Hours', $group->title);
    }

    /**
     * Test the find appointment group method
     * @return void
     */
    public function testFindAppointmentGroup(): void
    {
        $response = new Response(200, [], json_encode(['id' => 123, 'title' => 'Found Group']));

        $this->httpClientMock
            ->method('get')
            ->willReturn($response);

        $group = AppointmentGroup::find(123);

        $this->assertInstanceOf(AppointmentGroup::class, $group);
        $this->assertEquals(123, $group->id);
        $this->assertEquals('Found Group', $group->title);
    }

    /**
     * Test the update appointment group method
     * @return void
     */
    public function testUpdateAppointmentGroup(): void
    {
        $updateData = [
            'title' => 'Updated Office Hours',
        ];

        $response = new Response(200, [], json_encode(['id' => 1, 'title' => 'Updated Office Hours']));

        $this->httpClientMock
            ->method('put')
            ->willReturn($response);

        $dto = new UpdateAppointmentGroupDTO($updateData);
        $group = AppointmentGroup::update(1, $dto);

        $this->assertEquals('Updated Office Hours', $group->title);
    }

    /**
     * Test the save appointment group method
     * @return void
     */
    public function testSaveAppointmentGroup(): void
    {
        $this->appointmentGroup->id = 1;
        $this->appointmentGroup->title = 'Test Group';

        $responseBody = json_encode(['id' => 1, 'title' => 'Test Group']);
        $response = new Response(200, [], $responseBody);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                $this->stringContains('appointment_groups/1'),
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($response);

        $result = $this->appointmentGroup->save();

        $this->assertInstanceOf(AppointmentGroup::class, $result);
        $this->assertEquals('Test Group', $result->title);
    }

    /**
     * Test the delete appointment group method
     * @return void
     */
    public function testDeleteAppointmentGroup(): void
    {
        $this->appointmentGroup->id = 1;

        $response = new Response(200, [], json_encode(['deleted' => true]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('appointment_groups/1')
            ->willReturn($response);

        $result = $this->appointmentGroup->delete();

        $this->assertInstanceOf(AppointmentGroup::class, $result);
    }

    /**
     * Test the publish method
     * @return void
     */
    public function testPublishAppointmentGroup(): void
    {
        $this->appointmentGroup->id = 1;
        $this->appointmentGroup->workflowState = 'pending';

        $response = new Response(200, [], json_encode(['id' => 1, 'workflow_state' => 'active']));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                $this->stringContains('appointment_groups/1'),
                $this->callback(function ($options) {
                    // Check that publish is set to true
                    return isset($options['multipart']) && 
                           in_array(['name' => 'appointment_group[publish]', 'contents' => '1'], $options['multipart']);
                })
            )
            ->willReturn($response);

        $result = $this->appointmentGroup->publish();

        $this->assertInstanceOf(AppointmentGroup::class, $result);
        $this->assertEquals('active', $result->workflowState);
    }

    /**
     * Test list users method
     * @return void
     */
    public function testListUsers(): void
    {
        $this->appointmentGroup->id = 1;

        $expectedUsers = [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2']
        ];

        $response = new Response(200, [], json_encode($expectedUsers));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('appointment_groups/1/users')
            ->willReturn($response);

        $users = $this->appointmentGroup->listUsers();

        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        $this->assertEquals('User 1', $users[0]['name']);
    }

    /**
     * Test get calendar events method
     * @return void
     */
    public function testGetCalendarEvents(): void
    {
        $this->appointmentGroup->id = 1;
        $this->appointmentGroup->appointments = [
            ['id' => 10, 'start_at' => '2024-01-15T10:00:00Z', 'end_at' => '2024-01-15T11:00:00Z', 'title' => 'Slot 1'],
            ['id' => 11, 'start_at' => '2024-01-16T10:00:00Z', 'end_at' => '2024-01-16T11:00:00Z', 'title' => 'Slot 2']
        ];

        // Test default behavior (no API calls)
        $events = $this->appointmentGroup->getCalendarEvents();

        $this->assertIsArray($events);
        $this->assertCount(2, $events);
        $this->assertInstanceOf(CalendarEvent::class, $events[0]);
        $this->assertEquals(10, $events[0]->id);
        $this->assertEquals('Slot 1', $events[0]->title);
        
        // Test fresh fetch behavior
        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], json_encode(['id' => 10, 'title' => 'Slot 1 Updated', 'start_at' => '2024-01-15T10:00:00Z', 'end_at' => '2024-01-15T11:00:00Z'])),
                new Response(200, [], json_encode(['id' => 11, 'title' => 'Slot 2 Updated', 'start_at' => '2024-01-16T10:00:00Z', 'end_at' => '2024-01-16T11:00:00Z']))
            );
        
        $freshEvents = $this->appointmentGroup->getCalendarEvents(true);
        
        $this->assertIsArray($freshEvents);
        $this->assertCount(2, $freshEvents);
        $this->assertEquals('Slot 1 Updated', $freshEvents[0]->title);
    }

    /**
     * Test DateTime casting
     * @return void
     */
    public function testDateTimeCasting(): void
    {
        $group = new AppointmentGroup([
            'start_at' => '2024-01-15T10:00:00Z',
            'end_at' => '2024-01-16T11:00:00Z',
        ]);

        $this->assertInstanceOf(DateTime::class, $group->startAt);
        $this->assertInstanceOf(DateTime::class, $group->endAt);
        $this->assertEquals('2024-01-15 10:00:00', $group->startAt->format('Y-m-d H:i:s'));
    }
}