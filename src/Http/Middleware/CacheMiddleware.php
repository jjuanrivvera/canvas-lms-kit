<?php

declare(strict_types=1);

namespace CanvasLMS\Http\Middleware;

use CanvasLMS\Cache\Adapters\CacheAdapterInterface;
use CanvasLMS\Cache\Adapters\InMemoryAdapter;
use CanvasLMS\Cache\ResponseSerializer;
use CanvasLMS\Cache\Strategies\CacheKeyGenerator;
use CanvasLMS\Cache\Strategies\TtlStrategy;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware for caching Canvas API responses.
 *
 * Caches successful GET requests to reduce API calls and improve performance.
 * Supports multiple cache backends and configurable TTL strategies.
 */
class CacheMiddleware extends AbstractMiddleware
{
    /**
     * @var CacheAdapterInterface The cache adapter
     */
    private CacheAdapterInterface $adapter;

    /**
     * @var CacheKeyGenerator The cache key generator
     */
    private CacheKeyGenerator $keyGenerator;

    /**
     * @var TtlStrategy The TTL strategy
     */
    private TtlStrategy $ttlStrategy;

    /**
     * @var ResponseSerializer The response serializer
     */
    private ResponseSerializer $serializer;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $config Configuration options
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->adapter = $this->getConfig('adapter') ?? new InMemoryAdapter();
        $this->keyGenerator = $this->getConfig('key_generator') ?? new CacheKeyGenerator();
        $this->ttlStrategy = $this->getConfig('ttl_strategy') ?? new TtlStrategy();
        $this->serializer = $this->getConfig('serializer') ?? new ResponseSerializer();
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'cache';
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => false,  // Opt-in by default for backward compatibility
            'default_ttl' => 300,  // 5 minutes default
            'cache_get_only' => true,  // Only cache GET requests
            'cache_success_only' => true,  // Only cache successful responses
            'invalidate_on_mutation' => true,  // Invalidate cache on POST/PUT/DELETE
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(): callable
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                // Check if caching is enabled
                if (!$this->isCachingEnabled($options)) {
                    return $handler($request, $options);
                }

                // Only cache GET requests
                if ($this->getConfig('cache_get_only') && $request->getMethod() !== 'GET') {
                    // Handle cache invalidation for mutations
                    if ($this->getConfig('invalidate_on_mutation')) {
                        $this->invalidateOnMutation($request);
                    }

                    return $handler($request, $options);
                }

                // Check for cache refresh request
                if ($this->shouldRefreshCache($options)) {
                    return $this->executeAndCache($request, $handler, $options);
                }

                // Generate cache key
                $cacheKey = $this->keyGenerator->generate($request, $options);

                // Check cache
                $cachedData = $this->adapter->get($cacheKey);
                if ($cachedData !== null) {
                    // Cache hit - return cached response
                    $response = $this->serializer->deserialize($cachedData);
                    if ($response !== null) {
                        return Create::promiseFor($response);
                    }
                    // If deserialization failed, treat as cache miss
                }

                // Cache miss - execute request and cache response
                return $this->executeAndCache($request, $handler, $options, $cacheKey);
            };
        };
    }

    /**
     * Execute the request and cache the response.
     *
     * @param RequestInterface     $request   The request
     * @param callable             $handler   The handler
     * @param array<string, mixed> $options   The options
     * @param string|null          $cacheKey  The cache key (will be generated if not provided)
     *
     * @return PromiseInterface The response promise
     */
    private function executeAndCache(
        RequestInterface $request,
        callable $handler,
        array $options,
        ?string $cacheKey = null
    ): PromiseInterface {
        if ($cacheKey === null) {
            $cacheKey = $this->keyGenerator->generate($request, $options);
        }

        return $handler($request, $options)->then(
            function (ResponseInterface $response) use ($request, $options, $cacheKey) {
                // Only cache successful responses
                if ($this->shouldCacheResponse($response)) {
                    $ttl = $this->ttlStrategy->getTtl($request, $options);
                    if ($ttl > 0) {
                        $data = $this->serializer->serialize($response);
                        // Only cache if serialization succeeded
                        if (isset($data['cacheable']) && $data['cacheable']) {
                            $this->adapter->set($cacheKey, $data, $ttl);
                        }
                    }
                }

                return $response;
            }
        );
    }

    /**
     * Check if caching is enabled.
     *
     * @param array<string, mixed> $options Request options
     *
     * @return bool True if caching is enabled
     */
    private function isCachingEnabled(array $options): bool
    {
        // Check for explicit disable in options
        if (isset($options['cache']) && $options['cache'] === false) {
            return false;
        }

        // Check global enabled setting
        return (bool) $this->getConfig('enabled');
    }

    /**
     * Check if cache should be refreshed.
     *
     * @param array<string, mixed> $options Request options
     *
     * @return bool True if cache should be refreshed
     */
    private function shouldRefreshCache(array $options): bool
    {
        return isset($options['cache_refresh']) && $options['cache_refresh'] === true;
    }

    /**
     * Check if response should be cached.
     *
     * @param ResponseInterface $response The response
     *
     * @return bool True if response should be cached
     */
    private function shouldCacheResponse(ResponseInterface $response): bool
    {
        if (!$this->getConfig('cache_success_only')) {
            return true;
        }

        $statusCode = $response->getStatusCode();

        return $statusCode >= 200 && $statusCode < 300;
    }

    /**
     * Invalidate cache entries on mutation.
     *
     * @param RequestInterface $request The mutation request
     *
     * @return void
     */
    private function invalidateOnMutation(RequestInterface $request): void
    {
        $method = $request->getMethod();
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $path = $request->getUri()->getPath();
        $patterns = $this->getInvalidationPatterns($method, $path);

        foreach ($patterns as $pattern) {
            $this->adapter->deleteByPattern($pattern);
        }
    }

    /**
     * Get cache invalidation patterns for a mutation.
     *
     * @param string $method The HTTP method
     * @param string $path   The request path
     *
     * @return array<int, string> The invalidation patterns
     */
    private function getInvalidationPatterns(string $method, string $path): array
    {
        $patterns = [];

        // Extract resource type and ID from path
        if (preg_match('#/api/v1/(\w+)/(\d+)#', $path, $matches)) {
            $resourceType = $matches[1];
            $resourceId = $matches[2];

            // Invalidate list and specific resource
            $patterns[] = "*:GET:/api/v1/{$resourceType}*";
            $patterns[] = "*:GET:/api/v1/{$resourceType}/{$resourceId}*";

            // Handle specific resource relationships
            switch ($resourceType) {
                case 'courses':
                    // Invalidate related course data
                    $patterns[] = "*:GET:/api/v1/courses/{$resourceId}/*";
                    break;

                case 'users':
                    // Invalidate user-related data
                    $patterns[] = "*:GET:/api/v1/users/{$resourceId}/*";
                    break;

                case 'assignments':
                case 'modules':
                case 'pages':
                    // These might affect course data
                    if (preg_match('#/courses/(\d+)/#', $path, $courseMatch)) {
                        $courseId = $courseMatch[1];
                        $patterns[] = "*:GET:/api/v1/courses/{$courseId}/*";
                    }
                    break;
            }
        }

        return $patterns;
    }

    /**
     * Set the cache adapter.
     *
     * @param CacheAdapterInterface $adapter The cache adapter
     *
     * @return void
     */
    public function setAdapter(CacheAdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }

    /**
     * Get the cache adapter.
     *
     * @return CacheAdapterInterface The cache adapter
     */
    public function getAdapter(): CacheAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Get cache statistics.
     *
     * @return array{hits: int, misses: int, size: int, entries: int}
     */
    public function getStatistics(): array
    {
        return $this->adapter->getStats();
    }

    /**
     * Clear all cached entries.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->adapter->clear();
    }
}
