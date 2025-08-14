<?php

declare(strict_types=1);

namespace CanvasLMS\Api\DiscussionTopics;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Dto\DiscussionTopics\CreateDiscussionTopicDTO;
use CanvasLMS\Dto\DiscussionTopics\UpdateDiscussionTopicDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Discussion Topics API
 *
 * Provides functionality to manage discussion topics in Canvas LMS.
 * This class handles creating, reading, updating, and deleting discussion topics for a specific course.
 *
 * Usage Examples:
 *
 * ```php
 * // Set course context (required for all operations)
 * $course = Course::find(123);
 * DiscussionTopic::setCourse($course);
 *
 * // Create a new discussion topic
 * $discussionData = [
 *     'title' => 'Weekly Discussion: Introduction',
 *     'message' => 'Please introduce yourself to the class',
 *     'discussion_type' => 'threaded',
 *     'require_initial_post' => true
 * ];
 * $discussion = DiscussionTopic::create($discussionData);
 *
 * // Find a discussion topic by ID
 * $discussion = DiscussionTopic::find(456);
 *
 * // List all discussion topics for the course
 * $discussions = DiscussionTopic::fetchAll();
 *
 * // Get paginated discussion topics
 * $paginatedDiscussions = DiscussionTopic::fetchAllPaginated();
 * $paginationResult = DiscussionTopic::fetchPage();
 *
 * // Update a discussion topic
 * $updatedDiscussion = DiscussionTopic::update(456, ['title' => 'Updated Discussion Title']);
 *
 * // Update using DTO
 * $updateDto = new UpdateDiscussionTopicDTO(['title' => 'New Title', 'pinned' => true]);
 * $updatedDiscussion = DiscussionTopic::update(456, $updateDto);
 *
 * // Update using instance method
 * $discussion = DiscussionTopic::find(456);
 * $discussion->setTitle('Updated Title');
 * $success = $discussion->save();
 *
 * // Delete a discussion topic
 * $discussion = DiscussionTopic::find(456);
 * $success = $discussion->delete();
 *
 * // Pin/unpin a discussion
 * $discussion->pin();
 * $discussion->unpin();
 *
 * // Lock/unlock a discussion
 * $discussion->lock();
 * $discussion->unlock();
 * ```
 *
 * @package CanvasLMS\Api\DiscussionTopics
 */
class DiscussionTopic extends AbstractBaseApi
{
    protected static Course $course;

    /**
     * Discussion topic unique identifier
     */
    public ?int $id = null;

    /**
     * Discussion topic title
     */
    public ?string $title = null;

    /**
     * Discussion topic message/description (HTML)
     */
    public ?string $message = null;

    /**
     * HTML URL to the discussion topic
     */
    public ?string $htmlUrl = null;

    /**
     * When the discussion topic was posted
     */
    public ?string $postedAt = null;

    /**
     * Discussion type (threaded, side_comment, etc.)
     */
    public ?string $discussionType = null;

    /**
     * Whether users must post before seeing replies
     */
    public ?bool $requireInitialPost = null;

    /**
     * Whether the discussion is locked
     */
    public ?bool $locked = null;

    /**
     * Whether the discussion is pinned
     */
    public ?bool $pinned = null;

    /**
     * Course ID this discussion topic belongs to
     */
    public ?int $courseId = null;

    /**
     * User ID of the discussion author
     */
    public ?int $userId = null;

    /**
     * Discussion topic author information
     * @var array<string, mixed>|null
     */
    public ?array $author = null;

    /**
     * Whether the discussion topic is published
     */
    public ?bool $published = null;

    /**
     * Discussion workflow state
     */
    public ?string $workflowState = null;

    /**
     * Whether the discussion is read only
     */
    public ?bool $readOnly = null;

    /**
     * Assignment ID for graded discussions
     */
    public ?int $assignmentId = null;

    /**
     * Points possible for graded discussions
     */
    public ?float $pointsPossible = null;

    /**
     * Grading type for graded discussions
     */
    public ?string $gradingType = null;

    /**
     * Whether the discussion allows rating
     */
    public ?bool $allowRating = null;

    /**
     * Whether rating is only for graders
     */
    public ?bool $onlyGradersCanRate = null;

    /**
     * Whether the discussion is for a group
     */
    public ?bool $groupTopic = null;

    /**
     * Group category ID for group discussions
     */
    public ?int $groupCategoryId = null;

    /**
     * When the last reply was posted
     */
    public ?string $lastReplyAt = null;

    /**
     * Whether users can see posts (based on require_initial_post)
     */
    public ?bool $userCanSeePosts = null;

    /**
     * Number of replies in the topic
     */
    public ?int $discussionSubentryCount = null;

    /**
     * Read state for the current user (read, unread, partially_read)
     */
    public ?string $readState = null;

    /**
     * Number of unread entries for the current user
     */
    public ?int $unreadCount = null;

    /**
     * Whether the current user is subscribed to notifications
     */
    public ?bool $subscribed = null;

    /**
     * Subscription hold status
     */
    public ?string $subscriptionHold = null;

    /**
     * When the topic will be automatically published
     */
    public ?string $delayedPostAt = null;

    /**
     * Whether the topic is locked for the current user
     */
    public ?bool $lockedForUser = null;

    /**
     * Information about why the topic is locked
     * @var array<string, mixed>|null
     */
    public ?array $lockInfo = null;

    /**
     * Explanation of why the topic is locked
     */
    public ?string $lockExplanation = null;

    /**
     * Name of the topic author
     */
    public ?string $userName = null;

    /**
     * Child topics (for threaded discussions)
     * @var array<mixed>|null
     */
    public ?array $topicChildren = null;

    /**
     * Group-specific child topics
     * @var array<mixed>|null
     */
    public ?array $groupTopicChildren = null;

    /**
     * ID of the root topic (for group discussions)
     */
    public ?int $rootTopicId = null;

    /**
     * URL for podcast feed
     */
    public ?string $podcastUrl = null;

    /**
     * Array of file attachments
     * @var array<mixed>|null
     */
    public ?array $attachments = null;

    /**
     * User permissions for the topic
     * @var array<string, mixed>|null
     */
    public ?array $permissions = null;

    /**
     * Whether to sort entries by rating
     */
    public ?bool $sortByRating = null;

    /**
     * Default sort order of the discussion (asc or desc)
     */
    public ?string $sortOrder = null;

    /**
     * Whether users can choose their preferred sort order
     */
    public ?bool $sortOrderLocked = null;

    /**
     * Whether threaded replies should be expanded by default
     */
    public ?bool $expand = null;

    /**
     * Whether users can choose their preferred thread expansion setting
     */
    public ?bool $expandLocked = null;

    /**
     * Whether this is an announcement
     */
    public ?bool $isAnnouncement = null;

    /**
     * Discussion topic creation timestamp
     */
    public ?string $createdAt = null;

    /**
     * Discussion topic last update timestamp
     */
    public ?string $updatedAt = null;

    /**
     * Create a new DiscussionTopic instance
     *
     * @param array<string, mixed> $data Discussion topic data from Canvas API
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Set the course context for discussion topic operations
     *
     * @param Course $course The course to operate on
     * @return void
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Check if course context is set
     *
     * @return bool
     * @throws CanvasApiException If course is not set
     */
    public static function checkCourse(): bool
    {
        if (!isset(self::$course) || !isset(self::$course->id)) {
            throw new CanvasApiException('Course is required');
        }
        return true;
    }

    /**
     * Get discussion topic ID
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set discussion topic ID
     *
     * @param int|null $id
     * @return void
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get discussion topic title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set discussion topic title
     *
     * @param string|null $title
     * @return void
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get discussion topic message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set discussion topic message
     *
     * @param string|null $message
     * @return void
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * Get HTML URL
     *
     * @return string|null
     */
    public function getHtmlUrl(): ?string
    {
        return $this->htmlUrl;
    }

    /**
     * Set HTML URL
     *
     * @param string|null $htmlUrl
     * @return void
     */
    public function setHtmlUrl(?string $htmlUrl): void
    {
        $this->htmlUrl = $htmlUrl;
    }

    /**
     * Get posted at timestamp
     *
     * @return string|null
     */
    public function getPostedAt(): ?string
    {
        return $this->postedAt;
    }

    /**
     * Set posted at timestamp
     *
     * @param string|null $postedAt
     * @return void
     */
    public function setPostedAt(?string $postedAt): void
    {
        $this->postedAt = $postedAt;
    }

    /**
     * Get discussion type
     *
     * @return string|null
     */
    public function getDiscussionType(): ?string
    {
        return $this->discussionType;
    }

    /**
     * Set discussion type
     *
     * @param string|null $discussionType
     * @return void
     */
    public function setDiscussionType(?string $discussionType): void
    {
        $this->discussionType = $discussionType;
    }

    /**
     * Get require initial post status
     *
     * @return bool|null
     */
    public function getRequireInitialPost(): ?bool
    {
        return $this->requireInitialPost;
    }

    /**
     * Set require initial post status
     *
     * @param bool|null $requireInitialPost
     * @return void
     */
    public function setRequireInitialPost(?bool $requireInitialPost): void
    {
        $this->requireInitialPost = $requireInitialPost;
    }

    /**
     * Get locked status
     *
     * @return bool|null
     */
    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    /**
     * Set locked status
     *
     * @param bool|null $locked
     * @return void
     */
    public function setLocked(?bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * Get pinned status
     *
     * @return bool|null
     */
    public function getPinned(): ?bool
    {
        return $this->pinned;
    }

    /**
     * Set pinned status
     *
     * @param bool|null $pinned
     * @return void
     */
    public function setPinned(?bool $pinned): void
    {
        $this->pinned = $pinned;
    }

    /**
     * Get course ID
     *
     * @return int|null
     */
    public function getCourseId(): ?int
    {
        return $this->courseId;
    }

    /**
     * Set course ID
     *
     * @param int|null $courseId
     * @return void
     */
    public function setCourseId(?int $courseId): void
    {
        $this->courseId = $courseId;
    }

    /**
     * Get user ID
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Set user ID
     *
     * @param int|null $userId
     * @return void
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Get author information
     *
     * @return array<string, mixed>|null
     */
    public function getAuthor(): ?array
    {
        return $this->author;
    }

    /**
     * Set author information
     *
     * @param array<string, mixed>|null $author
     * @return void
     */
    public function setAuthor(?array $author): void
    {
        $this->author = $author;
    }

    /**
     * Get published status
     *
     * @return bool|null
     */
    public function getPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * Set published status
     *
     * @param bool|null $published
     * @return void
     */
    public function setPublished(?bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get workflow state
     *
     * @return string|null
     */
    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    /**
     * Set workflow state
     *
     * @param string|null $workflowState
     * @return void
     */
    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * Get read only status
     *
     * @return bool|null
     */
    public function getReadOnly(): ?bool
    {
        return $this->readOnly;
    }

    /**
     * Set read only status
     *
     * @param bool|null $readOnly
     * @return void
     */
    public function setReadOnly(?bool $readOnly): void
    {
        $this->readOnly = $readOnly;
    }

    /**
     * Get assignment ID
     *
     * @return int|null
     */
    public function getAssignmentId(): ?int
    {
        return $this->assignmentId;
    }

    /**
     * Set assignment ID
     *
     * @param int|null $assignmentId
     * @return void
     */
    public function setAssignmentId(?int $assignmentId): void
    {
        $this->assignmentId = $assignmentId;
    }

    /**
     * Get points possible
     *
     * @return float|null
     */
    public function getPointsPossible(): ?float
    {
        return $this->pointsPossible;
    }

    /**
     * Set points possible
     *
     * @param float|null $pointsPossible
     * @return void
     */
    public function setPointsPossible(?float $pointsPossible): void
    {
        $this->pointsPossible = $pointsPossible;
    }

    /**
     * Get grading type
     *
     * @return string|null
     */
    public function getGradingType(): ?string
    {
        return $this->gradingType;
    }

    /**
     * Set grading type
     *
     * @param string|null $gradingType
     * @return void
     */
    public function setGradingType(?string $gradingType): void
    {
        $this->gradingType = $gradingType;
    }

    /**
     * Get allow rating status
     *
     * @return bool|null
     */
    public function getAllowRating(): ?bool
    {
        return $this->allowRating;
    }

    /**
     * Set allow rating status
     *
     * @param bool|null $allowRating
     * @return void
     */
    public function setAllowRating(?bool $allowRating): void
    {
        $this->allowRating = $allowRating;
    }

    /**
     * Get only graders can rate status
     *
     * @return bool|null
     */
    public function getOnlyGradersCanRate(): ?bool
    {
        return $this->onlyGradersCanRate;
    }

    /**
     * Set only graders can rate status
     *
     * @param bool|null $onlyGradersCanRate
     * @return void
     */
    public function setOnlyGradersCanRate(?bool $onlyGradersCanRate): void
    {
        $this->onlyGradersCanRate = $onlyGradersCanRate;
    }

    /**
     * Get group topic status
     *
     * @return bool|null
     */
    public function getGroupTopic(): ?bool
    {
        return $this->groupTopic;
    }

    /**
     * Set group topic status
     *
     * @param bool|null $groupTopic
     * @return void
     */
    public function setGroupTopic(?bool $groupTopic): void
    {
        $this->groupTopic = $groupTopic;
    }

    /**
     * Get group category ID
     *
     * @return int|null
     */
    public function getGroupCategoryId(): ?int
    {
        return $this->groupCategoryId;
    }

    /**
     * Set group category ID
     *
     * @param int|null $groupCategoryId
     * @return void
     */
    public function setGroupCategoryId(?int $groupCategoryId): void
    {
        $this->groupCategoryId = $groupCategoryId;
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Set created at timestamp
     *
     * @param string|null $createdAt
     * @return void
     */
    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Set updated at timestamp
     *
     * @param string|null $updatedAt
     * @return void
     */
    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get last reply at timestamp
     *
     * @return string|null
     */
    public function getLastReplyAt(): ?string
    {
        return $this->lastReplyAt;
    }

    /**
     * Set last reply at timestamp
     *
     * @param string|null $lastReplyAt
     * @return void
     */
    public function setLastReplyAt(?string $lastReplyAt): void
    {
        $this->lastReplyAt = $lastReplyAt;
    }

    /**
     * Get user can see posts status
     *
     * @return bool|null
     */
    public function getUserCanSeePosts(): ?bool
    {
        return $this->userCanSeePosts;
    }

    /**
     * Set user can see posts status
     *
     * @param bool|null $userCanSeePosts
     * @return void
     */
    public function setUserCanSeePosts(?bool $userCanSeePosts): void
    {
        $this->userCanSeePosts = $userCanSeePosts;
    }

    /**
     * Get discussion subentry count
     *
     * @return int|null
     */
    public function getDiscussionSubentryCount(): ?int
    {
        return $this->discussionSubentryCount;
    }

    /**
     * Set discussion subentry count
     *
     * @param int|null $discussionSubentryCount
     * @return void
     */
    public function setDiscussionSubentryCount(?int $discussionSubentryCount): void
    {
        $this->discussionSubentryCount = $discussionSubentryCount;
    }

    /**
     * Get read state
     *
     * @return string|null
     */
    public function getReadState(): ?string
    {
        return $this->readState;
    }

    /**
     * Set read state
     *
     * @param string|null $readState
     * @return void
     */
    public function setReadState(?string $readState): void
    {
        $this->readState = $readState;
    }

    /**
     * Get unread count
     *
     * @return int|null
     */
    public function getUnreadCount(): ?int
    {
        return $this->unreadCount;
    }

    /**
     * Set unread count
     *
     * @param int|null $unreadCount
     * @return void
     */
    public function setUnreadCount(?int $unreadCount): void
    {
        $this->unreadCount = $unreadCount;
    }

    /**
     * Get subscribed status
     *
     * @return bool|null
     */
    public function getSubscribed(): ?bool
    {
        return $this->subscribed;
    }

    /**
     * Set subscribed status
     *
     * @param bool|null $subscribed
     * @return void
     */
    public function setSubscribed(?bool $subscribed): void
    {
        $this->subscribed = $subscribed;
    }

    /**
     * Get subscription hold status
     *
     * @return string|null
     */
    public function getSubscriptionHold(): ?string
    {
        return $this->subscriptionHold;
    }

    /**
     * Set subscription hold status
     *
     * @param string|null $subscriptionHold
     * @return void
     */
    public function setSubscriptionHold(?string $subscriptionHold): void
    {
        $this->subscriptionHold = $subscriptionHold;
    }

    /**
     * Get delayed post at timestamp
     *
     * @return string|null
     */
    public function getDelayedPostAt(): ?string
    {
        return $this->delayedPostAt;
    }

    /**
     * Set delayed post at timestamp
     *
     * @param string|null $delayedPostAt
     * @return void
     */
    public function setDelayedPostAt(?string $delayedPostAt): void
    {
        $this->delayedPostAt = $delayedPostAt;
    }

    /**
     * Get locked for user status
     *
     * @return bool|null
     */
    public function getLockedForUser(): ?bool
    {
        return $this->lockedForUser;
    }

    /**
     * Set locked for user status
     *
     * @param bool|null $lockedForUser
     * @return void
     */
    public function setLockedForUser(?bool $lockedForUser): void
    {
        $this->lockedForUser = $lockedForUser;
    }

    /**
     * Get lock info
     *
     * @return array<string, mixed>|null
     */
    public function getLockInfo(): ?array
    {
        return $this->lockInfo;
    }

    /**
     * Set lock info
     *
     * @param array<string, mixed>|null $lockInfo
     * @return void
     */
    public function setLockInfo(?array $lockInfo): void
    {
        $this->lockInfo = $lockInfo;
    }

    /**
     * Get lock explanation
     *
     * @return string|null
     */
    public function getLockExplanation(): ?string
    {
        return $this->lockExplanation;
    }

    /**
     * Set lock explanation
     *
     * @param string|null $lockExplanation
     * @return void
     */
    public function setLockExplanation(?string $lockExplanation): void
    {
        $this->lockExplanation = $lockExplanation;
    }

    /**
     * Get user name
     *
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * Set user name
     *
     * @param string|null $userName
     * @return void
     */
    public function setUserName(?string $userName): void
    {
        $this->userName = $userName;
    }

    /**
     * Get topic children
     *
     * @return array<mixed>|null
     */
    public function getTopicChildren(): ?array
    {
        return $this->topicChildren;
    }

    /**
     * Set topic children
     *
     * @param array<mixed>|null $topicChildren
     * @return void
     */
    public function setTopicChildren(?array $topicChildren): void
    {
        $this->topicChildren = $topicChildren;
    }

    /**
     * Get group topic children
     *
     * @return array<mixed>|null
     */
    public function getGroupTopicChildren(): ?array
    {
        return $this->groupTopicChildren;
    }

    /**
     * Set group topic children
     *
     * @param array<mixed>|null $groupTopicChildren
     * @return void
     */
    public function setGroupTopicChildren(?array $groupTopicChildren): void
    {
        $this->groupTopicChildren = $groupTopicChildren;
    }

    /**
     * Get root topic ID
     *
     * @return int|null
     */
    public function getRootTopicId(): ?int
    {
        return $this->rootTopicId;
    }

    /**
     * Set root topic ID
     *
     * @param int|null $rootTopicId
     * @return void
     */
    public function setRootTopicId(?int $rootTopicId): void
    {
        $this->rootTopicId = $rootTopicId;
    }

    /**
     * Get podcast URL
     *
     * @return string|null
     */
    public function getPodcastUrl(): ?string
    {
        return $this->podcastUrl;
    }

    /**
     * Set podcast URL
     *
     * @param string|null $podcastUrl
     * @return void
     */
    public function setPodcastUrl(?string $podcastUrl): void
    {
        $this->podcastUrl = $podcastUrl;
    }

    /**
     * Get attachments
     *
     * @return array<mixed>|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    /**
     * Set attachments
     *
     * @param array<mixed>|null $attachments
     * @return void
     */
    public function setAttachments(?array $attachments): void
    {
        $this->attachments = $attachments;
    }

    /**
     * Get permissions
     *
     * @return array<string, mixed>|null
     */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    /**
     * Set permissions
     *
     * @param array<string, mixed>|null $permissions
     * @return void
     */
    public function setPermissions(?array $permissions): void
    {
        $this->permissions = $permissions;
    }

    /**
     * Get sort by rating status
     *
     * @return bool|null
     */
    public function getSortByRating(): ?bool
    {
        return $this->sortByRating;
    }

    /**
     * Set sort by rating status
     *
     * @param bool|null $sortByRating
     * @return void
     */
    public function setSortByRating(?bool $sortByRating): void
    {
        $this->sortByRating = $sortByRating;
    }

    /**
     * Get is announcement status
     *
     * @return bool|null
     */
    public function getIsAnnouncement(): ?bool
    {
        return $this->isAnnouncement;
    }

    /**
     * Set is announcement status
     *
     * @param bool|null $isAnnouncement
     * @return void
     */
    public function setIsAnnouncement(?bool $isAnnouncement): void
    {
        $this->isAnnouncement = $isAnnouncement;
    }

    /**
     * Get sort order
     *
     * @return string|null
     */
    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    /**
     * Set sort order
     *
     * @param string|null $sortOrder
     * @return void
     */
    public function setSortOrder(?string $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * Get sort order locked status
     *
     * @return bool|null
     */
    public function getSortOrderLocked(): ?bool
    {
        return $this->sortOrderLocked;
    }

    /**
     * Set sort order locked status
     *
     * @param bool|null $sortOrderLocked
     * @return void
     */
    public function setSortOrderLocked(?bool $sortOrderLocked): void
    {
        $this->sortOrderLocked = $sortOrderLocked;
    }

    /**
     * Get expand status
     *
     * @return bool|null
     */
    public function getExpand(): ?bool
    {
        return $this->expand;
    }

    /**
     * Set expand status
     *
     * @param bool|null $expand
     * @return void
     */
    public function setExpand(?bool $expand): void
    {
        $this->expand = $expand;
    }

    /**
     * Get expand locked status
     *
     * @return bool|null
     */
    public function getExpandLocked(): ?bool
    {
        return $this->expandLocked;
    }

    /**
     * Set expand locked status
     *
     * @param bool|null $expandLocked
     * @return void
     */
    public function setExpandLocked(?bool $expandLocked): void
    {
        $this->expandLocked = $expandLocked;
    }

    /**
     * Convert discussion topic to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'html_url' => $this->htmlUrl,
            'posted_at' => $this->postedAt,
            'last_reply_at' => $this->lastReplyAt,
            'discussion_type' => $this->discussionType,
            'require_initial_post' => $this->requireInitialPost,
            'user_can_see_posts' => $this->userCanSeePosts,
            'discussion_subentry_count' => $this->discussionSubentryCount,
            'read_state' => $this->readState,
            'unread_count' => $this->unreadCount,
            'subscribed' => $this->subscribed,
            'subscription_hold' => $this->subscriptionHold,
            'locked' => $this->locked,
            'pinned' => $this->pinned,
            'delayed_post_at' => $this->delayedPostAt,
            'locked_for_user' => $this->lockedForUser,
            'lock_info' => $this->lockInfo,
            'lock_explanation' => $this->lockExplanation,
            'course_id' => $this->courseId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'author' => $this->author,
            'published' => $this->published,
            'workflow_state' => $this->workflowState,
            'read_only' => $this->readOnly,
            'assignment_id' => $this->assignmentId,
            'points_possible' => $this->pointsPossible,
            'grading_type' => $this->gradingType,
            'allow_rating' => $this->allowRating,
            'only_graders_can_rate' => $this->onlyGradersCanRate,
            'sort_by_rating' => $this->sortByRating,
            'sort_order' => $this->sortOrder,
            'sort_order_locked' => $this->sortOrderLocked,
            'expand' => $this->expand,
            'expand_locked' => $this->expandLocked,
            'group_topic' => $this->groupTopic,
            'group_category_id' => $this->groupCategoryId,
            'topic_children' => $this->topicChildren,
            'group_topic_children' => $this->groupTopicChildren,
            'root_topic_id' => $this->rootTopicId,
            'podcast_url' => $this->podcastUrl,
            'attachments' => $this->attachments,
            'permissions' => $this->permissions,
            'is_announcement' => $this->isAnnouncement,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Convert discussion topic to DTO array format
     *
     * @return array<string, mixed>
     */
    public function toDtoArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'message' => $this->message,
            'discussion_type' => $this->discussionType,
            'require_initial_post' => $this->requireInitialPost,
            'locked' => $this->locked,
            'pinned' => $this->pinned,
            'published' => $this->published,
            'assignment_id' => $this->assignmentId,
            'points_possible' => $this->pointsPossible,
            'grading_type' => $this->gradingType,
            'allow_rating' => $this->allowRating,
            'only_graders_can_rate' => $this->onlyGradersCanRate,
            'group_category_id' => $this->groupCategoryId,
        ], fn($value) => $value !== null);
    }

    /**
     * Find a single discussion topic by ID
     *
     * @param int $id Discussion topic ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/discussion_topics/%d', self::$course->id, $id);
        $response = self::$apiClient->get($endpoint);
        $discussionData = json_decode($response->getBody()->getContents(), true);

        return new self($discussionData);
    }

    /**
     * Fetch all discussion topics for the course
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<DiscussionTopic> Array of DiscussionTopic objects
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/discussion_topics', self::$course->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $discussionTopicsData = json_decode($response->getBody()->getContents(), true);

        $discussionTopics = [];
        foreach ($discussionTopicsData as $discussionData) {
            $discussionTopics[] = new self($discussionData);
        }

        return $discussionTopics;
    }

    /**
     * Fetch all discussion topics with pagination support
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/discussion_topics', self::$course->id);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Fetch a single page of discussion topics
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/discussion_topics', self::$course->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all pages of discussion topics
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<DiscussionTopic> Array of DiscussionTopic objects from all pages
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/discussion_topics', self::$course->id);
        return self::fetchAllPagesAsModels($endpoint, $params);
    }

    /**
     * Create a new discussion topic
     *
     * @param array<string, mixed>|CreateDiscussionTopicDTO $data Discussion topic data
     * @return self Created DiscussionTopic object
     * @throws CanvasApiException
     */
    public static function create(array|CreateDiscussionTopicDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateDiscussionTopicDTO($data);
        }

        $endpoint = sprintf('courses/%d/discussion_topics', self::$course->id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $discussionData = json_decode($response->getBody()->getContents(), true);

        return new self($discussionData);
    }

    /**
     * Update a discussion topic
     *
     * @param int $id Discussion topic ID
     * @param array<string, mixed>|UpdateDiscussionTopicDTO $data Discussion topic data
     * @return self Updated DiscussionTopic object
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateDiscussionTopicDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateDiscussionTopicDTO($data);
        }

        $endpoint = sprintf('courses/%d/discussion_topics/%d', self::$course->id, $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $discussionData = json_decode($response->getBody()->getContents(), true);

        return new self($discussionData);
    }

    /**
     * Save the current discussion topic (create or update)
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        // Check for required fields before trying to save
        if (!$this->id && empty($this->title)) {
            throw new CanvasApiException('Discussion topic title is required');
        }

        // Validate points possible for graded discussions
        if ($this->pointsPossible !== null && $this->pointsPossible < 0) {
            throw new CanvasApiException('Points possible must be non-negative');
        }

        // Validate discussion type
        if ($this->discussionType !== null) {
            $validDiscussionTypes = ['threaded', 'side_comment', 'flat'];
            if (!in_array($this->discussionType, $validDiscussionTypes, true)) {
                throw new CanvasApiException(
                    'Invalid discussion type. Must be one of: ' . implode(', ', $validDiscussionTypes)
                );
            }
        }

        // Validate grading type for graded discussions
        if ($this->gradingType !== null) {
            $validGradingTypes = ['pass_fail', 'percent', 'letter_grade', 'gpa_scale', 'points'];
            if (!in_array($this->gradingType, $validGradingTypes, true)) {
                throw new CanvasApiException(
                    'Invalid grading type. Must be one of: ' . implode(', ', $validGradingTypes)
                );
            }
        }

        if ($this->id) {
            // Update existing discussion topic
            $updateData = $this->toDtoArray();
            if (empty($updateData)) {
                return $this; // Nothing to update
            }

            $updatedDiscussion = self::update($this->id, $updateData);
            $this->populate($updatedDiscussion->toArray());
        } else {
            // Create new discussion topic
            $createData = $this->toDtoArray();

            $newDiscussion = self::create($createData);
            $this->populate($newDiscussion->toArray());
        }

        return $this;
    }

    /**
     * Delete the discussion topic
     *
     * @return self
     * @throws CanvasApiException
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Discussion topic ID is required for deletion');
        }

        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/discussion_topics/%d', self::$course->id, $this->id);
        self::$apiClient->delete($endpoint);

        return $this;
    }

    /**
     * Lock the discussion topic
     *
     * @return bool True if locking was successful, false otherwise
     * @throws CanvasApiException
     */
    public function lock(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Discussion topic ID is required');
        }

        try {
            $updatedDiscussion = self::update($this->id, ['locked' => true]);
            $this->populate($updatedDiscussion->toArray());
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Unlock the discussion topic
     *
     * @return bool True if unlocking was successful, false otherwise
     * @throws CanvasApiException
     */
    public function unlock(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Discussion topic ID is required');
        }

        try {
            $updatedDiscussion = self::update($this->id, ['locked' => false]);
            $this->populate($updatedDiscussion->toArray());
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Pin the discussion topic
     *
     * @return bool True if pinning was successful, false otherwise
     * @throws CanvasApiException
     */
    public function pin(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Discussion topic ID is required');
        }

        try {
            $updatedDiscussion = self::update($this->id, ['pinned' => true]);
            $this->populate($updatedDiscussion->toArray());
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Unpin the discussion topic
     *
     * @return bool True if unpinning was successful, false otherwise
     * @throws CanvasApiException
     */
    public function unpin(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Discussion topic ID is required');
        }

        try {
            $updatedDiscussion = self::update($this->id, ['pinned' => false]);
            $this->populate($updatedDiscussion->toArray());
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Mark discussion topic as read
     *
     * @return bool True if marking as read was successful, false otherwise
     * @throws CanvasApiException
     */
    public function markAsRead(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Discussion topic ID is required');
        }

        try {
            self::checkCourse();
            self::checkApiClient();

            $endpoint = sprintf('courses/%d/discussion_topics/%d/read', self::$course->id, $this->id);
            self::$apiClient->put($endpoint);

            $this->readState = 'read';
            $this->unreadCount = 0;
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Mark discussion topic as unread
     *
     * @return bool True if marking as unread was successful, false otherwise
     * @throws CanvasApiException
     */
    public function markAsUnread(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Discussion topic ID is required');
        }

        try {
            self::checkCourse();
            self::checkApiClient();

            $endpoint = sprintf('courses/%d/discussion_topics/%d/read', self::$course->id, $this->id);
            self::$apiClient->delete($endpoint);

            $this->readState = 'unread';
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Subscribe to discussion topic notifications
     *
     * @return bool True if subscription was successful, false otherwise
     * @throws CanvasApiException
     */
    public function subscribe(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Discussion topic ID is required');
        }

        try {
            self::checkCourse();
            self::checkApiClient();

            $endpoint = sprintf('courses/%d/discussion_topics/%d/subscribed', self::$course->id, $this->id);
            self::$apiClient->put($endpoint);

            $this->subscribed = true;
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Unsubscribe from discussion topic notifications
     *
     * @return bool True if unsubscription was successful, false otherwise
     * @throws CanvasApiException
     */
    public function unsubscribe(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Discussion topic ID is required');
        }

        try {
            self::checkCourse();
            self::checkApiClient();

            $endpoint = sprintf('courses/%d/discussion_topics/%d/subscribed', self::$course->id, $this->id);
            self::$apiClient->delete($endpoint);

            $this->subscribed = false;
            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Mark all discussion topics in course as read
     *
     * @return bool True if marking all as read was successful, false otherwise
     * @throws CanvasApiException
     */
    public static function markAllAsRead(): bool
    {
        try {
            self::checkCourse();
            self::checkApiClient();

            $endpoint = sprintf('courses/%d/discussion_topics/read_all', self::$course->id);
            self::$apiClient->put($endpoint);

            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Mark all discussion topics in course as unread
     *
     * @return bool True if marking all as unread was successful, false otherwise
     * @throws CanvasApiException
     */
    public static function markAllAsUnread(): bool
    {
        try {
            self::checkCourse();
            self::checkApiClient();

            $endpoint = sprintf('courses/%d/discussion_topics/read_all', self::$course->id);
            self::$apiClient->delete($endpoint);

            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    // Relationship Methods

    /**
     * Get the course this discussion topic belongs to
     *
     * @return Course|null
     */
    public function course(): ?Course
    {
        return isset(self::$course) ? self::$course : null;
    }

    /**
     * Get the author of this discussion topic
     *
     * @example
     * ```php
     * $course = Course::find(123);
     * DiscussionTopic::setCourse($course);
     *
     * $topic = DiscussionTopic::find(456);
     * $author = $topic->author();
     *
     * if ($author) {
     *     echo "Posted by: {$author->name}\n";
     *     echo "Email: {$author->email}\n";
     * }
     * ```
     *
     * @return User|null
     * @throws CanvasApiException
     */
    public function author(): ?User
    {
        if (!$this->userId) {
            return null;
        }

        try {
            return User::find($this->userId);
        } catch (\Exception $e) {
            throw new CanvasApiException("Could not load discussion topic author: " . $e->getMessage());
        }
    }

    /**
     * Get the assignment associated with this discussion (if graded)
     *
     * @example
     * ```php
     * $course = Course::find(123);
     * DiscussionTopic::setCourse($course);
     *
     * $topic = DiscussionTopic::find(456);
     * $assignment = $topic->assignment();
     *
     * if ($assignment) {
     *     echo "This is a graded discussion\n";
     *     echo "Points possible: {$assignment->pointsPossible}\n";
     *     echo "Due date: {$assignment->dueAt}\n";
     * } else {
     *     echo "This is an ungraded discussion\n";
     * }
     * ```
     *
     * @return Assignment|null
     * @throws CanvasApiException
     */
    public function assignment(): ?Assignment
    {
        if (!$this->assignmentId) {
            return null;
        }

        try {
            if (isset(self::$course)) {
                Assignment::setCourse(self::$course);
            }
            return Assignment::find($this->assignmentId);
        } catch (\Exception $e) {
            throw new CanvasApiException("Could not load associated assignment: " . $e->getMessage());
        }
    }
}
