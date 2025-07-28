<?php

namespace Tests\Dto\DiscussionTopics;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\DiscussionTopics\UpdateDiscussionTopicDTO;

/**
 * @covers \CanvasLMS\Dto\DiscussionTopics\UpdateDiscussionTopicDTO
 */
class UpdateDiscussionTopicDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdateDiscussionTopicDTO([]);

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
            'title' => 'Updated Discussion Topic',
            'message' => 'Updated discussion message',
            'discussion_type' => 'side_comment',
            'require_initial_post' => false,
            'locked' => true,
            'pinned' => false,
            'published' => false,
            'assignment_id' => 789,
            'points_possible' => 150.0,
            'grading_type' => 'percent',
            'allow_rating' => false,
            'only_graders_can_rate' => true,
            'group_category_id' => 321,
            'read_only' => true,
            'posted_at' => '2024-02-01T00:00:00Z'
        ];

        $dto = new UpdateDiscussionTopicDTO($data);

        $this->assertEquals('Updated Discussion Topic', $dto->getTitle());
        $this->assertEquals('Updated discussion message', $dto->getMessage());
        $this->assertEquals('side_comment', $dto->getDiscussionType());
        $this->assertFalse($dto->getRequireInitialPost());
        $this->assertTrue($dto->getLocked());
        $this->assertFalse($dto->getPinned());
        $this->assertFalse($dto->getPublished());
        $this->assertEquals(789, $dto->getAssignmentId());
        $this->assertEquals(150.0, $dto->getPointsPossible());
        $this->assertEquals('percent', $dto->getGradingType());
        $this->assertFalse($dto->getAllowRating());
        $this->assertTrue($dto->getOnlyGradersCanRate());
        $this->assertEquals(321, $dto->getGroupCategoryId());
        $this->assertTrue($dto->getReadOnly());
        $this->assertEquals('2024-02-01T00:00:00Z', $dto->getPostedAt());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new UpdateDiscussionTopicDTO([]);

        $dto->setTitle('Updated Discussion Topic');
        $this->assertEquals('Updated Discussion Topic', $dto->getTitle());

        $dto->setMessage('Updated discussion message');
        $this->assertEquals('Updated discussion message', $dto->getMessage());

        $dto->setDiscussionType('side_comment');
        $this->assertEquals('side_comment', $dto->getDiscussionType());

        $dto->setRequireInitialPost(false);
        $this->assertFalse($dto->getRequireInitialPost());

        $dto->setLocked(true);
        $this->assertTrue($dto->getLocked());

        $dto->setPinned(false);
        $this->assertFalse($dto->getPinned());

        $dto->setPublished(false);
        $this->assertFalse($dto->getPublished());

        $dto->setAssignmentId(789);
        $this->assertEquals(789, $dto->getAssignmentId());

        $dto->setPointsPossible(150.0);
        $this->assertEquals(150.0, $dto->getPointsPossible());

        $dto->setGradingType('percent');
        $this->assertEquals('percent', $dto->getGradingType());

        $dto->setAllowRating(false);
        $this->assertFalse($dto->getAllowRating());

        $dto->setOnlyGradersCanRate(true);
        $this->assertTrue($dto->getOnlyGradersCanRate());

        $dto->setGroupCategoryId(321);
        $this->assertEquals(321, $dto->getGroupCategoryId());

        $dto->setReadOnly(true);
        $this->assertTrue($dto->getReadOnly());

        $dto->setPostedAt('2024-02-01T00:00:00Z');
        $this->assertEquals('2024-02-01T00:00:00Z', $dto->getPostedAt());

        $dto->setLockAt('2024-12-31T23:59:59Z');
        $this->assertEquals('2024-12-31T23:59:59Z', $dto->getLockAt());

        $dto->setDelayedPostAt('2024-06-01T00:00:00Z');
        $this->assertEquals('2024-06-01T00:00:00Z', $dto->getDelayedPostAt());

        $podcastSettings = ['enabled' => false, 'feed_code' => 'updated123'];
        $dto->setPodcastSettings($podcastSettings);
        $this->assertEquals($podcastSettings, $dto->getPodcastSettings());

        $attachment = ['uploaded_file' => 'newfile456'];
        $dto->setAttachment($attachment);
        $this->assertEquals($attachment, $dto->getAttachment());

        $sections = [4, 5, 6];
        $dto->setSpecificSections($sections);
        $this->assertEquals($sections, $dto->getSpecificSections());
    }

    public function testNullValues(): void
    {
        $dto = new UpdateDiscussionTopicDTO([]);

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

    public function testPartialUpdates(): void
    {
        // Test that UpdateDTO supports partial updates (only some fields set)
        $dto = new UpdateDiscussionTopicDTO([]);

        // Only set title and pinned status
        $dto->setTitle('Partially Updated Discussion');
        $dto->setPinned(true);

        $this->assertEquals('Partially Updated Discussion', $dto->getTitle());
        $this->assertTrue($dto->getPinned());

        // All other fields should remain null
        $this->assertNull($dto->getMessage());
        $this->assertNull($dto->getDiscussionType());
        $this->assertNull($dto->getRequireInitialPost());
        $this->assertNull($dto->getLocked());
        $this->assertNull($dto->getPublished());
        $this->assertNull($dto->getAssignmentId());
        $this->assertNull($dto->getPointsPossible());
        $this->assertNull($dto->getGradingType());
    }

    public function testArrayValues(): void
    {
        $dto = new UpdateDiscussionTopicDTO([]);

        $podcastSettings = [
            'enabled' => false,
            'feed_code' => 'updated123',
            'url' => 'https://example.com/updated-feed'
        ];
        $dto->setPodcastSettings($podcastSettings);
        $this->assertEquals($podcastSettings, $dto->getPodcastSettings());

        $attachment = [
            'uploaded_file' => 'updatedfile456',
            'display_name' => 'Updated Test File'
        ];
        $dto->setAttachment($attachment);
        $this->assertEquals($attachment, $dto->getAttachment());

        $sections = [7, 8, 9, 10];
        $dto->setSpecificSections($sections);
        $this->assertEquals($sections, $dto->getSpecificSections());

        $emptyArray = [];
        $dto->setSpecificSections($emptyArray);
        $this->assertEquals($emptyArray, $dto->getSpecificSections());
    }

    public function testBooleanUpdates(): void
    {
        $dto = new UpdateDiscussionTopicDTO([]);

        // Test switching boolean values
        $dto->setRequireInitialPost(true);
        $this->assertTrue($dto->getRequireInitialPost());
        $dto->setRequireInitialPost(false);
        $this->assertFalse($dto->getRequireInitialPost());

        $dto->setLocked(false);
        $this->assertFalse($dto->getLocked());
        $dto->setLocked(true);
        $this->assertTrue($dto->getLocked());

        $dto->setPinned(true);
        $this->assertTrue($dto->getPinned());
        $dto->setPinned(false);
        $this->assertFalse($dto->getPinned());

        $dto->setPublished(false);
        $this->assertFalse($dto->getPublished());
        $dto->setPublished(true);
        $this->assertTrue($dto->getPublished());

        $dto->setAllowRating(true);
        $this->assertTrue($dto->getAllowRating());
        $dto->setAllowRating(false);
        $this->assertFalse($dto->getAllowRating());

        $dto->setOnlyGradersCanRate(false);
        $this->assertFalse($dto->getOnlyGradersCanRate());
        $dto->setOnlyGradersCanRate(true);
        $this->assertTrue($dto->getOnlyGradersCanRate());

        $dto->setReadOnly(true);
        $this->assertTrue($dto->getReadOnly());
        $dto->setReadOnly(false);
        $this->assertFalse($dto->getReadOnly());
    }

    public function testNumericUpdates(): void
    {
        $dto = new UpdateDiscussionTopicDTO([]);

        // Test updating integer values
        $dto->setAssignmentId(100);
        $this->assertEquals(100, $dto->getAssignmentId());
        $dto->setAssignmentId(200);
        $this->assertEquals(200, $dto->getAssignmentId());

        $dto->setGroupCategoryId(300);
        $this->assertEquals(300, $dto->getGroupCategoryId());
        $dto->setGroupCategoryId(400);
        $this->assertEquals(400, $dto->getGroupCategoryId());

        // Test updating float values
        $dto->setPointsPossible(50.5);
        $this->assertEquals(50.5, $dto->getPointsPossible());
        $dto->setPointsPossible(75.25);
        $this->assertEquals(75.25, $dto->getPointsPossible());

        // Test setting to zero
        $dto->setAssignmentId(0);
        $this->assertEquals(0, $dto->getAssignmentId());

        $dto->setPointsPossible(0.0);
        $this->assertEquals(0.0, $dto->getPointsPossible());
    }

    public function testStringUpdates(): void
    {
        $dto = new UpdateDiscussionTopicDTO([]);

        // Test updating title
        $dto->setTitle('Original Title');
        $this->assertEquals('Original Title', $dto->getTitle());
        $dto->setTitle('Updated Title');
        $this->assertEquals('Updated Title', $dto->getTitle());

        // Test updating message with HTML
        $originalMessage = '<p>Original message</p>';
        $dto->setMessage($originalMessage);
        $this->assertEquals($originalMessage, $dto->getMessage());

        $updatedMessage = '<p>Updated message with <strong>formatting</strong></p>';
        $dto->setMessage($updatedMessage);
        $this->assertEquals($updatedMessage, $dto->getMessage());

        // Test updating discussion type
        $dto->setDiscussionType('threaded');
        $this->assertEquals('threaded', $dto->getDiscussionType());
        $dto->setDiscussionType('side_comment');
        $this->assertEquals('side_comment', $dto->getDiscussionType());

        // Test updating grading type
        $dto->setGradingType('points');
        $this->assertEquals('points', $dto->getGradingType());
        $dto->setGradingType('percent');
        $this->assertEquals('percent', $dto->getGradingType());

        // Test updating dates
        $dto->setPostedAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $dto->getPostedAt());
        $dto->setPostedAt('2024-02-01T12:00:00Z');
        $this->assertEquals('2024-02-01T12:00:00Z', $dto->getPostedAt());

        // Test setting empty strings
        $dto->setTitle('');
        $this->assertEquals('', $dto->getTitle());

        $dto->setMessage('');
        $this->assertEquals('', $dto->getMessage());
    }

    public function testUpdateWithConstructorData(): void
    {
        // Test partial update via constructor
        $updateData = [
            'title' => 'Constructor Updated Title',
            'pinned' => true,
            'points_possible' => 125.0
        ];

        $dto = new UpdateDiscussionTopicDTO($updateData);

        $this->assertEquals('Constructor Updated Title', $dto->getTitle());
        $this->assertTrue($dto->getPinned());
        $this->assertEquals(125.0, $dto->getPointsPossible());

        // Fields not in constructor data should be null
        $this->assertNull($dto->getMessage());
        $this->assertNull($dto->getDiscussionType());
        $this->assertNull($dto->getLocked());
        $this->assertNull($dto->getPublished());
    }

    public function testNewCanvasApiProperties(): void
    {
        $dto = new UpdateDiscussionTopicDTO([]);
        
        // Test isAnnouncement
        $dto->setIsAnnouncement(true);
        $this->assertTrue($dto->getIsAnnouncement());
        
        $dto->setIsAnnouncement(false);
        $this->assertFalse($dto->getIsAnnouncement());
        
        $dto->setIsAnnouncement(null);
        $this->assertNull($dto->getIsAnnouncement());
        
        // Test positionAfter
        $dto->setPositionAfter('456');
        $this->assertEquals('456', $dto->getPositionAfter());
        
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
        $dto->setPodcastHasStudentPosts(true);
        $this->assertTrue($dto->getPodcastHasStudentPosts());
        
        $dto->setPodcastHasStudentPosts(false);
        $this->assertFalse($dto->getPodcastHasStudentPosts());
        
        $dto->setPodcastHasStudentPosts(null);
        $this->assertNull($dto->getPodcastHasStudentPosts());
        
        // Test assignment array
        $assignment = ['points_possible' => 50, 'grading_type' => 'percent'];
        $dto->setAssignment($assignment);
        $this->assertEquals($assignment, $dto->getAssignment());
        
        $dto->setAssignment(null);
        $this->assertNull($dto->getAssignment());
        
        // Test podcastSettings array (renamed from podcastEnabled array)
        $podcastSettings = ['feed_code' => 'xyz789', 'enabled' => false];
        $dto->setPodcastSettings($podcastSettings);
        $this->assertEquals($podcastSettings, $dto->getPodcastSettings());
        
        $dto->setPodcastSettings(null);
        $this->assertNull($dto->getPodcastSettings());
        
        // Test sortOrder
        $dto->setSortOrder('desc');
        $this->assertEquals('desc', $dto->getSortOrder());
        
        $dto->setSortOrder('asc');
        $this->assertEquals('asc', $dto->getSortOrder());
        
        $dto->setSortOrder(null);
        $this->assertNull($dto->getSortOrder());
        
        // Test sortOrderLocked
        $dto->setSortOrderLocked(false);
        $this->assertFalse($dto->getSortOrderLocked());
        
        $dto->setSortOrderLocked(true);
        $this->assertTrue($dto->getSortOrderLocked());
        
        $dto->setSortOrderLocked(null);
        $this->assertNull($dto->getSortOrderLocked());
        
        // Test expanded
        $dto->setExpanded(false);
        $this->assertFalse($dto->getExpanded());
        
        $dto->setExpanded(true);
        $this->assertTrue($dto->getExpanded());
        
        $dto->setExpanded(null);
        $this->assertNull($dto->getExpanded());
        
        // Test expandedLocked
        $dto->setExpandedLocked(false);
        $this->assertFalse($dto->getExpandedLocked());
        
        $dto->setExpandedLocked(true);
        $this->assertTrue($dto->getExpandedLocked());
        
        $dto->setExpandedLocked(null);
        $this->assertNull($dto->getExpandedLocked());
        
        // Test lockComment
        $dto->setLockComment(false);
        $this->assertFalse($dto->getLockComment());
        
        $dto->setLockComment(true);
        $this->assertTrue($dto->getLockComment());
        
        $dto->setLockComment(null);
        $this->assertNull($dto->getLockComment());
    }
}