<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Sections;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;
use CanvasLMS\Utilities\Str;

/**
 * Data Transfer Object for updating Canvas sections.
 *
 * @see https://canvas.instructure.com/doc/api/sections.html#method.sections.update
 */
class UpdateSectionDTO extends AbstractBaseDto implements DTOInterface
{
    protected string $apiPropertyName = 'course_section';

    /**
     * The name of the section.
     */
    public ?string $name = null;

    /**
     * The SIS ID of the section.
     * Requires manage_sis permission to set.
     */
    public ?string $sisSectionId = null;

    /**
     * The integration_id of the section.
     * Requires manage_sis permission to set.
     */
    public ?string $integrationId = null;

    /**
     * Section start date in ISO8601 format.
     * Example: 2011-01-01T01:00Z
     */
    public ?\DateTime $startAt = null;

    /**
     * Section end date in ISO8601 format.
     * Example: 2011-01-01T01:00Z
     */
    public ?\DateTime $endAt = null;

    /**
     * Set to true to restrict user enrollments to the start and end dates of the section.
     */
    public ?bool $restrictEnrollmentsToSectionDates = null;

    /**
     * Default is true. If false, any fields containing "sticky" changes will not be updated.
     * This parameter is NOT part of the course_section wrapper.
     */
    public ?bool $overrideSisStickiness = null;

    /**
     * Convert DTO to API array format.
     * Handles the special override_sis_stickiness parameter separately.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toApiArray(): array
    {
        // Get all properties except overrideSisStickiness which needs special handling
        $properties = get_object_vars($this);
        $modifiedProperties = [];

        foreach ($properties as $property => $value) {
            // Skip meta properties and the special overrideSisStickiness property
            if ($property === 'apiPropertyName' || $property === 'overrideSisStickiness') {
                continue;
            }

            if ($this->apiPropertyName === '') {
                throw new \Exception('The API property name must be set in the DTO');
            }

            $propertyName = $this->apiPropertyName . '[' . Str::toSnakeCase($property) . ']';

            // Skip null values
            if (is_null($value)) {
                continue;
            }

            // Handle DateTime objects
            if ($value instanceof \DateTimeInterface) {
                $modifiedProperties[] = [
                    'name' => $propertyName,
                    'contents' => $value->format(\DateTimeInterface::ATOM),
                ];
                continue;
            }

            // Handle arrays
            if (is_array($value)) {
                foreach ($value as $arrayValue) {
                    $modifiedProperties[] = [
                        'name' => $propertyName . '[]',
                        'contents' => $arrayValue,
                    ];
                }
                continue;
            }

            // Handle regular values
            $modifiedProperties[] = [
                'name' => $propertyName,
                'contents' => $value,
            ];
        }

        // Handle override_sis_stickiness separately (not part of course_section)
        if ($this->overrideSisStickiness !== null) {
            $modifiedProperties[] = [
                'name' => 'override_sis_stickiness',
                'contents' => $this->overrideSisStickiness,
            ];
        }

        return $modifiedProperties;
    }
}
