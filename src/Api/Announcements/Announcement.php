<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Announcements;

use CanvasLMS\Api\DiscussionTopics\DiscussionTopic;
use CanvasLMS\Dto\Announcements\CreateAnnouncementDTO;
use CanvasLMS\Dto\Announcements\UpdateAnnouncementDTO;
use CanvasLMS\Dto\DiscussionTopics\CreateDiscussionTopicDTO;
use CanvasLMS\Dto\DiscussionTopics\UpdateDiscussionTopicDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Announcements API
 *
 * Provides functionality to manage announcements in Canvas LMS.
 * Announcements are special discussion topics that serve as one-way broadcasts
 * from instructors to students. This class extends DiscussionTopic and automatically
 * applies announcement-specific filters and defaults.
 *
 * Usage Examples:
 *
 * ```php
 * // Set course context (required for all operations)
 * $course = Course::find(123);
 * Announcement::setCourse($course);
 *
 * // Create a new announcement
 * $announcementData = [
 *     'title' => 'Important: Exam Schedule Update',
 *     'message' => 'The midterm exam has been rescheduled to next Friday',
 *     'published' => true,
 *     'delayed_post_at' => '2024-03-01T09:00:00Z'  // Schedule for future
 * ];
 * $announcement = Announcement::create($announcementData);
 *
 * // Find an announcement by ID
 * $announcement = Announcement::find(456);
 *
 * // List all announcements for the course
 * $announcements = Announcement::get();
 *
 * // Get only active announcements
 * $activeAnnouncements = Announcement::get(['active_only' => true]);
 *
 * // Get paginated announcements
 * $paginatedAnnouncements = Announcement::paginate();
 *
 * // Update an announcement
 * $updatedAnnouncement = Announcement::update(456, ['title' => 'Updated: Exam Schedule']);
 *
 * // Lock/unlock an announcement to prevent/allow modifications
 * $announcement->lock();
 * $announcement->unlock();
 *
 * // Delete an announcement
 * $announcement = Announcement::find(456);
 * $announcement->delete();
 *
 * // Get global announcements across multiple courses
 * $contextCodes = ['course_123', 'course_456'];
 * $globalAnnouncements = Announcement::fetchGlobalAnnouncements($contextCodes);
 * ```
 *
 * @package CanvasLMS\Api\Announcements
 */
class Announcement extends DiscussionTopic
{
    /**
     * Fetch all announcements for the course
     * Overrides parent to automatically filter for announcements only
     *
     * @param array<string, mixed> $params Optional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<Announcement> Array of Announcement objects
     */
    public static function get(array $params = []): array
    {
        $params['only_announcements'] = true;

        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/discussion_topics', self::$course->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $announcementsData = self::parseJsonResponse($response);

        $announcements = [];
        foreach ($announcementsData as $announcementData) {
            $announcements[] = new self($announcementData);
        }

        return $announcements;
    }

    /**
     * Get paginated announcements
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return PaginationResult
     */
    public static function paginate(array $params = []): PaginationResult
    {
        $params['only_announcements'] = true;

        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/discussion_topics', self::$course->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        // Convert data to models
        $data = [];
        foreach ($paginatedResponse->getJsonData() as $item) {
            $data[] = new self($item);
        }

        return $paginatedResponse->toPaginationResult($data);
    }

    /**
     * Get all announcements from all pages
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, self>
     */
    public static function all(array $params = []): array
    {
        $params['only_announcements'] = true;

        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/discussion_topics', self::$course->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        $announcements = [];
        foreach ($allData as $item) {
            $announcements[] = new self($item);
        }

        return $announcements;
    }

    /**
     * Get the API endpoint for this resource
     *
     * @throws CanvasApiException
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        self::checkCourse();

        return sprintf('courses/%d/discussion_topics', self::$course->id);
    }

    /**
     * Create a new announcement
     * Overrides parent to ensure announcement flag is set
     *
     * @param array<string, mixed>|CreateAnnouncementDTO|CreateDiscussionTopicDTO $data Announcement data
     *
     * @throws CanvasApiException
     *
     * @return self Created Announcement object
     */
    public static function create(array|CreateDiscussionTopicDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateAnnouncementDTO($data);
        } elseif (!($data instanceof CreateAnnouncementDTO)) {
            // Convert CreateDiscussionTopicDTO to CreateAnnouncementDTO
            $data = new CreateAnnouncementDTO($data->toApiArray());
        }

        $endpoint = sprintf('courses/%d/discussion_topics', self::$course->id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $announcementData = self::parseJsonResponse($response);

        return new self($announcementData);
    }

    /**
     * Update an announcement
     * Overrides parent to use announcement-specific DTO
     *
     * @param int $id Announcement ID
     * @param array<string, mixed>|UpdateAnnouncementDTO|UpdateDiscussionTopicDTO $data Announcement data
     *
     * @throws CanvasApiException
     *
     * @return self Updated Announcement object
     */
    public static function update(int $id, array|UpdateDiscussionTopicDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateAnnouncementDTO($data);
        } elseif (!($data instanceof UpdateAnnouncementDTO)) {
            // Convert UpdateDiscussionTopicDTO to UpdateAnnouncementDTO
            $data = new UpdateAnnouncementDTO($data->toApiArray());
        }

        $endpoint = sprintf('courses/%d/discussion_topics/%d', self::$course->id, $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $announcementData = self::parseJsonResponse($response);

        return new self($announcementData);
    }

    /**
     * Fetch global announcements across multiple courses
     * Uses the /api/v1/announcements endpoint for cross-course announcements
     *
     * @example
     * ```php
     * // Get announcements for multiple courses
     * $contextCodes = ['course_123', 'course_456'];
     * $announcements = Announcement::fetchGlobalAnnouncements($contextCodes);
     *
     * // With date range
     * $announcements = Announcement::fetchGlobalAnnouncements(
     *     ['course_123', 'course_456'],
     *     [
     *         'start_date' => '2024-01-01',
     *         'end_date' => '2024-12-31',
     *         'active_only' => true
     *     ]
     * );
     * ```
     *
     * @param array<string> $contextCodes Array of context codes (e.g., ['course_123', 'course_456'])
     * @param array<string, mixed> $params Optional parameters (start_date, end_date, active_only, latest_only, include)
     *
     * @throws CanvasApiException
     *
     * @return array<Announcement> Array of Announcement objects with context_code added
     */
    public static function fetchGlobalAnnouncements(array $contextCodes, array $params = []): array
    {
        self::checkApiClient();

        if (empty($contextCodes)) {
            throw new CanvasApiException('At least one context code is required');
        }

        // Build query parameters
        $queryParams = $params;

        // Add context_codes as array parameters
        foreach ($contextCodes as $index => $code) {
            $queryParams["context_codes[$index]"] = $code;
        }

        $endpoint = 'announcements';
        $response = self::$apiClient->get($endpoint, ['query' => $queryParams]);
        $announcementsData = self::parseJsonResponse($response);

        $announcements = [];
        foreach ($announcementsData as $announcementData) {
            $announcement = new self($announcementData);
            // The global endpoint adds a context_code field
            if (isset($announcementData['context_code'])) {
                $announcement->contextCode = $announcementData['context_code'];
            }
            $announcements[] = $announcement;
        }

        return $announcements;
    }

    /**
     * Context code for global announcements (populated when using fetchGlobalAnnouncements)
     *
     * @var string|null
     */
    public ?string $contextCode = null;

    /**
     * Get context code (for global announcements)
     *
     * @return string|null
     */
    public function getContextCode(): ?string
    {
        return $this->contextCode;
    }

    /**
     * Set context code (for global announcements)
     *
     * @param string|null $contextCode
     *
     * @return void
     */
    public function setContextCode(?string $contextCode): void
    {
        $this->contextCode = $contextCode;
    }

    /**
     * Save the current announcement (create or update)
     * Overrides parent to ensure announcement-specific validation
     *
     * @throws CanvasApiException
     *
     * @return static
     */
    public function save(): static
    {
        // Ensure this is marked as an announcement
        $this->isAnnouncement = true;

        // Announcements don't allow student replies
        $this->requireInitialPost = false;

        // Call parent save method
        parent::save();

        return $this;
    }

    /**
     * Schedule an announcement to be posted at a future date
     *
     * @param string $datetime ISO 8601 formatted datetime (e.g., '2024-03-15T10:00:00Z')
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function scheduleFor(string $datetime): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Announcement must be saved before scheduling');
        }

        $updatedAnnouncement = self::update($this->id, ['delayed_post_at' => $datetime]);
        $this->delayedPostAt = $updatedAnnouncement->delayedPostAt;
        $this->published = $updatedAnnouncement->published;

        return $this;
    }

    /**
     * Post an announcement immediately (remove delayed posting)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function postImmediately(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Announcement must be saved before posting');
        }

        $updatedAnnouncement = self::update($this->id, [
            'delayed_post_at' => null,
            'published' => true,
        ]);
        $this->delayedPostAt = $updatedAnnouncement->delayedPostAt;
        $this->published = $updatedAnnouncement->published;

        return $this;
    }
}
