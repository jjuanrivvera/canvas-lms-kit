<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Sections;

use CanvasLMS\Dto\Sections\UpdateSectionDTO;
use PHPUnit\Framework\TestCase;

class UpdateSectionDTOTest extends TestCase
{
    public function testConstructorWithArray(): void
    {
        $data = [
            'name' => 'Updated Section',
            'sis_section_id' => 's34643-updated',
            'integration_id' => '3452342345-updated',
            'start_at' => '2024-02-01T08:00:00Z',
            'end_at' => '2024-06-30T17:00:00Z',
            'restrict_enrollments_to_section_dates' => false,
            'override_sis_stickiness' => true
        ];

        $dto = new UpdateSectionDTO($data);

        $this->assertEquals('Updated Section', $dto->name);
        $this->assertEquals('s34643-updated', $dto->sisSectionId);
        $this->assertEquals('3452342345-updated', $dto->integrationId);
        $this->assertEquals('2024-02-01T08:00:00+00:00', $dto->startAt->format('c'));
        $this->assertEquals('2024-06-30T17:00:00+00:00', $dto->endAt->format('c'));
        $this->assertFalse($dto->restrictEnrollmentsToSectionDates);
        $this->assertTrue($dto->overrideSisStickiness);
    }

    public function testToApiArray(): void
    {
        $dto = new UpdateSectionDTO([
            'name' => 'Modified Section',
            'end_at' => '2024-12-31T23:59:59Z',
            'restrict_enrollments_to_section_dates' => true
        ]);

        $apiArray = $dto->toApiArray();

        // Should be multipart format
        $this->assertIsArray($apiArray);
        $this->assertCount(3, $apiArray);
        
        // Extract names and contents for easier testing
        $formattedArray = [];
        foreach ($apiArray as $item) {
            $formattedArray[$item['name']] = $item['contents'];
        }

        $expected = [
            'course_section[name]' => 'Modified Section',
            'course_section[end_at]' => '2024-12-31T23:59:59+00:00',
            'course_section[restrict_enrollments_to_section_dates]' => true
        ];

        $this->assertEquals($expected, $formattedArray);
    }

    public function testToApiArrayWithOverrideSisStickiness(): void
    {
        $dto = new UpdateSectionDTO([
            'name' => 'Section with Override',
            'override_sis_stickiness' => false
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
            'course_section[name]' => 'Section with Override',
            'override_sis_stickiness' => false
        ];

        $this->assertEquals($expected, $formattedArray);
        $this->assertArrayHasKey('override_sis_stickiness', $formattedArray);
        $this->assertArrayNotHasKey('course_section[override_sis_stickiness]', $formattedArray);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $dto = new UpdateSectionDTO([
            'name' => 'Partial Update',
            'sis_section_id' => null,
            'integration_id' => null,
            'start_at' => null,
            'end_at' => null
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
        $this->assertArrayNotHasKey('course_section[start_at]', $formattedArray);
        $this->assertArrayNotHasKey('course_section[end_at]', $formattedArray);
        $this->assertArrayNotHasKey('override_sis_stickiness', $formattedArray);
    }

    public function testAllFieldsNull(): void
    {
        $dto = new UpdateSectionDTO([]);
        
        $apiArray = $dto->toApiArray();
        
        // When all fields are null, we should get an empty array
        $this->assertEmpty($apiArray);
    }

    public function testPartialUpdate(): void
    {
        $dto = new UpdateSectionDTO(['name' => 'Only Name Updated']);
        // Leave all other fields null

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        
        // Extract names and contents for easier testing
        $formattedArray = [];
        foreach ($apiArray as $item) {
            $formattedArray[$item['name']] = $item['contents'];
        }
        
        $this->assertArrayHasKey('course_section[name]', $formattedArray);
        $this->assertEquals('Only Name Updated', $formattedArray['course_section[name]']);
    }

    public function testSisFieldsUpdate(): void
    {
        $dto = new UpdateSectionDTO([
            'sis_section_id' => 'NEW-SIS-ID',
            'integration_id' => 'NEW-INT-ID',
            'override_sis_stickiness' => true
        ]);

        $apiArray = $dto->toApiArray();

        // Extract names and contents for easier testing
        $formattedArray = [];
        foreach ($apiArray as $item) {
            $formattedArray[$item['name']] = $item['contents'];
        }

        $expected = [
            'course_section[sis_section_id]' => 'NEW-SIS-ID',
            'course_section[integration_id]' => 'NEW-INT-ID',
            'override_sis_stickiness' => true
        ];

        $this->assertEquals($expected, $formattedArray);
    }

    public function testDateFieldsUpdate(): void
    {
        $dto = new UpdateSectionDTO([
            'start_at' => '2024-03-01T00:00:00Z',
            'end_at' => '2024-09-30T23:59:59Z'
        ]);

        $apiArray = $dto->toApiArray();

        // Extract names and contents for easier testing
        $formattedArray = [];
        foreach ($apiArray as $item) {
            $formattedArray[$item['name']] = $item['contents'];
        }

        $expected = [
            'course_section[start_at]' => '2024-03-01T00:00:00+00:00',
            'course_section[end_at]' => '2024-09-30T23:59:59+00:00'
        ];

        $this->assertEquals($expected, $formattedArray);
    }

    public function testBooleanHandling(): void
    {
        // Test with true
        $dto1 = new UpdateSectionDTO([
            'restrict_enrollments_to_section_dates' => true,
            'override_sis_stickiness' => true
        ]);

        $apiArray1 = $dto1->toApiArray();
        
        // Extract names and contents for easier testing
        $formattedArray1 = [];
        foreach ($apiArray1 as $item) {
            $formattedArray1[$item['name']] = $item['contents'];
        }
        
        $this->assertTrue($formattedArray1['course_section[restrict_enrollments_to_section_dates]']);
        $this->assertTrue($formattedArray1['override_sis_stickiness']);

        // Test with false
        $dto2 = new UpdateSectionDTO([
            'restrict_enrollments_to_section_dates' => false,
            'override_sis_stickiness' => false
        ]);

        $apiArray2 = $dto2->toApiArray();
        
        // Extract names and contents for easier testing
        $formattedArray2 = [];
        foreach ($apiArray2 as $item) {
            $formattedArray2[$item['name']] = $item['contents'];
        }
        
        $this->assertFalse($formattedArray2['course_section[restrict_enrollments_to_section_dates]']);
        $this->assertFalse($formattedArray2['override_sis_stickiness']);
    }

    public function testDefaultValues(): void
    {
        $dto = new UpdateSectionDTO([]);

        $this->assertNull($dto->name);
        $this->assertNull($dto->sisSectionId);
        $this->assertNull($dto->integrationId);
        $this->assertNull($dto->startAt);
        $this->assertNull($dto->endAt);
        $this->assertNull($dto->restrictEnrollmentsToSectionDates);
        $this->assertNull($dto->overrideSisStickiness);
    }

    public function testCompleteUpdate(): void
    {
        $dto = new UpdateSectionDTO([
            'name' => 'Complete Update',
            'sis_section_id' => 'COMPLETE-001',
            'integration_id' => 'INT-COMPLETE-001',
            'start_at' => '2024-01-01T00:00:00Z',
            'end_at' => '2024-12-31T23:59:59Z',
            'restrict_enrollments_to_section_dates' => true,
            'override_sis_stickiness' => false
        ]);

        $apiArray = $dto->toApiArray();

        // Extract names and contents for easier testing
        $formattedArray = [];
        foreach ($apiArray as $item) {
            $formattedArray[$item['name']] = $item['contents'];
        }

        $expected = [
            'course_section[name]' => 'Complete Update',
            'course_section[sis_section_id]' => 'COMPLETE-001',
            'course_section[integration_id]' => 'INT-COMPLETE-001',
            'course_section[start_at]' => '2024-01-01T00:00:00+00:00',
            'course_section[end_at]' => '2024-12-31T23:59:59+00:00',
            'course_section[restrict_enrollments_to_section_dates]' => true,
            'override_sis_stickiness' => false
        ];

        $this->assertEquals($expected, $formattedArray);
        $this->assertCount(7, $apiArray);
    }
}