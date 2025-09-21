<?php

declare(strict_types=1);

namespace CanvasLMS\Cache\Adapters;

/**
 * File system cache adapter for persistent storage.
 *
 * Stores cache entries as files on disk with automatic cleanup
 * of expired entries.
 */
class FileSystemAdapter implements CacheAdapterInterface
{
    /**
     * @var string Cache directory path
     */
    private string $cacheDir;

    /**
     * @var int Directory permissions
     */
    private int $dirPermissions;

    /**
     * @var int File permissions
     */
    private int $filePermissions;

    /**
     * @var array{hits: int, misses: int} Cache statistics
     */
    private array $stats = ['hits' => 0, 'misses' => 0];

    /**
     * @var bool Enable compression
     */
    private bool $compression;

    /**
     * Constructor.
     *
     * @param string $cacheDir        Cache directory path
     * @param bool   $compression     Enable gzip compression
     * @param int    $dirPermissions  Directory permissions (octal)
     * @param int    $filePermissions File permissions (octal)
     */
    public function __construct(
        string $cacheDir = '/tmp/canvas-cache',
        bool $compression = true,
        int $dirPermissions = 0755,
        int $filePermissions = 0644
    ) {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->compression = $compression && function_exists('gzencode') && function_exists('gzdecode');
        $this->dirPermissions = $dirPermissions;
        $this->filePermissions = $filePermissions;

        $this->ensureCacheDirectoryExists();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        $filepath = $this->getFilePath($key);

        if (!file_exists($filepath)) {
            $this->stats['misses']++;

            return null;
        }

        $content = @file_get_contents($filepath);
        if ($content === false) {
            $this->stats['misses']++;

            return null;
        }

        // Decompress if needed
        if ($this->compression) {
            $content = @gzdecode($content);
            if ($content === false) {
                @unlink($filepath);
                $this->stats['misses']++;

                return null;
            }
        }

        $data = @unserialize($content);
        if ($data === false || !is_array($data)) {
            @unlink($filepath);
            $this->stats['misses']++;

            return null;
        }

        // Check expiration
        if (isset($data['expires']) && $data['expires'] > 0 && $data['expires'] < time()) {
            @unlink($filepath);
            $this->stats['misses']++;

            return null;
        }

        $this->stats['hits']++;

        return $data['value'] ?? null;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $data
     */
    public function set(string $key, array $data, int $ttl = 0): void
    {
        $filepath = $this->getFilePath($key);
        $dir = dirname($filepath);

        // Ensure subdirectory exists
        if (!is_dir($dir)) {
            if (!@mkdir($dir, $this->dirPermissions, true) && !is_dir($dir)) {
                throw new \RuntimeException("Failed to create cache directory: $dir");
            }
        }

        $expires = $ttl > 0 ? time() + $ttl : 0;

        $cacheData = [
            'value' => $data,
            'expires' => $expires,
            'created' => time(),
        ];

        $content = serialize($cacheData);

        // Compress if enabled
        if ($this->compression) {
            $content = gzencode($content, 6);
        }

        // Write atomically using temp file
        $tempFile = $filepath . '.tmp.' . uniqid('', true);

        if (@file_put_contents($tempFile, $content, LOCK_EX) === false) {
            @unlink($tempFile);

            throw new \RuntimeException("Failed to write cache file: $tempFile");
        }

        @chmod($tempFile, $this->filePermissions);

        // Atomic rename
        if (!@rename($tempFile, $filepath)) {
            @unlink($tempFile);

            throw new \RuntimeException("Failed to rename cache file: $tempFile to $filepath");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $filepath = $this->getFilePath($key);

        if (file_exists($filepath)) {
            return @unlink($filepath);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->deleteDirectory($this->cacheDir, false);
        $this->stats = ['hits' => 0, 'misses' => 0];
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        $data = $this->get($key);

        return $data !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByPattern(string $pattern): int
    {
        $deleted = 0;
        $regex = $this->patternToRegex($pattern);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'cache') {
                continue;
            }

            // Extract key from filename
            $filename = $file->getBasename('.cache');
            if (preg_match($regex, $filename)) {
                if (@unlink($file->getPathname())) {
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
        $size = 0;
        $entries = 0;

        if (is_dir($this->cacheDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->cacheDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'cache') {
                    $size += $file->getSize();
                    $entries++;
                }
            }
        }

        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'size' => $size,
            'entries' => $entries,
        ];
    }

    /**
     * Get the file path for a cache key.
     *
     * @param string $key The cache key
     *
     * @return string The file path
     */
    private function getFilePath(string $key): string
    {
        // Hash the key to avoid filesystem issues
        $hash = md5($key);

        // Create subdirectories for better performance
        $subdir = substr($hash, 0, 2) . '/' . substr($hash, 2, 2);

        return $this->cacheDir . '/' . $subdir . '/' . $hash . '.cache';
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
     * Ensure the cache directory exists.
     *
     * @return void
     */
    private function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, $this->dirPermissions, true) && !is_dir($this->cacheDir)) {
                throw new \RuntimeException("Failed to create cache directory: {$this->cacheDir}");
            }
        }

        if (!is_writable($this->cacheDir)) {
            throw new \RuntimeException("Cache directory is not writable: {$this->cacheDir}");
        }
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $dir        The directory path
     * @param bool   $deleteRoot Whether to delete the root directory
     *
     * @return void
     */
    private function deleteDirectory(string $dir, bool $deleteRoot = true): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        if ($deleteRoot) {
            @rmdir($dir);
        }
    }

    /**
     * Clean up expired cache entries.
     *
     * @return int Number of entries cleaned
     */
    public function cleanExpired(): int
    {
        $cleaned = 0;
        $now = time();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'cache') {
                continue;
            }

            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            if ($this->compression) {
                $content = @gzdecode($content);
                if ($content === false) {
                    @unlink($file->getPathname());
                    $cleaned++;
                    continue;
                }
            }

            $data = @unserialize($content);
            if ($data === false || !is_array($data)) {
                @unlink($file->getPathname());
                $cleaned++;
                continue;
            }

            if (isset($data['expires']) && $data['expires'] > 0 && $data['expires'] < $now) {
                @unlink($file->getPathname());
                $cleaned++;
            }
        }

        return $cleaned;
    }
}
