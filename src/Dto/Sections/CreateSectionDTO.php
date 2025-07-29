<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Sections;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

use function str_to_snake_case;

/**
 * Data Transfer Object for creating Canvas sections.
 *
 * @see https://canvas.instructure.com/doc/api/sections.html#method.sections.create
 */
class CreateSectionDTO extends AbstractBaseDto implements DTOInterface
{
    protected string $apiPropertyName = 'course_section';

    /**
     * The name of the section (required).
     */
    public string $name = '';

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
     * When true, will first try to re-activate a deleted section with matching sis_section_id.
     * This parameter is NOT part of the course_section wrapper.
     */
    public ?bool $enableSisReactivation = null;

    /**
     * Convert DTO to API array format.
     * Handles the special enable_sis_reactivation parameter separately.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toApiArray(): array
    {
        // Get all properties except enableSisReactivation which needs special handling
        $properties = get_object_vars($this);
        $modifiedProperties = [];

        foreach ($properties as $property => $value) {
            // Skip meta properties and the special enableSisReactivation property
            if ($property === 'apiPropertyName' || $property === 'enableSisReactivation') {
                continue;
            }

            if ($this->apiPropertyName === '') {
                throw new \Exception('The API property name must be set in the DTO');
            }

            $propertyName = $this->apiPropertyName . '[' . str_to_snake_case($property) . ']';

            // Skip null values
            if (is_null($value)) {
                continue;
            }

            // Handle DateTime objects
            if ($value instanceof \DateTimeInterface) {
                $modifiedProperties[] = [
                    "name" => $propertyName,
                    "contents" => $value->format(\DateTimeInterface::ATOM)
                ];
                continue;
            }

            // Handle arrays
            if (is_array($value)) {
                foreach ($value as $arrayValue) {
                    $modifiedProperties[] = [
                        "name" => $propertyName . '[]',
                        "contents" => $arrayValue
                    ];
                }
                continue;
            }

            // Handle regular values
            $modifiedProperties[] = [
                "name" => $propertyName,
                "contents" => $value
            ];
        }

        // Handle enable_sis_reactivation separately (not part of course_section)
        if ($this->enableSisReactivation !== null) {
            $modifiedProperties[] = [
                "name" => 'enable_sis_reactivation',
                "contents" => $this->enableSisReactivation
            ];
        }

        return $modifiedProperties;
    }

    /**
     * Validate the DTO data.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Section name is required');
        }
    }
}
