<?php

declare(strict_types=1);

namespace Tests\Dto\ContentMigrations;

use CanvasLMS\Dto\ContentMigrations\UpdateContentMigrationDTO;
use PHPUnit\Framework\TestCase;

class UpdateContentMigrationDTOTest extends TestCase
{
    public function testRetryFileUpload(): void
    {
        $dto = new UpdateContentMigrationDTO();
        $dto->retryFileUpload('newfile.imscc', 54321, 'application/octet-stream');

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'pre_attachment[name]', 'contents' => 'newfile.imscc'], $apiArray);
        $this->assertContains(['name' => 'pre_attachment[size]', 'contents' => '54321'], $apiArray);
        $this->assertContains(['name' => 'pre_attachment[content_type]', 'contents' => 'application/octet-stream'], $apiArray);
    }

    public function testCopyParameters(): void
    {
        $dto = new UpdateContentMigrationDTO([
            'copy' => [
                'assignments' => [
                    'id_123' => '1',
                    'id_456' => '1'
                ],
                'all_quizzes' => '1'
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'copy[assignments][id_123]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'copy[assignments][id_456]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'copy[all_quizzes]', 'contents' => '1'], $apiArray);
    }

    public function testAddCopyItems(): void
    {
        $dto = new UpdateContentMigrationDTO();
        $dto->addCopyItems('assignments', ['123', '456', 789]);

        $copy = $dto->getCopy();
        $this->assertArrayHasKey('assignments', $copy);
        $this->assertEquals('1', $copy['assignments']['123']);
        $this->assertEquals('1', $copy['assignments']['456']);
        $this->assertEquals('1', $copy['assignments']['789']);
    }

    public function testSetCopyProperty(): void
    {
        $dto = new UpdateContentMigrationDTO();
        
        // Test with include = true
        $dto->setCopyProperty('copy[assignments][id_i2102a7fa93b29226774949298626719d]', true);
        $copy = $dto->getCopy();
        $this->assertEquals('1', $copy['assignments']['id_i2102a7fa93b29226774949298626719d']);

        // Test with include = false (should not add)
        $dto->setCopyProperty('copy[quizzes][id_xyz]', false);
        $copy = $dto->getCopy();
        $this->assertArrayNotHasKey('quizzes', $copy);
    }

    public function testSetCopyAll(): void
    {
        $dto = new UpdateContentMigrationDTO();
        $dto->setCopyAll('copy[all_assignments]');
        
        $copy = $dto->getCopy();
        $this->assertEquals('1', $copy['all_assignments']);
    }

    public function testNestedCopyParameters(): void
    {
        $dto = new UpdateContentMigrationDTO([
            'copy' => [
                'context_modules' => [
                    'id_123' => '1',
                    'id_456' => '1'
                ],
                'module_items' => [
                    'id_abc' => [
                        'content' => '1',
                        'settings' => '1'
                    ]
                ]
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'copy[context_modules][id_123]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'copy[module_items][id_abc][content]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'copy[module_items][id_abc][settings]', 'contents' => '1'], $apiArray);
    }

    public function testSettingsUpdate(): void
    {
        $dto = new UpdateContentMigrationDTO([
            'settings' => [
                'overwrite_quizzes' => false,
                'question_bank_name' => 'Updated Bank'
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'settings[overwrite_quizzes]', 'contents' => '0'], $apiArray);
        $this->assertContains(['name' => 'settings[question_bank_name]', 'contents' => 'Updated Bank'], $apiArray);
    }

    public function testDateShiftOptionsUpdate(): void
    {
        $dto = new UpdateContentMigrationDTO([
            'date_shift_options' => [
                'remove_dates' => true,
                'day_substitutions' => [
                    '0' => '1', // Sunday to Monday
                    '6' => '5'  // Saturday to Friday
                ]
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'date_shift_options[remove_dates]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'date_shift_options[day_substitutions][0]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'date_shift_options[day_substitutions][6]', 'contents' => '5'], $apiArray);
    }

    public function testValidation(): void
    {
        // Empty DTO should fail validation
        $dto = new UpdateContentMigrationDTO();
        $this->assertFalse($dto->validate());

        // DTO with at least one field should pass
        $dto = new UpdateContentMigrationDTO(['settings' => ['some_setting' => 'value']]);
        $this->assertTrue($dto->validate());

        $dto = new UpdateContentMigrationDTO();
        $dto->retryFileUpload('file.zip', 12345);
        $this->assertTrue($dto->validate());
    }

    public function testComplexSelectiveImportScenario(): void
    {
        $dto = new UpdateContentMigrationDTO();

        // Simulate processing selective data response
        $dto->setCopyProperty('copy[all_course_settings]', true);
        $dto->setCopyProperty('copy[assignments][id_123]', true);
        $dto->setCopyProperty('copy[assignments][id_456]', true);
        $dto->setCopyProperty('copy[quizzes][id_789]', false); // Should not be included
        $dto->addCopyItems('discussion_topics', ['topic_1', 'topic_2']);

        $apiArray = $dto->toApiArray();

        // Check all expected items are present
        $this->assertContains(['name' => 'copy[all_course_settings]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'copy[assignments][id_123]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'copy[assignments][id_456]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'copy[discussion_topics][topic_1]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'copy[discussion_topics][topic_2]', 'contents' => '1'], $apiArray);

        // Ensure excluded item is not present
        $quizFound = false;
        foreach ($apiArray as $item) {
            if ($item['name'] === 'copy[quizzes][id_789]') {
                $quizFound = true;
                break;
            }
        }
        $this->assertFalse($quizFound);
    }
}