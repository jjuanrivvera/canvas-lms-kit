<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\ContentMigrations;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating content migrations in Canvas LMS
 *
 * This DTO handles updating content migrations, primarily used for:
 * - Providing new pre_attachment values if file upload had issues
 * - Updating selective import copy parameters
 * - Modifying settings (though most won't take effect after migration starts)
 *
 * @package CanvasLMS\Dto\ContentMigrations
 */
class UpdateContentMigrationDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = '';

    /**
     * Pre-attachment array for file uploads (if retrying upload)
     *
     * @var array<string, mixed>|null
     */
    public ?array $preAttachment = null;

    /**
     * Copy parameters for selective import
     * Format: copy[resource_type][resource_id] = 1
     *
     * @var array<string, mixed>|null
     */
    public ?array $copy = null;

    /**
     * Migration settings (limited effect after migration starts)
     *
     * @var array<string, mixed>|null
     */
    public ?array $settings = null;

    /**
     * Date shift options (if updating)
     *
     * @var array<string, mixed>|null
     */
    public ?array $dateShiftOptions = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Transform the DTO to the API-expected array format
     *
     * @return array<int, array<string, mixed>>
     */
    public function toApiArray(): array
    {
        $result = [];

        // Handle pre_attachment fields for retry
        if ($this->preAttachment !== null) {
            foreach ($this->preAttachment as $key => $value) {
                $result[] = [
                    'name' => 'pre_attachment[' . $key . ']',
                    'contents' => (string) $value,
                ];
            }
        }

        // Handle copy parameters for selective import
        if ($this->copy !== null) {
            $this->processCopyParameters($this->copy, $result, 'copy');
        }

        // Handle settings updates
        if ($this->settings !== null) {
            foreach ($this->settings as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $result[] = [
                            'name' => 'settings[' . $key . '][' . $subKey . ']',
                            'contents' => (string) $subValue,
                        ];
                    }
                } else {
                    $result[] = [
                        'name' => 'settings[' . $key . ']',
                        'contents' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    ];
                }
            }
        }

        // Handle date shift options
        if ($this->dateShiftOptions !== null) {
            foreach ($this->dateShiftOptions as $key => $value) {
                if ($key === 'day_substitutions' && is_array($value)) {
                    foreach ($value as $day => $substitution) {
                        $result[] = [
                            'name' => 'date_shift_options[day_substitutions][' . $day . ']',
                            'contents' => (string) $substitution,
                        ];
                    }
                } else {
                    $result[] = [
                        'name' => 'date_shift_options[' . $key . ']',
                        'contents' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Process copy parameters recursively
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> &$result
     * @param string $prefix
     */
    private function processCopyParameters(array $data, array &$result, string $prefix): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->processCopyParameters($value, $result, $prefix . '[' . $key . ']');
            } else {
                $result[] = [
                    'name' => $prefix . '[' . $key . ']',
                    'contents' => (string) $value,
                ];
            }
        }
    }

    /**
     * Validate the DTO
     *
     * @return bool
     */
    public function validate(): bool
    {
        // At least one field should be present
        return $this->preAttachment !== null ||
               $this->copy !== null ||
               $this->settings !== null ||
               $this->dateShiftOptions !== null;
    }

    // Getters and setters

    /**
     * @return array<string, mixed>|null
     */
    public function getPreAttachment(): ?array
    {
        return $this->preAttachment;
    }

    /**
     * @param array<string, mixed>|null $preAttachment
     */
    public function setPreAttachment(?array $preAttachment): void
    {
        $this->preAttachment = $preAttachment;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCopy(): ?array
    {
        return $this->copy;
    }

    /**
     * @param array<string, mixed>|null $copy
     */
    public function setCopy(?array $copy): void
    {
        $this->copy = $copy;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    public function setSettings(?array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDateShiftOptions(): ?array
    {
        return $this->dateShiftOptions;
    }

    /**
     * @param array<string, mixed>|null $dateShiftOptions
     */
    public function setDateShiftOptions(?array $dateShiftOptions): void
    {
        $this->dateShiftOptions = $dateShiftOptions;
    }

    /**
     * Set file upload retry parameters
     *
     * @param string $fileName
     * @param int $fileSize
     * @param string|null $contentType
     */
    public function retryFileUpload(string $fileName, int $fileSize, ?string $contentType = null): void
    {
        $this->preAttachment = [
            'name' => $fileName,
            'size' => $fileSize,
        ];

        if ($contentType !== null) {
            $this->preAttachment['content_type'] = $contentType;
        }
    }

    /**
     * Add items to copy for selective import
     *
     * @param string $resourceType Resource type (e.g., 'assignments', 'quizzes')
     * @param array<string|int> $resourceIds Array of resource IDs to copy
     */
    public function addCopyItems(string $resourceType, array $resourceIds): void
    {
        if ($this->copy === null) {
            $this->copy = [];
        }

        if (!isset($this->copy[$resourceType])) {
            $this->copy[$resourceType] = [];
        }

        foreach ($resourceIds as $id) {
            $this->copy[$resourceType][(string) $id] = '1';
        }
    }

    /**
     * Set copy parameter using property string from selective data
     *
     * @param string $property Property string (e.g., 'copy[assignments][id_i2102a7fa93b29226774949298626719d]')
     * @param bool $include Whether to include this item
     */
    public function setCopyProperty(string $property, bool $include = true): void
    {
        if (!$include) {
            return;
        }

        // Parse the property string
        if (preg_match('/copy\[(.*?)\]\[(.*?)\]/', $property, $matches)) {
            // Handle nested properties like copy[assignments][id_123]
            $resourceType = $matches[1];
            $resourceId = $matches[2];

            if ($this->copy === null) {
                $this->copy = [];
            }

            if (!isset($this->copy[$resourceType])) {
                $this->copy[$resourceType] = [];
            }

            $this->copy[$resourceType][$resourceId] = '1';
        } elseif (preg_match('/copy\[(.*?)\]$/', $property, $matches)) {
            // Handle single-level properties like copy[all_course_settings]
            $propertyName = $matches[1];

            if ($this->copy === null) {
                $this->copy = [];
            }

            $this->copy[$propertyName] = '1';
        }
    }

    /**
     * Set all items of a type to be copied
     *
     * @param string $property Property for all items (e.g., 'copy[all_assignments]')
     */
    public function setCopyAll(string $property): void
    {
        if (preg_match('/copy\[(all_.*?)\]/', $property, $matches)) {
            $allType = $matches[1];

            if ($this->copy === null) {
                $this->copy = [];
            }

            $this->copy[$allType] = '1';
        }
    }
}
