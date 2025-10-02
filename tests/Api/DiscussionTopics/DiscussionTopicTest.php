<?php

declare(strict_types=1);

namespace Tests\Api\DiscussionTopics;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\DiscussionTopics\DiscussionTopic;
use CanvasLMS\Dto\DiscussionTopics\CreateDiscussionTopicDTO;
use CanvasLMS\Dto\DiscussionTopics\UpdateDiscussionTopicDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \CanvasLMS\Api\DiscussionTopics\DiscussionTopic
 */
class DiscussionTopicTest extends TestCase
{
    private HttpClientInterface $httpClientMock;

    private Course $course;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->course = new Course(['id' => 123]);

        DiscussionTopic::setApiClient($this->httpClientMock);
        DiscussionTopic::setCourse($this->course);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(DiscussionTopic::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 456]);
        DiscussionTopic::setCourse($course);

        $this->assertTrue(DiscussionTopic::checkCourse());
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course is required');

        $reflection = new \ReflectionClass(DiscussionTopic::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, null);

        DiscussionTopic::checkCourse();
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Discussion Topic',
            'message' => 'Test discussion message',
            'html_url' => '/courses/123/discussion_topics/1',
            'posted_at' => '2024-01-01T00:00:00Z',
            'discussion_type' => 'threaded',
            'require_initial_post' => true,
            'locked' => false,
            'pinned' => false,
            'course_id' => 123,
            'user_id' => 456,
            'published' => true,
            'workflow_state' => 'active',
            'read_only' => false,
            'assignment_id' => 789,
            'points_possible' => 100.0,
            'grading_type' => 'points',
            'allow_rating' => true,
            'only_graders_can_rate' => false,
            'group_topic' => false,
            'group_category_id' => null,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $discussionTopic = new DiscussionTopic($data);

        $this->assertEquals(1, $discussionTopic->getId());
        $this->assertEquals('Test Discussion Topic', $discussionTopic->getTitle());
        $this->assertEquals('Test discussion message', $discussionTopic->getMessage());
        $this->assertEquals('/courses/123/discussion_topics/1', $discussionTopic->getHtmlUrl());
        $this->assertInstanceOf(\DateTime::class, $discussionTopic->getPostedAt());
        $this->assertEquals('2024-01-01T00:00:00+00:00', $discussionTopic->getPostedAt()->format('c'));
        $this->assertEquals('threaded', $discussionTopic->getDiscussionType());
        $this->assertTrue($discussionTopic->getRequireInitialPost());
        $this->assertFalse($discussionTopic->getLocked());
        $this->assertFalse($discussionTopic->getPinned());
        $this->assertEquals(123, $discussionTopic->getCourseId());
        $this->assertEquals(456, $discussionTopic->getUserId());
        $this->assertTrue($discussionTopic->getPublished());
        $this->assertEquals('active', $discussionTopic->getWorkflowState());
        $this->assertFalse($discussionTopic->getReadOnly());
        $this->assertEquals(789, $discussionTopic->getAssignmentId());
        $this->assertEquals(100.0, $discussionTopic->getPointsPossible());
        $this->assertEquals('points', $discussionTopic->getGradingType());
        $this->assertTrue($discussionTopic->getAllowRating());
        $this->assertFalse($discussionTopic->getOnlyGradersCanRate());
        $this->assertFalse($discussionTopic->getGroupTopic());
        $this->assertNull($discussionTopic->getGroupCategoryId());
        $this->assertInstanceOf(\DateTime::class, $discussionTopic->getCreatedAt());
        $this->assertEquals('2024-01-01T00:00:00+00:00', $discussionTopic->getCreatedAt()->format('c'));
        $this->assertInstanceOf(\DateTime::class, $discussionTopic->getUpdatedAt());
        $this->assertEquals('2024-01-01T00:00:00+00:00', $discussionTopic->getUpdatedAt()->format('c'));
    }

    public function testGettersAndSetters(): void
    {
        $discussionTopic = new DiscussionTopic();

        $discussionTopic->setId(1);
        $this->assertEquals(1, $discussionTopic->getId());

        $discussionTopic->setTitle('Test Discussion Topic');
        $this->assertEquals('Test Discussion Topic', $discussionTopic->getTitle());

        $discussionTopic->setMessage('Test discussion message');
        $this->assertEquals('Test discussion message', $discussionTopic->getMessage());

        $discussionTopic->setHtmlUrl('/courses/123/discussion_topics/1');
        $this->assertEquals('/courses/123/discussion_topics/1', $discussionTopic->getHtmlUrl());

        $discussionTopic->setPostedAt(new \DateTime('2024-01-01T00:00:00Z'));
        $this->assertInstanceOf(\DateTime::class, $discussionTopic->getPostedAt());
        $this->assertEquals('2024-01-01T00:00:00+00:00', $discussionTopic->getPostedAt()->format('c'));

        $discussionTopic->setDiscussionType('threaded');
        $this->assertEquals('threaded', $discussionTopic->getDiscussionType());

        $discussionTopic->setRequireInitialPost(true);
        $this->assertTrue($discussionTopic->getRequireInitialPost());

        $discussionTopic->setLocked(false);
        $this->assertFalse($discussionTopic->getLocked());

        $discussionTopic->setPinned(true);
        $this->assertTrue($discussionTopic->getPinned());

        $discussionTopic->setCourseId(123);
        $this->assertEquals(123, $discussionTopic->getCourseId());

        $discussionTopic->setUserId(456);
        $this->assertEquals(456, $discussionTopic->getUserId());

        $discussionTopic->setAuthor(['id' => 456, 'display_name' => 'Test User']);
        $this->assertEquals(['id' => 456, 'display_name' => 'Test User'], $discussionTopic->getAuthor());

        $discussionTopic->setPublished(true);
        $this->assertTrue($discussionTopic->getPublished());

        $discussionTopic->setWorkflowState('active');
        $this->assertEquals('active', $discussionTopic->getWorkflowState());

        $discussionTopic->setReadOnly(false);
        $this->assertFalse($discussionTopic->getReadOnly());

        $discussionTopic->setAssignmentId(789);
        $this->assertEquals(789, $discussionTopic->getAssignmentId());

        $discussionTopic->setPointsPossible(100.0);
        $this->assertEquals(100.0, $discussionTopic->getPointsPossible());

        $discussionTopic->setGradingType('points');
        $this->assertEquals('points', $discussionTopic->getGradingType());

        $discussionTopic->setAllowRating(true);
        $this->assertTrue($discussionTopic->getAllowRating());

        $discussionTopic->setOnlyGradersCanRate(false);
        $this->assertFalse($discussionTopic->getOnlyGradersCanRate());

        $discussionTopic->setGroupTopic(false);
        $this->assertFalse($discussionTopic->getGroupTopic());

        $discussionTopic->setGroupCategoryId(null);
        $this->assertNull($discussionTopic->getGroupCategoryId());

        $discussionTopic->setCreatedAt(new \DateTime('2024-01-01T00:00:00Z'));
        $this->assertInstanceOf(\DateTime::class, $discussionTopic->getCreatedAt());
        $this->assertEquals('2024-01-01T00:00:00+00:00', $discussionTopic->getCreatedAt()->format('c'));

        $discussionTopic->setUpdatedAt(new \DateTime('2024-01-01T00:00:00Z'));
        $this->assertInstanceOf(\DateTime::class, $discussionTopic->getUpdatedAt());
        $this->assertEquals('2024-01-01T00:00:00+00:00', $discussionTopic->getUpdatedAt()->format('c'));
    }

    public function testFind(): void
    {
        $responseData = [
            'id' => 1,
            'title' => 'Test Discussion Topic',
            'message' => 'Test discussion message',
            'course_id' => 123,
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
            ->method('get')
            ->with('courses/123/discussion_topics/1')
            ->willReturn($responseMock);

        $discussionTopic = DiscussionTopic::find(1);

        $this->assertInstanceOf(DiscussionTopic::class, $discussionTopic);
        $this->assertEquals(1, $discussionTopic->getId());
        $this->assertEquals('Test Discussion Topic', $discussionTopic->getTitle());
        $this->assertEquals('Test discussion message', $discussionTopic->getMessage());
        $this->assertEquals(123, $discussionTopic->getCourseId());
        $this->assertTrue($discussionTopic->getPublished());
    }

    public function testGet(): void
    {
        $responseData = [
            [
                'id' => 1,
                'title' => 'Discussion Topic 1',
                'message' => 'First discussion',
                'course_id' => 123,
                'published' => true,
            ],
            [
                'id' => 2,
                'title' => 'Discussion Topic 2',
                'message' => 'Second discussion',
                'course_id' => 123,
                'published' => false,
            ],
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
            ->method('get')
            ->with('courses/123/discussion_topics', ['query' => []])
            ->willReturn($responseMock);

        $discussionTopics = DiscussionTopic::get();

        $this->assertIsArray($discussionTopics);
        $this->assertCount(2, $discussionTopics);
        $this->assertInstanceOf(DiscussionTopic::class, $discussionTopics[0]);
        $this->assertInstanceOf(DiscussionTopic::class, $discussionTopics[1]);
        $this->assertEquals(1, $discussionTopics[0]->getId());
        $this->assertEquals(2, $discussionTopics[1]->getId());
    }

    public function testCreateWithArray(): void
    {
        $createData = [
            'title' => 'New Discussion Topic',
            'message' => 'New discussion message',
            'discussion_type' => 'threaded',
            'published' => true,
        ];

        $responseData = [
            'id' => 1,
            'title' => 'New Discussion Topic',
            'message' => 'New discussion message',
            'discussion_type' => 'threaded',
            'course_id' => 123,
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
            ->method('post')
            ->with('courses/123/discussion_topics', $this->callback(function ($options) {
                return isset($options['multipart']);
            }))
            ->willReturn($responseMock);

        $discussionTopic = DiscussionTopic::create($createData);

        $this->assertInstanceOf(DiscussionTopic::class, $discussionTopic);
        $this->assertEquals(1, $discussionTopic->getId());
        $this->assertEquals('New Discussion Topic', $discussionTopic->getTitle());
        $this->assertEquals('New discussion message', $discussionTopic->getMessage());
        $this->assertEquals('threaded', $discussionTopic->getDiscussionType());
        $this->assertTrue($discussionTopic->getPublished());
    }

    public function testCreateWithDTO(): void
    {
        $createDTO = new CreateDiscussionTopicDTO([
            'title' => 'New Discussion Topic',
            'message' => 'New discussion message',
            'discussion_type' => 'threaded',
            'published' => true,
        ]);

        $responseData = [
            'id' => 1,
            'title' => 'New Discussion Topic',
            'message' => 'New discussion message',
            'discussion_type' => 'threaded',
            'course_id' => 123,
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
            ->method('post')
            ->with('courses/123/discussion_topics', $this->callback(function ($options) {
                return isset($options['multipart']);
            }))
            ->willReturn($responseMock);

        $discussionTopic = DiscussionTopic::create($createDTO);

        $this->assertInstanceOf(DiscussionTopic::class, $discussionTopic);
        $this->assertEquals(1, $discussionTopic->getId());
        $this->assertEquals('New Discussion Topic', $discussionTopic->getTitle());
    }

    public function testUpdateWithArray(): void
    {
        $updateData = [
            'title' => 'Updated Discussion Topic',
            'pinned' => true,
        ];

        $responseData = [
            'id' => 1,
            'title' => 'Updated Discussion Topic',
            'message' => 'Original message',
            'pinned' => true,
            'course_id' => 123,
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
            ->with('courses/123/discussion_topics/1', $this->callback(function ($options) {
                return isset($options['multipart']);
            }))
            ->willReturn($responseMock);

        $discussionTopic = DiscussionTopic::update(1, $updateData);

        $this->assertInstanceOf(DiscussionTopic::class, $discussionTopic);
        $this->assertEquals(1, $discussionTopic->getId());
        $this->assertEquals('Updated Discussion Topic', $discussionTopic->getTitle());
        $this->assertTrue($discussionTopic->getPinned());
    }

    public function testUpdateWithDTO(): void
    {
        $updateDTO = new UpdateDiscussionTopicDTO([
            'title' => 'Updated Discussion Topic',
            'pinned' => true,
        ]);

        $responseData = [
            'id' => 1,
            'title' => 'Updated Discussion Topic',
            'message' => 'Original message',
            'pinned' => true,
            'course_id' => 123,
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
            ->with('courses/123/discussion_topics/1', $this->callback(function ($options) {
                return isset($options['multipart']);
            }))
            ->willReturn($responseMock);

        $discussionTopic = DiscussionTopic::update(1, $updateDTO);

        $this->assertInstanceOf(DiscussionTopic::class, $discussionTopic);
        $this->assertEquals(1, $discussionTopic->getId());
        $this->assertEquals('Updated Discussion Topic', $discussionTopic->getTitle());
        $this->assertTrue($discussionTopic->getPinned());
    }

    public function testSaveNewDiscussion(): void
    {
        $responseData = [
            'id' => 1,
            'title' => 'New Discussion Topic',
            'message' => 'New discussion message',
            'course_id' => 123,
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
            ->method('post')
            ->with('courses/123/discussion_topics', $this->callback(function ($options) {
                return isset($options['multipart']);
            }))
            ->willReturn($responseMock);

        $discussionTopic = new DiscussionTopic();
        $discussionTopic->setTitle('New Discussion Topic');
        $discussionTopic->setMessage('New discussion message');
        $discussionTopic->setPublished(true);

        $result = $discussionTopic->save();

        $this->assertInstanceOf(DiscussionTopic::class, $result);
        $this->assertEquals(1, $discussionTopic->getId());
        $this->assertEquals('New Discussion Topic', $discussionTopic->getTitle());
    }

    public function testSaveThrowsExceptionWhenTitleIsMissing(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Discussion topic title is required');

        $discussionTopic = new DiscussionTopic();
        $discussionTopic->save();
    }

    public function testSaveThrowsExceptionForInvalidDiscussionType(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid discussion type. Must be one of: threaded, side_comment, flat');

        $discussionTopic = new DiscussionTopic();
        $discussionTopic->setTitle('Test Discussion');
        $discussionTopic->setDiscussionType('invalid_type');
        $discussionTopic->save();
    }

    public function testSaveThrowsExceptionForNegativePointsPossible(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Points possible must be non-negative');

        $discussionTopic = new DiscussionTopic();
        $discussionTopic->setTitle('Test Discussion');
        $discussionTopic->setPointsPossible(-10.0);
        $discussionTopic->save();
    }

    public function testDelete(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('courses/123/discussion_topics/1');

        $discussionTopic = new DiscussionTopic(['id' => 1]);
        $result = $discussionTopic->delete();

        $this->assertInstanceOf(DiscussionTopic::class, $result);
    }

    public function testDeleteThrowsExceptionWhenIdIsMissing(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Discussion topic ID is required for deletion');

        $discussionTopic = new DiscussionTopic();
        $discussionTopic->delete();
    }

    public function testLock(): void
    {
        $responseData = [
            'id' => 1,
            'title' => 'Test Discussion Topic',
            'locked' => true,
            'course_id' => 123,
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
            ->with('courses/123/discussion_topics/1', $this->callback(function ($options) {
                return isset($options['multipart']);
            }))
            ->willReturn($responseMock);

        $discussionTopic = new DiscussionTopic(['id' => 1, 'title' => 'Test Discussion Topic']);
        $result = $discussionTopic->lock();

        $this->assertTrue($result);
        $this->assertTrue($discussionTopic->getLocked());
    }

    public function testUnlock(): void
    {
        $responseData = [
            'id' => 1,
            'title' => 'Test Discussion Topic',
            'locked' => false,
            'course_id' => 123,
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
            ->with('courses/123/discussion_topics/1', $this->callback(function ($options) {
                return isset($options['multipart']);
            }))
            ->willReturn($responseMock);

        $discussionTopic = new DiscussionTopic(['id' => 1, 'title' => 'Test Discussion Topic']);
        $result = $discussionTopic->unlock();

        $this->assertTrue($result);
        $this->assertFalse($discussionTopic->getLocked());
    }

    public function testPin(): void
    {
        $responseData = [
            'id' => 1,
            'title' => 'Test Discussion Topic',
            'pinned' => true,
            'course_id' => 123,
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
            ->with('courses/123/discussion_topics/1', $this->callback(function ($options) {
                return isset($options['multipart']);
            }))
            ->willReturn($responseMock);

        $discussionTopic = new DiscussionTopic(['id' => 1, 'title' => 'Test Discussion Topic']);
        $result = $discussionTopic->pin();

        $this->assertTrue($result);
        $this->assertTrue($discussionTopic->getPinned());
    }

    public function testUnpin(): void
    {
        $responseData = [
            'id' => 1,
            'title' => 'Test Discussion Topic',
            'pinned' => false,
            'course_id' => 123,
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
            ->with('courses/123/discussion_topics/1', $this->callback(function ($options) {
                return isset($options['multipart']);
            }))
            ->willReturn($responseMock);

        $discussionTopic = new DiscussionTopic(['id' => 1, 'title' => 'Test Discussion Topic']);
        $result = $discussionTopic->unpin();

        $this->assertTrue($result);
        $this->assertFalse($discussionTopic->getPinned());
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Discussion Topic',
            'message' => 'Test message',
            'html_url' => '/courses/123/discussion_topics/1',
            'posted_at' => '2024-01-01T00:00:00Z',
            'discussion_type' => 'threaded',
            'require_initial_post' => true,
            'locked' => false,
            'pinned' => true,
            'course_id' => 123,
            'user_id' => 456,
            'published' => true,
            'workflow_state' => 'active',
            'read_only' => false,
            'assignment_id' => 789,
            'points_possible' => 100.0,
            'grading_type' => 'points',
            'allow_rating' => true,
            'only_graders_can_rate' => false,
            'group_topic' => false,
            'group_category_id' => null,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $discussionTopic = new DiscussionTopic($data);
        $array = $discussionTopic->toArray();

        $this->assertEquals($data['id'], $array['id']);
        $this->assertEquals($data['title'], $array['title']);
        $this->assertEquals($data['message'], $array['message']);
        $this->assertEquals($data['html_url'], $array['html_url']);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $array['posted_at']);
        $this->assertEquals($data['discussion_type'], $array['discussion_type']);
        $this->assertEquals($data['require_initial_post'], $array['require_initial_post']);
        $this->assertEquals($data['locked'], $array['locked']);
        $this->assertEquals($data['pinned'], $array['pinned']);
        $this->assertEquals($data['course_id'], $array['course_id']);
        $this->assertEquals($data['user_id'], $array['user_id']);
        $this->assertEquals($data['published'], $array['published']);
        $this->assertEquals($data['workflow_state'], $array['workflow_state']);
        $this->assertEquals($data['read_only'], $array['read_only']);
        $this->assertEquals($data['assignment_id'], $array['assignment_id']);
        $this->assertEquals($data['points_possible'], $array['points_possible']);
        $this->assertEquals($data['grading_type'], $array['grading_type']);
        $this->assertEquals($data['allow_rating'], $array['allow_rating']);
        $this->assertEquals($data['only_graders_can_rate'], $array['only_graders_can_rate']);
        $this->assertEquals($data['group_topic'], $array['group_topic']);
        $this->assertEquals($data['group_category_id'], $array['group_category_id']);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $array['created_at']);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $array['updated_at']);
    }

    public function testToDtoArray(): void
    {
        $discussionTopic = new DiscussionTopic();
        $discussionTopic->setTitle('Test Discussion Topic');
        $discussionTopic->setMessage('Test message');
        $discussionTopic->setDiscussionType('threaded');
        $discussionTopic->setRequireInitialPost(true);
        $discussionTopic->setLocked(false);
        $discussionTopic->setPinned(true);
        $discussionTopic->setPublished(true);
        $discussionTopic->setPointsPossible(100.0);
        $discussionTopic->setGradingType('points');
        $discussionTopic->setAllowRating(true);

        $dtoArray = $discussionTopic->toDtoArray();

        $this->assertEquals('Test Discussion Topic', $dtoArray['title']);
        $this->assertEquals('Test message', $dtoArray['message']);
        $this->assertEquals('threaded', $dtoArray['discussion_type']);
        $this->assertTrue($dtoArray['require_initial_post']);
        $this->assertFalse($dtoArray['locked']);
        $this->assertTrue($dtoArray['pinned']);
        $this->assertTrue($dtoArray['published']);
        $this->assertEquals(100.0, $dtoArray['points_possible']);
        $this->assertEquals('points', $dtoArray['grading_type']);
        $this->assertTrue($dtoArray['allow_rating']);
    }

    public function testNewCanvasApiPropertiesGettersAndSetters(): void
    {
        $topic = new DiscussionTopic([]);

        // Test lastReplyAt
        $lastReplyAt = new \DateTime('2023-12-25T10:00:00Z');
        $topic->setLastReplyAt($lastReplyAt);
        $this->assertInstanceOf(\DateTime::class, $topic->getLastReplyAt());
        $this->assertEquals('2023-12-25T10:00:00+00:00', $topic->getLastReplyAt()->format('c'));

        // Test userCanSeePosts
        $topic->setUserCanSeePosts(true);
        $this->assertTrue($topic->getUserCanSeePosts());

        // Test discussionSubentryCount
        $topic->setDiscussionSubentryCount(15);
        $this->assertEquals(15, $topic->getDiscussionSubentryCount());

        // Test readState
        $topic->setReadState('read');
        $this->assertEquals('read', $topic->getReadState());

        // Test unreadCount
        $topic->setUnreadCount(3);
        $this->assertEquals(3, $topic->getUnreadCount());

        // Test subscribed
        $topic->setSubscribed(true);
        $this->assertTrue($topic->getSubscribed());

        // Test subscriptionHold
        $topic->setSubscriptionHold('topic_is_announcement');
        $this->assertEquals('topic_is_announcement', $topic->getSubscriptionHold());

        // Test lockedForUser
        $topic->setLockedForUser(false);
        $this->assertFalse($topic->getLockedForUser());

        // Test lockInfo
        $lockInfo = ['locked_at' => '2023-12-31T23:59:59Z'];
        $topic->setLockInfo($lockInfo);
        $this->assertEquals($lockInfo, $topic->getLockInfo());

        // Test lockExplanation
        $topic->setLockExplanation('This discussion is locked until the due date');
        $this->assertEquals('This discussion is locked until the due date', $topic->getLockExplanation());

        // Test userName
        $topic->setUserName('John Instructor');
        $this->assertEquals('John Instructor', $topic->getUserName());

        // Test topicChildren
        $topicChildren = [['id' => 1], ['id' => 2]];
        $topic->setTopicChildren($topicChildren);
        $this->assertEquals($topicChildren, $topic->getTopicChildren());

        // Test groupTopicChildren
        $groupTopicChildren = [['id' => 3], ['id' => 4]];
        $topic->setGroupTopicChildren($groupTopicChildren);
        $this->assertEquals($groupTopicChildren, $topic->getGroupTopicChildren());

        // Test rootTopicId
        $topic->setRootTopicId(456);
        $this->assertEquals(456, $topic->getRootTopicId());

        // Test podcastUrl
        $topic->setPodcastUrl('https://example.com/podcast.xml');
        $this->assertEquals('https://example.com/podcast.xml', $topic->getPodcastUrl());

        // Test attachments
        $attachments = [['filename' => 'test.pdf'], ['filename' => 'image.jpg']];
        $topic->setAttachments($attachments);
        $this->assertEquals($attachments, $topic->getAttachments());

        // Test permissions
        $permissions = ['attach' => true, 'update' => false];
        $topic->setPermissions($permissions);
        $this->assertEquals($permissions, $topic->getPermissions());

        // Test sortByRating
        $topic->setSortByRating(true);
        $this->assertTrue($topic->getSortByRating());

        // Test sortOrder
        $topic->setSortOrder('asc');
        $this->assertEquals('asc', $topic->getSortOrder());

        $topic->setSortOrder('desc');
        $this->assertEquals('desc', $topic->getSortOrder());

        // Test sortOrderLocked
        $topic->setSortOrderLocked(true);
        $this->assertTrue($topic->getSortOrderLocked());

        // Test expand
        $topic->setExpand(false);
        $this->assertFalse($topic->getExpand());

        // Test expandLocked
        $topic->setExpandLocked(true);
        $this->assertTrue($topic->getExpandLocked());
    }

    public function testMarkAsReadSuccess(): void
    {
        $topic = new DiscussionTopic(['id' => 123]);

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('courses/123/discussion_topics/123/read')
            ->willReturn($responseMock);

        $result = $topic->markAsRead();

        $this->assertTrue($result);
        $this->assertEquals('read', $topic->getReadState());
        $this->assertEquals(0, $topic->getUnreadCount());
    }

    public function testMarkAsReadFailure(): void
    {
        $topic = new DiscussionTopic(['id' => 123]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('courses/123/discussion_topics/123/read')
            ->willThrowException(new CanvasApiException('Network error'));

        $result = $topic->markAsRead();

        $this->assertFalse($result);
    }

    public function testMarkAsReadWithoutId(): void
    {
        $topic = new DiscussionTopic([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Discussion topic ID is required');

        $topic->markAsRead();
    }

    public function testMarkAsUnreadSuccess(): void
    {
        $topic = new DiscussionTopic(['id' => 123]);

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('courses/123/discussion_topics/123/read')
            ->willReturn($responseMock);

        $result = $topic->markAsUnread();

        $this->assertTrue($result);
        $this->assertEquals('unread', $topic->getReadState());
    }

    public function testSubscribeSuccess(): void
    {
        $topic = new DiscussionTopic(['id' => 123]);

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('courses/123/discussion_topics/123/subscribed')
            ->willReturn($responseMock);

        $result = $topic->subscribe();

        $this->assertTrue($result);
        $this->assertTrue($topic->getSubscribed());
    }

    public function testUnsubscribeSuccess(): void
    {
        $topic = new DiscussionTopic(['id' => 123]);

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('courses/123/discussion_topics/123/subscribed')
            ->willReturn($responseMock);

        $result = $topic->unsubscribe();

        $this->assertTrue($result);
        $this->assertFalse($topic->getSubscribed());
    }

    public function testMarkAllAsReadSuccess(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('courses/123/discussion_topics/read_all')
            ->willReturn($responseMock);

        $result = DiscussionTopic::markAllAsRead();

        $this->assertTrue($result);
    }

    public function testMarkAllAsUnreadSuccess(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('courses/123/discussion_topics/read_all')
            ->willReturn($responseMock);

        $result = DiscussionTopic::markAllAsUnread();

        $this->assertTrue($result);
    }

    public function testToArrayIncludesNewProperties(): void
    {
        $topic = new DiscussionTopic([
            'id' => 123,
            'title' => 'Test Discussion',
            'lastReplyAt' => '2023-12-25T10:00:00Z',
            'userCanSeePosts' => true,
            'discussionSubentryCount' => 15,
            'readState' => 'read',
            'unreadCount' => 3,
            'subscribed' => true,
            'userName' => 'John Instructor',
            'sortOrder' => 'asc',
            'sortOrderLocked' => true,
            'expand' => false,
            'expandLocked' => true,
        ]);

        $array = $topic->toArray();

        $this->assertEquals(123, $array['id']);
        $this->assertEquals('Test Discussion', $array['title']);
        $this->assertEquals('2023-12-25T10:00:00+00:00', $array['last_reply_at']);
        $this->assertTrue($array['user_can_see_posts']);
        $this->assertEquals(15, $array['discussion_subentry_count']);
        $this->assertEquals('read', $array['read_state']);
        $this->assertEquals(3, $array['unread_count']);
        $this->assertTrue($array['subscribed']);
        $this->assertEquals('John Instructor', $array['user_name']);
        $this->assertEquals('asc', $array['sort_order']);
        $this->assertTrue($array['sort_order_locked']);
        $this->assertFalse($array['expand']);
        $this->assertTrue($array['expand_locked']);
    }
}
