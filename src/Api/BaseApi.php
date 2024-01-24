<?php

namespace CanvasLMS\Api;

use DateTime;
use Exception;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Interfaces\HttpClientInterface;

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
     * BaseApi constructor.
     * @param mixed[] $data
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
     * Set the API client
     * @param HttpClientInterface $apiClient
     * @return void
     */
    public static function setApiClient(HttpClientInterface $apiClient): void
    {
        self::$apiClient = $apiClient;
    }

    /**
     * Check if the API client is set, if not, instantiate a new one
     * @return void
     */
    protected static function checkApiClient(): void
    {
        if (!isset(self::$apiClient)) {
            self::$apiClient = new HttpClient();
        }
    }

    /**
     * Convert the object to an array
     * @return mixed[]
     */
    protected function toDtoArray(): array
    {
        $data = get_object_vars($this);

        // Format DateTime objects and handle other specific transformations
        foreach ($data as $key => &$value) {
            if ($value instanceof DateTime) {
                $value = $value->format('c'); // Convert DateTime to ISO 8601 string
            }
        }

        return $data;
    }

    /**
     * Populate the object with new data
     * @param mixed[] $data
     * @return void
     * @throws Exception
     */
    protected function populate(array $data): void
    {
        foreach ($data as $key => $value) {
            $key = str_to_snake_case($key);
            if (property_exists($this, $key)) {
                $this->{$key} = $this->castValue($key, $value);
            }
        }
    }

    /**
     * Cast a value to the correct type
     * @param string $key
     * @param mixed $value
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
