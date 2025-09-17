<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\RubricRating;
use PHPUnit\Framework\TestCase;

class RubricRatingTest extends TestCase
{
    /**
     * Test RubricRating constructor with full data
     */
    public function testConstructorWithFullData(): void
    {
        $data = [
            'id' => 'rating_123',
            'criterion_id' => 'criterion_456',
            'description' => 'Excellent',
            'long_description' => 'Demonstrates exceptional understanding',
            'points' => 10.0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $rating = new RubricRating($data);

        $this->assertEquals('rating_123', $rating->id);
        $this->assertEquals('criterion_456', $rating->criterionId);
        $this->assertEquals('Excellent', $rating->description);
        $this->assertEquals('Demonstrates exceptional understanding', $rating->longDescription);
        $this->assertEquals(10.0, $rating->points);
        $this->assertEquals('2024-01-01T00:00:00Z', $rating->createdAt);
        $this->assertEquals('2024-01-02T00:00:00Z', $rating->updatedAt);
    }

    /**
     * Test RubricRating constructor with minimal data
     */
    public function testConstructorWithMinimalData(): void
    {
        $data = [
            'description' => 'Good',
            'points' => 7.5,
        ];

        $rating = new RubricRating($data);

        $this->assertNull($rating->id);
        $this->assertNull($rating->criterionId);
        $this->assertEquals('Good', $rating->description);
        $this->assertNull($rating->longDescription);
        $this->assertEquals(7.5, $rating->points);
        $this->assertNull($rating->createdAt);
        $this->assertNull($rating->updatedAt);
    }

    /**
     * Test RubricRating constructor with empty data
     */
    public function testConstructorWithEmptyData(): void
    {
        $rating = new RubricRating([]);

        $this->assertNull($rating->id);
        $this->assertNull($rating->criterionId);
        $this->assertNull($rating->description);
        $this->assertNull($rating->longDescription);
        $this->assertNull($rating->points);
        $this->assertNull($rating->createdAt);
        $this->assertNull($rating->updatedAt);
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $data = [
            'id' => 'rating_789',
            'description' => 'Satisfactory',
            'points' => 5.0,
        ];

        $rating = new RubricRating($data);
        $array = $rating->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('rating_789', $array['id']);
        $this->assertEquals('Satisfactory', $array['description']);
        $this->assertEquals(5.0, $array['points']);
        $this->assertArrayNotHasKey('criterion_id', $array);
        $this->assertArrayNotHasKey('long_description', $array);
    }

    /**
     * Test toArray with all properties set
     */
    public function testToArrayWithAllProperties(): void
    {
        $data = [
            'id' => 'rating_full',
            'criterion_id' => 'criterion_full',
            'description' => 'Outstanding',
            'long_description' => 'Exceeds all expectations',
            'points' => 12.5,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $rating = new RubricRating($data);
        $array = $rating->toArray();

        $this->assertCount(7, $array);
        $this->assertEquals('rating_full', $array['id']);
        $this->assertEquals('criterion_full', $array['criterion_id']);
        $this->assertEquals('Outstanding', $array['description']);
        $this->assertEquals('Exceeds all expectations', $array['long_description']);
        $this->assertEquals(12.5, $array['points']);
        $this->assertEquals('2024-01-01T00:00:00Z', $array['created_at']);
        $this->assertEquals('2024-01-02T00:00:00Z', $array['updated_at']);
    }
}
