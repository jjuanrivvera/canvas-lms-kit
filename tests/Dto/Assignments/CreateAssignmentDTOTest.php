<?php

namespace Tests\Dto\Assignments;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Assignments\CreateAssignmentDTO;

/**
 * @covers \CanvasLMS\Dto\Assignments\CreateAssignmentDTO
 */
class CreateAssignmentDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateAssignmentDTO([]);

        $this->assertEquals('', $dto->getName());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getDueAt());
        $this->assertNull($dto->getPointsPossible());
        $this->assertNull($dto->getPublished());
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'name' => 'Test Assignment',
            'description' => 'Test description',
            'due_at' => '2024-12-31T23:59:59Z',
            'points_possible' => 100.0,
            'published' => true,
            'grading_type' => 'points',
            'submission_types' => ['online_text_entry'],
            'assignment_group_id' => 456
        ];

        $dto = new CreateAssignmentDTO($data);

        $this->assertEquals('Test Assignment', $dto->getName());
        $this->assertEquals('Test description', $dto->getDescription());
        $this->assertEquals('2024-12-31T23:59:59Z', $dto->getDueAt());
        $this->assertEquals(100.0, $dto->getPointsPossible());
        $this->assertTrue($dto->getPublished());
        $this->assertEquals('points', $dto->getGradingType());
        $this->assertEquals(['online_text_entry'], $dto->getSubmissionTypes());
        $this->assertEquals(456, $dto->getAssignmentGroupId());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new CreateAssignmentDTO([]);

        $dto->setName('Test Assignment');
        $this->assertEquals('Test Assignment', $dto->getName());

        $dto->setDescription('Test description');
        $this->assertEquals('Test description', $dto->getDescription());

        $dto->setDueAt('2024-12-31T23:59:59Z');
        $this->assertEquals('2024-12-31T23:59:59Z', $dto->getDueAt());

        $dto->setLockAt('2025-01-01T00:00:00Z');
        $this->assertEquals('2025-01-01T00:00:00Z', $dto->getLockAt());

        $dto->setUnlockAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $dto->getUnlockAt());

        $dto->setPointsPossible(100.0);
        $this->assertEquals(100.0, $dto->getPointsPossible());

        $dto->setGradingType('points');
        $this->assertEquals('points', $dto->getGradingType());

        $dto->setSubmissionTypes(['online_text_entry']);
        $this->assertEquals(['online_text_entry'], $dto->getSubmissionTypes());

        $dto->setAllowedExtensions(['pdf', 'doc']);
        $this->assertEquals(['pdf', 'doc'], $dto->getAllowedExtensions());

        $dto->setAllowedAttempts(3);
        $this->assertEquals(3, $dto->getAllowedAttempts());

        $dto->setPublished(true);
        $this->assertTrue($dto->getPublished());

        $dto->setAssignmentGroupId(456);
        $this->assertEquals(456, $dto->getAssignmentGroupId());

        $dto->setPosition(1);
        $this->assertEquals(1, $dto->getPosition());

        $dto->setOnlyVisibleToOverrides(true);
        $this->assertTrue($dto->getOnlyVisibleToOverrides());

        $dto->setPeerReviews(true);
        $this->assertTrue($dto->getPeerReviews());

        $dto->setAutomaticPeerReviews(true);
        $this->assertTrue($dto->getAutomaticPeerReviews());

        $dto->setPeerReviewsAssignAt('2024-12-30T23:59:59Z');
        $this->assertEquals('2024-12-30T23:59:59Z', $dto->getPeerReviewsAssignAt());

        $dto->setPeerReviewCount(2);
        $this->assertEquals(2, $dto->getPeerReviewCount());

        $dto->setAnonymousPeerReviews(true);
        $this->assertTrue($dto->getAnonymousPeerReviews());

        $dto->setAnonymousGrading(true);
        $this->assertTrue($dto->getAnonymousGrading());

        $dto->setGradersAnonymousToGraders(true);
        $this->assertTrue($dto->getGradersAnonymousToGraders());

        $dto->setGradersAnonymousToStudents(true);
        $this->assertTrue($dto->getGradersAnonymousToStudents());

        $dto->setAnonymousInstructorAnnotations(true);
        $this->assertTrue($dto->getAnonymousInstructorAnnotations());

        $dto->setModeratedGrading(true);
        $this->assertTrue($dto->getModeratedGrading());

        $dto->setGraderCount(3);
        $this->assertEquals(3, $dto->getGraderCount());

        $dto->setGraderCommentsVisibleToGraders(true);
        $this->assertTrue($dto->getGraderCommentsVisibleToGraders());

        $dto->setFinalGraderId(123);
        $this->assertEquals(123, $dto->getFinalGraderId());

        $dto->setGraderNamesVisibleToFinalGrader(true);
        $this->assertTrue($dto->getGraderNamesVisibleToFinalGrader());

        $dto->setGroupCategoryId(789);
        $this->assertEquals(789, $dto->getGroupCategoryId());

        $dto->setGradeGroupStudentsIndividually(true);
        $this->assertTrue($dto->getGradeGroupStudentsIndividually());

        $dto->setExternalToolTagAttributes(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $dto->getExternalToolTagAttributes());

        $dto->setIntegrationData(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $dto->getIntegrationData());

        $dto->setIntegrationId('test-integration');
        $this->assertEquals('test-integration', $dto->getIntegrationId());

        $dto->setOmitFromFinalGrade(true);
        $this->assertTrue($dto->getOmitFromFinalGrade());

        $dto->setHideInGradebook(true);
        $this->assertTrue($dto->getHideInGradebook());
    }

    public function testToArray(): void
    {
        $data = [
            'name' => 'Test Assignment',
            'description' => 'Test description',
            'due_at' => '2024-12-31T23:59:59Z',
            'points_possible' => 100.0,
            'published' => true,
            'grading_type' => 'points',
            'submission_types' => ['online_text_entry'],
            'assignment_group_id' => 456
        ];

        $dto = new CreateAssignmentDTO($data);
        $result = $dto->toArray();

        $this->assertIsArray($result);
        $this->assertEquals('Test Assignment', $result['name']);
        $this->assertEquals('Test description', $result['description']);
        $this->assertEquals('2024-12-31T23:59:59Z', $result['dueAt']);
        $this->assertEquals(100.0, $result['pointsPossible']);
        $this->assertTrue($result['published']);
        $this->assertEquals('points', $result['gradingType']);
        $this->assertEquals(['online_text_entry'], $result['submissionTypes']);
        $this->assertEquals(456, $result['assignmentGroupId']);
    }

    public function testToApiArray(): void
    {
        $data = [
            'name' => 'Test Assignment',
            'description' => 'Test description',
            'due_at' => '2024-12-31T23:59:59Z',
            'points_possible' => 100.0,
            'published' => true
        ];

        $dto = new CreateAssignmentDTO($data);
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
            'name' => 'Test Assignment',
            'submission_types' => ['online_text_entry', 'online_upload'],
            'allowed_extensions' => ['pdf', 'doc', 'docx']
        ];

        $dto = new CreateAssignmentDTO($data);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);

        $resultKeys = array_column($result, 'name');
        $this->assertContains('assignment[submission_types][]', $resultKeys);
        $this->assertContains('assignment[allowed_extensions][]', $resultKeys);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $data = [
            'name' => 'Test Assignment',
            'description' => null,
            'due_at' => null,
            'points_possible' => 100.0
        ];

        $dto = new CreateAssignmentDTO($data);
        $result = $dto->toApiArray();

        $resultKeys = array_column($result, 'name');
        $this->assertContains('assignment[name]', $resultKeys);
        $this->assertNotContains('assignment[description]', $resultKeys);
        $this->assertNotContains('assignment[due_at]', $resultKeys);
        $this->assertContains('assignment[points_possible]', $resultKeys);
    }
}