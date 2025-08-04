<?php

declare(strict_types=1);

namespace Tests\Dto\ContentMigrations;

use CanvasLMS\Dto\ContentMigrations\CreateContentMigrationDTO;
use PHPUnit\Framework\TestCase;

class CreateContentMigrationDTOTest extends TestCase
{
    public function testBasicMigration(): void
    {
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'course_copy_importer',
            'settings' => [
                'source_course_id' => 123
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'migration_type', 'contents' => 'course_copy_importer'], $apiArray);
        $this->assertContains(['name' => 'settings[source_course_id]', 'contents' => '123'], $apiArray);
    }

    public function testFileUploadMigration(): void
    {
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'common_cartridge_importer',
            'pre_attachment' => [
                'name' => 'course.imscc',
                'size' => 12345,
                'content_type' => 'application/zip'
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'migration_type', 'contents' => 'common_cartridge_importer'], $apiArray);
        $this->assertContains(['name' => 'pre_attachment[name]', 'contents' => 'course.imscc'], $apiArray);
        $this->assertContains(['name' => 'pre_attachment[size]', 'contents' => '12345'], $apiArray);
        $this->assertContains(['name' => 'pre_attachment[content_type]', 'contents' => 'application/zip'], $apiArray);
    }

    public function testDateShiftOptions(): void
    {
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'course_copy_importer',
            'settings' => ['source_course_id' => 123],
            'date_shift_options' => [
                'shift_dates' => true,
                'old_start_date' => '2024-01-01',
                'new_start_date' => '2024-09-01',
                'day_substitutions' => [
                    '1' => '2',
                    '3' => '4'
                ]
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'date_shift_options[shift_dates]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'date_shift_options[old_start_date]', 'contents' => '2024-01-01'], $apiArray);
        $this->assertContains(['name' => 'date_shift_options[new_start_date]', 'contents' => '2024-09-01'], $apiArray);
        $this->assertContains(['name' => 'date_shift_options[day_substitutions][1]', 'contents' => '2'], $apiArray);
        $this->assertContains(['name' => 'date_shift_options[day_substitutions][3]', 'contents' => '4'], $apiArray);
    }

    public function testSelectiveImport(): void
    {
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'course_copy_importer',
            'settings' => ['source_course_id' => 123],
            'selective_import' => true,
            'select' => [
                'assignments' => [1, 2, 3],
                'quizzes' => ['id_abc', 'id_def']
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'selective_import', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'select[assignments][]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'select[assignments][]', 'contents' => '2'], $apiArray);
        $this->assertContains(['name' => 'select[assignments][]', 'contents' => '3'], $apiArray);
        $this->assertContains(['name' => 'select[quizzes][]', 'contents' => 'id_abc'], $apiArray);
        $this->assertContains(['name' => 'select[quizzes][]', 'contents' => 'id_def'], $apiArray);
    }

    public function testValidation(): void
    {
        // Missing migration type
        $dto = new CreateContentMigrationDTO([]);
        $this->assertFalse($dto->validate());

        // Invalid migration type
        $dto = new CreateContentMigrationDTO(['migration_type' => 'invalid_type']);
        $this->assertFalse($dto->validate());

        // Course copy without source_course_id
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'course_copy_importer'
        ]);
        $this->assertFalse($dto->validate());

        // Valid course copy
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'course_copy_importer',
            'settings' => ['source_course_id' => 123]
        ]);
        $this->assertTrue($dto->validate());

        // File-based migration without file info
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'common_cartridge_importer'
        ]);
        $this->assertFalse($dto->validate());

        // File-based migration with pre_attachment
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'common_cartridge_importer',
            'pre_attachment' => ['name' => 'test.imscc']
        ]);
        $this->assertTrue($dto->validate());

        // File-based migration with file_url
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'zip_file_importer',
            'settings' => ['file_url' => 'https://example.com/file.zip']
        ]);
        $this->assertTrue($dto->validate());
    }

    public function testHelperMethods(): void
    {
        $dto = new CreateContentMigrationDTO();
        
        // Test file upload setter
        $dto->setFileUpload('test.zip', 12345, 'application/zip');
        $preAttachment = $dto->getPreAttachment();
        $this->assertEquals('test.zip', $preAttachment['name']);
        $this->assertEquals(12345, $preAttachment['size']);
        $this->assertEquals('application/zip', $preAttachment['content_type']);

        // Test course copy source setter
        $dto->setCourseCopySource(456);
        $settings = $dto->getSettings();
        $this->assertEquals(456, $settings['source_course_id']);

        // Test file URL setter
        $dto->setFileUrl('https://example.com/course.zip');
        $settings = $dto->getSettings();
        $this->assertEquals('https://example.com/course.zip', $settings['file_url']);

        // Test date shifting configuration
        $dto->configureDateShifting(true, '2024-01-01', '2024-12-31', '2024-09-01', '2025-05-31');
        $dateOptions = $dto->getDateShiftOptions();
        $this->assertTrue($dateOptions['shift_dates']);
        $this->assertEquals('2024-01-01', $dateOptions['old_start_date']);
        $this->assertEquals('2024-09-01', $dateOptions['new_start_date']);

        // Test day substitution
        $dto->addDaySubstitution(1, 3); // Monday to Wednesday
        $dateOptions = $dto->getDateShiftOptions();
        $this->assertEquals(3, $dateOptions['day_substitutions'][1]);
    }

    public function testComplexSettings(): void
    {
        $dto = new CreateContentMigrationDTO([
            'migration_type' => 'course_copy_importer',
            'settings' => [
                'source_course_id' => 123,
                'overwrite_quizzes' => true,
                'question_bank_id' => 456,
                'insert_into_module_id' => 789,
                'importer_skips' => ['all_course_settings', 'visibility_settings']
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'settings[source_course_id]', 'contents' => '123'], $apiArray);
        $this->assertContains(['name' => 'settings[overwrite_quizzes]', 'contents' => '1'], $apiArray);
        $this->assertContains(['name' => 'settings[question_bank_id]', 'contents' => '456'], $apiArray);
        $this->assertContains(['name' => 'settings[insert_into_module_id]', 'contents' => '789'], $apiArray);
        $this->assertContains(['name' => 'settings[importer_skips][0]', 'contents' => 'all_course_settings'], $apiArray);
        $this->assertContains(['name' => 'settings[importer_skips][1]', 'contents' => 'visibility_settings'], $apiArray);
    }
}