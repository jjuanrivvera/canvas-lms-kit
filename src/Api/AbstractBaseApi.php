<?php

namespace CanvasLMS\Api;

use DateTime;
use Exception;
use InvalidArgumentException;
use CanvasLMS\Config;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Interfaces\ApiInterface;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Abstract base class for Canvas LMS API resources.
 *
 * Provides common functionality for all API resource classes including CRUD operations,
 * pagination support, HTTP client management, and data population from API responses.
 * Implements the Active Record pattern for Canvas API interactions.
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractBaseApi implements ApiInterface
{
    /**
     * @var HttpClientInterface
     */
    protected static HttpClientInterface $apiClient;

    /**
     * @var int|null Instance-level masquerade user ID
     */
    protected ?int $instanceMasqueradeUserId = null;

    /**
     * @var int|null Temporary masquerade user ID for static method chaining
     */
    protected static ?int $tempMasqueradeUserId = null;

    /**
     * Define method aliases
     * @var mixed[]
     */
    protected static array $methodAliases = [
        'get' => ['fetch', 'list', 'fetchAll'],
        'all' => ['fetchAllPages', 'getAll'],
        'paginate' => ['getPaginated', 'withPagination', 'fetchPage'],
        'find' => ['one', 'getOne']
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

        // Transfer temporary masquerade user ID to instance if present
        if (static::$tempMasqueradeUserId !== null) {
            $this->instanceMasqueradeUserId = static::$tempMasqueradeUserId;
            static::$tempMasqueradeUserId = null; // Clear temporary value
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
     * Enable masquerading for fluent interface operations.
     *
     * This method temporarily sets a masquerade user ID that will be used
     * for the next static method call. The masquerading is automatically
     * cleared after the operation completes.
     *
     * Note: For this to work properly, static methods that make API calls
     * should use withTemporaryMasquerading() wrapper.
     *
     * @param int $userId The Canvas user ID to masquerade as
     * @return string Returns the class name for static method chaining
     *
     * @example
     * // Due to PHP limitations, use this pattern:
     * // Set masquerading
     * User::asUser(456);
     * $user = User::find(123); // This call will be masqueraded
     *
     * // Or for a more fluent approach in derived classes:
     * // Implement a custom find method that handles masquerading
     */
    public static function asUser(int $userId): string
    {
        // Store the masquerade user ID temporarily for the next static operation
        static::$tempMasqueradeUserId = $userId;

        // Return the class name to allow pseudo-chaining
        return static::class;
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
            // Use the configured logger from Config, defaults to NullLogger if not configured
            $logger = Config::getLogger();
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
        foreach ($data as &$value) {
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
     * Apply instance-level masquerading temporarily.
     *
     * This method temporarily overrides the global masquerading configuration
     * for a single operation, then restores the original state.
     *
     * @param callable $operation The operation to perform with masquerading
     * @return mixed The result of the operation
     */
    protected function withInstanceMasquerading(callable $operation): mixed
    {
        if ($this->instanceMasqueradeUserId === null) {
            // No instance masquerading, just execute the operation
            return $operation();
        }

        // Store the current global masquerade user ID
        $originalMasqueradeUserId = Config::getMasqueradeUserId();

        try {
            // Set the instance masquerade user ID globally
            Config::asUser($this->instanceMasqueradeUserId);

            // Execute the operation
            return $operation();
        } finally {
            // Restore the original masquerade user ID
            if ($originalMasqueradeUserId !== null) {
                Config::asUser($originalMasqueradeUserId);
            } else {
                Config::stopMasquerading();
            }
        }
    }

    /**
     * Apply temporary masquerading for static methods.
     *
     * This method checks for a temporary masquerade user ID set by asUser()
     * and applies it for the duration of the operation, then clears it.
     *
     * @param callable $operation The operation to perform with masquerading
     * @return mixed The result of the operation
     */
    protected static function withTemporaryMasquerading(callable $operation): mixed
    {
        if (static::$tempMasqueradeUserId === null) {
            // No temporary masquerading, just execute the operation
            return $operation();
        }

        // Store the current global masquerade user ID
        $originalMasqueradeUserId = Config::getMasqueradeUserId();
        $tempUserId = static::$tempMasqueradeUserId;

        try {
            // Clear the temporary value immediately to prevent reuse
            static::$tempMasqueradeUserId = null;

            // Set the temporary masquerade user ID globally
            Config::asUser($tempUserId);

            // Execute the operation
            return $operation();
        } finally {
            // Restore the original masquerade user ID
            if ($originalMasqueradeUserId !== null) {
                Config::asUser($originalMasqueradeUserId);
            } else {
                Config::stopMasquerading();
            }
        }
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
     * Get first page of results
     * @param array<string, mixed> $params Query parameters
     * @return array<static>
     */
    public static function get(array $params = []): array
    {
        static::checkApiClient();
        $endpoint = static::getEndpoint();
        $response = self::$apiClient->get($endpoint, ['query' => $params]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (!is_array($data)) {
            return [];
        }

        return array_map(fn($item) => new static($item), $data);
    }

    /**
     * Get all pages of results
     * @param array<string, mixed> $params Query parameters
     * @return array<static>
     */
    public static function all(array $params = []): array
    {
        static::checkApiClient();
        $allData = [];
        $endpoint = static::getEndpoint();
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        do {
            $data = $paginatedResponse->getJsonData();
            foreach ($data as $item) {
                $allData[] = new static($item);
            }
            $paginatedResponse = $paginatedResponse->getNext();
        } while ($paginatedResponse !== null);

        return $allData;
    }

    /**
     * Get paginated results with metadata
     * @param array<string, mixed> $params Query parameters
     * @return PaginationResult
     */
    public static function paginate(array $params = []): PaginationResult
    {
        static::checkApiClient();
        $endpoint = static::getEndpoint();
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        $data = array_map(fn($item) => new static($item), $paginatedResponse->getJsonData());
        return $paginatedResponse->toPaginationResult($data);
    }

    /**
     * Get the API endpoint for this resource
     * Subclasses must implement this to provide their specific endpoint
     * @return string
     */
    abstract protected static function getEndpoint(): string;

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
