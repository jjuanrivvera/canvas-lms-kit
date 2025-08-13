<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Conferences;

use CanvasLMS\Dto\Conferences\UpdateConferenceDTO;
use PHPUnit\Framework\TestCase;

class UpdateConferenceDTOTest extends TestCase
{
    public function testConstructorInitializesProperties(): void
    {
        $data = [
            'title' => 'Updated Conference',
            'conference_type' => 'Zoom',
            'description' => 'Updated description',
            'duration' => 120,
            'settings' => [
                'enable_recording' => false,
                'enable_chat' => true
            ],
            'long_running' => true,
            'users' => [5, 6, 7],
            'has_advanced_settings' => false
        ];

        $dto = new UpdateConferenceDTO($data);

        $this->assertEquals('Updated Conference', $dto->title);
        $this->assertEquals('Zoom', $dto->conference_type);
        $this->assertEquals('Updated description', $dto->description);
        $this->assertEquals(120, $dto->duration);
        $this->assertIsArray($dto->settings);
        $this->assertFalse($dto->settings['enable_recording']);
        $this->assertTrue($dto->settings['enable_chat']);
        $this->assertTrue($dto->long_running);
        $this->assertEquals([5, 6, 7], $dto->users);
        $this->assertFalse($dto->has_advanced_settings);
    }

    public function testToApiArrayWithPartialUpdate(): void
    {
        $dto = new UpdateConferenceDTO([
            'title' => 'New Title',
            'duration' => 45
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(2, $apiArray);

        $titleField = $this->findFieldInApiArray($apiArray, 'web_conference[title]');
        $this->assertNotNull($titleField);
        $this->assertEquals('New Title', $titleField['contents']);

        $durationField = $this->findFieldInApiArray($apiArray, 'web_conference[duration]');
        $this->assertNotNull($durationField);
        $this->assertEquals('45', $durationField['contents']);
    }

    public function testToApiArrayWithAllFields(): void
    {
        $dto = new UpdateConferenceDTO([
            'title' => 'Complete Update',
            'conference_type' => 'BigBlueButton',
            'description' => 'Fully updated conference',
            'duration' => 180,
            'settings' => [
                'enable_waiting_room' => true,
                'allow_guests' => false
            ],
            'long_running' => false,
            'users' => [100, 200],
            'has_advanced_settings' => true
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertGreaterThan(7, count($apiArray));

        $titleField = $this->findFieldInApiArray($apiArray, 'web_conference[title]');
        $this->assertEquals('Complete Update', $titleField['contents']);

        $typeField = $this->findFieldInApiArray($apiArray, 'web_conference[conference_type]');
        $this->assertEquals('BigBlueButton', $typeField['contents']);

        $descField = $this->findFieldInApiArray($apiArray, 'web_conference[description]');
        $this->assertEquals('Fully updated conference', $descField['contents']);

        $durationField = $this->findFieldInApiArray($apiArray, 'web_conference[duration]');
        $this->assertEquals('180', $durationField['contents']);

        $longRunningField = $this->findFieldInApiArray($apiArray, 'web_conference[long_running]');
        $this->assertEquals('0', $longRunningField['contents']);

        $advancedField = $this->findFieldInApiArray($apiArray, 'web_conference[has_advanced_settings]');
        $this->assertEquals('1', $advancedField['contents']);

        $waitingRoomField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][enable_waiting_room]');
        $this->assertEquals('1', $waitingRoomField['contents']);

        $guestsField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][allow_guests]');
        $this->assertEquals('0', $guestsField['contents']);

        $userFields = $this->findAllFieldsInApiArray($apiArray, 'web_conference[users][]');
        $this->assertCount(2, $userFields);
    }

    public function testEmptyDTOCreatesEmptyApiArray(): void
    {
        $dto = new UpdateConferenceDTO([]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(0, $apiArray);
    }

    public function testNullFieldsNotIncludedInApiArray(): void
    {
        $dto = new UpdateConferenceDTO([
            'title' => 'Only Title',
            'description' => null,
            'duration' => null,
            'settings' => null,
            'users' => null
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertNotNull($this->findFieldInApiArray($apiArray, 'web_conference[title]'));
        $this->assertNull($this->findFieldInApiArray($apiArray, 'web_conference[description]'));
        $this->assertNull($this->findFieldInApiArray($apiArray, 'web_conference[duration]'));
    }

    public function testBooleanFieldsConversion(): void
    {
        $dto = new UpdateConferenceDTO([
            'title' => 'Boolean Test',
            'long_running' => true,
            'has_advanced_settings' => false,
            'settings' => [
                'enabled' => true,
                'disabled' => false
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $longRunningField = $this->findFieldInApiArray($apiArray, 'web_conference[long_running]');
        $this->assertEquals('1', $longRunningField['contents']);

        $advancedField = $this->findFieldInApiArray($apiArray, 'web_conference[has_advanced_settings]');
        $this->assertEquals('0', $advancedField['contents']);

        $enabledField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][enabled]');
        $this->assertEquals('1', $enabledField['contents']);

        $disabledField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][disabled]');
        $this->assertEquals('0', $disabledField['contents']);
    }

    public function testSettingsWithComplexValues(): void
    {
        $dto = new UpdateConferenceDTO([
            'title' => 'Complex Settings',
            'settings' => [
                'max_duration' => 240,
                'conference_code' => 'CONF-2024',
                'auto_start' => true,
                'participant_limit' => 500
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $maxDurationField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][max_duration]');
        $this->assertEquals('240', $maxDurationField['contents']);

        $codeField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][conference_code]');
        $this->assertEquals('CONF-2024', $codeField['contents']);

        $autoStartField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][auto_start]');
        $this->assertEquals('1', $autoStartField['contents']);

        $limitField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][participant_limit]');
        $this->assertEquals('500', $limitField['contents']);
    }

    public function testUsersArrayHandling(): void
    {
        $dto = new UpdateConferenceDTO([
            'title' => 'Users Update',
            'users' => [111, 222, 333, 444]
        ]);

        $apiArray = $dto->toApiArray();

        $userFields = $this->findAllFieldsInApiArray($apiArray, 'web_conference[users][]');
        $this->assertCount(4, $userFields);

        $userValues = array_column($userFields, 'contents');
        $this->assertContains('111', $userValues);
        $this->assertContains('222', $userValues);
        $this->assertContains('333', $userValues);
        $this->assertContains('444', $userValues);
    }

    public function testUpdateOnlySettings(): void
    {
        $dto = new UpdateConferenceDTO([
            'settings' => [
                'new_setting' => 'value',
                'another_setting' => 123
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertCount(2, $apiArray);

        $newSettingField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][new_setting]');
        $this->assertEquals('value', $newSettingField['contents']);

        $anotherSettingField = $this->findFieldInApiArray($apiArray, 'web_conference[settings][another_setting]');
        $this->assertEquals('123', $anotherSettingField['contents']);
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