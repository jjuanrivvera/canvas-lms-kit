<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

use DateTime;
use DateTimeInterface;

/**
 * Page View object
 *
 * Represents a page view record in Canvas LMS.
 * Tracks user interactions and page visits within Canvas.
 */
class PageView
{
    /**
     * @var string Unique identifier for the page view
     */
    public string $id;

    /**
     * @var string URL that was visited
     */
    public string $url;

    /**
     * @var int|null Context ID (course, account, etc.)
     */
    public ?int $contextId = null;

    /**
     * @var string|null Context type (Course, Account, etc.)
     */
    public ?string $contextType = null;

    /**
     * @var int|null Asset ID (specific resource within context)
     */
    public ?int $assetId = null;

    /**
     * @var string|null Asset type (Assignment, Discussion, etc.)
     */
    public ?string $assetType = null;

    /**
     * @var string|null Controller name that handled the request
     */
    public ?string $controller = null;

    /**
     * @var string|null Action name within the controller
     */
    public ?string $action = null;

    /**
     * @var float|null Time spent on the page in seconds
     */
    public ?float $interactionSeconds = null;

    /**
     * @var string Timestamp when the page was accessed
     */
    public string $createdAt;

    /**
     * @var int User ID who accessed the page
     */
    public int $userId;

    /**
     * @var string|null User's IP address (if available)
     */
    public ?string $remoteIp = null;

    /**
     * @var array<string, mixed>|null Links to related resources
     */
    public ?array $links = null;

    /**
     * @var bool|null Whether this was a participated interaction
     */
    public ?bool $participated = null;

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
     * Get created timestamp as DateTime
     *
     * @return DateTimeInterface|null
     */
    public function getCreatedAtDate(): ?DateTimeInterface
    {
        return isset($this->createdAt) ? new DateTime($this->createdAt) : null;
    }

    /**
     * Get interaction time in a human-readable format
     *
     * @return string|null
     */
    public function getHumanReadableInteractionTime(): ?string
    {
        if (!$this->interactionSeconds) {
            return null;
        }

        $seconds = (int) $this->interactionSeconds;

        if ($seconds < 60) {
            return $seconds . ' second' . ($seconds === 1 ? '' : 's');
        }

        $minutes = intval($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            $result = $minutes . ' minute' . ($minutes === 1 ? '' : 's');
            if ($remainingSeconds > 0) {
                $result .= ', ' . $remainingSeconds . ' second' . ($remainingSeconds === 1 ? '' : 's');
            }

            return $result;
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        $result = $hours . ' hour' . ($hours === 1 ? '' : 's');
        if ($remainingMinutes > 0) {
            $result .= ', ' . $remainingMinutes . ' minute' . ($remainingMinutes === 1 ? '' : 's');
        }

        return $result;
    }

    /**
     * Check if this page view has context information
     *
     * @return bool
     */
    public function hasContext(): bool
    {
        return $this->contextId !== null && $this->contextType !== null;
    }

    /**
     * Check if this page view has asset information
     *
     * @return bool
     */
    public function hasAsset(): bool
    {
        return $this->assetId !== null && $this->assetType !== null;
    }

    /**
     * Check if this was a meaningful interaction (spent time on page)
     *
     * @return bool
     */
    public function hadMeaningfulInteraction(): bool
    {
        return $this->interactionSeconds !== null && $this->interactionSeconds > 10;
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
