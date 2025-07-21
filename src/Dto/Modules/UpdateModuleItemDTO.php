<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Modules;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Update Module Item DTO
 *
 * Data Transfer Object for updating Canvas module items.
 * Similar to CreateModuleItemDTO but all properties are optional.
 *
 * Usage:
 * ```php
 * // Update module item title and position
 * $dto = new UpdateModuleItemDTO([
 *     'title' => 'Updated Assignment Title',
 *     'position' => 2
 * ]);
 *
 * // Update external tool configuration
 * $dto = new UpdateModuleItemDTO([
 *     'external_url' => 'https://updated-tool.com',
 *     'iframe' => ['width' => 1000, 'height' => 800]
 * ]);
 *
 * // Update completion requirements
 * $dto = new UpdateModuleItemDTO([
 *     'completion_requirement' => [
 *         'type' => 'min_score',
 *         'min_score' => 80
 *     ]
 * ]);
 * ```
 */
class UpdateModuleItemDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * Canvas API property name for module items
     */
    protected string $apiPropertyName = 'module_item';

    /**
     * Content type (File, Page, Discussion, Assignment, Quiz, SubHeader, ExternalUrl, ExternalTool).
     *
     * @var string|null
     */
    public ?string $type = null;

    /**
     * ID of associated content object.
     *
     * @var int|null
     */
    public ?int $contentId = null;

    /**
     * Wiki page URL slug (Page type).
     *
     * @var string|null
     */
    public ?string $pageUrl = null;

    /**
     * External URL for ExternalUrl/ExternalTool types.
     *
     * @var string|null
     */
    public ?string $externalUrl = null;

    /**
     * Item title.
     *
     * @var string|null
     */
    public ?string $title = null;

    /**
     * 1-based position in module.
     *
     * @var int|null
     */
    public ?int $position = null;

    /**
     * 0-based hierarchy indent level (0-5 allowed).
     *
     * @var int|null
     */
    public ?int $indent = null;

    /**
     * Whether external tool opens in new tab (ExternalTool only).
     *
     * @var bool|null
     */
    public ?bool $newTab = null;

    /**
     * Completion requirement configuration.
     * Structure: ['type' => 'must_view|must_contribute|must_submit|min_score|must_mark_done', 'min_score' => int]
     *
     * @var array<string, mixed>|null
     */
    public ?array $completionRequirement = null;

    /**
     * External tool iframe configuration (ExternalTool only).
     * Structure: ['width' => int, 'height' => int]
     *
     * @var array<string, int>|null
     */
    public ?array $iframe = null;

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
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
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @param int|null $position
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return int|null
     */
    public function getIndent(): ?int
    {
        return $this->indent;
    }

    /**
     * @param int|null $indent
     */
    public function setIndent(?int $indent): void
    {
        $this->indent = $indent;
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
     * Convert the DTO to an array for API requests
     * Handles nested arrays for iframe and completion_requirement
     * @return mixed[]
     */
    public function toApiArray(): array
    {
        $properties = get_object_vars($this);
        $modifiedProperties = [];

        foreach ($properties as $property => $value) {
            // Skip apiPropertyName and null values
            if ($property === 'apiPropertyName' || is_null($value)) {
                continue;
            }

            $propertyName = $this->apiPropertyName . '[' . str_to_snake_case($property) . ']';

            // Handle nested arrays (iframe, completionRequirement)
            if (is_array($value) && in_array($property, ['iframe', 'completionRequirement'])) {
                foreach ($value as $key => $arrayValue) {
                    $modifiedProperties[] = [
                        "name" => $propertyName . '[' . $key . ']',
                        "contents" => $arrayValue
                    ];
                }
                continue;
            }

            // Handle scalar values
            $modifiedProperties[] = [
                "name" => $propertyName,
                "contents" => $value
            ];
        }

        return $modifiedProperties;
    }
}
