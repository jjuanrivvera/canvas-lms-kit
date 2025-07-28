<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\DiscussionTopics;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for creating discussion topics in Canvas LMS
 *
 * This DTO handles the creation of new discussion topics with all the necessary
 * fields supported by the Canvas API.
 *
 * @package CanvasLMS\Dto\DiscussionTopics
 */
class CreateDiscussionTopicDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'discussion_topic';

    /**
     * Discussion topic title (required)
     */
    public ?string $title = null;

    /**
     * Discussion topic message/description (HTML content)
     */
    public ?string $message = null;

    /**
     * Discussion type (threaded, side_comment, flat)
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
     * Whether the discussion topic is published
     */
    public ?bool $published = null;

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
     * Group category ID for group discussions
     */
    public ?int $groupCategoryId = null;

    /**
     * Whether the discussion is read only
     */
    public ?bool $readOnly = null;

    /**
     * Discussion posting date (ISO 8601 format)
     */
    public ?string $postedAt = null;

    /**
     * Discussion lock date (ISO 8601 format)
     */
    public ?string $lockAt = null;

    /**
     * Discussion delayed post date (ISO 8601 format)
     */
    public ?string $delayedPostAt = null;

    /**
     * Podcast settings
     * @var array<string, mixed>|null
     */
    public ?array $podcastSettings = null;

    /**
     * Attachment settings
     * @var array<string, mixed>|null
     */
    public ?array $attachment = null;

    /**
     * Specific sections the topic is assigned to
     * @var array<int>|null
     */
    public ?array $specificSections = null;

    /**
     * Whether this is an announcement
     */
    public ?bool $isAnnouncement = null;

    /**
     * Position after specified topic (for ordering)
     */
    public ?string $positionAfter = null;

    /**
     * Whether to enable podcast feed
     */
    public ?bool $podcastEnabled = null;

    /**
     * Whether to include student posts in podcast
     */
    public ?bool $podcastHasStudentPosts = null;

    /**
     * Assignment configuration for graded discussions
     * @var array<string, mixed>|null
     */
    public ?array $assignment = null;

    /**
     * Get discussion topic title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set discussion topic title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get discussion topic message
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set discussion topic message
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * Get discussion type
     */
    public function getDiscussionType(): ?string
    {
        return $this->discussionType;
    }

    /**
     * Set discussion type
     */
    public function setDiscussionType(?string $discussionType): void
    {
        $this->discussionType = $discussionType;
    }

    /**
     * Get require initial post status
     */
    public function getRequireInitialPost(): ?bool
    {
        return $this->requireInitialPost;
    }

    /**
     * Set require initial post status
     */
    public function setRequireInitialPost(?bool $requireInitialPost): void
    {
        $this->requireInitialPost = $requireInitialPost;
    }

    /**
     * Get locked status
     */
    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    /**
     * Set locked status
     */
    public function setLocked(?bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * Get pinned status
     */
    public function getPinned(): ?bool
    {
        return $this->pinned;
    }

    /**
     * Set pinned status
     */
    public function setPinned(?bool $pinned): void
    {
        $this->pinned = $pinned;
    }

    /**
     * Get published status
     */
    public function getPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * Set published status
     */
    public function setPublished(?bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get assignment ID
     */
    public function getAssignmentId(): ?int
    {
        return $this->assignmentId;
    }

    /**
     * Set assignment ID
     */
    public function setAssignmentId(?int $assignmentId): void
    {
        $this->assignmentId = $assignmentId;
    }

    /**
     * Get points possible
     */
    public function getPointsPossible(): ?float
    {
        return $this->pointsPossible;
    }

    /**
     * Set points possible
     */
    public function setPointsPossible(?float $pointsPossible): void
    {
        $this->pointsPossible = $pointsPossible;
    }

    /**
     * Get grading type
     */
    public function getGradingType(): ?string
    {
        return $this->gradingType;
    }

    /**
     * Set grading type
     */
    public function setGradingType(?string $gradingType): void
    {
        $this->gradingType = $gradingType;
    }

    /**
     * Get allow rating status
     */
    public function getAllowRating(): ?bool
    {
        return $this->allowRating;
    }

    /**
     * Set allow rating status
     */
    public function setAllowRating(?bool $allowRating): void
    {
        $this->allowRating = $allowRating;
    }

    /**
     * Get only graders can rate status
     */
    public function getOnlyGradersCanRate(): ?bool
    {
        return $this->onlyGradersCanRate;
    }

    /**
     * Set only graders can rate status
     */
    public function setOnlyGradersCanRate(?bool $onlyGradersCanRate): void
    {
        $this->onlyGradersCanRate = $onlyGradersCanRate;
    }

    /**
     * Get group category ID
     */
    public function getGroupCategoryId(): ?int
    {
        return $this->groupCategoryId;
    }

    /**
     * Set group category ID
     */
    public function setGroupCategoryId(?int $groupCategoryId): void
    {
        $this->groupCategoryId = $groupCategoryId;
    }

    /**
     * Get read only status
     */
    public function getReadOnly(): ?bool
    {
        return $this->readOnly;
    }

    /**
     * Set read only status
     */
    public function setReadOnly(?bool $readOnly): void
    {
        $this->readOnly = $readOnly;
    }

    /**
     * Get posted at date
     */
    public function getPostedAt(): ?string
    {
        return $this->postedAt;
    }

    /**
     * Set posted at date
     */
    public function setPostedAt(?string $postedAt): void
    {
        $this->postedAt = $postedAt;
    }

    /**
     * Get lock at date
     */
    public function getLockAt(): ?string
    {
        return $this->lockAt;
    }

    /**
     * Set lock at date
     */
    public function setLockAt(?string $lockAt): void
    {
        $this->lockAt = $lockAt;
    }

    /**
     * Get delayed post at date
     */
    public function getDelayedPostAt(): ?string
    {
        return $this->delayedPostAt;
    }

    /**
     * Set delayed post at date
     */
    public function setDelayedPostAt(?string $delayedPostAt): void
    {
        $this->delayedPostAt = $delayedPostAt;
    }

    /**
     * Get podcast settings
     * @return array<string, mixed>|null
     */
    public function getPodcastSettings(): ?array
    {
        return $this->podcastSettings;
    }

    /**
     * Set podcast settings
     * @param array<string, mixed>|null $podcastSettings
     */
    public function setPodcastSettings(?array $podcastSettings): void
    {
        $this->podcastSettings = $podcastSettings;
    }

    /**
     * Get attachment settings
     * @return array<string, mixed>|null
     */
    public function getAttachment(): ?array
    {
        return $this->attachment;
    }

    /**
     * Set attachment settings
     * @param array<string, mixed>|null $attachment
     */
    public function setAttachment(?array $attachment): void
    {
        $this->attachment = $attachment;
    }

    /**
     * Get specific sections
     * @return array<int>|null
     */
    public function getSpecificSections(): ?array
    {
        return $this->specificSections;
    }

    /**
     * Set specific sections
     * @param array<int>|null $specificSections
     */
    public function setSpecificSections(?array $specificSections): void
    {
        $this->specificSections = $specificSections;
    }

    /**
     * Get is announcement status
     */
    public function getIsAnnouncement(): ?bool
    {
        return $this->isAnnouncement;
    }

    /**
     * Set is announcement status
     */
    public function setIsAnnouncement(?bool $isAnnouncement): void
    {
        $this->isAnnouncement = $isAnnouncement;
    }

    /**
     * Get position after
     */
    public function getPositionAfter(): ?string
    {
        return $this->positionAfter;
    }

    /**
     * Set position after
     */
    public function setPositionAfter(?string $positionAfter): void
    {
        $this->positionAfter = $positionAfter;
    }

    /**
     * Get podcast enabled status
     */
    public function getPodcastEnabled(): ?bool
    {
        return $this->podcastEnabled;
    }

    /**
     * Set podcast enabled status
     */
    public function setPodcastEnabled(?bool $podcastEnabled): void
    {
        $this->podcastEnabled = $podcastEnabled;
    }

    /**
     * Get podcast has student posts status
     */
    public function getPodcastHasStudentPosts(): ?bool
    {
        return $this->podcastHasStudentPosts;
    }

    /**
     * Set podcast has student posts status
     */
    public function setPodcastHasStudentPosts(?bool $podcastHasStudentPosts): void
    {
        $this->podcastHasStudentPosts = $podcastHasStudentPosts;
    }

    /**
     * Get assignment configuration
     * @return array<string, mixed>|null
     */
    public function getAssignment(): ?array
    {
        return $this->assignment;
    }

    /**
     * Set assignment configuration
     * @param array<string, mixed>|null $assignment
     */
    public function setAssignment(?array $assignment): void
    {
        $this->assignment = $assignment;
    }
}
