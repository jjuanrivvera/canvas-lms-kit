<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Modules;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Modules\CreateModuleItemDTO;
use CanvasLMS\Dto\Modules\UpdateModuleItemDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\CompletionRequirement;
use CanvasLMS\Objects\ContentDetails;
use Exception;

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
 * $items = ModuleItem::get();
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
 *
 * @package CanvasLMS\Api\Modules
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
        'ExternalTool',
    ];

    /**
     * Canvas API supported completion requirement types
     */
    public const VALID_COMPLETION_TYPES = [
        'must_view',         // All types
        'must_contribute',   // Assignment, Discussion, Page
        'must_submit',       // Assignment, Quiz
        'min_score',        // Assignment, Quiz
        'must_mark_done',    // Assignment, Page
    ];

    /**
     * Course context (required)
     *
     * @var Course
     */
    protected static ?Course $course = null;

    /**
     * Module context (required)
     *
     * @var Module
     */
    protected static ?Module $module = null;

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
     * @var CompletionRequirement|null
     */
    public ?CompletionRequirement $completionRequirement = null;

    /**
     * Type-specific details.
     * Format: ['points_possible' => int, 'due_at' => string]
     *
     * @var ContentDetails|null
     */
    public ?ContentDetails $contentDetails = null;

    /**
     * External tool iframe configuration (when type = ExternalTool).
     * Format: ['width' => int, 'height' => int]
     *
     * @var array<string, int>|null
     */
    public ?array $iframe;

    /**
     * Set the course context
     *
     * @param Course $course
     *
     * @return void
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Set the module context
     *
     * @param Module $module
     *
     * @return void
     */
    public static function setModule(Module $module): void
    {
        self::$module = $module;
    }

    /**
     * Check if course exists and has id
     *
     * @throws CanvasApiException
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
     * Check if module exists and has id
     *
     * @throws CanvasApiException
     *
     * @return bool
     */
    public static function checkModule(): bool
    {
        if (!isset(self::$module) || !isset(self::$module->id)) {
            throw new CanvasApiException('Module is required');
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
            throw new CanvasApiException(
                'Course context not set. Call ' . static::class . '::setCourse() first.'
            );
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
    protected static function getContextCourseId(): int
    {
        return self::getCourse()->id;
    }

    /**
     * Get the Module instance, ensuring it is set
     *
     * @throws CanvasApiException if module is not set
     *
     * @return Module
     */
    protected static function getModule(): Module
    {
        if (self::$module === null) {
            throw new CanvasApiException(
                'Module context not set. Call ' . static::class . '::setModule() first.'
            );
        }

        return self::$module;
    }

    /**
     * Constructor
     *
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        // Handle object instantiation for specific properties
        if (isset($data['completion_requirement']) && is_array($data['completion_requirement'])) {
            $this->completionRequirement = new CompletionRequirement($data['completion_requirement']);
            unset($data['completion_requirement']);
        }

        if (isset($data['content_details']) && is_array($data['content_details'])) {
            $this->contentDetails = new ContentDetails($data['content_details']);
            unset($data['content_details']);
        }

        // Call parent constructor for remaining properties
        parent::__construct($data);
    }

    /**
     * Convert the object to an array for DTO operations
     *
     * @return mixed[]
     */
    protected function toDtoArray(): array
    {
        $data = parent::toDtoArray();

        // Convert objects back to arrays
        if ($this->completionRequirement !== null) {
            $data['completionRequirement'] = $this->completionRequirement->toArray();
        }

        if ($this->contentDetails !== null) {
            $data['contentDetails'] = $this->contentDetails->toArray();
        }

        return $data;
    }

    /**
     * Create a new module item
     *
     * @param CreateModuleItemDTO|mixed[] $data
     *
     * @throws CanvasApiException
     * @throws Exception
     *
     * @return self
     */
    public static function create(array | CreateModuleItemDTO $data): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        if (is_array($data)) {
            $data = new CreateModuleItemDTO($data);
        }

        $endpoint = sprintf('courses/%d/modules/%d/items', self::getContextCourseId(), self::getModule()->id);
        $response = self::getApiClient()->post($endpoint, [
            'multipart' => $data->toApiArray(),
            ]);

        $moduleItemData = self::parseJsonResponse($response);

        return new self($moduleItemData);
    }

    /**
     * Update a module item
     *
     * @param int $id
     * @param UpdateModuleItemDTO|mixed[] $data
     *
     * @throws CanvasApiException
     * @throws Exception
     *
     * @return self
     */
    public static function update(int $id, array | UpdateModuleItemDTO $data): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        if (is_array($data)) {
            $data = new UpdateModuleItemDTO($data);
        }

        $endpoint = sprintf('courses/%d/modules/%d/items/%d', self::getContextCourseId(), self::getModule()->id, $id);
        $response = self::getApiClient()->put($endpoint, [
            'multipart' => $data->toApiArray(),
            ]);

        $moduleItemData = self::parseJsonResponse($response);

        return new self($moduleItemData);
    }

    /**
     * Find a module item by its ID.
     *
     * @param int $id
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $endpoint = sprintf('courses/%d/modules/%d/items/%d', self::getContextCourseId(), self::getModule()->id, $id);
        $response = self::getApiClient()->get($endpoint, ['query' => $params]);

        $moduleItemData = self::parseJsonResponse($response);

        return new self($moduleItemData);
    }

    /**
     * Get all module items for a module.
     *
     * @param mixed[] $params
     *
     * @throws CanvasApiException
     *
     * @return mixed[]
     */
    public static function get(array $params = []): array
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $endpoint = sprintf('courses/%d/modules/%d/items', self::getContextCourseId(), self::getModule()->id);
        $response = self::getApiClient()->get($endpoint, [
            'query' => $params,
            ]);

        $moduleItemsData = self::parseJsonResponse($response);

        $moduleItems = [];
        foreach ($moduleItemsData as $moduleItemData) {
            $moduleItems[] = new self($moduleItemData);
        }

        return $moduleItems;
    }

    /**
     * Fetch all module items from all pages
     *
     * @param mixed[] $params Query parameters for the request
     *
     * @throws CanvasApiException
     *
     * @return ModuleItem[]
     */
    public static function all(array $params = []): array
    {
        self::checkCourse();
        self::checkModule();
        self::checkApiClient();
        $endpoint = sprintf('courses/%d/modules/%d/items', self::getContextCourseId(), self::getModule()->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        $items = [];
        foreach ($allData as $item) {
            $items[] = new self($item);
        }

        return $items;
    }

    /**
     * Save the module item
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function save(): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $data = $this->toDtoArray();

        $dto = isset($data['id']) ? new UpdateModuleItemDTO($data) : new CreateModuleItemDTO($data);
        $path = isset($data['id'])
            ? sprintf('courses/%d/modules/%d/items/%d', self::getContextCourseId(), self::getModule()->id, $data['id'])
            : sprintf('courses/%d/modules/%d/items', self::getContextCourseId(), self::getModule()->id);
        $method = isset($data['id']) ? 'PUT' : 'POST';

        $response = self::getApiClient()->request($method, $path, [
            'multipart' => $dto->toApiArray(),
        ]);

        $moduleItemData = self::parseJsonResponse($response);
        $this->populate($moduleItemData);

        return $this;
    }

    /**
     * Delete a module item
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function delete(): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $endpoint = sprintf(
            'courses/%d/modules/%d/items/%d',
            self::getContextCourseId(),
            self::getModule()->id,
            $this->id
        );
        self::getApiClient()->delete($endpoint);

        return $this;
    }

    /**
     * Mark module item as read (fulfills must_view completion requirement)
     * Cannot be used on locked or unpublished items
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function markAsRead(): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $endpoint = sprintf(
            'courses/%d/modules/%d/items/%d/mark_read',
            self::getContextCourseId(),
            self::getModule()->id,
            $this->id
        );
        self::getApiClient()->post($endpoint);

        return $this;
    }

    /**
     * Mark module item as done (manual completion marking)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function markAsDone(): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $endpoint = sprintf(
            'courses/%d/modules/%d/items/%d/done',
            self::getContextCourseId(),
            self::getModule()->id,
            $this->id
        );
        self::getApiClient()->put($endpoint);

        return $this;
    }

    /**
     * Mark module item as not done (removes manual completion marking)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function markAsNotDone(): self
    {
        self::checkApiClient();
        self::checkCourse();
        self::checkModule();

        $endpoint = sprintf(
            'courses/%d/modules/%d/items/%d/done',
            self::getContextCourseId(),
            self::getModule()->id,
            $this->id
        );
        self::getApiClient()->delete($endpoint);

        return $this;
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
     * @return CompletionRequirement|null
     */
    public function getCompletionRequirement(): ?CompletionRequirement
    {
        return $this->completionRequirement;
    }

    /**
     * @param CompletionRequirement|null $completionRequirement
     */
    public function setCompletionRequirement(?CompletionRequirement $completionRequirement): void
    {
        $this->completionRequirement = $completionRequirement;
    }

    /**
     * @return ContentDetails|null
     */
    public function getContentDetails(): ?ContentDetails
    {
        return $this->contentDetails;
    }

    /**
     * @param ContentDetails|null $contentDetails
     */
    public function setContentDetails(?ContentDetails $contentDetails): void
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
        self::checkModule();

        return sprintf('courses/%d/modules/%d/items', self::getCourse()->getId(), self::getModule()->getId());
    }
}
