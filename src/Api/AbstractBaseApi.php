<?php

declare(strict_types=1);

namespace CanvasLMS\Api;

use CanvasLMS\Http\ApiClientRegistry;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Interfaces\ApiInterface;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use DateTime;
use Exception;
use InvalidArgumentException;

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
     * Define method aliases
     *
     * @var array<string, string[]>
     */
    protected static array $methodAliases = [
        'get' => ['fetch', 'list'],
        'all' => ['fetchAllPages', 'getAll', 'fetchAll'],
        'paginate' => ['getPaginated', 'withPagination'],
        'find' => ['one', 'getOne'],
    ];

    /**
     * BaseApi constructor.
     *
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $key) && !is_null($value)) {
                // Handle type conversion for strict_types compatibility
                $reflection = new \ReflectionClass($this);
                if ($reflection->hasProperty($key)) {
                    $property = $reflection->getProperty($key);
                    $type = $property->getType();

                    if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
                        switch ($type->getName()) {
                            case 'int':
                                $value = is_numeric($value) ? (int) $value : null;
                                break;
                            case 'float':
                                $value = is_numeric($value) ? (float) $value : null;
                                break;
                            case 'string':
                                $value = is_scalar($value) ? (string) $value : null;
                                break;
                            case 'bool':
                                $value = (bool) $value;
                                break;
                        }
                    } else {
                        // For non-builtin types (like DateTime), use castValue()
                        $value = $this->castValue($key, $value);
                    }
                }

                $this->{$key} = $value;
            }
        }
    }

    /**
     * Set the shared default API client used by ALL resource classes.
     *
     * Calling this on any resource (e.g. Course::setApiClient()) replaces
     * the client for every resource, because relationship methods cross
     * class boundaries ($course->enrollments() calls Enrollment internally).
     * Use overrideApiClient() to scope a client to a single class, and
     * resetApiClients() in test teardown to avoid state leaking.
     *
     * @param HttpClientInterface $apiClient
     *
     * @return void
     */
    public static function setApiClient(HttpClientInterface $apiClient): void
    {
        ApiClientRegistry::setDefault($apiClient);
    }

    /**
     * Set an API client for this class only, leaving other resources on
     * the shared default.
     *
     * @param HttpClientInterface $apiClient
     *
     * @return void
     */
    public static function overrideApiClient(HttpClientInterface $apiClient): void
    {
        ApiClientRegistry::setFor(static::class, $apiClient);
    }

    /**
     * Clear the shared default client and all per-class overrides.
     *
     * @return void
     */
    public static function resetApiClients(): void
    {
        ApiClientRegistry::reset();
    }

    /**
     * Check if the API client is set, if not, instantiate a new one
     *
     * @return void
     */
    protected static function checkApiClient(): void
    {
        static::getApiClient();
    }

    /**
     * Get the API client, initializing if necessary
     *
     * @return HttpClientInterface
     */
    protected static function getApiClient(): HttpClientInterface
    {
        return ApiClientRegistry::resolve(static::class);
    }

    /**
     * Create an HttpClient with configured middleware
     *
     * @return HttpClient
     */
    protected static function createConfiguredHttpClient(): HttpClient
    {
        return ApiClientRegistry::createConfiguredHttpClient();
    }

    /**
     * Convert the object to an array
     *
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
     *
     * @param mixed[] $data
     *
     * @throws Exception
     *
     * @return void
     */
    protected function populate(array $data): void
    {
        foreach ($data as $key => $value) {
            // Convert snake_case keys to camelCase to match property names
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $key) && !is_null($value)) {
                $this->{$key} = $this->castValue($key, $value);
            }
        }
    }

    /**
     * Cast a value to the correct type
     *
     * @param string $key
     * @param mixed $value
     *
     * @throws Exception
     *
     * @return DateTime|mixed
     */
    protected function castValue(string $key, mixed $value): mixed
    {
        // List of common date fields in Canvas API
        $dateFields = [
            'startAt',
            'endAt',
            'startedAt',
            'endedAt',
            'finishedAt',
            'createdAt',
            'updatedAt',
            'deletedAt',
            'modifiedAt',
            'editedAt',
            'lastEditedAt',
            'publishedAt',
            'postedAt',
            'dueAt',
            'lockAt',
            'unlockAt',
            'submittedAt',
            'gradedAt',
            'delayedPostAt',
            'lastReplyAt',
            'lastMessageAt',
            'lastActivityAt',
            'lastUsedAt',
            'lastLogin',
            'submittedOrAssessedAt',
            'attemptedAt',
            'assessedAt',
            'peerReviewsAssignAt',
            'cachedDueDate',
            'expiresAt',
            'activatedAt',
            'archivedAt',
        ];

        if (in_array($key, $dateFields, true) && is_string($value) && !empty($value)) {
            try {
                return new DateTime($value);
            } catch (\Exception $e) {
                // Return null for invalid date strings
                return null;
            }
        }

        return $value;
    }

    /**
     * Helper method to get paginated response from API endpoint
     *
     * @param string $endpoint The API endpoint path
     * @param mixed[] $params Query parameters for the request
     *
     * @return PaginatedResponse
     */
    protected static function getPaginatedResponse(string $endpoint, array $params = []): PaginatedResponse
    {
        self::checkApiClient();

        return self::getApiClient()->getPaginated($endpoint, [
            'query' => $params,
        ]);
    }

    /**
     * Helper method to convert paginated response data to model instances
     *
     * @param PaginatedResponse $paginatedResponse
     *
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
     * Helper method to create PaginationResult from paginated response
     *
     * @param PaginatedResponse $paginatedResponse
     *
     * @return PaginationResult
     */
    protected static function createPaginationResult(PaginatedResponse $paginatedResponse): PaginationResult
    {
        $models = self::convertPaginatedResponseToModels($paginatedResponse);

        return $paginatedResponse->toPaginationResult($models);
    }

    /**
     * Parse JSON response from API safely handling StreamInterface
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array<mixed>
     */
    protected static function parseJsonResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Get first page of results
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @return array<static>
     */
    public static function get(array $params = []): array
    {
        static::checkApiClient();
        $endpoint = static::getEndpoint();
        $response = self::getApiClient()->get($endpoint, ['query' => $params]);

        $data = self::parseJsonResponse($response);

        return array_map(fn ($item) => new static($item), $data);
    }

    /**
     * Get all pages of results
     *
     * @param array<string, mixed> $params Query parameters
     *
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
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @return PaginationResult
     */
    public static function paginate(array $params = []): PaginationResult
    {
        static::checkApiClient();
        $endpoint = static::getEndpoint();
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        $data = array_map(fn ($item) => new static($item), $paginatedResponse->getJsonData());

        return $paginatedResponse->toPaginationResult($data);
    }

    /**
     * Get the API endpoint for this resource
     * Subclasses must implement this to provide their specific endpoint
     *
     * @return string
     */
    abstract protected static function getEndpoint(): string;

    /**
     * Magic method to handle function aliases
     *
     * @param string $name
     * @param mixed[] $arguments
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        foreach (static::$methodAliases as $method => $aliases) {
            if (is_array($aliases) && in_array($name, $aliases, true)) {
                return static::{$method}(...$arguments);
            }
        }

        throw new InvalidArgumentException("Method $name does not exist");
    }
}
