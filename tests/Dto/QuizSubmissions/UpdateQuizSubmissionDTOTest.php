<?php

declare(strict_types=1);

namespace Tests\Dto\QuizSubmissions;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\QuizSubmissions\UpdateQuizSubmissionDTO;

/**
 * @covers \CanvasLMS\Dto\QuizSubmissions\UpdateQuizSubmissionDTO
 */
class UpdateQuizSubmissionDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdateQuizSubmissionDTO([]);

        $this->assertNull($dto->getAttempt());
        $this->assertNull($dto->getFudgePoints());
        $this->assertNull($dto->getQuizSubmissions());
        $this->assertNull($dto->getQuestions());
        $this->assertNull($dto->getValidationToken());
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'attempt' => 2,
            'fudge_points' => 5.5,
            'quiz_submissions' => [
                ['attempt' => 2, 'fudge_points' => 5.5]
            ],
            'questions' => [
                'question_1' => ['score' => 10.0, 'comment' => 'Perfect answer']
            ],
            'validation_token' => 'abc123xyz'
        ];

        $dto = new UpdateQuizSubmissionDTO($data);

        $this->assertEquals(2, $dto->getAttempt());
        $this->assertEquals(5.5, $dto->getFudgePoints());
        $this->assertEquals([['attempt' => 2, 'fudge_points' => 5.5]], $dto->getQuizSubmissions());
        $this->assertEquals(['question_1' => ['score' => 10.0, 'comment' => 'Perfect answer']], $dto->getQuestions());
        $this->assertEquals('abc123xyz', $dto->getValidationToken());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new UpdateQuizSubmissionDTO([]);

        $dto->setAttempt(3);
        $this->assertEquals(3, $dto->getAttempt());

        $dto->setFudgePoints(2.5);
        $this->assertEquals(2.5, $dto->getFudgePoints());

        $quizSubmissions = [
            ['attempt' => 3, 'fudge_points' => 2.5]
        ];
        $dto->setQuizSubmissions($quizSubmissions);
        $this->assertEquals($quizSubmissions, $dto->getQuizSubmissions());

        $questions = [
            'question_2' => ['score' => 7.5, 'comment' => 'Good work']
        ];
        $dto->setQuestions($questions);
        $this->assertEquals($questions, $dto->getQuestions());

        $dto->setValidationToken('token456');
        $this->assertEquals('token456', $dto->getValidationToken());

        // Test null values
        $dto->setAttempt(null);
        $this->assertNull($dto->getAttempt());

        $dto->setFudgePoints(null);
        $this->assertNull($dto->getFudgePoints());

        $dto->setQuizSubmissions(null);
        $this->assertNull($dto->getQuizSubmissions());

        $dto->setQuestions(null);
        $this->assertNull($dto->getQuestions());

        $dto->setValidationToken(null);
        $this->assertNull($dto->getValidationToken());
    }

    public function testToApiArray(): void
    {
        $dto = new UpdateQuizSubmissionDTO([
            'attempt' => 2,
            'fudge_points' => 3.0,
            'validation_token' => 'abc123'
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertNotEmpty($apiArray);

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz_submission[attempt]', $names);
        $this->assertContains('quiz_submission[fudge_points]', $names);
        $this->assertContains('quiz_submission[validation_token]', $names);
    }

    public function testApiPropertyName(): void
    {
        $dto = new UpdateQuizSubmissionDTO(['attempt' => 1]);
        $apiArray = $dto->toApiArray();

        // Verify that all entries use the 'quiz_submission' prefix
        foreach ($apiArray as $entry) {
            $this->assertStringStartsWith('quiz_submission[', $entry['name']);
        }
    }

    public function testNullValuesAreFilteredOut(): void
    {
        $dto = new UpdateQuizSubmissionDTO([
            'attempt' => 2,
            'fudge_points' => null,
            'validation_token' => 'abc123',
            'questions' => null
        ]);

        $apiArray = $dto->toApiArray();

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz_submission[attempt]', $names);
        $this->assertContains('quiz_submission[validation_token]', $names);
        $this->assertNotContains('quiz_submission[fudge_points]', $names);
        $this->assertNotContains('quiz_submission[questions]', $names);
    }

    public function testArrayValuesConvertedCorrectly(): void
    {
        $quizSubmissions = [
            ['attempt' => 1, 'fudge_points' => 2.0],
            ['attempt' => 2, 'fudge_points' => 3.0]
        ];

        $dto = new UpdateQuizSubmissionDTO([
            'quiz_submissions' => $quizSubmissions
        ]);

        $apiArray = $dto->toApiArray();

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz_submission[quiz_submissions][]', $names);

        // Should have multiple entries for the array
        $quizSubmissionEntries = array_filter($apiArray, function($entry) {
            return $entry['name'] === 'quiz_submission[quiz_submissions][]';
        });

        $this->assertCount(2, $quizSubmissionEntries);
    }

    public function testNumericValues(): void
    {
        $dto = new UpdateQuizSubmissionDTO([
            'attempt' => 0,
            'fudge_points' => 0.0
        ]);

        $apiArray = $dto->toApiArray();

        $entryMap = [];
        foreach ($apiArray as $entry) {
            $entryMap[$entry['name']] = $entry['contents'];
        }

        $this->assertEquals('0', $entryMap['quiz_submission[attempt]']);
        $this->assertEquals('0', $entryMap['quiz_submission[fudge_points]']);
    }

    public function testNegativeValues(): void
    {
        $dto = new UpdateQuizSubmissionDTO([
            'fudge_points' => -2.5
        ]);

        $apiArray = $dto->toApiArray();

        $entryMap = [];
        foreach ($apiArray as $entry) {
            $entryMap[$entry['name']] = $entry['contents'];
        }

        $this->assertEquals('-2.5', $entryMap['quiz_submission[fudge_points]']);
    }

    public function testPartialUpdate(): void
    {
        // Test that we can update just a few fields
        $dto = new UpdateQuizSubmissionDTO([
            'fudge_points' => 1.5
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray); // 1 actual property (apiPropertyName excluded)

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz_submission[fudge_points]', $names);

        // Should not contain other fields
        $this->assertNotContains('quiz_submission[attempt]', $names);
        $this->assertNotContains('quiz_submission[validation_token]', $names);
        $this->assertNotContains('quiz_submission[questions]', $names);
    }

    public function testComplexQuestionsArray(): void
    {
        $questions = [
            'question_1' => [
                'score' => 8.5,
                'comment' => 'Good answer with minor issues'
            ],
            'question_2' => [
                'score' => 10.0,
                'comment' => 'Perfect!'
            ]
        ];

        $dto = new UpdateQuizSubmissionDTO([
            'questions' => $questions
        ]);

        $apiArray = $dto->toApiArray();

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz_submission[questions][]', $names);

        // Should have multiple entries for the questions array
        $questionEntries = array_filter($apiArray, function($entry) {
            return $entry['name'] === 'quiz_submission[questions][]';
        });

        $this->assertCount(2, $questionEntries);
    }

    public function testEmptyArrayValues(): void
    {
        $dto = new UpdateQuizSubmissionDTO([
            'quiz_submissions' => [],
            'questions' => []
        ]);

        $apiArray = $dto->toApiArray();

        $names = array_column($apiArray, 'name');
        
        // Empty arrays should not generate any entries
        $this->assertNotContains('quiz_submission[quiz_submissions][]', $names);
        $this->assertNotContains('quiz_submission[questions][]', $names);
    }
}