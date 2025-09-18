<?php

declare(strict_types=1);

namespace Tests\Dto\Rubrics;

use CanvasLMS\Dto\Rubrics\UpdateRubricAssessmentDTO;
use PHPUnit\Framework\TestCase;

class UpdateRubricAssessmentDTOTest extends TestCase
{
    /**
     * Test DTO with basic update data
     */
    public function testBasicUpdateData(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->userId = 123;
        $dto->assessmentType = 'grading';
        $dto->criterionData = [
            'criterion_1' => ['points' => 9.5, 'comments' => 'Updated comment'],
        ];

        $result = $dto->toApiArray();

        $this->assertIsArray($result);

        // Check user ID
        $this->assertContains([
            'name' => 'rubric_assessment[user_id]',
            'contents' => '123',
        ], $result);

        // Check assessment type
        $this->assertContains([
            'name' => 'rubric_assessment[assessment_type]',
            'contents' => 'grading',
        ], $result);

        // Check criterion data
        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][points]',
            'contents' => '9.5',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][comments]',
            'contents' => 'Updated comment',
        ], $result);
    }

    /**
     * Test DTO with all fields
     */
    public function testWithAllFields(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->userId = 456;
        $dto->assessmentType = 'peer_review';
        $dto->provisional = true;
        $dto->final = true;
        $dto->gradedAnonymously = false;
        $dto->criterionData = [
            'criterion_1' => [
                'points' => 10.0,
                'comments' => 'Perfect score',
                'rating_id' => 'rating_updated',
                'save_comment' => true,
            ],
        ];

        $result = $dto->toApiArray();

        // Check all fields
        $this->assertContains([
            'name' => 'rubric_assessment[user_id]',
            'contents' => '456',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[assessment_type]',
            'contents' => 'peer_review',
        ], $result);

        $this->assertContains([
            'name' => 'provisional',
            'contents' => 'true',
        ], $result);

        $this->assertContains([
            'name' => 'final',
            'contents' => 'true',
        ], $result);

        $this->assertContains([
            'name' => 'graded_anonymously',
            'contents' => 'false',
        ], $result);

        // Check complete criterion data
        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][points]',
            'contents' => '10',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][comments]',
            'contents' => 'Perfect score',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][rating_id]',
            'contents' => 'rating_updated',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][save_comment]',
            'contents' => '1',
        ], $result);
    }

    /**
     * Test partial update
     */
    public function testPartialUpdate(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->criterionData = [
            'criterion_1' => ['points' => 7.0],
        ];

        $result = $dto->toApiArray();

        $this->assertCount(1, $result);
        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][points]',
            'contents' => '7',
        ], $result);
    }

    /**
     * Test provisional grade update
     */
    public function testProvisionalGradeUpdate(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->provisional = true;
        $dto->final = false;

        $result = $dto->toApiArray();

        $this->assertContains([
            'name' => 'provisional',
            'contents' => 'true',
        ], $result);

        $this->assertContains([
            'name' => 'final',
            'contents' => 'false',
        ], $result);
    }

    /**
     * Test empty DTO
     */
    public function testEmptyDTO(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test DTO with null values
     */
    public function testWithNullValues(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->userId = null;
        $dto->assessmentType = null;
        $dto->provisional = null;
        $dto->final = null;
        $dto->gradedAnonymously = null;
        $dto->criterionData = null;

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test constructor with initial data
     */
    public function testConstructorWithData(): void
    {
        $data = [
            'userId' => 789,
            'assessmentType' => 'provisional_grade',
            'provisional' => true,
            'final' => false,
            'gradedAnonymously' => true,
            'criterionData' => [
                'criterion_1' => ['points' => 8.0],
            ],
        ];

        $dto = new UpdateRubricAssessmentDTO($data);

        $this->assertEquals(789, $dto->userId);
        $this->assertEquals('provisional_grade', $dto->assessmentType);
        $this->assertTrue($dto->provisional);
        $this->assertFalse($dto->final);
        $this->assertTrue($dto->gradedAnonymously);
        $this->assertIsArray($dto->criterionData);
        $this->assertEquals(8.0, $dto->criterionData['criterion_1']['points']);
    }

    /**
     * Test updating only comments
     */
    public function testUpdateOnlyComments(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->criterionData = [
            'criterion_1' => ['comments' => 'New feedback'],
            'criterion_2' => ['comments' => 'Improved work'],
        ];

        $result = $dto->toApiArray();

        $this->assertCount(2, $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][comments]',
            'contents' => 'New feedback',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_2][comments]',
            'contents' => 'Improved work',
        ], $result);
    }

    /**
     * Test updating rating selections
     */
    public function testUpdateRatingSelections(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->criterionData = [
            'criterion_1' => ['rating_id' => 'new_rating_1'],
            'criterion_2' => ['rating_id' => 'new_rating_2'],
            'criterion_3' => ['rating_id' => 'new_rating_3'],
        ];

        $result = $dto->toApiArray();

        $this->assertCount(3, $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][rating_id]',
            'contents' => 'new_rating_1',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_2][rating_id]',
            'contents' => 'new_rating_2',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_3][rating_id]',
            'contents' => 'new_rating_3',
        ], $result);
    }

    /**
     * Test mixed criterion updates
     */
    public function testMixedCriterionUpdates(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->criterionData = [
            'criterion_1' => [
                'points' => 8.5,
                'comments' => 'Good improvement',
            ],
            'criterion_2' => [
                'rating_id' => 'rating_excellent',
            ],
            'criterion_3' => [
                'comments' => 'Keep up the good work',
                'save_comment' => true,
            ],
            'criterion_4' => [
                'points' => 6.0,
                'rating_id' => 'rating_satisfactory',
                'save_comment' => false,
            ],
        ];

        $result = $dto->toApiArray();

        // Verify all updates are included
        $criterionEntries = array_filter($result, function ($item) {
            return strpos($item['name'], 'rubric_assessment[criterion_') === 0;
        });

        // Should have 8 total criterion entries:
        // criterion_1: points + comments = 2
        // criterion_2: rating_id = 1
        // criterion_3: comments + save_comment = 2
        // criterion_4: points + rating_id + save_comment = 3
        // Total: 8
        $this->assertCount(8, $criterionEntries);

        // Verify specific complex update
        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_4][points]',
            'contents' => '6',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_4][rating_id]',
            'contents' => 'rating_satisfactory',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_4][save_comment]',
            'contents' => '0',
        ], $result);
    }

    /**
     * Test boolean conversion
     */
    public function testBooleanConversion(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->provisional = false;
        $dto->final = true;
        $dto->gradedAnonymously = false;

        $result = $dto->toApiArray();

        $this->assertContains([
            'name' => 'provisional',
            'contents' => 'false',
        ], $result);

        $this->assertContains([
            'name' => 'final',
            'contents' => 'true',
        ], $result);

        $this->assertContains([
            'name' => 'graded_anonymously',
            'contents' => 'false',
        ], $result);
    }

    /**
     * Test save_comment boolean conversion
     */
    public function testSaveCommentBooleanConversion(): void
    {
        $dto = new UpdateRubricAssessmentDTO();
        $dto->criterionData = [
            'criterion_1' => ['save_comment' => true],
            'criterion_2' => ['save_comment' => false],
        ];

        $result = $dto->toApiArray();

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_1][save_comment]',
            'contents' => '1',
        ], $result);

        $this->assertContains([
            'name' => 'rubric_assessment[criterion_criterion_2][save_comment]',
            'contents' => '0',
        ], $result);
    }
}
