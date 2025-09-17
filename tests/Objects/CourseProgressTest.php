<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\CourseProgress;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Objects\CourseProgress
 */
class CourseProgressTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $courseProgress = new CourseProgress();

        $this->assertNull($courseProgress->requirementCount);
        $this->assertNull($courseProgress->requirementCompletedCount);
        $this->assertNull($courseProgress->nextRequirementUrl);
        $this->assertNull($courseProgress->completedAt);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'requirement_count' => 10,
            'requirement_completed_count' => 5,
            'next_requirement_url' => 'https://canvas.example.com/courses/123/modules/items/456',
            'completed_at' => '2023-12-01T10:00:00Z',
        ];

        $courseProgress = new CourseProgress($data);

        $this->assertEquals(10, $courseProgress->requirementCount);
        $this->assertEquals(5, $courseProgress->requirementCompletedCount);
        $this->assertEquals($data['next_requirement_url'], $courseProgress->nextRequirementUrl);
        $this->assertEquals($data['completed_at'], $courseProgress->completedAt);
    }

    public function testGettersAndSetters(): void
    {
        $courseProgress = new CourseProgress();

        $courseProgress->setRequirementCount(20);
        $this->assertEquals(20, $courseProgress->getRequirementCount());

        $courseProgress->setRequirementCompletedCount(15);
        $this->assertEquals(15, $courseProgress->getRequirementCompletedCount());

        $nextUrl = 'https://canvas.example.com/courses/123/modules/items/789';
        $courseProgress->setNextRequirementUrl($nextUrl);
        $this->assertEquals($nextUrl, $courseProgress->getNextRequirementUrl());

        $completedAt = '2023-12-15T14:30:00Z';
        $courseProgress->setCompletedAt($completedAt);
        $this->assertEquals($completedAt, $courseProgress->getCompletedAt());
    }

    public function testIsCompleted(): void
    {
        $courseProgress = new CourseProgress();

        // Not completed when completedAt is null
        $this->assertFalse($courseProgress->isCompleted());

        // Completed when completedAt has a value
        $courseProgress->setCompletedAt('2023-12-01T10:00:00Z');
        $this->assertTrue($courseProgress->isCompleted());
    }

    public function testHasMoreRequirements(): void
    {
        $courseProgress = new CourseProgress();

        // No more requirements when nextRequirementUrl is null
        $this->assertFalse($courseProgress->hasMoreRequirements());

        // Has more requirements when nextRequirementUrl has a value
        $courseProgress->setNextRequirementUrl('https://canvas.example.com/courses/123/modules/items/456');
        $this->assertTrue($courseProgress->hasMoreRequirements());
    }

    public function testGetCompletionPercentage(): void
    {
        $courseProgress = new CourseProgress();

        // 0% when no data
        $this->assertEquals(0.0, $courseProgress->getCompletionPercentage());

        // 0% when requirementCount is 0
        $courseProgress->setRequirementCount(0);
        $courseProgress->setRequirementCompletedCount(0);
        $this->assertEquals(0.0, $courseProgress->getCompletionPercentage());

        // 0% when requirementCompletedCount is null
        $courseProgress->setRequirementCount(10);
        $courseProgress->setRequirementCompletedCount(null);
        $this->assertEquals(0.0, $courseProgress->getCompletionPercentage());

        // 50% completion
        $courseProgress->setRequirementCount(10);
        $courseProgress->setRequirementCompletedCount(5);
        $this->assertEquals(50.0, $courseProgress->getCompletionPercentage());

        // 100% completion
        $courseProgress->setRequirementCount(20);
        $courseProgress->setRequirementCompletedCount(20);
        $this->assertEquals(100.0, $courseProgress->getCompletionPercentage());
    }

    public function testGetRemainingRequirements(): void
    {
        $courseProgress = new CourseProgress();

        // 0 when no data
        $this->assertEquals(0, $courseProgress->getRemainingRequirements());

        // 0 when counts are null
        $courseProgress->setRequirementCount(null);
        $courseProgress->setRequirementCompletedCount(null);
        $this->assertEquals(0, $courseProgress->getRemainingRequirements());

        // Calculate remaining
        $courseProgress->setRequirementCount(10);
        $courseProgress->setRequirementCompletedCount(3);
        $this->assertEquals(7, $courseProgress->getRemainingRequirements());

        // Never negative (edge case)
        $courseProgress->setRequirementCount(5);
        $courseProgress->setRequirementCompletedCount(10);
        $this->assertEquals(0, $courseProgress->getRemainingRequirements());
    }

    public function testToArray(): void
    {
        // Test with empty data
        $courseProgress = new CourseProgress();
        $this->assertEquals([], $courseProgress->toArray());

        // Test with partial data
        $courseProgress->setRequirementCount(10);
        $courseProgress->setRequirementCompletedCount(5);

        $expected = [
            'requirement_count' => 10,
            'requirement_completed_count' => 5,
        ];

        $this->assertEquals($expected, $courseProgress->toArray());

        // Test with all data
        $courseProgress->setNextRequirementUrl('https://canvas.example.com/next');
        $courseProgress->setCompletedAt('2023-12-01T10:00:00Z');

        $expected = [
            'requirement_count' => 10,
            'requirement_completed_count' => 5,
            'next_requirement_url' => 'https://canvas.example.com/next',
            'completed_at' => '2023-12-01T10:00:00Z',
        ];

        $this->assertEquals($expected, $courseProgress->toArray());
    }

    public function testSnakeCaseToCamelCaseConversion(): void
    {
        $data = [
            'requirement_count' => 10,
            'requirement_completed_count' => 5,
            'next_requirement_url' => 'https://example.com',
            'completed_at' => '2023-12-01T10:00:00Z',
            'unknown_property' => 'should be ignored',
        ];

        $courseProgress = new CourseProgress($data);

        $this->assertEquals(10, $courseProgress->requirementCount);
        $this->assertEquals(5, $courseProgress->requirementCompletedCount);
        $this->assertEquals($data['next_requirement_url'], $courseProgress->nextRequirementUrl);
        $this->assertEquals($data['completed_at'], $courseProgress->completedAt);
    }
}
