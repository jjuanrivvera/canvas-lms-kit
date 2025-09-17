<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Announcements;

use CanvasLMS\Dto\DiscussionTopics\CreateDiscussionTopicDTO;

/**
 * Data Transfer Object for creating announcements in Canvas LMS
 *
 * This DTO extends CreateDiscussionTopicDTO and automatically sets
 * announcement-specific defaults. Announcements are special discussion
 * topics that serve as one-way broadcasts from instructors to students.
 *
 * @package CanvasLMS\Dto\Announcements
 */
class CreateAnnouncementDTO extends CreateDiscussionTopicDTO
{
    /**
     * Create a new announcement DTO with announcement-specific defaults
     *
     * @param array<string, mixed> $data Announcement data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Force announcement-specific defaults
        $this->isAnnouncement = true;

        // Announcements use side_comment type (no threaded replies)
        if ($this->discussionType === null) {
            $this->discussionType = 'side_comment';
        }

        // Announcements don't allow student initial posts
        $this->requireInitialPost = false;

        // By default, announcements should be published
        if ($this->published === null) {
            $this->published = true;
        }
    }

    /**
     * Set delayed post date for scheduled announcements
     *
     * @param string|null $delayedPostAt ISO 8601 formatted datetime
     *
     * @return void
     */
    public function setDelayedPostAt(?string $delayedPostAt): void
    {
        parent::setDelayedPostAt($delayedPostAt);

        // If scheduling for later, don't publish immediately
        if ($delayedPostAt !== null) {
            $this->published = false;
        }
    }

    /**
     * Lock comments on the announcement
     * This prevents students from commenting on the announcement
     *
     * @param bool $lockComments Whether to lock comments
     *
     * @return self
     */
    public function lockComments(bool $lockComments = true): self
    {
        $this->lockComment = $lockComments;

        return $this;
    }

    /**
     * Set sections for targeted announcements
     * Allows announcements to be sent to specific course sections only
     *
     * @param array<int> $sectionIds Array of section IDs
     *
     * @return self
     */
    public function setSections(array $sectionIds): self
    {
        $this->specificSections = $sectionIds;

        return $this;
    }
}
