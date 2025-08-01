<?php

namespace CanvasLMS\Dto;

use DateTime;
use Exception;
use DateTimeInterface;

/**
 *
 */
abstract class AbstractBaseDto
{
    /**
     * The name of the property in the API
     * @var string
     */
    protected string $apiPropertyName = '';

    /**
     * BaseDto constructor.
     * @param mixed[] $data
     * @throws Exception
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $key)) {
                $this->$key = $value instanceof DateTime ? $value : $this->cast($value, $key);
            }
        }
    }

    /**
     * Cast the value to the correct type
     * @param mixed $value
     * @param string $key
     * @return DateTime|mixed
     * @throws Exception
     */
    private function cast($value, string $key)
    {
        // Check if the property expects a DateTimeInterface
        $reflection = new \ReflectionClass($this);
        if ($reflection->hasProperty($key)) {
            $property = $reflection->getProperty($key);
            $type = $property->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                if ($typeName === 'DateTimeInterface' || is_subclass_of($typeName, 'DateTimeInterface')) {
                    if (is_string($value) && !empty($value)) {
                        return new DateTime($value);
                    }
                    return null;
                }
            }
        }

        // Legacy support for known date fields ONLY if they're typed as DateTime/DateTimeInterface
        if (in_array($key, ['startAt', 'endAt']) && is_string($value) && !empty($value)) {
            $reflection = new \ReflectionClass($this);
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    return new DateTime($value);
                }
            }
        }

        return $value;
    }

    /**
     * Convert the DTO to an array
     * @return mixed[]
     */
    public function toArray(): array
    {
        $properties = get_object_vars($this);

        foreach ($properties as $key => &$value) {
            // Skip the apiPropertyName itself - it's a meta property, not data
            if ($key === 'apiPropertyName') {
                unset($properties[$key]);
                continue;
            }

            if ($value instanceof DateTime) {
                $value = $value->format('c'); // Convert DateTime to ISO 8601 string
            }

            if (is_null($value) || (empty($value) && !is_bool($value) && $value !== 0 && $value !== '0')) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    /**
     * Convert the DTO to an array for API requests
     * @return mixed[]
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
                throw new Exception('The API property name must be set in the DTO');
            }

            $propertyName = $this->apiPropertyName . '[' . str_to_snake_case($property) . ']';

            // Directly handle null values to continue to the next iteration.
            if (is_null($value)) {
                continue;
            }

            // For DateTimeInterface values, format them as ISO 8601 strings.
            if ($value instanceof DateTimeInterface) {
                $modifiedProperties[] = [
                    "name" => $propertyName,
                    "contents" => $value->format(DateTimeInterface::ATOM)
                ];
                continue;
            }

            // For arrays, handle each element as a separate field with the same name.
            if (is_array($value)) {
                foreach ($value as $arrayValue) {
                    $modifiedProperties[] = [
                        "name" => $propertyName . '[]',
                        "contents" => $arrayValue
                    ];
                }
                continue;
            }

            // Handle scalar values (int, string, bool) as they don't need special treatment.
            $modifiedProperties[] = [
                "name" => $propertyName,
                "contents" => $value
            ];
        }

        return $modifiedProperties;
    }
}
