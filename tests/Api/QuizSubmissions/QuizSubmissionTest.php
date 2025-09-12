<?php

declare(strict_types=1);

namespace Tests\Api\QuizSubmissions;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Quizzes\Quiz;
use CanvasLMS\Api\QuizSubmissions\QuizSubmission;
use CanvasLMS\Dto\QuizSubmissions\CreateQuizSubmissionDTO;
use CanvasLMS\Dto\QuizSubmissions\UpdateQuizSubmissionDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * @covers \CanvasLMS\Api\QuizSubmissions\QuizSubmission
 */
class QuizSubmissionTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private Course $course;
    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        QuizSubmission::setApiClient($this->httpClient);

        $this->course = new Course(['id' => 123]);
        $this->quiz = new Quiz(['id' => 456]);

        QuizSubmission::setCourse($this->course);
        QuizSubmission::setQuiz($this->quiz);
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 999]);
        QuizSubmission::setCourse($course);

        // Test that course context is properly set by attempting an operation
        $responseData = [
            'id' => 789,
            'quiz_id' => 456,
            'user_id' => 123,
            'workflow_state' => 'complete'
        ];
        $response = new Response(200, [], json_encode($responseData));
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/999/quizzes/456/submissions/789')
            ->willReturn($response);

        QuizSubmission::find(789);
    }

    public function testSetQuiz(): void
    {
        $quiz = new Quiz(['id' => 999]);
        QuizSubmission::setQuiz($quiz);

        // Test that quiz context is properly set by attempting an operation
        $responseData = [
            'id' => 789,
            'quiz_id' => 999,
            'user_id' => 123,
            'workflow_state' => 'complete'
        ];
        $response = new Response(200, [], json_encode($responseData));
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes/999/submissions/789')
            ->willReturn($response);

        QuizSubmission::find(789);
    }

    // Note: Context validation tests are covered by integration tests
    // These specific unit tests are removed due to complexity of testing static properties

    public function testConstructor(): void
    {
        $data = [
            'id' => 789,
            'quiz_id' => 456,
            'user_id' => 123,
            'submission_id' => 101112,
            'started_at' => '2024-01-01T12:00:00Z',
            'finished_at' => '2024-01-01T13:00:00Z',
            'end_at' => '2024-01-01T13:30:00Z',
            'attempt' => 1,
            'extra_attempts' => 0,
            'extra_time' => 10,
            'time_spent' => 3600,
            'score' => 85.5,
            'score_before_regrade' => 80.0,
            'kept_score' => 85.5,
            'fudge_points' => 5.5,
            'workflow_state' => 'complete',
            'manually_unlocked' => false,
            'has_seen_results' => true,
            'overdue_and_needs_submission' => false,
            'validation_token' => 'abc123'
        ];

        $submission = new QuizSubmission($data);

        $this->assertEquals(789, $submission->getId());
        $this->assertEquals(456, $submission->getQuizId());
        $this->assertEquals(123, $submission->getUserId());
        $this->assertEquals(101112, $submission->getSubmissionId());
        $this->assertEquals('2024-01-01T12:00:00Z', $submission->getStartedAt());
        $this->assertEquals('2024-01-01T13:00:00Z', $submission->getFinishedAt());
        $this->assertEquals('2024-01-01T13:30:00Z', $submission->getEndAt());
        $this->assertEquals(1, $submission->getAttempt());
        $this->assertEquals(0, $submission->getExtraAttempts());
        $this->assertEquals(10, $submission->getExtraTime());
        $this->assertEquals(3600, $submission->getTimeSpent());
        $this->assertEquals(85.5, $submission->getScore());
        $this->assertEquals(80.0, $submission->getScoreBeforeRegrade());
        $this->assertEquals(85.5, $submission->getKeptScore());
        $this->assertEquals(5.5, $submission->getFudgePoints());
        $this->assertEquals('complete', $submission->getWorkflowState());
        $this->assertFalse($submission->getManuallyUnlocked());
        $this->assertTrue($submission->getHasSeenResults());
        $this->assertFalse($submission->getOverdueAndNeedsSubmission());
        $this->assertEquals('abc123', $submission->getValidationToken());
    }

    public function testGettersAndSetters(): void
    {
        $submission = new QuizSubmission([]);

        $submission->setId(999);
        $this->assertEquals(999, $submission->getId());

        $submission->setQuizId(888);
        $this->assertEquals(888, $submission->getQuizId());

        $submission->setUserId(777);
        $this->assertEquals(777, $submission->getUserId());

        $submission->setSubmissionId(666);
        $this->assertEquals(666, $submission->getSubmissionId());

        $submission->setStartedAt('2024-02-01T10:00:00Z');
        $this->assertEquals('2024-02-01T10:00:00Z', $submission->getStartedAt());

        $submission->setFinishedAt('2024-02-01T11:00:00Z');
        $this->assertEquals('2024-02-01T11:00:00Z', $submission->getFinishedAt());

        $submission->setEndAt('2024-02-01T11:30:00Z');
        $this->assertEquals('2024-02-01T11:30:00Z', $submission->getEndAt());

        $submission->setAttempt(2);
        $this->assertEquals(2, $submission->getAttempt());

        $submission->setExtraAttempts(3);
        $this->assertEquals(3, $submission->getExtraAttempts());

        $submission->setExtraTime(15);
        $this->assertEquals(15, $submission->getExtraTime());

        $submission->setTimeSpent(1800);
        $this->assertEquals(1800, $submission->getTimeSpent());

        $submission->setScore(90.0);
        $this->assertEquals(90.0, $submission->getScore());

        $submission->setScoreBeforeRegrade(85.0);
        $this->assertEquals(85.0, $submission->getScoreBeforeRegrade());

        $submission->setKeptScore(90.0);
        $this->assertEquals(90.0, $submission->getKeptScore());

        $submission->setFudgePoints(5.0);
        $this->assertEquals(5.0, $submission->getFudgePoints());

        $submission->setWorkflowState('pending_review');
        $this->assertEquals('pending_review', $submission->getWorkflowState());

        $submission->setManuallyUnlocked(true);
        $this->assertTrue($submission->getManuallyUnlocked());

        $submission->setHasSeenResults(false);
        $this->assertFalse($submission->getHasSeenResults());

        $submission->setOverdueAndNeedsSubmission(true);
        $this->assertTrue($submission->getOverdueAndNeedsSubmission());

        $submission->setValidationToken('xyz789');
        $this->assertEquals('xyz789', $submission->getValidationToken());

        $submissionData = ['key' => 'value'];
        $submission->setSubmission($submissionData);
        $this->assertEquals($submissionData, $submission->getSubmission());

        $quizData = ['quiz_key' => 'quiz_value'];
        $submission->setQuizData($quizData);
        $this->assertEquals($quizData, $submission->getQuizData());

        $userData = ['user_key' => 'user_value'];
        $submission->setUser($userData);
        $this->assertEquals($userData, $submission->getUser());
    }

    public function testFind(): void
    {
        $expectedData = [
            'id' => 789,
            'quiz_id' => 456,
            'user_id' => 123,
            'workflow_state' => 'complete',
            'score' => 95.0
        ];
        $response = new Response(200, [], json_encode($expectedData));
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes/456/submissions/789')
            ->willReturn($response);

        $submission = QuizSubmission::find(789);

        $this->assertInstanceOf(QuizSubmission::class, $submission);
        $this->assertEquals(789, $submission->getId());
        $this->assertEquals(456, $submission->getQuizId());
        $this->assertEquals(123, $submission->getUserId());
        $this->assertEquals('complete', $submission->getWorkflowState());
        $this->assertEquals(95.0, $submission->getScore());
    }

    public function testFindWithQuizSubmissionsWrapper(): void
    {
        $expectedData = [
            'quiz_submissions' => [
                [
                    'id' => 789,
                    'quiz_id' => 456,
                    'user_id' => 123,
                    'workflow_state' => 'complete'
                ]
            ]
        ];
        $response = new Response(200, [], json_encode($expectedData));
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes/456/submissions/789')
            ->willReturn($response);

        $submission = QuizSubmission::find(789);

        $this->assertInstanceOf(QuizSubmission::class, $submission);
        $this->assertEquals(789, $submission->getId());
    }

    public function testGetCurrentUserSubmission(): void
    {
        $expectedData = [
            'quiz_submissions' => [
                [
                    'id' => 789,
                    'quiz_id' => 456,
                    'user_id' => 123,
                    'workflow_state' => 'complete'
                ]
            ]
        ];
        $response = new Response(200, [], json_encode($expectedData));
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes/456/submission')
            ->willReturn($response);

        $submission = QuizSubmission::getCurrentUserSubmission();

        $this->assertInstanceOf(QuizSubmission::class, $submission);
        $this->assertEquals(789, $submission->getId());
    }

    public function testGetCurrentUserSubmissionReturnsNullWhenNotFound(): void
    {
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes/456/submission')
            ->willThrowException(new CanvasApiException('404 Not Found'));

        $submission = QuizSubmission::getCurrentUserSubmission();

        $this->assertNull($submission);
    }

    public function testGetCurrentUserSubmissionThrowsNon404Exceptions(): void
    {
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes/456/submission')
            ->willThrowException(new CanvasApiException('500 Server Error'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('500 Server Error');

        QuizSubmission::getCurrentUserSubmission();
    }

    public function testGet(): void
    {
        $expectedData = [
            'quiz_submissions' => [
                [
                    'id' => 789,
                    'quiz_id' => 456,
                    'user_id' => 123,
                    'workflow_state' => 'complete'
                ],
                [
                    'id' => 790,
                    'quiz_id' => 456,
                    'user_id' => 124,
                    'workflow_state' => 'pending_review'
                ]
            ]
        ];
        $response = new Response(200, [], json_encode($expectedData));
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes/456/submissions', ['query' => []])
            ->willReturn($response);

        $submissions = QuizSubmission::get();

        $this->assertIsArray($submissions);
        $this->assertCount(2, $submissions);
        $this->assertInstanceOf(QuizSubmission::class, $submissions[0]);
        $this->assertInstanceOf(QuizSubmission::class, $submissions[1]);
        $this->assertEquals(789, $submissions[0]->getId());
        $this->assertEquals(790, $submissions[1]->getId());
    }

    public function testGetWithParams(): void
    {
        $params = ['include' => ['user', 'quiz']];
        $responseData = ['quiz_submissions' => []];
        $response = new Response(200, [], json_encode($responseData));
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes/456/submissions', ['query' => $params])
            ->willReturn($response);

        QuizSubmission::get($params);
    }

    public function testGetPaginated(): void
    {
        $paginatedResponse = $this->createMock(PaginatedResponse::class);
        $paginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn([
                ['id' => 1, 'quiz_id' => 456, 'user_id' => 789],
                ['id' => 2, 'quiz_id' => 456, 'user_id' => 790]
            ]);
        
        $paginatedResponse->expects($this->once())
            ->method('toPaginationResult')
            ->with($this->isType('array'))
            ->willReturn($this->createMock(PaginationResult::class));

        $this->httpClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/quizzes/456/submissions', ['query' => []])
            ->willReturn($paginatedResponse);

        $result = QuizSubmission::paginate();

        $this->assertInstanceOf(PaginationResult::class, $result);
    }

    public function testCreate(): void
    {
        $submissionData = [
            'access_code' => 'secret123',
            'preview' => false
        ];

        $expectedResponse = [
            'quiz_submissions' => [
                [
                    'id' => 789,
                    'quiz_id' => 456,
                    'user_id' => 123,
                    'workflow_state' => 'untaken'
                ]
            ]
        ];
        $response = new Response(200, [], json_encode($expectedResponse));
        
        $expectedRequestData = [
            ['name' => 'access_code', 'contents' => 'secret123'],
            ['name' => 'preview', 'contents' => false]
        ];

        $this->httpClient->expects($this->once())
            ->method('post')
            ->with('courses/123/quizzes/456/submissions', ['multipart' => $expectedRequestData])
            ->willReturn($response);

        $submission = QuizSubmission::create($submissionData);

        $this->assertInstanceOf(QuizSubmission::class, $submission);
        $this->assertEquals(789, $submission->getId());
        $this->assertEquals('untaken', $submission->getWorkflowState());
    }

    public function testCreateWithDTO(): void
    {
        $dto = new CreateQuizSubmissionDTO([
            'access_code' => 'secret123',
            'preview' => false
        ]);

        $expectedResponse = [
            'id' => 789,
            'quiz_id' => 456,
            'user_id' => 123,
            'workflow_state' => 'untaken'
        ];
        $response = new Response(200, [], json_encode($expectedResponse));
        
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with('courses/123/quizzes/456/submissions', $this->isType('array'))
            ->willReturn($response);

        $submission = QuizSubmission::create($dto);

        $this->assertInstanceOf(QuizSubmission::class, $submission);
        $this->assertEquals(789, $submission->getId());
    }

    public function testStart(): void
    {
        $params = ['access_code' => 'secret123'];

        $expectedResponse = [
            'id' => 789,
            'quiz_id' => 456,
            'user_id' => 123,
            'workflow_state' => 'untaken'
        ];
        $response = new Response(200, [], json_encode($expectedResponse));
        
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with('courses/123/quizzes/456/submissions', $this->isType('array'))
            ->willReturn($response);

        $submission = QuizSubmission::start($params);

        $this->assertInstanceOf(QuizSubmission::class, $submission);
        $this->assertEquals(789, $submission->getId());
    }

    public function testUpdate(): void
    {
        $updateData = [
            'fudge_points' => 2.5,
            'quiz_submissions' => [
                ['attempt' => 1, 'fudge_points' => 2.5]
            ]
        ];

        $expectedResponse = [
            'quiz_submissions' => [
                [
                    'id' => 789,
                    'quiz_id' => 456,
                    'user_id' => 123,
                    'score' => 87.5,
                    'fudge_points' => 2.5
                ]
            ]
        ];
        $response = new Response(200, [], json_encode($expectedResponse));
        
        $this->httpClient->expects($this->once())
            ->method('put')
            ->with('courses/123/quizzes/456/submissions/789', $this->isType('array'))
            ->willReturn($response);

        $submission = QuizSubmission::update(789, $updateData);

        $this->assertInstanceOf(QuizSubmission::class, $submission);
        $this->assertEquals(789, $submission->getId());
        $this->assertEquals(87.5, $submission->getScore());
        $this->assertEquals(2.5, $submission->getFudgePoints());
    }

    public function testUpdateWithDTO(): void
    {
        $dto = new UpdateQuizSubmissionDTO([
            'attempt' => 1,
            'fudge_points' => 2.5
        ]);

        $expectedResponse = [
            'id' => 789,
            'quiz_id' => 456,
            'user_id' => 123,
            'score' => 87.5
        ];
        $response = new Response(200, [], json_encode($expectedResponse));
        
        $this->httpClient->expects($this->once())
            ->method('put')
            ->with('courses/123/quizzes/456/submissions/789', $this->isType('array'))
            ->willReturn($response);

        $submission = QuizSubmission::update(789, $dto);

        $this->assertInstanceOf(QuizSubmission::class, $submission);
        $this->assertEquals(789, $submission->getId());
    }

    public function testComplete(): void
    {
        $submission = new QuizSubmission([
            'id' => 789,
            'validation_token' => 'abc123',
            'attempt' => 1
        ]);

        $expectedResponse = [
            'quiz_submissions' => [
                [
                    'id' => 789,
                    'workflow_state' => 'complete',
                    'finished_at' => '2024-01-01T13:00:00Z',
                    'score' => 85.0
                ]
            ]
        ];
        $response = new Response(200, [], json_encode($expectedResponse));
        
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with('courses/123/quizzes/456/submissions/789/complete', $this->isType('array'))
            ->willReturn($response);

        $result = $submission->complete();

        $this->assertInstanceOf(QuizSubmission::class, $result);
        $this->assertEquals('complete', $submission->getWorkflowState());
        $this->assertEquals('2024-01-01T13:00:00Z', $submission->getFinishedAt());
        $this->assertEquals(85.0, $submission->getScore());
    }

    public function testCompleteWithoutId(): void
    {
        $submission = new QuizSubmission([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Quiz submission ID is required for completion');

        $submission->complete();
    }

    public function testCompleteReturnsFalseOnException(): void
    {
        $submission = new QuizSubmission(['id' => 789]);

        $this->httpClient->expects($this->once())
            ->method('post')
            ->willThrowException(new CanvasApiException('API Error'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('API Error');
        $submission->complete();
    }

    public function testSave(): void
    {
        $submission = new QuizSubmission([
            'id' => 789,
            'fudge_points' => 5.0
        ]);

        $expectedResponse = [
            'id' => 789,
            'quiz_id' => 456,
            'user_id' => 123,
            'score' => 90.0,
            'fudge_points' => 5.0
        ];
        $response = new Response(200, [], json_encode($expectedResponse));
        
        $this->httpClient->expects($this->once())
            ->method('put')
            ->with('courses/123/quizzes/456/submissions/789', $this->isType('array'))
            ->willReturn($response);

        $result = $submission->save();

        $this->assertInstanceOf(QuizSubmission::class, $result);
        $this->assertEquals(90.0, $submission->getScore());
    }

    public function testSaveWithoutId(): void
    {
        $submission = new QuizSubmission([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Quiz submission ID is required for saving');

        $submission->save();
    }

    public function testSaveReturnsFalseOnException(): void
    {
        $submission = new QuizSubmission(['id' => 789]);

        $this->httpClient->expects($this->once())
            ->method('put')
            ->willThrowException(new CanvasApiException('API Error'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('API Error');
        $submission->save();
    }

    public function testIsComplete(): void
    {
        $submission = new QuizSubmission(['workflow_state' => 'complete']);
        $this->assertTrue($submission->isComplete());

        $submission = new QuizSubmission(['workflow_state' => 'pending_review']);
        $this->assertFalse($submission->isComplete());
    }

    public function testIsInProgress(): void
    {
        $submission = new QuizSubmission(['workflow_state' => 'untaken']);
        $this->assertTrue($submission->isInProgress());

        $submission = new QuizSubmission(['workflow_state' => 'pending_review']);
        $this->assertTrue($submission->isInProgress());

        $submission = new QuizSubmission(['workflow_state' => 'complete']);
        $this->assertFalse($submission->isInProgress());
    }

    public function testCanBeRetaken(): void
    {
        // Test with multiple attempts allowed and first attempt
        $quiz = $this->createMock(Quiz::class);
        $quiz->method('getAllowedAttempts')->willReturn(3);
        QuizSubmission::setQuiz($quiz);

        $submission = new QuizSubmission(['workflow_state' => 'complete', 'attempt' => 1]);
        $this->assertTrue($submission->canBeRetaken());

        // Test with multiple attempts allowed but last attempt reached
        $submission = new QuizSubmission(['workflow_state' => 'complete', 'attempt' => 3]);
        $this->assertFalse($submission->canBeRetaken());

        // Test with single attempt quiz
        $singleAttemptQuiz = $this->createMock(Quiz::class);
        $singleAttemptQuiz->method('getAllowedAttempts')->willReturn(1);
        QuizSubmission::setQuiz($singleAttemptQuiz);

        $submission = new QuizSubmission(['workflow_state' => 'complete', 'attempt' => 1]);
        $this->assertFalse($submission->canBeRetaken());

        // Test with unlimited attempts (-1)
        $unlimitedQuiz = $this->createMock(Quiz::class);
        $unlimitedQuiz->method('getAllowedAttempts')->willReturn(-1);
        QuizSubmission::setQuiz($unlimitedQuiz);

        $submission = new QuizSubmission(['workflow_state' => 'complete', 'attempt' => 5]);
        $this->assertTrue($submission->canBeRetaken());

        // Test with incomplete submission
        $submission = new QuizSubmission(['workflow_state' => 'untaken', 'attempt' => 1]);
        $this->assertFalse($submission->canBeRetaken());
    }

    public function testGetRemainingTime(): void
    {
        // Test with end time in future
        $futureTime = date('c', time() + 3600); // 1 hour from now
        $submission = new QuizSubmission(['end_at' => $futureTime]);
        $remaining = $submission->getRemainingTime();
        $this->assertGreaterThan(3500, $remaining); // Should be close to 3600 seconds
        $this->assertLessThanOrEqual(3600, $remaining);

        // Test with end time in past
        $pastTime = date('c', time() - 3600); // 1 hour ago
        $submission = new QuizSubmission(['end_at' => $pastTime]);
        $this->assertEquals(0, $submission->getRemainingTime());

        // Test with no end time
        $submission = new QuizSubmission([]);
        $this->assertNull($submission->getRemainingTime());
    }

    public function testHasTimeLimit(): void
    {
        $submission = new QuizSubmission(['end_at' => '2024-01-01T13:00:00Z']);
        $this->assertTrue($submission->hasTimeLimit());

        $submission = new QuizSubmission([]);
        $this->assertFalse($submission->hasTimeLimit());
    }

    public function testIsOverdue(): void
    {
        $submission = new QuizSubmission(['overdue_and_needs_submission' => true]);
        $this->assertTrue($submission->isOverdue());

        $submission = new QuizSubmission(['overdue_and_needs_submission' => false]);
        $this->assertFalse($submission->isOverdue());

        $submission = new QuizSubmission([]);
        $this->assertFalse($submission->isOverdue());
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 789,
            'quiz_id' => 456,
            'user_id' => 123,
            'workflow_state' => 'complete',
            'score' => 85.0
        ];

        $submission = new QuizSubmission($data);
        $result = $submission->toArray();

        $this->assertIsArray($result);
        $this->assertEquals(789, $result['id']);
        $this->assertEquals(456, $result['quizId']);
        $this->assertEquals(123, $result['userId']);
        $this->assertEquals('complete', $result['workflowState']);
        $this->assertEquals(85.0, $result['score']);
    }

    public function testToDtoArray(): void
    {
        $data = [
            'id' => 789,
            'quiz_id' => 456,
            'user_id' => 123,
            'workflow_state' => 'complete',
            'score' => 85.0
        ];

        $submission = new QuizSubmission($data);
        $result = $submission->toDtoArray();

        $this->assertIsArray($result);
        $this->assertEquals(456, $result['quiz_id']);
        $this->assertEquals(123, $result['user_id']);
        $this->assertEquals('complete', $result['workflow_state']);
        $this->assertEquals(85.0, $result['score']);
    }
}