<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Tabs;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Tabs\UpdateTabDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\ApiInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Tabs API
 *
 * Provides functionality to manage course tabs in Canvas LMS.
 * This class handles listing and updating tabs for a specific course.
 *
 * Usage Examples:
 *
 * // Set course context (required for all operations)
 * $course = Course::find(123);
 * Tab::setCourse($course);
 *
 * // List all tabs for the course
 * $tabs = Tab::fetchAll();
 *
 * // Get paginated tabs
 * $paginatedTabs = Tab::fetchAllPaginated();
 * $paginationResult = Tab::fetchPage();
 *
 * // Update a tab
 * $updatedTab = Tab::update('assignments', ['position' => 3, 'hidden' => false]);
 *
 * // Update using DTO
 * $updateDto = new UpdateTabDTO(position: 2, hidden: true);
 * $updatedTab = Tab::update('home', $updateDto);
 *
 * // Update using instance method
 * $tab = Tab::fetchAll()[0];
 * $tab->position = 5;
 * $tab->hidden = true;
 * $success = $tab->save();
 *
 * @package CanvasLMS\Api\Tabs
 */
class Tab extends AbstractBaseApi implements ApiInterface
{
    protected static ?Course $course = null;

    /**
     * HTML URL of the tab
     */
    public ?string $html_url = null;

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
        if (!empty($data)) {
            $this->populate($data);
        }
    }

    /**
     * Set the course context for tab operations
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
        if (self::$course === null || !isset(self::$course->id)) {
            throw new CanvasApiException('Course is required');
        }
        return true;
    }

    /**
     * Get HTML URL
     *
     * @return string|null
     */
    public function getHtmlUrl(): ?string
    {
        return $this->html_url;
    }

    /**
     * Set HTML URL
     *
     * @param string|null $html_url
     * @return void
     */
    public function setHtmlUrl(?string $html_url): void
    {
        $this->html_url = $html_url;
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
     * @return void
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    /**
     * Fetch all tabs for the course
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<Tab> Array of Tab objects
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/tabs', self::$course->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $tabsData = json_decode($response->getBody()->getContents(), true);

        $tabs = [];
        foreach ($tabsData as $tabData) {
            $tabs[] = new self($tabData);
        }

        return $tabs;
    }

    /**
     * Find a single tab by ID
     *
     * Note: Canvas API doesn't support finding individual tabs by ID.
     * This method throws an exception as it's not supported.
     *
     * @param int $id Tab ID
     * @return static
     * @throws CanvasApiException Always throws as this operation is not supported
     */
    public static function find(int $id): static
    {
        throw new CanvasApiException(
            'Canvas API does not support finding individual tabs by ID. Use fetchAll() to retrieve all tabs.'
        );
    }

    /**
     * Fetch all tabs with pagination support
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/tabs', self::$course->id);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Fetch a single page of tabs
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/tabs', self::$course->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all pages of tabs
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<Tab> Array of Tab objects from all pages
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/tabs', self::$course->id);
        return self::fetchAllPagesAsModels($endpoint, $params);
    }

    /**
     * Update a tab
     *
     * @param string $id Tab ID
     * @param array<string, mixed>|UpdateTabDTO $data Tab data
     * @return self Updated Tab object
     * @throws CanvasApiException
     */
    public static function update(string $id, array|UpdateTabDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/tabs/%s', self::$course->id, $id);

        if ($data instanceof UpdateTabDTO) {
            $data = $data->toApiArray();
        }

        $response = self::$apiClient->put($endpoint, ['multipart' => $data]);
        $tabData = json_decode($response->getBody()->getContents(), true);

        return new self($tabData);
    }

    /**
     * Save the current tab (update only)
     *
     * @return bool True if save was successful, false otherwise
     * @throws CanvasApiException
     */
    public function save(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Tab ID is required for save operation');
        }

        try {
            $updateData = [];

            if ($this->position !== null) {
                $updateData['position'] = $this->position;
            }

            if ($this->hidden !== null) {
                $updateData['hidden'] = $this->hidden;
            }

            if (empty($updateData)) {
                return true; // Nothing to update
            }

            $updatedTab = self::update($this->id, $updateData);
            $this->populate($updatedTab->toArray());

            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Convert tab to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'html_url' => $this->html_url,
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'hidden' => $this->hidden,
            'visibility' => $this->visibility,
            'position' => $this->position,
        ];
    }
}
