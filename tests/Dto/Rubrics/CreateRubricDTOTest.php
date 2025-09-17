<?php

declare(strict_types=1);

namespace Tests\Dto\Rubrics;

use CanvasLMS\Dto\Rubrics\CreateRubricDTO;
use PHPUnit\Framework\TestCase;

class CreateRubricDTOTest extends TestCase
{
    /**
     * Test DTO with basic rubric data
     */
    public function testBasicRubricData(): void
    {
        $dto = new CreateRubricDTO();
        $dto->title = 'Essay Rubric';
        $dto->freeFormCriterionComments = true;
        $dto->hideScoreTotal = false;

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Check title
        $this->assertEquals('rubric[title]', $result[0]['name']);
        $this->assertEquals('Essay Rubric', $result[0]['contents']);

        // Check free form comments
        $this->assertEquals('rubric[free_form_criterion_comments]', $result[1]['name']);
        $this->assertEquals('1', $result[1]['contents']);

        // Check hide score total
        $this->assertEquals('rubric[hide_score_total]', $result[2]['name']);
        $this->assertEquals('0', $result[2]['contents']);
    }

    /**
     * Test DTO with criteria including ratings
     */
    public function testWithCriteriaAndRatings(): void
    {
        $dto = new CreateRubricDTO();
        $dto->title = 'Project Rubric';
        $dto->criteria = [
            [
                'id' => 'criterion_1',
                'description' => 'Content Knowledge',
                'long_description' => 'Demonstrates understanding of subject matter',
                'points' => 20,
                'criterion_use_range' => true,
                'ratings' => [
                    [
                        'id' => 'rating_1',
                        'description' => 'Excellent',
                        'long_description' => 'Exceeds expectations',
                        'points' => 20,
                    ],
                    [
                        'id' => 'rating_2',
                        'description' => 'Good',
                        'points' => 15,
                    ],
                ],
            ],
            [
                'description' => 'Writing Style',
                'points' => 10,
                'ratings' => [
                    ['description' => 'Clear', 'points' => 10],
                    ['description' => 'Adequate', 'points' => 7],
                ],
            ],
        ];

        $result = $dto->toApiArray();

        // Find criteria-related entries
        $criteriaEntries = array_filter($result, function ($item) {
            return strpos($item['name'], 'rubric[criteria]') === 0;
        });

        // Check first criterion
        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][description]',
            'contents' => 'Content Knowledge',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][long_description]',
            'contents' => 'Demonstrates understanding of subject matter',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][points]',
            'contents' => '20',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][criterion_use_range]',
            'contents' => '1',
        ], $result);

        // Check first criterion ratings
        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][ratings][rating_1][description]',
            'contents' => 'Excellent',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][ratings][rating_1][long_description]',
            'contents' => 'Exceeds expectations',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][ratings][rating_1][points]',
            'contents' => '20',
        ], $result);

        // Check second criterion (uses index as key when no ID provided)
        $this->assertContains([
            'name' => 'rubric[criteria][1][description]',
            'contents' => 'Writing Style',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][1][points]',
            'contents' => '10',
        ], $result);
    }

    /**
     * Test DTO with rubric association ID
     */
    public function testWithRubricAssociationId(): void
    {
        $dto = new CreateRubricDTO();
        $dto->title = 'Quick Rubric';
        $dto->rubricAssociationId = 123;

        $result = $dto->toApiArray();

        $this->assertContains([
            'name' => 'rubric_association_id',
            'contents' => '123',
        ], $result);
    }

    /**
     * Test DTO with association data
     */
    public function testWithAssociationData(): void
    {
        $dto = new CreateRubricDTO();
        $dto->title = 'Assignment Rubric';
        $dto->association = [
            'associationId' => 456,
            'associationType' => 'Assignment',
            'useForGrading' => true,
            'hideScoreTotal' => false,
            'hidePoints' => true,
        ];

        $result = $dto->toApiArray();

        // Check association fields
        $this->assertContains([
            'name' => 'rubric_association[association_id]',
            'contents' => '456',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[association_type]',
            'contents' => 'Assignment',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[use_for_grading]',
            'contents' => '1',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_score_total]',
            'contents' => '0',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_points]',
            'contents' => '1',
        ], $result);
    }

    /**
     * Test DTO with empty data
     */
    public function testEmptyDTO(): void
    {
        $dto = new CreateRubricDTO();
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test DTO with null values
     */
    public function testWithNullValues(): void
    {
        $dto = new CreateRubricDTO();
        $dto->title = 'Test Rubric';
        $dto->freeFormCriterionComments = null;
        $dto->hideScoreTotal = null;
        $dto->criteria = null;
        $dto->rubricAssociationId = null;
        $dto->association = null;

        $result = $dto->toApiArray();

        $this->assertCount(1, $result);
        $this->assertEquals('rubric[title]', $result[0]['name']);
        $this->assertEquals('Test Rubric', $result[0]['contents']);
    }

    /**
     * Test DTO constructor with initial data
     */
    public function testConstructorWithData(): void
    {
        $data = [
            'title' => 'Initial Rubric',
            'freeFormCriterionComments' => true,
            'hideScoreTotal' => false,
        ];

        $dto = new CreateRubricDTO($data);

        $this->assertEquals('Initial Rubric', $dto->title);
        $this->assertTrue($dto->freeFormCriterionComments);
        $this->assertFalse($dto->hideScoreTotal);
    }

    /**
     * Test criteria without ID uses index
     */
    public function testCriteriaWithoutIdUsesIndex(): void
    {
        $dto = new CreateRubricDTO();
        $dto->criteria = [
            ['description' => 'First', 'points' => 5],
            ['description' => 'Second', 'points' => 10],
            ['description' => 'Third', 'points' => 15],
        ];

        $result = $dto->toApiArray();

        // Check that indices are used as keys
        $this->assertContains([
            'name' => 'rubric[criteria][0][description]',
            'contents' => 'First',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][1][description]',
            'contents' => 'Second',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][2][description]',
            'contents' => 'Third',
        ], $result);
    }

    /**
     * Test ratings without ID uses index
     */
    public function testRatingsWithoutIdUsesIndex(): void
    {
        $dto = new CreateRubricDTO();
        $dto->criteria = [
            [
                'id' => 'criterion_1',
                'description' => 'Test Criterion',
                'ratings' => [
                    ['description' => 'High', 'points' => 3],
                    ['description' => 'Medium', 'points' => 2],
                    ['description' => 'Low', 'points' => 1],
                ],
            ],
        ];

        $result = $dto->toApiArray();

        // Check that indices are used as keys for ratings
        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][ratings][0][description]',
            'contents' => 'High',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][ratings][1][description]',
            'contents' => 'Medium',
        ], $result);

        $this->assertContains([
            'name' => 'rubric[criteria][criterion_1][ratings][2][description]',
            'contents' => 'Low',
        ], $result);
    }

    /**
     * Test boolean conversion for association data
     */
    public function testBooleanConversionInAssociation(): void
    {
        $dto = new CreateRubricDTO();
        $dto->association = [
            'useForGrading' => false,
            'hideScoreTotal' => true,
            'hidePoints' => false,
            'hideOutcomeResults' => true,
        ];

        $result = $dto->toApiArray();

        $this->assertContains([
            'name' => 'rubric_association[use_for_grading]',
            'contents' => '0',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_score_total]',
            'contents' => '1',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_points]',
            'contents' => '0',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_outcome_results]',
            'contents' => '1',
        ], $result);
    }
}
