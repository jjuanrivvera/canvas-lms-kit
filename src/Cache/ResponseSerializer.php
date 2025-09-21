<?php

declare(strict_types=1);

namespace CanvasLMS\Cache;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Handles serialization and deserialization of PSR-7 Response objects.
 *
 * Converts Response objects to arrays for cache storage and reconstructs
 * Response objects from cached arrays.
 */
class ResponseSerializer
{
    /**
     * @var int Maximum body size to cache (1MB default)
     */
    private int $maxBodySize;

    /**
     * Constructor.
     *
     * @param int $maxBodySize Maximum response body size to cache in bytes
     */
    public function __construct(int $maxBodySize = 1048576)
    {
        $this->maxBodySize = $maxBodySize;
    }

    /**
     * Serialize a PSR-7 Response to an array.
     *
     * @param ResponseInterface $response The response to serialize
     *
     * @return array<string, mixed> The serialized response data
     */
    public function serialize(ResponseInterface $response): array
    {
        $body = $response->getBody();
        $bodySize = $body->getSize();

        // Check if body size exceeds limit
        if ($bodySize !== null && $bodySize > $this->maxBodySize) {
            // Don't cache large responses
            return [
                'cacheable' => false,
                'reason' => 'Response too large',
            ];
        }

        // Read body content and rewind stream
        $bodyContent = (string) $body;
        $body->rewind();

        return [
            'cacheable' => true,
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => $bodyContent,
            'version' => $response->getProtocolVersion(),
            'reason' => $response->getReasonPhrase(),
        ];
    }

    /**
     * Deserialize an array back to a PSR-7 Response.
     *
     * @param array<string, mixed> $data The serialized response data
     *
     * @return ResponseInterface|null The reconstructed response or null if not cacheable
     */
    public function deserialize(array $data): ?ResponseInterface
    {
        // Check if data was cacheable
        if (!isset($data['cacheable']) || !$data['cacheable']) {
            return null;
        }

        return new Response(
            $data['status'] ?? 200,
            $data['headers'] ?? [],
            $data['body'] ?? '',
            $data['version'] ?? '1.1',
            $data['reason'] ?? null
        );
    }
}
