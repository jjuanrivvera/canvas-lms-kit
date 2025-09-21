<?php

declare(strict_types=1);

namespace CanvasLMS\Cache\Strategies;

use Psr\Http\Message\RequestInterface;

/**
 * Generates unique cache keys for HTTP requests.
 *
 * Creates cache keys that include the request method, URL, query parameters,
 * and a hash of the authorization header to prevent cross-tenant pollution.
 */
class CacheKeyGenerator
{
    /**
     * @var string Cache key prefix
     */
    private string $prefix;

    /**
     * Constructor.
     *
     * @param string $prefix The cache key prefix (default: 'canvas')
     */
    public function __construct(string $prefix = 'canvas')
    {
        $this->prefix = $prefix;
    }

    /**
     * Generate a cache key for a request.
     *
     * @param RequestInterface     $request The HTTP request
     * @param array<string, mixed> $options Request options
     *
     * @return string The generated cache key
     */
    public function generate(RequestInterface $request, array $options = []): string
    {
        $parts = [
            $this->prefix,
            'v1',
            $request->getMethod(),
            $this->normalizeUrl($request),
            $this->getAuthHash($request),
            $this->getOptionsHash($options),
        ];

        // Remove empty parts
        $parts = array_filter($parts, fn ($part) => $part !== '');

        return implode(':', $parts);
    }

    /**
     * Normalize the URL for consistent cache keys.
     *
     * @param RequestInterface $request The request
     *
     * @return string The normalized URL
     */
    private function normalizeUrl(RequestInterface $request): string
    {
        $uri = $request->getUri();
        $url = $uri->getPath();

        // Add sorted query parameters for consistency
        $query = $uri->getQuery();
        if ($query) {
            parse_str($query, $params);
            ksort($params);
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Get a hash of the authorization header.
     *
     * @param RequestInterface $request The request
     *
     * @return string The auth hash (first 8 characters)
     */
    private function getAuthHash(RequestInterface $request): string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader)) {
            return '';
        }

        return substr(md5($authHeader), 0, 16);
    }

    /**
     * Get a hash of cache-affecting options.
     *
     * @param array<string, mixed> $options Request options
     *
     * @return string The options hash
     */
    private function getOptionsHash(array $options): string
    {
        // Only include options that affect the response
        $cacheAffectingOptions = [];

        // Add any custom headers that might affect response
        if (isset($options['headers'])) {
            foreach ($options['headers'] as $key => $value) {
                // Skip Authorization as it's already handled
                if (strtolower($key) !== 'authorization') {
                    $cacheAffectingOptions['h_' . $key] = $value;
                }
            }
        }

        // Add query parameters from options
        if (isset($options['query'])) {
            $cacheAffectingOptions['query'] = $options['query'];
        }

        if (empty($cacheAffectingOptions)) {
            return '';
        }

        ksort($cacheAffectingOptions);

        // Use JSON encoding for better performance than serialize()
        return substr(md5(json_encode($cacheAffectingOptions, JSON_UNESCAPED_SLASHES)), 0, 16);
    }
}
