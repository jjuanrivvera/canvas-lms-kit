<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Outcomes;

use CanvasLMS\Dto\Outcomes\UpdateOutcomeDTO;
use PHPUnit\Framework\TestCase;

class UpdateOutcomeDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdateOutcomeDTO();

        $this->assertNull($dto->title);
        $this->assertNull($dto->displayName);
        $this->assertNull($dto->description);
        $this->assertNull($dto->vendorGuid);
        $this->assertNull($dto->masteryPoints);
        $this->assertNull($dto->ratings);
        $this->assertNull($dto->calculationMethod);
        $this->assertNull($dto->calculationInt);
    }

    public function testConstructorWithPartialData(): void
    {
        $data = [
            'title' => 'Updated Outcome Title',
            'description' => 'Updated description',
        ];

        $dto = new UpdateOutcomeDTO($data);

        $this->assertEquals('Updated Outcome Title', $dto->title);
        $this->assertEquals('Updated description', $dto->description);
        $this->assertNull($dto->displayName);
        $this->assertNull($dto->masteryPoints);
    }

    public function testConstructorWithSnakeCaseData(): void
    {
        $data = [
            'display_name' => 'Updated Display',
            'vendor_guid' => 'updated-guid',
            'mastery_points' => 3.5,
            'calculation_method' => 'latest',
            'calculation_int' => 90,
        ];

        $dto = new UpdateOutcomeDTO($data);

        $this->assertEquals('Updated Display', $dto->displayName);
        $this->assertEquals('updated-guid', $dto->vendorGuid);
        $this->assertEquals(3.5, $dto->masteryPoints);
        $this->assertEquals('latest', $dto->calculationMethod);
        $this->assertEquals(90, $dto->calculationInt);
    }

    public function testToArrayWithSingleField(): void
    {
        $dto = new UpdateOutcomeDTO(['title' => 'New Title']);

        $result = $dto->toArray();

        $expected = [
            [
                'name' => 'title',
                'contents' => 'New Title',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testToArrayWithMultipleFields(): void
    {
        $dto = new UpdateOutcomeDTO([
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'masteryPoints' => 2.5,
        ]);

        $result = $dto->toArray();

        $this->assertCount(3, $result);

        // Check each field
        $fields = array_column($result, 'name');
        $this->assertContains('title', $fields);
        $this->assertContains('description', $fields);
        $this->assertContains('mastery_points', $fields);
    }

    public function testToArrayWithRatings(): void
    {
        $dto = new UpdateOutcomeDTO([
            'ratings' => [
                ['description' => 'Excellent', 'points' => 4.0],
                ['description' => 'Good', 'points' => 3.0],
            ],
        ]);

        $result = $dto->toArray();

        $this->assertCount(4, $result); // 2 ratings × 2 fields each

        // Find rating fields
        $ratingFields = array_filter($result, fn ($item) => str_contains($item['name'], 'ratings['));
        $this->assertCount(4, $ratingFields);

        // Check specific rating values
        $rating0Desc = array_filter($result, fn ($item) => $item['name'] === 'ratings[0][description]');
        $rating1Points = array_filter($result, fn ($item) => $item['name'] === 'ratings[1][points]');

        $this->assertEquals('Excellent', array_values($rating0Desc)[0]['contents']);
        $this->assertEquals('3', array_values($rating1Points)[0]['contents']);
    }

    public function testToArrayExcludesNullFields(): void
    {
        $dto = new UpdateOutcomeDTO([
            'title' => 'Updated Title',
            'description' => null,
            'masteryPoints' => null,
        ]);

        $result = $dto->toArray();

        $this->assertCount(1, $result);
        $this->assertEquals('title', $result[0]['name']);
    }

    public function testWithFieldsStaticMethod(): void
    {
        $fields = [
            'title' => 'Title from withFields',
            'description' => 'Description from withFields',
            'masteryPoints' => 3.0,
        ];

        $dto = UpdateOutcomeDTO::withFields($fields);

        $this->assertEquals('Title from withFields', $dto->title);
        $this->assertEquals('Description from withFields', $dto->description);
        $this->assertEquals(3.0, $dto->masteryPoints);
        $this->assertNull($dto->calculationMethod);
    }

    public function testWithFieldsIgnoresInvalidProperties(): void
    {
        $fields = [
            'title' => 'Valid Title',
            'invalidProperty' => 'Should be ignored',
            'anotherInvalid' => 123,
        ];

        $dto = UpdateOutcomeDTO::withFields($fields);

        $this->assertEquals('Valid Title', $dto->title);
        $this->assertObjectNotHasProperty('invalidProperty', $dto);
        $this->assertObjectNotHasProperty('anotherInvalid', $dto);
    }

    public function testUpdateTitleStaticMethod(): void
    {
        $dto = UpdateOutcomeDTO::updateTitle('New Outcome Title');

        $this->assertEquals('New Outcome Title', $dto->title);
        $this->assertNull($dto->description);
        $this->assertNull($dto->masteryPoints);

        $result = $dto->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals('title', $result[0]['name']);
    }

    public function testUpdateDescriptionStaticMethod(): void
    {
        $dto = UpdateOutcomeDTO::updateDescription('Updated description text');

        $this->assertEquals('Updated description text', $dto->description);
        $this->assertNull($dto->title);
        $this->assertNull($dto->masteryPoints);

        $result = $dto->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals('description', $result[0]['name']);
    }

    public function testUpdateMasteryPointsStaticMethod(): void
    {
        $dto = UpdateOutcomeDTO::updateMasteryPoints(3.5);

        $this->assertEquals(3.5, $dto->masteryPoints);
        $this->assertNull($dto->title);
        $this->assertNull($dto->description);

        $result = $dto->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals('mastery_points', $result[0]['name']);
        $this->assertEquals('3.5', $result[0]['contents']);
    }

    public function testUpdateRatingsStaticMethod(): void
    {
        $ratings = [
            ['description' => 'Exceeds', 'points' => 4.0],
            ['description' => 'Meets', 'points' => 3.0],
            ['description' => 'Below', 'points' => 2.0],
        ];

        $dto = UpdateOutcomeDTO::updateRatings($ratings);

        $this->assertEquals($ratings, $dto->ratings);
        $this->assertNull($dto->title);

        $result = $dto->toArray();
        $this->assertCount(6, $result); // 3 ratings × 2 fields each
    }

    public function testUpdateCalculationMethodStaticMethod(): void
    {
        $dto = UpdateOutcomeDTO::updateCalculationMethod('decaying_average', 75);

        $this->assertEquals('decaying_average', $dto->calculationMethod);
        $this->assertEquals(75, $dto->calculationInt);
        $this->assertNull($dto->title);

        $result = $dto->toArray();
        $this->assertCount(2, $result);

        $fields = array_column($result, 'name');
        $this->assertContains('calculation_method', $fields);
        $this->assertContains('calculation_int', $fields);
    }

    public function testUpdateCalculationMethodWithoutInt(): void
    {
        $dto = UpdateOutcomeDTO::updateCalculationMethod('highest');

        $this->assertEquals('highest', $dto->calculationMethod);
        $this->assertNull($dto->calculationInt);

        $result = $dto->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals('calculation_method', $result[0]['name']);
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
            $dto = new UpdateOutcomeDTO(['calculationMethod' => $method]);
            $this->assertTrue($dto->validateCalculationMethod(), "Method {$method} should be valid");
        }
    }

    public function testValidateCalculationMethodWithInvalidMethod(): void
    {
        $dto = new UpdateOutcomeDTO(['calculationMethod' => 'invalid_method']);
        $this->assertFalse($dto->validateCalculationMethod());
    }

    public function testValidateCalculationMethodWithNull(): void
    {
        $dto = new UpdateOutcomeDTO();
        $this->assertTrue($dto->validateCalculationMethod());
    }

    public function testValidateRatingsWithMasteryPointsMatch(): void
    {
        $dto = new UpdateOutcomeDTO([
            'masteryPoints' => 3.0,
            'ratings' => [
                ['description' => 'Exceeds', 'points' => 4.0],
                ['description' => 'Meets', 'points' => 3.0],
                ['description' => 'Below', 'points' => 2.0],
            ],
        ]);

        $this->assertTrue($dto->validateRatings());
    }

    public function testValidateRatingsWithoutMasteryPointsMatch(): void
    {
        $dto = new UpdateOutcomeDTO([
            'masteryPoints' => 5.0, // No rating has 5.0 points
            'ratings' => [
                ['description' => 'Good', 'points' => 4.0],
                ['description' => 'Fair', 'points' => 3.0],
            ],
        ]);

        $this->assertFalse($dto->validateRatings());
    }

    public function testValidateRatingsWithNullValues(): void
    {
        $dto = new UpdateOutcomeDTO();
        $this->assertTrue($dto->validateRatings());

        $dto = new UpdateOutcomeDTO(['ratings' => []]);
        $this->assertTrue($dto->validateRatings());

        $dto = new UpdateOutcomeDTO(['masteryPoints' => 3.0]);
        $this->assertTrue($dto->validateRatings());
    }

    public function testNumericCasting(): void
    {
        $dto = new UpdateOutcomeDTO([
            'masteryPoints' => '2.75',
            'calculationInt' => '85',
        ]);

        $this->assertSame(2.75, $dto->masteryPoints);
        $this->assertSame(85, $dto->calculationInt);
    }

    public function testApiPropertyName(): void
    {
        $dto = new UpdateOutcomeDTO(['title' => 'Test']);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('outcome[title]', $result[0]['name']);
        $this->assertEquals('Test', $result[0]['contents']);
    }

    public function testPartialUpdateScenario(): void
    {
        // Simulate updating only title and mastery points
        $dto = UpdateOutcomeDTO::withFields([
            'title' => 'Partially Updated Outcome',
            'masteryPoints' => 3.0,
        ]);

        $this->assertEquals('Partially Updated Outcome', $dto->title);
        $this->assertEquals(3.0, $dto->masteryPoints);
        $this->assertNull($dto->description);
        $this->assertNull($dto->calculationMethod);

        $result = $dto->toArray();
        $this->assertCount(2, $result);

        $fields = array_column($result, 'name');
        $this->assertContains('title', $fields);
        $this->assertContains('mastery_points', $fields);
    }

    public function testRatingsUpdateOnlyScenario(): void
    {
        // Test updating only ratings without changing anything else
        $ratings = [
            ['description' => 'Advanced', 'points' => 4.0],
            ['description' => 'Proficient', 'points' => 3.0],
            ['description' => 'Basic', 'points' => 2.0],
            ['description' => 'Novice', 'points' => 1.0],
        ];

        $dto = UpdateOutcomeDTO::updateRatings($ratings);

        $this->assertCount(4, $dto->ratings);
        $this->assertNull($dto->title);
        $this->assertNull($dto->masteryPoints);

        $result = $dto->toArray();
        $this->assertCount(8, $result); // 4 ratings × 2 fields each

        // Verify all ratings are properly formatted
        $ratingDescriptions = array_filter($result, fn ($item) => str_contains($item['name'], '[description]'));
        $ratingPoints = array_filter($result, fn ($item) => str_contains($item['name'], '[points]'));

        $this->assertCount(4, $ratingDescriptions);
        $this->assertCount(4, $ratingPoints);
    }

    public function testComplexUpdateScenario(): void
    {
        // Test a complex update with multiple fields including ratings
        $dto = new UpdateOutcomeDTO([
            'title' => 'Complex Updated Outcome',
            'description' => 'This outcome has been significantly updated',
            'masteryPoints' => 3.0,
            'calculationMethod' => 'decaying_average',
            'calculationInt' => 65,
            'ratings' => [
                ['description' => 'Mastery', 'points' => 4.0],
                ['description' => 'Proficient', 'points' => 3.0],
                ['description' => 'Developing', 'points' => 2.0],
            ],
        ]);

        $this->assertTrue($dto->validateCalculationMethod());
        $this->assertTrue($dto->validateRatings());

        $result = $dto->toArray();

        // Should have 5 base fields + 6 rating fields = 11 total
        $this->assertCount(11, $result);

        $apiResult = $dto->toApiArray();
        $this->assertIsArray($apiResult);
        $this->assertNotEmpty($apiResult);

        // Check that API fields are properly formatted with outcome[] prefix
        $apiFields = array_column($apiResult, 'name');
        $this->assertContains('outcome[title]', $apiFields);
    }
}
