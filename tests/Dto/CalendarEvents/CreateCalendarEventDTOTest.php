<?php

declare(strict_types=1);

namespace Tests\Dto\CalendarEvents;

use CanvasLMS\Dto\CalendarEvents\CreateCalendarEventDTO;
use DateTime;
use PHPUnit\Framework\TestCase;

class CreateCalendarEventDTOTest extends TestCase
{
    /**
     * Test basic DTO creation and array conversion
     *
     * @return void
     */
    public function testBasicDtoCreation(): void
    {
        $data = [
            'contextCode' => 'course_123',
            'title' => 'Test Event',
            'description' => 'Test Description',
            'startAt' => new DateTime('2024-01-15T10:00:00Z'),
            'endAt' => new DateTime('2024-01-15T11:00:00Z'),
            'locationName' => 'Room 101',
            'locationAddress' => '123 Main St',
        ];

        $dto = new CreateCalendarEventDTO($data);
        $apiArray = $dto->toApiArray();

        // Check the array has the right structure
        $this->assertIsArray($apiArray);

        // Find and check specific values
        $this->assertContains(['name' => 'calendar_event[context_code]', 'contents' => 'course_123'], $apiArray);
        $this->assertContains(['name' => 'calendar_event[title]', 'contents' => 'Test Event'], $apiArray);
        $this->assertContains(['name' => 'calendar_event[description]', 'contents' => 'Test Description'], $apiArray);
        $this->assertContains(['name' => 'calendar_event[location_name]', 'contents' => 'Room 101'], $apiArray);
        $this->assertContains(['name' => 'calendar_event[location_address]', 'contents' => '123 Main St'], $apiArray);

        // Check DateTime formatting
        $this->assertContains(['name' => 'calendar_event[start_at]', 'contents' => '2024-01-15T10:00:00+00:00'], $apiArray);
        $this->assertContains(['name' => 'calendar_event[end_at]', 'contents' => '2024-01-15T11:00:00+00:00'], $apiArray);
    }

    /**
     * Test boolean conversion
     *
     * @return void
     */
    public function testBooleanConversion(): void
    {
        $dto = new CreateCalendarEventDTO([
            'contextCode' => 'course_123',
            'allDay' => true,
            'blackoutDate' => false,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'calendar_event[all_day]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'calendar_event[blackout_date]', 'contents' => '0'], $apiArray);
    }

    /**
     * Test child event data formatting
     *
     * @return void
     */
    public function testChildEventData(): void
    {
        $dto = new CreateCalendarEventDTO([
            'contextCode' => 'course_123',
            'childEventData' => [
                'section_1' => [
                    'start_at' => new DateTime('2024-01-15T10:00:00Z'),
                    'end_at' => new DateTime('2024-01-15T11:00:00Z'),
                    'context_code' => 'course_section_456',
                ],
                'section_2' => [
                    'start_at' => new DateTime('2024-01-15T14:00:00Z'),
                    'end_at' => new DateTime('2024-01-15T15:00:00Z'),
                ],
            ],
        ]);

        $apiArray = $dto->toApiArray();

        // Check first section data
        $this->assertContains(
            ['name' => 'calendar_event[child_event_data][section_1][start_at]', 'contents' => '2024-01-15T10:00:00+00:00'],
            $apiArray
        );
        $this->assertContains(
            ['name' => 'calendar_event[child_event_data][section_1][end_at]', 'contents' => '2024-01-15T11:00:00+00:00'],
            $apiArray
        );
        $this->assertContains(
            ['name' => 'calendar_event[child_event_data][section_1][context_code]', 'contents' => 'course_section_456'],
            $apiArray
        );

        // Check second section data
        $this->assertContains(
            ['name' => 'calendar_event[child_event_data][section_2][start_at]', 'contents' => '2024-01-15T14:00:00+00:00'],
            $apiArray
        );
    }

    /**
     * Test duplicate options
     *
     * @return void
     */
    public function testDuplicateOptions(): void
    {
        $dto = new CreateCalendarEventDTO([
            'contextCode' => 'course_123',
            'duplicate' => [
                'count' => 3,
                'interval' => 7,
                'frequency' => 'weekly',
                'append_iterator' => true,
            ],
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'calendar_event[duplicate][count]', 'contents' => '3'], $apiArray);
        $this->assertContains(['name' => 'calendar_event[duplicate][interval]', 'contents' => '7'], $apiArray);
        $this->assertContains(['name' => 'calendar_event[duplicate][frequency]', 'contents' => 'weekly'], $apiArray);
        $this->assertContains(['name' => 'calendar_event[duplicate][append_iterator]', 'contents' => '1'], $apiArray);
    }

    /**
     * Test RRULE support
     *
     * @return void
     */
    public function testRruleSupport(): void
    {
        $dto = new CreateCalendarEventDTO([
            'contextCode' => 'course_123',
            'rrule' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;UNTIL=20240315T000000Z',
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(
            ['name' => 'calendar_event[rrule]', 'contents' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;UNTIL=20240315T000000Z'],
            $apiArray
        );
    }

    /**
     * Test required fields validation
     *
     * @return void
     */
    public function testRequiredFields(): void
    {
        // Context code is required
        $dto = new CreateCalendarEventDTO([
            'contextCode' => 'user_456',
        ]);

        $apiArray = $dto->toApiArray();

        // Should at minimum have context code
        $this->assertNotEmpty($apiArray);
        $this->assertContains(['name' => 'calendar_event[context_code]', 'contents' => 'user_456'], $apiArray);
    }
}
