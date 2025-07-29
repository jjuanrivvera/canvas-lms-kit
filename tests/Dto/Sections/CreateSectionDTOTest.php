<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Sections;

use CanvasLMS\Dto\Sections\CreateSectionDTO;
use PHPUnit\Framework\TestCase;

class CreateSectionDTOTest extends TestCase
{
    public function testConstructorWithArray(): void
    {
        $data = [
            'name' => 'Section A',
            'sis_section_id' => 's34643',
            'integration_id' => '3452342345',
            'start_at' => '2024-01-15T08:00:00Z',
            'end_at' => '2024-05-15T17:00:00Z',
            'restrict_enrollments_to_section_dates' => true,
            'enable_sis_reactivation' => false
        ];

        $dto = new CreateSectionDTO($data);

        $this->assertEquals('Section A', $dto->name);
        $this->assertEquals('s34643', $dto->sisSectionId);
        $this->assertEquals('3452342345', $dto->integrationId);
        $this->assertEquals('2024-01-15T08:00:00+00:00', $dto->startAt->format('c'));
        $this->assertEquals('2024-05-15T17:00:00+00:00', $dto->endAt->format('c'));
        $this->assertTrue($dto->restrictEnrollmentsToSectionDates);
        $this->assertFalse($dto->enableSisReactivation);
    }

    public function testToApiArray(): void
    {
        $dto = new CreateSectionDTO([
            'name' => 'Section B',
            'sis_section_id' => 'SIS-001',
            'start_at' => '2024-01-01T00:00:00Z',
            'restrict_enrollments_to_section_dates' => true
        ]);

        $apiArray = $dto->toApiArray();

        // Should be multipart format
        $this->assertIsArray($apiArray);
        $this->assertCount(4, $apiArray);
        
        // Extract names and contents for easier testing
        $formattedArray = [];
        foreach ($apiArray as $item) {
            $formattedArray[$item['name']] = $item['contents'];
        }

        $expected = [
            'course_section[name]' => 'Section B',
            'course_section[sis_section_id]' => 'SIS-001',
            'course_section[start_at]' => '2024-01-01T00:00:00+00:00',
            'course_section[restrict_enrollments_to_section_dates]' => true
        ];

        $this->assertEquals($expected, $formattedArray);
    }

    public function testToApiArrayWithEnableSisReactivation(): void
    {
        $dto = new CreateSectionDTO([
            'name' => 'Section C',
            'enable_sis_reactivation' => true
        ]);

        $apiArray = $dto->toApiArray();

        // Should be multipart format
        $this->assertIsArray($apiArray);
        $this->assertCount(2, $apiArray);
        
        // Extract names and contents for easier testing
        $formattedArray = [];
        foreach ($apiArray as $item) {
            $formattedArray[$item['name']] = $item['contents'];
        }

        $expected = [
            'course_section[name]' => 'Section C',
            'enable_sis_reactivation' => true
        ];

        $this->assertEquals($expected, $formattedArray);
        $this->assertArrayHasKey('enable_sis_reactivation', $formattedArray);
        $this->assertArrayNotHasKey('course_section[enable_sis_reactivation]', $formattedArray);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $dto = new CreateSectionDTO([
            'name' => 'Section D',
            'sis_section_id' => null,
            'integration_id' => null
        ]);

        $apiArray = $dto->toApiArray();

        // Extract names and contents for easier testing
        $formattedArray = [];
        foreach ($apiArray as $item) {
            $formattedArray[$item['name']] = $item['contents'];
        }

        $this->assertArrayHasKey('course_section[name]', $formattedArray);
        $this->assertArrayNotHasKey('course_section[sis_section_id]', $formattedArray);
        $this->assertArrayNotHasKey('course_section[integration_id]', $formattedArray);
        $this->assertArrayNotHasKey('enable_sis_reactivation', $formattedArray);
    }

    public function testValidateWithValidData(): void
    {
        $dto = new CreateSectionDTO(['name' => 'Valid Section']);

        // Should not throw an exception
        $dto->validate();
        $this->assertTrue(true);
    }

    public function testValidateWithEmptyName(): void
    {
        $dto = new CreateSectionDTO(['name' => '']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Section name is required');

        $dto->validate();
    }

    public function testDateFormatting(): void
    {
        $dto = new CreateSectionDTO([
            'name' => 'Date Test Section',
            'start_at' => '2024-01-15T08:00:00Z',
            'end_at' => '2024-05-15T17:00:00Z'
        ]);

        $apiArray = $dto->toApiArray();

        // Extract names and contents for easier testing
        $formattedArray = [];
        foreach ($apiArray as $item) {
            $formattedArray[$item['name']] = $item['contents'];
        }

        $this->assertEquals('2024-01-15T08:00:00+00:00', $formattedArray['course_section[start_at]']);
        $this->assertEquals('2024-05-15T17:00:00+00:00', $formattedArray['course_section[end_at]']);
    }

    public function testBooleanHandling(): void
    {
        // Test with true
        $dto1 = new CreateSectionDTO([
            'name' => 'Boolean Test 1',
            'restrict_enrollments_to_section_dates' => true
        ]);

        $apiArray1 = $dto1->toApiArray();
        
        // Extract names and contents for easier testing
        $formattedArray1 = [];
        foreach ($apiArray1 as $item) {
            $formattedArray1[$item['name']] = $item['contents'];
        }
        
        $this->assertTrue($formattedArray1['course_section[restrict_enrollments_to_section_dates]']);

        // Test with false
        $dto2 = new CreateSectionDTO([
            'name' => 'Boolean Test 2',
            'restrict_enrollments_to_section_dates' => false
        ]);

        $apiArray2 = $dto2->toApiArray();
        
        // Extract names and contents for easier testing
        $formattedArray2 = [];
        foreach ($apiArray2 as $item) {
            $formattedArray2[$item['name']] = $item['contents'];
        }
        
        $this->assertFalse($formattedArray2['course_section[restrict_enrollments_to_section_dates]']);

        // Test with null (should be excluded)
        $dto3 = new CreateSectionDTO([
            'name' => 'Boolean Test 3',
            'restrict_enrollments_to_section_dates' => null
        ]);

        $apiArray3 = $dto3->toApiArray();
        $this->assertArrayNotHasKey('course_section[restrict_enrollments_to_section_dates]', $apiArray3);
    }

    public function testDefaultValues(): void
    {
        $dto = new CreateSectionDTO([]);

        $this->assertEquals('', $dto->name);
        $this->assertNull($dto->sis_section_id);
        $this->assertNull($dto->integration_id);
        $this->assertNull($dto->start_at);
        $this->assertNull($dto->end_at);
        $this->assertNull($dto->restrict_enrollments_to_section_dates);
        $this->assertNull($dto->enable_sis_reactivation);
    }
}