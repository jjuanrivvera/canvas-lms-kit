<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Course Nickname object
 *
 * Represents a user's custom nickname for a course in Canvas LMS.
 * Users can set custom names for courses to help them organize and identify courses.
 */
class CourseNickname
{
    /**
     * @var int Course ID
     */
    public int $courseId;

    /**
     * @var string The user's custom nickname for the course
     */
    public string $name;

    /**
     * @var string|null The course's original name
     */
    public ?string $courseName = null;

    /**
     * Constructor
     *
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
     * Check if nickname is different from original course name
     *
     * @return bool
     */
    public function isCustomized(): bool
    {
        return $this->courseName && $this->name !== $this->courseName;
    }

    /**
     * Get display name (nickname or original name)
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Get original course name
     *
     * @return string|null
     */
    public function getOriginalName(): ?string
    {
        return $this->courseName;
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
