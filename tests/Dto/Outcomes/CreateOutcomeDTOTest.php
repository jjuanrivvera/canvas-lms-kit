<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Outcomes;

use CanvasLMS\Dto\Outcomes\CreateOutcomeDTO;
use PHPUnit\Framework\TestCase;

class CreateOutcomeDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateOutcomeDTO();

        $this->assertNull($dto->title);
        $this->assertNull($dto->displayName);
        $this->assertNull($dto->description);
        $this->assertNull($dto->vendorGuid);
        $this->assertNull($dto->masteryPoints);
        $this->assertNull($dto->ratings);
        $this->assertNull($dto->calculationMethod);
        $this->assertNull($dto->calculationInt);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'title' => 'Mathematical Problem Solving',
            'displayName' => 'Math Problem Solving',
            'description' => 'Students can solve complex mathematical problems',
            'vendorGuid' => 'math-ps-001',
            'masteryPoints' => 3.0,
            'calculationMethod' => 'decaying_average',
            'calculationInt' => 65,
            'ratings' => [
                ['description' => 'Exceeds', 'points' => 4.0],
                ['description' => 'Meets', 'points' => 3.0],
                ['description' => 'Below', 'points' => 2.0],
            ],
        ];

        $dto = new CreateOutcomeDTO($data);

        $this->assertEquals('Mathematical Problem Solving', $dto->title);
        $this->assertEquals('Math Problem Solving', $dto->displayName);
        $this->assertEquals('Students can solve complex mathematical problems', $dto->description);
        $this->assertEquals('math-ps-001', $dto->vendorGuid);
        $this->assertEquals(3.0, $dto->masteryPoints);
        $this->assertEquals('decaying_average', $dto->calculationMethod);
        $this->assertEquals(65, $dto->calculationInt);
        $this->assertCount(3, $dto->ratings);
        $this->assertEquals('Exceeds', $dto->ratings[0]['description']);
        $this->assertEquals(4.0, $dto->ratings[0]['points']);
    }

    public function testConstructorWithSnakeCaseData(): void
    {
        $data = [
            'display_name' => 'Snake Case Display',
            'vendor_guid' => 'snake-001',
            'mastery_points' => 2.5,
            'calculation_method' => 'highest',
            'calculation_int' => 80,
        ];

        $dto = new CreateOutcomeDTO($data);

        $this->assertEquals('Snake Case Display', $dto->displayName);
        $this->assertEquals('snake-001', $dto->vendorGuid);
        $this->assertEquals(2.5, $dto->masteryPoints);
        $this->assertEquals('highest', $dto->calculationMethod);
        $this->assertEquals(80, $dto->calculationInt);
    }

    public function testToArrayWithMinimalData(): void
    {
        $dto = new CreateOutcomeDTO(['title' => 'Basic Outcome']);

        $result = $dto->toArray();

        $expected = [
            [
                'name' => 'title',
                'contents' => 'Basic Outcome',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testToArrayWithAllFields(): void
    {
        $dto = new CreateOutcomeDTO([
            'title' => 'Complete Outcome',
            'displayName' => 'Complete',
            'description' => 'A complete outcome',
            'vendorGuid' => 'complete-001',
            'masteryPoints' => 3.0,
            'calculationMethod' => 'average',
            'calculationInt' => 70,
            'ratings' => [
                ['description' => 'Excellent', 'points' => 4.0],
                ['description' => 'Good', 'points' => 3.0],
            ],
        ]);

        $result = $dto->toArray();

        $this->assertCount(11, $result); // 7 base fields + 4 rating fields

        // Check title
        $this->assertEquals('title', $result[0]['name']);
        $this->assertEquals('Complete Outcome', $result[0]['contents']);

        // Check display name (snake case)
        $this->assertEquals('display_name', $result[1]['name']);
        $this->assertEquals('Complete', $result[1]['contents']);

        // Check ratings format
        $ratingFields = array_filter($result, fn ($item) => str_contains($item['name'], 'ratings['));
        $this->assertCount(4, $ratingFields);

        // Find rating description and points
        $rating0Desc = array_filter($result, fn ($item) => $item['name'] === 'ratings[0][description]');
        $rating0Points = array_filter($result, fn ($item) => $item['name'] === 'ratings[0][points]');

        $this->assertCount(1, $rating0Desc);
        $this->assertCount(1, $rating0Points);
        $this->assertEquals('Excellent', array_values($rating0Desc)[0]['contents']);
        $this->assertEquals('4', array_values($rating0Points)[0]['contents']);
    }

    public function testToArrayExcludesNullValues(): void
    {
        $dto = new CreateOutcomeDTO([
            'title' => 'Outcome with nulls',
            'description' => null,
            'masteryPoints' => null,
        ]);

        $result = $dto->toArray();

        $this->assertCount(1, $result);
        $this->assertEquals('title', $result[0]['name']);

        // Ensure no null fields are included
        foreach ($result as $field) {
            $this->assertNotNull($field['contents']);
        }
    }

    public function testValidateCalculationMethodWithValidMethods(): void
    {
        $validMethods = [
            'decaying_average',
            'n_mastery',
            'latest',
            'highest',
            'average',
        ];

        foreach ($validMethods as $method) {
            $dto = new CreateOutcomeDTO(['calculationMethod' => $method]);
            $this->assertTrue($dto->validateCalculationMethod(), "Method {$method} should be valid");
        }
    }

    public function testValidateCalculationMethodWithInvalidMethod(): void
    {
        $dto = new CreateOutcomeDTO(['calculationMethod' => 'invalid_method']);
        $this->assertFalse($dto->validateCalculationMethod());
    }

    public function testValidateCalculationMethodWithNullMethod(): void
    {
        $dto = new CreateOutcomeDTO();
        $this->assertTrue($dto->validateCalculationMethod());
    }

    public function testValidateRatingsWithValidRatings(): void
    {
        $dto = new CreateOutcomeDTO([
            'masteryPoints' => 3.0,
            'ratings' => [
                ['description' => 'Exceeds', 'points' => 4.0],
                ['description' => 'Meets', 'points' => 3.0],
                ['description' => 'Below', 'points' => 2.0],
            ],
        ]);

        $this->assertTrue($dto->validateRatings());
    }

    public function testValidateRatingsWithoutMasteryPointMatch(): void
    {
        $dto = new CreateOutcomeDTO([
            'masteryPoints' => 5.0, // No rating matches this
            'ratings' => [
                ['description' => 'Exceeds', 'points' => 4.0],
                ['description' => 'Meets', 'points' => 3.0],
            ],
        ]);

        $this->assertFalse($dto->validateRatings());
    }

    public function testValidateRatingsWithNoMasteryPoints(): void
    {
        $dto = new CreateOutcomeDTO([
            'ratings' => [
                ['description' => 'Good', 'points' => 3.0],
                ['description' => 'Fair', 'points' => 2.0],
            ],
        ]);

        $this->assertTrue($dto->validateRatings());
    }

    public function testValidateRatingsWithEmptyRatings(): void
    {
        $dto = new CreateOutcomeDTO();
        $this->assertTrue($dto->validateRatings());

        $dto = new CreateOutcomeDTO(['ratings' => []]);
        $this->assertTrue($dto->validateRatings());
    }

    public function testWithRatingsBuilderPattern(): void
    {
        $ratings = [
            ['description' => 'Excellent', 'points' => 4.0],
            ['description' => 'Good', 'points' => 3.0],
        ];

        $dto = (new CreateOutcomeDTO())
            ->withRatings($ratings);

        $this->assertEquals($ratings, $dto->ratings);
    }

    public function testAddRatingMethod(): void
    {
        $dto = new CreateOutcomeDTO();

        $result = $dto->addRating('Excellent', 4.0);

        $this->assertSame($dto, $result); // Builder pattern
        $this->assertCount(1, $dto->ratings);
        $this->assertEquals('Excellent', $dto->ratings[0]['description']);
        $this->assertEquals(4.0, $dto->ratings[0]['points']);

        // Add another rating
        $dto->addRating('Good', 3.0);

        $this->assertCount(2, $dto->ratings);
        $this->assertEquals('Good', $dto->ratings[1]['description']);
        $this->assertEquals(3.0, $dto->ratings[1]['points']);
    }

    public function testWithCalculationMethodValid(): void
    {
        $dto = new CreateOutcomeDTO();

        $result = $dto->withCalculationMethod('decaying_average', 65);

        $this->assertSame($dto, $result); // Builder pattern
        $this->assertEquals('decaying_average', $dto->calculationMethod);
        $this->assertEquals(65, $dto->calculationInt);
    }

    public function testWithCalculationMethodInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid calculation method: invalid_method');

        $dto = new CreateOutcomeDTO();
        $dto->withCalculationMethod('invalid_method');
    }

    public function testWithCalculationMethodWithoutInt(): void
    {
        $dto = new CreateOutcomeDTO();
        $dto->withCalculationMethod('highest');

        $this->assertEquals('highest', $dto->calculationMethod);
        $this->assertNull($dto->calculationInt);
    }

    public function testNumericCasting(): void
    {
        $dto = new CreateOutcomeDTO([
            'masteryPoints' => '3.5', // String that should be cast to float
            'calculationInt' => '75',   // String that should be cast to int
        ]);

        $this->assertSame(3.5, $dto->masteryPoints);
        $this->assertSame(75, $dto->calculationInt);
    }

    public function testApiPropertyName(): void
    {
        $dto = new CreateOutcomeDTO(['title' => 'Test']);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('outcome[title]', $result[0]['name']);
        $this->assertEquals('Test', $result[0]['contents']);
    }

    public function testComplexScenario(): void
    {
        $dto = (new CreateOutcomeDTO(['title' => 'Complex Outcome']))
            ->addRating('Mastery', 4.0)
            ->addRating('Proficient', 3.0)
            ->addRating('Developing', 2.0)
            ->withCalculationMethod('decaying_average', 65);

        // Set mastery points to match a rating
        $dto->masteryPoints = 3.0;
        $dto->description = 'A complex outcome for testing';

        $this->assertTrue($dto->validateRatings());
        $this->assertTrue($dto->validateCalculationMethod());

        $result = $dto->toArray();

        // Should have title, description, masteryPoints, calculationMethod, calculationInt + 6 rating fields
        $this->assertCount(11, $result);

        // Verify API format
        $apiResult = $dto->toApiArray();
        $this->assertIsArray($apiResult);
        $this->assertNotEmpty($apiResult);

        // Check that API fields are properly formatted with outcome[] prefix
        $apiFields = array_column($apiResult, 'name');
        $this->assertContains('outcome[title]', $apiFields);
    }
}
