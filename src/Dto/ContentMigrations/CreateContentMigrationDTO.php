<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\ContentMigrations;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for creating content migrations in Canvas LMS
 *
 * This DTO handles the creation of new content migrations with support for
 * various migration types, file uploads, settings, and date shifting options.
 *
 * @package CanvasLMS\Dto\ContentMigrations
 */
class CreateContentMigrationDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = '';

    /**
     * The type of the migration (required)
     * Allowed values: canvas_cartridge_importer, common_cartridge_importer,
     * course_copy_importer, zip_file_importer, qti_converter, moodle_converter
     */
    public ?string $migrationType = null;

    /**
     * Pre-attachment array for file uploads
     * Contains: name, size, content_type, etc.
     *
     * @var array<string, mixed>|null
     */
    public ?array $preAttachment = null;

    /**
     * Migration settings
     *
     * @var array<string, mixed>|null
     */
    public ?array $settings = null;

    /**
     * Date shift options for content migration
     *
     * @var array<string, mixed>|null
     */
    public ?array $dateShiftOptions = null;

    /**
     * Whether to perform selective import
     */
    public ?bool $selectiveImport = null;

    /**
     * Selection parameters for course copy migrations
     *
     * @var array<string, array<string|int>>|null
     */
    public ?array $select = null;

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

        // Migration type is required and goes at top level
        if ($this->migrationType !== null) {
            $result[] = [
                'name' => 'migration_type',
                'contents' => $this->migrationType,
            ];
        }

        // Handle pre_attachment fields
        if ($this->preAttachment !== null) {
            foreach ($this->preAttachment as $key => $value) {
                $result[] = [
                    'name' => 'pre_attachment[' . $key . ']',
                    'contents' => (string) $value,
                ];
            }
        }

        // Handle settings
        if ($this->settings !== null) {
            foreach ($this->settings as $key => $value) {
                if (is_array($value)) {
                    // Handle nested arrays in settings
                    foreach ($value as $subKey => $subValue) {
                        $result[] = [
                            'name' => 'settings[' . $key . '][' . $subKey . ']',
                            'contents' => (string) $subValue,
                        ];
                    }
                } else {
                    $result[] = [
                        'name' => 'settings[' . $key . ']',
                        'contents' => (string) $value,
                    ];
                }
            }
        }

        // Handle date shift options
        if ($this->dateShiftOptions !== null) {
            foreach ($this->dateShiftOptions as $key => $value) {
                if ($key === 'day_substitutions' && is_array($value)) {
                    // Special handling for day substitutions
                    foreach ($value as $day => $substitution) {
                        $result[] = [
                            'name' => 'date_shift_options[day_substitutions][' . $day . ']',
                            'contents' => (string) $substitution,
                        ];
                    }
                } else {
                    $result[] = [
                        'name' => 'date_shift_options[' . $key . ']',
                        'contents' => (string) $value,
                    ];
                }
            }
        }

        // Handle selective import flag
        if ($this->selectiveImport !== null) {
            $result[] = [
                'name' => 'selective_import',
                'contents' => $this->selectiveImport ? '1' : '0',
            ];
        }

        // Handle select parameter for course copy
        if ($this->select !== null) {
            foreach ($this->select as $type => $ids) {
                foreach ($ids as $id) {
                    $result[] = [
                        'name' => 'select[' . $type . '][]',
                        'contents' => (string) $id,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Validate the DTO
     *
     * @return bool
     */
    public function validate(): bool
    {
        // Migration type is required
        if (empty($this->migrationType)) {
            return false;
        }

        // Validate migration type
        $validTypes = [
            'canvas_cartridge_importer',
            'common_cartridge_importer',
            'course_copy_importer',
            'zip_file_importer',
            'qti_converter',
            'moodle_converter',
        ];

        if (!in_array($this->migrationType, $validTypes, true)) {
            return false;
        }

        // Course copy requires source_course_id
        if ($this->migrationType === 'course_copy_importer') {
            if (empty($this->settings['source_course_id'])) {
                return false;
            }
        }

        // File-based migrations require pre_attachment or settings[file_url]
        $fileBasedTypes = [
            'canvas_cartridge_importer',
            'common_cartridge_importer',
            'zip_file_importer',
            'qti_converter',
            'moodle_converter',
        ];

        if (in_array($this->migrationType, $fileBasedTypes, true)) {
            $hasPreAttachment = !empty($this->preAttachment['name']);
            $hasFileUrl = !empty($this->settings['file_url']);
            $hasContentExportId = !empty($this->settings['content_export_id']);

            if (!$hasPreAttachment && !$hasFileUrl && !$hasContentExportId) {
                return false;
            }
        }

        return true;
    }

    // Getters and setters

    public function getMigrationType(): ?string
    {
        return $this->migrationType;
    }

    public function setMigrationType(?string $migrationType): void
    {
        $this->migrationType = $migrationType;
    }

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

    public function getSelectiveImport(): ?bool
    {
        return $this->selectiveImport;
    }

    public function setSelectiveImport(?bool $selectiveImport): void
    {
        $this->selectiveImport = $selectiveImport;
    }

    /**
     * @return array<string, array<string|int>>|null
     */
    public function getSelect(): ?array
    {
        return $this->select;
    }

    /**
     * @param array<string, array<string|int>>|null $select
     */
    public function setSelect(?array $select): void
    {
        $this->select = $select;
    }

    /**
     * Set file upload parameters
     *
     * @param string $fileName
     * @param int $fileSize
     * @param string|null $contentType
     */
    public function setFileUpload(string $fileName, int $fileSize, ?string $contentType = null): void
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
     * Set course copy source
     *
     * @param int $sourceCourseId
     */
    public function setCourseCopySource(int $sourceCourseId): void
    {
        if ($this->settings === null) {
            $this->settings = [];
        }
        $this->settings['source_course_id'] = $sourceCourseId;
    }

    /**
     * Set file URL for import
     *
     * @param string $fileUrl
     */
    public function setFileUrl(string $fileUrl): void
    {
        if ($this->settings === null) {
            $this->settings = [];
        }
        $this->settings['file_url'] = $fileUrl;
    }

    /**
     * Configure date shifting
     *
     * @param bool $shiftDates
     * @param string|null $oldStartDate
     * @param string|null $oldEndDate
     * @param string|null $newStartDate
     * @param string|null $newEndDate
     */
    public function configureDateShifting(
        bool $shiftDates,
        ?string $oldStartDate = null,
        ?string $oldEndDate = null,
        ?string $newStartDate = null,
        ?string $newEndDate = null
    ): void {
        $this->dateShiftOptions = [
            'shift_dates' => $shiftDates,
        ];

        if ($oldStartDate !== null) {
            $this->dateShiftOptions['old_start_date'] = $oldStartDate;
        }
        if ($oldEndDate !== null) {
            $this->dateShiftOptions['old_end_date'] = $oldEndDate;
        }
        if ($newStartDate !== null) {
            $this->dateShiftOptions['new_start_date'] = $newStartDate;
        }
        if ($newEndDate !== null) {
            $this->dateShiftOptions['new_end_date'] = $newEndDate;
        }
    }

    /**
     * Add day substitution for date shifting
     *
     * @param int $fromDay Day of week (0-6, 0=Sunday)
     * @param int $toDay Day of week (0-6, 0=Sunday)
     */
    public function addDaySubstitution(int $fromDay, int $toDay): void
    {
        if ($this->dateShiftOptions === null) {
            $this->dateShiftOptions = [];
        }
        if (!isset($this->dateShiftOptions['day_substitutions'])) {
            $this->dateShiftOptions['day_substitutions'] = [];
        }
        $this->dateShiftOptions['day_substitutions'][$fromDay] = $toDay;
    }
}
