<?php

declare(strict_types=1);

namespace CanvasLMS\Cache\Adapters;

/**
 * APCu cache adapter for shared memory caching.
 *
 * Provides high-performance caching using APCu extension for
 * single-server environments. Requires the APCu PHP extension.
 */
class APCuAdapter implements CacheAdapterInterface
{
    /**
     * @var string Cache key prefix
     */
    private string $prefix;

    /**
     * @var array{hits: int, misses: int} Cache statistics
     */
    private array $stats = ['hits' => 0, 'misses' => 0];

    /**
     * @var bool Whether APCu is available
     */
    private bool $available;

    /**
     * Constructor.
     *
     * @param string $prefix Cache key prefix to avoid collisions
     *
     * @throws \RuntimeException If APCu extension is not available
     */
    public function __construct(string $prefix = 'canvas:')
    {
        $this->prefix = $prefix;
        $this->checkAvailability();
    }

    /**
     * Check if APCu is available and enabled.
     *
     * @throws \RuntimeException If APCu is not available
     *
     * @return void
     */
    private function checkAvailability(): void
    {
        if (!extension_loaded('apcu')) {
            throw new \RuntimeException('APCu extension is not installed');
        }

        if (!ini_get('apc.enabled')) {
            throw new \RuntimeException('APCu is installed but not enabled');
        }

        // Check for CLI mode
        if (PHP_SAPI === 'cli' && !ini_get('apc.enable_cli')) {
            throw new \RuntimeException('APCu is not enabled for CLI mode');
        }

        $this->available = true;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        if (!$this->available) {
            $this->stats['misses']++;

            return null;
        }

        $prefixedKey = $this->prefix . $key;
        $success = false;
        $data = apcu_fetch($prefixedKey, $success);

        if (!$success) {
            $this->stats['misses']++;

            return null;
        }

        // Validate data is an array
        if (!is_array($data)) {
            apcu_delete($prefixedKey);
            $this->stats['misses']++;

            return null;
        }

        $this->stats['hits']++;

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $data
     */
    public function set(string $key, array $data, int $ttl = 0): void
    {
        if (!$this->available) {
            return;
        }

        $prefixedKey = $this->prefix . $key;

        // APCu handles TTL internally
        $success = apcu_store($prefixedKey, $data, $ttl);

        if (!$success) {
            // Try to clear some space if storage failed
            $this->clearOldEntries();

            // Retry once
            $success = apcu_store($prefixedKey, $data, $ttl);

            if (!$success) {
                throw new \RuntimeException("Failed to store data in APCu cache for key: $key");
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        if (!$this->available) {
            return false;
        }

        $prefixedKey = $this->prefix . $key;

        return apcu_delete($prefixedKey);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        if (!$this->available) {
            return;
        }

        // Clear only entries with our prefix
        $iterator = new \APCUIterator('/^' . preg_quote($this->prefix, '/') . '/');
        apcu_delete($iterator);

        $this->stats = ['hits' => 0, 'misses' => 0];
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        if (!$this->available) {
            return false;
        }

        $prefixedKey = $this->prefix . $key;

        return apcu_exists($prefixedKey);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByPattern(string $pattern): int
    {
        if (!$this->available) {
            return 0;
        }

        $deleted = 0;
        $regex = $this->patternToRegex($pattern);

        // Create full pattern with prefix
        $fullPattern = '/^' . preg_quote($this->prefix, '/') . '(.*)$/';
        $iterator = new \APCUIterator($fullPattern);

        foreach ($iterator as $item) {
            // Extract the key without prefix
            $key = substr($item['key'], strlen($this->prefix));

            if (preg_match($regex, $key)) {
                if (apcu_delete($item['key'])) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        if (!$this->available) {
            return [
                'hits' => 0,
                'misses' => 0,
                'size' => 0,
                'entries' => 0,
            ];
        }

        $info = apcu_cache_info();
        $smaInfo = apcu_sma_info();

        // Count only our entries
        $entries = 0;
        $size = 0;

        if (isset($info['cache_list'])) {
            foreach ($info['cache_list'] as $entry) {
                if (str_starts_with($entry['key'], $this->prefix)) {
                    $entries++;
                    $size += $entry['mem_size'];
                }
            }
        }

        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'size' => $size,
            'entries' => $entries,
            'memory_available' => $smaInfo['avail_mem'] ?? 0,
            'memory_used' => ($smaInfo['seg_size'] ?? 0) - ($smaInfo['avail_mem'] ?? 0),
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
     * Clear old entries to free up space.
     *
     * This method removes entries that haven't been accessed recently.
     *
     * @return int Number of entries cleared
     */
    private function clearOldEntries(): int
    {
        if (!$this->available) {
            return 0;
        }

        $cleared = 0;
        $now = time();
        $maxAge = 3600; // Clear entries not accessed in the last hour

        $iterator = new \APCUIterator('/^' . preg_quote($this->prefix, '/') . '/');

        foreach ($iterator as $item) {
            // Check last access time
            if (isset($item['access_time']) && ($now - $item['access_time']) > $maxAge) {
                if (apcu_delete($item['key'])) {
                    $cleared++;
                }
            }
        }

        return $cleared;
    }

    /**
     * Get APCu cache information.
     *
     * @return array<string, mixed> Detailed cache information
     */
    public function getInfo(): array
    {
        if (!$this->available) {
            return ['available' => false];
        }

        $info = apcu_cache_info();
        $smaInfo = apcu_sma_info();

        return [
            'available' => true,
            'version' => phpversion('apcu'),
            'num_slots' => $info['num_slots'] ?? 0,
            'ttl' => $info['ttl'] ?? 0,
            'num_hits' => $info['num_hits'] ?? 0,
            'num_misses' => $info['num_misses'] ?? 0,
            'num_inserts' => $info['num_inserts'] ?? 0,
            'num_entries' => $info['num_entries'] ?? 0,
            'expunges' => $info['expunges'] ?? 0,
            'start_time' => $info['start_time'] ?? 0,
            'mem_size' => $info['mem_size'] ?? 0,
            'memory_type' => $info['memory_type'] ?? 'unknown',
            'sma_avail_mem' => $smaInfo['avail_mem'] ?? 0,
            'sma_num_seg' => $smaInfo['num_seg'] ?? 0,
            'sma_seg_size' => $smaInfo['seg_size'] ?? 0,
        ];
    }
}
