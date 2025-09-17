<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\Migrator;
use PHPUnit\Framework\TestCase;

class MigratorTest extends TestCase
{
    public function testConstructor(): void
    {
        $data = [
            'type' => 'course_copy_importer',
            'requires_file_upload' => false,
            'name' => 'Course Copy',
            'required_settings' => ['source_course_id'],
        ];

        $migrator = new Migrator($data);

        $this->assertEquals('course_copy_importer', $migrator->getType());
        $this->assertFalse($migrator->getRequiresFileUpload());
        $this->assertEquals('Course Copy', $migrator->getName());
        $this->assertEquals(['source_course_id'], $migrator->getRequiredSettings());
    }

    public function testSnakeCaseToCamelCase(): void
    {
        $data = [
            'type' => 'common_cartridge_importer',
            'requires_file_upload' => true,
            'name' => 'Common Cartridge',
            'required_settings' => [],
        ];

        $migrator = new Migrator($data);

        $this->assertEquals('common_cartridge_importer', $migrator->type);
        $this->assertTrue($migrator->requiresFileUpload);
        $this->assertEquals('Common Cartridge', $migrator->name);
        $this->assertIsArray($migrator->requiredSettings);
    }

    public function testRequiresSetting(): void
    {
        $migrator = new Migrator([
            'type' => 'course_copy_importer',
            'required_settings' => ['source_course_id', 'import_quizzes'],
        ]);

        $this->assertTrue($migrator->requiresSetting('source_course_id'));
        $this->assertTrue($migrator->requiresSetting('import_quizzes'));
        $this->assertFalse($migrator->requiresSetting('non_existent_setting'));

        // Test with null required_settings
        $migrator = new Migrator(['type' => 'zip_file_importer']);
        $this->assertFalse($migrator->requiresSetting('any_setting'));
    }

    public function testIsFileBased(): void
    {
        $fileMigrator = new Migrator([
            'type' => 'common_cartridge_importer',
            'requires_file_upload' => true,
        ]);
        $this->assertTrue($fileMigrator->isFileBased());

        $nonFileMigrator = new Migrator([
            'type' => 'course_copy_importer',
            'requires_file_upload' => false,
        ]);
        $this->assertFalse($nonFileMigrator->isFileBased());

        // Test with null
        $nullMigrator = new Migrator(['type' => 'test']);
        $this->assertFalse($nullMigrator->isFileBased());
    }

    public function testMigratorTypeHelpers(): void
    {
        // Course Copy
        $migrator = new Migrator(['type' => 'course_copy_importer']);
        $this->assertTrue($migrator->isCourseCopy());
        $this->assertFalse($migrator->isCommonCartridge());
        $this->assertFalse($migrator->isCanvasCartridge());
        $this->assertFalse($migrator->isZipFile());
        $this->assertFalse($migrator->isQti());
        $this->assertFalse($migrator->isMoodle());

        // Common Cartridge
        $migrator = new Migrator(['type' => 'common_cartridge_importer']);
        $this->assertFalse($migrator->isCourseCopy());
        $this->assertTrue($migrator->isCommonCartridge());
        $this->assertFalse($migrator->isCanvasCartridge());

        // Canvas Cartridge
        $migrator = new Migrator(['type' => 'canvas_cartridge_importer']);
        $this->assertFalse($migrator->isCourseCopy());
        $this->assertFalse($migrator->isCommonCartridge());
        $this->assertTrue($migrator->isCanvasCartridge());

        // ZIP File
        $migrator = new Migrator(['type' => 'zip_file_importer']);
        $this->assertTrue($migrator->isZipFile());
        $this->assertFalse($migrator->isQti());
        $this->assertFalse($migrator->isMoodle());

        // QTI
        $migrator = new Migrator(['type' => 'qti_converter']);
        $this->assertFalse($migrator->isZipFile());
        $this->assertTrue($migrator->isQti());
        $this->assertFalse($migrator->isMoodle());

        // Moodle
        $migrator = new Migrator(['type' => 'moodle_converter']);
        $this->assertFalse($migrator->isQti());
        $this->assertTrue($migrator->isMoodle());
    }

    public function testEmptyConstructor(): void
    {
        $migrator = new Migrator();

        $this->assertNull($migrator->getType());
        $this->assertNull($migrator->getRequiresFileUpload());
        $this->assertNull($migrator->getName());
        $this->assertNull($migrator->getRequiredSettings());
    }

    public function testUnknownProperties(): void
    {
        $data = [
            'type' => 'test_importer',
            'unknown_property' => 'should be ignored',
            'another_unknown' => 123,
        ];

        $migrator = new Migrator($data);

        $this->assertEquals('test_importer', $migrator->getType());
        $this->assertObjectNotHasProperty('unknownProperty', $migrator);
        $this->assertObjectNotHasProperty('anotherUnknown', $migrator);
    }
}
