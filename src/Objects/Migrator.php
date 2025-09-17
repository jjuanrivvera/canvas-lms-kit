<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Migrator Object
 *
 * Represents a migration system available in Canvas LMS.
 * This is a read-only object returned by the Content Migrations API
 * when listing available migration systems.
 *
 * @package CanvasLMS\Objects
 */
class Migrator
{
    /**
     * The value to pass to the create endpoint
     * Examples: common_cartridge_importer, course_copy_importer, zip_file_importer
     */
    public ?string $type = null;

    /**
     * Whether this endpoint requires a file upload
     */
    public ?bool $requiresFileUpload = null;

    /**
     * Description of the package type expected
     * Example: "Common Cartridge 1.0/1.1/1.2 Package"
     */
    public ?string $name = null;

    /**
     * A list of fields this system requires
     *
     * @var array<string>|null
     */
    public ?array $requiredSettings = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $key) && !is_null($value)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Check if this migrator requires a specific setting
     *
     * @param string $setting Setting name to check
     *
     * @return bool
     */
    public function requiresSetting(string $setting): bool
    {
        if ($this->requiredSettings === null) {
            return false;
        }

        return in_array($setting, $this->requiredSettings, true);
    }

    /**
     * Check if this is a file-based migrator
     *
     * @return bool
     */
    public function isFileBased(): bool
    {
        return $this->requiresFileUpload === true;
    }

    /**
     * Check if this is a course copy migrator
     *
     * @return bool
     */
    public function isCourseCopy(): bool
    {
        return $this->type === 'course_copy_importer';
    }

    /**
     * Check if this is a Common Cartridge migrator
     *
     * @return bool
     */
    public function isCommonCartridge(): bool
    {
        return $this->type === 'common_cartridge_importer';
    }

    /**
     * Check if this is a Canvas Cartridge migrator
     *
     * @return bool
     */
    public function isCanvasCartridge(): bool
    {
        return $this->type === 'canvas_cartridge_importer';
    }

    /**
     * Check if this is a ZIP file migrator
     *
     * @return bool
     */
    public function isZipFile(): bool
    {
        return $this->type === 'zip_file_importer';
    }

    /**
     * Check if this is a QTI migrator
     *
     * @return bool
     */
    public function isQti(): bool
    {
        return $this->type === 'qti_converter';
    }

    /**
     * Check if this is a Moodle migrator
     *
     * @return bool
     */
    public function isMoodle(): bool
    {
        return $this->type === 'moodle_converter';
    }

    // Getter methods

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getRequiresFileUpload(): ?bool
    {
        return $this->requiresFileUpload;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array<string>|null
     */
    public function getRequiredSettings(): ?array
    {
        return $this->requiredSettings;
    }
}
