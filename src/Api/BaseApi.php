<?php

namespace CanvasLMS\Api;

use CanvasLMS\Http\HttpClient;
use CanvasLMS\Interfaces\HttpClientInterface;
use DateTime;
use Exception;


/**
 *
 */
abstract class BaseApi
{
    /**
     * @var HttpClientInterface
     */
    protected static HttpClientInterface $apiClient;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $key) && !is_null($value)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @param $apiClient
     * @return void
     */
    public static function setApiClient($apiClient): void
    {
        self::$apiClient = $apiClient;
    }

    /**
     * @return void
     */
    protected static function checkApiClient(): void
    {
        if (!isset(self::$apiClient)) {
            self::$apiClient = new HttpClient();
        }
    }

    /**
     * @return array
     */
    protected function toDtoArray(): array
    {
        $courseData = get_object_vars($this);

        // Format DateTime objects and handle other specific transformations
        foreach ($courseData as $key => &$value) {
            if ($value instanceof DateTime) {
                $value = $value->format('c'); // Convert DateTime to ISO 8601 string
            }
        }

        return $courseData;
    }

    /**
     * @param array $courseData
     * @return void
     * @throws Exception
     */
    protected function populate(array $courseData): void
    {
        foreach ($courseData as $key => $value) {
            $key = str_to_snake_case($key);
            if (property_exists($this, $key)) {
                $this->{$key} = $this->castValue($key, $value);
            }
        }
    }

    /**
     * @param string $key
     * @param $value
     * @return DateTime|mixed
     * @throws Exception
     */
    protected function castValue(string $key, $value)
    {
        if (in_array($key, ['startAt', 'endAt']) && is_string($value)) {
            return new DateTime($value);
        }
        return $value;
    }
}