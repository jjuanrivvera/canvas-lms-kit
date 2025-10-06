<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Tabs;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Tabs\UpdateTabDTO;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * Canvas LMS Tabs API
 *
 * Provides functionality to manage course tabs in Canvas LMS.
 * This class handles listing and updating tabs for a specific course.
 *
 * Usage Examples:
 *
 * ```php
 * // Set course context (required for all operations)
 * $course = Course::find(123);
 * Tab::setCourse($course);
 *
 * // List all tabs for the course
 * $tabs = Tab::get();
 *
 * // Get paginated tabs
 * $paginationResult = Tab::paginate();
 *
 * // Update a tab
 * $updatedTab = Tab::update('assignments', ['position' => 3, 'hidden' => false]);
 *
 * // Update using DTO
 * $updateDto = new UpdateTabDTO(position: 2, hidden: true);
 * $updatedTab = Tab::update('home', $updateDto);
 *
 * // Update using instance method
 * $tab = Tab::get()[0];
 * $tab->position = 5;
 * $tab->hidden = true;
 * $success = $tab->save();
 * ```
 *
 * @package CanvasLMS\Api\Tabs
 */
class Tab extends AbstractBaseApi
{
    protected static ?Course $course = null;

    /**
     * HTML URL of the tab
     */
    public ?string $htmlUrl = null;

    /**
     * Unique identifier for the tab
     */
    public ?string $id = null;

    /**
     * Display label for the tab
     */
    public ?string $label = null;

    /**
     * Type of tab (internal or external)
     */
    public ?string $type = null;

    /**
     * Whether the tab is hidden from students
     */
    public ?bool $hidden = null;

    /**
     * Visibility level of the tab (public, members, admins, none)
     */
    public ?string $visibility = null;

    /**
     * Position of the tab in the navigation (1-based)
     */
    public ?int $position = null;

    /**
     * Create a new Tab instance
     *
     * @param array<string, mixed> $data Tab data from Canvas API
     */
    public function __construct(array $data = [])
    {
        // Call parent constructor for snake_case to camelCase conversion
        parent::__construct($data);
    }

    /**
     * Set the course context for tab operations
     *
     * @param Course $course The course to operate on
     *
     * @return void
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Check if course context is set
     *
     * @throws CanvasApiException If course is not set
     *
     * @return bool
     */
    public static function checkCourse(): bool
    {
        if (!isset(self::$course) || !isset(self::$course->id)) {
            throw new CanvasApiException('Course is required');
        }

        return true;
    }

    /**
     * Get the Course instance, ensuring it is set
     *
     * @throws CanvasApiException if course is not set
     *
     * @return Course
     */
    protected static function getCourse(): Course
    {
        if (self::$course === null) {
            throw new CanvasApiException('Course context not set. Call ' . static::class . '::setCourse() first.');
        }

        return self::$course;
    }

    /**
     * Get the Course ID from context, ensuring course is set
     *
     * @throws CanvasApiException if course is not set
     *
     * @return int
     */
    protected static function getContextCourseId(): ?int
    {
        return self::getCourse()->id;
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
     *
     * @return void
     */
    public function setHtmlUrl(?string $htmlUrl): void
    {
        $this->htmlUrl = $htmlUrl;
    }

    /**
     * Get ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param string|null $id
     *
     * @return void
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get label
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string|null $label
     *
     * @return void
     */
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    /**
     * Get type
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string|null $type
     *
     * @return void
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get hidden status
     *
     * @return bool|null
     */
    public function getHidden(): ?bool
    {
        return $this->hidden;
    }

    /**
     * Set hidden status
     *
     * @param bool|null $hidden
     *
     * @return void
     */
    public function setHidden(?bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Get visibility
     *
     * @return string|null
     */
    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    /**
     * Set visibility
     *
     * @param string|null $visibility
     *
     * @return void
     */
    public function setVisibility(?string $visibility): void
    {
        $this->visibility = $visibility;
    }

    /**
     * Get position
     *
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Set position
     *
     * @param int|null $position
     *
     * @return void
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    /**
     * Find a single tab by ID
     *
     * Note: Canvas API doesn't support finding individual tabs by ID.
     * This method throws an exception as it's not supported.
     *
     * @param int $id Tab ID
     *
     * @throws CanvasApiException Always throws as this operation is not supported
     *
     * @return static
     */
    public static function find(int $id, array $params = []): static
    {
        throw new CanvasApiException(
            'Canvas API does not support finding individual tabs by ID. Use get() to retrieve all tabs.'
        );
    }

    /**
     * Update a tab
     *
     * @param string $id Tab ID
     * @param array<string, mixed>|UpdateTabDTO $data Tab data
     *
     * @throws CanvasApiException
     *
     * @return self Updated Tab object
     */
    public static function update(string $id, array|UpdateTabDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        // Validate tab ID
        if (empty(trim($id))) {
            throw new CanvasApiException('Tab ID cannot be empty');
        }

        $endpoint = sprintf('courses/%d/tabs/%s', self::getContextCourseId(), $id);

        if ($data instanceof UpdateTabDTO) {
            $data = $data->toApiArray();
        } elseif (is_array($data)) {
            // Validate position if provided (Canvas API typically supports positions 1-50)
            if (isset($data['position'])) {
                $position = $data['position'];
                if (!is_int($position) || $position < 1 || $position > 50) {
                    throw new CanvasApiException('Position must be a positive integer between 1 and 50');
                }
            }
        }

        $response = self::getApiClient()->put($endpoint, ['multipart' => $data]);
        $tabData = self::parseJsonResponse($response);

        return new self($tabData);
    }

    /**
     * Save the current tab (update only)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function save(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Tab ID is required for save operation');
        }

        $updateData = [];

        if ($this->position !== null) {
            // Validate position (Canvas API typically supports positions 1-50)
            if ($this->position < 1 || $this->position > 50) {
                throw new CanvasApiException('Position must be a positive integer between 1 and 50');
            }
            $updateData['position'] = $this->position;
        }

        if ($this->hidden !== null) {
            $updateData['hidden'] = $this->hidden;
        }

        if (empty($updateData)) {
            return $this; // Nothing to update
        }

        $updatedTab = self::update($this->id, $updateData);
        $this->populate($updatedTab->toArray());

        return $this;
    }

    /**
     * Convert tab to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'html_url' => $this->htmlUrl,
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'hidden' => $this->hidden,
            'visibility' => $this->visibility,
            'position' => $this->position,
        ];
    }

    /**
     * Convert tab to DTO array format
     *
     * @return array<string, mixed>
     */
    public function toDtoArray(): array
    {
        $data = [];

        if ($this->position !== null) {
            $data['position'] = $this->position;
        }

        if ($this->hidden !== null) {
            $data['hidden'] = $this->hidden;
        }

        return $data;
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

        return sprintf('courses/%d/tabs', self::getCourse()->getId());
    }
}
