<?php

namespace Tests\Dto\Assignments;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Assignments\UpdateAssignmentDTO;

/**
 * @covers \CanvasLMS\Dto\Assignments\UpdateAssignmentDTO
 */
class UpdateAssignmentDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdateAssignmentDTO([]);

        $this->assertNull($dto->getName());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getDueAt());
        $this->assertNull($dto->getPointsPossible());
        $this->assertNull($dto->getPublished());
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'name' => 'Updated Assignment',
            'description' => 'Updated description',
            'due_at' => '2024-12-31T23:59:59Z',
            'points_possible' => 150.0,
            'published' => false,
            'grading_type' => 'percent',
            'submission_types' => ['online_upload'],
            'assignment_group_id' => 789
        ];

        $dto = new UpdateAssignmentDTO($data);

        $this->assertEquals('Updated Assignment', $dto->getName());
        $this->assertEquals('Updated description', $dto->getDescription());
        $this->assertEquals('2024-12-31T23:59:59Z', $dto->getDueAt());
        $this->assertEquals(150.0, $dto->getPointsPossible());
        $this->assertFalse($dto->getPublished());
        $this->assertEquals('percent', $dto->getGradingType());
        $this->assertEquals(['online_upload'], $dto->getSubmissionTypes());
        $this->assertEquals(789, $dto->getAssignmentGroupId());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new UpdateAssignmentDTO([]);

        $dto->setName('Updated Assignment');
        $this->assertEquals('Updated Assignment', $dto->getName());

        $dto->setDescription('Updated description');
        $this->assertEquals('Updated description', $dto->getDescription());

        $dto->setDueAt('2024-12-31T23:59:59Z');
        $this->assertEquals('2024-12-31T23:59:59Z', $dto->getDueAt());

        $dto->setLockAt('2025-01-01T00:00:00Z');
        $this->assertEquals('2025-01-01T00:00:00Z', $dto->getLockAt());

        $dto->setUnlockAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $dto->getUnlockAt());

        $dto->setPointsPossible(150.0);
        $this->assertEquals(150.0, $dto->getPointsPossible());

        $dto->setGradingType('percent');
        $this->assertEquals('percent', $dto->getGradingType());

        $dto->setSubmissionTypes(['online_upload']);
        $this->assertEquals(['online_upload'], $dto->getSubmissionTypes());

        $dto->setAllowedExtensions(['pdf', 'jpg']);
        $this->assertEquals(['pdf', 'jpg'], $dto->getAllowedExtensions());

        $dto->setAllowedAttempts(5);
        $this->assertEquals(5, $dto->getAllowedAttempts());

        $dto->setPublished(false);
        $this->assertFalse($dto->getPublished());

        $dto->setAssignmentGroupId(789);
        $this->assertEquals(789, $dto->getAssignmentGroupId());

        $dto->setPosition(2);
        $this->assertEquals(2, $dto->getPosition());

        $dto->setOnlyVisibleToOverrides(false);
        $this->assertFalse($dto->getOnlyVisibleToOverrides());

        $dto->setPeerReviews(false);
        $this->assertFalse($dto->getPeerReviews());

        $dto->setAutomaticPeerReviews(false);
        $this->assertFalse($dto->getAutomaticPeerReviews());

        $dto->setPeerReviewsAssignAt('2024-12-30T23:59:59Z');
        $this->assertEquals('2024-12-30T23:59:59Z', $dto->getPeerReviewsAssignAt());

        $dto->setPeerReviewCount(1);
        $this->assertEquals(1, $dto->getPeerReviewCount());

        $dto->setAnonymousPeerReviews(false);
        $this->assertFalse($dto->getAnonymousPeerReviews());

        $dto->setAnonymousGrading(false);
        $this->assertFalse($dto->getAnonymousGrading());

        $dto->setGradersAnonymousToGraders(false);
        $this->assertFalse($dto->getGradersAnonymousToGraders());

        $dto->setGradersAnonymousToStudents(false);
        $this->assertFalse($dto->getGradersAnonymousToStudents());

        $dto->setAnonymousInstructorAnnotations(false);
        $this->assertFalse($dto->getAnonymousInstructorAnnotations());

        $dto->setModeratedGrading(false);
        $this->assertFalse($dto->getModeratedGrading());

        $dto->setGraderCount(1);
        $this->assertEquals(1, $dto->getGraderCount());

        $dto->setGraderCommentsVisibleToGraders(false);
        $this->assertFalse($dto->getGraderCommentsVisibleToGraders());

        $dto->setFinalGraderId(456);
        $this->assertEquals(456, $dto->getFinalGraderId());

        $dto->setGraderNamesVisibleToFinalGrader(false);
        $this->assertFalse($dto->getGraderNamesVisibleToFinalGrader());

        $dto->setGroupCategoryId(null);
        $this->assertNull($dto->getGroupCategoryId());

        $dto->setGradeGroupStudentsIndividually(false);
        $this->assertFalse($dto->getGradeGroupStudentsIndividually());

        $dto->setExternalToolTagAttributes(['updated' => 'value']);
        $this->assertEquals(['updated' => 'value'], $dto->getExternalToolTagAttributes());

        $dto->setIntegrationData(['updated' => 'data']);
        $this->assertEquals(['updated' => 'data'], $dto->getIntegrationData());

        $dto->setIntegrationId('updated-integration');
        $this->assertEquals('updated-integration', $dto->getIntegrationId());

        $dto->setOmitFromFinalGrade(false);
        $this->assertFalse($dto->getOmitFromFinalGrade());

        $dto->setHideInGradebook(false);
        $this->assertFalse($dto->getHideInGradebook());
    }

    public function testToArray(): void
    {
        $data = [
            'name' => 'Updated Assignment',
            'description' => 'Updated description',
            'due_at' => '2024-12-31T23:59:59Z',
            'points_possible' => 150.0,
            'published' => false,
            'grading_type' => 'percent',
            'submission_types' => ['online_upload'],
            'assignment_group_id' => 789
        ];

        $dto = new UpdateAssignmentDTO($data);
        $result = $dto->toArray();

        $this->assertIsArray($result);
        $this->assertEquals('Updated Assignment', $result['name']);
        $this->assertEquals('Updated description', $result['description']);
        $this->assertEquals('2024-12-31T23:59:59Z', $result['dueAt']);
        $this->assertEquals(150.0, $result['pointsPossible']);
        $this->assertFalse($result['published']); // false values are now preserved
        $this->assertEquals('percent', $result['gradingType']);
        $this->assertEquals(['online_upload'], $result['submissionTypes']);
        $this->assertEquals(789, $result['assignmentGroupId']);
    }

    public function testToApiArray(): void
    {
        $data = [
            'name' => 'Updated Assignment',
            'description' => 'Updated description',
            'due_at' => '2024-12-31T23:59:59Z',
            'points_possible' => 150.0,
            'published' => false
        ];

        $dto = new UpdateAssignmentDTO($data);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $resultKeys = array_column($result, 'name');
        $this->assertContains('assignment[name]', $resultKeys);
        $this->assertContains('assignment[description]', $resultKeys);
        $this->assertContains('assignment[due_at]', $resultKeys);
        $this->assertContains('assignment[points_possible]', $resultKeys);
        $this->assertContains('assignment[published]', $resultKeys);
    }

    public function testToApiArrayWithArrayValues(): void
    {
        $data = [
            'name' => 'Updated Assignment',
            'submission_types' => ['online_upload', 'media_recording'],
            'allowed_extensions' => ['jpg', 'png', 'gif']
        ];

        $dto = new UpdateAssignmentDTO($data);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);

        $resultKeys = array_column($result, 'name');
        $this->assertContains('assignment[submission_types][]', $resultKeys);
        $this->assertContains('assignment[allowed_extensions][]', $resultKeys);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $data = [
            'name' => 'Updated Assignment',
            'description' => null,
            'due_at' => null,
            'points_possible' => 150.0
        ];

        $dto = new UpdateAssignmentDTO($data);
        $result = $dto->toApiArray();

        $resultKeys = array_column($result, 'name');
        $this->assertContains('assignment[name]', $resultKeys);
        $this->assertNotContains('assignment[description]', $resultKeys);
        $this->assertNotContains('assignment[due_at]', $resultKeys);
        $this->assertContains('assignment[points_possible]', $resultKeys);
    }

    public function testPartialUpdate(): void
    {
        $data = [
            'name' => 'Updated Assignment Name Only'
        ];

        $dto = new UpdateAssignmentDTO($data);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $resultKeys = array_column($result, 'name');
        $this->assertContains('assignment[name]', $resultKeys);
        $this->assertNotContains('assignment[description]', $resultKeys);
        $this->assertNotContains('assignment[points_possible]', $resultKeys);
    }
}