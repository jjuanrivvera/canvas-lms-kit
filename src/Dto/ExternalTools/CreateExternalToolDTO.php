<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\ExternalTools;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for creating external tools in Canvas LMS
 *
 * This DTO handles the creation of new external tools (LTI integrations) with all the necessary
 * fields supported by the Canvas API. External tools are IMS LTI links that extend Canvas
 * functionality with third-party educational applications.
 *
 * @package CanvasLMS\Dto\ExternalTools
 */
class CreateExternalToolDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'external_tool';

    /**
     * Placement configuration property names that need nested array handling
     */
    private const PLACEMENT_PROPERTIES = [
        'accountNavigation' => 'account_navigation',
        'assignmentSelection' => 'assignment_selection',
        'courseHomeSubNavigation' => 'course_home_sub_navigation',
        'courseNavigation' => 'course_navigation',
        'editorButton' => 'editor_button',
        'homeworkSubmission' => 'homework_submission',
        'linkSelection' => 'link_selection',
        'migrationSelection' => 'migration_selection',
        'resourceSelection' => 'resource_selection',
        'toolConfiguration' => 'tool_configuration',
        'userNavigation' => 'user_navigation',
    ];

    /**
     * External tool name (required)
     */
    public ?string $name = null;

    /**
     * How much user information to send to the external tool (required)
     * Allowed values: anonymous, name_only, email_only, public
     */
    public ?string $privacyLevel = null;

    /**
     * The consumer key for the external tool (required)
     */
    public ?string $consumerKey = null;

    /**
     * The shared secret with the external tool (required)
     */
    public ?string $sharedSecret = null;

    /**
     * A description of the tool
     */
    public ?string $description = null;

    /**
     * The url to match links against (either url or domain should be set, not both)
     */
    public ?string $url = null;

    /**
     * The domain to match links against (either url or domain should be set, not both)
     */
    public ?string $domain = null;

    /**
     * The url of the icon to show for this tool
     */
    public ?string $iconUrl = null;

    /**
     * The default text to show for this tool
     */
    public ?string $text = null;

    /**
     * Custom fields that will be sent to the tool consumer
     * @var array<string, string>|null
     */
    public ?array $customFields = null;

    /**
     * Whether this tool should appear in a preferred location in the RCE
     * (Deprecated in favor of Mark tool to RCE Favorites)
     */
    public ?bool $isRceFavorite = null;

    /**
     * Configuration can be passed in as CC xml instead of using query parameters
     * Allowed values: by_url, by_xml
     */
    public ?string $configType = null;

    /**
     * XML tool configuration, as specified in the CC xml specification
     * Required if config_type is set to "by_xml"
     */
    public ?string $configXml = null;

    /**
     * URL where the server can retrieve an XML tool configuration
     * Required if config_type is set to "by_url"
     */
    public ?string $configUrl = null;

    /**
     * If set to true, and if resource_selection is set to false,
     * the tool won't show up in the external tool selection UI
     */
    public ?bool $notSelectable = null;

    /**
     * If set to true LTI query params will not be copied to the post body
     */
    public ?bool $oauthCompliant = null;

    /**
     * The unique identifier for the tool in LearnPlatform
     */
    public ?string $unifiedToolId = null;

    /**
     * The configuration for account navigation links
     * @var array<string, mixed>|null
     */
    public ?array $accountNavigation = null;

    /**
     * The configuration for user navigation links
     * @var array<string, mixed>|null
     */
    public ?array $userNavigation = null;

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
     * The configuration for a tool configuration link
     * @var array<string, mixed>|null
     */
    public ?array $toolConfiguration = null;

    /**
     * The configuration for a resource selector in modules
     * @var array<string, mixed>|null
     */
    public ?array $resourceSelection = null;

    /**
     * Get external tool name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set external tool name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get privacy level
     */
    public function getPrivacyLevel(): ?string
    {
        return $this->privacyLevel;
    }

    /**
     * Set privacy level
     */
    public function setPrivacyLevel(?string $privacyLevel): void
    {
        $this->privacyLevel = $privacyLevel;
    }

    /**
     * Get consumer key
     */
    public function getConsumerKey(): ?string
    {
        return $this->consumerKey;
    }

    /**
     * Set consumer key
     */
    public function setConsumerKey(?string $consumerKey): void
    {
        $this->consumerKey = $consumerKey;
    }

    /**
     * Get shared secret
     */
    public function getSharedSecret(): ?string
    {
        return $this->sharedSecret;
    }

    /**
     * Set shared secret
     */
    public function setSharedSecret(?string $sharedSecret): void
    {
        $this->sharedSecret = $sharedSecret;
    }

    /**
     * Get description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get URL
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set URL
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get domain
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Set domain
     */
    public function setDomain(?string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * Get icon URL
     */
    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    /**
     * Set icon URL
     */
    public function setIconUrl(?string $iconUrl): void
    {
        $this->iconUrl = $iconUrl;
    }

    /**
     * Get text
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Set text
     */
    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * Get custom fields
     * @return array<string, string>|null
     */
    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    /**
     * Set custom fields
     * @param array<string, string>|null $customFields
     */
    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }

    /**
     * Get RCE favorite status
     */
    public function getIsRceFavorite(): ?bool
    {
        return $this->isRceFavorite;
    }

    /**
     * Set RCE favorite status
     */
    public function setIsRceFavorite(?bool $isRceFavorite): void
    {
        $this->isRceFavorite = $isRceFavorite;
    }

    /**
     * Get config type
     */
    public function getConfigType(): ?string
    {
        return $this->configType;
    }

    /**
     * Set config type
     */
    public function setConfigType(?string $configType): void
    {
        $this->configType = $configType;
    }

    /**
     * Get config XML
     */
    public function getConfigXml(): ?string
    {
        return $this->configXml;
    }

    /**
     * Set config XML
     */
    public function setConfigXml(?string $configXml): void
    {
        $this->configXml = $configXml;
    }

    /**
     * Get config URL
     */
    public function getConfigUrl(): ?string
    {
        return $this->configUrl;
    }

    /**
     * Set config URL
     */
    public function setConfigUrl(?string $configUrl): void
    {
        $this->configUrl = $configUrl;
    }

    /**
     * Get not selectable status
     */
    public function getNotSelectable(): ?bool
    {
        return $this->notSelectable;
    }

    /**
     * Set not selectable status
     */
    public function setNotSelectable(?bool $notSelectable): void
    {
        $this->notSelectable = $notSelectable;
    }

    /**
     * Get OAuth compliant status
     */
    public function getOauthCompliant(): ?bool
    {
        return $this->oauthCompliant;
    }

    /**
     * Set OAuth compliant status
     */
    public function setOauthCompliant(?bool $oauthCompliant): void
    {
        $this->oauthCompliant = $oauthCompliant;
    }

    /**
     * Get unified tool ID
     */
    public function getUnifiedToolId(): ?string
    {
        return $this->unifiedToolId;
    }

    /**
     * Set unified tool ID
     */
    public function setUnifiedToolId(?string $unifiedToolId): void
    {
        $this->unifiedToolId = $unifiedToolId;
    }

    /**
     * Get account navigation configuration
     * @return array<string, mixed>|null
     */
    public function getAccountNavigation(): ?array
    {
        return $this->accountNavigation;
    }

    /**
     * Set account navigation configuration
     * @param array<string, mixed>|null $accountNavigation
     */
    public function setAccountNavigation(?array $accountNavigation): void
    {
        $this->accountNavigation = $accountNavigation;
    }

    /**
     * Get user navigation configuration
     * @return array<string, mixed>|null
     */
    public function getUserNavigation(): ?array
    {
        return $this->userNavigation;
    }

    /**
     * Set user navigation configuration
     * @param array<string, mixed>|null $userNavigation
     */
    public function setUserNavigation(?array $userNavigation): void
    {
        $this->userNavigation = $userNavigation;
    }

    /**
     * Get course home sub navigation configuration
     * @return array<string, mixed>|null
     */
    public function getCourseHomeSubNavigation(): ?array
    {
        return $this->courseHomeSubNavigation;
    }

    /**
     * Set course home sub navigation configuration
     * @param array<string, mixed>|null $courseHomeSubNavigation
     */
    public function setCourseHomeSubNavigation(?array $courseHomeSubNavigation): void
    {
        $this->courseHomeSubNavigation = $courseHomeSubNavigation;
    }

    /**
     * Get course navigation configuration
     * @return array<string, mixed>|null
     */
    public function getCourseNavigation(): ?array
    {
        return $this->courseNavigation;
    }

    /**
     * Set course navigation configuration
     * @param array<string, mixed>|null $courseNavigation
     */
    public function setCourseNavigation(?array $courseNavigation): void
    {
        $this->courseNavigation = $courseNavigation;
    }

    /**
     * Get editor button configuration
     * @return array<string, mixed>|null
     */
    public function getEditorButton(): ?array
    {
        return $this->editorButton;
    }

    /**
     * Set editor button configuration
     * @param array<string, mixed>|null $editorButton
     */
    public function setEditorButton(?array $editorButton): void
    {
        $this->editorButton = $editorButton;
    }

    /**
     * Get homework submission configuration
     * @return array<string, mixed>|null
     */
    public function getHomeworkSubmission(): ?array
    {
        return $this->homeworkSubmission;
    }

    /**
     * Set homework submission configuration
     * @param array<string, mixed>|null $homeworkSubmission
     */
    public function setHomeworkSubmission(?array $homeworkSubmission): void
    {
        $this->homeworkSubmission = $homeworkSubmission;
    }

    /**
     * Get link selection configuration
     * @return array<string, mixed>|null
     */
    public function getLinkSelection(): ?array
    {
        return $this->linkSelection;
    }

    /**
     * Set link selection configuration
     * @param array<string, mixed>|null $linkSelection
     */
    public function setLinkSelection(?array $linkSelection): void
    {
        $this->linkSelection = $linkSelection;
    }

    /**
     * Get migration selection configuration
     * @return array<string, mixed>|null
     */
    public function getMigrationSelection(): ?array
    {
        return $this->migrationSelection;
    }

    /**
     * Set migration selection configuration
     * @param array<string, mixed>|null $migrationSelection
     */
    public function setMigrationSelection(?array $migrationSelection): void
    {
        $this->migrationSelection = $migrationSelection;
    }

    /**
     * Get tool configuration
     * @return array<string, mixed>|null
     */
    public function getToolConfiguration(): ?array
    {
        return $this->toolConfiguration;
    }

    /**
     * Set tool configuration
     * @param array<string, mixed>|null $toolConfiguration
     */
    public function setToolConfiguration(?array $toolConfiguration): void
    {
        $this->toolConfiguration = $toolConfiguration;
    }

    /**
     * Get resource selection configuration
     * @return array<string, mixed>|null
     */
    public function getResourceSelection(): ?array
    {
        return $this->resourceSelection;
    }

    /**
     * Set resource selection configuration
     * @param array<string, mixed>|null $resourceSelection
     */
    public function setResourceSelection(?array $resourceSelection): void
    {
        $this->resourceSelection = $resourceSelection;
    }

    /**
     * Set account navigation URL
     */
    public function setAccountNavigationUrl(?string $url): void
    {
        if (!$this->accountNavigation) {
            $this->accountNavigation = [];
        }
        $this->accountNavigation['url'] = $url;
    }

    /**
     * Set account navigation enabled status
     */
    public function setAccountNavigationEnabled(?bool $enabled): void
    {
        if (!$this->accountNavigation) {
            $this->accountNavigation = [];
        }
        $this->accountNavigation['enabled'] = $enabled;
    }

    /**
     * Set account navigation text
     */
    public function setAccountNavigationText(?string $text): void
    {
        if (!$this->accountNavigation) {
            $this->accountNavigation = [];
        }
        $this->accountNavigation['text'] = $text;
    }

    /**
     * Set course navigation enabled status
     */
    public function setCourseNavigationEnabled(?bool $enabled): void
    {
        if (!$this->courseNavigation) {
            $this->courseNavigation = [];
        }
        $this->courseNavigation['enabled'] = $enabled;
    }

    /**
     * Set course navigation text
     */
    public function setCourseNavigationText(?string $text): void
    {
        if (!$this->courseNavigation) {
            $this->courseNavigation = [];
        }
        $this->courseNavigation['text'] = $text;
    }

    /**
     * Set course navigation visibility
     */
    public function setCourseNavigationVisibility(?string $visibility): void
    {
        if (!$this->courseNavigation) {
            $this->courseNavigation = [];
        }
        $this->courseNavigation['visibility'] = $visibility;
    }

    /**
     * Set course navigation window target
     */
    public function setCourseNavigationWindowTarget(?string $windowTarget): void
    {
        if (!$this->courseNavigation) {
            $this->courseNavigation = [];
        }
        $this->courseNavigation['windowTarget'] = $windowTarget;
    }

    /**
     * Set course navigation default state
     */
    public function setCourseNavigationDefault(?string $default): void
    {
        if (!$this->courseNavigation) {
            $this->courseNavigation = [];
        }
        $this->courseNavigation['default'] = $default;
    }

    /**
     * Set editor button URL
     */
    public function setEditorButtonUrl(?string $url): void
    {
        if (!$this->editorButton) {
            $this->editorButton = [];
        }
        $this->editorButton['url'] = $url;
    }

    /**
     * Set editor button enabled status
     */
    public function setEditorButtonEnabled(?bool $enabled): void
    {
        if (!$this->editorButton) {
            $this->editorButton = [];
        }
        $this->editorButton['enabled'] = $enabled;
    }

    /**
     * Set editor button icon URL
     */
    public function setEditorButtonIconUrl(?string $iconUrl): void
    {
        if (!$this->editorButton) {
            $this->editorButton = [];
        }
        $this->editorButton['icon_url'] = $iconUrl;
    }

    /**
     * Set editor button selection width
     */
    public function setEditorButtonSelectionWidth(?string $selectionWidth): void
    {
        if (!$this->editorButton) {
            $this->editorButton = [];
        }
        $this->editorButton['selection_width'] = $selectionWidth;
    }

    /**
     * Set editor button selection height
     */
    public function setEditorButtonSelectionHeight(?string $selectionHeight): void
    {
        if (!$this->editorButton) {
            $this->editorButton = [];
        }
        $this->editorButton['selection_height'] = $selectionHeight;
    }

    /**
     * Set editor button message type
     */
    public function setEditorButtonMessageType(?string $messageType): void
    {
        if (!$this->editorButton) {
            $this->editorButton = [];
        }
        $this->editorButton['message_type'] = $messageType;
    }

    /**
     * Set resource selection URL
     */
    public function setResourceSelectionUrl(?string $url): void
    {
        if (!$this->resourceSelection) {
            $this->resourceSelection = [];
        }
        $this->resourceSelection['url'] = $url;
    }

    /**
     * Set resource selection enabled status
     */
    public function setResourceSelectionEnabled(?bool $enabled): void
    {
        if (!$this->resourceSelection) {
            $this->resourceSelection = [];
        }
        $this->resourceSelection['enabled'] = $enabled;
    }

    /**
     * Set resource selection icon URL
     */
    public function setResourceSelectionIconUrl(?string $iconUrl): void
    {
        if (!$this->resourceSelection) {
            $this->resourceSelection = [];
        }
        $this->resourceSelection['icon_url'] = $iconUrl;
    }

    /**
     * Set resource selection width
     */
    public function setResourceSelectionWidth(?string $selectionWidth): void
    {
        if (!$this->resourceSelection) {
            $this->resourceSelection = [];
        }
        $this->resourceSelection['selection_width'] = $selectionWidth;
    }

    /**
     * Set resource selection height
     */
    public function setResourceSelectionHeight(?string $selectionHeight): void
    {
        if (!$this->resourceSelection) {
            $this->resourceSelection = [];
        }
        $this->resourceSelection['selection_height'] = $selectionHeight;
    }

    /**
     * Convert the DTO to an array for API requests with custom handling for placement configurations
     *
     * @return array<array{name: string, contents: mixed}>
     */
    public function toApiArray(): array
    {
        $properties = get_object_vars($this);
        $modifiedProperties = [];

        foreach ($properties as $property => $value) {
            // Skip the apiPropertyName itself - it's a meta property, not data
            if ($property === 'apiPropertyName') {
                continue;
            }

            if ($this->apiPropertyName === '') {
                throw new \Exception('The API property name must be set in the DTO');
            }

            // Skip null values
            if (is_null($value)) {
                continue;
            }

            // Handle placement configuration properties with nested arrays
            if (array_key_exists($property, self::PLACEMENT_PROPERTIES)) {
                $placementName = self::PLACEMENT_PROPERTIES[$property];
                if (is_array($value)) {
                    foreach ($value as $configKey => $configValue) {
                        $fieldName = $this->apiPropertyName . '[' . $placementName . '][' . $configKey . ']';
                        $modifiedProperties[] = [
                            'name' => $fieldName,
                            'contents' => $configValue
                        ];
                    }
                }
                continue;
            }

            $propertyName = $this->apiPropertyName . '[' . str_to_snake_case($property) . ']';

            // For DateTimeInterface values, format them as ISO 8601 strings
            if ($value instanceof \DateTimeInterface) {
                $modifiedProperties[] = [
                    'name' => $propertyName,
                    'contents' => $value->format(\DateTimeInterface::ATOM)
                ];
                continue;
            }

            // For arrays that are not placement configurations, handle as regular arrays
            if (is_array($value)) {
                foreach ($value as $arrayValue) {
                    $modifiedProperties[] = [
                        'name' => $propertyName . '[]',
                        'contents' => $arrayValue
                    ];
                }
                continue;
            }

            // Handle scalar values (int, string, bool)
            $modifiedProperties[] = [
                'name' => $propertyName,
                'contents' => $value
            ];
        }

        return $modifiedProperties;
    }
}
