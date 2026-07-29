<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Http\Middleware;

use CanvasLMS\Cache\Adapters\InMemoryAdapter;
use CanvasLMS\Cache\ResponseSerializer;
use CanvasLMS\Cache\Strategies\CacheKeyGenerator;
use CanvasLMS\Cache\Strategies\TtlStrategy;
use CanvasLMS\Http\Middleware\CacheMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class CacheMiddlewareTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Build a Guzzle Client wired with the given middleware and mock responses.
     *
     * @param CacheMiddleware   $middleware
     * @param Response[]        $responses
     *
     * @return array{Client, MockHandler}
     */
    private function buildClient(CacheMiddleware $middleware, array $responses): array
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push($middleware(), 'cache');

        $client = new Client([
            'handler' => $stack,
            'http_errors' => false,
            'base_uri' => 'https://canvas.example.com',
        ]);

        return [$client, $mock];
    }

    /**
     * Build a CacheMiddleware pre-configured with shared dependencies so tests
     * can also inspect the adapter directly.
     *
     * @param InMemoryAdapter $adapter
     * @param array<string, mixed> $extraConfig
     *
     * @return CacheMiddleware
     */
    private function buildMiddleware(InMemoryAdapter $adapter, array $extraConfig = []): CacheMiddleware
    {
        $ttlStrategy = new TtlStrategy(300);

        return new CacheMiddleware(array_merge([
            'enabled' => true,
            'adapter' => $adapter,
            'ttl_strategy' => $ttlStrategy,
            'serializer' => new ResponseSerializer(),
            'key_generator' => new CacheKeyGenerator('test'),
        ], $extraConfig));
    }

    // -----------------------------------------------------------------------
    // (a) Second identical GET returns cached response — handler called once
    // -----------------------------------------------------------------------

    public function testSecondIdenticalGetReturnsCachedResponseWithoutHittingHandler(): void
    {
        $adapter = new InMemoryAdapter();
        $middleware = $this->buildMiddleware($adapter);

        // Queue only ONE response; if the second call hits the handler it would throw.
        [$client, $mock] = $this->buildClient($middleware, [
            new Response(200, [], 'courses-payload'),
        ]);

        $first = $client->get('/api/v1/courses');
        $this->assertSame(200, $first->getStatusCode());
        $this->assertSame('courses-payload', (string) $first->getBody());

        // Second call — must be served from cache (no additional handler invocation).
        $second = $client->get('/api/v1/courses');
        $this->assertSame(200, $second->getStatusCode());
        $this->assertSame('courses-payload', (string) $second->getBody());

        // MockHandler queue is now empty, confirming the second call never reached it.
        $this->assertSame(0, $mock->count());
    }

    public function testCachedResponseBodyMatchesOriginal(): void
    {
        $adapter = new InMemoryAdapter();
        $middleware = $this->buildMiddleware($adapter);
        $body = '{"id":1,"name":"Bio 101"}';

        [$client] = $this->buildClient($middleware, [new Response(200, [], $body)]);

        $client->get('/api/v1/courses/1');
        $cached = $client->get('/api/v1/courses/1');

        $this->assertSame($body, (string) $cached->getBody());
    }

    // -----------------------------------------------------------------------
    // (b) Disabled middleware passes through without caching
    // -----------------------------------------------------------------------

    public function testDisabledMiddlewarePassesThroughBothRequests(): void
    {
        $adapter = new InMemoryAdapter();
        $middleware = new CacheMiddleware([
            'enabled' => false,  // explicitly disabled (default)
            'adapter' => $adapter,
            'ttl_strategy' => new TtlStrategy(300),
            'serializer' => new ResponseSerializer(),
            'key_generator' => new CacheKeyGenerator('test'),
        ]);

        // Two responses queued — both must be consumed because no caching occurs.
        [$client, $mock] = $this->buildClient($middleware, [
            new Response(200, [], 'first'),
            new Response(200, [], 'second'),
        ]);

        $r1 = $client->get('/api/v1/courses');
        $r2 = $client->get('/api/v1/courses');

        $this->assertSame('first', (string) $r1->getBody());
        $this->assertSame('second', (string) $r2->getBody());
        $this->assertSame(0, $mock->count(), 'Both mock responses should have been consumed');
    }

    public function testDefaultConfigurationIsDisabled(): void
    {
        // CacheMiddleware is opt-in — no config → disabled.
        $middleware = new CacheMiddleware();
        $config = new \ReflectionClass($middleware);
        $prop = $config->getProperty('config');
        $prop->setAccessible(true);
        $cfg = $prop->getValue($middleware);

        $this->assertFalse($cfg['enabled']);
    }

    // -----------------------------------------------------------------------
    // (c) POST invalidates related GET cache entry
    // -----------------------------------------------------------------------

    public function testPostRequestInvalidatesRelatedGetCacheEntry(): void
    {
        $adapter = new InMemoryAdapter();

        // Use the same CacheKeyGenerator the middleware uses so we can plant a
        // realistic cache key and verify it gets deleted on mutation.
        // invalidateOnMutation requires a path matching /api/v1/{resource}/{id}
        // to extract the resource type. Using courses/123 satisfies this.
        $keyGenerator = new CacheKeyGenerator('test');
        $middleware = $this->buildMiddleware($adapter, ['key_generator' => $keyGenerator]);

        $serializer = new ResponseSerializer();

        // Plant GET /api/v1/courses list entry — matches pattern *:GET:/api/v1/courses*
        $listRequest = new Request('GET', 'https://canvas.example.com/api/v1/courses');
        $listKey = $keyGenerator->generate($listRequest);
        $adapter->set($listKey, $serializer->serialize(new Response(200, [], 'courses-list')));

        // Plant GET /api/v1/courses/123 entry — also matched by *:GET:/api/v1/courses*
        $itemRequest = new Request('GET', 'https://canvas.example.com/api/v1/courses/123');
        $itemKey = $keyGenerator->generate($itemRequest);
        $adapter->set($itemKey, $serializer->serialize(new Response(200, [], 'course-123')));

        $this->assertNotNull($adapter->get($listKey), 'Pre-condition: list key must exist');
        $this->assertNotNull($adapter->get($itemKey), 'Pre-condition: item key must exist');

        // POST to /api/v1/courses/123 — triggers invalidateOnMutation with resource=courses, id=123
        [$client] = $this->buildClient($middleware, [
            new Response(201, [], 'created'),
        ]);

        $postResponse = $client->post('/api/v1/courses/123');
        $this->assertSame(201, $postResponse->getStatusCode());

        // Both GET entries should be removed via deleteByPattern('*:GET:/api/v1/courses*')
        $this->assertNull($adapter->get($listKey), 'GET list entry must be invalidated after POST');
        $this->assertNull($adapter->get($itemKey), 'GET item entry must be invalidated after POST');
    }

    // -----------------------------------------------------------------------
    // (d) Non-2xx responses are not cached when cache_success_only=true
    // -----------------------------------------------------------------------

    public function testNon2xxResponseIsNotCachedWhenCacheSuccessOnlyEnabled(): void
    {
        $adapter = new InMemoryAdapter();
        $middleware = $this->buildMiddleware($adapter, ['cache_success_only' => true]);

        // First call returns 500 — should NOT be cached.
        // Second call returns 200 — the mock must be consumed twice.
        [$client, $mock] = $this->buildClient($middleware, [
            new Response(500, [], 'error'),
            new Response(200, [], 'ok'),
        ]);

        $first = $client->get('/api/v1/courses');
        $this->assertSame(500, $first->getStatusCode());

        $second = $client->get('/api/v1/courses');
        $this->assertSame(200, $second->getStatusCode());

        // Both responses consumed — 500 was not cached and didn't short-circuit the second call.
        $this->assertSame(0, $mock->count());
    }

    public function testSuccessful2xxResponseIsCached(): void
    {
        $adapter = new InMemoryAdapter();
        $middleware = $this->buildMiddleware($adapter, ['cache_success_only' => true]);

        [$client, $mock] = $this->buildClient($middleware, [
            new Response(200, [], 'success'),
        ]);

        $client->get('/api/v1/courses');
        $second = $client->get('/api/v1/courses');

        $this->assertSame(200, $second->getStatusCode());
        // Only 1 mock response queued and not thrown — second came from cache.
        $this->assertSame(0, $mock->count());
    }

    public function test404ResponseIsNotCachedWhenCacheSuccessOnlyEnabled(): void
    {
        $adapter = new InMemoryAdapter();
        $middleware = $this->buildMiddleware($adapter, ['cache_success_only' => true]);

        [$client, $mock] = $this->buildClient($middleware, [
            new Response(404, [], 'not found'),
            new Response(404, [], 'not found again'),
        ]);

        $first = $client->get('/api/v1/courses/999');
        $second = $client->get('/api/v1/courses/999');

        $this->assertSame(404, $first->getStatusCode());
        $this->assertSame(404, $second->getStatusCode());
        $this->assertSame(0, $mock->count(), '404 must not be cached; both calls hit the handler');
    }

    // -----------------------------------------------------------------------
    // Adapter accessors
    // -----------------------------------------------------------------------

    public function testGetAdapterReturnsConfiguredAdapter(): void
    {
        $adapter = new InMemoryAdapter();
        $middleware = $this->buildMiddleware($adapter);

        $this->assertSame($adapter, $middleware->getAdapter());
    }

    public function testSetAdapterReplacesAdapter(): void
    {
        $original = new InMemoryAdapter();
        $replacement = new InMemoryAdapter();

        $middleware = $this->buildMiddleware($original);
        $middleware->setAdapter($replacement);

        $this->assertSame($replacement, $middleware->getAdapter());
    }

    public function testGetStatisticsReturnsAdapterStats(): void
    {
        $adapter = new InMemoryAdapter();
        $middleware = $this->buildMiddleware($adapter);

        $stats = $middleware->getStatistics();

        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('entries', $stats);
        $this->assertArrayHasKey('size', $stats);
    }

    public function testClearCacheRemovesAllEntries(): void
    {
        $adapter = new InMemoryAdapter();
        $middleware = $this->buildMiddleware($adapter);

        [$client] = $this->buildClient($middleware, [
            new Response(200, [], 'data'),
        ]);

        $client->get('/api/v1/courses');

        $this->assertSame(1, $middleware->getStatistics()['entries']);

        $middleware->clearCache();

        $this->assertSame(0, $middleware->getStatistics()['entries']);
    }
}
