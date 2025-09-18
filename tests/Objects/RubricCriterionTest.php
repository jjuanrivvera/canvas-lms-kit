<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\RubricCriterion;
use CanvasLMS\Objects\RubricRating;
use PHPUnit\Framework\TestCase;

class RubricCriterionTest extends TestCase
{
    /**
     * Test RubricCriterion constructor with full data including ratings
     */
    public function testConstructorWithFullDataAndRatings(): void
    {
        $data = [
            'id' => 'criterion_123',
            'criterion_id' => 'alt_criterion_123',
            'position' => 1,
            'description' => 'Content Knowledge',
            'long_description' => 'Demonstrates understanding of subject matter',
            'points' => 20.0,
            'criterion_use_range' => true,
            'ratings' => [
                [
                    'id' => 'rating_1',
                    'description' => 'Excellent',
                    'points' => 20.0,
                ],
                [
                    'id' => 'rating_2',
                    'description' => 'Good',
                    'points' => 15.0,
                ],
                [
                    'id' => 'rating_3',
                    'description' => 'Satisfactory',
                    'points' => 10.0,
                ],
            ],
            'learning_outcome_id' => 'outcome_456',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $criterion = new RubricCriterion($data);

        $this->assertEquals('criterion_123', $criterion->id);
        $this->assertEquals('alt_criterion_123', $criterion->criterionId);
        $this->assertEquals(1, $criterion->position);
        $this->assertEquals('Content Knowledge', $criterion->description);
        $this->assertEquals('Demonstrates understanding of subject matter', $criterion->longDescription);
        $this->assertEquals(20.0, $criterion->points);
        $this->assertTrue($criterion->criterionUseRange);
        $this->assertEquals('outcome_456', $criterion->learningOutcomeId);
        $this->assertEquals('2024-01-01T00:00:00Z', $criterion->createdAt);
        $this->assertEquals('2024-01-02T00:00:00Z', $criterion->updatedAt);

        // Test ratings
        $this->assertIsArray($criterion->ratings);
        $this->assertCount(3, $criterion->ratings);

        foreach ($criterion->ratings as $rating) {
            $this->assertInstanceOf(RubricRating::class, $rating);
        }

        $this->assertEquals('Excellent', $criterion->ratings[0]->description);
        $this->assertEquals(20.0, $criterion->ratings[0]->points);
        $this->assertEquals('Good', $criterion->ratings[1]->description);
        $this->assertEquals(15.0, $criterion->ratings[1]->points);
        $this->assertEquals('Satisfactory', $criterion->ratings[2]->description);
        $this->assertEquals(10.0, $criterion->ratings[2]->points);
    }

    /**
     * Test RubricCriterion constructor with minimal data
     */
    public function testConstructorWithMinimalData(): void
    {
        $data = [
            'description' => 'Writing Style',
            'points' => 10.0,
        ];

        $criterion = new RubricCriterion($data);

        $this->assertNull($criterion->id);
        $this->assertNull($criterion->criterionId);
        $this->assertNull($criterion->position);
        $this->assertEquals('Writing Style', $criterion->description);
        $this->assertNull($criterion->longDescription);
        $this->assertEquals(10.0, $criterion->points);
        $this->assertNull($criterion->criterionUseRange);
        $this->assertNull($criterion->ratings);
        $this->assertNull($criterion->learningOutcomeId);
        $this->assertNull($criterion->createdAt);
        $this->assertNull($criterion->updatedAt);
    }

    /**
     * Test RubricCriterion constructor with invalid ratings data
     */
    public function testConstructorWithInvalidRatingsData(): void
    {
        $data = [
            'description' => 'Test Criterion',
            'points' => 5.0,
            'ratings' => [
                'invalid_string_rating',
                null,
                123,
                ['description' => 'Valid Rating', 'points' => 5.0],
            ],
        ];

        $criterion = new RubricCriterion($data);

        // Should only have one valid rating
        $this->assertIsArray($criterion->ratings);
        $this->assertCount(1, $criterion->ratings);
        $this->assertInstanceOf(RubricRating::class, $criterion->ratings[0]);
        $this->assertEquals('Valid Rating', $criterion->ratings[0]->description);
    }

    /**
     * Test RubricCriterion constructor with empty ratings array
     */
    public function testConstructorWithEmptyRatingsArray(): void
    {
        $data = [
            'description' => 'Test Criterion',
            'points' => 5.0,
            'ratings' => [],
        ];

        $criterion = new RubricCriterion($data);

        $this->assertIsArray($criterion->ratings);
        $this->assertEmpty($criterion->ratings);
    }

    /**
     * Test toArray method with full data
     */
    public function testToArrayWithFullData(): void
    {
        $data = [
            'id' => 'criterion_456',
            'description' => 'Analysis',
            'long_description' => 'Critical thinking and analysis skills',
            'points' => 25.0,
            'criterion_use_range' => false,
            'ratings' => [
                ['description' => 'Advanced', 'points' => 25.0],
                ['description' => 'Proficient', 'points' => 20.0],
            ],
        ];

        $criterion = new RubricCriterion($data);
        $array = $criterion->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('criterion_456', $array['id']);
        $this->assertEquals('Analysis', $array['description']);
        $this->assertEquals('Critical thinking and analysis skills', $array['long_description']);
        $this->assertEquals(25.0, $array['points']);
        $this->assertFalse($array['criterion_use_range']);

        // Check ratings in array format
        $this->assertIsArray($array['ratings']);
        $this->assertCount(2, $array['ratings']);
        $this->assertEquals('Advanced', $array['ratings'][0]['description']);
        $this->assertEquals(25.0, $array['ratings'][0]['points']);
    }

    /**
     * Test toArray method with minimal data
     */
    public function testToArrayWithMinimalData(): void
    {
        $criterion = new RubricCriterion([]);
        $array = $criterion->toArray();

        $this->assertIsArray($array);
        $this->assertEmpty($array);
    }

    /**
     * Test toArray excludes null values
     */
    public function testToArrayExcludesNullValues(): void
    {
        $data = [
            'id' => 'criterion_789',
            'description' => 'Presentation',
            'points' => 15.0,
            'long_description' => null,
            'criterion_use_range' => null,
            'ratings' => null,
        ];

        $criterion = new RubricCriterion($data);
        $array = $criterion->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('points', $array);
        $this->assertArrayNotHasKey('long_description', $array);
        $this->assertArrayNotHasKey('criterion_use_range', $array);
        $this->assertArrayNotHasKey('ratings', $array);
    }
}
