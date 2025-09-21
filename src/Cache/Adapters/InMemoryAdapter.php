<?php

declare(strict_types=1);

namespace CanvasLMS\Cache\Adapters;

/**
 * In-memory cache adapter for development and testing.
 *
 * This adapter stores cache entries in a PHP array that exists
 * only for the duration of the request. It's the default adapter
 * and provides a no-op caching behavior without persistence.
 */
class InMemoryAdapter implements CacheAdapterInterface
{
    /**
     * @var array<string, array{data: array<string, mixed>, expires: int}> Cache storage
     */
    private array $cache = [];

    /**
     * @var array{hits: int, misses: int} Cache statistics
     */
    private array $stats = ['hits' => 0, 'misses' => 0];

    /**
     * @var int Maximum number of cache entries (0 = unlimited)
     */
    private int $maxEntries;

    /**
     * Constructor.
     *
     * @param int $maxEntries Maximum number of cache entries (0 = unlimited)
     */
    public function __construct(int $maxEntries = 1000)
    {
        $this->maxEntries = $maxEntries;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        if (!isset($this->cache[$key])) {
            $this->stats['misses']++;

            return null;
        }

        $entry = $this->cache[$key];

        // Check expiration
        if ($entry['expires'] > 0 && $entry['expires'] < time()) {
            unset($this->cache[$key]);
            $this->stats['misses']++;

            return null;
        }

        $this->stats['hits']++;

        return $entry['data'];
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $data
     */
    public function set(string $key, array $data, int $ttl = 0): void
    {
        // Enforce max entries limit
        if ($this->maxEntries > 0 && count($this->cache) >= $this->maxEntries) {
            // Remove oldest entry (FIFO)
            $this->evictOldest();
        }

        $expires = $ttl > 0 ? time() + $ttl : 0;

        $this->cache[$key] = [
            'data' => $data,
            'expires' => $expires,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->cache = [];
        $this->stats = ['hits' => 0, 'misses' => 0];
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $entry = $this->cache[$key];

        // Check expiration
        if ($entry['expires'] > 0 && $entry['expires'] < time()) {
            unset($this->cache[$key]);

            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByPattern(string $pattern): int
    {
        $deleted = 0;
        $regex = $this->patternToRegex($pattern);

        foreach (array_keys($this->cache) as $key) {
            if (preg_match($regex, $key)) {
                unset($this->cache[$key]);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        $this->cleanExpired();

        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'size' => memory_get_usage(),
            'entries' => count($this->cache),
        ];
    }

    /**
     * Convert a pattern with wildcards to a regex.
     *
     * @param string $pattern The pattern with * wildcards
     *
     * @return string The regex pattern
     */
    private function patternToRegex(string $pattern): string
    {
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('\\*', '.*', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Remove expired entries from the cache.
     *
     * @return void
     */
    private function cleanExpired(): void
    {
        $now = time();

        foreach ($this->cache as $key => $entry) {
            if ($entry['expires'] > 0 && $entry['expires'] < $now) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * Evict the oldest cache entry.
     *
     * @return void
     */
    private function evictOldest(): void
    {
        if (!empty($this->cache)) {
            reset($this->cache);
            $oldestKey = key($this->cache);
            if ($oldestKey !== null) {
                unset($this->cache[$oldestKey]);
            }
        }
    }
}
