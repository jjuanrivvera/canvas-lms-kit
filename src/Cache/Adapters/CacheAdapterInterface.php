<?php

declare(strict_types=1);

namespace CanvasLMS\Cache\Adapters;

/**
 * Interface for cache adapters in the Canvas LMS SDK.
 *
 * All cache adapters must implement this interface to ensure
 * consistent behavior across different cache backends.
 */
interface CacheAdapterInterface
{
    /**
     * Retrieve an item from the cache.
     *
     * @param string $key The cache key
     *
     * @return array<string, mixed>|null The cached data or null if not found/expired
     */
    public function get(string $key): ?array;

    /**
     * Store an item in the cache.
     *
     * @param string               $key  The cache key
     * @param array<string, mixed> $data The data to cache
     * @param int                  $ttl  Time to live in seconds (0 = infinite)
     *
     * @return void
     */
    public function set(string $key, array $data, int $ttl = 0): void;

    /**
     * Delete an item from the cache.
     *
     * @param string $key The cache key
     *
     * @return bool True if deleted, false if not found
     */
    public function delete(string $key): bool;

    /**
     * Clear all items from the cache.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Check if an item exists in the cache.
     *
     * @param string $key The cache key
     *
     * @return bool True if the item exists and is not expired
     */
    public function has(string $key): bool;

    /**
     * Delete items matching a pattern.
     *
     * @param string $pattern The pattern to match (supports * wildcards)
     *
     * @return int The number of items deleted
     */
    public function deleteByPattern(string $pattern): int;

    /**
     * Get cache statistics.
     *
     * @return array{hits: int, misses: int, size: int, entries: int}
     */
    public function getStats(): array;
}
