<?php

declare(strict_types=1);

namespace Tests\Dto\AppointmentGroups;

use CanvasLMS\Dto\AppointmentGroups\CreateAppointmentGroupDTO;
use PHPUnit\Framework\TestCase;

class CreateAppointmentGroupDTOTest extends TestCase
{
    /**
     * Test basic DTO creation and array conversion
     *
     * @return void
     */
    public function testBasicDtoCreation(): void
    {
        $data = [
            'contextCodes' => ['course_123', 'course_456'],
            'title' => 'Office Hours',
            'description' => 'Weekly office hours',
            'locationName' => 'Room 101',
            'locationAddress' => '123 Main St',
            'participantsPerAppointment' => 1,
        ];

        $dto = new CreateAppointmentGroupDTO($data);
        $apiArray = $dto->toApiArray();

        // Check the array has the right structure
        $this->assertIsArray($apiArray);

        // Check context codes
        $this->assertContains(['name' => 'appointment_group[context_codes][0]', 'contents' => 'course_123'], $apiArray);
        $this->assertContains(['name' => 'appointment_group[context_codes][1]', 'contents' => 'course_456'], $apiArray);

        // Check other fields
        $this->assertContains(['name' => 'appointment_group[title]', 'contents' => 'Office Hours'], $apiArray);
        $this->assertContains(['name' => 'appointment_group[description]', 'contents' => 'Weekly office hours'], $apiArray);
        $this->assertContains(['name' => 'appointment_group[location_name]', 'contents' => 'Room 101'], $apiArray);
        $this->assertContains(['name' => 'appointment_group[location_address]', 'contents' => '123 Main St'], $apiArray);
        $this->assertContains(['name' => 'appointment_group[participants_per_appointment]', 'contents' => '1'], $apiArray);
    }

    /**
     * Test boolean conversion
     *
     * @return void
     */
    public function testBooleanConversion(): void
    {
        $dto = new CreateAppointmentGroupDTO([
            'contextCodes' => ['course_123'],
            'publish' => true,
            'allowObserverSignup' => false,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'appointment_group[publish]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'appointment_group[allow_observer_signup]', 'contents' => '0'], $apiArray);
    }

    /**
     * Test new appointments array formatting
     *
     * @return void
     */
    public function testNewAppointmentsFormatting(): void
    {
        $dto = new CreateAppointmentGroupDTO([
            'contextCodes' => ['course_123'],
            'newAppointments' => [
                ['start_at' => '2024-01-15T10:00:00Z', 'end_at' => '2024-01-15T11:00:00Z'],
                ['start_at' => '2024-01-16T10:00:00Z', 'end_at' => '2024-01-16T11:00:00Z'],
            ],
        ]);

        $apiArray = $dto->toApiArray();

        // Check first appointment
        $this->assertContains(
            ['name' => 'appointment_group[new_appointments][0][0]', 'contents' => '2024-01-15T10:00:00Z'],
            $apiArray
        );
        $this->assertContains(
            ['name' => 'appointment_group[new_appointments][0][1]', 'contents' => '2024-01-15T11:00:00Z'],
            $apiArray
        );

        // Check second appointment
        $this->assertContains(
            ['name' => 'appointment_group[new_appointments][1][0]', 'contents' => '2024-01-16T10:00:00Z'],
            $apiArray
        );
        $this->assertContains(
            ['name' => 'appointment_group[new_appointments][1][1]', 'contents' => '2024-01-16T11:00:00Z'],
            $apiArray
        );
    }

    /**
     * Test sub context codes
     *
     * @return void
     */
    public function testSubContextCodes(): void
    {
        $dto = new CreateAppointmentGroupDTO([
            'contextCodes' => ['course_123'],
            'subContextCodes' => ['course_section_456', 'course_section_789'],
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'appointment_group[sub_context_codes][0]', 'contents' => 'course_section_456'], $apiArray);
        $this->assertContains(['name' => 'appointment_group[sub_context_codes][1]', 'contents' => 'course_section_789'], $apiArray);
    }

    /**
     * Test participant limits
     *
     * @return void
     */
    public function testParticipantLimits(): void
    {
        $dto = new CreateAppointmentGroupDTO([
            'contextCodes' => ['course_123'],
            'minAppointmentsPerParticipant' => 1,
            'maxAppointmentsPerParticipant' => 3,
            'participantsPerAppointment' => 2,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'appointment_group[min_appointments_per_participant]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'appointment_group[max_appointments_per_participant]', 'contents' => '3'], $apiArray);
        $this->assertContains(['name' => 'appointment_group[participants_per_appointment]', 'contents' => '2'], $apiArray);
    }

    /**
     * Test participant visibility
     *
     * @return void
     */
    public function testParticipantVisibility(): void
    {
        $dto = new CreateAppointmentGroupDTO([
            'contextCodes' => ['course_123'],
            'participantVisibility' => 'protected',
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'appointment_group[participant_visibility]', 'contents' => 'protected'], $apiArray);
    }

    /**
     * Test required fields
     *
     * @return void
     */
    public function testRequiredFields(): void
    {
        // Only context codes is required
        $dto = new CreateAppointmentGroupDTO([
            'contextCodes' => ['course_123'],
        ]);

        $apiArray = $dto->toApiArray();

        // Should at minimum have context codes
        $this->assertNotEmpty($apiArray);
        $this->assertContains(['name' => 'appointment_group[context_codes][0]', 'contents' => 'course_123'], $apiArray);
    }
}
