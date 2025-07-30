<?php

namespace Tests\Dto\Rubrics;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Rubrics\CreateRubricAssessmentDTO;

class CreateRubricAssessmentDTOTest extends TestCase
{
    /**
     * Test DTO with basic assessment data
     */
    public function testBasicAssessmentData(): void
    {
        $dto = new CreateRubricAssessmentDTO();
        $dto->userId = 123;
        $dto->assessmentType = 'grading';
        $dto->criterionData = [
            'criterion_1' => ['points' => 8.5, 'comments' => 'Good work'],
            'criterion_2' => ['points' => 9.0, 'comments' => 'Excellent']
        ];

        $result = $dto->toApiArray();

        $this->assertIsArray($result);

        // Check user ID
        $this->assertContains([
            'name' => 'rubric_assessment[user_id]',
            'contents' => '123'
        ], $result);

        // Check assessment type
        $this->assertContains([
            'name' => 'rubric_assessment[assessment_type]',
            'contents' => 'grading'
        ], $result);

        // Check criterion data
        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][points]',
            'contents' => '8.5'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][comments]',
            'contents' => 'Good work'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_2][points]',
            'contents' => '9'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_2][comments]',
            'contents' => 'Excellent'
        ], $result);
    }

    /**
     * Test DTO with all fields
     */
    public function testWithAllFields(): void
    {
        $dto = new CreateRubricAssessmentDTO();
        $dto->userId = 456;
        $dto->assessmentType = 'peer_review';
        $dto->provisional = true;
        $dto->final = false;
        $dto->gradedAnonymously = true;
        $dto->criterionData = [
            'criterion_1' => [
                'points' => 10.0,
                'comments' => 'Outstanding',
                'rating_id' => 'rating_1',
                'save_comment' => true
            ]
        ];

        $result = $dto->toApiArray();

        // Check all fields
        $this->assertContains([
            'name' => 'rubric_assessment[user_id]',
            'contents' => '456'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[assessment_type]',
            'contents' => 'peer_review'
        ], $result);

        $this->assertContains([
            'name' => 'provisional',
            'contents' => 'true'
        ], $result);

        $this->assertContains([
            'name' => 'final',
            'contents' => 'false'
        ], $result);

        $this->assertContains([
            'name' => 'graded_anonymously',
            'contents' => 'true'
        ], $result);

        // Check complete criterion data
        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][points]',
            'contents' => '10'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][comments]',
            'contents' => 'Outstanding'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][rating_id]',
            'contents' => 'rating_1'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][save_comment]',
            'contents' => '1'
        ], $result);
    }

    /**
     * Test assessment types
     */
    public function testAssessmentTypes(): void
    {
        $types = ['grading', 'peer_review', 'provisional_grade'];

        foreach ($types as $type) {
            $dto = new CreateRubricAssessmentDTO();
            $dto->assessmentType = $type;

            $result = $dto->toApiArray();

            $this->assertContains([
                'name' => 'rubric_assessment[assessment_type]',
                'contents' => $type
            ], $result);
        }
    }

    /**
     * Test provisional grading flags
     */
    public function testProvisionalGradingFlags(): void
    {
        $dto = new CreateRubricAssessmentDTO();
        $dto->provisional = true;
        $dto->final = true;

        $result = $dto->toApiArray();

        $this->assertContains([
            'name' => 'provisional',
            'contents' => 'true'
        ], $result);

        $this->assertContains([
            'name' => 'final',
            'contents' => 'true'
        ], $result);
    }

    /**
     * Test criterion data with partial fields
     */
    public function testCriterionDataPartialFields(): void
    {
        $dto = new CreateRubricAssessmentDTO();
        $dto->criterionData = [
            'criterion_1' => ['points' => 5.0],
            'criterion_2' => ['comments' => 'Needs improvement'],
            'criterion_3' => ['rating_id' => 'rating_3'],
            'criterion_4' => ['save_comment' => false]
        ];

        $result = $dto->toApiArray();

        // Check only included fields are present
        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][points]',
            'contents' => '5'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_2][comments]',
            'contents' => 'Needs improvement'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_3][rating_id]',
            'contents' => 'rating_3'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_4][save_comment]',
            'contents' => '0'
        ], $result);
    }

    /**
     * Test empty DTO
     */
    public function testEmptyDTO(): void
    {
        $dto = new CreateRubricAssessmentDTO();
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test DTO with null values
     */
    public function testWithNullValues(): void
    {
        $dto = new CreateRubricAssessmentDTO();
        $dto->userId = 789;
        $dto->assessmentType = null;
        $dto->provisional = null;
        $dto->final = null;
        $dto->gradedAnonymously = null;
        $dto->criterionData = null;

        $result = $dto->toApiArray();

        // Only userId should be included
        $this->assertCount(1, $result);
        $this->assertEquals('rubric_assessment[user_id]', $result[0]['name']);
        $this->assertEquals('789', $result[0]['contents']);
    }

    /**
     * Test constructor with initial data
     */
    public function testConstructorWithData(): void
    {
        $data = [
            'userId' => 999,
            'assessmentType' => 'grading',
            'provisional' => false,
            'final' => false,
            'gradedAnonymously' => true,
            'criterionData' => [
                'criterion_1' => ['points' => 7.5]
            ]
        ];

        $dto = new CreateRubricAssessmentDTO($data);

        $this->assertEquals(999, $dto->userId);
        $this->assertEquals('grading', $dto->assessmentType);
        $this->assertFalse($dto->provisional);
        $this->assertFalse($dto->final);
        $this->assertTrue($dto->gradedAnonymously);
        $this->assertIsArray($dto->criterionData);
        $this->assertEquals(7.5, $dto->criterionData['criterion_1']['points']);
    }

    /**
     * Test boolean conversion
     */
    public function testBooleanConversion(): void
    {
        $dto = new CreateRubricAssessmentDTO();
        $dto->provisional = false;
        $dto->final = true;
        $dto->gradedAnonymously = false;

        $result = $dto->toApiArray();

        $this->assertContains([
            'name' => 'provisional',
            'contents' => 'false'
        ], $result);

        $this->assertContains([
            'name' => 'final',
            'contents' => 'true'
        ], $result);

        $this->assertContains([
            'name' => 'graded_anonymously',
            'contents' => 'false'
        ], $result);
    }

    /**
     * Test save_comment boolean conversion
     */
    public function testSaveCommentBooleanConversion(): void
    {
        $dto = new CreateRubricAssessmentDTO();
        $dto->criterionData = [
            'criterion_1' => ['save_comment' => true],
            'criterion_2' => ['save_comment' => false]
        ];

        $result = $dto->toApiArray();

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][save_comment]',
            'contents' => '1'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_2][save_comment]',
            'contents' => '0'
        ], $result);
    }

    /**
     * Test complex criterion data
     */
    public function testComplexCriterionData(): void
    {
        $dto = new CreateRubricAssessmentDTO();
        $dto->userId = 111;
        $dto->assessmentType = 'grading';
        $dto->criterionData = [
            'criterion_abc123' => [
                'points' => 8.75,
                'comments' => 'Well structured argument with clear examples',
                'rating_id' => 'rating_xyz789',
                'save_comment' => true
            ],
            'criterion_def456' => [
                'points' => 6.5,
                'comments' => 'Could use more detail in analysis'
            ],
            'criterion_ghi789' => [
                'rating_id' => 'rating_mno012'
            ]
        ];

        $result = $dto->toApiArray();

        // Verify all criterion data is properly formatted
        $criterionEntries = array_filter($result, function ($item) {
            return strpos($item['name'], 'rubric_assessment[criterion_') === 0;
        });

        // Should have 8 criterion entries (4 + 2 + 1 fields)
        $this->assertCount(7, $criterionEntries);

        // Verify specific entries
        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_abc123][points]',
            'contents' => '8.75'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_abc123][comments]',
            'contents' => 'Well structured argument with clear examples'
        ], $result);
    }
}