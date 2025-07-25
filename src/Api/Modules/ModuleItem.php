<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Modules;

use Exception;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\Modules\CreateModuleItemDTO;
use CanvasLMS\Dto\Modules\UpdateModuleItemDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * Module Item Class
 *
 * Module items represent individual pieces of content within a module.
 * Items can be files, pages, assignments, quizzes, external tools, or other types of content.
 * Module items can have completion requirements and support sequential progression.
 *
 * Usage:
 * ```php
 * $course = Course::find(1);
 * $module = Module::find(1);
 * ModuleItem::setCourse($course);
 * ModuleItem::setModule($module);
 *
 * // Get all module items
 * $items = ModuleItem::fetchAll();
 *
 * // Create new assignment module item
 * $item = ModuleItem::create([
 *     'title' => 'Assignment 1',
 *     'type' => 'Assignment',
 *     'content_id' => 123,
 *     'position' => 1
 * ]);
 *
 * // Create page module item
 * $item = ModuleItem::create([
 *     'title' => 'Course Introduction',
 *     'type' => 'Page',
 *     'page_url' => 'course-introduction'
 * ]);
 *
 * // Create external tool module item
 * $item = ModuleItem::create([
 *     'title' => 'External Learning Tool',
 *     'type' => 'ExternalTool',
 *     'external_url' => 'https://example.com/tool',
 *     'new_tab' => true,
 *     'iframe' => ['width' => 800, 'height' => 600]
 * ]);
 *
 * // Update module item
 * $item->setTitle('Updated Assignment');
 * $item->save();
 *
 * // Mark item as read (fulfills must_view requirement)
 * $item->markAsRead();
 *
 * // Mark item as done (manual completion)
 * $item->markAsDone();
 *
 * // Delete module item
 * $item->delete();
 * ```
 */
class ModuleItem extends AbstractBaseApi
{
    /**
     * Canvas API supported module item types
     */
    public const VALID_TYPES = [
        'File',
        'Page',
        'Discussion',
        'Assignment',
        'Quiz',
        'SubHeader',
        'ExternalUrl',
        'ExternalTool'
    ];

    /**
     * Canvas API supported completion requirement types
     */
    public const VALID_COMPLETION_TYPES = [
        'must_view',         // All types
        'must_contribute',   // Assignment, Discussion, Page
        'must_submit',       // Assignment, Quiz
        'min_score',        // Assignment, Quiz
        'must_mark_done'    // Assignment, Page
    ];

    /**
     * Course context (required)
     * @var Course
     */
    protected static Course $course;

    /**
     * Module context (required)
     * @var Module
     */
    protected static Module $module;

    /**
     * Unique identifier for the module item.
     *
     * @var int
     */
    public int $id;

    /**
     * ID of the module containing this item.
     *
     * @var int
     */
    public int $moduleId;

    /**
     * Content type (File, Page, Discussion, Assignment, Quiz, SubHeader, ExternalUrl, ExternalTool).
     *
     * @var string
     */
    public string $type;

    /**
     * ID of associated content object (not required for ExternalUrl, Page, SubHeader).
     *
     * @var int|null
     */
    public ?int $contentId;

    /**
     * Item title.
     *
     * @var string
     */
    public string $title;

    /**
     * 1-based position in module.
     *
     * @var int
     */
    public int $position;

    /**
     * 0-based hierarchy indent level (0-5 allowed).
     *
     * @var int
     */
    public int $indent;

    /**
     * Visibility flag (optional, requires permissions).
     *
     * @var bool|null
     */
    public ?bool $published;

    /**
     * Canvas URL for this item.
     *
     * @var string
     */
    public string $htmlUrl;

    /**
     * Optional API endpoint URL.
     *
     * @var string|null
     */
    public ?string $url;

    /**
     * Only for 'Page' type - wiki page URL slug.
     *
     * @var string|null
     */
    public ?string $pageUrl;

    /**
     * For ExternalUrl/ExternalTool types.
     *
     * @var string|null
     */
    public ?string $externalUrl;

    /**
     * Whether external tool opens in new tab.
     *
     * @var bool|null
     */
    public ?bool $newTab;

    /**
     * Completion requirement structure.
     * Format: ['type' => 'completion_type', 'min_score' => int]
     *
     * @var array<string, mixed>|null
     */
    public ?array $completionRequirement;

    /**
     * Type-specific details.
     * Format: ['points_possible' => int, 'due_at' => string]
     *
     * @var array<string, mixed>|null
     */
    public ?array $contentDetails;

    /**
     * External tool iframe configuration (when type = ExternalTool).
     * Format: ['width' => int, 'height' => int]
     *
     * @var array<string, int>|null
     */
    public ?array $iframe;

    /**
     * Set the course context
     * @param Course $course
     * @return void
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Set the module context
     * @param Module $module
     * @return void
     */
    public static function setModule(Module $module): void
    {
        self::$module = $module;
    }

    /**
     * Check if course exists and has id
     * @return bool
     * @throws CanvasApiException
     */
    public static function checkCourse(): bool
    {
        if (!isset(self::$course) || !isset(self::$course->id)) {
            throw new CanvasApiException('Course is required');
        }

        return true;
    }

    /**
     * Check if module exists and has id
     * @return bool
     * @throws CanvasApiException
     */
    public static function checkModule(): bool
    {
        if (!isset(self::$module) || !isset(self::$module->id)) {
            throw new CanvasApiException('Module is required');
        }

        return true;
    }

    /**
     * Create a new module item
     * @param CreateModuleItemDTO|mixed[] $data
     * @return self
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function create(array | CreateModuleItemDTO $data): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        if (is_array($data)) {
            $data = new CreateModuleItemDTO($data);
        }

        $endpoint = sprintf('courses/%d/modules/%d/items', self::$course->id, self::$module->id);
        $response = self::$apiClient->post($endpoint, [
            'multipart' => $data->toApiArray()
            ]);

        $moduleItemData = json_decode($response->getBody()->getContents(), true);

        return new self($moduleItemData);
    }

    /**
     * Update a module item
     * @param int $id
     * @param UpdateModuleItemDTO|mixed[] $data
     * @return self
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function update(int $id, array | UpdateModuleItemDTO $data): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        if (is_array($data)) {
            $data = new UpdateModuleItemDTO($data);
        }

        $endpoint = sprintf('courses/%d/modules/%d/items/%d', self::$course->id, self::$module->id, $id);
        $response = self::$apiClient->put($endpoint, [
            'multipart' => $data->toApiArray()
            ]);

        $moduleItemData = json_decode($response->getBody()->getContents(), true);

        return new self($moduleItemData);
    }

    /**
     * Find a module item by its ID.
     * @param int $id
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $endpoint = sprintf('courses/%d/modules/%d/items/%d', self::$course->id, self::$module->id, $id);
        $response = self::$apiClient->get($endpoint);

        $moduleItemData = json_decode($response->getBody()->getContents(), true);

        return new self($moduleItemData);
    }

    /**
     * Get all module items for a module.
     * @param mixed[] $params
     * @return mixed[]
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $endpoint = sprintf('courses/%d/modules/%d/items', self::$course->id, self::$module->id);
        $response = self::$apiClient->get($endpoint, [
            'query' => $params
            ]);

        $moduleItemsData = json_decode($response->getBody()->getContents(), true);

        $moduleItems = [];
        foreach ($moduleItemsData as $moduleItemData) {
            $moduleItems[] = new self($moduleItemData);
        }

        return $moduleItems;
    }

    /**
     * Fetch module items with pagination support
     * @param mixed[] $params Query parameters for the request
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkCourse();
        self::checkModule();
        $endpoint = sprintf('courses/%d/modules/%d/items', self::$course->id, self::$module->id);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Fetch module items from a specific page
     * @param mixed[] $params Query parameters for the request
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        $paginatedResponse = self::fetchAllPaginated($params);
        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all module items from all pages
     * @param mixed[] $params Query parameters for the request
     * @return ModuleItem[]
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        self::checkCourse();
        self::checkModule();
        $endpoint = sprintf('courses/%d/modules/%d/items', self::$course->id, self::$module->id);
        return self::fetchAllPagesAsModels($endpoint, $params);
    }

    /**
     * Save the module item
     * @return bool
     * @throws CanvasApiException
     * @throws Exception
     */
    public function save(): bool
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $data = $this->toDtoArray();

        $dto = isset($data['id']) ? new UpdateModuleItemDTO($data) : new CreateModuleItemDTO($data);
        $path = isset($data['id'])
            ? sprintf('courses/%d/modules/%d/items/%d', self::$course->id, self::$module->id, $data['id'])
            : sprintf('courses/%d/modules/%d/items', self::$course->id, self::$module->id);
        $method = isset($data['id']) ? 'PUT' : 'POST';

        try {
            $response = self::$apiClient->request($method, $path, [
                'multipart' => $dto->toApiArray()
            ]);

            $moduleItemData = json_decode($response->getBody()->getContents(), true);
            $this->populate($moduleItemData);
        } catch (CanvasApiException $exception) {
            // Log the error for debugging
            error_log('ModuleItem operation failed: ' . $exception->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Delete a module item
     * @return bool
     * @throws CanvasApiException
     */
    public function delete(): bool
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        try {
            $endpoint = sprintf('courses/%d/modules/%d/items/%d', self::$course->id, self::$module->id, $this->id);
            self::$apiClient->delete($endpoint);
        } catch (CanvasApiException $exception) {
            // Log the error for debugging
            error_log('ModuleItem operation failed: ' . $exception->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Mark module item as read (fulfills must_view completion requirement)
     * Cannot be used on locked or unpublished items
     * @return bool
     * @throws CanvasApiException
     */
    public function markAsRead(): bool
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        try {
            $endpoint = sprintf(
                'courses/%d/modules/%d/items/%d/mark_read',
                self::$course->id,
                self::$module->id,
                $this->id
            );
            self::$apiClient->post($endpoint);
        } catch (CanvasApiException $exception) {
            // Log the error for debugging
            error_log('ModuleItem operation failed: ' . $exception->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Mark module item as done (manual completion marking)
     * @return bool
     * @throws CanvasApiException
     */
    public function markAsDone(): bool
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        try {
            $endpoint = sprintf('courses/%d/modules/%d/items/%d/done', self::$course->id, self::$module->id, $this->id);
            self::$apiClient->put($endpoint);
        } catch (CanvasApiException $exception) {
            // Log the error for debugging
            error_log('ModuleItem operation failed: ' . $exception->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Mark module item as not done (removes manual completion marking)
     * @return bool
     * @throws CanvasApiException
     */
    public function markAsNotDone(): bool
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        try {
            $endpoint = sprintf('courses/%d/modules/%d/items/%d/done', self::$course->id, self::$module->id, $this->id);
            self::$apiClient->delete($endpoint);
        } catch (CanvasApiException $exception) {
            // Log the error for debugging
            error_log('ModuleItem operation failed: ' . $exception->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    /**
     * @param int $moduleId
     */
    public function setModuleId(int $moduleId): void
    {
        $this->moduleId = $moduleId;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int|null
     */
    public function getContentId(): ?int
    {
        return $this->contentId;
    }

    /**
     * @param int|null $contentId
     */
    public function setContentId(?int $contentId): void
    {
        $this->contentId = $contentId;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function getIndent(): int
    {
        return $this->indent;
    }

    /**
     * @param int $indent
     */
    public function setIndent(int $indent): void
    {
        $this->indent = $indent;
    }

    /**
     * @return bool|null
     */
    public function getPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * @param bool|null $published
     */
    public function setPublished(?bool $published): void
    {
        $this->published = $published;
    }

    /**
     * @return string
     */
    public function getHtmlUrl(): string
    {
        return $this->htmlUrl;
    }

    /**
     * @param string $htmlUrl
     */
    public function setHtmlUrl(string $htmlUrl): void
    {
        $this->htmlUrl = $htmlUrl;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getPageUrl(): ?string
    {
        return $this->pageUrl;
    }

    /**
     * @param string|null $pageUrl
     */
    public function setPageUrl(?string $pageUrl): void
    {
        $this->pageUrl = $pageUrl;
    }

    /**
     * @return string|null
     */
    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    /**
     * @param string|null $externalUrl
     */
    public function setExternalUrl(?string $externalUrl): void
    {
        $this->externalUrl = $externalUrl;
    }

    /**
     * @return bool|null
     */
    public function getNewTab(): ?bool
    {
        return $this->newTab;
    }

    /**
     * @param bool|null $newTab
     */
    public function setNewTab(?bool $newTab): void
    {
        $this->newTab = $newTab;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCompletionRequirement(): ?array
    {
        return $this->completionRequirement;
    }

    /**
     * @param array<string, mixed>|null $completionRequirement
     */
    public function setCompletionRequirement(?array $completionRequirement): void
    {
        $this->completionRequirement = $completionRequirement;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContentDetails(): ?array
    {
        return $this->contentDetails;
    }

    /**
     * @param array<string, mixed>|null $contentDetails
     */
    public function setContentDetails(?array $contentDetails): void
    {
        $this->contentDetails = $contentDetails;
    }

    /**
     * @return array<string, int>|null
     */
    public function getIframe(): ?array
    {
        return $this->iframe;
    }

    /**
     * @param array<string, int>|null $iframe
     */
    public function setIframe(?array $iframe): void
    {
        $this->iframe = $iframe;
    }
}
