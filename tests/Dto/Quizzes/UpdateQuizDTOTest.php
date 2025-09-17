<?php

declare(strict_types=1);

namespace Tests\Dto\Quizzes;

use CanvasLMS\Dto\Quizzes\UpdateQuizDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Dto\Quizzes\UpdateQuizDTO
 */
class UpdateQuizDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdateQuizDTO([]);

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
            'title' => 'Updated Quiz',
            'description' => 'Updated description',
            'quiz_type' => 'survey',
            'assignment_group_id' => 789,
            'time_limit' => 120,
            'points_possible' => 200.0,
            'due_at' => '2024-12-25T23:59:59Z',
            'lock_at' => '2024-12-26T00:00:00Z',
            'unlock_at' => '2024-12-20T00:00:00Z',
            'published' => false,
            'shuffle_answers' => false,
            'show_correct_answers' => true,
            'allowed_attempts' => -1,
            'one_question_at_a_time' => false,
            'hide_results' => 'until_after_last_attempt',
            'ip_filter' => '172.16.0.0/12',
            'access_code' => 'newpassword',
            'require_lockdown_browser' => false,
        ];

        $dto = new UpdateQuizDTO($data);

        $this->assertEquals('Updated Quiz', $dto->getTitle());
        $this->assertEquals('Updated description', $dto->getDescription());
        $this->assertEquals('survey', $dto->getQuizType());
        $this->assertEquals(789, $dto->getAssignmentGroupId());
        $this->assertEquals(120, $dto->getTimeLimit());
        $this->assertEquals(200.0, $dto->getPointsPossible());
        $this->assertEquals('2024-12-25T23:59:59Z', $dto->getDueAt());
        $this->assertEquals('2024-12-26T00:00:00Z', $dto->getLockAt());
        $this->assertEquals('2024-12-20T00:00:00Z', $dto->getUnlockAt());
        $this->assertFalse($dto->getPublished());
        $this->assertFalse($dto->getShuffleAnswers());
        $this->assertTrue($dto->getShowCorrectAnswers());
        $this->assertEquals(-1, $dto->getAllowedAttempts());
        $this->assertFalse($dto->getOneQuestionAtATime());
        $this->assertEquals('until_after_last_attempt', $dto->getHideResults());
        $this->assertEquals('172.16.0.0/12', $dto->getIpFilter());
        $this->assertEquals('newpassword', $dto->getAccessCode());
        $this->assertFalse($dto->getRequireLockdownBrowser());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new UpdateQuizDTO([]);

        $dto->setTitle('Updated Quiz Title');
        $this->assertEquals('Updated Quiz Title', $dto->getTitle());

        $dto->setDescription('Updated description');
        $this->assertEquals('Updated description', $dto->getDescription());

        $dto->setQuizType('graded_survey');
        $this->assertEquals('graded_survey', $dto->getQuizType());

        $dto->setAssignmentGroupId(999);
        $this->assertEquals(999, $dto->getAssignmentGroupId());

        $dto->setTimeLimit(45);
        $this->assertEquals(45, $dto->getTimeLimit());

        $dto->setPointsPossible(75.5);
        $this->assertEquals(75.5, $dto->getPointsPossible());

        $dto->setDueAt('2024-11-30T23:59:59Z');
        $this->assertEquals('2024-11-30T23:59:59Z', $dto->getDueAt());

        $dto->setLockAt('2024-12-01T00:00:00Z');
        $this->assertEquals('2024-12-01T00:00:00Z', $dto->getLockAt());

        $dto->setUnlockAt('2024-11-20T00:00:00Z');
        $this->assertEquals('2024-11-20T00:00:00Z', $dto->getUnlockAt());

        $dto->setPublished(false);
        $this->assertFalse($dto->getPublished());

        $dto->setShuffleAnswers(true);
        $this->assertTrue($dto->getShuffleAnswers());

        $dto->setShowCorrectAnswers(false);
        $this->assertFalse($dto->getShowCorrectAnswers());

        $dto->setAllowedAttempts(5);
        $this->assertEquals(5, $dto->getAllowedAttempts());

        $dto->setOneQuestionAtATime(true);
        $this->assertTrue($dto->getOneQuestionAtATime());

        $dto->setHideResults(null);
        $this->assertNull($dto->getHideResults());

        $dto->setIpFilter('');
        $this->assertEquals('', $dto->getIpFilter());

        $dto->setAccessCode('updated_password');
        $this->assertEquals('updated_password', $dto->getAccessCode());

        $dto->setRequireLockdownBrowser(false);
        $this->assertFalse($dto->getRequireLockdownBrowser());

        $dto->setRequireLockdownBrowserForResults(true);
        $this->assertTrue($dto->getRequireLockdownBrowserForResults());

        $dto->setRequireLockdownBrowserMonitor(false);
        $this->assertFalse($dto->getRequireLockdownBrowserMonitor());

        $dto->setLockdownBrowserMonitorData('updated_monitor_data');
        $this->assertEquals('updated_monitor_data', $dto->getLockdownBrowserMonitorData());
    }

    public function testToApiArray(): void
    {
        $dto = new UpdateQuizDTO([
            'title' => 'Updated Quiz',
            'quiz_type' => 'practice_quiz',
            'time_limit' => 90,
            'published' => false,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertNotEmpty($apiArray);

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz[title]', $names);
        $this->assertContains('quiz[quiz_type]', $names);
        $this->assertContains('quiz[time_limit]', $names);
        $this->assertContains('quiz[published]', $names);

        // Find the published entry
        $publishedEntry = null;
        foreach ($apiArray as $entry) {
            if ($entry['name'] === 'quiz[published]') {
                $publishedEntry = $entry;
                break;
            }
        }
        $this->assertNotNull($publishedEntry);
        $this->assertEquals('0', $publishedEntry['contents']); // false converts to '0'
    }

    public function testApiPropertyName(): void
    {
        $dto = new UpdateQuizDTO(['title' => 'Test']);
        $apiArray = $dto->toApiArray();

        // Verify that all entries use the 'quiz' prefix
        foreach ($apiArray as $entry) {
            $this->assertStringStartsWith('quiz[', $entry['name']);
        }
    }

    public function testNullValuesAreFilteredOut(): void
    {
        $dto = new UpdateQuizDTO([
            'title' => 'Updated Quiz',
            'description' => null,
            'time_limit' => 90,
            'points_possible' => null,
        ]);

        $apiArray = $dto->toApiArray();

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz[title]', $names);
        $this->assertContains('quiz[time_limit]', $names);
        $this->assertNotContains('quiz[description]', $names);
        $this->assertNotContains('quiz[points_possible]', $names);
    }

    public function testPartialUpdate(): void
    {
        // Test that we can update just a few fields
        $dto = new UpdateQuizDTO([
            'published' => true,
            'time_limit' => 30,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertCount(2, $apiArray); // 2 actual properties (apiPropertyName excluded)

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz[published]', $names);
        $this->assertContains('quiz[time_limit]', $names);

        // Should not contain other fields
        $this->assertNotContains('quiz[title]', $names);
        $this->assertNotContains('quiz[description]', $names);
        $this->assertNotContains('quiz[quiz_type]', $names);
    }

    public function testEmptyStringValues(): void
    {
        $dto = new UpdateQuizDTO([
            'title' => '',
            'description' => '',
            'access_code' => '',
            'ip_filter' => '',
        ]);

        $apiArray = $dto->toApiArray();

        // Empty strings should be included (they're not null)
        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz[title]', $names);
        $this->assertContains('quiz[description]', $names);
        $this->assertContains('quiz[access_code]', $names);
        $this->assertContains('quiz[ip_filter]', $names);
    }

    public function testBooleanValuesConvertedCorrectly(): void
    {
        $dto = new UpdateQuizDTO([
            'published' => true,
            'shuffle_answers' => false,
            'one_question_at_a_time' => true,
            'require_lockdown_browser' => false,
        ]);

        $apiArray = $dto->toApiArray();

        $entryMap = [];
        foreach ($apiArray as $entry) {
            $entryMap[$entry['name']] = $entry['contents'];
        }

        $this->assertEquals('1', $entryMap['quiz[published]']);
        $this->assertEquals('0', $entryMap['quiz[shuffle_answers]']);
        $this->assertEquals('1', $entryMap['quiz[one_question_at_a_time]']);
        $this->assertEquals('0', $entryMap['quiz[require_lockdown_browser]']);
    }

    public function testNumericValues(): void
    {
        $dto = new UpdateQuizDTO([
            'time_limit' => 0,
            'points_possible' => 0.0,
            'allowed_attempts' => -1,
            'assignment_group_id' => 123,
        ]);

        $apiArray = $dto->toApiArray();

        $entryMap = [];
        foreach ($apiArray as $entry) {
            $entryMap[$entry['name']] = $entry['contents'];
        }

        $this->assertEquals('0', $entryMap['quiz[time_limit]']);
        $this->assertEquals('0', $entryMap['quiz[points_possible]']);
        $this->assertEquals('-1', $entryMap['quiz[allowed_attempts]']);
        $this->assertEquals('123', $entryMap['quiz[assignment_group_id]']);
    }
}
