<?php

namespace CanvasLMS\Objects;

/**
 * Avatar option object
 *
 * Represents an available avatar option for a user in Canvas LMS.
 * Users can choose from multiple avatar options or upload their own.
 */
class Avatar
{
    /**
     * @var string Avatar type (e.g., 'attachment', 'gravatar', 'twitter', 'facebook')
     */
    public string $type;

    /**
     * @var string|null URL to the avatar image
     */
    public ?string $url = null;

    /**
     * @var string|null Token for selecting this avatar
     */
    public ?string $token = null;

    /**
     * @var string|null Display name for the avatar option
     */
    public ?string $displayName = null;

    /**
     * @var int|null Avatar ID (for attachment type)
     */
    public ?int $id = null;

    /**
     * @var string|null Content type of the avatar image
     */
    public ?string $contentType = null;

    /**
     * @var string|null Filename of the avatar (for attachment type)
     */
    public ?string $filename = null;

    /**
     * @var int|null Size in bytes (for attachment type)
     */
    public ?int $size = null;

    /**
     * Constructor
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Check if this is an attachment avatar
     * @return bool
     */
    public function isAttachment(): bool
    {
        return $this->type === 'attachment';
    }

    /**
     * Check if this is a Gravatar
     * @return bool
     */
    public function isGravatar(): bool
    {
        return $this->type === 'gravatar';
    }

    /**
     * Check if this is a social media avatar
     * @return bool
     */
    public function isSocialMedia(): bool
    {
        return in_array($this->type, ['twitter', 'facebook', 'linkedin']);
    }

    /**
     * Get avatar size info (for attachment type)
     * @return string|null Human readable size
     */
    public function getHumanReadableSize(): ?string
    {
        if (!$this->size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Convert to array
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
