<?php

namespace Tests\Dto\Submissions;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Submissions\UpdateSubmissionDTO;

/**
 * @covers \CanvasLMS\Dto\Submissions\UpdateSubmissionDTO
 */
class UpdateSubmissionDTOTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'posted_grade' => '85',
            'excuse' => false,
            'rubric_assessment' => [
                'criterion_1' => ['points' => 8, 'comments' => 'Good work'],
                'criterion_2' => ['points' => 9, 'comments' => 'Excellent']
            ],
            'comment' => 'Great improvement since last submission',
            'group_comment' => true,
            'media_comment_id' => 'feedback123',
            'media_comment_type' => 'audio',
            'extra_attempts' => 2,
            'late' => false,
            'points_deducted' => 0.0
        ];

        $dto = new UpdateSubmissionDTO($data);

        $this->assertEquals('85', $dto->getPostedGrade());
        $this->assertFalse($dto->getExcuse());
        $this->assertEquals([
            'criterion_1' => ['points' => 8, 'comments' => 'Good work'],
            'criterion_2' => ['points' => 9, 'comments' => 'Excellent']
        ], $dto->getRubricAssessment());
        $this->assertEquals('Great improvement since last submission', $dto->getComment());
        $this->assertTrue($dto->getGroupComment());
        $this->assertEquals('feedback123', $dto->getMediaCommentId());
        $this->assertEquals('audio', $dto->getMediaCommentType());
        $this->assertEquals(2, $dto->getExtraAttempts());
        $this->assertFalse($dto->getLate());
        $this->assertEquals(0.0, $dto->getPointsDeducted());
    }

    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdateSubmissionDTO([]);

        $this->assertNull($dto->getPostedGrade());
        $this->assertNull($dto->getExcuse());
        $this->assertNull($dto->getRubricAssessment());
        $this->assertNull($dto->getComment());
        $this->assertNull($dto->getGroupComment());
        $this->assertNull($dto->getMediaCommentId());
        $this->assertNull($dto->getMediaCommentType());
        $this->assertNull($dto->getExtraAttempts());
        $this->assertNull($dto->getLate());
        $this->assertNull($dto->getPointsDeducted());
    }

    public function testGettersAndSetters(): void
    {
        $dto = new UpdateSubmissionDTO([]);

        // Test posted grade
        $dto->setPostedGrade('92');
        $this->assertEquals('92', $dto->getPostedGrade());

        // Test excuse
        $dto->setExcuse(true);
        $this->assertTrue($dto->getExcuse());

        // Test rubric assessment
        $rubricAssessment = [
            'criterion_1' => ['points' => 10, 'comments' => 'Perfect']
        ];
        $dto->setRubricAssessment($rubricAssessment);
        $this->assertEquals($rubricAssessment, $dto->getRubricAssessment());

        // Test comment
        $dto->setComment('Excellent work overall');
        $this->assertEquals('Excellent work overall', $dto->getComment());

        // Test group comment
        $dto->setGroupComment(false);
        $this->assertFalse($dto->getGroupComment());

        // Test media comment ID
        $dto->setMediaCommentId('video789');
        $this->assertEquals('video789', $dto->getMediaCommentId());

        // Test media comment type
        $dto->setMediaCommentType('video');
        $this->assertEquals('video', $dto->getMediaCommentType());

        // Test extra attempts
        $dto->setExtraAttempts(3);
        $this->assertEquals(3, $dto->getExtraAttempts());

        // Test late status
        $dto->setLate(true);
        $this->assertTrue($dto->getLate());

        // Test points deducted
        $dto->setPointsDeducted(5.5);
        $this->assertEquals(5.5, $dto->getPointsDeducted());
    }

    public function testSettersWithNullValues(): void
    {
        $dto = new UpdateSubmissionDTO([
            'posted_grade' => '80',
            'comment' => 'Initial comment'
        ]);

        // Set all values to null
        $dto->setPostedGrade(null);
        $dto->setExcuse(null);
        $dto->setRubricAssessment(null);
        $dto->setComment(null);
        $dto->setGroupComment(null);
        $dto->setMediaCommentId(null);
        $dto->setMediaCommentType(null);
        $dto->setExtraAttempts(null);
        $dto->setLate(null);
        $dto->setPointsDeducted(null);

        $this->assertNull($dto->getPostedGrade());
        $this->assertNull($dto->getExcuse());
        $this->assertNull($dto->getRubricAssessment());
        $this->assertNull($dto->getComment());
        $this->assertNull($dto->getGroupComment());
        $this->assertNull($dto->getMediaCommentId());
        $this->assertNull($dto->getMediaCommentType());
        $this->assertNull($dto->getExtraAttempts());
        $this->assertNull($dto->getLate());
        $this->assertNull($dto->getPointsDeducted());
    }

    public function testToApiArrayForGrading(): void
    {
        $dto = new UpdateSubmissionDTO([
            'posted_grade' => '88',
            'comment' => 'Good work with minor improvements needed'
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'submission[posted_grade]', 'contents' => '88'], $apiArray);
        $this->assertContains(['name' => 'submission[comment]', 'contents' => 'Good work with minor improvements needed'], $apiArray);
    }

    public function testToApiArrayForExcusing(): void
    {
        $dto = new UpdateSubmissionDTO([
            'excuse' => true,
            'comment' => 'Excused due to illness'
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'submission[excuse]', 'contents' => true], $apiArray);
        $this->assertContains(['name' => 'submission[comment]', 'contents' => 'Excused due to illness'], $apiArray);
    }

    public function testToApiArrayForRubricAssessment(): void
    {
        $dto = new UpdateSubmissionDTO([
            'posted_grade' => '90',
            'rubric_assessment' => [
                'criterion_1' => ['points' => 9, 'comments' => 'Excellent analysis'],
                'criterion_2' => ['points' => 8, 'comments' => 'Good structure']
            ]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'submission[posted_grade]', 'contents' => '90'], $apiArray);

        // Check that rubric assessment is handled as an array
        $hasRubricAssessment = false;
        foreach ($apiArray as $item) {
            if ($item['name'] === 'submission[rubric_assessment][]') {
                $hasRubricAssessment = true;
                break;
            }
        }
        $this->assertTrue($hasRubricAssessment, 'Rubric assessment should be present in API array');
    }

    public function testToApiArrayForMediaFeedback(): void
    {
        $dto = new UpdateSubmissionDTO([
            'posted_grade' => '87',
            'media_comment_id' => 'audio_feedback_123',
            'media_comment_type' => 'audio',
            'comment' => 'See audio feedback for detailed comments'
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'submission[posted_grade]', 'contents' => '87'], $apiArray);
        $this->assertContains(['name' => 'submission[media_comment_id]', 'contents' => 'audio_feedback_123'], $apiArray);
        $this->assertContains(['name' => 'submission[media_comment_type]', 'contents' => 'audio'], $apiArray);
        $this->assertContains(['name' => 'submission[comment]', 'contents' => 'See audio feedback for detailed comments'], $apiArray);
    }

    public function testToApiArrayForLatePenalty(): void
    {
        $dto = new UpdateSubmissionDTO([
            'posted_grade' => '80',
            'late' => true,
            'points_deducted' => 10.0,
            'comment' => 'Grade reduced due to late submission'
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'submission[posted_grade]', 'contents' => '80'], $apiArray);
        $this->assertContains(['name' => 'submission[late]', 'contents' => true], $apiArray);
        $this->assertContains(['name' => 'submission[points_deducted]', 'contents' => 10.0], $apiArray);
        $this->assertContains(['name' => 'submission[comment]', 'contents' => 'Grade reduced due to late submission'], $apiArray);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $dto = new UpdateSubmissionDTO([
            'posted_grade' => '85',
            'comment' => 'Good work'
            // All other fields are null
        ]);

        $apiArray = $dto->toApiArray();

        // Should only contain non-null values
        $fieldNames = array_column($apiArray, 'name');
        $this->assertContains('submission[posted_grade]', $fieldNames);
        $this->assertContains('submission[comment]', $fieldNames);
        $this->assertNotContains('submission[excuse]', $fieldNames);
        $this->assertNotContains('submission[rubric_assessment]', $fieldNames);
        $this->assertNotContains('submission[media_comment_id]', $fieldNames);
        $this->assertNotContains('submission[late]', $fieldNames);
    }

    public function testToArrayIncludesAllSetValues(): void
    {
        $dto = new UpdateSubmissionDTO([
            'posted_grade' => '90',
            'excuse' => false,
            'comment' => 'Excellent work',
            'extra_attempts' => 1
        ]);

        $array = $dto->toArray();

        $this->assertEquals('90', $array['postedGrade']);
        $this->assertFalse($array['excuse']);
        $this->assertEquals('Excellent work', $array['comment']);
        $this->assertEquals(1, $array['extraAttempts']);
        $this->assertArrayNotHasKey('rubricAssessment', $array);
        $this->assertArrayNotHasKey('late', $array);
    }

    public function testSnakeCaseToApiPropertyConversion(): void
    {
        $dto = new UpdateSubmissionDTO([
            'posted_grade' => '85',
            'media_comment_id' => 'test123',
            'media_comment_type' => 'video',
            'group_comment' => true,
            'extra_attempts' => 2,
            'points_deducted' => 5.0
        ]);

        $apiArray = $dto->toApiArray();
        $fieldNames = array_column($apiArray, 'name');

        // Verify snake_case conversion in API property names
        $this->assertContains('submission[posted_grade]', $fieldNames);
        $this->assertContains('submission[media_comment_id]', $fieldNames);
        $this->assertContains('submission[media_comment_type]', $fieldNames);
        $this->assertContains('submission[group_comment]', $fieldNames);
        $this->assertContains('submission[extra_attempts]', $fieldNames);
        $this->assertContains('submission[points_deducted]', $fieldNames);
    }

    public function testBooleanValuesInApiArray(): void
    {
        $dto = new UpdateSubmissionDTO([
            'excuse' => true,
            'group_comment' => false,
            'late' => true
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'submission[excuse]', 'contents' => true], $apiArray);
        $this->assertContains(['name' => 'submission[group_comment]', 'contents' => false], $apiArray);
        $this->assertContains(['name' => 'submission[late]', 'contents' => true], $apiArray);
    }

    public function testNumericValuesInApiArray(): void
    {
        $dto = new UpdateSubmissionDTO([
            'extra_attempts' => 3,
            'points_deducted' => 12.5
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'submission[extra_attempts]', 'contents' => 3], $apiArray);
        $this->assertContains(['name' => 'submission[points_deducted]', 'contents' => 12.5], $apiArray);
    }
}