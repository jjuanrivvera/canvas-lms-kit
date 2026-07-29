<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Cache\Adapters;

use CanvasLMS\Cache\Adapters\FileSystemAdapter;
use PHPUnit\Framework\TestCase;

class FileSystemAdapterTest extends TestCase
{
    private string $tempDir;

    private FileSystemAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/canvas-cache-test-' . uniqid('', true);
        $this->adapter = new FileSystemAdapter($this->tempDir, false);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->tempDir);
    }

    // -----------------------------------------------------------------------
    // Basic operations
    // -----------------------------------------------------------------------

    public function testSetAndGetRoundTrip(): void
    {
        $key = 'test-key';
        $data = ['status' => 200, 'body' => 'hello world', 'headers' => ['Content-Type' => ['application/json']]];

        $this->adapter->set($key, $data);
        $result = $this->adapter->get($key);

        $this->assertSame($data, $result);
    }

    public function testGetReturnNullForMissingKey(): void
    {
        $result = $this->adapter->get('does-not-exist');

        $this->assertNull($result);
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->adapter->set('exists', ['v' => 1]);

        $this->assertTrue($this->adapter->has('exists'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->adapter->has('missing-key'));
    }

    public function testDeleteRemovesExistingKey(): void
    {
        $this->adapter->set('to-delete', ['v' => 1]);
        $this->assertTrue($this->adapter->has('to-delete'));

        $result = $this->adapter->delete('to-delete');

        $this->assertTrue($result);
        $this->assertFalse($this->adapter->has('to-delete'));
    }

    public function testDeleteReturnsFalseForNonExistentKey(): void
    {
        $result = $this->adapter->delete('ghost-key');

        $this->assertFalse($result);
    }

    public function testClearRemovesAllEntries(): void
    {
        $this->adapter->set('k1', ['a' => 1]);
        $this->adapter->set('k2', ['b' => 2]);
        $this->adapter->set('k3', ['c' => 3]);

        $this->adapter->clear();

        $this->assertFalse($this->adapter->has('k1'));
        $this->assertFalse($this->adapter->has('k2'));
        $this->assertFalse($this->adapter->has('k3'));
    }

    // -----------------------------------------------------------------------
    // TTL behaviour
    // -----------------------------------------------------------------------

    public function testZeroTtlMeansNoExpiry(): void
    {
        $this->adapter->set('permanent', ['value' => 'stays'], 0);

        $result = $this->adapter->get('permanent');

        $this->assertSame(['value' => 'stays'], $result);
    }

    public function testExpiredEntryReturnsNull(): void
    {
        // Write a cache file whose envelope already shows a past expiry time
        // without relying on sleep().
        $key = 'already-expired';
        $this->adapter->set($key, ['v' => 'old'], 3600);

        // Locate the file and overwrite it with an expired envelope
        $hash = md5($key);
        $subdir = substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
        $filepath = $this->tempDir . '/' . $subdir . '/' . $hash . '.cache';

        $expiredEnvelope = [
            'key' => $key,
            'value' => ['v' => 'old'],
            'expires' => time() - 1,  // one second in the past
            'created' => time() - 10,
        ];

        file_put_contents($filepath, serialize($expiredEnvelope));

        $result = $this->adapter->get($key);

        $this->assertNull($result);
    }

    public function testTtlExpirySleepBased(): void
    {
        // Only use sleep as a last resort — kept isolated in its own test.
        $key = 'sleep-expiry';
        $this->adapter->set($key, ['data' => 'temp'], 1);

        $this->assertNotNull($this->adapter->get($key), 'Entry should exist before expiry');

        sleep(2);

        $this->assertNull($this->adapter->get($key));
        $this->assertFalse($this->adapter->has($key));
    }

    // -----------------------------------------------------------------------
    // Compression
    // -----------------------------------------------------------------------

    public function testCompressionEnabledRoundTrip(): void
    {
        $compressedDir = $this->tempDir . '-compressed-' . uniqid('', true);
        $adapter = new FileSystemAdapter($compressedDir, true);

        try {
            $data = ['body' => str_repeat('compress me', 100)];
            $adapter->set('comp-key', $data);
            $result = $adapter->get('comp-key');

            $this->assertSame($data, $result);
        } finally {
            $this->removeDirectory($compressedDir);
        }
    }

    public function testCompressionDisabledRoundTrip(): void
    {
        $data = ['body' => str_repeat('no-compress', 100)];
        $this->adapter->set('plain-key', $data);
        $result = $this->adapter->get('plain-key');

        $this->assertSame($data, $result);
    }

    // -----------------------------------------------------------------------
    // getStats
    // -----------------------------------------------------------------------

    public function testGetStatsReflectsEntryCount(): void
    {
        $this->adapter->set('s1', ['x' => 1]);
        $this->adapter->set('s2', ['x' => 2]);

        $stats = $this->adapter->getStats();

        $this->assertArrayHasKey('entries', $stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('size', $stats);
        $this->assertSame(2, $stats['entries']);
    }

    public function testGetStatsTracksHitsAndMisses(): void
    {
        $this->adapter->set('hit-key', ['data' => 'x']);

        $this->adapter->get('hit-key');    // hit
        $this->adapter->get('miss-key');   // miss

        $stats = $this->adapter->getStats();

        $this->assertSame(1, $stats['hits']);
        $this->assertSame(1, $stats['misses']);
    }

    // -----------------------------------------------------------------------
    // deleteByPattern — key stored in envelope, not filename
    // -----------------------------------------------------------------------

    public function testDeleteByPatternMatchesOriginalKeys(): void
    {
        $this->adapter->set('abc:GET:/api/v1/courses', ['data' => 'courses']);
        $this->adapter->set('abc:GET:/api/v1/users', ['data' => 'users']);

        $deleted = $this->adapter->deleteByPattern('*GET:/api/v1/courses*');

        $this->assertSame(1, $deleted);
        $this->assertNull($this->adapter->get('abc:GET:/api/v1/courses'));
        $this->assertNotNull($this->adapter->get('abc:GET:/api/v1/users'));
    }

    public function testDeleteByPatternDeletesNothingWhenNoMatch(): void
    {
        $this->adapter->set('abc:GET:/api/v1/courses', ['d' => 1]);

        $deleted = $this->adapter->deleteByPattern('*PATCH:/api/v1/no-match*');

        $this->assertSame(0, $deleted);
        $this->assertNotNull($this->adapter->get('abc:GET:/api/v1/courses'));
    }

    public function testDeleteByPatternWithWildcardDeletesMultiple(): void
    {
        $this->adapter->set('prefix:GET:/api/v1/courses', ['d' => 1]);
        $this->adapter->set('prefix:GET:/api/v1/courses/123', ['d' => 2]);
        $this->adapter->set('prefix:GET:/api/v1/users', ['d' => 3]);

        $deleted = $this->adapter->deleteByPattern('*:GET:/api/v1/courses*');

        $this->assertSame(2, $deleted);
        $this->assertNull($this->adapter->get('prefix:GET:/api/v1/courses'));
        $this->assertNull($this->adapter->get('prefix:GET:/api/v1/courses/123'));
        $this->assertNotNull($this->adapter->get('prefix:GET:/api/v1/users'));
    }

    // -----------------------------------------------------------------------
    // Security: unserialize hardening
    // -----------------------------------------------------------------------

    public function testGetReturnsNullForCorruptedCacheFile(): void
    {
        // Write a cache file containing completely invalid serialized data.
        // The adapter must return null rather than crash on corrupt content.
        $key = 'corrupt-cache';
        $hash = md5($key);
        $subdir = substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
        $dir = $this->tempDir . '/' . $subdir;

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $filepath = $dir . '/' . $hash . '.cache';
        // Garbage bytes that are not valid serialized PHP
        file_put_contents($filepath, 'NOT_VALID_SERIALIZED_DATA!!!!!!');

        $result = $this->adapter->get($key);

        $this->assertNull($result);
    }

    public function testGetReturnsNullWhenEnvelopeIsNotArray(): void
    {
        // Write a cache file whose serialized content is a scalar, not an array.
        // The adapter guards `!is_array($data)` and must return null.
        $key = 'scalar-envelope';
        $hash = md5($key);
        $subdir = substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
        $dir = $this->tempDir . '/' . $subdir;

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $filepath = $dir . '/' . $hash . '.cache';
        // Serialize a plain string — valid PHP serialization but not the expected array envelope
        file_put_contents($filepath, serialize('just-a-string'));

        $result = $this->adapter->get($key);

        $this->assertNull($result);
    }

    // -----------------------------------------------------------------------
    // File permissions (POSIX only)
    // -----------------------------------------------------------------------

    public function testDefaultFilePermissionsAreOwnerOnly(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('File permission check not applicable on Windows');
        }

        $this->adapter->set('perm-key', ['v' => 1]);

        $hash = md5('perm-key');
        $subdir = substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
        $filepath = $this->tempDir . '/' . $subdir . '/' . $hash . '.cache';

        $perms = fileperms($filepath) & 0777;

        $this->assertSame(0600, $perms, sprintf('Expected 0600, got %04o', $perms));
    }

    // -----------------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------------

    private function removeDirectory(string $dir): void
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
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}
