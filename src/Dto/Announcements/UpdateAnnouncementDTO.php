<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Announcements;

use CanvasLMS\Dto\DiscussionTopics\UpdateDiscussionTopicDTO;

/**
 * Data Transfer Object for updating announcements in Canvas LMS
 *
 * This DTO extends UpdateDiscussionTopicDTO and ensures announcement-specific
 * constraints are maintained during updates.
 *
 * @package CanvasLMS\Dto\Announcements
 */
class UpdateAnnouncementDTO extends UpdateDiscussionTopicDTO
{
    /**
     * Create a new update announcement DTO
     *
     * @param array<string, mixed> $data Announcement update data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Ensure it remains an announcement
        if (isset($data['is_announcement'])) {
            $this->isAnnouncement = true;
        }

        // Ensure announcement constraints
        if (isset($data['require_initial_post'])) {
            $this->requireInitialPost = false;
        }
    }

    /**
     * Update delayed post date for scheduled announcements
     *
     * @param string|null $delayedPostAt ISO 8601 formatted datetime
     * @return void
     */
    public function setDelayedPostAt(?string $delayedPostAt): void
    {
        parent::setDelayedPostAt($delayedPostAt);

        // If removing delayed post (posting immediately)
        if ($delayedPostAt === null) {
            $this->published = true;
        }
    }

    /**
     * Lock comments on the announcement
     * This prevents students from commenting on the announcement
     *
     * @param bool $lockComments Whether to lock comments
     * @return self
     */
    public function lockComments(bool $lockComments = true): self
    {
        $this->lockComment = $lockComments;
        return $this;
    }

    /**
     * Update sections for targeted announcements
     * Allows announcements to be sent to specific course sections only
     *
     * @param array<int> $sectionIds Array of section IDs
     * @return self
     */
    public function setSections(array $sectionIds): self
    {
        $this->specificSections = $sectionIds;
        return $this;
    }
}
