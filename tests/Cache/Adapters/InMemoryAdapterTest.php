<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Cache\Adapters;

use CanvasLMS\Cache\Adapters\InMemoryAdapter;
use PHPUnit\Framework\TestCase;

class InMemoryAdapterTest extends TestCase
{
    private InMemoryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new InMemoryAdapter();
    }

    public function testGetReturnsNullForNonExistentKey(): void
    {
        $result = $this->adapter->get('non-existent-key');
        $this->assertNull($result);
    }

    public function testSetAndGet(): void
    {
        $key = 'test-key';
        $data = ['status' => 200, 'body' => 'test content'];

        $this->adapter->set($key, $data);

        $retrieved = $this->adapter->get($key);
        $this->assertSame($data, $retrieved);
    }

    public function testHas(): void
    {
        $key = 'test-key';
        $data = ['test' => 'data'];

        $this->assertFalse($this->adapter->has($key));

        $this->adapter->set($key, $data);

        $this->assertTrue($this->adapter->has($key));
    }

    public function testDelete(): void
    {
        $key = 'test-key';
        $data = ['test' => 'data'];

        $this->adapter->set($key, $data);
        $this->assertTrue($this->adapter->has($key));

        $deleted = $this->adapter->delete($key);
        $this->assertTrue($deleted);
        $this->assertFalse($this->adapter->has($key));

        // Deleting non-existent key returns false
        $deleted = $this->adapter->delete('non-existent');
        $this->assertFalse($deleted);
    }

    public function testClear(): void
    {
        $this->adapter->set('key1', ['data1']);
        $this->adapter->set('key2', ['data2']);
        $this->adapter->set('key3', ['data3']);

        $this->assertTrue($this->adapter->has('key1'));
        $this->assertTrue($this->adapter->has('key2'));
        $this->assertTrue($this->adapter->has('key3'));

        $this->adapter->clear();

        $this->assertFalse($this->adapter->has('key1'));
        $this->assertFalse($this->adapter->has('key2'));
        $this->assertFalse($this->adapter->has('key3'));
    }

    public function testTtlExpiration(): void
    {
        $key = 'expiring-key';
        $data = ['test' => 'data'];

        // Set with 1 second TTL
        $this->adapter->set($key, $data, 1);
        $this->assertTrue($this->adapter->has($key));
        $this->assertSame($data, $this->adapter->get($key));

        // Wait for expiration
        sleep(2);

        $this->assertFalse($this->adapter->has($key));
        $this->assertNull($this->adapter->get($key));
    }

    public function testDeleteByPattern(): void
    {
        $this->adapter->set('user:1:profile', ['user1']);
        $this->adapter->set('user:2:profile', ['user2']);
        $this->adapter->set('user:3:settings', ['settings']);
        $this->adapter->set('course:1:data', ['course']);

        // Delete all user profile keys
        $deleted = $this->adapter->deleteByPattern('user:*:profile');
        $this->assertEquals(2, $deleted);

        $this->assertFalse($this->adapter->has('user:1:profile'));
        $this->assertFalse($this->adapter->has('user:2:profile'));
        $this->assertTrue($this->adapter->has('user:3:settings'));
        $this->assertTrue($this->adapter->has('course:1:data'));

        // Delete all user keys
        $deleted = $this->adapter->deleteByPattern('user:*');
        $this->assertEquals(1, $deleted);

        $this->assertFalse($this->adapter->has('user:3:settings'));
        $this->assertTrue($this->adapter->has('course:1:data'));
    }

    public function testGetStats(): void
    {
        $stats = $this->adapter->getStats();

        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('size', $stats);
        $this->assertArrayHasKey('entries', $stats);

        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
        $this->assertEquals(0, $stats['entries']);

        // Add some data and test stats
        $this->adapter->set('key1', ['data1']);
        $this->adapter->set('key2', ['data2']);

        // Cause a hit and a miss
        $this->adapter->get('key1'); // hit
        $this->adapter->get('non-existent'); // miss

        $stats = $this->adapter->getStats();
        $this->assertEquals(1, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(2, $stats['entries']);
    }

    public function testMaxEntriesEviction(): void
    {
        // Create adapter with max 3 entries
        $adapter = new InMemoryAdapter(3);

        $adapter->set('key1', ['data1']);
        $adapter->set('key2', ['data2']);
        $adapter->set('key3', ['data3']);

        $this->assertTrue($adapter->has('key1'));
        $this->assertTrue($adapter->has('key2'));
        $this->assertTrue($adapter->has('key3'));

        // Adding a 4th entry should evict the oldest (key1)
        $adapter->set('key4', ['data4']);

        $this->assertFalse($adapter->has('key1')); // Evicted
        $this->assertTrue($adapter->has('key2'));
        $this->assertTrue($adapter->has('key3'));
        $this->assertTrue($adapter->has('key4'));
    }

    public function testExpiredEntriesCleanup(): void
    {
        $this->adapter->set('permanent', ['data'], 0); // No expiry
        $this->adapter->set('expiring1', ['data'], 1); // 1 second
        $this->adapter->set('expiring2', ['data'], 1); // 1 second

        $stats = $this->adapter->getStats();
        $this->assertEquals(3, $stats['entries']);

        sleep(2);

        // Getting stats should clean expired entries
        $stats = $this->adapter->getStats();
        $this->assertEquals(1, $stats['entries']); // Only permanent remains

        $this->assertTrue($this->adapter->has('permanent'));
        $this->assertFalse($this->adapter->has('expiring1'));
        $this->assertFalse($this->adapter->has('expiring2'));
    }

    public function testComplexPatternMatching(): void
    {
        $this->adapter->set('canvas:v1:GET:/courses', ['data']);
        $this->adapter->set('canvas:v1:GET:/courses/123', ['data']);
        $this->adapter->set('canvas:v1:POST:/courses', ['data']);
        $this->adapter->set('canvas:v1:GET:/users', ['data']);

        // Delete all GET requests for courses
        $deleted = $this->adapter->deleteByPattern('canvas:v1:GET:/courses*');
        $this->assertEquals(2, $deleted);

        $this->assertFalse($this->adapter->has('canvas:v1:GET:/courses'));
        $this->assertFalse($this->adapter->has('canvas:v1:GET:/courses/123'));
        $this->assertTrue($this->adapter->has('canvas:v1:POST:/courses'));
        $this->assertTrue($this->adapter->has('canvas:v1:GET:/users'));
    }
}
