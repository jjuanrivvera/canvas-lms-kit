<?php

declare(strict_types=1);

namespace Tests\Objects;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Objects\SubmissionVersion;

class SubmissionVersionTest extends TestCase
{
    public function testConstructorWithFullData(): void
    {
        $data = [
            'assignment_id' => 789,
            'assignment_name' => 'Quiz 1',
            'body' => 'Submission content',
            'current_grade' => '85',
            'current_graded_at' => '2025-01-15T15:00:00Z',
            'current_grader' => 'John Teacher',
            'grade_matches_current_submission' => true,
            'graded_at' => '2025-01-15T15:00:00Z',
            'grader' => 'John Teacher',
            'grader_id' => 456,
            'id' => 12345,
            'new_grade' => '85',
            'new_graded_at' => '2025-01-15T15:00:00Z',
            'new_grader' => 'John Teacher',
            'previous_grade' => '80',
            'previous_graded_at' => '2025-01-14T14:00:00Z',
            'previous_grader' => 'John Teacher',
            'score' => 85.0,
            'user_name' => 'Alice Student',
            'submission_type' => 'online_quiz',
            'url' => 'https://example.com',
            'user_id' => 123,
            'workflow_state' => 'graded'
        ];

        $version = new SubmissionVersion($data);

        $this->assertEquals(789, $version->assignmentId);
        $this->assertEquals('Quiz 1', $version->assignmentName);
        $this->assertEquals('Submission content', $version->body);
        $this->assertEquals('85', $version->currentGrade);
        $this->assertEquals('85', $version->newGrade);
        $this->assertEquals('80', $version->previousGrade);
        $this->assertEquals(85.0, $version->score);
        $this->assertEquals('graded', $version->workflowState);
        $this->assertTrue($version->gradeMatchesCurrentSubmission);
    }

    public function testConstructorWithEmptyData(): void
    {
        $version = new SubmissionVersion([]);

        $this->assertNull($version->assignmentId);
        $this->assertNull($version->assignmentName);
        $this->assertNull($version->currentGrade);
        $this->assertNull($version->newGrade);
        $this->assertNull($version->previousGrade);
    }

    public function testFromArray(): void
    {
        $data = [
            'assignment_id' => 123,
            'assignment_name' => 'Test Assignment',
            'new_grade' => '90',
            'previous_grade' => '85'
        ];

        $version = SubmissionVersion::fromArray($data);

        $this->assertInstanceOf(SubmissionVersion::class, $version);
        $this->assertEquals(123, $version->assignmentId);
        $this->assertEquals('Test Assignment', $version->assignmentName);
    }

    public function testHasGradeChange(): void
    {
        $versionWithChange = new SubmissionVersion([
            'new_grade' => '85',
            'previous_grade' => '80'
        ]);
        $this->assertTrue($versionWithChange->hasGradeChange());

        $versionWithoutChange = new SubmissionVersion([
            'new_grade' => '85',
            'previous_grade' => '85'
        ]);
        $this->assertFalse($versionWithoutChange->hasGradeChange());

        $versionMissingGrades = new SubmissionVersion([]);
        $this->assertFalse($versionMissingGrades->hasGradeChange());
    }

    public function testGetGrade(): void
    {
        // Test with new_grade
        $versionWithNewGrade = new SubmissionVersion([
            'new_grade' => '90',
            'current_grade' => '85'
        ]);
        $this->assertEquals('90', $versionWithNewGrade->getGrade());

        // Test without new_grade, fallback to current_grade
        $versionWithCurrentGrade = new SubmissionVersion([
            'current_grade' => '85'
        ]);
        $this->assertEquals('85', $versionWithCurrentGrade->getGrade());

        // Test with neither
        $versionNoGrade = new SubmissionVersion([]);
        $this->assertNull($versionNoGrade->getGrade());
    }

    public function testIsGraded(): void
    {
        $gradedVersion = new SubmissionVersion([
            'workflow_state' => 'graded'
        ]);
        $this->assertTrue($gradedVersion->isGraded());

        $ungradedVersion = new SubmissionVersion([
            'workflow_state' => 'pending'
        ]);
        $this->assertFalse($ungradedVersion->isGraded());

        $noStateVersion = new SubmissionVersion([]);
        $this->assertFalse($noStateVersion->isGraded());
    }

    public function testIsUnsubmitted(): void
    {
        $unsubmittedVersion = new SubmissionVersion([
            'workflow_state' => 'unsubmitted'
        ]);
        $this->assertTrue($unsubmittedVersion->isUnsubmitted());

        $submittedVersion = new SubmissionVersion([
            'workflow_state' => 'submitted'
        ]);
        $this->assertFalse($submittedVersion->isUnsubmitted());
    }

    public function testGetGradedTimestamp(): void
    {
        // Test with new_graded_at
        $versionWithNewTimestamp = new SubmissionVersion([
            'new_graded_at' => '2025-01-15T15:00:00Z',
            'graded_at' => '2025-01-14T14:00:00Z'
        ]);
        $this->assertEquals('2025-01-15T15:00:00Z', $versionWithNewTimestamp->getGradedTimestamp());

        // Test without new_graded_at, fallback to graded_at
        $versionWithTimestamp = new SubmissionVersion([
            'graded_at' => '2025-01-14T14:00:00Z'
        ]);
        $this->assertEquals('2025-01-14T14:00:00Z', $versionWithTimestamp->getGradedTimestamp());
    }

    public function testGetGraderName(): void
    {
        // Test with new_grader
        $versionWithNewGrader = new SubmissionVersion([
            'new_grader' => 'New Grader',
            'grader' => 'Old Grader'
        ]);
        $this->assertEquals('New Grader', $versionWithNewGrader->getGraderName());

        // Test without new_grader, fallback to grader
        $versionWithGrader = new SubmissionVersion([
            'grader' => 'Grader Name'
        ]);
        $this->assertEquals('Grader Name', $versionWithGrader->getGraderName());
    }

    public function testTypeCasting(): void
    {
        $data = [
            'assignment_id' => '789',
            'grader_id' => '456',
            'id' => '12345',
            'score' => '85.5',
            'user_id' => '123'
        ];

        $version = new SubmissionVersion($data);

        $this->assertSame(789, $version->assignmentId);
        $this->assertSame(456, $version->graderId);
        $this->assertSame(12345, $version->id);
        $this->assertSame(85.5, $version->score);
        $this->assertSame(123, $version->userId);
    }
}