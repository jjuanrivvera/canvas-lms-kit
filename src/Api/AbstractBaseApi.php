<?php

declare(strict_types=1);

namespace CanvasLMS\Api;

use CanvasLMS\Exceptions\CanvasApiException;
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
     * Cached property-to-builtin-type maps, keyed by class name.
     *
     * Hydrating large result sets previously created a ReflectionClass per
     * property per object; the map is now computed once per class.
     *
     * @var array<class-string, array<string, string|null>>
     */
    private static array $propertyTypeCache = [];

    /**
     * BaseApi constructor.
     *
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $this->hydrate($data);
    }

    /**
     * Get the builtin type name for each property of this class.
     *
     * @return array<string, string|null> Property name => builtin type name,
     *                                    or null for non-builtin/untyped properties
     */
    private static function getPropertyTypeMap(): array
    {
        $class = static::class;

        if (!isset(self::$propertyTypeCache[$class])) {
            $map = [];
            $reflection = new \ReflectionClass($class);

            foreach ($reflection->getProperties() as $property) {
                $type = $property->getType();
                $map[$property->getName()] = $type instanceof \ReflectionNamedType && $type->isBuiltin()
                    ? $type->getName()
                    : null;
            }

            self::$propertyTypeCache[$class] = $map;
        }

        return self::$propertyTypeCache[$class];
    }

    /**
     * Assign API response data to properties with type coercion.
     *
     * Shared by the constructor and populate() so objects keep the same
     * type guarantees after save()/update() round-trips as on creation.
     *
     * @param mixed[] $data
     *
     * @return void
     */
    protected function hydrate(array $data): void
    {
        $typeMap = self::getPropertyTypeMap();

        foreach ($data as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (!array_key_exists($key, $typeMap) || is_null($value)) {
                continue;
            }

            $builtinType = $typeMap[$key];

            if ($builtinType !== null) {
                // Coerce scalars for strict_types compatibility
                $value = match ($builtinType) {
                    'int' => is_numeric($value) ? (int) $value : null,
                    'float' => is_numeric($value) ? (float) $value : null,
                    'string' => is_scalar($value) ? (string) $value : null,
                    'bool' => (bool) $value,
                    default => $value,
                };
            } else {
                // For non-builtin types (like DateTime), use castValue()
                $value = $this->castValue($key, $value);
            }

            $this->{$key} = $value;
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
        $this->hydrate($data);
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
     * Stream all items across all pages one at a time.
     *
     * Unlike all(), only one page of raw data is held in memory at a time,
     * making this safe for very large datasets (e.g. tens of thousands of
     * enrollments):
     *
     * ```php
     * foreach (User::stream(['per_page' => 100]) as $user) {
     *     processUser($user);
     * }
     * ```
     *
     * @param mixed[] $params Query parameters for the request
     *
     * @throws CanvasApiException
     *
     * @return \Generator<int, static>
     */
    public static function stream(array $params = []): \Generator
    {
        static::checkApiClient();
        $paginatedResponse = self::getPaginatedResponse(static::getEndpoint(), $params);

        do {
            foreach ($paginatedResponse->getJsonData() as $item) {
                yield new static($item);
            }
            $paginatedResponse = $paginatedResponse->getNext();
        } while ($paginatedResponse !== null);
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
        $method = static::getAliasMap()[$name] ?? null;

        if ($method !== null) {
            return static::{$method}(...$arguments);
        }

        throw new InvalidArgumentException("Method $name does not exist");
    }

    /**
     * Build a flat alias-to-method lookup from $methodAliases.
     *
     * @return array<string, string>
     */
    protected static function getAliasMap(): array
    {
        static $maps = [];
        $class = static::class;

        if (!isset($maps[$class])) {
            $map = [];
            foreach (static::$methodAliases as $method => $aliases) {
                foreach ($aliases as $alias) {
                    $map[$alias] = $method;
                }
            }
            $maps[$class] = $map;
        }

        return $maps[$class];
    }
}
