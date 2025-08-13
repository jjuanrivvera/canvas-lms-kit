<?php

namespace CanvasLMS\Api;

use DateTime;
use Exception;
use InvalidArgumentException;
use CanvasLMS\Config;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Interfaces\ApiInterface;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * Abstract base class for Canvas LMS API resources.
 *
 * Provides common functionality for all API resource classes including CRUD operations,
 * pagination support, HTTP client management, and data population from API responses.
 * Implements the Active Record pattern for Canvas API interactions.
 */
abstract class AbstractBaseApi implements ApiInterface
{
    /**
     * @var HttpClientInterface
     */
    protected static HttpClientInterface $apiClient;

    /**
     * Define method aliases
     * @var mixed[]
     */
    protected static array $methodAliases = [
        'fetchAll' => ['all', 'get', 'getAll'],
        'find' => ['one', 'getOne'],
        'fetchAllPaginated' => ['allPaginated', 'getPaginated'],
        'fetchAllPages' => ['allPages', 'getPages'],
        'fetchPage' => ['page', 'getPage']
    ];

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
            self::$apiClient = self::createConfiguredHttpClient();
        }
    }

    /**
     * Create an HttpClient with configured middleware
     * @return HttpClient
     */
    protected static function createConfiguredHttpClient(): HttpClient
    {
        $middlewareConfig = Config::getMiddleware();
        $middleware = [];
        $logger = null;

        // Check if logging is configured
        if (isset($middlewareConfig['logging']) && $middlewareConfig['logging']['enabled'] !== false) {
            // For now, use NullLogger unless a logger is provided via setApiClient
            // In a future enhancement, we could support PSR-3 logger configuration
            $logger = new \Psr\Log\NullLogger();
        }

        // If middleware config is empty, HttpClient will use defaults
        if (!empty($middlewareConfig)) {
            // Build middleware instances from configuration
            if (isset($middlewareConfig['retry'])) {
                $middleware[] = new \CanvasLMS\Http\Middleware\RetryMiddleware($middlewareConfig['retry']);
            }

            if (isset($middlewareConfig['rate_limit'])) {
                $middleware[] = new \CanvasLMS\Http\Middleware\RateLimitMiddleware($middlewareConfig['rate_limit']);
            }

            if (isset($middlewareConfig['logging']) && $logger !== null) {
                $loggingConfig = $middlewareConfig['logging'];
                unset($loggingConfig['enabled']); // Remove the enabled flag
                $middleware[] = new \CanvasLMS\Http\Middleware\LoggingMiddleware($logger, $loggingConfig);
            }
        }

        return new HttpClient(null, $logger, $middleware);
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
    protected function castValue(string $key, mixed $value): mixed
    {
        if (in_array($key, ['startAt', 'endAt']) && is_string($value)) {
            return new DateTime($value);
        }
        return $value;
    }

    /**
     * Helper method to get paginated response from API endpoint
     * @param string $endpoint The API endpoint path
     * @param mixed[] $params Query parameters for the request
     * @return PaginatedResponse
     */
    protected static function getPaginatedResponse(string $endpoint, array $params = []): PaginatedResponse
    {
        self::checkApiClient();

        return self::$apiClient->getPaginated($endpoint, [
            'query' => $params
        ]);
    }

    /**
     * Helper method to convert paginated response data to model instances
     * @param PaginatedResponse $paginatedResponse
     * @return static[]
     */
    protected static function convertPaginatedResponseToModels(PaginatedResponse $paginatedResponse): array
    {
        $data = $paginatedResponse->getJsonData();

        return array_map(function ($item) {
            /** @phpstan-ignore-next-line */
            return new static($item);
        }, $data);
    }

    /**
     * Helper method to fetch all pages and convert to model instances
     * @param string $endpoint The API endpoint path
     * @param mixed[] $params Query parameters for the request
     * @return static[]
     */
    protected static function fetchAllPagesAsModels(string $endpoint, array $params = []): array
    {
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->fetchAllPages();

        return array_map(function ($item) {
            /** @phpstan-ignore-next-line */
            return new static($item);
        }, $allData);
    }

    /**
     * Helper method to create PaginationResult from paginated response
     * @param PaginatedResponse $paginatedResponse
     * @return PaginationResult
     */
    protected static function createPaginationResult(PaginatedResponse $paginatedResponse): PaginationResult
    {
        $models = self::convertPaginatedResponseToModels($paginatedResponse);
        return $paginatedResponse->toPaginationResult($models);
    }

    /**
     * Magic method to handle function aliases
     * @param string $name
     * @param mixed[] $arguments
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function __callStatic($name, $arguments)
    {
        foreach (static::$methodAliases as $method => $aliases) {
            if (in_array($name, $aliases)) {
                return static::$method(...$arguments);
            }
        }

        throw new InvalidArgumentException("Method $name does not exist");
    }
}
