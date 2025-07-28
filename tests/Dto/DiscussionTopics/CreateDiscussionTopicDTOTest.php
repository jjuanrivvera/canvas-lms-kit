<?php

namespace Tests\Dto\DiscussionTopics;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\DiscussionTopics\CreateDiscussionTopicDTO;

/**
 * @covers \CanvasLMS\Dto\DiscussionTopics\CreateDiscussionTopicDTO
 */
class CreateDiscussionTopicDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateDiscussionTopicDTO([]);

        $this->assertNull($dto->getTitle());
        $this->assertNull($dto->getMessage());
        $this->assertNull($dto->getDiscussionType());
        $this->assertNull($dto->getRequireInitialPost());
        $this->assertNull($dto->getLocked());
        $this->assertNull($dto->getPinned());
        $this->assertNull($dto->getPublished());
        $this->assertNull($dto->getAssignmentId());
        $this->assertNull($dto->getPointsPossible());
        $this->assertNull($dto->getGradingType());
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'title' => 'Test Discussion Topic',
            'message' => 'Test discussion message',
            'discussion_type' => 'threaded',
            'require_initial_post' => true,
            'locked' => false,
            'pinned' => true,
            'published' => true,
            'assignment_id' => 123,
            'points_possible' => 100.0,
            'grading_type' => 'points',
            'allow_rating' => true,
            'only_graders_can_rate' => false,
            'group_category_id' => 456,
            'read_only' => false,
            'posted_at' => '2024-01-01T00:00:00Z'
        ];

        $dto = new CreateDiscussionTopicDTO($data);

        $this->assertEquals('Test Discussion Topic', $dto->getTitle());
        $this->assertEquals('Test discussion message', $dto->getMessage());
        $this->assertEquals('threaded', $dto->getDiscussionType());
        $this->assertTrue($dto->getRequireInitialPost());
        $this->assertFalse($dto->getLocked());
        $this->assertTrue($dto->getPinned());
        $this->assertTrue($dto->getPublished());
        $this->assertEquals(123, $dto->getAssignmentId());
        $this->assertEquals(100.0, $dto->getPointsPossible());
        $this->assertEquals('points', $dto->getGradingType());
        $this->assertTrue($dto->getAllowRating());
        $this->assertFalse($dto->getOnlyGradersCanRate());
        $this->assertEquals(456, $dto->getGroupCategoryId());
        $this->assertFalse($dto->getReadOnly());
        $this->assertEquals('2024-01-01T00:00:00Z', $dto->getPostedAt());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new CreateDiscussionTopicDTO([]);

        $dto->setTitle('Test Discussion Topic');
        $this->assertEquals('Test Discussion Topic', $dto->getTitle());

        $dto->setMessage('Test discussion message');
        $this->assertEquals('Test discussion message', $dto->getMessage());

        $dto->setDiscussionType('threaded');
        $this->assertEquals('threaded', $dto->getDiscussionType());

        $dto->setRequireInitialPost(true);
        $this->assertTrue($dto->getRequireInitialPost());

        $dto->setLocked(false);
        $this->assertFalse($dto->getLocked());

        $dto->setPinned(true);
        $this->assertTrue($dto->getPinned());

        $dto->setPublished(true);
        $this->assertTrue($dto->getPublished());

        $dto->setAssignmentId(123);
        $this->assertEquals(123, $dto->getAssignmentId());

        $dto->setPointsPossible(100.0);
        $this->assertEquals(100.0, $dto->getPointsPossible());

        $dto->setGradingType('points');
        $this->assertEquals('points', $dto->getGradingType());

        $dto->setAllowRating(true);
        $this->assertTrue($dto->getAllowRating());

        $dto->setOnlyGradersCanRate(false);
        $this->assertFalse($dto->getOnlyGradersCanRate());

        $dto->setGroupCategoryId(456);
        $this->assertEquals(456, $dto->getGroupCategoryId());

        $dto->setReadOnly(false);
        $this->assertFalse($dto->getReadOnly());

        $dto->setPostedAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $dto->getPostedAt());

        $dto->setLockAt('2024-12-31T23:59:59Z');
        $this->assertEquals('2024-12-31T23:59:59Z', $dto->getLockAt());

        $dto->setDelayedPostAt('2024-06-01T00:00:00Z');
        $this->assertEquals('2024-06-01T00:00:00Z', $dto->getDelayedPostAt());

        $podcastSettings = ['enabled' => true, 'feed_code' => 'test123'];
        $dto->setPodcastSettings($podcastSettings);
        $this->assertEquals($podcastSettings, $dto->getPodcastSettings());

        $attachment = ['uploaded_file' => 'file123'];
        $dto->setAttachment($attachment);
        $this->assertEquals($attachment, $dto->getAttachment());

        $sections = [1, 2, 3];
        $dto->setSpecificSections($sections);
        $this->assertEquals($sections, $dto->getSpecificSections());
    }

    public function testNullValues(): void
    {
        $dto = new CreateDiscussionTopicDTO([]);

        $dto->setTitle(null);
        $this->assertNull($dto->getTitle());

        $dto->setMessage(null);
        $this->assertNull($dto->getMessage());

        $dto->setDiscussionType(null);
        $this->assertNull($dto->getDiscussionType());

        $dto->setRequireInitialPost(null);
        $this->assertNull($dto->getRequireInitialPost());

        $dto->setLocked(null);
        $this->assertNull($dto->getLocked());

        $dto->setPinned(null);
        $this->assertNull($dto->getPinned());

        $dto->setPublished(null);
        $this->assertNull($dto->getPublished());

        $dto->setAssignmentId(null);
        $this->assertNull($dto->getAssignmentId());

        $dto->setPointsPossible(null);
        $this->assertNull($dto->getPointsPossible());

        $dto->setGradingType(null);
        $this->assertNull($dto->getGradingType());

        $dto->setAllowRating(null);
        $this->assertNull($dto->getAllowRating());

        $dto->setOnlyGradersCanRate(null);
        $this->assertNull($dto->getOnlyGradersCanRate());

        $dto->setGroupCategoryId(null);
        $this->assertNull($dto->getGroupCategoryId());

        $dto->setReadOnly(null);
        $this->assertNull($dto->getReadOnly());

        $dto->setPostedAt(null);
        $this->assertNull($dto->getPostedAt());

        $dto->setLockAt(null);
        $this->assertNull($dto->getLockAt());

        $dto->setDelayedPostAt(null);
        $this->assertNull($dto->getDelayedPostAt());

        $dto->setPodcastSettings(null);
        $this->assertNull($dto->getPodcastSettings());

        $dto->setAttachment(null);
        $this->assertNull($dto->getAttachment());

        $dto->setSpecificSections(null);
        $this->assertNull($dto->getSpecificSections());
    }

    public function testArrayValues(): void
    {
        $dto = new CreateDiscussionTopicDTO([]);

        $podcastSettings = [
            'enabled' => true,
            'feed_code' => 'test123',
            'url' => 'https://example.com/feed'
        ];
        $dto->setPodcastSettings($podcastSettings);
        $this->assertEquals($podcastSettings, $dto->getPodcastSettings());

        $attachment = [
            'uploaded_file' => 'file123',
            'display_name' => 'Test File'
        ];
        $dto->setAttachment($attachment);
        $this->assertEquals($attachment, $dto->getAttachment());

        $sections = [1, 2, 3, 4, 5];
        $dto->setSpecificSections($sections);
        $this->assertEquals($sections, $dto->getSpecificSections());

        $emptyArray = [];
        $dto->setSpecificSections($emptyArray);
        $this->assertEquals($emptyArray, $dto->getSpecificSections());
    }

    public function testBooleanValues(): void
    {
        $dto = new CreateDiscussionTopicDTO([]);

        // Test true values
        $dto->setRequireInitialPost(true);
        $this->assertTrue($dto->getRequireInitialPost());

        $dto->setLocked(true);
        $this->assertTrue($dto->getLocked());

        $dto->setPinned(true);
        $this->assertTrue($dto->getPinned());

        $dto->setPublished(true);
        $this->assertTrue($dto->getPublished());

        $dto->setAllowRating(true);
        $this->assertTrue($dto->getAllowRating());

        $dto->setOnlyGradersCanRate(true);
        $this->assertTrue($dto->getOnlyGradersCanRate());

        $dto->setReadOnly(true);
        $this->assertTrue($dto->getReadOnly());

        // Test false values
        $dto->setRequireInitialPost(false);
        $this->assertFalse($dto->getRequireInitialPost());

        $dto->setLocked(false);
        $this->assertFalse($dto->getLocked());

        $dto->setPinned(false);
        $this->assertFalse($dto->getPinned());

        $dto->setPublished(false);
        $this->assertFalse($dto->getPublished());

        $dto->setAllowRating(false);
        $this->assertFalse($dto->getAllowRating());

        $dto->setOnlyGradersCanRate(false);
        $this->assertFalse($dto->getOnlyGradersCanRate());

        $dto->setReadOnly(false);
        $this->assertFalse($dto->getReadOnly());
    }

    public function testNumericValues(): void
    {
        $dto = new CreateDiscussionTopicDTO([]);

        // Test integer values
        $dto->setAssignmentId(123);
        $this->assertEquals(123, $dto->getAssignmentId());

        $dto->setGroupCategoryId(456);
        $this->assertEquals(456, $dto->getGroupCategoryId());

        // Test float values
        $dto->setPointsPossible(100.5);
        $this->assertEquals(100.5, $dto->getPointsPossible());

        $dto->setPointsPossible(0.0);
        $this->assertEquals(0.0, $dto->getPointsPossible());

        // Test zero values
        $dto->setAssignmentId(0);
        $this->assertEquals(0, $dto->getAssignmentId());

        $dto->setGroupCategoryId(0);
        $this->assertEquals(0, $dto->getGroupCategoryId());
    }

    public function testStringValues(): void
    {
        $dto = new CreateDiscussionTopicDTO([]);

        // Test basic string values
        $dto->setTitle('Simple Title');
        $this->assertEquals('Simple Title', $dto->getTitle());

        $dto->setMessage('Simple message');
        $this->assertEquals('Simple message', $dto->getMessage());

        // Test HTML content
        $htmlMessage = '<p>This is a <strong>bold</strong> message with <em>emphasis</em>.</p>';
        $dto->setMessage($htmlMessage);
        $this->assertEquals($htmlMessage, $dto->getMessage());

        // Test discussion types
        $dto->setDiscussionType('threaded');
        $this->assertEquals('threaded', $dto->getDiscussionType());

        $dto->setDiscussionType('side_comment');
        $this->assertEquals('side_comment', $dto->getDiscussionType());

        $dto->setDiscussionType('flat');
        $this->assertEquals('flat', $dto->getDiscussionType());

        // Test grading types
        $dto->setGradingType('points');
        $this->assertEquals('points', $dto->getGradingType());

        $dto->setGradingType('percent');
        $this->assertEquals('percent', $dto->getGradingType());

        $dto->setGradingType('pass_fail');
        $this->assertEquals('pass_fail', $dto->getGradingType());

        // Test date strings
        $dto->setPostedAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $dto->getPostedAt());

        $dto->setLockAt('2024-12-31T23:59:59Z');
        $this->assertEquals('2024-12-31T23:59:59Z', $dto->getLockAt());

        $dto->setDelayedPostAt('2024-06-15T12:00:00Z');
        $this->assertEquals('2024-06-15T12:00:00Z', $dto->getDelayedPostAt());

        // Test empty strings
        $dto->setTitle('');
        $this->assertEquals('', $dto->getTitle());

        $dto->setMessage('');
        $this->assertEquals('', $dto->getMessage());
    }

    public function testNewCanvasApiProperties(): void
    {
        $dto = new CreateDiscussionTopicDTO([]);
        
        // Test isAnnouncement
        $dto->setIsAnnouncement(true);
        $this->assertTrue($dto->getIsAnnouncement());
        
        $dto->setIsAnnouncement(false);
        $this->assertFalse($dto->getIsAnnouncement());
        
        $dto->setIsAnnouncement(null);
        $this->assertNull($dto->getIsAnnouncement());
        
        // Test positionAfter
        $dto->setPositionAfter('123');
        $this->assertEquals('123', $dto->getPositionAfter());
        
        $dto->setPositionAfter(null);
        $this->assertNull($dto->getPositionAfter());
        
        // Test podcastEnabled (boolean)
        $dto->setPodcastEnabled(true);
        $this->assertTrue($dto->getPodcastEnabled());
        
        $dto->setPodcastEnabled(false);
        $this->assertFalse($dto->getPodcastEnabled());
        
        $dto->setPodcastEnabled(null);
        $this->assertNull($dto->getPodcastEnabled());
        
        // Test podcastHasStudentPosts
        $dto->setPodcastHasStudentPosts(false);
        $this->assertFalse($dto->getPodcastHasStudentPosts());
        
        $dto->setPodcastHasStudentPosts(true);
        $this->assertTrue($dto->getPodcastHasStudentPosts());
        
        $dto->setPodcastHasStudentPosts(null);
        $this->assertNull($dto->getPodcastHasStudentPosts());
        
        // Test assignment array
        $assignment = ['points_possible' => 25, 'due_at' => '2023-12-31T23:59:59Z'];
        $dto->setAssignment($assignment);
        $this->assertEquals($assignment, $dto->getAssignment());
        
        $dto->setAssignment(null);
        $this->assertNull($dto->getAssignment());
        
        // Test podcastSettings array (renamed from podcastEnabled array)
        $podcastSettings = ['feed_code' => 'abc123', 'enabled' => true];
        $dto->setPodcastSettings($podcastSettings);
        $this->assertEquals($podcastSettings, $dto->getPodcastSettings());
        
        $dto->setPodcastSettings(null);
        $this->assertNull($dto->getPodcastSettings());
        
        // Test sortOrder
        $dto->setSortOrder('asc');
        $this->assertEquals('asc', $dto->getSortOrder());
        
        $dto->setSortOrder('desc');
        $this->assertEquals('desc', $dto->getSortOrder());
        
        $dto->setSortOrder(null);
        $this->assertNull($dto->getSortOrder());
        
        // Test sortOrderLocked
        $dto->setSortOrderLocked(true);
        $this->assertTrue($dto->getSortOrderLocked());
        
        $dto->setSortOrderLocked(false);
        $this->assertFalse($dto->getSortOrderLocked());
        
        $dto->setSortOrderLocked(null);
        $this->assertNull($dto->getSortOrderLocked());
        
        // Test expanded
        $dto->setExpanded(true);
        $this->assertTrue($dto->getExpanded());
        
        $dto->setExpanded(false);
        $this->assertFalse($dto->getExpanded());
        
        $dto->setExpanded(null);
        $this->assertNull($dto->getExpanded());
        
        // Test expandedLocked
        $dto->setExpandedLocked(true);
        $this->assertTrue($dto->getExpandedLocked());
        
        $dto->setExpandedLocked(false);
        $this->assertFalse($dto->getExpandedLocked());
        
        $dto->setExpandedLocked(null);
        $this->assertNull($dto->getExpandedLocked());
        
        // Test lockComment
        $dto->setLockComment(true);
        $this->assertTrue($dto->getLockComment());
        
        $dto->setLockComment(false);
        $this->assertFalse($dto->getLockComment());
        
        $dto->setLockComment(null);
        $this->assertNull($dto->getLockComment());
    }
}