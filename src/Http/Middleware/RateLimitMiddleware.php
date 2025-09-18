<?php

declare(strict_types=1);

namespace CanvasLMS\Http\Middleware;

use CanvasLMS\Config;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\Create;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware for handling Canvas API rate limits using a leaky bucket algorithm
 */
class RateLimitMiddleware extends AbstractMiddleware
{
    /**
     * @var array<string, array{remaining: int, cost: int, timestamp: float}>
     */
    private static array $buckets = [];

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'rate-limit';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'bucket_size' => 3000, // Canvas default bucket size
            'leak_rate' => 50, // Units leaked per second (3000/hour = ~0.83/sec, but Canvas is more generous)
            'initial_cost' => 50, // Canvas charges 50 units upfront
            'min_remaining' => 100, // Start throttling when this many units remain
            'wait_on_limit' => true, // Wait when rate limited instead of failing
            'max_wait_time' => 60, // Maximum seconds to wait for bucket to refill
        ];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(): callable
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!$this->getConfig('enabled', true)) {
                    return $handler($request, $options);
                }

                // Get bucket key from options or generate based on host and credential
                $bucketKey = $this->makeBucketKey($request, $options);

                // Check if we should delay before making the request
                $delay = $this->calculateDelay($bucketKey);
                if ($delay > 0) {
                    if (!$this->getConfig('wait_on_limit', true)) {
                        // Fail fast if configured
                        $response = new \GuzzleHttp\Psr7\Response(
                            429,
                            [],
                            "Rate limit would be exceeded. Would need to wait {$delay} seconds."
                        );

                        return Create::rejectionFor(new ClientException(
                            "Rate limit would be exceeded. Would need to wait {$delay} seconds.",
                            $request,
                            $response
                        ));
                    }

                    $maxWait = $this->getConfig('max_wait_time', 60);
                    if ($delay > $maxWait) {
                        $response = new \GuzzleHttp\Psr7\Response(
                            429,
                            [],
                            "Rate limit wait time ({$delay}s) exceeds maximum ({$maxWait}s)."
                        );

                        return Create::rejectionFor(new ClientException(
                            "Rate limit wait time ({$delay}s) exceeds maximum ({$maxWait}s).",
                            $request,
                            $response
                        ));
                    }

                    // Wait for the bucket to refill
                    usleep($delay * 1000000);
                }

                // Pre-charge the initial cost
                $this->consumeFromBucket($bucketKey, $this->getConfig('initial_cost', 50));

                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($bucketKey) {
                        // Update bucket based on response headers
                        $this->updateBucketFromResponse($bucketKey, $response);

                        return $response;
                    },
                    function ($reason) use ($bucketKey) {
                        // Refund the initial cost on failure (except for rate limit errors)
                        if (!$this->isRateLimitError($reason)) {
                            $this->refundToBucket($bucketKey, $this->getConfig('initial_cost', 50));
                        }

                        return Create::rejectionFor($reason);
                    }
                );
            };
        };
    }

    /**
     * Generate a bucket key based on the request host and credential fingerprint.
     * This ensures rate limits are properly isolated per host and credential.
     *
     * @param RequestInterface $request The request being made
     * @param array<string, mixed> $options Request options
     *
     * @return string The bucket key to use for rate limiting
     */
    private function makeBucketKey(RequestInterface $request, array $options): string
    {
        // Manual override takes precedence
        if (isset($options['rate_limit_bucket']) && is_string($options['rate_limit_bucket'])) {
            return $options['rate_limit_bucket'];
        }

        // Get host from request, fallback to config base URL host
        $requestHost = $request->getUri()->getHost() ?: '';
        $configHost = '';

        $baseUrl = Config::getBaseUrl();
        if ($baseUrl) {
            $configHost = parse_url($baseUrl, PHP_URL_HOST) ?: '';
        }

        $host = $requestHost !== '' ? $requestHost : $configHost;

        // If no host can be determined, use default
        if ($host === '') {
            return 'default';
        }

        // Generate credential fingerprint (non-sensitive hash)
        $fingerprint = '';

        if (Config::getAuthMode() === 'oauth') {
            $token = Config::getOAuthToken();
            if (!empty($token)) {
                // Use first 8 chars of SHA1 hash for security
                $fingerprint = substr(sha1($token), 0, 8);
            }
        } else {
            $appKey = Config::getApiKey();
            if (!empty($appKey)) {
                // Use first 8 chars of SHA1 hash for security
                $fingerprint = substr(sha1($appKey), 0, 8);
            }
        }

        // Return host_fingerprint or just host if no credential available
        return $fingerprint !== '' ? "{$host}_{$fingerprint}" : $host;
    }

    /**
     * Calculate delay needed before making a request
     *
     * @param string $bucketKey
     *
     * @return int Delay in seconds
     */
    private function calculateDelay(string $bucketKey): int
    {
        $bucket = $this->getBucket($bucketKey);
        $minRemaining = $this->getConfig('min_remaining', 100);

        // If we have enough capacity, no delay needed
        $initialCost = (int) $this->getConfig('initial_cost', 50);
        if ($bucket['remaining'] >= $minRemaining + $initialCost) {
            return 0;
        }

        // Calculate how long until we have enough capacity
        $needed = $minRemaining + $initialCost - $bucket['remaining'];
        $leakRate = $this->getConfig('leak_rate', 50);

        return (int) ceil($needed / $leakRate);
    }

    /**
     * Get or initialize a bucket
     *
     * @param string $bucketKey
     *
     * @return array{remaining: int, cost: int, timestamp: float}
     */
    private function getBucket(string $bucketKey): array
    {
        if (!isset(self::$buckets[$bucketKey])) {
            self::$buckets[$bucketKey] = [
                'remaining' => $this->getConfig('bucket_size', 3000),
                'cost' => 0,
                'timestamp' => microtime(true),
            ];
        }

        // Apply leak rate to refill bucket
        $now = microtime(true);
        $elapsed = $now - self::$buckets[$bucketKey]['timestamp'];
        $leaked = (int) ($elapsed * $this->getConfig('leak_rate', 50));

        if ($leaked > 0) {
            self::$buckets[$bucketKey]['remaining'] = min(
                $this->getConfig('bucket_size', 3000),
                self::$buckets[$bucketKey]['remaining'] + $leaked
            );
            self::$buckets[$bucketKey]['timestamp'] = $now;
        }

        return self::$buckets[$bucketKey];
    }

    /**
     * Consume units from the bucket
     *
     * @param string $bucketKey
     * @param int $cost
     *
     * @return void
     */
    private function consumeFromBucket(string $bucketKey, int $cost): void
    {
        $bucket = $this->getBucket($bucketKey);
        self::$buckets[$bucketKey]['remaining'] = max(0, $bucket['remaining'] - $cost);
        self::$buckets[$bucketKey]['cost'] = $cost;
    }

    /**
     * Refund units to the bucket
     *
     * @param string $bucketKey
     * @param int $cost
     *
     * @return void
     */
    private function refundToBucket(string $bucketKey, int $cost): void
    {
        $bucket = $this->getBucket($bucketKey);
        self::$buckets[$bucketKey]['remaining'] = min(
            $this->getConfig('bucket_size', 3000),
            $bucket['remaining'] + $cost
        );
    }

    /**
     * Update bucket state from Canvas response headers
     *
     * @param string $bucketKey
     * @param ResponseInterface $response
     *
     * @return void
     */
    private function updateBucketFromResponse(string $bucketKey, ResponseInterface $response): void
    {
        // Canvas provides rate limit info in headers
        if ($response->hasHeader('X-Rate-Limit-Remaining')) {
            $remaining = (int) $response->getHeaderLine('X-Rate-Limit-Remaining');
            self::$buckets[$bucketKey]['remaining'] = $remaining;
        }

        // Get actual cost from response
        if ($response->hasHeader('X-Request-Cost')) {
            $actualCost = (int) $response->getHeaderLine('X-Request-Cost');
            $initialCost = $this->getConfig('initial_cost', 50);

            // Refund the difference between initial and actual cost
            if ($actualCost < $initialCost) {
                $this->refundToBucket($bucketKey, $initialCost - $actualCost);
            } elseif ($actualCost > $initialCost) {
                // Consume additional cost if actual was higher
                $this->consumeFromBucket($bucketKey, $actualCost - $initialCost);
            }
        }
    }

    /**
     * Check if an error is a rate limit error
     *
     * @param mixed $reason
     *
     * @return bool
     */
    private function isRateLimitError($reason): bool
    {
        if ($reason instanceof \GuzzleHttp\Exception\RequestException) {
            $response = $reason->getResponse();
            if ($response && $response->getStatusCode() === 403) {
                // Check for Canvas rate limit indicators
                if ($response->hasHeader('X-Rate-Limit-Remaining')) {
                    $remaining = (int) $response->getHeaderLine('X-Rate-Limit-Remaining');

                    return $remaining <= 0;
                }

                $body = (string) $response->getBody();

                return strpos($body, 'Rate Limit Exceeded') !== false;
            }
        }

        return false;
    }

    /**
     * Reset rate limit buckets (useful for testing)
     *
     * @param string|null $bucketKey Specific bucket to reset, or null for all
     *
     * @return void
     */
    public static function resetBuckets(?string $bucketKey = null): void
    {
        if ($bucketKey === null) {
            self::$buckets = [];
        } else {
            unset(self::$buckets[$bucketKey]);
        }
    }
}
