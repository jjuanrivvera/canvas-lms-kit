<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Cache;

use CanvasLMS\Cache\ResponseSerializer;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ResponseSerializerTest extends TestCase
{
    private ResponseSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = new ResponseSerializer();
    }

    // -----------------------------------------------------------------------
    // serialize()
    // -----------------------------------------------------------------------

    public function testSerializePreservesStatusCode(): void
    {
        $response = new Response(201, [], 'created');
        $data = $this->serializer->serialize($response);

        $this->assertTrue($data['cacheable']);
        $this->assertSame(201, $data['status']);
    }

    public function testSerializePreservesHeaders(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json', 'X-Custom' => 'value'], '{}');
        $data = $this->serializer->serialize($response);

        $this->assertArrayHasKey('headers', $data);
        $this->assertArrayHasKey('Content-Type', $data['headers']);
        $this->assertSame(['application/json'], $data['headers']['Content-Type']);
        $this->assertSame(['value'], $data['headers']['X-Custom']);
    }

    public function testSerializePreservesBody(): void
    {
        $body = '{"courses":[{"id":1}]}';
        $response = new Response(200, [], $body);
        $data = $this->serializer->serialize($response);

        $this->assertSame($body, $data['body']);
    }

    public function testSerializePreservesProtocolVersion(): void
    {
        $response = new Response(200, [], '', '1.0');
        $data = $this->serializer->serialize($response);

        $this->assertSame('1.0', $data['version']);
    }

    public function testSerializePreservesReasonPhrase(): void
    {
        $response = new Response(404, [], '', '1.1', 'Not Found');
        $data = $this->serializer->serialize($response);

        $this->assertSame('Not Found', $data['reason']);
    }

    public function testBodyRemainsReadableAfterSerialize(): void
    {
        // The stream should be rewound after serialize reads it, so reading
        // the original response body again should still return the content.
        $body = 'rewind-me';
        $response = new Response(200, [], $body);

        $this->serializer->serialize($response);

        $this->assertSame($body, (string) $response->getBody());
    }

    public function testSerializeReturnsCacheableFlagTrue(): void
    {
        $response = new Response(200, [], 'ok');
        $data = $this->serializer->serialize($response);

        $this->assertArrayHasKey('cacheable', $data);
        $this->assertTrue($data['cacheable']);
    }

    public function testSerializeReturnsNonCacheableWhenBodyExceedsMaxSize(): void
    {
        // Construct a serializer with a tiny max-body limit.
        $smallSerializer = new ResponseSerializer(10);

        // Guzzle's stream knows its size when built from a string.
        $body = str_repeat('x', 11); // 11 bytes > 10 byte limit
        $response = new Response(200, [], $body);

        $data = $smallSerializer->serialize($response);

        $this->assertArrayHasKey('cacheable', $data);
        $this->assertFalse($data['cacheable']);
    }

    // -----------------------------------------------------------------------
    // deserialize()
    // -----------------------------------------------------------------------

    public function testDeserializeRoundTripPreservesStatusHeadersAndBody(): void
    {
        $original = new Response(200, ['Content-Type' => 'application/json'], '{"id":1}');
        $serialized = $this->serializer->serialize($original);
        $restored = $this->serializer->deserialize($serialized);

        $this->assertNotNull($restored);
        $this->assertSame(200, $restored->getStatusCode());
        $this->assertSame(['application/json'], $restored->getHeader('Content-Type'));
        $this->assertSame('{"id":1}', (string) $restored->getBody());
    }

    public function testDeserializeReturnsNullWhenCacheableFalse(): void
    {
        $data = ['cacheable' => false, 'reason' => 'Response too large'];
        $result = $this->serializer->deserialize($data);

        $this->assertNull($result);
    }

    public function testDeserializeReturnsNullWhenCacheableKeyMissing(): void
    {
        $data = ['status' => 200, 'headers' => [], 'body' => 'no-cacheable-flag'];
        $result = $this->serializer->deserialize($data);

        $this->assertNull($result);
    }

    public function testDeserializeUsesDefaultsForMissingFields(): void
    {
        $data = ['cacheable' => true];  // minimal envelope
        $result = $this->serializer->deserialize($data);

        $this->assertNotNull($result);
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('', (string) $result->getBody());
    }

    public function testDeserializePreservesProtocolVersion(): void
    {
        $original = new Response(200, [], '', '2.0');
        $serialized = $this->serializer->serialize($original);
        $restored = $this->serializer->deserialize($serialized);

        $this->assertNotNull($restored);
        $this->assertSame('2.0', $restored->getProtocolVersion());
    }

    public function testDeserializePreservesReasonPhrase(): void
    {
        $original = new Response(422, [], '', '1.1', 'Unprocessable Entity');
        $serialized = $this->serializer->serialize($original);
        $restored = $this->serializer->deserialize($serialized);

        $this->assertNotNull($restored);
        $this->assertSame('Unprocessable Entity', $restored->getReasonPhrase());
    }

    // -----------------------------------------------------------------------
    // Full round-trip integration
    // -----------------------------------------------------------------------

    public function testFullRoundTripWithComplexResponse(): void
    {
        $original = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                'Link' => '<https://canvas.example.com/api/v1/courses?page=2>; rel="next"',
            ],
            '{"courses":[{"id":1,"name":"Bio 101"},{"id":2,"name":"Chem 201"}]}'
        );

        $serialized = $this->serializer->serialize($original);
        $restored = $this->serializer->deserialize($serialized);

        $this->assertNotNull($restored);
        $this->assertSame($original->getStatusCode(), $restored->getStatusCode());
        $this->assertSame((string) $original->getBody(), (string) $restored->getBody());
        $this->assertSame($original->getHeaders(), $restored->getHeaders());
    }

    public function testBodySizeAtExactLimitIsCacheable(): void
    {
        $maxSize = 100;
        $serializer = new ResponseSerializer($maxSize);

        // Exactly at limit — must be cacheable
        $body = str_repeat('a', $maxSize);
        $response = new Response(200, [], $body);
        $data = $serializer->serialize($response);

        $this->assertTrue($data['cacheable']);
    }

    public function testBodySizeOneOverLimitIsNotCacheable(): void
    {
        $maxSize = 100;
        $serializer = new ResponseSerializer($maxSize);

        $body = str_repeat('a', $maxSize + 1);
        $response = new Response(200, [], $body);
        $data = $serializer->serialize($response);

        $this->assertFalse($data['cacheable']);
    }
}
