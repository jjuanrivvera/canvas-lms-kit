<?php

declare(strict_types=1);

namespace CanvasLMS\Api\ExternalTools;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\ExternalTools\CreateExternalToolDTO;
use CanvasLMS\Dto\ExternalTools\UpdateExternalToolDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS External Tools API
 *
 * Provides functionality to manage external tools (LTI integrations) in Canvas LMS.
 * This class handles creating, reading, updating, and deleting external tools for a specific course.
 * External tools are IMS LTI links that extend Canvas functionality with third-party applications.
 *
 * Usage Examples:
 *
 * ```php
 * // Set course context (required for all operations)
 * $course = Course::find(123);
 * ExternalTool::setCourse($course);
 *
 * // Create a new external tool
 * $toolData = [
 *     'name' => 'My LTI Tool',
 *     'consumer_key' => 'key123',
 *     'shared_secret' => 'secret456',
 *     'url' => 'https://tool.example.com/lti/launch',
 *     'privacy_level' => 'public'
 * ];
 * $tool = ExternalTool::create($toolData);
 *
 * // Find an external tool by ID
 * $tool = ExternalTool::find(456);
 *
 * // List all external tools for the course
 * $tools = ExternalTool::fetchAll();
 *
 * // Get tools including parent account tools
 * $allTools = ExternalTool::fetchAll(['include_parents' => true]);
 *
 * // Get paginated external tools
 * $paginatedTools = ExternalTool::fetchAllPaginated();
 * $paginationResult = ExternalTool::fetchPage();
 *
 * // Update an external tool
 * $updatedTool = ExternalTool::update(456, ['name' => 'Updated Tool Name']);
 *
 * // Update using DTO
 * $updateDto = new UpdateExternalToolDTO(['name' => 'New Name']);
 * $updatedTool = ExternalTool::update(456, $updateDto);
 *
 * // Update using instance method
 * $tool = ExternalTool::find(456);
 * $tool->setName('New Tool Name');
 * $success = $tool->save();
 *
 * // Delete an external tool
 * $tool = ExternalTool::find(456);
 * $success = $tool->delete();
 *
 * // Generate sessionless launch URL
 * $launchData = ExternalTool::generateSessionlessLaunch(['id' => 456]);
 * ```
 *
 * @package CanvasLMS\Api\ExternalTools
 */
class ExternalTool extends AbstractBaseApi
{
    protected static Course $course;

    /**
     * External tool unique identifier
     */
    public ?int $id = null;

    /**
     * External tool name
     */
    public ?string $name = null;

    /**
     * External tool description
     */
    public ?string $description = null;

    /**
     * The domain to match links against
     */
    public ?string $domain = null;

    /**
     * The URL to match links against
     */
    public ?string $url = null;

    /**
     * The consumer key used by the tool (shared secret is not returned)
     */
    public ?string $consumerKey = null;

    /**
     * The shared secret with the external tool (only used for creation/updates)
     */
    public ?string $sharedSecret = null;

    /**
     * How much user information to send to the external tool
     * Allowed values: anonymous, name_only, email_only, public
     */
    public ?string $privacyLevel = null;

    /**
     * Custom fields that will be sent to the tool consumer
     * @var array<string, string>|null
     */
    public ?array $customFields = null;

    /**
     * Boolean determining whether this tool should be in a preferred location in the RCE
     */
    public ?bool $isRceFavorite = null;

    /**
     * Boolean determining whether this tool should have a dedicated button in Top Navigation
     */
    public ?bool $isTopNavFavorite = null;

    /**
     * The configuration for account navigation links
     * @var array<string, mixed>|null
     */
    public ?array $accountNavigation = null;

    /**
     * The configuration for assignment selection links
     * @var array<string, mixed>|null
     */
    public ?array $assignmentSelection = null;

    /**
     * The configuration for course home navigation links
     * @var array<string, mixed>|null
     */
    public ?array $courseHomeSubNavigation = null;

    /**
     * The configuration for course navigation links
     * @var array<string, mixed>|null
     */
    public ?array $courseNavigation = null;

    /**
     * The configuration for a WYSIWYG editor button
     * @var array<string, mixed>|null
     */
    public ?array $editorButton = null;

    /**
     * The configuration for homework submission selection
     * @var array<string, mixed>|null
     */
    public ?array $homeworkSubmission = null;

    /**
     * The configuration for link selection
     * @var array<string, mixed>|null
     */
    public ?array $linkSelection = null;

    /**
     * The configuration for migration selection
     * @var array<string, mixed>|null
     */
    public ?array $migrationSelection = null;

    /**
     * The configuration for a resource selector in modules
     * @var array<string, mixed>|null
     */
    public ?array $resourceSelection = null;

    /**
     * The configuration for a tool configuration link
     * @var array<string, mixed>|null
     */
    public ?array $toolConfiguration = null;

    /**
     * The configuration for user navigation links
     * @var array<string, mixed>|null
     */
    public ?array $userNavigation = null;

    /**
     * The pixel width of the iFrame that the tool will be rendered in
     */
    public ?int $selectionWidth = null;

    /**
     * The pixel height of the iFrame that the tool will be rendered in
     */
    public ?int $selectionHeight = null;

    /**
     * The url for the tool icon
     */
    public ?string $iconUrl = null;

    /**
     * Whether the tool is not selectable from assignment and modules
     */
    public ?bool $notSelectable = null;

    /**
     * The workflow state of the external tool
     */
    public ?string $workflowState = null;

    /**
     * External tool creation timestamp
     */
    public ?string $createdAt = null;

    /**
     * External tool last update timestamp
     */
    public ?string $updatedAt = null;

    /**
     * The unique identifier for the deployment of the tool
     */
    public ?string $deploymentId = null;

    /**
     * The unique identifier for the tool in LearnPlatform
     */
    public ?string $unifiedToolId = null;

    /**
     * Create a new ExternalTool instance
     *
     * @param array<string, mixed> $data External tool data from Canvas API
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Set the course context for external tool operations
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
        if (!isset(self::$course) || !isset(self::$course->id)) {
            throw new CanvasApiException('Course is required');
        }
        return true;
    }

    /**
     * Get external tool ID
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set external tool ID
     *
     * @param int|null $id
     * @return void
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get external tool name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set external tool name
     *
     * @param string|null $name
     * @return void
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get external tool description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set external tool description
     *
     * @param string|null $description
     * @return void
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get domain
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Set domain
     *
     * @param string|null $domain
     * @return void
     */
    public function setDomain(?string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * Get URL
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set URL
     *
     * @param string|null $url
     * @return void
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get consumer key
     *
     * @return string|null
     */
    public function getConsumerKey(): ?string
    {
        return $this->consumerKey;
    }

    /**
     * Set consumer key
     *
     * @param string|null $consumerKey
     * @return void
     */
    public function setConsumerKey(?string $consumerKey): void
    {
        $this->consumerKey = $consumerKey;
    }

    /**
     * Get shared secret
     *
     * @return string|null
     */
    public function getSharedSecret(): ?string
    {
        return $this->sharedSecret;
    }

    /**
     * Set shared secret
     *
     * @param string|null $sharedSecret
     * @return void
     */
    public function setSharedSecret(?string $sharedSecret): void
    {
        $this->sharedSecret = $sharedSecret;
    }

    /**
     * Get privacy level
     *
     * @return string|null
     */
    public function getPrivacyLevel(): ?string
    {
        return $this->privacyLevel;
    }

    /**
     * Set privacy level
     *
     * @param string|null $privacyLevel
     * @return void
     */
    public function setPrivacyLevel(?string $privacyLevel): void
    {
        $this->privacyLevel = $privacyLevel;
    }

    /**
     * Get custom fields
     *
     * @return array<string, string>|null
     */
    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    /**
     * Set custom fields
     *
     * @param array<string, string>|null $customFields
     * @return void
     */
    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }

    /**
     * Get RCE favorite status
     *
     * @return bool|null
     */
    public function getIsRceFavorite(): ?bool
    {
        return $this->isRceFavorite;
    }

    /**
     * Set RCE favorite status
     *
     * @param bool|null $isRceFavorite
     * @return void
     */
    public function setIsRceFavorite(?bool $isRceFavorite): void
    {
        $this->isRceFavorite = $isRceFavorite;
    }

    /**
     * Get top navigation favorite status
     *
     * @return bool|null
     */
    public function getIsTopNavFavorite(): ?bool
    {
        return $this->isTopNavFavorite;
    }

    /**
     * Set top navigation favorite status
     *
     * @param bool|null $isTopNavFavorite
     * @return void
     */
    public function setIsTopNavFavorite(?bool $isTopNavFavorite): void
    {
        $this->isTopNavFavorite = $isTopNavFavorite;
    }

    /**
     * Get account navigation configuration
     *
     * @return array<string, mixed>|null
     */
    public function getAccountNavigation(): ?array
    {
        return $this->accountNavigation;
    }

    /**
     * Set account navigation configuration
     *
     * @param array<string, mixed>|null $accountNavigation
     * @return void
     */
    public function setAccountNavigation(?array $accountNavigation): void
    {
        $this->accountNavigation = $accountNavigation;
    }

    /**
     * Get assignment selection configuration
     *
     * @return array<string, mixed>|null
     */
    public function getAssignmentSelection(): ?array
    {
        return $this->assignmentSelection;
    }

    /**
     * Set assignment selection configuration
     *
     * @param array<string, mixed>|null $assignmentSelection
     * @return void
     */
    public function setAssignmentSelection(?array $assignmentSelection): void
    {
        $this->assignmentSelection = $assignmentSelection;
    }

    /**
     * Get course home sub navigation configuration
     *
     * @return array<string, mixed>|null
     */
    public function getCourseHomeSubNavigation(): ?array
    {
        return $this->courseHomeSubNavigation;
    }

    /**
     * Set course home sub navigation configuration
     *
     * @param array<string, mixed>|null $courseHomeSubNavigation
     * @return void
     */
    public function setCourseHomeSubNavigation(?array $courseHomeSubNavigation): void
    {
        $this->courseHomeSubNavigation = $courseHomeSubNavigation;
    }

    /**
     * Get course navigation configuration
     *
     * @return array<string, mixed>|null
     */
    public function getCourseNavigation(): ?array
    {
        return $this->courseNavigation;
    }

    /**
     * Set course navigation configuration
     *
     * @param array<string, mixed>|null $courseNavigation
     * @return void
     */
    public function setCourseNavigation(?array $courseNavigation): void
    {
        $this->courseNavigation = $courseNavigation;
    }

    /**
     * Get editor button configuration
     *
     * @return array<string, mixed>|null
     */
    public function getEditorButton(): ?array
    {
        return $this->editorButton;
    }

    /**
     * Set editor button configuration
     *
     * @param array<string, mixed>|null $editorButton
     * @return void
     */
    public function setEditorButton(?array $editorButton): void
    {
        $this->editorButton = $editorButton;
    }

    /**
     * Get homework submission configuration
     *
     * @return array<string, mixed>|null
     */
    public function getHomeworkSubmission(): ?array
    {
        return $this->homeworkSubmission;
    }

    /**
     * Set homework submission configuration
     *
     * @param array<string, mixed>|null $homeworkSubmission
     * @return void
     */
    public function setHomeworkSubmission(?array $homeworkSubmission): void
    {
        $this->homeworkSubmission = $homeworkSubmission;
    }

    /**
     * Get link selection configuration
     *
     * @return array<string, mixed>|null
     */
    public function getLinkSelection(): ?array
    {
        return $this->linkSelection;
    }

    /**
     * Set link selection configuration
     *
     * @param array<string, mixed>|null $linkSelection
     * @return void
     */
    public function setLinkSelection(?array $linkSelection): void
    {
        $this->linkSelection = $linkSelection;
    }

    /**
     * Get migration selection configuration
     *
     * @return array<string, mixed>|null
     */
    public function getMigrationSelection(): ?array
    {
        return $this->migrationSelection;
    }

    /**
     * Set migration selection configuration
     *
     * @param array<string, mixed>|null $migrationSelection
     * @return void
     */
    public function setMigrationSelection(?array $migrationSelection): void
    {
        $this->migrationSelection = $migrationSelection;
    }

    /**
     * Get resource selection configuration
     *
     * @return array<string, mixed>|null
     */
    public function getResourceSelection(): ?array
    {
        return $this->resourceSelection;
    }

    /**
     * Set resource selection configuration
     *
     * @param array<string, mixed>|null $resourceSelection
     * @return void
     */
    public function setResourceSelection(?array $resourceSelection): void
    {
        $this->resourceSelection = $resourceSelection;
    }

    /**
     * Get tool configuration
     *
     * @return array<string, mixed>|null
     */
    public function getToolConfiguration(): ?array
    {
        return $this->toolConfiguration;
    }

    /**
     * Set tool configuration
     *
     * @param array<string, mixed>|null $toolConfiguration
     * @return void
     */
    public function setToolConfiguration(?array $toolConfiguration): void
    {
        $this->toolConfiguration = $toolConfiguration;
    }

    /**
     * Get user navigation configuration
     *
     * @return array<string, mixed>|null
     */
    public function getUserNavigation(): ?array
    {
        return $this->userNavigation;
    }

    /**
     * Set user navigation configuration
     *
     * @param array<string, mixed>|null $userNavigation
     * @return void
     */
    public function setUserNavigation(?array $userNavigation): void
    {
        $this->userNavigation = $userNavigation;
    }

    /**
     * Get selection width
     *
     * @return int|null
     */
    public function getSelectionWidth(): ?int
    {
        return $this->selectionWidth;
    }

    /**
     * Set selection width
     *
     * @param int|null $selectionWidth
     * @return void
     */
    public function setSelectionWidth(?int $selectionWidth): void
    {
        $this->selectionWidth = $selectionWidth;
    }

    /**
     * Get selection height
     *
     * @return int|null
     */
    public function getSelectionHeight(): ?int
    {
        return $this->selectionHeight;
    }

    /**
     * Set selection height
     *
     * @param int|null $selectionHeight
     * @return void
     */
    public function setSelectionHeight(?int $selectionHeight): void
    {
        $this->selectionHeight = $selectionHeight;
    }

    /**
     * Get icon URL
     *
     * @return string|null
     */
    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    /**
     * Set icon URL
     *
     * @param string|null $iconUrl
     * @return void
     */
    public function setIconUrl(?string $iconUrl): void
    {
        $this->iconUrl = $iconUrl;
    }

    /**
     * Get not selectable status
     *
     * @return bool|null
     */
    public function getNotSelectable(): ?bool
    {
        return $this->notSelectable;
    }

    /**
     * Set not selectable status
     *
     * @param bool|null $notSelectable
     * @return void
     */
    public function setNotSelectable(?bool $notSelectable): void
    {
        $this->notSelectable = $notSelectable;
    }

    /**
     * Get workflow state
     *
     * @return string|null
     */
    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    /**
     * Set workflow state
     *
     * @param string|null $workflowState
     * @return void
     */
    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Set created at timestamp
     *
     * @param string|null $createdAt
     * @return void
     */
    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Set updated at timestamp
     *
     * @param string|null $updatedAt
     * @return void
     */
    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get deployment ID
     *
     * @return string|null
     */
    public function getDeploymentId(): ?string
    {
        return $this->deploymentId;
    }

    /**
     * Set deployment ID
     *
     * @param string|null $deploymentId
     * @return void
     */
    public function setDeploymentId(?string $deploymentId): void
    {
        $this->deploymentId = $deploymentId;
    }

    /**
     * Get unified tool ID
     *
     * @return string|null
     */
    public function getUnifiedToolId(): ?string
    {
        return $this->unifiedToolId;
    }

    /**
     * Set unified tool ID
     *
     * @param string|null $unifiedToolId
     * @return void
     */
    public function setUnifiedToolId(?string $unifiedToolId): void
    {
        $this->unifiedToolId = $unifiedToolId;
    }

    /**
     * Convert external tool to array
     * Note: Shared secret is excluded for security reasons as it should never be exposed
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'domain' => $this->domain,
            'url' => $this->url,
            'consumer_key' => $this->consumerKey,
            'privacy_level' => $this->privacyLevel,
            'custom_fields' => $this->customFields,
            'is_rce_favorite' => $this->isRceFavorite,
            'is_top_nav_favorite' => $this->isTopNavFavorite,
            'account_navigation' => $this->accountNavigation,
            'assignment_selection' => $this->assignmentSelection,
            'course_home_sub_navigation' => $this->courseHomeSubNavigation,
            'course_navigation' => $this->courseNavigation,
            'editor_button' => $this->editorButton,
            'homework_submission' => $this->homeworkSubmission,
            'link_selection' => $this->linkSelection,
            'migration_selection' => $this->migrationSelection,
            'resource_selection' => $this->resourceSelection,
            'tool_configuration' => $this->toolConfiguration,
            'user_navigation' => $this->userNavigation,
            'selection_width' => $this->selectionWidth,
            'selection_height' => $this->selectionHeight,
            'icon_url' => $this->iconUrl,
            'not_selectable' => $this->notSelectable,
            'workflow_state' => $this->workflowState,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deployment_id' => $this->deploymentId,
            'unified_tool_id' => $this->unifiedToolId,
        ];
    }

    /**
     * Convert external tool to DTO array format
     *
     * @return array<string, mixed>
     */
    public function toDtoArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'domain' => $this->domain,
            'url' => $this->url,
            'consumer_key' => $this->consumerKey,
            'shared_secret' => $this->sharedSecret,
            'privacy_level' => $this->privacyLevel,
            'custom_fields' => $this->customFields,
            'account_navigation' => $this->accountNavigation,
            'assignment_selection' => $this->assignmentSelection,
            'course_home_sub_navigation' => $this->courseHomeSubNavigation,
            'course_navigation' => $this->courseNavigation,
            'editor_button' => $this->editorButton,
            'homework_submission' => $this->homeworkSubmission,
            'link_selection' => $this->linkSelection,
            'migration_selection' => $this->migrationSelection,
            'resource_selection' => $this->resourceSelection,
            'tool_configuration' => $this->toolConfiguration,
            'user_navigation' => $this->userNavigation,
            'selection_width' => $this->selectionWidth,
            'selection_height' => $this->selectionHeight,
            'icon_url' => $this->iconUrl,
            'not_selectable' => $this->notSelectable,
        ], fn($value) => $value !== null);
    }

    /**
     * Find a single external tool by ID
     *
     * @param int $id External tool ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/external_tools/%d', self::$course->id, $id);
        $response = self::$apiClient->get($endpoint);
        $toolData = json_decode($response->getBody()->getContents(), true);

        return new self($toolData);
    }

    /**
     * Fetch all external tools for the course
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<ExternalTool> Array of ExternalTool objects
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/external_tools', self::$course->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $toolsData = json_decode($response->getBody()->getContents(), true);

        $tools = [];
        foreach ($toolsData as $toolData) {
            $tools[] = new self($toolData);
        }

        return $tools;
    }

    /**
     * Fetch all external tools with pagination support
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/external_tools', self::$course->id);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Fetch a single page of external tools
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/external_tools', self::$course->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all pages of external tools
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<ExternalTool> Array of ExternalTool objects from all pages
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/external_tools', self::$course->id);
        return self::fetchAllPagesAsModels($endpoint, $params);
    }

    /**
     * Create a new external tool
     *
     * @param array<string, mixed>|CreateExternalToolDTO $data External tool data
     * @return self Created ExternalTool object
     * @throws CanvasApiException
     */
    public static function create(array|CreateExternalToolDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateExternalToolDTO($data);
        }

        $endpoint = sprintf('courses/%d/external_tools', self::$course->id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $toolData = json_decode($response->getBody()->getContents(), true);

        return new self($toolData);
    }

    /**
     * Update an external tool
     *
     * @param int $id External tool ID
     * @param array<string, mixed>|UpdateExternalToolDTO $data External tool data
     * @return self Updated ExternalTool object
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateExternalToolDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateExternalToolDTO($data);
        }

        $endpoint = sprintf('courses/%d/external_tools/%d', self::$course->id, $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $toolData = json_decode($response->getBody()->getContents(), true);

        return new self($toolData);
    }

    /**
     * Generate a sessionless launch URL for an external tool
     *
     * @param array<string, mixed> $params Launch parameters
     * @return array<string, mixed> Launch data with id, name, and url
     * @throws CanvasApiException
     */
    public static function generateSessionlessLaunch(array $params): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/external_tools/sessionless_launch', self::$course->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Save the current external tool (create or update)
     *
     * @return bool True if save was successful, false otherwise
     * @throws CanvasApiException
     */
    public function save(): bool
    {
        if (!$this->id && empty($this->name)) {
            throw new CanvasApiException('External tool name is required');
        }

        if (!$this->id && empty($this->consumerKey)) {
            throw new CanvasApiException('Consumer key is required');
        }

        if (!$this->id && empty($this->sharedSecret)) {
            throw new CanvasApiException('Shared secret is required');
        }

        if (!$this->id && empty($this->privacyLevel)) {
            throw new CanvasApiException('Privacy level is required');
        }

        if (!$this->id && empty($this->url) && empty($this->domain)) {
            throw new CanvasApiException('Either URL or domain is required');
        }

        if ($this->privacyLevel !== null) {
            $validPrivacyLevels = ['anonymous', 'name_only', 'email_only', 'public'];
            if (!in_array($this->privacyLevel, $validPrivacyLevels, true)) {
                throw new CanvasApiException(
                    'Invalid privacy level. Must be one of: ' . implode(', ', $validPrivacyLevels)
                );
            }
        }

        // Validate URLs for security
        if ($this->url && !$this->isValidUrl($this->url)) {
            throw new CanvasApiException('Invalid or insecure URL. Only HTTPS URLs are allowed.');
        }

        if ($this->iconUrl && !$this->isValidUrl($this->iconUrl)) {
            throw new CanvasApiException('Invalid or insecure icon URL. Only HTTPS URLs are allowed.');
        }

        try {
            if ($this->id) {
                $updateData = $this->toDtoArray();
                if (empty($updateData)) {
                    return true;
                }

                $updatedTool = self::update($this->id, $updateData);
                $this->populate($updatedTool->toArray());
            } else {
                $createData = $this->toDtoArray();

                $newTool = self::create($createData);
                $this->populate($newTool->toArray());
            }

            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Delete the external tool
     *
     * @return bool True if deletion was successful, false otherwise
     * @throws CanvasApiException
     */
    public function delete(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('External tool ID is required for deletion');
        }

        try {
            self::checkCourse();
            self::checkApiClient();

            $endpoint = sprintf('courses/%d/external_tools/%d', self::$course->id, $this->id);
            self::$apiClient->delete($endpoint);

            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Generate launch URL for this external tool
     *
     * @param array<string, mixed> $params Optional launch parameters
     * @return string The launch URL
     * @throws CanvasApiException
     */
    public function getLaunchUrl(array $params = []): string
    {
        if (!$this->id) {
            throw new CanvasApiException('External tool ID is required to generate launch URL');
        }

        $params['id'] = $this->id;
        $launchData = self::generateSessionlessLaunch($params);

        return $launchData['url'] ?? '';
    }

    /**
     * Validate external tool configuration
     *
     * @return bool True if configuration is valid
     */
    public function validateConfiguration(): bool
    {
        if (empty($this->name) || empty($this->consumerKey) || empty($this->privacyLevel)) {
            return false;
        }

        if (empty($this->url) && empty($this->domain)) {
            return false;
        }

        $validPrivacyLevels = ['anonymous', 'name_only', 'email_only', 'public'];
        if (!in_array($this->privacyLevel, $validPrivacyLevels, true)) {
            return false;
        }

        // Validate URLs for security
        if ($this->url && !$this->isValidUrl($this->url)) {
            return false;
        }

        if ($this->iconUrl && !$this->isValidUrl($this->iconUrl)) {
            return false;
        }

        return true;
    }

    /**
     * Validate URL for security (HTTPS only, no malicious schemes)
     *
     * @param string $url URL to validate
     * @return bool True if URL is valid and secure
     */
    private function isValidUrl(string $url): bool
    {
        // Parse URL
        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        // Require scheme
        if (!isset($parsed['scheme'])) {
            return false;
        }

        // Only allow HTTPS (more secure) or HTTP for localhost/development
        $allowedSchemes = ['https'];

        // Allow HTTP only for localhost/development environments
        if (
            isset($parsed['host']) &&
            (str_starts_with($parsed['host'], 'localhost') ||
             str_starts_with($parsed['host'], '127.0.0.1') ||
             str_ends_with($parsed['host'], '.local'))
        ) {
            $allowedSchemes[] = 'http';
        }

        if (!in_array(strtolower($parsed['scheme']), $allowedSchemes, true)) {
            return false;
        }

        // Require host
        if (!isset($parsed['host']) || empty($parsed['host'])) {
            return false;
        }

        // Block malicious schemes and hosts
        $maliciousPatterns = [
            'javascript:', 'data:', 'vbscript:', 'file:', 'ftp:',
            'localhost', '127.0.0.1', '0.0.0.0', '::1'
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (stripos($url, $pattern) === 0) {
                // Allow localhost only for development (already handled above)
                if (!str_starts_with($pattern, 'localhost') && !str_starts_with($pattern, '127.0.0.1')) {
                    return false;
                }
            }
        }

        // Additional validation for production environments
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') {
            // In production, only allow HTTPS
            if (strtolower($parsed['scheme']) !== 'https') {
                return false;
            }
        }

        return true;
    }
}
