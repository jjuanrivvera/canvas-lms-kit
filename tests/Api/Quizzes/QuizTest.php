<?php

declare(strict_types=1);

namespace Tests\Api\Quizzes;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Quizzes\Quiz;
use CanvasLMS\Dto\Quizzes\CreateQuizDTO;
use CanvasLMS\Dto\Quizzes\UpdateQuizDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \CanvasLMS\Api\Quizzes\Quiz
 */
class QuizTest extends TestCase
{
    private HttpClientInterface $httpClientMock;

    private Course $course;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->course = new Course(['id' => 123]);

        Quiz::setApiClient($this->httpClientMock);
        Quiz::setCourse($this->course);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Quiz::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 456]);
        Quiz::setCourse($course);

        $this->assertTrue(Quiz::checkCourse());
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course is required');

        $reflection = new \ReflectionClass(Quiz::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, null);

        Quiz::checkCourse();
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Quiz',
            'description' => 'Test description',
            'quiz_type' => 'assignment',
            'course_id' => 123,
            'assignment_group_id' => 456,
            'time_limit' => 60,
            'points_possible' => 100.0,
            'due_at' => '2024-12-31T23:59:59Z',
            'lock_at' => '2025-01-01T00:00:00Z',
            'unlock_at' => '2024-01-01T00:00:00Z',
            'published' => true,
            'workflow_state' => 'published',
            'shuffle_answers' => true,
            'show_correct_answers' => false,
            'allowed_attempts' => 3,
            'one_question_at_a_time' => true,
            'hide_results' => 'always',
            'ip_filter' => '192.168.1.0/24',
            'access_code' => 'secret123',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $quiz = new Quiz($data);

        $this->assertEquals(1, $quiz->getId());
        $this->assertEquals('Test Quiz', $quiz->getTitle());
        $this->assertEquals('Test description', $quiz->getDescription());
        $this->assertEquals('assignment', $quiz->getQuizType());
        $this->assertEquals(123, $quiz->getCourseId());
        $this->assertEquals(456, $quiz->getAssignmentGroupId());
        $this->assertEquals(60, $quiz->getTimeLimit());
        $this->assertEquals(100.0, $quiz->getPointsPossible());
        $this->assertEquals('2024-12-31T23:59:59Z', $quiz->getDueAt());
        $this->assertEquals('2025-01-01T00:00:00Z', $quiz->getLockAt());
        $this->assertEquals('2024-01-01T00:00:00Z', $quiz->getUnlockAt());
        $this->assertTrue($quiz->getPublished());
        $this->assertEquals('published', $quiz->getWorkflowState());
        $this->assertTrue($quiz->getShuffleAnswers());
        $this->assertFalse($quiz->getShowCorrectAnswers());
        $this->assertEquals(3, $quiz->getAllowedAttempts());
        $this->assertTrue($quiz->getOneQuestionAtATime());
        $this->assertEquals('always', $quiz->getHideResults());
        $this->assertEquals('192.168.1.0/24', $quiz->getIpFilter());
        $this->assertEquals('secret123', $quiz->getAccessCode());
        $this->assertEquals('2024-01-01T00:00:00Z', $quiz->getCreatedAt());
        $this->assertEquals('2024-01-01T00:00:00Z', $quiz->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $quiz = new Quiz();

        $quiz->setId(1);
        $this->assertEquals(1, $quiz->getId());

        $quiz->setTitle('Test Quiz');
        $this->assertEquals('Test Quiz', $quiz->getTitle());

        $quiz->setDescription('Test description');
        $this->assertEquals('Test description', $quiz->getDescription());

        $quiz->setQuizType('practice_quiz');
        $this->assertEquals('practice_quiz', $quiz->getQuizType());

        $quiz->setCourseId(123);
        $this->assertEquals(123, $quiz->getCourseId());

        $quiz->setAssignmentGroupId(456);
        $this->assertEquals(456, $quiz->getAssignmentGroupId());

        $quiz->setTimeLimit(90);
        $this->assertEquals(90, $quiz->getTimeLimit());

        $quiz->setPointsPossible(150.0);
        $this->assertEquals(150.0, $quiz->getPointsPossible());

        $quiz->setDueAt('2024-12-31T23:59:59Z');
        $this->assertEquals('2024-12-31T23:59:59Z', $quiz->getDueAt());

        $quiz->setLockAt('2025-01-01T00:00:00Z');
        $this->assertEquals('2025-01-01T00:00:00Z', $quiz->getLockAt());

        $quiz->setUnlockAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $quiz->getUnlockAt());

        $quiz->setPublished(true);
        $this->assertTrue($quiz->getPublished());

        $quiz->setWorkflowState('published');
        $this->assertEquals('published', $quiz->getWorkflowState());

        $quiz->setShuffleAnswers(true);
        $this->assertTrue($quiz->getShuffleAnswers());

        $quiz->setShowCorrectAnswers(false);
        $this->assertFalse($quiz->getShowCorrectAnswers());

        $quiz->setAllowedAttempts(-1);
        $this->assertEquals(-1, $quiz->getAllowedAttempts());

        $quiz->setOneQuestionAtATime(false);
        $this->assertFalse($quiz->getOneQuestionAtATime());

        $quiz->setHideResults('until_after_last_attempt');
        $this->assertEquals('until_after_last_attempt', $quiz->getHideResults());

        $quiz->setIpFilter('10.0.0.0/8');
        $this->assertEquals('10.0.0.0/8', $quiz->getIpFilter());

        $quiz->setAccessCode('password123');
        $this->assertEquals('password123', $quiz->getAccessCode());

        $quiz->setHtmlUrl('https://example.com/quiz/1');
        $this->assertEquals('https://example.com/quiz/1', $quiz->getHtmlUrl());

        $quiz->setMobileUrl('https://mobile.example.com/quiz/1');
        $this->assertEquals('https://mobile.example.com/quiz/1', $quiz->getMobileUrl());

        $quiz->setQuestionCount(10);
        $this->assertEquals(10, $quiz->getQuestionCount());

        $quiz->setRequireLockdownBrowser(true);
        $this->assertTrue($quiz->getRequireLockdownBrowser());

        $quiz->setRequireLockdownBrowserForResults(false);
        $this->assertFalse($quiz->getRequireLockdownBrowserForResults());

        $quiz->setRequireLockdownBrowserMonitor(true);
        $this->assertTrue($quiz->getRequireLockdownBrowserMonitor());

        $quiz->setLockdownBrowserMonitorData('monitor_data');
        $this->assertEquals('monitor_data', $quiz->getLockdownBrowserMonitorData());

        $allDates = [['base' => true, 'due_at' => '2024-12-31T23:59:59Z']];
        $quiz->setAllDates($allDates);
        $this->assertEquals($allDates, $quiz->getAllDates());

        $quiz->setCreatedAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $quiz->getCreatedAt());

        $quiz->setUpdatedAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $quiz->getUpdatedAt());
    }

    public function testToArray(): void
    {
        $quiz = new Quiz([
            'id' => 1,
            'title' => 'Test Quiz',
            'quiz_type' => 'assignment',
            'points_possible' => 100.0,
            'published' => true,
        ]);

        $array = $quiz->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('Test Quiz', $array['title']);
        $this->assertEquals('assignment', $array['quiz_type']);
        $this->assertEquals(100.0, $array['points_possible']);
        $this->assertTrue($array['published']);
    }

    public function testToDtoArray(): void
    {
        $quiz = new Quiz([
            'id' => 1,
            'title' => 'Test Quiz',
            'quiz_type' => 'assignment',
            'points_possible' => 100.0,
            'published' => true,
            'course_id' => 123,
        ]);

        $dtoArray = $quiz->toDtoArray();

        $this->assertIsArray($dtoArray);
        $this->assertEquals('Test Quiz', $dtoArray['title']);
        $this->assertEquals('assignment', $dtoArray['quiz_type']);
        $this->assertEquals(100.0, $dtoArray['points_possible']);
        $this->assertTrue($dtoArray['published']);
        $this->assertArrayNotHasKey('id', $dtoArray);
        $this->assertArrayNotHasKey('course_id', $dtoArray);
    }

    public function testFind(): void
    {
        $quizData = [
            'id' => 1,
            'title' => 'Test Quiz',
            'quiz_type' => 'assignment',
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($quizData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes/1')
            ->willReturn($responseMock);

        $quiz = Quiz::find(1);

        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertEquals(1, $quiz->getId());
        $this->assertEquals('Test Quiz', $quiz->getTitle());
        $this->assertEquals('assignment', $quiz->getQuizType());
    }

    public function testGet(): void
    {
        $quizzesData = [
            ['id' => 1, 'title' => 'Quiz 1', 'quiz_type' => 'assignment'],
            ['id' => 2, 'title' => 'Quiz 2', 'quiz_type' => 'practice_quiz'],
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($quizzesData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes', ['query' => []])
            ->willReturn($responseMock);

        $quizzes = Quiz::get();

        $this->assertIsArray($quizzes);
        $this->assertCount(2, $quizzes);
        $this->assertInstanceOf(Quiz::class, $quizzes[0]);
        $this->assertInstanceOf(Quiz::class, $quizzes[1]);
        $this->assertEquals('Quiz 1', $quizzes[0]->getTitle());
        $this->assertEquals('Quiz 2', $quizzes[1]->getTitle());
    }

    public function testGetWithParams(): void
    {
        $params = ['published' => true];
        $quizzesData = [
            ['id' => 1, 'title' => 'Published Quiz', 'quiz_type' => 'assignment', 'published' => true],
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($quizzesData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes', ['query' => $params])
            ->willReturn($responseMock);

        $quizzes = Quiz::get($params);

        $this->assertIsArray($quizzes);
        $this->assertCount(1, $quizzes);
        $this->assertEquals('Published Quiz', $quizzes[0]->getTitle());
    }

    public function testCreate(): void
    {
        $createData = [
            'title' => 'New Quiz',
            'quiz_type' => 'assignment',
            'points_possible' => 100.0,
        ];

        $responseData = array_merge($createData, ['id' => 1]);

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                'courses/123/quizzes',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $quiz = Quiz::create($createData);

        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertEquals(1, $quiz->getId());
        $this->assertEquals('New Quiz', $quiz->getTitle());
        $this->assertEquals('assignment', $quiz->getQuizType());
        $this->assertEquals(100.0, $quiz->getPointsPossible());
    }

    public function testCreateWithDTO(): void
    {
        $createDto = new CreateQuizDTO([
            'title' => 'New Quiz with DTO',
            'quiz_type' => 'practice_quiz',
            'time_limit' => 30,
        ]);

        $responseData = [
            'id' => 2,
            'title' => 'New Quiz with DTO',
            'quiz_type' => 'practice_quiz',
            'time_limit' => 30,
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                'courses/123/quizzes',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $quiz = Quiz::create($createDto);

        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertEquals(2, $quiz->getId());
        $this->assertEquals('New Quiz with DTO', $quiz->getTitle());
        $this->assertEquals('practice_quiz', $quiz->getQuizType());
        $this->assertEquals(30, $quiz->getTimeLimit());
    }

    public function testUpdate(): void
    {
        $updateData = [
            'title' => 'Updated Quiz',
            'time_limit' => 120,
        ];

        $responseData = [
            'id' => 1,
            'title' => 'Updated Quiz',
            'quiz_type' => 'assignment',
            'time_limit' => 120,
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'courses/123/quizzes/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $quiz = Quiz::update(1, $updateData);

        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertEquals(1, $quiz->getId());
        $this->assertEquals('Updated Quiz', $quiz->getTitle());
        $this->assertEquals(120, $quiz->getTimeLimit());
    }

    public function testUpdateWithDTO(): void
    {
        $updateDto = new UpdateQuizDTO([
            'title' => 'Updated Quiz with DTO',
            'published' => true,
        ]);

        $responseData = [
            'id' => 1,
            'title' => 'Updated Quiz with DTO',
            'quiz_type' => 'assignment',
            'published' => true,
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'courses/123/quizzes/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $quiz = Quiz::update(1, $updateDto);

        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertEquals(1, $quiz->getId());
        $this->assertEquals('Updated Quiz with DTO', $quiz->getTitle());
        $this->assertTrue($quiz->getPublished());
    }

    public function testSaveNewQuiz(): void
    {
        $quiz = new Quiz([
            'title' => 'New Quiz to Save',
            'quiz_type' => 'survey',
        ]);

        $responseData = [
            'id' => 3,
            'title' => 'New Quiz to Save',
            'quiz_type' => 'survey',
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                'courses/123/quizzes',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $result = $quiz->save();

        $this->assertInstanceOf(Quiz::class, $result);
        $this->assertEquals(3, $quiz->getId());
    }

    public function testSaveExistingQuiz(): void
    {
        $quiz = new Quiz([
            'id' => 1,
            'title' => 'Existing Quiz',
            'quiz_type' => 'assignment',
        ]);

        $quiz->setTitle('Updated Existing Quiz');

        $responseData = [
            'id' => 1,
            'title' => 'Updated Existing Quiz',
            'quiz_type' => 'assignment',
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'courses/123/quizzes/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $result = $quiz->save();

        $this->assertInstanceOf(Quiz::class, $result);
        $this->assertEquals('Updated Existing Quiz', $quiz->getTitle());
    }

    public function testDelete(): void
    {
        $quiz = new Quiz(['id' => 1]);

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('courses/123/quizzes/1');

        $result = $quiz->delete();

        $this->assertInstanceOf(Quiz::class, $result);
    }

    public function testDeleteWithoutId(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Quiz ID is required for deletion');

        $quiz = new Quiz();
        $quiz->delete();
    }

    public function testPublish(): void
    {
        $quiz = new Quiz(['id' => 1, 'published' => false]);

        $responseData = [
            'id' => 1,
            'title' => 'Test Quiz',
            'published' => true,
            'workflow_state' => 'published',
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'courses/123/quizzes/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $result = $quiz->publish();

        $this->assertInstanceOf(Quiz::class, $result);
        $this->assertTrue($quiz->getPublished());
        $this->assertEquals('published', $quiz->getWorkflowState());
    }

    public function testUnpublish(): void
    {
        $quiz = new Quiz(['id' => 1, 'published' => true]);

        $responseData = [
            'id' => 1,
            'title' => 'Test Quiz',
            'published' => false,
            'workflow_state' => 'unpublished',
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'courses/123/quizzes/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($responseMock);

        $result = $quiz->unpublish();

        $this->assertInstanceOf(Quiz::class, $result);
        $this->assertFalse($quiz->getPublished());
        $this->assertEquals('unpublished', $quiz->getWorkflowState());
    }

    public function testIsPublished(): void
    {
        $quiz = new Quiz(['published' => true]);
        $this->assertTrue($quiz->isPublished());

        $quiz = new Quiz(['published' => false, 'workflow_state' => 'published']);
        $this->assertTrue($quiz->isPublished());

        $quiz = new Quiz(['published' => false, 'workflow_state' => 'unpublished']);
        $this->assertFalse($quiz->isPublished());

        $quiz = new Quiz(['published' => null, 'workflow_state' => null]);
        $this->assertFalse($quiz->isPublished());
    }

    public function testSaveValidationErrors(): void
    {
        $quiz = new Quiz();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Quiz title is required');

        $quiz->save();
    }

    public function testSaveValidationPointsPossible(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);
        $quiz->setPointsPossible(-10);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Points possible must be non-negative');

        $quiz->save();
    }

    public function testSaveValidationQuizType(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);
        $quiz->setQuizType('invalid_type');

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid quiz type. Must be one of: assignment, practice_quiz, survey, graded_survey');

        $quiz->save();
    }

    public function testSaveValidationTimeLimit(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);
        $quiz->setTimeLimit(-5);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Time limit must be non-negative');

        $quiz->save();
    }

    public function testSaveValidationAllowedAttempts(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);
        $quiz->setAllowedAttempts(-2);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Allowed attempts must be -1 (unlimited) or greater');

        $quiz->save();
    }

    public function testSaveValidationHideResults(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);
        $quiz->setHideResults('invalid_value');

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid hide results value. Must be one of: null, always, until_after_last_attempt');

        $quiz->save();
    }

    public function testDescriptionXSSValidation(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Quiz description contains potentially dangerous content');

        $quiz->setDescription('<script>alert("xss")</script>');
    }

    public function testDescriptionXSSValidationWithEventHandlers(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Quiz description contains potentially dangerous content');

        $quiz->setDescription('<div onclick="alert(\'xss\')">Click me</div>');
    }

    public function testDescriptionXSSValidationWithJavascriptUrls(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Quiz description contains potentially dangerous content');

        $quiz->setDescription('<a href="javascript:alert(\'xss\')">Link</a>');
    }

    public function testDescriptionLengthValidation(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Quiz description is too long. Maximum length is 65535 characters.');

        $quiz->setDescription(str_repeat('a', 65536));
    }

    public function testDescriptionValidHTML(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);

        // Should not throw exception for safe HTML
        $quiz->setDescription('<p>This is <strong>safe</strong> HTML content with <em>formatting</em>.</p>');

        $this->assertEquals('<p>This is <strong>safe</strong> HTML content with <em>formatting</em>.</p>', $quiz->getDescription());
    }

    public function testDescriptionNullValue(): void
    {
        $quiz = new Quiz(['title' => 'Test Quiz']);

        // Should not throw exception for null
        $quiz->setDescription(null);

        $this->assertNull($quiz->getDescription());
    }
}
