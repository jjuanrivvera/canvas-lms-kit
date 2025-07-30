<?php

namespace Tests\Dto\Rubrics;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Rubrics\UpdateRubricDTO;

class UpdateRubricDTOTest extends TestCase
{
    /**
     * Test DTO with basic update data
     */
    public function testBasicUpdateData(): void
    {
        $dto = new UpdateRubricDTO();
        $dto->title = 'Updated Essay Rubric';
        $dto->freeFormCriterionComments = false;
        $dto->skipUpdatingPointsPossible = true;
        $dto->hideScoreTotal = true;

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertCount(4, $result);

        // Check title
        $this->assertEquals('rubric[title]', $result[0]['name']);
        $this->assertEquals('Updated Essay Rubric', $result[0]['contents']);

        // Check free form comments
        $this->assertEquals('rubric[free_form_criterion_comments]', $result[1]['name']);
        $this->assertEquals('0', $result[1]['contents']);

        // Check skip updating points
        $this->assertEquals('rubric[skip_updating_points_possible]', $result[2]['name']);
        $this->assertEquals('1', $result[2]['contents']);

        // Check hide score total
        $this->assertEquals('rubric[hide_score_total]', $result[3]['name']);
        $this->assertEquals('1', $result[3]['contents']);
    }

    /**
     * Test DTO with updated criteria
     */
    public function testWithUpdatedCriteria(): void
    {
        $dto = new UpdateRubricDTO();
        $dto->title = 'Modified Rubric';
        $dto->criteria = [
            [
                'id' => 'existing_criterion_1',
                'description' => 'Updated Content Knowledge',
                'long_description' => 'Updated understanding description',
                'points' => 25,
                'criterion_use_range' => false,
                'ratings' => [
                    [
                        'id' => 'existing_rating_1',
                        'description' => 'Outstanding',
                        'points' => 25
                    ],
                    [
                        'description' => 'New Rating',
                        'points' => 20
                    ]
                ]
            ]
        ];

        $result = $dto->toApiArray();

        // Check criterion updates
        $this->assertContains([
            'name' => 'rubric[criteria][existing_criterion_1][description]',
            'contents' => 'Updated Content Knowledge'
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][existing_criterion_1][long_description]',
            'contents' => 'Updated understanding description'
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][existing_criterion_1][points]',
            'contents' => '25'
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][existing_criterion_1][criterion_use_range]',
            'contents' => '0'
        ], $result);

        // Check rating updates
        $this->assertContains([
            'name' => 'rubric[criteria][existing_criterion_1][ratings][existing_rating_1][description]',
            'contents' => 'Outstanding'
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][existing_criterion_1][ratings][existing_rating_1][points]',
            'contents' => '25'
        ], $result);

        // New rating uses index
        $this->assertContains([
            'name' => 'rubric[criteria][existing_criterion_1][ratings][1][description]',
            'contents' => 'New Rating'
        ], $result);
    }

    /**
     * Test DTO with rubric association ID
     */
    public function testWithRubricAssociationId(): void
    {
        $dto = new UpdateRubricDTO();
        $dto->title = 'Updated with Association';
        $dto->rubricAssociationId = 456;

        $result = $dto->toApiArray();

        $this->assertContains([
            'name' => 'rubric_association_id',
            'contents' => '456'
        ], $result);
    }

    /**
     * Test DTO with association update data
     */
    public function testWithAssociationUpdateData(): void
    {
        $dto = new UpdateRubricDTO();
        $dto->title = 'Update Association Rubric';
        $dto->association = [
            'useForGrading' => false,
            'hideScoreTotal' => true,
            'hidePoints' => false,
            'purpose' => 'bookmark'
        ];

        $result = $dto->toApiArray();

        // Check association fields
        $this->assertContains([
            'name' => 'rubric_association[use_for_grading]',
            'contents' => '0'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_score_total]',
            'contents' => '1'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_points]',
            'contents' => '0'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[purpose]',
            'contents' => 'bookmark'
        ], $result);
    }

    /**
     * Test DTO with null values
     */
    public function testWithNullValues(): void
    {
        $dto = new UpdateRubricDTO();
        $dto->title = null;
        $dto->freeFormCriterionComments = null;
        $dto->skipUpdatingPointsPossible = null;
        $dto->hideScoreTotal = null;
        $dto->criteria = null;
        $dto->rubricAssociationId = null;
        $dto->association = null;

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test DTO constructor with initial data
     */
    public function testConstructorWithData(): void
    {
        $data = [
            'title' => 'Initial Update Title',
            'freeFormCriterionComments' => true,
            'skipUpdatingPointsPossible' => false,
            'hideScoreTotal' => true
        ];

        $dto = new UpdateRubricDTO($data);

        $this->assertEquals('Initial Update Title', $dto->title);
        $this->assertTrue($dto->freeFormCriterionComments);
        $this->assertFalse($dto->skipUpdatingPointsPossible);
        $this->assertTrue($dto->hideScoreTotal);
    }

    /**
     * Test partial criteria update
     */
    public function testPartialCriteriaUpdate(): void
    {
        $dto = new UpdateRubricDTO();
        $dto->criteria = [
            [
                'id' => 'criterion_to_update',
                'points' => 30
                // Only updating points, not description or ratings
            ]
        ];

        $result = $dto->toApiArray();

        // Should only contain the points update
        $criteriaEntries = array_filter($result, function ($item) {
            return strpos($item['name'], 'rubric[criteria]') === 0;
        });

        $this->assertCount(1, $criteriaEntries);
        $this->assertContains([
            'name' => 'rubric[criteria][criterion_to_update][points]',
            'contents' => '30'
        ], $result);
    }

    /**
     * Test association data with null values excluded
     */
    public function testAssociationDataWithNullValues(): void
    {
        $dto = new UpdateRubricDTO();
        $dto->association = [
            'useForGrading' => true,
            'hideScoreTotal' => null,
            'hidePoints' => false,
            'purpose' => null
        ];

        $result = $dto->toApiArray();

        // Check that only non-null values are included
        $associationEntries = array_filter($result, function ($item) {
            return strpos($item['name'], 'rubric_association[') === 0;
        });

        $this->assertCount(2, $associationEntries);

        $this->assertContains([
            'name' => 'rubric_association[use_for_grading]',
            'contents' => '1'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_points]',
            'contents' => '0'
        ], $result);

        // Null values should not be included
        foreach ($result as $item) {
            $this->assertNotEquals('rubric_association[hide_score_total]', $item['name']);
            $this->assertNotEquals('rubric_association[purpose]', $item['name']);
        }
    }

    /**
     * Test snake_case conversion for association keys
     */
    public function testSnakeCaseConversionForAssociation(): void
    {
        $dto = new UpdateRubricDTO();
        $dto->association = [
            'useForGrading' => true,
            'hideScoreTotal' => false,
            'hideOutcomeResults' => true
        ];

        $result = $dto->toApiArray();

        // Check snake_case conversion
        $this->assertContains([
            'name' => 'rubric_association[use_for_grading]',
            'contents' => '1'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_score_total]',
            'contents' => '0'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_outcome_results]',
            'contents' => '1'
        ], $result);
    }
}