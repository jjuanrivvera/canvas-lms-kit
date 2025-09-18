<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Conferences;

use CanvasLMS\Dto\Conferences\CreateConferenceDTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CreateConferenceDTOTest extends TestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $data = [
            'title' => 'Test Conference',
            'conference_type' => 'BigBlueButton',
            'description' => 'Test description',
            'duration' => 60,
            'settings' => [
                'enable_recording' => true,
                'mute_on_join' => false,
            ],
            'long_running' => false,
            'users' => [1, 2, 3],
            'has_advanced_settings' => true,
        ];

        $dto = new CreateConferenceDTO($data);

        $this->assertEquals('Test Conference', $dto->title);
        $this->assertEquals('BigBlueButton', $dto->conference_type);
        $this->assertEquals('Test description', $dto->description);
        $this->assertEquals(60, $dto->duration);
        $this->assertIsArray($dto->settings);
        $this->assertTrue($dto->settings['enable_recording']);
        $this->assertFalse($dto->settings['mute_on_join']);
        $this->assertFalse($dto->long_running);
        $this->assertEquals([1, 2, 3], $dto->users);
        $this->assertTrue($dto->has_advanced_settings);
    }

    public function testToApiArrayWithRequiredFields(): void
    {
        $dto = new CreateConferenceDTO([
            'title' => 'Basic Conference',
            'conference_type' => 'Zoom',
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);

        $titleField = $this->findFieldInApiArray($apiArray, 'web_conference[title]');
        $this->assertNotNull($titleField);
        $this->assertEquals('Basic Conference', $titleField['contents']);

        $typeField = $this->findFieldInApiArray($apiArray, 'web_conference[conference_type]');
        $this->assertNotNull($typeField);
        $this->assertEquals('Zoom', $typeField['contents']);
    }

    public function testToApiArrayWithAllFields(): void
    {
        $dto = new CreateConferenceDTO([
            'title' => 'Full Conference',
            'conference_type' => 'BigBlueButton',
            'description' => 'Complete conference with all fields',
            'duration' => 90,
            'settings' => [
                'enable_recording' => true,
                'enable_waiting_room' => false,
                'mute_on_join' => true,
            ],
            'long_running' => true,
            'users' => [10, 20, 30],
            'has_advanced_settings' => false,
        ]);

        $apiArray = $dto->toApiArray();

        $descField = $this->findFieldInApiArray($apiArray, 'web_conference[description]');
        $this->assertEquals('Complete conference with all fields', $descField['contents']);

        $durationField = $this->findFieldInApiArray($apiArray, 'web_conference[duration]');
        $this->assertEquals('90', $durationField['contents']);

        $longRunningField = $this->findFieldInApiArray($apiArray, 'web_conference[long_running]');
        $this->assertEquals('1', $longRunningField['contents']);

        $advancedField = $this->findFieldInApiArray($apiArray, 'web_conference[has_advanced_settings]');
        $this->assertEquals('0', $advancedField['contents']);

        $recordingField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][enable_recording]');
        $this->assertEquals('1', $recordingField['contents']);

        $waitingRoomField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][enable_waiting_room]');
        $this->assertEquals('0', $waitingRoomField['contents']);

        $muteField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][mute_on_join]');
        $this->assertEquals('1', $muteField['contents']);

        $userFields = $this->findAllFieldsInApiArray($apiArray, 'web_conference[users][]');
        $this->assertCount(3, $userFields);
        $userValues = array_column($userFields, 'contents');
        $this->assertContains('10', $userValues);
        $this->assertContains('20', $userValues);
        $this->assertContains('30', $userValues);
    }

    public function testToApiArrayWithoutTitleThrowsException(): void
    {
        $dto = new CreateConferenceDTO([
            'conference_type' => 'Zoom',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conference title is required');

        $dto->toApiArray();
    }

    public function testToApiArrayWithoutConferenceTypeThrowsException(): void
    {
        $dto = new CreateConferenceDTO([
            'title' => 'Test Conference',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conference type is required');

        $dto->toApiArray();
    }

    public function testSettingsWithMixedTypes(): void
    {
        $dto = new CreateConferenceDTO([
            'title' => 'Mixed Settings Conference',
            'conference_type' => 'Zoom',
            'settings' => [
                'enable_recording' => true,
                'max_participants' => 100,
                'meeting_id' => 'ABC-123-XYZ',
                'auto_recording' => 'cloud',
                'registration_required' => false,
            ],
        ]);

        $apiArray = $dto->toApiArray();

        $recordingField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][enable_recording]');
        $this->assertEquals('1', $recordingField['contents']);

        $maxParticipantsField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][max_participants]');
        $this->assertEquals('100', $maxParticipantsField['contents']);

        $meetingIdField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][meeting_id]');
        $this->assertEquals('ABC-123-XYZ', $meetingIdField['contents']);

        $autoRecordingField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][auto_recording]');
        $this->assertEquals('cloud', $autoRecordingField['contents']);

        $registrationField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][registration_required]');
        $this->assertEquals('0', $registrationField['contents']);
    }

    public function testEmptyUsersArray(): void
    {
        $dto = new CreateConferenceDTO([
            'title' => 'No Users Conference',
            'conference_type' => 'BigBlueButton',
            'users' => [],
        ]);

        $apiArray = $dto->toApiArray();

        $userFields = $this->findAllFieldsInApiArray($apiArray, 'web_conference[users][]');
        $this->assertCount(0, $userFields);
    }

    public function testNullFieldsNotIncludedInApiArray(): void
    {
        $dto = new CreateConferenceDTO([
            'title' => 'Minimal Conference',
            'conference_type' => 'Zoom',
            'description' => null,
            'duration' => null,
            'settings' => null,
            'users' => null,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertNotNull($this->findFieldInApiArray($apiArray, 'web_conference[title]'));
        $this->assertNotNull($this->findFieldInApiArray($apiArray, 'web_conference[conference_type]'));
        $this->assertNull($this->findFieldInApiArray($apiArray, 'web_conference[description]'));
        $this->assertNull($this->findFieldInApiArray($apiArray, 'web_conference[duration]'));
        $this->assertNull($this->findFieldInApiArray($apiArray, 'web_conference[settings]'));
        $this->assertEmpty($this->findAllFieldsInApiArray($apiArray, 'web_conference[users][]'));
    }

    private function findFieldInApiArray(array $apiArray, string $fieldName): ?array
    {
        foreach ($apiArray as $field) {
            if ($field['name'] === $fieldName) {
                return $field;
            }
        }

        return null;
    }

    private function findAllFieldsInApiArray(array $apiArray, string $fieldName): array
    {
        $fields = [];
        foreach ($apiArray as $field) {
            if ($field['name'] === $fieldName) {
                $fields[] = $field;
            }
        }

        return $fields;
    }
}
