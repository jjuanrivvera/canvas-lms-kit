<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

use DateTime;
use DateTimeInterface;

/**
 * Base class for activity stream items
 *
 * All activity stream items share these common properties
 */
abstract class ActivityStreamItem
{
    /**
     * @var string
     */
    public string $createdAt;

    /**
     * @var string
     */
    public string $updatedAt;

    /**
     * @var int
     */
    public int $id;

    /**
     * @var string
     */
    public string $title;

    /**
     * @var string
     */
    public string $message;

    /**
     * @var string
     */
    public string $type;

    /**
     * @var bool
     */
    public bool $readState;

    /**
     * @var string
     */
    public string $contextType;

    /**
     * @var int|null
     */
    public ?int $courseId;

    /**
     * @var int|null
     */
    public ?int $groupId;

    /**
     * @var string
     */
    public string $htmlUrl;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Create an activity stream item from data based on type
     *
     * @param array<string, mixed> $data
     *
     * @return ActivityStreamItem
     */
    public static function createFromData(array $data): ActivityStreamItem
    {
        $type = $data['type'] ?? '';

        return match ($type) {
            'DiscussionTopic' => new DiscussionTopicItem($data),
            'Announcement' => new AnnouncementItem($data),
            'Conversation' => new ConversationItem($data),
            'Message' => new MessageItem($data),
            'Submission' => new SubmissionItem($data),
            'Conference' => new ConferenceItem($data),
            'Collaboration' => new CollaborationItem($data),
            'AssessmentRequest' => new AssessmentRequestItem($data),
            default => throw new \InvalidArgumentException(
                'Unknown activity stream item type: ' . (is_scalar($type) ? $type : 'unknown')
            )
        };
    }

    /**
     * Get created at as DateTime
     *
     * @return DateTimeInterface|null
     */
    public function getCreatedAtDate(): ?DateTimeInterface
    {
        return isset($this->createdAt) ? new DateTime($this->createdAt) : null;
    }

    /**
     * Get updated at as DateTime
     *
     * @return DateTimeInterface|null
     */
    public function getUpdatedAtDate(): ?DateTimeInterface
    {
        return isset($this->updatedAt) ? new DateTime($this->updatedAt) : null;
    }

    /**
     * Check if the item is read
     *
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->readState ?? false;
    }

    /**
     * Check if this is a course item
     *
     * @return bool
     */
    public function isCourseItem(): bool
    {
        return $this->contextType === 'course' && $this->courseId !== null;
    }

    /**
     * Check if this is a group item
     *
     * @return bool
     */
    public function isGroupItem(): bool
    {
        return $this->contextType === 'group' && $this->groupId !== null;
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
