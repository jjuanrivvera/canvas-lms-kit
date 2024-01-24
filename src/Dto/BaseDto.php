<?php

namespace CanvasLMS\Dto;

use DateTime;
use Exception;

/**
 *
 */
abstract class BaseDto
{
    /**
     * @param array $data
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
     * @param $value
     * @param string $key
     * @return DateTime|mixed
     * @throws Exception
     */
    private function cast($value, string $key)
    {
        if (in_array($key, ['startAt', 'endAt']) && is_string($value)) {
            return new DateTime($value);
        }
        return $value;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $properties = get_object_vars($this);

        foreach ($properties as $key => &$value) {
            if ($value instanceof DateTime) {
                $value = $value->format('c'); // Convert DateTime to ISO 8601 string
            }

            if (empty($value)) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    /**
     * @return array
     */
    public function toApiArray(): array
    {
        $properties = get_object_vars($this);

        $modifiedProperties = [];

        foreach ($properties as $key => &$value) {
            if ($value instanceof DateTime) {
                $value = $value->format('c'); // Convert DateTime to ISO 8601 string
            }

            if (empty($value)) {
                unset($properties[$key]);
                continue;
            }

            // Rename keys to this format course[{key}]
            $modifiedProperties[] = [
                "name" => 'course[' . str_to_snake_case($key) . ']',
                "contents" => $value
            ];
        }

        return $modifiedProperties;
    }
}
