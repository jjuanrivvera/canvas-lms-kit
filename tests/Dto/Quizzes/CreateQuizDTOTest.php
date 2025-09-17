<?php

declare(strict_types=1);

namespace Tests\Dto\Quizzes;

use CanvasLMS\Dto\Quizzes\CreateQuizDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Dto\Quizzes\CreateQuizDTO
 */
class CreateQuizDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateQuizDTO([]);

        $this->assertNull($dto->getTitle());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getQuizType());
        $this->assertNull($dto->getTimeLimit());
        $this->assertNull($dto->getPointsPossible());
        $this->assertNull($dto->getDueAt());
        $this->assertNull($dto->getPublished());
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'title' => 'Test Quiz',
            'description' => 'Test description',
            'quiz_type' => 'assignment',
            'assignment_group_id' => 456,
            'time_limit' => 60,
            'points_possible' => 100.0,
            'due_at' => '2024-12-31T23:59:59Z',
            'lock_at' => '2025-01-01T00:00:00Z',
            'unlock_at' => '2024-01-01T00:00:00Z',
            'published' => true,
            'shuffle_answers' => true,
            'show_correct_answers' => false,
            'allowed_attempts' => 3,
            'one_question_at_a_time' => true,
            'hide_results' => 'always',
            'ip_filter' => '192.168.1.0/24',
            'access_code' => 'secret123',
            'require_lockdown_browser' => true,
        ];

        $dto = new CreateQuizDTO($data);

        $this->assertEquals('Test Quiz', $dto->getTitle());
        $this->assertEquals('Test description', $dto->getDescription());
        $this->assertEquals('assignment', $dto->getQuizType());
        $this->assertEquals(456, $dto->getAssignmentGroupId());
        $this->assertEquals(60, $dto->getTimeLimit());
        $this->assertEquals(100.0, $dto->getPointsPossible());
        $this->assertEquals('2024-12-31T23:59:59Z', $dto->getDueAt());
        $this->assertEquals('2025-01-01T00:00:00Z', $dto->getLockAt());
        $this->assertEquals('2024-01-01T00:00:00Z', $dto->getUnlockAt());
        $this->assertTrue($dto->getPublished());
        $this->assertTrue($dto->getShuffleAnswers());
        $this->assertFalse($dto->getShowCorrectAnswers());
        $this->assertEquals(3, $dto->getAllowedAttempts());
        $this->assertTrue($dto->getOneQuestionAtATime());
        $this->assertEquals('always', $dto->getHideResults());
        $this->assertEquals('192.168.1.0/24', $dto->getIpFilter());
        $this->assertEquals('secret123', $dto->getAccessCode());
        $this->assertTrue($dto->getRequireLockdownBrowser());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new CreateQuizDTO([]);

        $dto->setTitle('Test Quiz');
        $this->assertEquals('Test Quiz', $dto->getTitle());

        $dto->setDescription('Test description');
        $this->assertEquals('Test description', $dto->getDescription());

        $dto->setQuizType('practice_quiz');
        $this->assertEquals('practice_quiz', $dto->getQuizType());

        $dto->setAssignmentGroupId(456);
        $this->assertEquals(456, $dto->getAssignmentGroupId());

        $dto->setTimeLimit(90);
        $this->assertEquals(90, $dto->getTimeLimit());

        $dto->setPointsPossible(150.0);
        $this->assertEquals(150.0, $dto->getPointsPossible());

        $dto->setDueAt('2024-12-31T23:59:59Z');
        $this->assertEquals('2024-12-31T23:59:59Z', $dto->getDueAt());

        $dto->setLockAt('2025-01-01T00:00:00Z');
        $this->assertEquals('2025-01-01T00:00:00Z', $dto->getLockAt());

        $dto->setUnlockAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $dto->getUnlockAt());

        $dto->setPublished(true);
        $this->assertTrue($dto->getPublished());

        $dto->setShuffleAnswers(false);
        $this->assertFalse($dto->getShuffleAnswers());

        $dto->setShowCorrectAnswers(true);
        $this->assertTrue($dto->getShowCorrectAnswers());

        $dto->setAllowedAttempts(-1);
        $this->assertEquals(-1, $dto->getAllowedAttempts());

        $dto->setOneQuestionAtATime(false);
        $this->assertFalse($dto->getOneQuestionAtATime());

        $dto->setHideResults('until_after_last_attempt');
        $this->assertEquals('until_after_last_attempt', $dto->getHideResults());

        $dto->setIpFilter('10.0.0.0/8');
        $this->assertEquals('10.0.0.0/8', $dto->getIpFilter());

        $dto->setAccessCode('password123');
        $this->assertEquals('password123', $dto->getAccessCode());

        $dto->setRequireLockdownBrowser(true);
        $this->assertTrue($dto->getRequireLockdownBrowser());

        $dto->setRequireLockdownBrowserForResults(false);
        $this->assertFalse($dto->getRequireLockdownBrowserForResults());

        $dto->setRequireLockdownBrowserMonitor(true);
        $this->assertTrue($dto->getRequireLockdownBrowserMonitor());

        $dto->setLockdownBrowserMonitorData('monitor_data');
        $this->assertEquals('monitor_data', $dto->getLockdownBrowserMonitorData());
    }
}
